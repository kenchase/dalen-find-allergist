    <?php
    /*
* Shortcodes for Dalen Find Allergist Plugin
*/

    /**
     * Find An Allergist Results Shortcode
     * Usage: [find_allergist_results]
     */
    function find_allergist_form_shortcode($atts)
    {
        // Get API key from admin settings
        $api_key = dalen_get_google_maps_api_key();

        // Only enqueue Google Maps API if key is configured
        if (!empty($api_key)) {
            wp_enqueue_script(
                'google-maps-api',
                'https://maps.google.com/maps/api/js?key=' . esc_attr($api_key),
                array(),
                null,
                true
            );
        }

        // Enqueue the JavaScript file for this shortcode
        wp_enqueue_script(
            'find-allergist-results-js',
            plugin_dir_url(__FILE__) . '../assets/js/find-allergist-results.js',
            array('jquery', !empty($api_key) ? 'google-maps-api' : 'jquery'),
            '1.0.0',
            true
        );

        // Enqueue the CSS file for this shortcode
        wp_enqueue_style(
            'find-allergist-results-css',
            plugin_dir_url(__FILE__) . '../assets/css/find-allergist-results.css',
            array(),
            '1.0.0'
        );

        // Start output buffering
        ob_start();

    ?>

        <!-- Search Form -->
        <div class="physicians_search" id="allergist-search-container">
            <h1><?php _e('Find An Allergist', 'dalen-find-allergist'); ?></h1>
            <p><?php _e('Welcome to CSACI Find an Allergist. The search options below can be used to locate an allergist/immunologist close to you.', 'dalen-find-allergist'); ?></p>
            <p><?php _e('Please either enter a name, city and/or postal code to start your search.', 'dalen-find-allergist'); ?></p>
            <!-- Form submission is handled via JavaScript -->
            <form action="javascript:void(0);" id="allergistfrm">
                <input type="hidden" name="Find" value="Physician" />

                <div class="grid-field-box grid-column-one">
                    <label for="phy_fname"><?php _e('Physician\'s First Name', 'dalen-find-allergist'); ?></label>
                    <input type="text" id="phy_fname" name="phy_fname" value="" />
                </div>

                <div class="grid-field-box grid-column-one">
                    <label for="phy_lname"><?php _e('Physician\'s Last Name', 'dalen-find-allergist'); ?></label>
                    <input type="text" id="phy_lname" name="phy_lname" value="" />
                </div>
                <div class="grid-field-box grid-column-one">
                    <label for="phy_oit"><?php _e('Practices Oral Immunotherapy (OIT)', 'dalen-find-allergist'); ?></label>
                    <input type="checkbox" id="phy_oit" name="phy_oit" value="true" />
                </div>
                <div class="grid-field-box grid-column-one">
                    <label for="phy_city"><?php _e('City', 'dalen-find-allergist'); ?></label>
                    <input type="text" id="phy_city" name="phy_city" value="" />
                </div>

                <div class="grid-field-box grid-column-one">
                    <label for="phy_postal"><?php _e('Postal Code', 'dalen-find-allergist'); ?></label>
                    <input type="text" id="phy_postal" name="phy_postal" value="" />
                </div>

                <div class="grid-field-box grid-column-one">
                    <label for="phy_province"><?php _e('Province', 'dalen-find-allergist'); ?></label>
                    <select id="phy_province" name="phy_province">
                        <option value=""></option>
                        <option value="Alberta"><?php _e('Alberta', 'dalen-find-allergist'); ?></option>
                        <option value="British Columbia"><?php _e('British Columbia', 'dalen-find-allergist'); ?></option>
                        <option value="Manitoba"><?php _e('Manitoba', 'dalen-find-allergist'); ?></option>
                        <option value="New Brunswick"><?php _e('New Brunswick', 'dalen-find-allergist'); ?></option>
                        <option value="Newfoundland"><?php _e('Newfoundland', 'dalen-find-allergist'); ?></option>
                        <option value="Nova Scotia"><?php _e('Nova Scotia', 'dalen-find-allergist'); ?></option>
                        <option value="Ontario"><?php _e('Ontario', 'dalen-find-allergist'); ?></option>
                        <option value="Prince Edward Island"><?php _e('Prince Edward Island', 'dalen-find-allergist'); ?></option>
                        <option value="Quebec"><?php _e('Quebec', 'dalen-find-allergist'); ?></option>
                        <option value="Saskatchewan"><?php _e('Saskatchewan', 'dalen-find-allergist'); ?></option>
                        <option value="Northwest Territory"><?php _e('Northwest Territory', 'dalen-find-allergist'); ?></option>
                        <option value="Nunavut"><?php _e('Nunavut', 'dalen-find-allergist'); ?></option>
                        <option value="Yukon"><?php _e('Yukon', 'dalen-find-allergist'); ?></option>
                    </select>
                </div>

                <div class="grid-field-box grid-column-one">
                    <label><?php _e('Within the range of', 'dalen-find-allergist'); ?></label>
                    <select id="phy_kms" name="phy_kms" class="short-box">
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="30" selected>30</option>
                        <option value="40">40</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="150">150</option>
                        <option value="200">200</option>
                        <option value="500">500</option>
                    </select> <?php _e('Kilometers', 'dalen-find-allergist'); ?>
                </div>

                <div class="grid-column-two">
                    <button type="submit" id="btn-search" class="btn_search et_pb_contact_submit et_pb_button"><?php _e('Search', 'dalen-find-allergist'); ?></button>
                    <button type="button" id="btn-clear"><?php _e('Clear Search', 'dalen-find-allergist'); ?></button>
                </div>
            </form>
        </div>


    <?php
        // Return the buffered content
        return ob_get_clean();
    }

    function find_allergist_results_shortcode($atts)
    {
        // Start output buffering
        ob_start();
    ?>
        <!-- Search Results -->
        <div id="results" class="find-allergists-results"></div>
    <?php

        // Return the buffered content
        return ob_get_clean();
    }


    function find_allergist_single_shortcode($atts)
    {
        // Get API key from admin settings
        $api_key = dalen_get_google_maps_api_key();

        // Only enqueue Google Maps API if key is configured
        if (!empty($api_key)) {
            wp_enqueue_script(
                'google-maps-api',
                'https://maps.google.com/maps/api/js?key=' . esc_attr($api_key),
                array(),
                null,
                true
            );
        }

        // Enqueue the CSS file (same as used for search results)
        wp_enqueue_style(
            'find-allergist-results-css',
            plugin_dir_url(__FILE__) . '../assets/css/find-allergist-results.css',
            array(),
            '1.0.0'
        );

        // Get the current post ID
        global $post;
        $post_id = get_the_ID();

        // If no post ID, return empty
        if (!$post_id) {
            return '';
        }

        // Get ACF fields
        $physician_name = get_the_title($post_id);
        $credentials = get_field('physician_credentials', $post_id) ?: '';
        $oit_field = get_field('practices_oral_immunotherapy_oit', $post_id);  // ACF Checkbox
        $practice_setting = get_field('practice_setting', $post_id) ?: ''; // ACF Multi Select
        $practice_population = get_field('practice_population', $post_id) ?: ''; // ACF Select
        $virtual_careconsultation_services = get_field('virtual_careconsultation_services', $post_id) ?: ''; // ACF Radio Button
        $site_for_clinical_trials = get_field('site_for_clinical_trials', $post_id) ?: ''; // ACF Radio Button
        $special_areas_of_interest = get_field('special_areas_of_interest', $post_id) ?: ''; // ACF Text
        $treatment_services_offered = get_field('treatment_services_offered', $post_id) ?: ''; // ACF Multi Select
        $organizations_details = get_field('organizations_details', $post_id) ?: []; // ACF Repeater

        // Map OIT field value to Yes/No (similar to JavaScript logic)
        $oit = is_array($oit_field) && !empty($oit_field) ? 'Yes' : 'No';

        // Start output buffering
        ob_start();
    ?>
        <!-- Single Allergist Display -->
        <div class="allergist-single">

            <?php
            // Prepare map data for organizations with coordinates
            $map_locations = [];
            if (!empty($organizations_details)) {
                foreach ($organizations_details as $index => $org) {
                    $lat = isset($org['institution_gmap']['lat']) ? floatval($org['institution_gmap']['lat']) : 0;
                    $lng = isset($org['institution_gmap']['lng']) ? floatval($org['institution_gmap']['lng']) : 0;
                    $org_name = $org['institutation_name'] ?? 'Organization';

                    if ($lat && $lng && $org_name) {
                        $map_locations[] = [
                            'lat' => $lat,
                            'lng' => $lng,
                            'title' => $org_name,
                            'address' => $org['institution_gmap']['name'] ?? '',
                            'city' => $org['institution_gmap']['city'] ?? '',
                            'state' => $org['institution_gmap']['state'] ?? '',
                            'physicianName' => $physician_name,
                            'physicianCredentials' => $credentials
                        ];
                    }
                }
            }
            ?>

            <?php if (!empty($map_locations)): ?>
                <div class="far-map">
                    <div id="single-allergist-map" style="width: 100%; height: 400px; margin-bottom: 2rem; border: 1px solid #ddd; border-radius: 8px;"></div>
                </div>
            <?php endif; ?>

            <div class="far-item">
                <div class="far-physician-info">
                    <h2 class="far-orgs-title"><?php _e('Physician:', 'dalen-find-allergist'); ?></h2>
                    <h3 class="far-physician-name">

                        <?php echo esc_html($physician_name); ?>

                        <?php if ($credentials): ?>
                            , <?php echo esc_html($credentials); ?>
                        <?php endif; ?>
                    </h3>
                    <p><strong><?php _e('Practices OIT:', 'dalen-find-allergist'); ?></strong> <?php echo esc_html($oit); ?></p>

                    <?php if ($practice_setting): ?>
                        <p><strong><?php _e('Practice Setting(s):', 'dalen-find-allergist'); ?></strong></p>
                        <ul>
                            <?php
                            if (is_array($practice_setting)) {
                                foreach ($practice_setting as $setting) {
                                    echo '<li>' . esc_html($setting) . '</li>';
                                }
                            } else {
                                echo '<li>' . esc_html($practice_setting) . '</li>';
                            }
                            ?>
                        </ul>
                    <?php endif; ?>

                    <?php if ($practice_population): ?>
                        <p><strong><?php _e('Practice Population:', 'dalen-find-allergist'); ?></strong> <?php echo esc_html($practice_population); ?></p>
                    <?php endif; ?>

                    <?php if ($virtual_careconsultation_services): ?>
                        <p><strong><?php _e('Virtual Care/Consultation Services:', 'dalen-find-allergist'); ?></strong> <?php echo esc_html($virtual_careconsultation_services); ?></p>
                    <?php endif; ?>

                    <?php if ($site_for_clinical_trials): ?>
                        <p><strong><?php _e('Site for Clinical Trials:', 'dalen-find-allergist'); ?></strong> <?php echo esc_html($site_for_clinical_trials); ?></p>
                    <?php endif; ?>

                    <?php if ($special_areas_of_interest): ?>
                        <p><strong><?php _e('Special Areas of Interest:', 'dalen-find-allergist'); ?></strong> <?php echo esc_html($special_areas_of_interest); ?></p>
                    <?php endif; ?>

                    <?php if ($treatment_services_offered): ?>
                        <p><strong><?php _e('Treatment Services Offered:', 'dalen-find-allergist'); ?></strong></p>
                        <ul>
                            <?php
                            if (is_array($treatment_services_offered)) {
                                foreach ($treatment_services_offered as $service) {
                                    echo '<li>' . esc_html($service) . '</li>';
                                }
                            } else {
                                echo '<li>' . esc_html($treatment_services_offered) . '</li>';
                            }
                            ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <?php if (!empty($organizations_details)): ?>
                    <div class="far-orgs">
                        <h2 class="far-orgs-title"><?php _e('Practice Location(s):', 'dalen-find-allergist'); ?></h2>
                        <?php foreach ($organizations_details as $index => $org):
                            // Generate organization ID (similar to JavaScript logic)
                            $org_name = $org['institutation_name'] ?? 'Organization';
                            $org_id = 'org-' . abs(crc32($org_name . '-' . ($org['institution_gmap']['name'] ?? '') . '-' . $physician_name));

                            $address = $org['institution_gmap']['name'] ?? '';
                            $city = $org['institution_gmap']['city'] ?? '';
                            $state = $org['institution_gmap']['state'] ?? '';
                            $postal_code = $org['institution_gmap']['post_code'] ?? '';
                            $phone = $org['institution_phone'] ?? '';
                            $phone_ext = $org['intitution_ext'] ?? '';
                            $fax = $org['institution_fax'] ?? '';

                            // Append extension to phone number if extension exists
                            if ($phone && $phone_ext) {
                                $phone .= ' ext. ' . $phone_ext;
                            }

                        ?>
                            <div class="far-org" id="<?php echo esc_attr($org_id); ?>">
                                <h3 class="far-org-title"><?php echo esc_html($org_name); ?></h3>
                                <ul class="far-org-list">
                                    <?php if ($address): ?>
                                        <li class="far-org-list-item"> <?php echo esc_html($address); ?></li>
                                    <?php endif; ?>

                                    <?php if ($city || $state):
                                        $city_state_parts = array_filter([$city, $state]);
                                        if (!empty($city_state_parts)):
                                    ?>
                                            <li class="far-org-list-item"> <?php echo esc_html(implode(', ', $city_state_parts)); ?></li>
                                    <?php endif;
                                    endif; ?>

                                    <?php if ($postal_code): ?>
                                        <li class="far-org-list-item"> <?php echo esc_html($postal_code); ?></li>
                                    <?php endif; ?>

                                    <?php if ($phone): ?>
                                        <li class="far-org-list-item"><strong aria-label="Phone">T:</strong> <?php echo esc_html($phone); ?></li>
                                    <?php endif; ?>

                                    <?php if ($fax): ?>
                                        <li class="far-org-list-item"><strong aria-label="Fax">F:</strong> <?php echo esc_html($fax); ?></li>
                                    <?php endif; ?>

                                    <?php
                                    // Check if organization has valid coordinates for map display
                                    $lat = isset($org['institution_gmap']['lat']) ? floatval($org['institution_gmap']['lat']) : 0;
                                    $lng = isset($org['institution_gmap']['lng']) ? floatval($org['institution_gmap']['lng']) : 0;
                                    if ($lat && $lng && $org_name): ?>
                                        <li class="far-org-list-item far-org-list-item--map-link">
                                            <a href="#" class="show-on-map-link" data-org-id="<?php echo esc_attr($org_id); ?>">📍 Show on map</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <?php if (!empty($map_locations) && !empty($api_key)): ?>
            <script>
                let singleAllergistMap = null;
                let singleAllergistMarkers = [];
                let singleAllergistInfoWindow = null;
                let orgMarkerMap = new Map(); // Maps organization IDs to their markers

                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof google !== 'undefined' && google.maps) {
                        initializeSingleAllergistMap();
                    } else {
                        // Wait for Google Maps API to load
                        setTimeout(function() {
                            if (typeof google !== 'undefined' && google.maps) {
                                initializeSingleAllergistMap();
                            }
                        }, 1000);
                    }

                    // Add event listeners for "Show on map" links
                    document.addEventListener('click', function(e) {
                        if (e.target.classList.contains('show-on-map-link')) {
                            e.preventDefault();
                            const orgId = e.target.getAttribute('data-org-id');
                            showLocationOnMap(orgId);
                        }
                    });
                });

                function initializeSingleAllergistMap() {
                    const mapContainer = document.getElementById('single-allergist-map');
                    if (!mapContainer) return;

                    const locations = <?php echo json_encode($map_locations); ?>;
                    if (!locations || locations.length === 0) return;

                    const bounds = new google.maps.LatLngBounds();
                    singleAllergistInfoWindow = new google.maps.InfoWindow();

                    // Initialize map
                    singleAllergistMap = new google.maps.Map(mapContainer, {
                        zoom: 10,
                        mapTypeId: google.maps.MapTypeId.ROADMAP
                    });

                    // Clear existing markers and mapping
                    singleAllergistMarkers = [];
                    orgMarkerMap.clear();

                    // Add markers for each location
                    locations.forEach(function(location, index) {
                        const marker = new google.maps.Marker({
                            position: {
                                lat: location.lat,
                                lng: location.lng
                            },
                            map: singleAllergistMap,
                            title: location.title,
                            icon: {
                                url: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png',
                                scaledSize: new google.maps.Size(32, 32)
                            }
                        });

                        // Create info window content
                        const content = createInfoWindowContent(location);

                        // Add click listener for info window
                        marker.addListener('click', function() {
                            singleAllergistInfoWindow.setContent(content);
                            singleAllergistInfoWindow.open(singleAllergistMap, marker);
                        });

                        // Store marker in array and create organization ID mapping
                        singleAllergistMarkers.push(marker);

                        // Generate the same org ID as used in the HTML
                        const orgId = 'org-' + Math.abs(crc32(location.title + '-' + location.address + '-' + location.physicianName));
                        orgMarkerMap.set(orgId, {
                            marker: marker,
                            content: content
                        });

                        bounds.extend(new google.maps.LatLng(location.lat, location.lng));
                    });

                    // Fit map to show all markers
                    if (locations.length === 1) {
                        singleAllergistMap.setCenter(bounds.getCenter());
                        singleAllergistMap.setZoom(15);
                    } else {
                        singleAllergistMap.fitBounds(bounds);
                    }
                }

                function createInfoWindowContent(location) {
                    let content = '<div class="map-info-window">';
                    content += '<h4>' + escapeHtml(location.title) + '</h4>';

                    if (location.address) {
                        content += '<p><strong><?php _e('Address:', 'dalen-find-allergist'); ?></strong> ' + escapeHtml(location.address) + '</p>';
                    }

                    if (location.city || location.state) {
                        const cityState = [location.city, location.state].filter(Boolean).join(', ');
                        if (cityState) {
                            content += '<p><strong><?php _e('Location:', 'dalen-find-allergist'); ?></strong> ' + escapeHtml(cityState) + '</p>';
                        }
                    }

                    if (location.physicianName) {
                        content += '<p><strong><?php _e('Physician:', 'dalen-find-allergist'); ?></strong> ' + escapeHtml(location.physicianName);
                        if (location.physicianCredentials) {
                            content += ', ' + escapeHtml(location.physicianCredentials);
                        }
                        content += '</p>';
                    }

                    content += '</div>';
                    return content;
                }

                function showLocationOnMap(orgId) {
                    // Smooth scroll to map
                    const mapContainer = document.getElementById('single-allergist-map');
                    if (mapContainer) {
                        mapContainer.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }

                    // Show marker info window after a short delay to allow scrolling
                    setTimeout(function() {
                        const markerData = orgMarkerMap.get(orgId);
                        if (markerData && singleAllergistMap && singleAllergistInfoWindow) {
                            // Center map on the specific marker
                            singleAllergistMap.setCenter(markerData.marker.getPosition());
                            singleAllergistMap.setZoom(15);

                            // Open info window
                            singleAllergistInfoWindow.setContent(markerData.content);
                            singleAllergistInfoWindow.open(singleAllergistMap, markerData.marker);
                        }
                    }, 500); // Delay to allow smooth scroll to complete
                }

                // Simple CRC32 implementation for consistent ID generation
                function crc32(str) {
                    let crc = 0 ^ (-1);
                    for (let i = 0; i < str.length; i++) {
                        crc = (crc >>> 8) ^ crcTable[(crc ^ str.charCodeAt(i)) & 0xFF];
                    }
                    return (crc ^ (-1)) >>> 0;
                }

                // CRC32 lookup table
                const crcTable = [];
                for (let i = 0; i < 256; i++) {
                    let c = i;
                    for (let j = 0; j < 8; j++) {
                        c = ((c & 1) ? (0xEDB88320 ^ (c >>> 1)) : (c >>> 1));
                    }
                    crcTable[i] = c;
                }

                function escapeHtml(text) {
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                }
            </script>
        <?php endif; ?>

    <?php

        // Return the buffered content
        return ob_get_clean();
    }

    // Register the shortcode
    // Ideally this would use a single shortcode. However, because of how Divi was setup, we need to use two.
    // TO-DO: Refactor to use a single shortcode
    add_shortcode('find_allergists_form', 'find_allergist_form_shortcode');
    add_shortcode('find_allergists_results', 'find_allergist_results_shortcode');

    // Short code to display Single Allergist on front-end
    add_shortcode('find_allergist_single', 'find_allergist_single_shortcode');
