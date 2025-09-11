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
    if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
        add_settings_error('dalen_find_allergist_messages', 'dalen_find_allergist_message', __('Settings saved successfully!', 'dalen-find-allergist'), 'updated');
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
        <h3><?php esc_html_e('Settings Help', 'dalen-find-allergist'); ?></h3>
        <div class="dalen-help-sections">
            <div class="dalen-help-item">
                <h4><?php esc_html_e('Google Maps API Key', 'dalen-find-allergist'); ?></h4>
                <p><?php 
                    printf(
                        /* translators: %s: URL to Google Cloud Console */
                        esc_html__('To display maps with allergist locations, you need a Google Maps API key. You can obtain one from the %s.', 'dalen-find-allergist'),
                        '<a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">' . esc_html__('Google Cloud Console', 'dalen-find-allergist') . '</a>'
                    );
                ?></p>
                <p><strong><?php esc_html_e('Required APIs:', 'dalen-find-allergist'); ?></strong> <?php esc_html_e('Maps JavaScript API, Geocoding API, Places API', 'dalen-find-allergist'); ?></p>
            </div>
        </div>
    </div>

    <div class="dalen-settings-reset">
        <h3><?php esc_html_e('Reset Settings', 'dalen-find-allergist'); ?></h3>
        <p><strong><?php esc_html_e('Warning:', 'dalen-find-allergist'); ?></strong> <?php esc_html_e('This will reset all plugin settings to their default values. This action cannot be undone.', 'dalen-find-allergist'); ?></p>
        <?php wp_nonce_field('dalen_reset_settings', 'dalen_reset_nonce'); ?>
        <button type="button" class="button button-secondary dalen-reset-settings">
            <span class="dashicons dashicons-update"></span>
            <?php esc_html_e('Reset to Defaults', 'dalen-find-allergist'); ?>
        </button>
    </div>
</div>