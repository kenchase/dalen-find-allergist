<?php

/**
 * Main admin page template for Dalen Find Allergist plugin
 *
 * @package Dalen_Find_Allergist
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get basic allergist count for display
$total_allergists = wp_count_posts('physicians')->publish;
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="dalen-admin-header">
        <h2>Welcome to Find Allergist Dashboard</h2>
        <p>Manage your allergist directory and search functionality from this central location.</p>
    </div>

    <div class="dalen-admin-content">
        <div class="dalen-admin-grid">
            <!-- Basic Info Card -->
            <div class="dalen-card">
                <h3>Total Allergists</h3>
                <div class="dalen-stat-number"><?php echo esc_html($total_allergists); ?></div>
                <p>Active allergist profiles</p>
            </div>

            <div class="dalen-card">
                <h3>Plugin Status</h3>
                <div class="dalen-stat-number">✓</div>
                <p>Plugin is active and running</p>
            </div>

            <div class="dalen-card">
                <h3>Google Maps API</h3>
                <?php
                $options = get_option('dalen_find_allergist_options');
                $api_key = isset($options['google_maps_api_key']) ? $options['google_maps_api_key'] : '';
                ?>
                <div class="dalen-stat-number"><?php echo $api_key ? '✓' : '⚠'; ?></div>
                <p><?php echo $api_key ? 'API Key Configured' : 'API Key Not Set'; ?></p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="dalen-quick-actions">
            <h3>Quick Actions</h3>
            <div class="dalen-action-buttons">
                <a href="<?php echo admin_url('post-new.php?post_type=physicians'); ?>" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    Add New Allergist
                </a>
                <a href="<?php echo admin_url('edit.php?post_type=physicians'); ?>" class="button button-secondary">
                    <span class="dashicons dashicons-list-view"></span>
                    View All Allergists
                </a>
                <a href="<?php echo admin_url('admin.php?page=dalen-find-allergist-settings'); ?>" class="button button-secondary">
                    <span class="dashicons dashicons-admin-settings"></span>
                    Plugin Settings
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="dalen-recent-activity">
            <h3>Recent Activity</h3>
            <?php
            $recent_allergists = get_posts(array(
                'post_type' => 'physicians',
                'posts_per_page' => 5,
                'post_status' => 'publish',
                'orderby' => 'date',
                'order' => 'DESC'
            ));

            if ($recent_allergists) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Allergist Name</th>
                            <th>Date Added</th>
                            <th>Author</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_allergists as $allergist) : ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($allergist->post_title); ?></strong>
                                </td>
                                <td><?php echo esc_html(date('M j, Y', strtotime($allergist->post_date))); ?></td>
                                <td><?php echo esc_html(get_the_author_meta('display_name', $allergist->post_author)); ?></td>
                                <td>
                                    <a href="<?php echo get_edit_post_link($allergist->ID); ?>" class="button button-small">Edit</a>
                                    <a href="<?php echo get_permalink($allergist->ID); ?>" class="button button-small" target="_blank">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>No allergists found. <a href="<?php echo admin_url('post-new.php?post_type=physicians'); ?>">Add your first allergist</a>.</p>
            <?php endif; ?>
        </div>

        <!-- System Information -->
        <div class="dalen-system-info">
            <h3>System Information</h3>
            <div class="dalen-info-grid">
                <div class="dalen-info-item">
                    <strong>Plugin Version:</strong> <?php echo esc_html(get_plugin_data(plugin_dir_path(__FILE__) . '../../dalen-find-allergist.php')['Version']); ?>
                </div>
                <div class="dalen-info-item">
                    <strong>WordPress Version:</strong> <?php echo esc_html(get_bloginfo('version')); ?>
                </div>
                <div class="dalen-info-item">
                    <strong>PHP Version:</strong> <?php echo esc_html(PHP_VERSION); ?>
                </div>
                <div class="dalen-info-item">
                    <strong>Google Maps API:</strong>
                    <?php
                    $options = get_option('dalen_find_allergist_options');
                    $api_key = isset($options['google_maps_api_key']) ? $options['google_maps_api_key'] : '';
                    echo $api_key ? '<span class="dalen-status-good">Configured</span>' : '<span class="dalen-status-warning">Not Configured</span>';
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>