<?php
/**
 * Plugin Name: Evergreen Posts
 * Description: Adds Evergreen marking functionality to WordPress posts.
 * Version:     2.0
 * Author:      RiotRequest
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue required scripts and styles on post editing screens.
 */
function evergreen_posts_enqueue_scripts($hook_suffix) {
    if ( in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
        wp_enqueue_script( 'evergreen-posts-script', plugin_dir_url( __FILE__ ) . 'js/evergreen-posts.js', array( 'jquery' ), '1.1', true );
        wp_enqueue_style( 'evergreen-posts-style', plugin_dir_url( __FILE__ ) . 'css/evergreen-posts.css', array(), '1.1' );
    }
    
    // Enqueue scripts for our settings page and main dashboard pages
    if ( strpos($hook_suffix, 'evergreen-posts') !== false ) {
        wp_enqueue_style( 'evergreen-posts-admin-style', plugin_dir_url( __FILE__ ) . 'css/evergreen-posts-admin.css', array(), '1.0' );
    }
}
add_action( 'admin_enqueue_scripts', 'evergreen_posts_enqueue_scripts' );

/**
 * Add the "Evergreen" checkbox to the Publish meta box.
 */
function evergreen_posts_add_button() {
    global $post;
    if ( ! $post ) {
        return;
    }

    // Security nonce for verification
    wp_nonce_field( 'evergreen_save_post', 'evergreen_nonce' );

    $evergreen_value = get_post_meta( $post->ID, 'evergreen_post', true );
    $evergreen_checked = checked( $evergreen_value, 1, false );
    ?>
    <div class="misc-pub-section misc-pub-evergreen <?php echo $evergreen_value ? 'evergreen-active' : ''; ?>">
        <label for="evergreen-post" class="selectit">
            <input type="checkbox" name="evergreen_post" id="evergreen-post" value="1" <?php echo $evergreen_checked; ?>>
            Evergreen
        </label>
    </div>
    <?php
}
add_action( 'post_submitbox_misc_actions', 'evergreen_posts_add_button' );

/**
 * Save the evergreen status when the post is saved.
 *
 * @param int $post_id The current post ID.
 */
function evergreen_posts_save_status( $post_id ) {
    // Verify nonce.
    if ( ! isset( $_POST['evergreen_nonce'] ) || ! wp_verify_nonce( $_POST['evergreen_nonce'], 'evergreen_save_post' ) ) {
        return;
    }

    // Validate user capabilities.
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Update or delete the meta based on the checkbox.
    if ( isset( $_POST['evergreen_post'] ) && '1' === $_POST['evergreen_post'] ) {
        update_post_meta( $post_id, 'evergreen_post', 1 );
    } else {
        delete_post_meta( $post_id, 'evergreen_post' );
    }
}
add_action( 'save_post', 'evergreen_posts_save_status' );

/**
 * Add the "Evergreen" column to the All Posts screen.
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 */
function evergreen_posts_add_column( $columns ) {
    $columns['evergreen'] = __( 'Evergreen', 'evergreen-posts' );
    return $columns;
}
add_filter( 'manage_posts_columns', 'evergreen_posts_add_column' );

/**
 * Display the evergreen status in the "Evergreen" column.
 *
 * @param string $column  Column name.
 * @param int    $post_id Current post ID.
 */
function evergreen_posts_display_column( $column, $post_id ) {
    if ( 'evergreen' === $column ) {
        if ( get_post_meta( $post_id, 'evergreen_post', true ) ) {
            echo '<span class="evergreen-indicator dashicons dashicons-yes-alt"></span>';
        }
    }
}
add_action( 'manage_posts_custom_column', 'evergreen_posts_display_column', 10, 2 );

/**
 * Add the main admin menu for Evergreen Posts
 */
function evergreen_posts_admin_menu() {
    // Add the main menu item
    add_menu_page(
        __('Evergreen Posts', 'evergreen-posts'),
        __('Evergreen Posts', 'evergreen-posts'),
        'edit_posts',
        'evergreen-posts',
        'evergreen_posts_page',
        'dashicons-sticky',
        25
    );
    
    // Add the listing page as a submenu
    add_submenu_page(
        'evergreen-posts',
        __('Evergreen Posts Listing', 'evergreen-posts'),
        __('Posts List', 'evergreen-posts'),
        'edit_posts',
        'evergreen-posts',
        'evergreen_posts_page'
    );
    
    // Add the auto settings page as a submenu
    add_submenu_page(
        'evergreen-posts',
        __('Auto Evergreen Settings', 'evergreen-posts'),
        __('Auto Settings', 'evergreen-posts'),
        'manage_options',
        'evergreen-posts-auto-settings',
        'evergreen_posts_auto_settings_callback'
    );
}
add_action('admin_menu', 'evergreen_posts_admin_menu');

/**
 * Callback function for the Evergreen posts listing page.
 */
function evergreen_posts_page() {
    // Handle removal of evergreen status if requested
    if (isset($_GET['remove_evergreen']) && isset($_GET['post_id']) && isset($_GET['_wpnonce'])) {
        $post_id = intval($_GET['post_id']);
        if (wp_verify_nonce($_GET['_wpnonce'], 'remove_evergreen_' . $post_id) && current_user_can('edit_post', $post_id)) {
            delete_post_meta($post_id, 'evergreen_post');
            echo '<div class="notice notice-success is-dismissible"><p>' . 
                 sprintf(__('Evergreen status removed from "%s".', 'evergreen-posts'), get_the_title($post_id)) . 
                 '</p></div>';
        }
    }

    // Get the current tab from the URL, default to 'list'
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';
    
    // Display the tabs navigation
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Evergreen Posts', 'evergreen-posts'); ?></h1>
        
        <nav class="nav-tab-wrapper wp-clearfix">
            <a href="<?php echo esc_url(admin_url('admin.php?page=evergreen-posts&tab=list')); ?>" class="nav-tab <?php echo $current_tab === 'list' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Posts List', 'evergreen-posts'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=evergreen-posts-auto-settings')); ?>" class="nav-tab <?php echo $current_tab === 'auto' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Auto Settings', 'evergreen-posts'); ?>
            </a>
        </nav>
        
        <?php
        // Display the posts list
        ?>
        <p><?php esc_html_e('Posts marked as Evergreen, ordered by publish date.', 'evergreen-posts'); ?></p>
        <hr class="wp-header-end">
        
        <?php
        // Allow developers to filter query arguments.
        $args = apply_filters('evergreen_posts_query_args', array(
            'post_type'      => 'post',
            'meta_key'       => 'evergreen_post',
            'meta_value'     => '1',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ));
        
        $evergreen_posts = new WP_Query($args);
        
        if ($evergreen_posts->have_posts()) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column"><input type="checkbox"></td>
                        <th scope="col" class="manage-column column-title column-primary"><?php esc_html_e('Title', 'evergreen-posts'); ?></th>
                        <th scope="col" class="manage-column column-author"><?php esc_html_e('Author', 'evergreen-posts'); ?></th>
                        <th scope="col" class="manage-column column-categories"><?php esc_html_e('Categories', 'evergreen-posts'); ?></th>
                        <th scope="col" class="manage-column column-comments"><?php esc_html_e('Comments', 'evergreen-posts'); ?></th>
                        <th scope="col" class="manage-column column-date"><?php esc_html_e('Date', 'evergreen-posts'); ?></th>
                        <th scope="col" class="manage-column column-actions"><?php esc_html_e('Actions', 'evergreen-posts'); ?></th>
                    </tr>
                </thead>
                <tbody id="the-list">
                    <?php while ($evergreen_posts->have_posts()) : $evergreen_posts->the_post(); ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="post[]" value="<?php the_ID(); ?>">
                            </th>
                            <td class="title column-title has-row-actions column-primary">
                                <strong>
                                    <a class="row-title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </strong>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo esc_url(get_edit_post_link()); ?>"><?php esc_html_e('Edit', 'evergreen-posts'); ?></a>
                                    </span>
                                </div>
                            </td>
                            <td class="author column-author"><?php the_author(); ?></td>
                            <td class="categories column-categories"><?php echo get_the_category_list(', '); ?></td>
                            <td class="comments column-comments">
                                <?php
                                $comment_count = get_comments_number();
                                echo '<a href="' . esc_url(get_comments_link()) . '">' . intval($comment_count) . '</a>';
                                ?>
                            </td>
                            <td class="date column-date"><?php echo esc_html(get_the_date()); ?></td>
                            <td class="actions column-actions">
                                <?php
                                $remove_url = wp_nonce_url(
                                    add_query_arg(
                                        array(
                                            'page' => 'evergreen-posts',
                                            'remove_evergreen' => 1,
                                            'post_id' => get_the_ID()
                                        ),
                                        admin_url('admin.php')
                                    ),
                                    'remove_evergreen_' . get_the_ID()
                                );
                                ?>
                                <a href="<?php echo esc_url($remove_url); ?>" class="button button-small remove-evergreen" 
                                   onclick="return confirm('<?php esc_attr_e('Are you sure you want to remove the evergreen status?', 'evergreen-posts'); ?>');">
                                    <?php esc_html_e('Remove Evergreen', 'evergreen-posts'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e('No Evergreen posts found.', 'evergreen-posts'); ?></p>
        <?php endif; ?>
    </div>
    <?php
    wp_reset_postdata();
}

/**
 * Callback function for the Auto Evergreen Settings page.
 */
function evergreen_posts_auto_settings_callback() {
    // Get the current tab (for highlighting in the nav)
    $current_tab = 'auto';
    $message = '';
    
    // Save settings if submitted
    if (isset($_POST['evergreen_posts_save_settings']) && check_admin_referer('evergreen_posts_settings_nonce')) {
        // Update auto-evergreen enabled status
        $auto_evergreen_enabled = isset($_POST['evergreen_posts_auto_enabled']) ? 1 : 0;
        update_option('evergreen_posts_auto_enabled', $auto_evergreen_enabled);
        
        // Update thresholds
        $view_threshold = intval($_POST['evergreen_posts_view_threshold']);
        update_option('evergreen_posts_view_threshold', $view_threshold);
        
        $comment_threshold = intval($_POST['evergreen_posts_comment_threshold']);
        update_option('evergreen_posts_comment_threshold', $comment_threshold);
        
        $message = '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved.', 'evergreen-posts') . '</p></div>';
    }
    
    // Run auto evergreen check manually if requested
    if (isset($_POST['evergreen_posts_run_now']) && check_admin_referer('evergreen_posts_run_now_nonce')) {
        $marked_count = evergreen_posts_check_and_mark(true); // Pass true to indicate a manual run
        $message = '<div class="notice notice-success is-dismissible"><p>' . 
                    sprintf(__('Auto-evergreen check completed. %d posts were marked as evergreen.', 'evergreen-posts'), $marked_count) .
                  '</p></div>';
    }
    
    // Clear all evergreen marks if requested
    if (isset($_POST['evergreen_posts_clear_all']) && check_admin_referer('evergreen_posts_clear_all_nonce')) {
        $cleared_count = evergreen_posts_clear_all_marks();
        $message = '<div class="notice notice-success is-dismissible"><p>' . 
                    sprintf(__('Evergreen marks cleared from %d posts.', 'evergreen-posts'), $cleared_count) .
                  '</p></div>';
    }
    
    // Get current settings
    $auto_evergreen_enabled = get_option('evergreen_posts_auto_enabled', 0);
    $view_threshold = get_option('evergreen_posts_view_threshold', 1000);
    $comment_threshold = get_option('evergreen_posts_comment_threshold', 20);
    
    // Check if WPP is active
    $wpp_active = function_exists('wpp_get_views');
    
    // Settings page HTML
    ?>
    <div class="wrap">
        <h1><?php _e('Evergreen Posts', 'evergreen-posts'); ?></h1>
        
        <nav class="nav-tab-wrapper wp-clearfix">
            <a href="<?php echo esc_url(admin_url('admin.php?page=evergreen-posts&tab=list')); ?>" class="nav-tab <?php echo $current_tab === 'list' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Posts List', 'evergreen-posts'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=evergreen-posts-auto-settings')); ?>" class="nav-tab <?php echo $current_tab === 'auto' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Auto Settings', 'evergreen-posts'); ?>
            </a>
        </nav>
        
        <?php 
        // Show any messages
        if (!empty($message)) {
            echo $message;
        }
        ?>
        
        <div class="evergreen-settings-container">
            <h2><?php _e('Auto Evergreen Settings', 'evergreen-posts'); ?></h2>
            <p><?php _e('Configure settings for automatically marking posts as evergreen based on popularity.', 'evergreen-posts'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('evergreen_posts_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable Auto Evergreen', 'evergreen-posts'); ?></th>
                        <td>
                            <label for="evergreen_posts_auto_enabled">
                                <input type="checkbox" name="evergreen_posts_auto_enabled" id="evergreen_posts_auto_enabled" value="1" <?php checked($auto_evergreen_enabled, 1); ?> />
                                <?php _e('Automatically mark posts as evergreen based on popularity', 'evergreen-posts'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, posts will be automatically marked as evergreen if they meet the criteria below.', 'evergreen-posts'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Popularity Metric', 'evergreen-posts'); ?></th>
                        <td>
                            <?php if ($wpp_active): ?>
                                <span class="dashicons dashicons-yes" style="color:green;"></span>
                                <?php _e('WordPress Popular Posts is active. Using view count as primary metric.', 'evergreen-posts'); ?>
                            <?php else: ?>
                                <span class="dashicons dashicons-no" style="color:red;"></span>
                                <?php _e('WordPress Popular Posts not detected. Using comment count as fallback.', 'evergreen-posts'); ?>
                                <p class="description">
                                    <?php _e('Install and activate WordPress Popular Posts for view-based metrics.', 'evergreen-posts'); ?>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('View Threshold', 'evergreen-posts'); ?></th>
                        <td>
                            <input type="number" name="evergreen_posts_view_threshold" value="<?php echo esc_attr($view_threshold); ?>" min="1" />
                            <p class="description">
                                <?php _e('Posts with this many views will be automatically marked as evergreen.', 'evergreen-posts'); ?>
                                <?php if (!$wpp_active): ?>
                                <br>
                                <em><?php _e('(Only applies when WordPress Popular Posts is active)', 'evergreen-posts'); ?></em>
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Comment Threshold', 'evergreen-posts'); ?></th>
                        <td>
                            <input type="number" name="evergreen_posts_comment_threshold" value="<?php echo esc_attr($comment_threshold); ?>" min="1" />
                            <p class="description">
                                <?php _e('If WPP is not active, posts with this many comments will be marked as evergreen.', 'evergreen-posts'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="evergreen_posts_save_settings" class="button button-primary" value="<?php _e('Save Settings', 'evergreen-posts'); ?>" />
                </p>
            </form>
            
            <hr />
            
            <div class="manual-run-section">
                <h2><?php _e('Run Auto Evergreen Check', 'evergreen-posts'); ?></h2>
                <p><?php _e('Click the button below to manually run the auto-evergreen check now.', 'evergreen-posts'); ?></p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('evergreen_posts_run_now_nonce'); ?>
                    <p>
                        <label for="evergreen_posts_check_all">
                            <input type="checkbox" name="evergreen_posts_check_all" id="evergreen_posts_check_all" value="1" />
                            <?php _e('Re-check all posts (including already marked posts)', 'evergreen-posts'); ?>
                        </label>
                    </p>
                    <p>
                        <input type="submit" name="evergreen_posts_run_now" class="button button-secondary" value="<?php _e('Run Now', 'evergreen-posts'); ?>" />
                    </p>
                </form>
            </div>
            
            <div class="evergreen-danger-zone manual-run-section" style="border-left-color: #dc3232; margin-top: 40px;">
                <h2><?php _e('Clear All Evergreen Marks', 'evergreen-posts'); ?></h2>
                <p><?php _e('This will remove the evergreen mark from all posts. This action cannot be undone.', 'evergreen-posts'); ?></p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('evergreen_posts_clear_all_nonce'); ?>
                    <p>
                        <input type="submit" name="evergreen_posts_clear_all" class="button button-secondary" 
                            value="<?php _e('Clear All Marks', 'evergreen-posts'); ?>" 
                            onclick="return confirm('<?php _e('Are you sure you want to remove all evergreen marks? This action cannot be undone.', 'evergreen-posts'); ?>');" />
                    </p>
                </form>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Clear all evergreen marks from posts
 * 
 * @return int Number of posts that had their evergreen mark removed
 */
function evergreen_posts_clear_all_marks() {
    global $wpdb;
    
    // Count how many posts have the mark
    $count_query = $wpdb->prepare(
        "SELECT COUNT(post_id) FROM $wpdb->postmeta WHERE meta_key = %s",
        'evergreen_post'
    );
    $count = $wpdb->get_var($count_query);
    
    // Delete all evergreen post meta
    $wpdb->delete(
        $wpdb->postmeta,
        array('meta_key' => 'evergreen_post'),
        array('%s')
    );
    
    return (int) $count;
}

/**
 * Get the popularity metric for a post (views if WPP is active, otherwise comments)
 * 
 * @param int $post_id The post ID
 * @param bool $all_time Whether to get all-time stats or just recent
 * @return array Contains type ('views' or 'comments') and count
 */
function evergreen_posts_get_metric($post_id, $all_time = false) {
    // Check if WordPress Popular Posts is active
    if (function_exists('wpp_get_views')) {
        // For auto-run use recent stats, for manual run use all_time
        // Valid range options: 'all', 'last24hours', 'last7days', 'last30days'
        $range = $all_time ? 'all' : 'last24hours';
        
        // Get views from WPP with proper parameters
        // Parameters: post_id, time range, formatted (false to get raw number), cached (true for performance)
        $views = wpp_get_views($post_id, $range, false, true);
        
        return [
            'type' => 'views',
            'count' => $views,
            'range' => $range
        ];
    } else {
        // Fallback to comments
        $comments = get_comments_number($post_id);
        return [
            'type' => 'comments',
            'count' => $comments,
            'range' => 'all_time'
        ];
    }
}

/**
 * The function that runs during the cron event to check and mark evergreen posts
 */
function evergreen_posts_check_and_mark($manual_run = false) {
    // If auto-evergreen is disabled and this is not a manual run, don't do anything
    if (!get_option('evergreen_posts_auto_enabled', 0) && !$manual_run) {
        return;
    }
    
    // Get thresholds from settings
    $view_threshold = get_option('evergreen_posts_view_threshold', 1000);
    $comment_threshold = get_option('evergreen_posts_comment_threshold', 20);
    
    // Option to check all posts (even those already marked) on manual runs
    $check_all = $manual_run && isset($_POST['evergreen_posts_check_all']) ? true : false;
    
    // For manual runs, try using wpp_get_mostpopular for better performance when available
    if ($manual_run && function_exists('wpp_get_ids')) {
        $marked_count = evergreen_posts_mark_popular_via_wpp($view_threshold, $check_all);
        return $marked_count;
    }
    
    // Query posts that aren't already evergreen (unless check_all is enabled)
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => 1000, // Increased from 500 to handle more posts
        'post_status' => 'publish',
        'no_found_rows' => true, // Performance optimization
    );
    
    // Only filter for non-evergreen posts if we're not checking all posts
    if (!$check_all) {
        $args['meta_query'] = array(
            array(
                'key' => 'evergreen_post',
                'compare' => 'NOT EXISTS'
            )
        );
    }
    
    $query = new WP_Query($args);
    $marked_count = 0;
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            // Skip if already marked as evergreen and we're not checking all
            if (!$check_all && get_post_meta($post_id, 'evergreen_post', true)) {
                continue;
            }
            
            // Manual runs should use all_time stats, scheduled runs should use recent
            $metrics = evergreen_posts_get_metric($post_id, $manual_run);
            
            // Determine if post meets the threshold
            $meets_threshold = false;
            if ($metrics['type'] === 'views' && $metrics['count'] >= $view_threshold) {
                $meets_threshold = true;
            } elseif ($metrics['type'] === 'comments' && $metrics['count'] >= $comment_threshold) {
                $meets_threshold = true;
            }
            
            // Mark post as evergreen if it meets the threshold
            if ($meets_threshold) {
                update_post_meta($post_id, 'evergreen_post', 1);
                $marked_count++;
            }
        }
    }
    
    wp_reset_postdata();
    
    // Return the number of marked posts for reporting
    return $marked_count;
}

/**
 * Mark popular posts via WordPress Popular Posts directly
 * This is a more efficient method for manual runs
 * 
 * @param int $view_threshold The view threshold to meet
 * @param bool $check_all Whether to check all posts or only unmarked ones
 * @return int Number of posts marked as evergreen
 */
function evergreen_posts_mark_popular_via_wpp($view_threshold, $check_all = false) {
    // Get popular posts directly from WPP
    $popular_ids = wpp_get_ids([
        'range' => 'all',
        'limit' => 1000,
        'post_type' => 'post',
        'order_by' => 'views'
    ]);
    
    $marked_count = 0;
    
    // No popular posts found
    if (empty($popular_ids)) {
        return 0;
    }
    
    // Process each popular post
    foreach ($popular_ids as $post_id) {
        // Skip if already marked and we're not checking all
        if (!$check_all && get_post_meta($post_id, 'evergreen_post', true)) {
            continue;
        }
        
        // Get view count for this post
        $views = wpp_get_views($post_id, 'all', false, true);
        
        // If it meets the threshold, mark it
        if ($views >= $view_threshold) {
            update_post_meta($post_id, 'evergreen_post', 1);
            $marked_count++;
        }
    }
    
    return $marked_count;
}

/**
 * Register the cron event on plugin activation
 */
function evergreen_posts_activate() {
    if (!wp_next_scheduled('evergreen_posts_daily_check')) {
        wp_schedule_event(time(), 'daily', 'evergreen_posts_daily_check');
    }
}
register_activation_hook(__FILE__, 'evergreen_posts_activate');

/**
 * Clean up cron event on plugin deactivation
 */
function evergreen_posts_deactivate() {
    wp_clear_scheduled_hook('evergreen_posts_daily_check');
}
register_deactivation_hook(__FILE__, 'evergreen_posts_deactivate');

/**
 * Hook the cron event to our check function
 */
add_action('evergreen_posts_daily_check', 'evergreen_posts_check_and_mark');
?>
