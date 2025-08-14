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

    <div class="dalen-settings-export-import">
        <h3>Export/Import Settings</h3>
        <div class="dalen-export-import-grid">
            <div class="dalen-export-section">
                <h4>Export Settings</h4>
                <p>Download your current plugin settings as a JSON file for backup or transfer to another site.</p>
                <button type="button" class="button button-secondary" id="dalen-export-settings">
                    <span class="dashicons dashicons-download"></span>
                    Export Settings
                </button>
            </div>

            <div class="dalen-import-section">
                <h4>Import Settings</h4>
                <p>Upload a previously exported settings file to restore your configuration.</p>
                <input type="file" id="dalen-import-file" accept=".json" style="margin-bottom: 10px;">
                <br>
                <button type="button" class="button button-secondary" id="dalen-import-settings">
                    <span class="dashicons dashicons-upload"></span>
                    Import Settings
                </button>
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
        // Export settings functionality
        $('#dalen-export-settings').on('click', function() {
            var settings = {
                <?php
                $options = get_option('dalen_find_allergist_options', array());
                echo 'options: ' . json_encode($options) . ',';
                ?>
                export_date: '<?php echo current_time('mysql'); ?>',
                plugin_version: '<?php echo get_plugin_data(plugin_dir_path(__FILE__) . '../../dalen-find-allergist.php')['Version']; ?>'
            };

            var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(settings, null, 2));
            var downloadAnchorNode = document.createElement('a');
            downloadAnchorNode.setAttribute("href", dataStr);
            downloadAnchorNode.setAttribute("download", "dalen-find-allergist-settings-" + new Date().getTime() + ".json");
            document.body.appendChild(downloadAnchorNode);
            downloadAnchorNode.click();
            downloadAnchorNode.remove();
        });

        // Import settings functionality
        $('#dalen-import-settings').on('click', function() {
            var fileInput = document.getElementById('dalen-import-file');
            if (fileInput.files.length === 0) {
                alert('Please select a file to import.');
                return;
            }

            var file = fileInput.files[0];
            var reader = new FileReader();

            reader.onload = function(e) {
                try {
                    var settings = JSON.parse(e.target.result);
                    if (settings.options) {
                        // Here you would normally send an AJAX request to save the settings
                        // For now, we'll just show a message
                        alert('Import functionality requires additional backend implementation.');
                    } else {
                        alert('Invalid settings file format.');
                    }
                } catch (error) {
                    alert('Error reading settings file: ' + error.message);
                }
            };

            reader.readAsText(file);
        });

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