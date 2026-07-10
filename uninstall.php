<?php

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

register_taxonomy(
	'mfo_media_folder',
	array( 'attachment' ),
	array(
		'hierarchical' => true,
		'public'       => false,
		'show_ui'      => false,
	)
);

$term_ids = get_terms(
	array(
		'taxonomy'   => 'mfo_media_folder',
		'hide_empty' => false,
		'fields'     => 'ids',
	)
);

if ( ! is_wp_error( $term_ids ) ) {
	foreach ( array_map( 'absint', $term_ids ) as $term_id ) {
		wp_delete_term( $term_id, 'mfo_media_folder' );
	}
}

delete_option( 'mfo_version' );
