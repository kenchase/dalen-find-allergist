<?php

/**
 * Find Allergist Form Shortcode Class
 *
 * @package FAA
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class FAA_Form_Shortcode extends FAA_Shortcode_Base
{

    /**
     * Initialize the shortcode
     */
    protected function init()
    {
        add_shortcode('find_allergists_form', [$this, 'render']);
    }

    /**
     * Render the shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render($atts = [])
    {
        $this->enqueue_assets();
        $this->start_output_buffer();

        $this->render_form();

        return $this->get_output_buffer();
    }

    /**
     * Enqueue necessary assets
     */
    private function enqueue_assets()
    {
        // Enqueue Google Maps API
        $has_maps_api = $this->enqueue_google_maps_api();

        // Enqueue JavaScript
        $asset_base_url = $this->plugin_url . '../assets/';
        wp_enqueue_script(
            'find-allergist-scripts',
            faa_get_asset_url('js/find-allergist.js', $asset_base_url),
            array('jquery', $has_maps_api ? 'google-maps-api' : 'jquery'),
            faa_get_asset_version('js/find-allergist.js'),
            true
        );

        // Enqueue CSS
        $this->enqueue_main_css();
    }

    /**
     * Render the search form
     */
    private function render_form()
    {
?>
        <!-- Search Form -->
        <div id="faa-search" class="faa-search-wrap">
            <div class="faa-search-intro">
                <h1 class="faa-search-intro__title"><?php echo esc_html(get_option('faa_search_form_title', 'Find An Allergist')); ?></h1>
                <div class="faa-search-intro__text">
                    <?php
                    $intro_text = get_option('faa_search_form_intro', '');
                    if (!empty($intro_text)) {
                        echo wp_kses_post($intro_text);
                    } else {
                        // Fallback to default text if no custom intro is set
                    ?>
                        <p><?php _e('Welcome to CSACI Find an Allergist. The search options below can be used to locate an allergist/immunologist close to you.', 'faa'); ?></p>
                        <p><?php _e('Please either enter a name, city and/or postal code to start your search.', 'faa'); ?></p>
                    <?php
                    }
                    ?>
                </div>
            </div>
            <!-- Form submission is handled via JavaScript -->
            <form action="javascript:void(0);" id="faa-search-form" class="faa-search-form">
                <input type="hidden" name="Find" value="Physician" />

                <div class="faa-form-field">
                    <label for="phy_name" class="faa-form-field__label"><?php _e('Physician\'s Name', 'faa'); ?></label>
                    <input type="text" id="phy_name" name="phy_name" placeholder="<?php _e('Search by Name', 'faa'); ?>" value="" />
                </div>

                <div class="faa-form-field">
                    <label for="phy_prac_pop" class="faa-form-field__label"><?php _e('Practice Population', 'faa'); ?></label>
                    <select id="phy_prac_pop" name="phy_prac_pop">
                        <option value=""><?php _e('All Ages', 'faa'); ?></option>
                        <option value="Adults"><?php _e('Adults', 'faa'); ?></option>
                        <option value="Pediatric"><?php _e('Pediatric', 'faa'); ?></option>
                    </select>
                </div>

                <div class="faa-form-field">
                    <label for="phy_city" class="faa-form-field__label"><?php _e('City', 'faa'); ?></label>
                    <input type="text" id="phy_city" name="phy_city" value="" placeholder="<?php _e('Search by City', 'faa'); ?>" />
                </div>

                <div class="faa-form-field">
                    <label for="phy_province" class="faa-form-field__label"><?php _e('Province', 'faa'); ?></label>
                    <select id="phy_province" name="phy_province">
                        <option value="">Search by Province</option>
                        <option value="AB"><?php _e('Alberta', 'faa'); ?></option>
                        <option value="BC"><?php _e('British Columbia', 'faa'); ?></option>
                        <option value="MB"><?php _e('Manitoba', 'faa'); ?></option>
                        <option value="NB"><?php _e('New Brunswick', 'faa'); ?></option>
                        <option value="NL"><?php _e('Newfoundland', 'faa'); ?></option>
                        <option value="NS"><?php _e('Nova Scotia', 'faa'); ?></option>
                        <option value="ON"><?php _e('Ontario', 'faa'); ?></option>
                        <option value="PE"><?php _e('Prince Edward Island', 'faa'); ?></option>
                        <option value="QC"><?php _e('Quebec', 'faa'); ?></option>
                        <option value="SK"><?php _e('Saskatchewan', 'faa'); ?></option>
                        <option value="NT"><?php _e('Northwest Territory', 'faa'); ?></option>
                        <option value="NU"><?php _e('Nunavut', 'faa'); ?></option>
                        <option value="YT"><?php _e('Yukon', 'faa'); ?></option>
                    </select>
                </div>

                <div class="faa-form-field">
                    <label for="phy_postal" class="faa-form-field__label"><?php _e('Postal Code', 'faa'); ?></label>
                    <input type="text" id="phy_postal" name="phy_postal" value="" maxlength="7" placeholder="<?php _e('Search by Postal Code', 'faa'); ?>" pattern="^[A-Za-z]\d[A-Za-z][ -]?\d[A-Za-z]\d$"
                        aria-describedby="postal-error" autocomplete="postal-code" />
                    <div id="postal-error" class="field-error" style="display: none;" role="alert" aria-live="polite">
                        <?php _e('Please enter a valid Canadian postal code (e.g., K1A 0A6)', 'faa'); ?>
                    </div>
                </div>

                <div class="faa-form-field">
                    <label for="phy_kms" class="faa-form-field__label"><?php _e('Within the Range of', 'faa'); ?></label>
                    <select id="phy_kms" name="phy_kms" class="short-box" disabled aria-describedby="range-help-text">
                        <option value="10">10km</option>
                        <option value="20">20km</option>
                        <option value="30" selected>30km</option>
                        <option value="40">40km</option>
                        <option value="50">50km</option>
                        <option value="100">100km</option>
                        <option value="150">150km</option>
                        <option value="200">200km</option>
                        <option value="500">500km</option>
                    </select>
                    <div id="range-help-text" class="field-help-text" role="status" aria-live="polite">
                        <?php _e('This field is only available when a valid postal code is provided.', 'faa'); ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" id="btn-search" class="form-actions_btn form-actions_btn-primary"><?php _e('Search', 'faa'); ?></button>
                    <button type="button" id="btn-clear" class="form-actions_btn form-actions_btn-secondary"><?php _e('Clear Search', 'faa'); ?></button>
                </div>
            </form>
        </div>
<?php
    }
}
