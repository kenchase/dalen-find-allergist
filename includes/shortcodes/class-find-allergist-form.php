<?php

/**
 * Find Allergist Form Shortcode Class
 *
 * @package Dalen_Find_Allergist
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Find_Allergist_Form_Shortcode extends Find_Allergist_Shortcode_Base
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
            dalen_get_asset_url('js/find-allergist.js', $asset_base_url),
            array('jquery', $has_maps_api ? 'google-maps-api' : 'jquery'),
            dalen_get_asset_version('js/find-allergist.js'),
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
                <h1 class="faa-search-intro__title"><?php echo esc_html(get_option('dalen_search_form_title', 'Find An Allergist')); ?></h1>
                <div class="faa-search-intro__text">
                    <?php
                    $intro_text = get_option('dalen_search_form_intro', '');
                    if (!empty($intro_text)) {
                        echo wp_kses_post($intro_text);
                    } else {
                        // Fallback to default text if no custom intro is set
                    ?>
                        <p><?php _e('Welcome to CSACI Find an Allergist. The search options below can be used to locate an allergist/immunologist close to you.', 'dalen-find-allergist'); ?></p>
                        <p><?php _e('Please either enter a name, city and/or postal code to start your search.', 'dalen-find-allergist'); ?></p>
                    <?php
                    }
                    ?>
                </div>
            </div>
            <!-- Form submission is handled via JavaScript -->
            <form action="javascript:void(0);" id="faa-search-form" class="faa-search-form">
                <input type="hidden" name="Find" value="Physician" />

                <div class="faa-form-field">
                    <label for="phy_name" class="faa-form-field__label"><?php _e('Physician\'s Name', 'dalen-find-allergist'); ?></label>
                    <input type="text" id="phy_name" name="phy_name" placeholder="<?php _e('Search by Name', 'dalen-find-allergist'); ?>" value="" />
                </div>

                <div class="faa-form-field">
                    <label for="phy_prac_pop" class="faa-form-field__label"><?php _e('Practice Population', 'dalen-find-allergist'); ?></label>
                    <select id="phy_prac_pop" name="phy_prac_pop">
                        <option value=""><?php _e('All Ages', 'dalen-find-allergist'); ?></option>
                        <option value="Adults"><?php _e('Adults', 'dalen-find-allergist'); ?></option>
                        <option value="Pediatric"><?php _e('Pediatric', 'dalen-find-allergist'); ?></option>
                    </select>
                </div>

                <div class="faa-form-field">
                    <label for="phy_city" class="faa-form-field__label"><?php _e('City', 'dalen-find-allergist'); ?></label>
                    <input type="text" id="phy_city" name="phy_city" value="" placeholder="<?php _e('Search by City', 'dalen-find-allergist'); ?>" />
                </div>

                <div class="faa-form-field">
                    <label for="phy_province" class="faa-form-field__label"><?php _e('Province', 'dalen-find-allergist'); ?></label>
                    <select id="phy_province" name="phy_province">
                        <option value="">Search by Province</option>
                        <option value="AB"><?php _e('Alberta', 'dalen-find-allergist'); ?></option>
                        <option value="BC"><?php _e('British Columbia', 'dalen-find-allergist'); ?></option>
                        <option value="MB"><?php _e('Manitoba', 'dalen-find-allergist'); ?></option>
                        <option value="NB"><?php _e('New Brunswick', 'dalen-find-allergist'); ?></option>
                        <option value="NL"><?php _e('Newfoundland', 'dalen-find-allergist'); ?></option>
                        <option value="NS"><?php _e('Nova Scotia', 'dalen-find-allergist'); ?></option>
                        <option value="ON"><?php _e('Ontario', 'dalen-find-allergist'); ?></option>
                        <option value="PE"><?php _e('Prince Edward Island', 'dalen-find-allergist'); ?></option>
                        <option value="QC"><?php _e('Quebec', 'dalen-find-allergist'); ?></option>
                        <option value="SK"><?php _e('Saskatchewan', 'dalen-find-allergist'); ?></option>
                        <option value="NT"><?php _e('Northwest Territory', 'dalen-find-allergist'); ?></option>
                        <option value="NU"><?php _e('Nunavut', 'dalen-find-allergist'); ?></option>
                        <option value="YT"><?php _e('Yukon', 'dalen-find-allergist'); ?></option>
                    </select>
                </div>

                <div class="faa-form-field">
                    <label for="phy_postal" class="faa-form-field__label"><?php _e('Postal Code', 'dalen-find-allergist'); ?></label>
                    <input type="text" id="phy_postal" name="phy_postal" value="" maxlength="7" placeholder="<?php _e('Search by Postal Code', 'dalen-find-allergist'); ?>" pattern="^[A-Za-z]\d[A-Za-z][ -]?\d[A-Za-z]\d$"
                        aria-describedby="postal-error" autocomplete="postal-code" />
                    <div id="postal-error" class="field-error" style="display: none;" role="alert" aria-live="polite">
                        <?php _e('Please enter a valid Canadian postal code (e.g., K1A 0A6)', 'dalen-find-allergist'); ?>
                    </div>
                </div>

                <div class="faa-form-field">
                    <label for="phy_kms" class="faa-form-field__label"><?php _e('Within the Range of', 'dalen-find-allergist'); ?></label>
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
                        <?php _e('This field is only available when a valid postal code is provided.', 'dalen-find-allergist'); ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" id="btn-search" class="form-actions_btn form-actions_btn-primary"><?php _e('Search', 'dalen-find-allergist'); ?></button>
                    <button type="button" id="btn-clear" class="form-actions_btn form-actions_btn-secondary"><?php _e('Clear Search', 'dalen-find-allergist'); ?></button>
                </div>
            </form>
        </div>
<?php
    }
}
