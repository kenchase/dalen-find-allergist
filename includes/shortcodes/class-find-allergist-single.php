<?php

/**
 * Find Allergist Single Shortcode Class
 *
 * @package Dalen_Find_Allergist
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Find_Allergist_Single_Shortcode extends Find_Allergist_Shortcode_Base
{

    /**
     * Current post ID
     *
     * @var int
     */
    private $post_id;

    /**
     * Physician data
     *
     * @var array
     */
    private $physician_data;

    /**
     * Map locations
     *
     * @var array
     */
    private $map_locations;

    /**
     * Initialize the shortcode
     */
    protected function init()
    {
        add_shortcode('find_allergist_single', [$this, 'render']);
    }

    /**
     * Render the shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render($atts = [])
    {
        $this->post_id = get_the_ID();

        if (!$this->post_id) {
            return '';
        }

        $this->load_physician_data();
        $this->prepare_map_locations();
        $this->enqueue_assets();

        $this->start_output_buffer();

        $this->render_single_allergist();

        return $this->get_output_buffer();
    }

    /**
     * Load physician data from ACF fields
     */
    private function load_physician_data()
    {
        $this->physician_data = [
            'name' => get_the_title($this->post_id),
            'link' => get_permalink($this->post_id),
            'credentials' => get_field('physician_credentials', $this->post_id) ?: '',
            'oit_field' => get_field('practices_oral_immunotherapy_oit', $this->post_id),
            'practice_setting' => get_field('practice_setting', $this->post_id) ?: '',
            'practice_population' => get_field('practice_population', $this->post_id) ?: '',
            'virtual_care' => get_field('virtual_careconsultation_services', $this->post_id) ?: '',
            'clinical_trials' => get_field('site_for_clinical_trials', $this->post_id) ?: '',
            'special_areas' => get_field('special_areas_of_interest', $this->post_id) ?: '',
            'treatment_services' => get_field('treatment_services_offered', $this->post_id) ?: '',
            'organizations' => get_field('organizations_details', $this->post_id) ?: []
        ];

        // Handle ACF Select field: OIT can be array (multi-select) or string (single select)
        // Convert to Yes/No for display consistency
        $this->physician_data['oit'] = $this->format_oit_value($this->physician_data['oit_field']);
    }

    /**
     * Format OIT field value from ACF Select field
     * 
     * @param mixed $oit_field Raw OIT field value from ACF
     * @return string 'Yes' or 'No'
     */
    private function format_oit_value($oit_field)
    {
        if (empty($oit_field)) {
            return 'No';
        }

        // ACF Select field can return:
        // - String (single select): "OIT"
        // - Array (multi-select): ["OIT"] or ["OIT", "other_value"]
        if (is_array($oit_field)) {
            return in_array('OIT', $oit_field) ? 'Yes' : 'No';
        }

        // Single select string value
        return ($oit_field === 'OIT') ? 'Yes' : 'No';
    }

    /**
     * Prepare map locations from organizations
     */
    private function prepare_map_locations()
    {
        $this->map_locations = [];

        if (!empty($this->physician_data['organizations'])) {
            foreach ($this->physician_data['organizations'] as $org) {
                $lat = isset($org['institution_gmap']['lat']) ? floatval($org['institution_gmap']['lat']) : 0;
                $lng = isset($org['institution_gmap']['lng']) ? floatval($org['institution_gmap']['lng']) : 0;
                $org_name = $org['institutation_name'] ?? 'Organization';

                if ($lat && $lng && $org_name) {
                    $this->map_locations[] = [
                        'lat' => $lat,
                        'lng' => $lng,
                        'title' => $org_name,
                        'address' => $org['institution_gmap']['name'] ?? '',
                        'city' => $org['institution_gmap']['city'] ?? '',
                        'state' => $org['institution_gmap']['state'] ?? '',
                        'physicianName' => $this->physician_data['name'],
                        'physicianCredentials' => $this->physician_data['credentials']
                    ];
                }
            }
        }
    }

    /**
     * Enqueue necessary assets
     */
    private function enqueue_assets()
    {
        // Enqueue Google Maps API
        $this->enqueue_google_maps_api();

        // Enqueue CSS
        $this->enqueue_main_css();
    }

    /**
     * Render the single allergist display
     */
    private function render_single_allergist()
    {
?>
        <!-- Single Allergist Display -->
        <div class="allergist-single">
            <?php $this->render_map_container(); ?>

            <div class="far-item">
                <?php $this->render_physician_info(); ?>
                <?php $this->render_organizations(); ?>
            </div>
        </div>

        <?php $this->render_map_javascript(); ?>
        <?php
    }

    /**
     * Render map container if locations exist
     */
    private function render_map_container()
    {
        if (!empty($this->map_locations)): ?>
            <div class="far-map">
                <div id="single-allergist-map" style="width: 100%; height: 400px; margin-bottom: 2rem; border: 1px solid #ddd; border-radius: 8px;"></div>
            </div>
        <?php endif;
    }

    /**
     * Render physician information section
     */
    private function render_physician_info()
    {
        $data = $this->physician_data;
        ?>
        <div class="far-physician-info">
            <h2 class="far-orgs-title"><?php _e('Physician:', 'dalen-find-allergist'); ?></h2>
            <h3 class="far-physician-name">
                <?php echo $this->esc_html($data['name']); ?>
                <?php if ($data['credentials']): ?>
                    , <?php echo $this->esc_html($data['credentials']); ?>
                <?php endif; ?>
            </h3>

            <p><strong><?php _e('Practices OIT:', 'dalen-find-allergist'); ?></strong> <?php echo $this->esc_html($data['oit']); ?></p>

            <?php $this->render_practice_settings($data['practice_setting']); ?>
            <?php $this->render_field_if_exists('Practice Population:', $data['practice_population']); ?>
            <?php $this->render_field_if_exists('Virtual Care/Consultation Services:', $data['virtual_care']); ?>
            <?php $this->render_field_if_exists('Site for Clinical Trials:', $data['clinical_trials']); ?>
            <?php $this->render_field_if_exists('Special Areas of Interest:', $data['special_areas']); ?>
            <?php $this->render_treatment_services($data['treatment_services']); ?>
        </div>
    <?php
    }

    /**
     * Render practice settings as list
     */
    private function render_practice_settings($practice_setting)
    {
        if (!$practice_setting) return;
    ?>
        <p><strong><?php _e('Practice Setting(s):', 'dalen-find-allergist'); ?></strong></p>
        <ul>
            <?php
            if (is_array($practice_setting)) {
                foreach ($practice_setting as $setting) {
                    echo '<li>' . $this->esc_html($setting) . '</li>';
                }
            } else {
                echo '<li>' . $this->esc_html($practice_setting) . '</li>';
            }
            ?>
        </ul>
    <?php
    }

    /**
     * Render treatment services as list
     */
    private function render_treatment_services($treatment_services)
    {
        if (!$treatment_services) return;
    ?>
        <p><strong><?php _e('Treatment Services Offered:', 'dalen-find-allergist'); ?></strong></p>
        <ul>
            <?php
            if (is_array($treatment_services)) {
                foreach ($treatment_services as $service) {
                    echo '<li>' . $this->esc_html($service) . '</li>';
                }
            } else {
                echo '<li>' . $this->esc_html($treatment_services) . '</li>';
            }
            ?>
        </ul>
    <?php
    }

    /**
     * Render a field if it exists
     */
    private function render_field_if_exists($label, $value)
    {
        if (!$value) return;
    ?>
        <p><strong><?php _e($label, 'dalen-find-allergist'); ?></strong> <?php echo $this->esc_html($value); ?></p>
    <?php
    }

    /**
     * Render organizations section
     */
    private function render_organizations()
    {
        if (empty($this->physician_data['organizations'])) return;
    ?>
        <div class="far-orgs">
            <h2 class="far-orgs-title"><?php _e('Practice Location(s):', 'dalen-find-allergist'); ?></h2>
            <?php foreach ($this->physician_data['organizations'] as $org): ?>
                <?php $this->render_single_organization($org); ?>
            <?php endforeach; ?>
        </div>
    <?php
    }

    /**
     * Render a single organization
     */
    private function render_single_organization($org)
    {
        $org_name = $org['institutation_name'] ?? 'Organization';
        $org_id = 'org-' . abs(crc32($org_name . '-' . ($org['institution_gmap']['name'] ?? '') . '-' . $this->physician_data['name']));

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

        $lat = isset($org['institution_gmap']['lat']) ? floatval($org['institution_gmap']['lat']) : 0;
        $lng = isset($org['institution_gmap']['lng']) ? floatval($org['institution_gmap']['lng']) : 0;
    ?>
        <div class="far-org" id="<?php echo $this->esc_attr($org_id); ?>">
            <h3 class="far-org-title"><?php echo $this->esc_html($org_name); ?></h3>
            <ul class="far-org-list">
                <?php if ($address): ?>
                    <li class="far-org-list-item"> <?php echo $this->esc_html($address); ?></li>
                <?php endif; ?>

                <?php if ($city || $state):
                    $city_state_parts = array_filter([$city, $state]);
                    if (!empty($city_state_parts)):
                ?>
                        <li class="far-org-list-item"> <?php echo $this->esc_html(implode(', ', $city_state_parts)); ?></li>
                <?php endif;
                endif; ?>

                <?php if ($postal_code): ?>
                    <li class="far-org-list-item"> <?php echo $this->esc_html($postal_code); ?></li>
                <?php endif; ?>

                <?php if ($phone): ?>
                    <li class="far-org-list-item"><strong aria-label="Phone">T:</strong> <?php echo $this->esc_html($phone); ?></li>
                <?php endif; ?>

                <?php if ($fax): ?>
                    <li class="far-org-list-item"><strong aria-label="Fax">F:</strong> <?php echo $this->esc_html($fax); ?></li>
                <?php endif; ?>

                <?php if ($lat && $lng && $org_name): ?>
                    <li class="far-org-list-item far-org-list-item--map-link">
                        <a href="#" class="show-on-map-link"
                            data-lat="<?php echo $this->esc_attr($lat); ?>"
                            data-lng="<?php echo $this->esc_attr($lng); ?>"
                            data-org-name="<?php echo $this->esc_attr($org_name); ?>"
                            data-address="<?php echo $this->esc_attr($address); ?>">📍 Show on map</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    <?php
    }

    /**
     * Render map JavaScript functionality
     */
    private function render_map_javascript()
    {
        $api_key = $this->get_google_maps_api_key();
        if (empty($this->map_locations) || empty($api_key)) return;
    ?>
        <script>
            let singleAllergistMap = null;
            let singleAllergistMarkers = [];
            let singleAllergistInfoWindow = null;

            document.addEventListener('DOMContentLoaded', function() {
                if (typeof google !== 'undefined' && google.maps) {
                    initializeSingleAllergistMap();
                } else {
                    setTimeout(function() {
                        if (typeof google !== 'undefined' && google.maps) {
                            initializeSingleAllergistMap();
                        }
                    }, 1000);
                }

                document.addEventListener('click', function(e) {
                    if (e.target.classList.contains('show-on-map-link')) {
                        e.preventDefault();
                        const lat = parseFloat(e.target.getAttribute('data-lat'));
                        const lng = parseFloat(e.target.getAttribute('data-lng'));
                        const orgName = e.target.getAttribute('data-org-name');
                        const address = e.target.getAttribute('data-address');

                        showLocationOnMap(lat, lng, orgName, address);
                    }
                });
            });

            function initializeSingleAllergistMap() {
                const mapContainer = document.getElementById('single-allergist-map');
                if (!mapContainer) return;

                const locations = <?php echo json_encode($this->map_locations); ?>;
                if (!locations || locations.length === 0) return;

                const bounds = new google.maps.LatLngBounds();
                singleAllergistInfoWindow = new google.maps.InfoWindow();

                singleAllergistMap = new google.maps.Map(mapContainer, {
                    zoom: 10,
                    mapTypeId: google.maps.MapTypeId.ROADMAP
                });

                singleAllergistMarkers = [];

                locations.forEach(function(location) {
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

                    const content = createInfoWindowContent(location);

                    marker.addListener('click', function() {
                        singleAllergistInfoWindow.setContent(content);
                        singleAllergistInfoWindow.open(singleAllergistMap, marker);
                    });

                    singleAllergistMarkers.push(marker);
                    bounds.extend(new google.maps.LatLng(location.lat, location.lng));
                });

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

            function showLocationOnMap(lat, lng, orgName, address) {
                const mapContainer = document.getElementById('single-allergist-map');
                if (mapContainer) {
                    mapContainer.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }

                setTimeout(function() {
                    if (!singleAllergistMap || !singleAllergistInfoWindow) return;

                    const targetMarker = singleAllergistMarkers.find(marker => {
                        const position = marker.getPosition();
                        return Math.abs(position.lat() - lat) < 0.0001 && Math.abs(position.lng() - lng) < 0.0001;
                    });

                    if (targetMarker) {
                        singleAllergistMap.setCenter(new google.maps.LatLng(lat, lng));
                        singleAllergistMap.setZoom(15);

                        const content = createInfoWindowContentFromData(orgName, address);
                        singleAllergistInfoWindow.setContent(content);
                        singleAllergistInfoWindow.open(singleAllergistMap, targetMarker);
                    }
                }, 500);
            }

            function createInfoWindowContentFromData(orgName, address) {
                let content = '<div class="map-info-window">';
                content += '<h4>' + escapeHtml(orgName) + '</h4>';

                if (address) {
                    content += '<p><strong><?php _e('Address:', 'dalen-find-allergist'); ?></strong> ' + escapeHtml(address) + '</p>';
                }

                content += '<p><strong><?php _e('Physician:', 'dalen-find-allergist'); ?></strong> <?php echo $this->esc_js($this->physician_data['name']); ?>';
                <?php if ($this->physician_data['credentials']): ?>
                    content += ', <?php echo $this->esc_js($this->physician_data['credentials']); ?>';
                <?php endif; ?>
                content += '</p>';

                content += '</div>';
                return content;
            }

            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        </script>
<?php
    }
}
