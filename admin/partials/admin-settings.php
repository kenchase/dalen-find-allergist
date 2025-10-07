<?php

/**
 * Admin Settings Page Template
 *
 * @package FAA
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php settings_errors('faa_options'); ?>

    <form method="post" action="options.php">
        <?php
        // Output security fields for the registered setting
        settings_fields('faa_settings');

        // Output setting sections and their fields
        do_settings_sections('faa-settings');

        // Output save settings button
        submit_button(__('Save Settings', 'faa'));
        ?>
    </form>

    <hr style="margin-top: 30px;">

    <h2><?php esc_html_e('Reset Settings', 'faa'); ?></h2>
    <p><?php esc_html_e('Reset all settings to their default values.', 'faa'); ?></p>
    <button type="button" class="button button-secondary" id="faa-reset-settings">
        <?php esc_html_e('Reset to Defaults', 'faa'); ?>
    </button>
</div>