<?php

/**
 * Register assets for PMP Distrbution plugin
 *
 * @since 0.0.1
 */
function pmp_dist_register_assets() {
	wp_register_script(
		'pmp-dist',
		PMP_DIST_PLUGIN_DIR_URI . '/assets/js/pmp-dist.js',
		array('jquery', 'pmp-post'),
		PMP_DIST_VERSION,
		true
	);

	wp_register_style(
		'pmp-dist',
		PMP_DIST_PLUGIN_DIR_URI . '/assets/css/pmp-dist.css'
	);
}
add_action('admin_enqueue_scripts', 'pmp_dist_register_assets', 10);

/**
 * Enqueue assets for PMP Distribution plugin
 *
 * @since 0.0.1
 */
function pmp_dist_enqueue_assets() {
	$screen = get_current_screen();

	if ( isset( $_GET['page'] ) && $_GET['page'] == 'pmp-manage-dist-settings' ) {
		wp_enqueue_script('pmp-chosen');
		wp_enqueue_style('pmp-chosen');
	}

	if ($screen->base == 'post' && $screen->post_type == 'post') {
		wp_enqueue_script('pmp-dist');
		wp_enqueue_style('pmp-dist');
	}
}
add_action('admin_enqueue_scripts', 'pmp_dist_enqueue_assets', 20);
