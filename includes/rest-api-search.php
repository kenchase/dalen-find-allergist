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
            'miles'     => ['required' => false, 'sanitize_callback' => 'absint'], // distance filter (see note below)

            // pagination
            'page'      => ['required' => false, 'sanitize_callback' => 'absint', 'default' => 1],
            'per_page'  => ['required' => false, 'sanitize_callback' => 'absint', 'default' => 10],
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

function my_physician_search(WP_REST_Request $req)
{
    $fname = trim((string) $req['fname']);
    $lname = trim((string) $req['lname']);
    $full  = trim("$fname $lname");

    // Require at least one search criterion
    if (empty($fname) && empty($lname) && empty($req['city']) && empty($req['postal'])) {
        return new WP_Error('missing_criteria', 'Please provide at least one search criterion.', ['status' => 400]);
    }

    $meta_query = ['relation' => 'AND'];

    // Map your form fields to ACF keys. (Change these to YOUR actual ACF field names.)
    // Map request params -> your ACF meta keys
    $acf_keys = [
        'oit'      => 'practices_oral_immunotherapy_oit',
        'city'     => 'physician_city',
        'province' => 'physician_province',
        'postal'   => 'physician_zipcode',
    ];

    // OIT (checkbox field - value is "OIT" when checked, empty when unchecked)
    if (null !== $req['oit']) {
        if ($req['oit']) {
            // Looking for physicians who DO practice OIT
            $meta_query[] = [
                'key'     => $acf_keys['oit'],
                'value'   => 'OIT',
                'compare' => '='
            ];
        } else {
            // Looking for physicians who DON'T practice OIT (empty or not set)
            $meta_query[] = [
                'relation' => 'OR',
                [
                    'key'     => $acf_keys['oit'],
                    'value'   => '',
                    'compare' => '='
                ],
                [
                    'key'     => $acf_keys['oit'],
                    'compare' => 'NOT EXISTS'
                ]
            ];
        }
    }

    // City/province: exact or partial; choose one. Here we do case-insensitive partial:
    if (!empty($req['city'])) {
        $meta_query[] = [
            'key'     => $acf_keys['city'],
            'value'   => $req['city'],
            'compare' => 'LIKE',
        ];
    }
    if (!empty($req['province'])) {
        $meta_query[] = [
            'key'     => $acf_keys['province'],
            'value'   => $req['province'],
            'compare' => 'LIKE',
        ];
    }

    // Postal: allow match without space (store normalized; use LIKE for flexibility)
    if (!empty($req['postal'])) {
        $meta_query[] = [
            'key'     => $acf_keys['postal'],
            'value'   => $req['postal'],
            'compare' => 'LIKE',
        ];
    }

    // Base query: try exact slug first (fast), else phrase search filtered to exact title.
    $per_page = max(1, min(50, (int)$req['per_page']));
    $paged    = max(1, (int)$req['page']);

    // 1) Try exact slug
    $q1 = new WP_Query([
        'post_type'      => 'physicians',
        'post_status'    => 'publish',
        'name'           => sanitize_title($full),
        'meta_query'     => $meta_query,
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'no_found_rows'  => false,
    ]);

    $posts = $q1->posts;

    // 2) Fallback: phrase search, then exact-title filter (case-insensitive)
    if (empty($posts)) {
        $q2 = new WP_Query([
            'post_type'      => 'physicians',
            'post_status'    => 'publish',
            's'              => $full,
            'sentence'       => true,
            'meta_query'     => $meta_query,
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'no_found_rows'  => false,
        ]);

        $posts = array_values(array_filter($q2->posts, function ($p) use ($full) {
            return mb_strtolower(get_the_title($p)) === mb_strtolower($full);
        }));
    }

    // Optional: distance filter (requires lat/lng on each post)
    // If you store ACF fields 'lat' & 'lng' on the post, you can compute distance here in PHP
    // (after fetching a modest set) and then array_filter by $req['miles'].
    if (!empty($req['miles']) && !empty($req['postal'])) {
        // You’ll need to geocode the postal to lat/lng (once) or keep a lookup table.
        // Example outline (pseudo):
        // $origin = my_geocode_postal($req['postal']); // ['lat' => ..., 'lng' => ...]
        // $radius = (float)$req['miles'];
        // $posts = array_values(array_filter($posts, function($p) use ($origin, $radius){
        //   $lat = (float) get_post_meta($p->ID, 'lat', true);
        //   $lng = (float) get_post_meta($p->ID, 'lng', true);
        //   return $lat && $lng && my_haversine_miles($origin['lat'],$origin['lng'],$lat,$lng) <= $radius;
        // }));
    }

    // Build response—include selected ACF fields if useful
    $out = array_map(function ($p) {
        return [
            'id'    => $p->ID,
            'title' => get_the_title($p),
            'link'  => get_permalink($p),
            'acf'   => [
                'oit'      => get_post_meta($p->ID, 'practices_oral_immunotherapy_oit', true),
                'city'     => get_post_meta($p->ID, 'physician_city', true),
                'province' => get_post_meta($p->ID, 'physician_province', true),
                'postal'   => get_post_meta($p->ID, 'physician_zipcode', true),
                'organizations_details' => get_field('organizations_details', $p->ID) ?: [],
            ],
        ];
    }, $posts);

    return rest_ensure_response([
        'page'      => $paged,
        'per_page'  => $per_page,
        'count'     => count($out),
        'results'   => $out,
    ]);
}
