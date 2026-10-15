<?php
/**
 * Settings page (Settings API).
 *
 * @package BestRelatedPosts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds the options page.
 */
function boposts_admin_menu() {
	add_options_page(
		__( 'Best Related Posts', 'best-related-posts' ),
		__( 'Best Related Posts', 'best-related-posts' ),
		'manage_options',
		'best-related-posts',
		'boposts_render_settings_page'
	);
}
add_action( 'admin_menu', 'boposts_admin_menu' );

/**
 * Registers the setting, sections and fields.
 */
function boposts_admin_init() {
	register_setting(
		'boposts_group',
		'boposts',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'boposts_sanitize_options',
			'default'           => boposts_default_options(),
		)
	);

	add_settings_section(
		'boposts_template',
		__( 'Template', 'best-related-posts' ),
		'boposts_section_template_help',
		'best-related-posts'
	);
	add_settings_field( 'header', __( 'Header', 'best-related-posts' ), 'boposts_field_textarea', 'best-related-posts', 'boposts_template', array( 'key' => 'header' ) );
	add_settings_field( 'body', __( 'Body (repeated per post)', 'best-related-posts' ), 'boposts_field_textarea', 'best-related-posts', 'boposts_template', array( 'key' => 'body' ) );
	add_settings_field( 'footer', __( 'Footer', 'best-related-posts' ), 'boposts_field_textarea', 'best-related-posts', 'boposts_template', array( 'key' => 'footer' ) );

	add_settings_section(
		'boposts_config',
		__( 'Configuration', 'best-related-posts' ),
		'__return_false',
		'best-related-posts'
	);
	add_settings_field( 'max', __( 'Max posts to show', 'best-related-posts' ), 'boposts_field_number', 'best-related-posts', 'boposts_config', array( 'key' => 'max', 'min' => 1, 'max' => 20 ) );
	add_settings_field( 'excerpt', __( 'Excerpt length (characters)', 'best-related-posts' ), 'boposts_field_number', 'best-related-posts', 'boposts_config', array( 'key' => 'excerpt', 'min' => 0, 'max' => 1000 ) );
	add_settings_field( 'auto_append', __( 'Automatic display', 'best-related-posts' ), 'boposts_field_checkbox', 'best-related-posts', 'boposts_config', array( 'key' => 'auto_append', 'label' => __( 'Append the related posts list after the content of single posts', 'best-related-posts' ) ) );
	add_settings_field( 'featured_image', __( 'Thumbnails', 'best-related-posts' ), 'boposts_field_checkbox', 'best-related-posts', 'boposts_config', array( 'key' => 'featured_image', 'label' => __( 'Use the featured image when set (falls back to the first image in the post)', 'best-related-posts' ) ) );
	add_settings_field( 'post_types', __( 'Post types', 'best-related-posts' ), 'boposts_field_post_types', 'best-related-posts', 'boposts_config' );
}
add_action( 'admin_init', 'boposts_admin_init' );

/**
 * Help text for the template section.
 */
function boposts_section_template_help() {
	echo '<p>' . esc_html__( 'Placeholders available in the body: {title} for the post title, {link} for the permalink, {excerpt} for the content excerpt, {image} for the thumbnail URL.', 'best-related-posts' ) . '</p>';
}

/**
 * Textarea field.
 *
 * @param array $args Field args.
 */
function boposts_field_textarea( $args ) {
	$options = boposts_get_options( true );
	printf(
		'<textarea name="boposts[%1$s]" id="boposts_%1$s" rows="5" cols="75" class="large-text code">%2$s</textarea>',
		esc_attr( $args['key'] ),
		esc_textarea( $options[ $args['key'] ] )
	);
}

/**
 * Number field.
 *
 * @param array $args Field args.
 */
function boposts_field_number( $args ) {
	$options = boposts_get_options( true );
	printf(
		'<input type="number" name="boposts[%1$s]" id="boposts_%1$s" value="%2$s" min="%3$d" max="%4$d" class="small-text" />',
		esc_attr( $args['key'] ),
		esc_attr( $options[ $args['key'] ] ),
		(int) $args['min'],
		(int) $args['max']
	);
}

/**
 * Checkbox field.
 *
 * @param array $args Field args.
 */
function boposts_field_checkbox( $args ) {
	$options = boposts_get_options( true );
	printf(
		'<label><input type="checkbox" name="boposts[%1$s]" id="boposts_%1$s" value="1" %2$s /> %3$s</label>',
		esc_attr( $args['key'] ),
		checked( ! empty( $options[ $args['key'] ] ), true, false ),
		esc_html( $args['label'] )
	);
}

/**
 * Post type checkboxes.
 */
function boposts_field_post_types() {
	$options  = boposts_get_options( true );
	$selected = (array) $options['post_types'];
	$types    = get_post_types( array( 'public' => true ), 'objects' );
	unset( $types['attachment'] );

	foreach ( $types as $type ) {
		printf(
			'<label style="display:block"><input type="checkbox" name="boposts[post_types][]" value="%1$s" %2$s /> %3$s</label>',
			esc_attr( $type->name ),
			checked( in_array( $type->name, $selected, true ), true, false ),
			esc_html( $type->labels->name )
		);
	}
	echo '<p class="description">' . esc_html__( 'Related posts are only chosen from these post types.', 'best-related-posts' ) . '</p>';
}

/**
 * Renders the settings page.
 */
function boposts_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Best Related Posts', 'best-related-posts' ); ?></h1>

		<h2><?php esc_html_e( 'How to display the list', 'best-related-posts' ); ?></h2>
		<ul>
			<li><?php esc_html_e( 'Turn on "Automatic display" below to append it after every single post, or', 'best-related-posts' ); ?></li>
			<li><?php esc_html_e( 'add the "Best Related Posts" block in the editor, or', 'best-related-posts' ); ?></li>
			<li><?php esc_html_e( 'use the shortcode', 'best-related-posts' ); ?> <code>[best_related_posts max="4"]</code>, <?php esc_html_e( 'or', 'best-related-posts' ); ?></li>
			<li><?php esc_html_e( 'call this in your theme template:', 'best-related-posts' ); ?> <code>&lt;?php if ( function_exists( 'boposts_show' ) ) { boposts_show(); } ?&gt;</code></li>
		</ul>

		<form method="post" action="options.php">
			<?php
			settings_fields( 'boposts_group' );
			echo '<input type="hidden" name="boposts[_form]" value="1" />';
			do_settings_sections( 'best-related-posts' );
			submit_button();
			?>
		</form>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:1em">
			<?php wp_nonce_field( 'boposts_reset' ); ?>
			<input type="hidden" name="action" value="boposts_reset" />
			<?php submit_button( __( 'Revert to Defaults', 'best-related-posts' ), 'secondary', 'submit', false, array( 'onclick' => "return confirm('" . esc_js( __( 'Reset all settings to their defaults?', 'best-related-posts' ) ) . "');" ) ); ?>
		</form>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block">
			<?php wp_nonce_field( 'boposts_clear_cache' ); ?>
			<input type="hidden" name="action" value="boposts_clear_cache" />
			<?php submit_button( __( 'Clear related posts cache', 'best-related-posts' ), 'secondary', 'submit', false ); ?>
		</form>
	</div>
	<?php
}

/**
 * Handles "Revert to Defaults".
 */
function boposts_handle_reset() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do that.', 'best-related-posts' ) );
	}
	check_admin_referer( 'boposts_reset' );

	update_option( 'boposts', boposts_default_options() );
	boposts_bump_cache_generation();

	wp_safe_redirect( add_query_arg( array( 'page' => 'best-related-posts', 'boposts-reset' => '1' ), admin_url( 'options-general.php' ) ) );
	exit;
}
add_action( 'admin_post_boposts_reset', 'boposts_handle_reset' );

/**
 * Handles "Clear cache".
 */
function boposts_handle_clear_cache() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do that.', 'best-related-posts' ) );
	}
	check_admin_referer( 'boposts_clear_cache' );

	boposts_bump_cache_generation();

	wp_safe_redirect( add_query_arg( array( 'page' => 'best-related-posts', 'boposts-cleared' => '1' ), admin_url( 'options-general.php' ) ) );
	exit;
}
add_action( 'admin_post_boposts_clear_cache', 'boposts_handle_clear_cache' );

/**
 * Admin notices after reset / clear cache.
 */
function boposts_admin_notices() {
	if ( ! isset( $_GET['page'] ) || 'best-related-posts' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	if ( ! empty( $_GET['boposts-reset'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings reverted to defaults.', 'best-related-posts' ) . '</p></div>';
	}
	if ( ! empty( $_GET['boposts-cleared'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Related posts cache cleared.', 'best-related-posts' ) . '</p></div>';
	}
}
add_action( 'admin_notices', 'boposts_admin_notices' );

/**
 * "Settings" link on the plugins list.
 *
 * @param string[] $links Action links.
 * @return string[]
 */
function boposts_action_links( $links ) {
	$links[] = '<a href="' . esc_url( admin_url( 'options-general.php?page=best-related-posts' ) ) . '">' . esc_html__( 'Settings', 'best-related-posts' ) . '</a>';
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( BOPOSTS_FILE ), 'boposts_action_links' );
