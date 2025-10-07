<?php

/**
 * Main admin page template for Find an Allergist plugin
 *
 * @package FAA
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

    <div class="faa-admin-header">
        <h2><?php esc_html_e('Welcome to Find Allergist Dashboard', 'faa'); ?></h2>
        <p><?php esc_html_e('Manage your allergist directory and search functionality from this central location.', 'faa'); ?></p>
    </div>

    <div class="faa-admin-content">
        <div class="faa-admin-grid">
            <!-- Basic Info Card -->
            <div class="faa-card">
                <h3><?php esc_html_e('Total Allergists', 'faa'); ?></h3>
                <div class="faa-stat-number"><?php echo esc_html($total_allergists); ?></div>
                <p><?php esc_html_e('Active allergist profiles', 'faa'); ?></p>
            </div>

            <div class="faa-card">
                <h3><?php esc_html_e('Plugin Status', 'faa'); ?></h3>
                <div class="faa-stat-number"><?php echo esc_html__('✓', 'faa'); ?></div>
                <p><?php esc_html_e('Plugin is active and running', 'faa'); ?></p>
            </div>

            <div class="faa-card">
                <h3><?php esc_html_e('Google Maps API', 'faa'); ?></h3>
                <?php $api_key = faa_get_google_maps_api_key(); ?>
                <div class="faa-stat-number"><?php echo $api_key ? esc_html__('✓', 'faa') : esc_html__('⚠', 'faa'); ?></div>
                <p>
                    <?php if ($api_key) : ?>
                        <?php esc_html_e('API Key Configured', 'faa'); ?>
                    <?php else : ?>
                        <span style="color: #dc3232;"><?php esc_html_e('API Key Not Set', 'faa'); ?></span><br>
                        <small><a href="<?php echo esc_url(admin_url('admin.php?page=faa-settings')); ?>"><?php esc_html_e('Configure in Settings', 'faa'); ?></a></small>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="faa-quick-actions">
            <h3><?php esc_html_e('Quick Actions', 'faa'); ?></h3>
            <div class="faa-action-buttons">
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=physicians')); ?>" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Add New Allergist', 'faa'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=physicians')); ?>" class="button button-secondary">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php esc_html_e('View All Allergists', 'faa'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=faa-settings')); ?>" class="button button-secondary">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php esc_html_e('Plugin Settings', 'faa'); ?>
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="faa-recent-activity">
            <h3><?php esc_html_e('Recent Activity', 'faa'); ?></h3>
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
                            <th><?php esc_html_e('Allergist Name', 'faa'); ?></th>
                            <th><?php esc_html_e('Date Added', 'faa'); ?></th>
                            <th><?php esc_html_e('Author', 'faa'); ?></th>
                            <th><?php esc_html_e('Actions', 'faa'); ?></th>
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
                                    <a href="<?php echo esc_url(get_edit_post_link($allergist->ID)); ?>" class="button button-small"><?php esc_html_e('Edit', 'faa'); ?></a>
                                    <a href="<?php echo esc_url(get_permalink($allergist->ID)); ?>" class="button button-small" target="_blank"><?php esc_html_e('View', 'faa'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php
                    printf(
                        /* translators: %s: URL to add new allergist */
                        esc_html__('No allergists found. %s.', 'faa'),
                        '<a href="' . esc_url(admin_url('post-new.php?post_type=physicians')) . '">' . esc_html__('Add your first allergist', 'faa') . '</a>'
                    );
                    ?></p>
            <?php endif; ?>
        </div>

        <!-- System Information -->
        <div class="faa-system-info">
            <h3><?php esc_html_e('System Information', 'faa'); ?></h3>
            <div class="faa-info-grid">
                <div class="faa-info-item">
                    <strong><?php esc_html_e('Plugin Version:', 'faa'); ?></strong>
                    <?php
                    if (!function_exists('get_plugin_data')) {
                        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
                    }
                    $plugin_data = get_plugin_data(plugin_dir_path(__FILE__) . '../../faa.php');
                    echo esc_html($plugin_data['Version']);
                    ?>
                </div>
                <div class="faa-info-item">
                    <strong><?php esc_html_e('WordPress Version:', 'faa'); ?></strong> <?php echo esc_html(get_bloginfo('version')); ?>
                </div>
                <div class="faa-info-item">
                    <strong><?php esc_html_e('PHP Version:', 'faa'); ?></strong> <?php echo esc_html(PHP_VERSION); ?>
                </div>
                <div class="faa-info-item">
                    <strong><?php esc_html_e('Google Maps API:', 'faa'); ?></strong>
                    <?php
                    $api_key = faa_get_google_maps_api_key();
                    if ($api_key) {
                        echo '<span class="faa-status-good">' . esc_html__('Configured', 'faa') . '</span>';
                    } else {
                        echo '<span class="faa-status-warning">' . esc_html__('Not Configured', 'faa') . '</span>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>