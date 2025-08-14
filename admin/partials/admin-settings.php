<?php

/**
 * Settings admin page template for Dalen Find Allergist plugin
 *
 * @package Dalen_Find_Allergist
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="dalen-admin-header">
        <h2>Plugin Settings</h2>
        <p>Configure the behavior and appearance of the Find Allergist functionality.</p>
    </div>

    <?php
    // Show success message if settings were saved
    if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
        add_settings_error('dalen_find_allergist_messages', 'dalen_find_allergist_message', 'Settings saved successfully!', 'updated');
    }

    // Show error messages
    settings_errors('dalen_find_allergist_messages');
    ?>

    <form method="post" action="options.php">
        <?php
        settings_fields('dalen_find_allergist_settings');
        do_settings_sections('dalen-find-allergist-settings');
        submit_button();
        ?>
    </form>

    <div class="dalen-settings-help">
        <h3>Settings Help</h3>
        <div class="dalen-help-sections">
            <div class="dalen-help-item">
                <h4>Google Maps API Key</h4>
                <p>To display maps with allergist locations, you need a Google Maps API key. You can obtain one from the <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">Google Cloud Console</a>.</p>
                <p><strong>Required APIs:</strong> Maps JavaScript API, Geocoding API, Places API</p>
            </div>

            <div class="dalen-help-item">
                <h4>Search Results Limit</h4>
                <p>This setting controls the maximum number of allergists that will be returned in search results. Setting a lower number improves page load times, while a higher number provides more comprehensive results.</p>
            </div>

            <div class="dalen-help-item">
                <h4>Default Search Radius</h4>
                <p>When users search for allergists, this is the default radius (in kilometers) that will be used if they don't specify one. Users can still adjust this in the search form.</p>
            </div>
        </div>
    </div>

    <div class="dalen-settings-reset">
        <h3>Reset Settings</h3>
        <p><strong>Warning:</strong> This will reset all plugin settings to their default values. This action cannot be undone.</p>
        <button type="button" class="button button-secondary dalen-reset-settings" onclick="return confirm('Are you sure you want to reset all settings? This cannot be undone.');">
            <span class="dashicons dashicons-update"></span>
            Reset to Defaults
        </button>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        // Reset settings functionality
        $('.dalen-reset-settings').on('click', function() {
            if (confirm('Are you sure you want to reset all settings? This cannot be undone.')) {
                // Here you would normally send an AJAX request to reset settings
                // For now, we'll just show a message
                alert('Reset functionality requires additional backend implementation.');
            }
        });
    });
</script>