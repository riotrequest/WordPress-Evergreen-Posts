<?php
/**
 * Plugin Name: Evergreen Posts
 * Description: Adds Evergreen marking functionality to WordPress posts.
 * Version:     1.3
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
            echo '<span class="evergreen-indicator">' . esc_html__( 'Evergreen', 'evergreen-posts' ) . '</span>';
        }
    }
}
add_action( 'manage_posts_custom_column', 'evergreen_posts_display_column', 10, 2 );

/**
 * Add custom admin menu item under "Posts" for viewing Evergreen posts.
 */
function evergreen_posts_menu_item() {
    add_posts_page( __( 'Evergreen', 'evergreen-posts' ), __( 'Evergreen', 'evergreen-posts' ), 'edit_posts', 'evergreen-posts', 'evergreen_posts_page' );
}
add_action( 'admin_menu', 'evergreen_posts_menu_item' );

/**
 * Callback function for the Evergreen posts listing page.
 */
function evergreen_posts_page() {
    // Allow developers to filter query arguments.
    $args = apply_filters( 'evergreen_posts_query_args', array(
        'post_type'      => 'post',
        'meta_key'       => 'evergreen_post',
        'meta_value'     => '1',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );

    $evergreen_posts = new WP_Query( $args );
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php esc_html_e( 'Evergreen', 'evergreen-posts' ); ?></h1>
        <p><?php esc_html_e( 'Posts marked as Evergreen, ordered by publish date.', 'evergreen-posts' ); ?></p>
        <hr class="wp-header-end">

        <?php if ( $evergreen_posts->have_posts() ) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column"><input type="checkbox"></td>
                        <th scope="col" class="manage-column column-title column-primary"><?php esc_html_e( 'Title', 'evergreen-posts' ); ?></th>
                        <th scope="col" class="manage-column column-author"><?php esc_html_e( 'Author', 'evergreen-posts' ); ?></th>
                        <th scope="col" class="manage-column column-categories"><?php esc_html_e( 'Categories', 'evergreen-posts' ); ?></th>
                        <th scope="col" class="manage-column column-tags"><?php esc_html_e( 'Tags', 'evergreen-posts' ); ?></th>
                        <th scope="col" class="manage-column column-comments"><?php esc_html_e( 'Comments', 'evergreen-posts' ); ?></th>
                        <th scope="col" class="manage-column column-date"><?php esc_html_e( 'Date', 'evergreen-posts' ); ?></th>
                    </tr>
                </thead>
                <tbody id="the-list">
                    <?php while ( $evergreen_posts->have_posts() ) : $evergreen_posts->the_post(); ?>
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
                                        <a href="<?php echo esc_url( get_edit_post_link() ); ?>"><?php esc_html_e( 'Edit', 'evergreen-posts' ); ?></a>
                                    </span>
                                </div>
                            </td>
                            <td class="author column-author"><?php the_author(); ?></td>
                            <td class="categories column-categories"><?php echo get_the_category_list( ', ' ); ?></td>
                            <td class="tags column-tags"><?php the_tags( '', ', ' ); ?></td>
                            <td class="comments column-comments">
                                <?php
                                $comment_count = get_comments_number();
                                echo '<a href="' . esc_url( get_comments_link() ) . '">' . intval( $comment_count ) . '</a>';
                                ?>
                            </td>
                            <td class="date column-date"><?php echo esc_html( get_the_date() ); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e( 'No Evergreen posts found.', 'evergreen-posts' ); ?></p>
        <?php endif; ?>
    </div>
    <?php
    wp_reset_postdata();
}
?>
