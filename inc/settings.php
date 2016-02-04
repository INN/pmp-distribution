<?php

/**
 * Add PMP distribution settings to the PMP menu
 *
 * @since 0.0.1
 */
function pmp_dist_admin_menu() {
	add_submenu_page(
		'pmp-search',
		'Distribution settings',
		'Distribution settings',
		'manage_options',
		'pmp-manage-dist-settings',
		'pmp_manage_dist_settings'
	);
}
add_action('admin_menu', 'pmp_dist_admin_menu', 10);

/**
 * Print the distribution settings panel
 *
 * @since 0.0.1
 */
function pmp_manage_dist_settings() {

}
