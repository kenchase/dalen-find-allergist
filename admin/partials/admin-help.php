<?php
/**
 * Help admin page template for Dalen Find Allergist plugin
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
        <h2>Plugin Help & Documentation</h2>
        <p>Find answers to common questions and learn how to use the Find Allergist plugin effectively.</p>
    </div>

    <div class="dalen-help-content">
        <!-- Quick Start Guide -->
        <div class="dalen-help-section">
            <h3>Quick Start Guide</h3>
            <div class="dalen-steps">
                <div class="dalen-step">
                    <div class="dalen-step-number">1</div>
                    <div class="dalen-step-content">
                        <h4>Configure Settings</h4>
                        <p>Go to <a href="<?php echo admin_url('admin.php?page=dalen-find-allergist-settings'); ?>">Settings</a> and configure your Google Maps API key and other preferences.</p>
                    </div>
                </div>
                
                <div class="dalen-step">
                    <div class="dalen-step-number">2</div>
                    <div class="dalen-step-content">
                        <h4>Add Allergists</h4>
                        <p>Start adding allergist profiles by going to <a href="<?php echo admin_url('post-new.php?post_type=physicians'); ?>">Add New Allergist</a>.</p>
                    </div>
                </div>
                
                <div class="dalen-step">
                    <div class="dalen-step-number">3</div>
                    <div class="dalen-step-content">
                        <h4>Add Search Form</h4>
                        <p>Use the shortcode <code>[allergist_search]</code> to display the search form on any page or post.</p>
                    </div>
                </div>
                
                <div class="dalen-step">
                    <div class="dalen-step-number">4</div>
                    <div class="dalen-step-content">
                        <h4>Display Results</h4>
                        <p>Use the shortcode <code>[allergist_results]</code> to display search results.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="dalen-help-section">
            <h3>Frequently Asked Questions</h3>
            <div class="dalen-faq">
                <div class="dalen-faq-item">
                    <h4 class="dalen-faq-question">How do I get a Google Maps API key?</h4>
                    <div class="dalen-faq-answer">
                        <p>To get a Google Maps API key:</p>
                        <ol>
                            <li>Go to the <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                            <li>Create a new project or select an existing one</li>
                            <li>Enable the Maps JavaScript API, Geocoding API, and Places API</li>
                            <li>Create credentials (API key)</li>
                            <li>Copy the API key and paste it in the plugin settings</li>
                        </ol>
                        <p><strong>Note:</strong> Make sure to restrict your API key to your domain for security.</p>
                    </div>
                </div>

                <div class="dalen-faq-item">
                    <h4 class="dalen-faq-question">What shortcodes are available?</h4>
                    <div class="dalen-faq-answer">
                        <p>The plugin provides several shortcodes:</p>
                        <ul>
                            <li><code>[allergist_search]</code> - Displays the search form</li>
                            <li><code>[allergist_results]</code> - Displays search results</li>
                            <li><code>[allergist_map]</code> - Displays a map with all allergists</li>
                            <li><code>[allergist_list limit="10"]</code> - Displays a list of allergists</li>
                        </ul>
                    </div>
                </div>

                <div class="dalen-faq-item">
                    <h4 class="dalen-faq-question">How do I customize the search form appearance?</h4>
                    <div class="dalen-faq-answer">
                        <p>You can customize the appearance by:</p>
                        <ul>
                            <li>Adding custom CSS to your theme</li>
                            <li>Using the plugin's CSS classes in your theme's style.css</li>
                            <li>Modifying the template files (for advanced users)</li>
                        </ul>
                        <p>Main CSS classes: <code>.allergist-search-form</code>, <code>.allergist-results</code>, <code>.allergist-map</code></p>
                    </div>
                </div>

                <div class="dalen-faq-item">
                    <h4 class="dalen-faq-question">Can I import allergist data from a CSV file?</h4>
                    <div class="dalen-faq-answer">
                        <p>Currently, CSV import is not built into the plugin, but you can:</p>
                        <ul>
                            <li>Use WordPress's built-in import/export tools</li>
                            <li>Use a plugin like WP All Import</li>
                            <li>Contact support for custom import solutions</li>
                        </ul>
                    </div>
                </div>

                <div class="dalen-faq-item">
                    <h4 class="dalen-faq-question">How do I backup my allergist data?</h4>
                    <div class="dalen-faq-answer">
                        <p>To backup your data:</p>
                        <ul>
                            <li>Use WordPress's built-in export tool (Tools → Export)</li>
                            <li>Use a backup plugin that includes database content</li>
                            <li>Contact your hosting provider for full site backups</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shortcode Reference -->
        <div class="dalen-help-section">
            <h3>Shortcode Reference</h3>
            <div class="dalen-shortcode-reference">
                <div class="dalen-shortcode-item">
                    <h4><code>[allergist_search]</code></h4>
                    <p><strong>Description:</strong> Displays the allergist search form</p>
                    <p><strong>Parameters:</strong></p>
                    <ul>
                        <li><code>style</code> - Form style (default, compact, minimal)</li>
                        <li><code>default_radius</code> - Default search radius in km</li>
                    </ul>
                    <p><strong>Example:</strong> <code>[allergist_search style="compact" default_radius="25"]</code></p>
                </div>

                <div class="dalen-shortcode-item">
                    <h4><code>[allergist_results]</code></h4>
                    <p><strong>Description:</strong> Displays allergist search results</p>
                    <p><strong>Parameters:</strong></p>
                    <ul>
                        <li><code>limit</code> - Maximum number of results</li>
                    </ul>
                    <p><strong>Example:</strong> <code>[allergist_results limit="15"]</code></p>
                </div>

                <div class="dalen-shortcode-item">
                    <h4><code>[allergist_list]</code></h4>
                    <p><strong>Description:</strong> Displays a list of allergists</p>
                    <p><strong>Parameters:</strong></p>
                    <ul>
                        <li><code>limit</code> - Number of allergists to show</li>
                        <li><code>orderby</code> - Sort order (title, date, menu_order)</li>
                        <li><code>order</code> - Sort direction (ASC, DESC)</li>
                    </ul>
                    <p><strong>Example:</strong> <code>[allergist_list limit="10" orderby="title" order="ASC"]</code></p>
                </div>
            </div>
        </div>

        <!-- Troubleshooting -->
        <div class="dalen-help-section">
            <h3>Troubleshooting</h3>
            <div class="dalen-troubleshooting">
                <div class="dalen-trouble-item">
                    <h4>Maps not displaying</h4>
                    <ul>
                        <li>Check that your Google Maps API key is configured correctly</li>
                        <li>Ensure the Maps JavaScript API is enabled</li>
                        <li>Verify that your API key has the correct domain restrictions</li>
                        <li>Check browser console for JavaScript errors</li>
                    </ul>
                </div>

                <div class="dalen-trouble-item">
                    <h4>Search not returning results</h4>
                    <ul>
                        <li>Verify that allergist posts have location data (ACF fields)</li>
                        <li>Check that the Geocoding API is enabled</li>
                        <li>Ensure allergist posts are published, not in draft</li>
                        <li>Try increasing the search radius</li>
                    </ul>
                </div>

                <div class="dalen-trouble-item">
                    <h4>Shortcodes not working</h4>
                    <ul>
                        <li>Ensure the plugin is activated</li>
                        <li>Check for theme compatibility issues</li>
                        <li>Verify shortcode syntax is correct</li>
                        <li>Look for conflicts with other plugins</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Support Information -->
        <div class="dalen-help-section">
            <h3>Support Information</h3>
            <div class="dalen-support-info">
                <div class="dalen-support-item">
                    <h4>Plugin Information</h4>
                    <p><strong>Version:</strong> <?php echo esc_html(get_plugin_data(plugin_dir_path(__FILE__) . '../../dalen-find-allergist.php')['Version']); ?></p>
                    <p><strong>Author:</strong> Dalen Design</p>
                    <p><strong>Website:</strong> <a href="https://www.dalendesign.com/" target="_blank">https://www.dalendesign.com/</a></p>
                </div>

                <div class="dalen-support-item">
                    <h4>System Information</h4>
                    <p><strong>WordPress Version:</strong> <?php echo esc_html(get_bloginfo('version')); ?></p>
                    <p><strong>PHP Version:</strong> <?php echo esc_html(PHP_VERSION); ?></p>
                    <p><strong>Active Theme:</strong> <?php echo esc_html(wp_get_theme()->get('Name')); ?></p>
                </div>

                <div class="dalen-support-item">
                    <h4>Need Help?</h4>
                    <p>If you need additional support:</p>
                    <ul>
                        <li>Check the plugin documentation</li>
                        <li>Contact Dalen Design support</li>
                        <li>Submit a support ticket</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // FAQ accordion functionality
    $('.dalen-faq-question').on('click', function() {
        $(this).next('.dalen-faq-answer').slideToggle();
        $(this).toggleClass('active');
    });
});
</script>
