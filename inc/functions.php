<?php

/**
 * See if a user is considered a distributor for a given post
 *
 * @since 0.0.1
 */
function pmp_user_is_distributor_of_post($post_id, $user_guid=null) {
	if (empty($user_guid))
		$user_guid = pmp_get_my_guid();

	$sdk = new SDKWrapper();
	$user = $sdk->fetchDoc($user_guid);

	$pmp_guid = get_post_meta($post_id, 'pmp_guid', true);
	if (empty($pmp_guid))
		return false;

	$doc = $sdk->fetchDoc($pmp_guid);
	if (!empty($doc->links->distributor)) {
		foreach ($doc->links->distributor as $distrib) {
			if (SDKWrapper::guid4href($distrib->href) == $user_guid) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Print distributor meta box for distributor
 *
 * @since 0.0.1
 */
function pmp_distributor_options_meta_box_for_distributor() {
	global $post;
	wp_nonce_field('pmp_dist_meta_box', 'pmp_dist_meta_box_nonce'); ?>
	<div id="pmp-dist-options" class="async-menu-container">
		<p>Set a Series and Property for this post.</p>
		<?php foreach (array('series', 'property') as $type) { ?>
		<div
			id="pmp-<?php echo $type; ?>-for-post"
			class="async-menu-option pmp-dist-option-for-post"
			data-pmp-dist-option-type="<?php echo $type; ?>">
			<span class="spinner"></span>
		</div>
		<?php } ?>
	</div>
	<div id="pmp-publish-actions">
		<?php submit_button('Update dist. options', 'primary', 'pmp_save_dist_options', false, $attrs); ?>
	</div>
	<?php
	/*
	 * Javascript required for the async select menus for Groups, Series, Property
	 */ ?>
	<script type="text/javascript">
		var PMP = <?php echo json_encode(pmp_json_obj(array('post_id' => $post->ID))); ?>;
	</script><?php

	pmp_async_select_template();
}

/**
 * Print distributor meta box for doc/post owner
 *
 * @since 0.0.1
 */
function pmp_distributor_options_meta_box_for_owner() {
	global $post;
	wp_nonce_field('pmp_dist_meta_box', 'pmp_dist_meta_box_nonce'); ?>
	<div id="pmp-dist-options" class="async-menu-container">
		<p>Set distribution group(s) for this post</p>
		<div
			id="pmp-distributor-for-post"
			class="async-menu-option pmp-dist-option-for-post"
			data-pmp-dist-option-type="distributor">
			<span class="spinner"></span>
		</div>
	</div>
	<div id="pmp-publish-actions">
		<?php submit_button('Update dist. options', 'primary', 'pmp_save_distributors_for_post', false, $attrs); ?>
	</div><?php
}

/**
 * Add distribution meta box if our user is a distributor
 *
 * @since 0.0.1
 */
function pmp_dist_meta_box() {
	$screen = get_current_screen();

	if ($screen->id == 'post') {
		global $post;

		$pmp_guid = get_post_meta($post->ID, 'pmp_guid', true);

		if (!empty($pmp_guid)) {
			if (pmp_user_is_distributor_of_post($post->ID)) {
				$callback = 'pmp_distributor_options_meta_box_for_distributor';
			} else if (pmp_post_is_mine($post->ID)) {
				$callback = 'pmp_distributor_options_meta_box_for_owner';
			} else {
				return; // Do nothing if not an owner or distributor
			}
			add_meta_box(
				'pmp_distributor_options_meta',
				'PMP: Distribution options',
				$callback,
				'post', 'side'
			);
		}
	}
}
add_action('add_meta_boxes', 'pmp_dist_meta_box');

/**
 * Save function for the PMP distribution options meta box
 *
 * @since 0.3
 */
function pmp_dist_meta_box_save($post_id) {
	if (!isset($_POST['pmp_dist_meta_box_nonce']))
		return;

	if (!wp_verify_nonce($_POST['pmp_dist_meta_box_nonce'], 'pmp_dist_meta_box'))
		return;

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
		return;

	if (!current_user_can('edit_post', $post_id))
		return;

	if (isset($_POST['pmp_save_distributors_for_post']) || isset($_POST['pmp_update_push'])) {
		pmp_save_distributors_for_post($post_id);
	} else if (isset($_POST['pmp_save_dist_options'])) {
		pmp_save_dist_options($post_id);
	}
}
add_action('save_post', 'pmp_dist_meta_box_save');

/**
 * Save function for the PMP distribution options meta box (for doc owners)
 *
 * @since 0.3
 */
function pmp_save_distributors_for_post($post_id) {
	if (!isset($_POST['pmp_save_distributors_for_post'])) {
		return;
	}

	$sdk = new SDKWrapper();
	$pmp_guid = get_post_meta($post_id, 'pmp_guid', true);
	$pmp_doc = $sdk->fetchDoc($pmp_guid);
	$pmp_doc->links->distributor = array();

	foreach ((array) $_POST['pmp_distributor_override'] as $distributor_guid) {
		if (!in_array($distributor_guid, $existing_guids)) {
			$pmp_doc->links->distributor[] = (object) array(
				'href' => $sdk->href4guid($distributor_guid)
			);
		}
	}
	$pmp_doc->save();
}

/**
 * Save/push updated distribution options (for distributors)
 *
 * @since 0.0.1
 */
function pmp_save_dist_options($post_id) {
	if (!isset($_POST['pmp_save_dist_options']))
		return;

	$sdk = new SDKWrapper();
	$pmp_guid = get_post_meta($post_id, 'pmp_guid', true);
	$pmp_doc = $sdk->fetchDoc($pmp_guid);

	$types = array('series', 'property');
	foreach ($types as $type) {
		$meta_key = 'pmp_' . $type . '_override';

		// Indicate that the $type was explicitly net to false
		if (!isset($_POST[$meta_key]) || empty($_POST[$meta_key]))
			$collection_guid = false;
		else
			$collection_guid = $_POST[$meta_key];

		if (!empty($collection_guid)) {
			$existing_guids = array();

			if (!isset($pmp_doc->links->collection)) {
				$pmp_doc->links->collection = array();
			} else {
				$existing_guids = array_map(function($item) {
					return SDKWrapper::guid4href($item->href);
				}, $pmp_doc->links->collection);
			}

			foreach ((array) $collection_guid as $guid) {
				// Preserve existing $profile links, only add ours
				if (!in_array($guid, $existing_guids)) {
					$pmp_doc->links->collection[] = (object) array(
						'href' => $sdk->href4guid($guid),
						'rels' => array("urn:collectiondoc:collection:$type"),
					);
				}
			}
		} else {
			// Unset the values that belong to us
			$our_collection_guids = (array) get_post_meta($post_id, $meta_key . '_distribution', true);
			$new_collections = array();
			foreach ($pmp_doc->links->collection as $collection) {
				if (!in_array(SDKWrapper::guid4href($collection->href), $our_collection_guids)) {
					array_push($new_collections, $collection);
				}
			}
			$pmp_doc->links->collection = $new_collections;
		}
		update_post_meta($post_id, $meta_key . '_distribution', $collection_guid);
	}
	$pmp_doc->save();
}

/**
 * Make sure distributor-set collections are preserved when pushing to the PMP
 *
 * @since 0.0.1
 */
function pmp_dist_preserve_distributor_collection($doc, $previous_collection, $post) {
	$current_guids = array_map(function($item) { return SDKWrapper::guid4href($item->href); }, $doc->links->collection);
	$previous_guids = array_map(function($item) { return SDKWrapper::guid4href($item->href); }, $previous_collection);
	$difference = array_diff($previous_guids, $current_guids);

	if (!empty($difference)) {
		$sdk = new SDKWrapper();
		foreach ($difference as $idx => $guid_to_check) {
			$collection = $sdk->fetchDoc($guid_to_check);

			foreach ($doc->links->distributor as $distributor) {
				if ($distributor->href == $collection->links->owner[0]->href) {
					$doc->links->collection[] = $previous_collection[$idx];
				}
			}
		}
	}

	return $doc;
}
add_filter('pmp_set_doc_collection', 'pmp_dist_preserve_distributor_collection', 10, 3);
