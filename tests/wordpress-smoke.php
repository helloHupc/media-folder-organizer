<?php

if ( '1' !== getenv( 'MFO_ALLOW_DESTRUCTIVE_TESTS' ) ) {
	fwrite( STDERR, "Set MFO_ALLOW_DESTRUCTIVE_TESTS=1 to run this test.\n" );
	exit( 2 );
}

$wp_root = getenv( 'MFO_WP_ROOT' );
if ( ! $wp_root ) {
	$wp_root = '/tmp/mctest_wp';
}

require $wp_root . '/wp-load.php';
require_once dirname( __DIR__ ) . '/media-folder-organizer.php';

function mfo_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function mfo_test_error_code( $value, $code, $message ) {
	mfo_test_assert( is_wp_error( $value ), $message . ' (expected WP_Error)' );
	mfo_test_assert( $code === $value->get_error_code(), $message . ' (unexpected error code)' );
}

$site_url = get_option( 'siteurl' );
if ( false === strpos( $site_url, '127.0.0.1' ) && false === strpos( $site_url, 'localhost' ) ) {
	fwrite( STDERR, "Refusing to run against a non-local WordPress site: {$site_url}\n" );
	exit( 2 );
}

wp_set_current_user( 1 );
mfo_test_assert( current_user_can( 'upload_files' ), 'Test user must be able to upload files.' );

$taxonomy = new MFO_Taxonomy();
$taxonomy->register();

$existing_terms = get_terms(
	array(
		'taxonomy'   => MFO_Taxonomy::TAXONOMY,
		'hide_empty' => false,
		'fields'     => 'ids',
	)
);
if ( ! is_wp_error( $existing_terms ) ) {
	foreach ( array_map( 'absint', $existing_terms ) as $term_id ) {
		wp_delete_term( $term_id, MFO_Taxonomy::TAXONOMY );
	}
}

$attachment_ids = array();

try {
	$baseline_uncategorized = $taxonomy->get_uncategorized_count();
	$baseline_total         = $taxonomy->get_total_count();

	$root_id  = $taxonomy->create_folder( 'Smoke Root' );
	$child_id = $taxonomy->create_folder( 'Smoke Child', $root_id );
	$deep_id  = $taxonomy->create_folder( 'Smoke Deep', $child_id );
	$other_id = $taxonomy->create_folder( 'Smoke Other' );

	mfo_test_assert( is_int( $root_id ) && $root_id > 0, 'Root folder was not created.' );
	mfo_test_assert( is_int( $child_id ) && $child_id > 0, 'Child folder was not created.' );
	mfo_test_assert( is_int( $deep_id ) && $deep_id > 0, 'Deep folder was not created.' );
	mfo_test_assert( is_int( $other_id ) && $other_id > 0, 'Second root folder was not created.' );

	mfo_test_error_code(
		$taxonomy->create_folder( 'Smoke Child', $root_id ),
		'mfo_duplicate_name',
		'Duplicate sibling names must be rejected.'
	);
	mfo_test_error_code(
		$taxonomy->update_folder( $root_id, null, $deep_id ),
		'mfo_invalid_parent',
		'Moving a folder into its descendant must be rejected.'
	);

	for ( $index = 1; $index <= 3; ++$index ) {
		$attachment_id = wp_insert_attachment(
			array(
				'post_title'     => 'MFO Smoke Attachment ' . $index,
				'post_status'    => 'inherit',
				'post_mime_type' => 'image/jpeg',
			)
		);
		mfo_test_assert( ! is_wp_error( $attachment_id ) && $attachment_id > 0, 'Test attachment was not created.' );
		$attachment_ids[] = (int) $attachment_id;
	}

	mfo_test_assert(
		$baseline_uncategorized + 3 === $taxonomy->get_uncategorized_count(),
		'New attachments should initially be uncategorized.'
	);
	mfo_test_assert(
		$baseline_total + 3 === $taxonomy->get_total_count(),
		'All media count should include newly created attachments.'
	);

	$assigned = $taxonomy->assign_attachments( array( $attachment_ids[0] ), $deep_id );
	mfo_test_assert( array( $attachment_ids[0] ) === $assigned, 'Attachment was not assigned to the deep folder.' );
	$assigned = $taxonomy->assign_attachments( array( $attachment_ids[1] ), $root_id );
	mfo_test_assert( array( $attachment_ids[1] ) === $assigned, 'Attachment was not assigned to the root folder.' );
	mfo_test_error_code(
		$taxonomy->assign_attachments( array( $attachment_ids[2] ), MFO_Taxonomy::ALL_FOLDER ),
		'mfo_invalid_folder',
		'All media must not be accepted as a move destination.'
	);

	mfo_test_assert(
		$baseline_uncategorized + 1 === $taxonomy->get_uncategorized_count(),
		'Only one smoke attachment should remain uncategorized after assignment.'
	);

	$parent_query = new WP_Query(
		array(
			'post_type'      => 'attachment',
			'post_status'    => array( 'inherit', 'private' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'tax_query'      => array(
				array(
					'taxonomy'         => MFO_Taxonomy::TAXONOMY,
					'field'            => 'term_id',
					'terms'            => array( $root_id ),
					'include_children' => true,
				),
			),
		)
	);
	$parent_ids = array_map( 'absint', $parent_query->posts );
	sort( $parent_ids );
	$expected_parent_ids = array( $attachment_ids[0], $attachment_ids[1] );
	sort( $expected_parent_ids );
	mfo_test_assert( $expected_parent_ids === $parent_ids, 'Parent folder query must include attachments in descendants.' );

	$admin = new MFO_Admin( $taxonomy );
	$_REQUEST['query']['mfo_folder'] = (string) $root_id;
	$modal_args = $admin->filter_media_modal_query(
		array(
			'post_type'      => 'attachment',
			'post_status'    => array( 'inherit', 'private' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);
	unset( $_REQUEST['query']['mfo_folder'] );
	$modal_query = new WP_Query( $modal_args );
	$modal_ids   = array_map( 'absint', $modal_query->posts );
	sort( $modal_ids );
	mfo_test_assert( $expected_parent_ids === $modal_ids, 'Media modal Ajax query should filter by the requested folder.' );

	$tree = $taxonomy->get_tree_data();
	mfo_test_assert( 2 === $tree[0]['count'], 'Root aggregate count should include its descendants.' );
	mfo_test_assert( 1 === $tree[0]['direct_count'], 'Root direct count should exclude descendants.' );

	$reordered = $taxonomy->reorder(
		array(
			array( 'id' => $child_id, 'parent' => 0, 'order' => 0 ),
			array( 'id' => $other_id, 'parent' => 0, 'order' => 1 ),
			array( 'id' => $root_id, 'parent' => $child_id, 'order' => 0 ),
			array( 'id' => $deep_id, 'parent' => $root_id, 'order' => 0 ),
		)
	);
	mfo_test_assert( true === $reordered, 'Valid hierarchy reorder should succeed.' );
	mfo_test_assert( $child_id === (int) get_term( $root_id, MFO_Taxonomy::TAXONOMY )->parent, 'Root folder was not moved below its former child.' );
	mfo_test_assert( $root_id === (int) get_term( $deep_id, MFO_Taxonomy::TAXONOMY )->parent, 'Deep folder parent was not updated.' );

	mfo_test_error_code(
		$taxonomy->reorder(
			array(
				array( 'id' => $child_id, 'parent' => $deep_id, 'order' => 0 ),
				array( 'id' => $root_id, 'parent' => $child_id, 'order' => 0 ),
				array( 'id' => $deep_id, 'parent' => $root_id, 'order' => 0 ),
				array( 'id' => $other_id, 'parent' => 0, 'order' => 1 ),
			)
		),
		'mfo_invalid_parent',
		'Cyclic reorder data must be rejected.'
	);

	mfo_test_error_code(
		$taxonomy->reorder(
			array(
				array( 'id' => $child_id, 'parent' => 0, 'order' => 0 ),
			)
		),
		'mfo_incomplete_order',
		'Partial reorder data must be rejected.'
	);

	$deleted = $taxonomy->delete_folder( $child_id );
	mfo_test_assert( ! is_wp_error( $deleted ) && false !== $deleted, 'Recursive folder deletion should succeed.' );
	mfo_test_assert( ! $taxonomy->folder_exists( $child_id ), 'Deleted root folder still exists.' );
	mfo_test_assert( ! $taxonomy->folder_exists( $root_id ), 'Deleted descendant folder still exists.' );
	mfo_test_assert( ! $taxonomy->folder_exists( $deep_id ), 'Deleted deep folder still exists.' );
	mfo_test_assert( $taxonomy->folder_exists( $other_id ), 'Unrelated folder should not be deleted.' );
	mfo_test_assert( 0 === $taxonomy->get_attachment_folder_id( $attachment_ids[0] ), 'Attachment in a deleted folder should become uncategorized.' );
	mfo_test_assert( 0 === $taxonomy->get_attachment_folder_id( $attachment_ids[1] ), 'Attachment in a deleted parent folder should become uncategorized.' );
	mfo_test_assert(
		$baseline_uncategorized + 3 === $taxonomy->get_uncategorized_count(),
		'Deleting folders should return their attachments to Uncategorized.'
	);

	echo "WordPress smoke tests passed.\n";
} finally {
	foreach ( $attachment_ids as $attachment_id ) {
		wp_delete_attachment( $attachment_id, true );
	}

	$term_ids = get_terms(
		array(
			'taxonomy'   => MFO_Taxonomy::TAXONOMY,
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);
	if ( ! is_wp_error( $term_ids ) ) {
		foreach ( array_map( 'absint', $term_ids ) as $term_id ) {
			wp_delete_term( $term_id, MFO_Taxonomy::TAXONOMY );
		}
	}
}
