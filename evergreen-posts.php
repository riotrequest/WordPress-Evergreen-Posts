<?php
/**
 * Plugin Name: Evergreen Posts
 * Description: Adds Evergreen marking functionality to WordPress posts.
 * Version:     1.2
 * Author:      RiotRequest
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Enqueue the required scripts and styles
function evergreen_posts_enqueue_scripts($hook_suffix) {
    if ($hook_suffix === 'post.php' || $hook_suffix === 'post-new.php') {
        wp_enqueue_script( 'evergreen-posts-script', plugin_dir_url( __FILE__ ) . 'js/evergreen-posts.js', array( 'jquery' ), '1.0', true );
        wp_enqueue_style( 'evergreen-posts-style', plugin_dir_url( __FILE__ ) . 'css/evergreen-posts.css' );
    }
}
add_action( 'admin_enqueue_scripts', 'evergreen_posts_enqueue_scripts' );

// Add the "Evergreen" button to the Edit Post screen
function evergreen_posts_add_button() {
    global $post;

    // Security nonce for verification
    wp_nonce_field( 'evergreen_save_post', 'evergreen_nonce' );

    $evergreen_checked = checked(get_post_meta($post->ID, 'evergreen_post', true), 1, false);

    // Output the button HTML with the updated CSS class
    echo '<div class="misc-pub-section misc-pub-evergreen">';
    echo '<label for="evergreen-post" class="selectit">';
    echo '<input type="checkbox" name="evergreen_post" id="evergreen-post" value="1" ' . $evergreen_checked . '>';
    echo 'Evergreen';
    echo '</label>';
    echo '</div>';
}
add_action( 'post_submitbox_misc_actions', 'evergreen_posts_add_button' );

// Save the evergreen status when the checkbox is checked/unchecked
function evergreen_posts_save_status($post_id) {
    // Verify nonce
    if (!isset($_POST['evergreen_nonce']) || !wp_verify_nonce($_POST['evergreen_nonce'], 'evergreen_save_post')) {
        return;
    }

    // Validate user capabilities
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['evergreen_post'])) {
        update_post_meta($post_id, 'evergreen_post', 1);
    } else {
        delete_post_meta($post_id, 'evergreen_post');
    }
}
add_action( 'save_post', 'evergreen_posts_save_status' );

// Add the "Evergreen" column to the All Posts screen
function evergreen_posts_add_column($columns) {
    $columns['evergreen'] = 'Evergreen';
    return $columns;
}
add_filter( 'manage_posts_columns', 'evergreen_posts_add_column' );

// Display the evergreen status in the "Evergreen" column
function evergreen_posts_display_column($column, $post_id) {
    if ($column === 'evergreen') {
        if (get_post_meta($post_id, 'evergreen_post', true)) {
            echo '<span class="evergreen-indicator">Evergreen</span>';
        }
    }
}
add_action( 'manage_posts_custom_column', 'evergreen_posts_display_column', 10, 2 );

// Add custom menu item under "Posts"
function evergreen_posts_menu_item() {
    add_posts_page('Evergreen', 'Evergreen', 'edit_posts', 'evergreen-posts', 'evergreen_posts_page');
}
add_action('admin_menu', 'evergreen_posts_menu_item');

// Callback function for the Evergreen page
function evergreen_posts_page() {
    $post_type = 'post';

    // Prepare arguments for WP_Query
    $args = array(
        'post_type' => $post_type,
        'meta_key' => 'evergreen_post',
        'meta_value' => '1',
        'posts_per_page' => -1,
    );

    // Perform WP_Query
    $evergreen_posts = new WP_Query($args);

    // Output the Evergreen posts in a format similar to All Posts
    if ($evergreen_posts->have_posts()) {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Evergreen</h1>
            <p class="wp-heading-inline">Posts are in order of Publish Date.</p>
            <hr class="wp-header-end">

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-cb check-column"><input type="checkbox"></th>
                        <th scope="col" class="manage-column column-title column-primary">Title</th>
                        <th scope="col" class="manage-column column-author">Author</th>
                        <th scope="col" class="manage-column column-categories">Categories</th>
                        <th scope="col" class="manage-column column-tags">Tags</th>
                        <th scope="col" class="manage-column column-comments">Comments</th>
                        <th scope="col" class="manage-column column-date">Date</th>
                    </tr>
                </thead>
                <tbody id="the-list">
                    <?php
                    while ($evergreen_posts->have_posts()) {
                        $evergreen_posts->the_post();
                        $post_id = get_the_ID();
                        ?>
                        <tr>
                            <th scope="row" class="check-column"><input type="checkbox"></th>
                            <td class="title column-title has-row-actions column-primary">
                                <strong>
                                    <a class="row-title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </strong>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo esc_url(get_edit_post_link($post_id)); ?>">Edit</a>
                                    </span>
                                </div>
                            </td>
                            <td class="author column-author"><?php the_author(); ?></td>
                            <td class="categories column-categories"><?php the_category(', '); ?></td>
                            <td class="tags column-tags"><?php the_tags('', ', '); ?></td>
                            <td class="comments column-comments">
                                <?php
                                $comment_count = get_comments_number();
                                echo '<a href="' . esc_url(get_comments_link()) . '">' . $comment_count . '</a>';
                                ?>
                            </td>
                            <td class="date column-date"><?php the_time(get_option('date_format')); ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    } else {
        echo '<div class="wrap"><h1 class="wp-heading-inline">Evergreen</h1><p>No Evergreen posts found.</p></div>';
    }

    // Restore original post data
    wp_reset_postdata();
}
?>
