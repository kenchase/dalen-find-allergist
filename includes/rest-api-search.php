<?php

/**
 * REST API Search Endpoints for Find an Allergist Plugin
 * 
 * @package FAA
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function () {
    register_rest_route('faa/v1', '/physicians/search', [
        'methods'  => 'GET',
        'callback' => 'faa_physician_search',
        'permission_callback' => '__return_true',
        'args' => [
            'name'         => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
            'city'         => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
            'province'     => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
            'postal'       => ['required' => false, 'sanitize_callback' => 'faa_sanitize_postal'],
            'kms'          => ['required' => false, 'sanitize_callback' => 'absint'],
            'prac_pop'     => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        ],
    ]);
});

/**
 * Sanitize postal code for search
 */
function faa_sanitize_postal($value)
{
    return strtoupper(preg_replace('/\s+/', '', (string)$value));
}

/**
 * Simple geocoding for Canadian postal codes
 */
function faa_geocode_postal($postal_code)
{
    if (empty($postal_code)) {
        return null;
    }

    $api_key = faa_get_google_maps_api_key();
    if (empty($api_key)) {
        error_log('FAA: Google Maps API key not configured for geocoding');
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

    $response = wp_remote_get($url, [
        'timeout' => 10,
        'headers' => [
            'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url()
        ]
    ]);

    if (is_wp_error($response)) {
        error_log('FAA: Geocoding API request failed: ' . $response->get_error_message());
        return null;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (!$data) {
        error_log('FAA: Invalid JSON response from geocoding API');
        return null;
    }

    if ($data['status'] !== 'OK') {
        error_log('FAA: Geocoding API error: ' . $data['status'] . ' for postal code: ' . $postal_code);
        return null;
    }

    if (empty($data['results'])) {
        error_log('FAA: No geocoding results for postal code: ' . $postal_code);
        return null;
    }

    $location = $data['results'][0]['geometry']['location'];
    return [
        'lat' => (float) $location['lat'],
        'lng' => (float) $location['lng']
    ];
}

/**
 * Calculate distance between two points using Haversine formula
 */
function faa_haversine_distance($lat1, $lng1, $lat2, $lng2)
{
    // Validate coordinates
    if (!is_numeric($lat1) || !is_numeric($lng1) || !is_numeric($lat2) || !is_numeric($lng2)) {
        return false;
    }

    // Convert to float and validate ranges
    $lat1 = (float) $lat1;
    $lng1 = (float) $lng1;
    $lat2 = (float) $lat2;
    $lng2 = (float) $lng2;

    // Validate latitude range (-90 to 90)
    if ($lat1 < -90 || $lat1 > 90 || $lat2 < -90 || $lat2 > 90) {
        return false;
    }

    // Validate longitude range (-180 to 180)
    if ($lng1 < -180 || $lng1 > 180 || $lng2 < -180 || $lng2 > 180) {
        return false;
    }

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
 * Normalize city name for comparison (case insensitive, remove accents, trim spaces)
 */
function faa_normalize_city_name($city_name)
{
    if (empty($city_name)) {
        return '';
    }

    // Trim spaces
    $normalized = trim($city_name);

    // Convert to lowercase
    $normalized = strtolower($normalized);

    // Remove accents/diacritics
    $normalized = remove_accents($normalized);

    return $normalized;
}

/**
 * Check if an organization matches the search criteria
 */
function faa_organization_matches_search($org, $city = null, $province = null, $postal = null, $prac_pop = null)
{
    $gmap = $org['institution_gmap'] ?? [];

    // Check city
    if ($city && !empty($gmap['city'])) {
        $normalized_search_city = faa_normalize_city_name($city);
        $normalized_stored_city = faa_normalize_city_name($gmap['city']);

        if ($normalized_search_city !== $normalized_stored_city) {
            return false;
        }
    }

    // Check province - case sensitive match required
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

    // Check practice population
    if ($prac_pop) {
        $practice_population = $org['institution_practice_population'] ?? '';

        // Handle empty or null values
        if (empty($practice_population)) {
            return false;
        }

        // ACF select field can return string (single select) or array (multi-select)
        if (is_array($practice_population)) {
            // Multi-select: check if the search value is in the array
            if (!in_array($prac_pop, $practice_population, true)) {
                return false;
            }
        } else {
            // Single select: exact match comparison
            if ((string)$practice_population !== $prac_pop) {
                return false;
            }
        }
    }

    return true;
}

/**
 * REST API endpoint handler for physician search
 */
function faa_physician_search(WP_REST_Request $req)
{
    // Sanitize inputs
    $name = trim($req->get_param('name') ?? '');
    $city = trim($req->get_param('city') ?? '');
    $province = trim($req->get_param('province') ?? '');
    $postal = trim($req->get_param('postal') ?? '');
    $kms = absint($req->get_param('kms') ?? 0);
    $prac_pop = trim($req->get_param('prac_pop') ?? '');

    // Require at least one search criterion
    if (empty($name) && empty($city) && empty($province) && empty($postal) && $kms === 0 && empty($prac_pop)) {
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

    // Debug: Log initial physician count
    error_log('FAA:  Initial physicians found: ' . count($physicians));

    // Filter by name if provided
    if (!empty($name)) {
        $physicians = array_filter($physicians, function ($physician) use ($name) {
            $title = strtolower(get_the_title($physician));
            $search_name = strtolower($name);

            // Check if the search name matches any part of the physician title
            return stripos($title, $search_name) !== false;
        });
    }

    // Filter by practice population if provided
    if (!empty($prac_pop)) {
        $physicians = array_filter($physicians, function ($physician) use ($prac_pop) {
            $organizations = get_field('organizations_details', $physician->ID) ?: [];

            // Check if any organization has matching practice population
            foreach ($organizations as $org) {
                $practice_population = $org['institution_practice_population'] ?? '';

                // Handle empty or null values
                if (empty($practice_population)) {
                    continue;
                }

                // ACF select field can return string (single select) or array (multi-select)
                if (is_array($practice_population)) {
                    // Multi-select: check if the search value is in the array
                    if (in_array($prac_pop, $practice_population, true)) {
                        return true;
                    }
                } else {
                    // Single select: exact match comparison
                    if ((string)$practice_population === $prac_pop) {
                        return true;
                    }
                }
            }

            return false;
        });
    }

    // Filter by location criteria (only if city or province specified)
    if (!empty($city) || !empty($province)) {
        $physicians = array_filter($physicians, function ($physician) use ($city, $province, $prac_pop) {
            $organizations = get_field('organizations_details', $physician->ID) ?: [];

            foreach ($organizations as $org) {
                if (faa_organization_matches_search($org, $city, $province, null, $prac_pop)) {
                    return true;
                }
            }

            return false;
        });

        error_log('FAA:  After location filtering: ' . count($physicians) . ' physicians');
    }

    // Geocode postal code for distance calculations if needed
    $origin_coords = null;
    if (!empty($postal) && $kms > 0) {
        error_log('FAA:  Attempting to geocode postal: ' . $postal);
        $origin_coords = faa_geocode_postal($postal);

        // If geocoding fails, we can't do distance filtering
        if (!$origin_coords) {
            error_log('FAA:  Geocoding failed for postal: ' . $postal);
            return new WP_Error('geocoding_failed', 'Unable to geocode the provided postal code for distance calculation.', ['status' => 400]);
        } else {
            error_log('FAA:  Geocoded coordinates: ' . json_encode($origin_coords));
        }
    }

    // Build response
    $results = array_map(function ($physician) use ($city, $province, $postal, $origin_coords, $kms, $prac_pop) {
        $organizations = get_field('organizations_details', $physician->ID) ?: [];

        // Debug: Log organization count for this physician
        error_log('FAA:  Physician ID ' . $physician->ID . ' has ' . count($organizations) . ' organizations');

        // Filter organizations based on search criteria
        $filtered_orgs = [];

        foreach ($organizations as $org) {
            // For distance-based searches with only postal code and no city/province, 
            // skip location matching and rely only on distance calculation
            $location_match = true;

            // Only apply location filtering if city or province is specified
            if (!empty($city) || !empty($province)) {
                $location_match = faa_organization_matches_search($org, $city, $province, null, null);
            }

            // Check practice population for each organization
            $practice_population_match = true;
            if (!empty($prac_pop)) {
                $practice_population_match = faa_organization_matches_search($org, null, null, null, $prac_pop);
            }

            // Check distance if needed
            $distance_match = true;
            $distance_km = null;

            if ($origin_coords && $kms > 0) {
                $gmap = $org['institution_gmap'] ?? [];

                if (!empty($gmap['lat']) && !empty($gmap['lng'])) {
                    $distance_km = faa_haversine_distance(
                        $origin_coords['lat'],
                        $origin_coords['lng'],
                        (float)$gmap['lat'],
                        (float)$gmap['lng']
                    );

                    // Check if distance calculation was successful and within range
                    if ($distance_km !== false && $distance_km <= $kms) {
                        $distance_match = true;
                        error_log('FAA:  Organization matches distance (' . round($distance_km, 1) . 'km <= ' . $kms . 'km)');
                    } else {
                        $distance_match = false;
                        if ($distance_km !== false) {
                            error_log('FAA:  Organization outside distance (' . round($distance_km, 1) . 'km > ' . $kms . 'km)');
                        } else {
                            error_log('FAA:  Distance calculation failed for organization');
                        }
                    }
                } else {
                    // No coordinates available, can't calculate distance
                    $distance_match = false;
                    error_log('FAA:  Organization missing coordinates');
                }
            }

            // Include organization if it matches all criteria
            if ($location_match && $practice_population_match && $distance_match) {
                if ($distance_km !== null && $distance_km !== false) {
                    $org['distance_km'] = round($distance_km, 1);
                }
                $filtered_orgs[] = $org;
            }
        }

        error_log('FAA:  Physician ID ' . $physician->ID . ' has ' . count($filtered_orgs) . ' matching organizations');

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

    // Filter out physicians with no matching organizations
    $results = array_filter($results, function ($result) {
        return !empty($result['acf']['organizations_details']);
    });

    error_log('FAA:  Final result count: ' . count($results));

    return rest_ensure_response([
        'total_results' => count($results),
        'results'       => array_values($results),
    ]);
}
