<?php

/**
 * Find Allergist Results Shortcode Class
 *
 * @package Dalen_Find_Allergist
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Find_Allergist_Results_Shortcode extends Find_Allergist_Shortcode_Base
{

    /**
     * Initialize the shortcode
     */
    protected function init()
    {
        add_shortcode('find_allergists_results', [$this, 'render']);
    }

    /**
     * Render the shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render($atts = [])
    {
        $this->start_output_buffer();

        $this->render_results_container();

        return $this->get_output_buffer();
    }

    /**
     * Render the results container
     */
    private function render_results_container()
    {
?>
        <!-- Search Results -->
        <div id="results" class="find-allergists-results"></div>
<?php
    }
}
