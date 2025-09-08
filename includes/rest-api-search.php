<?php

/**
 * REST API Search Endpoints for Dalen Find Allergist Plugin
 * 
 * @package Dalen_Find_Allergist
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function () {
    register_rest_route('dalen/v1', '/physicians/search', [
        'methods'  => 'GET',
        'callback' => 'dalen_physician_search',
        'permission_callback' => '__return_true',
        'args' => [
            'fname'    => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
            'lname'    => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
            'city'     => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
            'province' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
            'postal'   => ['required' => false, 'sanitize_callback' => 'dalen_sanitize_postal'],
            'kms'      => ['required' => false, 'sanitize_callback' => 'absint'],
        ],
    ]);
});

/**
 * Sanitize postal code for search
 */
function dalen_sanitize_postal($value)
{
    return strtoupper(preg_replace('/\s+/', '', (string)$value));
}

/**
 * Simple geocoding for Canadian postal codes
 */
function dalen_geocode_postal($postal_code)
{
    if (empty($postal_code)) {
        return null;
    }

    $api_key = dalen_get_google_maps_api_key();
    if (empty($api_key)) {
        return null;
    }

    // Format postal code for Google API
    $clean_postal = strtoupper(preg_replace('/\s+/', '', $postal_code));
    if (strlen($clean_postal) === 6) {
        $formatted_postal = substr($clean_postal, 0, 3) . ' ' . substr($clean_postal, 3, 3);
    } else {
        $formatted_postal = $clean_postal;
    }

    $address = urlencode($formatted_postal . ', Canada');
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$api_key}";

    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        return null;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if ($data['status'] === 'OK' && !empty($data['results'])) {
        $location = $data['results'][0]['geometry']['location'];
        return [
            'lat' => (float) $location['lat'],
            'lng' => (float) $location['lng']
        ];
    }

    return null;
}

/**
 * Calculate distance between two points using Haversine formula
 */
function dalen_haversine_distance($lat1, $lng1, $lat2, $lng2)
{
    $earth_radius = 6371; // Earth's radius in kilometers

    $lat1 = deg2rad($lat1);
    $lng1 = deg2rad($lng1);
    $lat2 = deg2rad($lat2);
    $lng2 = deg2rad($lng2);

    $dlat = $lat2 - $lat1;
    $dlng = $lng2 - $lng1;

    $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlng / 2) * sin($dlng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earth_radius * $c;
}

/**
 * Check if an organization matches the search criteria
 */
function dalen_organization_matches_search($org, $city = null, $province = null, $postal = null)
{
    $gmap = $org['institution_gmap'] ?? [];

    // Check city
    if ($city && !empty($gmap['city'])) {
        if (stripos($gmap['city'], $city) === false) {
            return false;
        }
    }

    // Check province - exact match required
    if ($province && ($gmap['state_short'] ?? '') !== $province) {
        return false;
    }

    // Check postal code
    if ($postal && !empty($gmap['post_code'])) {
        $stored_postal = str_replace(' ', '', $gmap['post_code']);
        $search_postal = str_replace(' ', '', $postal);
        if (
            stripos($stored_postal, $search_postal) === false &&
            stripos($stored_postal, substr($search_postal, 0, 3)) === false
        ) {
            return false;
        }
    }

    return true;
}

/**
 * REST API endpoint handler for physician search
 */
function dalen_physician_search(WP_REST_Request $req)
{
    // Sanitize inputs
    $fname = trim($req->get_param('fname') ?? '');
    $lname = trim($req->get_param('lname') ?? '');
    $city = trim($req->get_param('city') ?? '');
    $province = trim($req->get_param('province') ?? '');
    $postal = trim($req->get_param('postal') ?? '');
    $kms = absint($req->get_param('kms') ?? 0);

    // Require at least one search criterion
    if (empty($fname) && empty($lname) && empty($city) && empty($province) && empty($postal) && $kms === 0) {
        return new WP_Error('missing_criteria', 'Please provide at least one search criterion.', ['status' => 400]);
    }

    // Validate distance parameter
    if ($kms > 0 && ($kms < 1 || $kms > 500)) {
        return new WP_Error('invalid_distance', 'Distance must be between 1 and 500 kilometers.', ['status' => 400]);
    }

    // Get all physicians (excluding those marked as not searchable)
    $query_args = [
        'post_type'      => 'physicians',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'no_found_rows'  => true,
        'meta_query'     => [
            [
                'relation' => 'OR',
                [
                    'key'     => 'immunologist_online_search_tool',
                    'value'   => 'YES',
                    'compare' => '!='
                ],
                [
                    'key'     => 'immunologist_online_search_tool',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ]
    ];

    $physicians = get_posts($query_args);

    // Filter by name if provided
    if (!empty($fname) || !empty($lname)) {
        $physicians = array_filter($physicians, function ($physician) use ($fname, $lname) {
            $title = strtolower(get_the_title($physician));

            if (!empty($fname) && stripos($title, strtolower($fname)) === false) {
                return false;
            }

            if (!empty($lname) && stripos($title, strtolower($lname)) === false) {
                return false;
            }

            return true;
        });
    }

    // Filter by location criteria
    if (!empty($city) || !empty($province) || !empty($postal)) {
        $physicians = array_filter($physicians, function ($physician) use ($city, $province, $postal) {
            $organizations = get_field('organizations_details', $physician->ID) ?: [];

            foreach ($organizations as $org) {
                if (dalen_organization_matches_search($org, $city, $province, $postal)) {
                    return true;
                }
            }

            return false;
        });
    }

    // Filter by distance if postal code and radius provided
    $origin_coords = null;
    if (!empty($postal) && $kms > 0) {
        $origin_coords = dalen_geocode_postal($postal);

        if ($origin_coords) {
            $physicians = array_filter($physicians, function ($physician) use ($origin_coords, $kms) {
                $organizations = get_field('organizations_details', $physician->ID) ?: [];

                foreach ($organizations as $org) {
                    $gmap = $org['institution_gmap'] ?? [];

                    if (!empty($gmap['lat']) && !empty($gmap['lng'])) {
                        $distance = dalen_haversine_distance(
                            $origin_coords['lat'],
                            $origin_coords['lng'],
                            (float)$gmap['lat'],
                            (float)$gmap['lng']
                        );

                        if ($distance <= $kms) {
                            return true;
                        }
                    }
                }

                return false;
            });
        }
    }

    // Build response
    $results = array_map(function ($physician) use ($city, $province, $postal, $origin_coords) {
        $organizations = get_field('organizations_details', $physician->ID) ?: [];

        // Filter organizations to only include matching ones
        $filtered_orgs = array_filter($organizations, function ($org) use ($city, $province, $postal) {
            return dalen_organization_matches_search($org, $city, $province, $postal);
        });

        // Add distance information if we have origin coordinates
        if ($origin_coords) {
            foreach ($filtered_orgs as &$org) {
                $gmap = $org['institution_gmap'] ?? [];

                if (!empty($gmap['lat']) && !empty($gmap['lng'])) {
                    $distance = dalen_haversine_distance(
                        $origin_coords['lat'],
                        $origin_coords['lng'],
                        (float)$gmap['lat'],
                        (float)$gmap['lng']
                    );
                    $org['distance_km'] = round($distance, 1);
                }
            }
        }

        return [
            'id'    => $physician->ID,
            'title' => get_the_title($physician),
            'link'  => get_permalink($physician),
            'acf'   => [
                'credentials' => get_post_meta($physician->ID, 'physician_credentials', true),
                'organizations_details' => array_values($filtered_orgs),
            ],
        ];
    }, $physicians);

    return rest_ensure_response([
        'total_results' => count($results),
        'results'       => array_values($results),
    ]);
}
