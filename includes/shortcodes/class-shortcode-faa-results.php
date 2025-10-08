<?php

/**
 * Find Allergist Results Shortcode Class
 *
 * @package FAA
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class FAA_Search_Results_Shortcode extends FAA_Shortcode_Base
{

    /**
     * Initialize the shortcode
     */
    protected function init()
    {
        add_shortcode('faa-search-results', [$this, 'render']);
    }

    /**
     * Render the shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render(array $atts = []): string
    {
        // Parse shortcode attributes
        $atts = shortcode_atts(
            array(
                'class' => '', // Allow additional CSS classes
            ),
            $atts,
            'faa-search-results'
        );

        // Enqueue necessary assets
        $this->enqueue_assets();

        $this->start_output_buffer();

        $this->render_results_container($atts);

        return $this->get_output_buffer();
    }

    /**
     * Enqueue necessary assets
     */
    private function enqueue_assets()
    {
        // Enqueue JavaScript
        $asset_base_url = $this->plugin_url . '../assets/';
        wp_enqueue_script(
            'find-allergist-scripts',
            faa_get_asset_url('js/find-allergist.js', $asset_base_url),
            array('jquery'),
            faa_get_asset_version('js/find-allergist.js'),
            true
        );

        // Enqueue CSS
        $this->enqueue_main_css();
    }

    /**
     * Render the results container
     *
     * @param array $atts Shortcode attributes
     */
    private function render_results_container($atts)
    {
        // Build CSS classes
        $classes = array('faa-res-section');
        if (!empty($atts['class'])) {
            $classes[] = sanitize_html_class($atts['class']);
        }
        $class_string = implode(' ', $classes);

?>
        <!-- Search Results -->
        <div id="faa-res-section"
            class="<?php echo esc_attr($class_string); ?>"
            role="region"
            aria-live="polite"
            aria-label="<?php esc_attr_e('Search Results', FAA_TEXT_DOMAIN); ?>">
        </div>
<?php
    }
}
