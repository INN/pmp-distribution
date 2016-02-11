<?php

/**
 * Register plugin settings
 *
 * @since 0.0.1
 */
function pmp_dist_admin_init(){
	register_setting('pmp_dist_settings_fields', 'pmp_dist_settings');
	add_settings_section('pmp_dist_main', 'For content creators', null, 'pmp_dist_settings');
	add_settings_field('pmp_dist_default_distributor', 'Default distributor(s)', 'pmp_dist_default_distributor_input', 'pmp_dist_settings', 'pmp_dist_main');
}
add_action('admin_init', 'pmp_dist_admin_init');

/**
 * Input field for default distributor setting
 *
 * @since 0.0.1
 */
function pmp_dist_default_distributor_input() {
	$sdk = new SDKWrapper();
	$pmp_things = $sdk->query2json('queryDocs', array(
		'profile' => 'user',
		'limit' => 9999
	));

	$settings = get_option('pmp_dist_settings');
	$guids = $settings['pmp_dist_default_distributor'];
	?>
	<select id="pmp_dist_default_distributor" name="pmp_dist_settings[pmp_dist_default_distributor][]" class="chosen" multiple>
		<?php foreach ($pmp_things['items'] as $user) { ?>
		<option <?php if ( in_array($user['attributes']['guid'], $guids) ) { ?>selected<?php } ?> value="<?php echo $user['attributes']['guid']; ?>"><? echo $user['attributes']['title']; ?></option>
		<?php }  ?>
	</select>
	<script type="text/javascript">
		(function() {
			var $ = jQuery;

			$(document).ready(function() {
				$('select.chosen').chosen({disable_search_threshold: 10});
			});
		})();
	</script>

<?php
}

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
	include_once dirname(__DIR__) . '/templates/settings.php';
}
