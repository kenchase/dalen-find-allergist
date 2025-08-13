<?php
// /wp-content/mu-plugins/physicians-search.php

add_action('rest_api_init', function () {
    register_rest_route('my/v1', '/physicians/search', [
        'methods'  => 'GET',
        'callback' => 'my_physician_search',
        'permission_callback' => '__return_true',
        'args' => [
            // Title parts (mapped from your form)
            'fname' => ['required' => false,  'sanitize_callback' => 'sanitize_text_field'],
            'lname' => ['required' => false,  'sanitize_callback' => 'sanitize_text_field'],

            // ACF-backed filters (all optional)
            'oit'       => ['required' => false, 'sanitize_callback' => 'rest_sanitize_boolean'],
            'city'      => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
            'province'  => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
            'postal'    => ['required' => false, 'sanitize_callback' => 'my_sanitize_postal'],
            'kms'       => ['required' => false, 'sanitize_callback' => 'absint'], // distance filter in kilometers
        ],
    ]);
});

/**
 * Normalize Canadian postal codes like "M5V 3A8" -> "M5V 3A8" (uppercase, single space) or "M5V3A8" if you prefer.
 */
function my_sanitize_postal($value)
{
    $v = strtoupper(preg_replace('/\s+/', '', (string)$value));
    // optionally validate: if (!preg_match('/^[ABCEGHJ-NPRSTVXY]\d[ABCEGHJ-NPRSTV-Z]\d[ABCEGHJ-NPRSTV-Z]\d$/', $v)) { return ''; }
    return $v; // store without space; compare with LIKE to be flexible
}

/**
 * Simple geocoding function for Canadian postal codes
 * You might want to cache results or use a postal code database
 */
function my_geocode_postal($postal_code)
{
    if (empty($postal_code)) {
        return null;
    }

    // Normalize postal code - remove spaces and convert to uppercase
    $clean_postal = strtoupper(preg_replace('/\s+/', '', $postal_code));

    // Format postal code for Google API (add space: "K1A0A6" -> "K1A 0A6")
    if (strlen($clean_postal) === 6) {
        $formatted_postal = substr($clean_postal, 0, 3) . ' ' . substr($clean_postal, 3, 3);
    } else {
        $formatted_postal = $clean_postal; // Use as-is if not 6 characters
    }

    // Use Google Geocoding API
    $api_key = 'AIzaSyDxGyqMkrVCU7C65nKSHqaI0pXKGhgCW1Q'; // Same key from shortcodes.php
    $address = urlencode($formatted_postal . ', Canada');
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$api_key}";

    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return null;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

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
 * Returns distance in kilometers
 */
function my_haversine_distance($lat1, $lng1, $lat2, $lng2)
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

function my_physician_search(WP_REST_Request $req)
{
    $fname = trim((string) $req['fname']);
    $lname = trim((string) $req['lname']);
    $full  = trim("$fname $lname");

    // Require at least one search criterion
    if (empty($fname) && empty($lname) && empty($req['city']) && empty($req['province']) && empty($req['postal']) && empty($req['kms'])) {
        return new WP_Error('missing_criteria', 'Please provide at least one search criterion.', ['status' => 400]);
    }

    $meta_query = ['relation' => 'AND'];

    // Exclude records where immunologist_online_search_tool is "YES"
    $meta_query[] = [
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
    ];

    // Map your form fields to ACF keys. (Change these to YOUR actual ACF field names.)
    // Map request params -> your ACF meta keys
    $acf_keys = [
        'oit'      => 'practices_oral_immunotherapy_oit',
        'city'     => 'physician_city',
        'province' => 'physician_province',
        // Note: postal is handled separately as it searches within organizations_details
    ];

    // OIT filtering: only filter when checkbox is selected (true)
    if ($req['oit'] === true) {
        // Only return records where the practices_oral_immunotherapy_oit array contains "OIT"
        $meta_query[] = [
            'key'     => $acf_keys['oit'],
            'value'   => 'OIT',
            'compare' => 'LIKE'
        ];
    }
    // If OIT checkbox is not selected, the OIT field value doesn't impact results

    // City/province: exact or partial; choose one. Here we do case-insensitive partial:
    if (!empty($req['city'])) {
        $meta_query[] = [
            'key'     => $acf_keys['city'],
            'value'   => $req['city'],
            'compare' => 'LIKE',
        ];
    }
    if (!empty($req['province'])) {
        $province_search = trim($req['province']);
        $meta_query[] = [
            'key'     => $acf_keys['province'],
            'value'   => $province_search,
            'compare' => 'LIKE',
        ];
    }

    // Note: postal code search is handled post-query for better performance

    // Store postal search for post-query filtering if meta query fails
    $postal_search_term = !empty($req['postal']) ? $req['postal'] : null;

    // Remove postal from meta_query for now - we'll filter after the query
    $meta_query_without_postal = array_filter($meta_query, function ($query) {
        return !isset($query['relation']) || $query['relation'] !== 'OR' ||
            !isset($query[0]['key']) || strpos($query[0]['key'], 'post_code') === false;
    });

    $posts = [];

    // If we have a name to search for, use title-based search only
    if (!empty(trim($full))) {
        // Get all physicians first, then filter by title
        $q = new WP_Query([
            'post_type'      => 'physicians',
            'post_status'    => 'publish',
            'meta_query'     => $meta_query_without_postal,
            'posts_per_page' => -1, // Get all posts to filter
            'no_found_rows'  => true,
        ]);

        // Filter by title matching
        $posts = array_values(array_filter($q->posts, function ($p) use ($fname, $lname) {
            $title = mb_strtolower(get_the_title($p));

            // Check if both first and last name are in the title (if both provided)
            if (!empty($fname) && !empty($lname)) {
                return (stripos($title, mb_strtolower($fname)) !== false &&
                    stripos($title, mb_strtolower($lname)) !== false);
            }

            // Check if just first name is in title (if only first name provided)
            if (!empty($fname) && empty($lname)) {
                return stripos($title, mb_strtolower($fname)) !== false;
            }

            // Check if just last name is in title (if only last name provided)
            if (empty($fname) && !empty($lname)) {
                return stripos($title, mb_strtolower($lname)) !== false;
            }

            return false;
        }));
    } else {
        // No name provided, search only by meta fields (except postal code)
        // If only postal+kms provided, get all physicians to filter by distance
        $meta_query_for_search = $meta_query_without_postal;

        // If no traditional search criteria provided (only postal+kms), get all physicians
        if (empty($req['city']) && empty($req['province']) && !($req['oit'] === true)) {
            // Only use the immunologist exclusion filter, get all other physicians
            $meta_query_for_search = [
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
            ];
        }

        $q = new WP_Query([
            'post_type'      => 'physicians',
            'post_status'    => 'publish',
            'meta_query'     => $meta_query_for_search,
            'posts_per_page' => -1, // Get all posts
            'no_found_rows'  => true,
        ]);

        $posts = $q->posts;
    }

    // Post-query filter for postal code if needed (but skip if doing distance filtering)
    if ($postal_search_term && !empty($posts) && empty($req['kms'])) {
        $posts = array_values(array_filter($posts, function ($p) use ($postal_search_term) {
            $organizations_details = get_field('organizations_details', $p->ID) ?: [];

            foreach ($organizations_details as $org) {
                if (isset($org['institution_gmap']['post_code'])) {
                    $stored_postal = $org['institution_gmap']['post_code'];

                    // Check for matches with different formats
                    if (
                        stripos($stored_postal, $postal_search_term) !== false ||
                        stripos(str_replace(' ', '', $stored_postal), str_replace(' ', '', $postal_search_term)) !== false ||
                        stripos($stored_postal, substr($postal_search_term, 0, 3)) !== false
                    ) {
                        return true;
                    }
                }
            }
            return false;
        }));
    }

    // Distance-based filtering: filter physicians by organizations within specified radius
    if (!empty($req['kms']) && !empty($req['postal'])) {
        $origin = my_geocode_postal($req['postal']);
        $radius = (float)$req['kms'];

        if ($origin && is_array($origin) && isset($origin['lat']) && isset($origin['lng'])) {
            $posts = array_values(array_filter($posts, function ($p) use ($origin, $radius) {
                $organizations_details = get_field('organizations_details', $p->ID) ?: [];

                // Check if ANY organization is within the radius
                foreach ($organizations_details as $org) {
                    // Check both institution_gmap structure and direct lat/lng fields
                    $lat = null;
                    $lng = null;

                    if (isset($org['institution_gmap']['lat']) && isset($org['institution_gmap']['lng'])) {
                        $lat = (float) $org['institution_gmap']['lat'];
                        $lng = (float) $org['institution_gmap']['lng'];
                    } elseif (isset($org['institution_latitude']) && isset($org['institution_longitude'])) {
                        $lat = (float) $org['institution_latitude'];
                        $lng = (float) $org['institution_longitude'];
                    }

                    if ($lat && $lng) {
                        $distance = my_haversine_distance($origin['lat'], $origin['lng'], $lat, $lng);

                        if ($distance <= $radius) {
                            return true;
                        }
                    }
                }
                return false; // No organizations within radius
            }));
        }
    }

    // Build response—include selected ACF fields if useful
    $search_postal_coords = null;
    if (!empty($req['postal'])) {
        // Reuse the geocoded coordinates if we already have them from distance filtering
        if (isset($origin) && is_array($origin) && isset($origin['lat']) && isset($origin['lng'])) {
            $search_postal_coords = $origin;
        } else {
            $search_postal_coords = my_geocode_postal($req['postal']);
        }
    }

    $out = array_map(function ($p) use ($search_postal_coords) {
        $organizations_details = get_field('organizations_details', $p->ID) ?: [];

        // Calculate distances for each organization if postal code provided
        if ($search_postal_coords && !empty($organizations_details)) {
            foreach ($organizations_details as &$org) {
                if (isset($org['institution_gmap']['lat']) && isset($org['institution_gmap']['lng'])) {
                    $org_lat = (float) $org['institution_gmap']['lat'];
                    $org_lng = (float) $org['institution_gmap']['lng'];

                    if ($org_lat && $org_lng) {
                        $distance = my_haversine_distance(
                            $search_postal_coords['lat'],
                            $search_postal_coords['lng'],
                            $org_lat,
                            $org_lng
                        );
                        $org['distance_km'] = round($distance, 1);
                    }
                }
            }
        }

        return [
            'id'    => $p->ID,
            'title' => get_the_title($p),
            'link'  => get_permalink($p),
            'acf'   => [
                'oit'      => get_post_meta($p->ID, 'practices_oral_immunotherapy_oit', true),
                'city'     => get_post_meta($p->ID, 'physician_city', true),
                'province' => get_post_meta($p->ID, 'physician_province', true),
                'postal'   => get_post_meta($p->ID, 'physician_zipcode', true),
                'credentials' => get_post_meta($p->ID, 'physician_credentials', true),
                'organizations_details' => $organizations_details,
            ],
        ];
    }, $posts);

    return rest_ensure_response([
        'count'     => count($out),
        'results'   => $out,
    ]);
}
