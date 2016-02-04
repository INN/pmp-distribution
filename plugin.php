<?php
/**
 * Plugin Name: Public Media Platform Distribution Extension
 * Plugin URI: https://nerds.inn.org
 * Description: An extension for managing distribution of content from several organizations via the PMP
 * Author: INN nerds
 * Version: latest
 * Author URI: https://nerds.inn.org
 * License: MIT
 */

/**
 * Plugin set up
 *
 * Depends on the PMP WordPress plugin being installed.
 *
 * @since 0.0.1
 */
function pmp_dist_init() {
	define('PMP_DIST_PLUGIN_DIR', __DIR__);
	define('PMP_DIST_PLUGIN_DIR_URI', plugins_url(basename(__DIR__), __DIR__));
	define('PMP_DIST_VERSION', '0.0.1');

	$includes = array(
		'inc/assets.php',
		'inc/functions.php',
		'inc/settings.php',
		'inc/ajax.php'
	);

	foreach ($includes as $include)
		include_once PMP_DIST_PLUGIN_DIR . '/' . $include;
}
add_action('pmp_after_init', 'pmp_dist_init');
