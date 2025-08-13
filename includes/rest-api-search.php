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

    // Format postal code for Google API
    $formatted_postal = substr($postal_code, 0, 3) . ' ' . substr($postal_code, 3, 3);

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

    // Debug logging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Name search - fname: ' . $fname);
        error_log('Name search - lname: ' . $lname);
        error_log('Name search - full: ' . $full);
    }

    // Require at least one search criterion
    if (empty($fname) && empty($lname) && empty($req['city']) && empty($req['province']) && empty($req['postal'])) {
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

    // Note: OIT field is not used for filtering - it's just displayed in results

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

        // Debug: Log search results
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Name search - fname: ' . $fname . ', lname: ' . $lname);
            error_log('Search found ' . count($posts) . ' total posts');
            if (!empty($posts)) {
                foreach ($posts as $post) {
                    error_log('Found post: ' . get_the_title($post) . ' (ID: ' . $post->ID . ')');
                }
            } else {
                // Log some existing physicians for debugging
                $all_physicians = get_posts([
                    'post_type' => 'physicians',
                    'post_status' => 'publish',
                    'numberposts' => 10
                ]);
                error_log('Available physicians:');
                foreach ($all_physicians as $physician) {
                    error_log('- ' . get_the_title($physician) . ' (ID: ' . $physician->ID . ')');
                }
            }
        }
    } else {
        // No name provided, search only by meta fields (except postal code)
        $q = new WP_Query([
            'post_type'      => 'physicians',
            'post_status'    => 'publish',
            'meta_query'     => $meta_query_without_postal,
            'posts_per_page' => -1, // Get all posts
            'no_found_rows'  => true,
        ]);

        $posts = $q->posts;
    }

    // Post-query filter for postal code if needed
    if ($postal_search_term && !empty($posts)) {
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

    // Debug: Log the query for troubleshooting (remove in production)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Physician Search Debug:');
        error_log('Search params: ' . print_r($req->get_params(), true));
        error_log('Meta query: ' . print_r($meta_query_without_postal, true));

        // If searching by province only, let's see what province values exist
        if (!empty($req['province']) && empty($fname) && empty($lname) && empty($req['city']) && empty($req['postal'])) {
            error_log('Province-only search for: ' . $req['province']);

            // Get all physicians and their province values for debugging
            global $wpdb;
            $province_meta = $wpdb->get_results($wpdb->prepare("
                SELECT post_id, meta_value 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = %s
                LIMIT 20
            ", 'physician_province'));
            error_log('Sample province values in database: ' . print_r($province_meta, true));
        }

        error_log('Posts found: ' . count($posts));

        // Additional debug for postal code searches
        if (!empty($req['postal'])) {
            error_log('Postal code search for: ' . $req['postal']);
            // Let's also try a direct database query to see what's actually stored
            global $wpdb;
            $postal_meta = $wpdb->get_results($wpdb->prepare("
                SELECT post_id, meta_key, meta_value 
                FROM {$wpdb->postmeta} 
                WHERE meta_key LIKE %s 
                AND meta_value LIKE %s
                LIMIT 10
            ", '%institution_gmap_post_code', '%' . $req['postal'] . '%'));
            error_log('Direct postal meta query results: ' . print_r($postal_meta, true));
        }
    }

    // Optional: distance filter (requires lat/lng on each post)
    // If you store ACF fields 'lat' & 'lng' on the post, you can compute distance here in PHP
    // (after fetching a modest set) and then array_filter by $req['kms'].
    if (!empty($req['kms']) && !empty($req['postal'])) {
        // You’ll need to geocode the postal to lat/lng (once) or keep a lookup table.
        // Example outline (pseudo):
        // $origin = my_geocode_postal($req['postal']); // ['lat' => ..., 'lng' => ...]
        // $radius = (float)$req['kms'];
        // $posts = array_values(array_filter($posts, function($p) use ($origin, $radius){
        //   $lat = (float) get_post_meta($p->ID, 'lat', true);
        //   $lng = (float) get_post_meta($p->ID, 'lng', true);
        //   return $lat && $lng && my_haversine_miles($origin['lat'],$origin['lng'],$lat,$lng) <= $radius;
        // }));
    }

    // Build response—include selected ACF fields if useful
    $search_postal_coords = null;
    if (!empty($req['postal'])) {
        $search_postal_coords = my_geocode_postal($req['postal']);
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
