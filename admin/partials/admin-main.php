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
                <?php $api_key = dalen_get_google_maps_api_key(); ?>
                <div class="dalen-stat-number"><?php echo $api_key ? '✓' : '⚠'; ?></div>
                <p>
                    <?php if ($api_key) : ?>
                        <?php esc_html_e('API Key Configured', 'dalen-find-allergist'); ?>
                    <?php else : ?>
                        <span style="color: #dc3232;"><?php esc_html_e('API Key Not Set', 'dalen-find-allergist'); ?></span><br>
                        <small><a href="<?php echo esc_url(admin_url('admin.php?page=dalen-find-allergist-settings')); ?>"><?php esc_html_e('Configure in Settings', 'dalen-find-allergist'); ?></a></small>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="dalen-quick-actions">
            <h3><?php esc_html_e('Quick Actions', 'dalen-find-allergist'); ?></h3>
            <div class="dalen-action-buttons">
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=physicians')); ?>" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Add New Allergist', 'dalen-find-allergist'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=physicians')); ?>" class="button button-secondary">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php esc_html_e('View All Allergists', 'dalen-find-allergist'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=dalen-find-allergist-settings')); ?>" class="button button-secondary">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php esc_html_e('Plugin Settings', 'dalen-find-allergist'); ?>
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="dalen-recent-activity">
            <h3><?php esc_html_e('Recent Activity', 'dalen-find-allergist'); ?></h3>
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
                            <th><?php esc_html_e('Allergist Name', 'dalen-find-allergist'); ?></th>
                            <th><?php esc_html_e('Date Added', 'dalen-find-allergist'); ?></th>
                            <th><?php esc_html_e('Author', 'dalen-find-allergist'); ?></th>
                            <th><?php esc_html_e('Actions', 'dalen-find-allergist'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_allergists as $allergist) : ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($allergist->post_title); ?></strong>
                                </td>
                                <td><?php echo esc_html(wp_date(get_option('date_format'), strtotime($allergist->post_date))); ?></td>
                                <td><?php echo esc_html(get_the_author_meta('display_name', $allergist->post_author)); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_post_link($allergist->ID)); ?>" class="button button-small"><?php esc_html_e('Edit', 'dalen-find-allergist'); ?></a>
                                    <a href="<?php echo esc_url(get_permalink($allergist->ID)); ?>" class="button button-small" target="_blank"><?php esc_html_e('View', 'dalen-find-allergist'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php 
                    printf(
                        /* translators: %s: URL to add new allergist */
                        esc_html__('No allergists found. %s.', 'dalen-find-allergist'),
                        '<a href="' . esc_url(admin_url('post-new.php?post_type=physicians')) . '">' . esc_html__('Add your first allergist', 'dalen-find-allergist') . '</a>'
                    );
                ?></p>
            <?php endif; ?>
        </div>

        <!-- System Information -->
        <div class="dalen-system-info">
            <h3><?php esc_html_e('System Information', 'dalen-find-allergist'); ?></h3>
            <div class="dalen-info-grid">
                <div class="dalen-info-item">
                    <strong><?php esc_html_e('Plugin Version:', 'dalen-find-allergist'); ?></strong> 
                    <?php 
                    if (!function_exists('get_plugin_data')) {
                        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
                    }
                    $plugin_data = get_plugin_data(plugin_dir_path(__FILE__) . '../../dalen-find-allergist.php');
                    echo esc_html($plugin_data['Version']); 
                    ?>
                </div>
                <div class="dalen-info-item">
                    <strong><?php esc_html_e('WordPress Version:', 'dalen-find-allergist'); ?></strong> <?php echo esc_html(get_bloginfo('version')); ?>
                </div>
                <div class="dalen-info-item">
                    <strong><?php esc_html_e('PHP Version:', 'dalen-find-allergist'); ?></strong> <?php echo esc_html(PHP_VERSION); ?>
                </div>
                <div class="dalen-info-item">
                    <strong><?php esc_html_e('Google Maps API:', 'dalen-find-allergist'); ?></strong>
                    <?php
                    $api_key = dalen_get_google_maps_api_key();
                    if ($api_key) {
                        echo '<span class="dalen-status-good">' . esc_html__('Configured', 'dalen-find-allergist') . '</span>';
                    } else {
                        echo '<span class="dalen-status-warning">' . esc_html__('Not Configured', 'dalen-find-allergist') . '</span>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>