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
            'find-allergist-results-js',
            dalen_get_asset_url('js/find-allergist-results.js', $asset_base_url),
            array('jquery', $has_maps_api ? 'google-maps-api' : 'jquery'),
            dalen_get_asset_version('js/find-allergist-results.js'),
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
    }
}
