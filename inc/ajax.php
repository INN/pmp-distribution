<?php

/**
 * Get async menu options for distributors meta box
 *
 * @since 0.0.1
 */
function pmp_dist_async_menu_options() {
	check_ajax_referer('pmp_ajax_nonce', 'security');

	$data = json_decode(stripslashes($_POST['data']), true);

	$post = get_post($data['post_id']);
	$type = $data['type'];

	if ($type == 'distributor') {
		$ret = _pmp_dist_option_select_distributor_for_post($post);
	} else {
		$ret = _pmp_dist_option_select_for_post($post, $type);
	}

	print json_encode(array_merge(array("success" => true), $ret));

	wp_die();
}
add_action('wp_ajax_pmp_dist_async_menu_options', 'pmp_dist_async_menu_options');

/**
 * Distribution options menu for post
 *
 * @since 0.0.1
 */
function _pmp_dist_option_select_for_post($post, $type) {
	$ret = array(
		'default_guid' => get_option('pmp_default_' . $type, false),
		'type' => $type
	);

	$sdk = new SDKWrapper();
	$pmp_things = $sdk->query2json('queryDocs', array(
		'profile' => $type,
		'writeable' => 'true',
		'limit' => 9999
	));

	$pmp_guid = get_post_meta($post->ID, 'pmp_guid', true);
	$pmp_doc = $sdk->query2json('fetchDoc', $pmp_guid);

	$existing_collections = $pmp_doc['items'][0]['links']['collection'];
	$existing_options = array();
	if (!empty($existing_collections)) {
		foreach($existing_collections as $collection) {
			foreach ($collection->rels as $rel) {
				if (strpos($rel, $type) !== false) {
					$collection->guid = $sdk->guid4href($collection->href);
					array_push($existing_options, $collection);
				}
			}
		}
	}

	$options = array();

	$existing_guids = array_map(function($item) { return $item->guid; }, $existing_options);

	if (!empty($pmp_things['items'])) {
		foreach ($pmp_things['items'] as $thing) {
			if (in_array($thing['attributes']['guid'], $existing_guids))
				$selected = true;
			else
				$selected = false;

			$option = array(
				'selected' => $selected,
				'guid' => $thing['attributes']['guid'],
				'title' => $thing['attributes']['title']
			);
			$options[] = $option;
		}
	}

	$ret['options'] = $options;
	return $ret;
}

/**
 * Returns data used to build select menu of users that can be set as a distributor for a post
 *
 * @since 0.0.1
 */
function _pmp_dist_option_select_distributor_for_post($post) {
	$ret = array(
		'default_guid' => get_option('pmp_default_distributor', false),
		'type' => 'distributor'
	);

	$sdk = new SDKWrapper();
	$pmp_things = $sdk->query2json('queryDocs', array(
		'profile' => 'user',
		'limit' => 9999
	));

	$pmp_guid = get_post_meta($post->ID, 'pmp_guid', true);
	$pmp_doc = $sdk->query2json('fetchDoc', $pmp_guid);

	$existing_distributors = $pmp_doc['items'][0]['links']['distributor'];
	$existing_options = array();
	if (!empty($existing_distributors)) {
		foreach($existing_distributors as $distributor) {
			$distributor->guid = $sdk->guid4href($distributor->href);
			array_push($existing_options, $distributor);
		}
	}

	$existing_guids = array_map(function($item) { return $item->guid; }, $existing_options);

	if (!empty($pmp_things['items'])) {
		foreach ($pmp_things['items'] as $thing) {
			if (in_array($thing['attributes']['guid'], $existing_guids))
				$selected = true;
			else
				$selected = false;

			$option = array(
				'selected' => $selected,
				'guid' => $thing['attributes']['guid'],
				'title' => $thing['attributes']['title']
			);
			$options[] = $option;
		}
	}

	$ret['options'] = $options;
	return $ret;
}
