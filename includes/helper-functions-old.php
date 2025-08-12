<?php

add_action('wp_ajax_find_an_allergist', 'find_an_allergist');
add_action('wp_ajax_nopriv_find_an_allergist', 'find_an_allergist');

define("ALLERGIST_SEARCH_TEST_MODE", false);

function curl_get_contents($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}


function name_distance($str1, $str2): int
{
    $str1 = trim(preg_replace("/Dr[\.]([a-zA-Z\s\-]{1,})/i", "$1", $str1));
    $str2 = trim(preg_replace("/Dr[\.]([a-zA-Z\s\-]{1,})/i", "$1", $str2));

    return levenshtein($str1, $str2);
}



function get_distance_points($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $unit): float
{
    $theta = $longitudeFrom - $longitudeTo;
    $dist = sin(deg2rad($latitudeFrom)) * sin(deg2rad($latitudeTo)) +  cos(deg2rad($latitudeFrom)) * cos(deg2rad($latitudeTo)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;
    $unit = strtoupper($unit);

    if ($unit == "K") {
        $miles = $miles * 1.609344;

        return round($miles, 2);
    } else if ($unit == "N") {
        $miles = $miles * 0.8684;

        return round($miles, 2) . ' nm';
    } else {
        return round($miles, 2);
    }
}

function get_destination_lat($lat1, $lng1, $d, $brng): float
{
    return asin(sin($lat1) * cos($d / $R) + cos($lat1) * sin($d / $R) * cos($brng));
}

function get_destination_lng($lat1, $lng1, $d, $brng): float
{
    $lat2 = get_destination_lat($lat1, $lng1, $d, $brng);
    return $lng1 + atan2(sin($brng) * sin($d / $R) * cos($lat1), cos($d / $R) - sin($lat1) * sin($lat2));
}

function get_bounding_box($lat, $lng, $range): array
{
    // latlng in radians, range in m
    $latmin = get_destination_lat($lat, $lng, $range, 0);
    $latmax = get_destination_lat($lat, $lng, $range, deg2rad(180));
    $lngmax = get_destination_lng($lat, $lng, $range, deg2rad(90));
    $lngmin = get_destination_lng($lat, $lng, $range, deg2rad(270));

    // return approx bounding latlng in radians
    return array($latmin, $latmax, $lngmin, $lngmax);
}

function getDistance($addressFrom, $addressTo, $unit): float
{
    $formattedAddrFrom = str_replace(' ', '+', $addressFrom);
    $formattedAddrTo = str_replace(' ', '+', $addressTo);

    //Send request and receive json data
    $geocodeFrom = file_get_contents('https://maps.google.com/maps/api/geocode/json?key=AIzaSyDxGyqMkrVCU7C65nKSHqaI0pXKGhgCW1Q&address=' . $formattedAddrFrom . '&sensor=false');
    $outputFrom = json_decode($geocodeFrom);

    $geocodeTo = file_get_contents('https://maps.google.com/maps/api/geocode/json?key=AIzaSyDxGyqMkrVCU7C65nKSHqaI0pXKGhgCW1Q&address=' . $formattedAddrTo . '&sensor=false');
    $outputTo = json_decode($geocodeTo);

    //Get latitude and longitude from geo data
    $latitudeFrom = $outputFrom->results[0]->geometry->location->lat;
    $longitudeFrom = $outputFrom->results[0]->geometry->location->lng;
    $latitudeTo = $outputTo->results[0]->geometry->location->lat;
    $longitudeTo = $outputTo->results[0]->geometry->location->lng;

    //Calculate distance from latitude and longitude
    $theta = $longitudeFrom - $longitudeTo;
    $dist = sin(deg2rad($latitudeFrom)) * sin(deg2rad($latitudeTo)) +  cos(deg2rad($latitudeFrom)) * cos(deg2rad($latitudeTo)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;
    $unit = strtoupper($unit);

    if ($unit == "K") {
        $miles = $miles * 1.609344;

        return round($miles, 2);
    } else if ($unit == "N") {
        $miles = $miles * 0.8684;

        return round($miles, 2) . ' nm';
    } else {
        return round($miles, 2);
    }
}

// Get locations for the specified physician IDs
function getlocations($physician_ids): string
{
    $locations = array();
    $counter = 0;

    foreach ($physician_ids as $physician_id) {
        if (have_rows('organizations_details', $physician_id)) {
            $n = 0;
            while (have_rows('organizations_details', $physician_id)) : the_row();
                if ($n == 0) {

                    $institutation_name = get_sub_field('institutation_name');
                    $institition_map = get_sub_field('institition_map');


                    if ($institition_map) {
                        $latitude_2 = esc_html($location['lat']);
                        $longitude_2 = esc_html($location['lng']);
                        $institution_street_number = esc_html($location['street_number']);
                        $institution_street_name = esc_html($location['street_name']);
                        $address1 =  $institution_street_number . ' ' . $institution_street_name;
                        $institutioncity  = esc_html($location['city']);
                        $institutionstate = esc_html($location['state']);
                        $institutionzipcode = esc_html($location['post_code']);
                        $institutioncountry  = esc_html($location['country']);
                    } else {
                        $address1 = get_sub_field('address_line_1');
                        $address2 = get_sub_field('address_line_2');
                        $institutioncity = get_sub_field('institution_city');
                        $institutionstate = get_sub_field('institution_state');
                        $institutionzipcode = get_sub_field('institution_zipcode');
                    }
                }
                $n++;
            endwhile;
            $address = $address1 . ' ' . $institutioncity . ' ' . $institutionstate . ' ' . $institutionzipcode;
            $corrdination = getLnt($institutionzipcode);
            $locations[] = array($address, $corrdination['lat'], $corrdination['lng'], 0);
        }
        $counter++;
    }

    $out = array_values($locations);
    return json_encode($out);
}


function getLnt($zip)
{
    $url = "https://maps.googleapis.com/maps/api/geocode/json?key=AIzaSyBntBv1hPRrsAZGHZPMMbxdMuRo6HwQ4dM&address=" . urlencode($zip) . "&sensor=false";
    $result_string = file_get_contents($url);
    $result = json_decode($result_string, true);

    $result1[] = $result['results'][0];
    $result2[] = $result1[0]['geometry'];
    $result3[] = $result2[0]['location'];

    return $result3[0];
}


function msort($array, $key, $sort_flags = SORT_REGULAR)
{
    if (is_array($array) && count($array) > 0) {
        if (!empty($key)) {
            $mapping = array();
            foreach ($array as $k => $v) {
                $sort_key = '';

                if (!is_array($key)) {
                    $sort_key = $v[$key];
                } else {
                    // @TODO This should be fixed, now it will be sorted as string
                    foreach ($key as $key_key) {
                        $sort_key .= $v[$key_key];
                    }

                    $sort_flags = SORT_STRING;
                }

                $mapping[$k] = $sort_key;
            }

            asort($mapping, $sort_flags);
            $sorted = array();

            foreach ($mapping as $k => $v) {
                $sorted[] = $array[$k];
            }
            return $sorted;
        }
    }
    return $array;
}

function find_an_allergist()
{ //AJAX Response
    parse_str($_REQUEST['data'] ?? [], $data);

    if (ALLERGIST_SEARCH_TEST_MODE) {
        ob_start();
        print_r($data);
        $contents = ob_get_contents();
        ob_end_clean();

        file_put_contents(__DIR__ . "/allergists_debug.txt", $contents);
    }

    $Flag = 0;
    $phy_array = array();

    if (isset($data['Find']) && $data['Find'] == "Physician") {
        $Flag = 1;

        $phy_fname = trim($data['phy_fname']);
        $phy_lname = trim($data['phy_lname']);
        $phy_oit = trim($data['phy_oit']);
        $phy_name = "";
        if ($phy_fname != "") {
            $phy_name = $phy_fname;
        }
        if ($phy_lname != "") {
            $phy_name = trim($phy_name . " " . $phy_lname);
        }

        $phy_city = trim($data['phy_city']);
        $phy_postal = trim(preg_replace("/[^a-zA-Z0-9]/", "", $data['phy_postal']));
        $phy_province = $data['phy_province'];
        if ($phy_postal == "" && $phy_city == "" && $phy_province == "") {
            $phy_miles = 99999;
        } else {
            $phy_miles = $data['phy_miles'];
        }

        $addressFrom = trim($phy_city . ' ' . $phy_province . ' ' . $phy_postal) . ' Canada';

        $prepAddr = str_replace(' ', '+', $addressFrom);
        $url = 'https://maps.google.com/maps/api/geocode/json?key=AIzaSyDxGyqMkrVCU7C65nKSHqaI0pXKGhgCW1Q&address=' . $prepAddr;
        $geocode = curl_get_contents($url);
        $output = json_decode($geocode);
        $latitude_1 = $output->results[0]->geometry->location->lat;
        $longitude_1 = $output->results[0]->geometry->location->lng;

        $args = array(
            'post_type' => 'physicians',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_key' => 'physician_city',
            'orderby' => $phy_city,
        );

        $args2 = array('relation' => 'AND');
        $meta_query[] = array(
            'key' => 'immunologist_online_search_tool',
            'value' => 'No',
            'compare' => '=',
        );

        $args2 = array_merge($args2, $meta_query);

        if ($args2) {
            $args = array_merge($args, array('meta_query' => $args2));
        }

        if ($phy_oit == "true") {
            $args3 = array('relation' => 'AND');
            $meta_query[] = array(
                'key' => 'practices_oral_immunotherapy_oit',
                'value' => 'OIT',
                'compare' => 'like'
            );

            $args3 = array_merge($args3, $meta_query);

            if ($args3) {
                $args = array_merge($args, array('meta_query' => $args3));
            }
        }

        $search_result_debug = [];
        if (ALLERGIST_SEARCH_TEST_MODE) {
            file_put_contents(__DIR__ . "/allergists_debug.txt", "");
        }

        $physicians = get_posts($args);
        $phy_array = array();
        foreach ($physicians as $physician) {

            $physician_id = $physician->ID;

            $physician_name = $physician->post_title;
            $physician_name = trim(preg_replace("/^(Dr\. )/i", "", trim($physician_name)));

            $credentials = get_field('physician_credentials', $physician_id);
            $practices_oral_immunotherapy_oit = get_field('practices_oral_immunotherapy_oit', $physician_id);
            if ($practices_oral_immunotherapy_oit != null && $practices_oral_immunotherapy_oit[0] == 'OIT') {
                $practices_oral_immunotherapy_oit = "Yes";
            } else {
                $practices_oral_immunotherapy_oit = "No ";
            }
            // $practices_oral_immunotherapy_oit = get_field('practices_oral_immunotherapy_oit', $physician->ID );

            //$phy_name = preg_replace("/[\s]/","[\s\S]*?",trim($phy_name)); //CONSIDER THIS

            $name_is_ok = ($phy_name == "" || ($phy_name != "" && (preg_match("/\b" . $phy_name . "/i", $physician_name) != false || name_distance($physician_name, $phy_name) <= 4)));


            if (ALLERGIST_SEARCH_TEST_MODE) {
                if ($name_is_ok) {
                    file_put_contents(__DIR__ . "/allergists_debug.txt", "Name is OK as " . $phy_name . " vs. " . $physician_name . " (DB)\n", FILE_APPEND);
                } else {
                    file_put_contents(__DIR__ . "/allergists_debug.txt", "Name MISMATCH: " . $phy_name . " vs. " . $physician_name . " (DB)\n", FILE_APPEND);
                }
            }


            //TODO - check if they opted out and ignore them if they did


            //Checks for Institutions

            if ($name_is_ok) {
                if (strpos($physician_name, "Dr.") !== 0) {
                    $physician_name = preg_replace("/^([\S\s]{4})/", "Dr. $1", $physician_name);
                }

                while (have_rows('organizations_details', $physician_id)): the_row();

                    $institution_name = get_sub_field('institutation_name');

                    // The ACF Google Map field contains location/address data
                    $location = get_sub_field('institutation_map');
                    if ($location) {
                        $latitude_2 = esc_html($location['lat']);
                        $longitude_2 = esc_html($location['lng']);
                        $institution_street_number = esc_html($location['street_number']);
                        $institution_street_name = esc_html($location['street_name']);
                        $address1 =  $institution_street_number . ' ' . $institution_street_name;
                        $institutioncity  = esc_html($location['city']);
                        $institutionstate = esc_html($location['state']);
                        $institutionzipcode = esc_html($location['post_code']);
                        $institutioncountry  = esc_html($location['country']);
                    } else {
                        $latitude_2 = get_sub_field('institution_latitude');
                        $longitude_2 = get_sub_field('institution_longitude');

                        $address1 = get_sub_field('address_line_1');
                        $address2 = get_sub_field('address_line_2');
                        $address3 = get_sub_field('address_line_3');

                        $institutioncity = get_sub_field('institution_city');
                        $institutionstate = get_sub_field('institution_state');
                        $institutionzipcode = get_sub_field('institution_zipcode');
                        $institutioncountry = get_sub_field('institution_country');

                        $institution_phone = get_sub_field('institution_phone');
                        $institution_fax = get_sub_field('institution_fax');
                        $institution_ext = get_sub_field('intitution_ext');

                        $address = "";
                        if ($address1 != '') $address .= $address1;
                        if ($address2 != '') $address .= ', ' . $address2;
                        if ($address3 != '') $address .= ', ' . $address3;
                    }




                    if ($phy_miles == 99999) {
                        $distance = -1;
                    } else {
                        $distance = get_distance_points($latitude_1, $longitude_1, $latitude_2, $longitude_2, "K");
                    }

                    if (ALLERGIST_SEARCH_TEST_MODE) {
                        file_put_contents(__DIR__ . "/allergists_debug.txt", "Distance is " . $distance . "\n", FILE_APPEND);
                    }

                    if ($distance <= $phy_miles) {
                        if (ALLERGIST_SEARCH_TEST_MODE) {
                            file_put_contents(__DIR__ . "/allergists_debug.txt", "Distance is good\n", FILE_APPEND);
                        }

                        $physicianinfo = array(
                            'physician' => [
                                "id" => $physician_id,
                                "name" => $physician_name,
                                "practices_oral_immunotherapy_oit" => $practices_oral_immunotherapy_oit,
                                "credentials" => $credentials
                            ],
                            'institution' => [
                                "name" => $institution_name,
                                "phone" => $institution_phone,
                                "fax" => $institution_fax,
                                "ext" => $institution_ext,
                                "distance" => $distance,
                                "latitude" => $latitude_2,
                                "longitude" => $longitude_2
                            ],
                            'address' => [
                                "address" => $address,
                                "city" => $institutioncity,
                                "state" => $institutionstate,
                                "zipcode" => $institutionzipcode,
                                "country" => $institutioncountry
                            ]
                        );

                        $phy_array[] = $physicianinfo;
                    }

                endwhile;
            }
        }

        $phy_array = msort($phy_array, 'distance');


        $_SESSION['parameters'] = array($data['phy_city'], $data['phy_province'], $data['phy_miles']);
        //$_SESSION['phy_latlong'] = $latitude_1 . '|' . $longitude_1;
        $_SESSION['phy_array'] = $phy_array;
    } //End of $data['Find']

    $page = (get_query_var('paged')) ? get_query_var('paged') : 1;

    if ($data['q'] == 's') {
        $Flag = 1;
        if (isset($_SESSION['phy_array'])) {
            //$phy_latlong = explode('|', $_SESSION['phy_latlong']);
            //$latitude_1 = $phy_latlong[0];
            //$longitude_1 = $phy_latlong[1];
            $phy_array = $_SESSION['phy_array'];
            $phy_city = $_SESSION['parameters'][0];
            $phy_province = $_SESSION['parameters'][1];
            $phy_miles = $_SESSION['parameters'][2];
        }
    }

    if (get_query_var('paged')) {
        $Flag = 1;
        if (isset($_SESSION['phy_array'])) {
            //$phy_latlong = explode('|', $_SESSION['phy_latlong']);
            //$latitude_1 = $phy_latlong[0];
            //$longitude_1 = $phy_latlong[1];
            $phy_array = $_SESSION['phy_array'];
            $phy_city = $_SESSION['parameters'][0];
            $phy_province = $_SESSION['parameters'][1];
            $phy_miles = $_SESSION['parameters'][2];
        }
    }

    $location_no = 0;
    $total = count($phy_array);
    $limit = 10;
    $totalPages = ceil($total / $limit);
    $page = max($page, 1); //get 1 page when $_GET['page'] <= 0
    $page = min($page, $totalPages); //get last page when $_GET['page']> $totalPages
    $offset = ($page - 1) * $limit;

    if ($offset < 0) {
        $offset = 0;
    }
    $output_data = [
        "marker_path" => get_stylesheet_directory_uri() . "/images",
        "items" => $phy_array
    ];

    if (ALLERGIST_SEARCH_TEST_MODE) {
        ob_start();
        print_r($output_data);
        $contents = ob_get_contents();
        ob_end_clean();

        file_put_contents(__DIR__ . "/allergists_debug.txt", $contents, FILE_APPEND);
    }


    echo json_encode($output_data);

    die();
}
