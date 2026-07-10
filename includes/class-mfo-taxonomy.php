<?php

defined( 'ABSPATH' ) || exit;

class MFO_Taxonomy {
	const TAXONOMY      = 'mfo_media_folder';
	const ORDER_META    = '_mfo_order';
	const ALL_FOLDER    = -1;
	const UNCATEGORIZED = 0;

	public function register() {
		$labels = array(
			'name'          => __( 'Media Folders', 'media-folder-organizer' ),
			'singular_name' => __( 'Media Folder', 'media-folder-organizer' ),
		);

		register_taxonomy(
			self::TAXONOMY,
			array( 'attachment' ),
			array(
				'labels'                => $labels,
				'hierarchical'          => true,
				'public'                => false,
				'publicly_queryable'    => false,
				'show_ui'               => false,
				'show_in_menu'          => false,
				'show_in_nav_menus'     => false,
				'show_tagcloud'         => false,
				'show_in_quick_edit'    => false,
				'show_admin_column'     => false,
				'show_in_rest'          => false,
				'query_var'             => false,
				'rewrite'               => false,
				'update_count_callback' => '_update_generic_term_count',
				'capabilities'          => array(
					'manage_terms' => 'upload_files',
					'edit_terms'   => 'upload_files',
					'delete_terms' => 'upload_files',
					'assign_terms' => 'upload_files',
				),
			)
		);

		register_term_meta(
			self::TAXONOMY,
			self::ORDER_META,
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => false,
			)
		);
	}

	public function get_terms() {
		$terms = get_terms(
			array(
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$orders = array();
		foreach ( $terms as $term ) {
			$orders[ $term->term_id ] = (int) get_term_meta( $term->term_id, self::ORDER_META, true );
		}

		usort(
			$terms,
			static function ( $left, $right ) use ( $orders ) {
				if ( (int) $left->parent !== (int) $right->parent ) {
					return (int) $left->parent <=> (int) $right->parent;
				}

				$order_compare = $orders[ $left->term_id ] <=> $orders[ $right->term_id ];
				if ( 0 !== $order_compare ) {
					return $order_compare;
				}

				return strnatcasecmp( $left->name, $right->name );
			}
		);

		return $terms;
	}

	public function get_tree_data() {
		$terms         = $this->get_terms();
		$direct_counts = $this->get_direct_counts();
		$children      = array();

		foreach ( $terms as $term ) {
			$parent = (int) $term->parent;
			if ( ! isset( $children[ $parent ] ) ) {
				$children[ $parent ] = array();
			}
			$children[ $parent ][] = $term;
		}

		return $this->build_branch( 0, 0, $children, $direct_counts );
	}

	private function build_branch( $parent, $level, $children, $direct_counts ) {
		$branch = array();

		if ( empty( $children[ $parent ] ) ) {
			return $branch;
		}

		foreach ( $children[ $parent ] as $term ) {
			$child_nodes = $this->build_branch( (int) $term->term_id, $level + 1, $children, $direct_counts );
			$count       = isset( $direct_counts[ $term->term_id ] ) ? $direct_counts[ $term->term_id ] : 0;

			foreach ( $child_nodes as $child_node ) {
				$count += (int) $child_node['count'];
			}

			$branch[] = array(
				'id'           => (int) $term->term_id,
				'name'         => $term->name,
				'parent'       => (int) $term->parent,
				'order'        => (int) get_term_meta( $term->term_id, self::ORDER_META, true ),
				'level'        => (int) $level,
				'count'        => (int) $count,
				'direct_count' => isset( $direct_counts[ $term->term_id ] ) ? (int) $direct_counts[ $term->term_id ] : 0,
				'children'     => $child_nodes,
			);
		}

		return $branch;
	}

	public function flatten_tree( $tree = null, $output = array() ) {
		if ( null === $tree ) {
			$tree = $this->get_tree_data();
		}

		foreach ( $tree as $node ) {
			$children         = $node['children'];
			$node['children'] = array();
			$output[]         = $node;
			if ( $children ) {
				$output = $this->flatten_tree( $children, $output );
			}
		}

		return $output;
	}

	public function get_direct_counts() {
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT tt.term_id, COUNT(DISTINCT tr.object_id) AS attachment_count
			FROM {$wpdb->term_taxonomy} tt
			INNER JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
			INNER JOIN {$wpdb->posts} p ON p.ID = tr.object_id
			WHERE tt.taxonomy = %s
				AND p.post_type = 'attachment'
				AND p.post_status IN ('inherit', 'private')
			GROUP BY tt.term_id",
			self::TAXONOMY
		);

		$rows   = $wpdb->get_results( $sql );
		$counts = array();

		foreach ( $rows as $row ) {
			$counts[ (int) $row->term_id ] = (int) $row->attachment_count;
		}

		return $counts;
	}

	public function get_uncategorized_count() {
		$query = new WP_Query(
			array(
				'post_type'              => 'attachment',
				'post_status'            => array( 'inherit', 'private' ),
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'tax_query'              => array(
					array(
						'taxonomy' => self::TAXONOMY,
						'operator' => 'NOT EXISTS',
					),
				),
			)
		);

		return (int) $query->found_posts;
	}

	public function get_total_count() {
		$query = new WP_Query(
			array(
				'post_type'              => 'attachment',
				'post_status'            => array( 'inherit', 'private' ),
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		return (int) $query->found_posts;
	}

	public function create_folder( $name, $parent = 0 ) {
		$name   = sanitize_text_field( wp_unslash( $name ) );
		$parent = absint( $parent );

		if ( '' === $name ) {
			return new WP_Error( 'mfo_empty_name', __( 'Folder name cannot be empty.', 'media-folder-organizer' ), array( 'status' => 400 ) );
		}

		if ( $parent && ! $this->folder_exists( $parent ) ) {
			return new WP_Error( 'mfo_invalid_parent', __( 'The parent folder does not exist.', 'media-folder-organizer' ), array( 'status' => 400 ) );
		}

		if ( $this->term_exists_id( $name, $parent ) ) {
			return new WP_Error( 'mfo_duplicate_name', __( 'A folder with this name already exists at this level.', 'media-folder-organizer' ), array( 'status' => 409 ) );
		}

		$order  = $this->next_order( $parent );
		$result = wp_insert_term(
			$name,
			self::TAXONOMY,
			array(
				'parent' => $parent,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		update_term_meta( $result['term_id'], self::ORDER_META, $order );

		return (int) $result['term_id'];
	}

	public function update_folder( $folder_id, $name, $parent = null ) {
		$folder_id = absint( $folder_id );
		$term      = get_term( $folder_id, self::TAXONOMY );

		if ( ! $term || is_wp_error( $term ) ) {
			return new WP_Error( 'mfo_folder_not_found', __( 'Folder not found.', 'media-folder-organizer' ), array( 'status' => 404 ) );
		}

		$args = array();

		if ( null !== $name ) {
			$name = sanitize_text_field( wp_unslash( $name ) );
			if ( '' === $name ) {
				return new WP_Error( 'mfo_empty_name', __( 'Folder name cannot be empty.', 'media-folder-organizer' ), array( 'status' => 400 ) );
			}

			$args['name'] = $name;
		}

		if ( null !== $parent ) {
			$parent = absint( $parent );
			if ( $parent === $folder_id || $this->is_descendant( $parent, $folder_id ) ) {
				return new WP_Error( 'mfo_invalid_parent', __( 'A folder cannot be moved inside itself or one of its descendants.', 'media-folder-organizer' ), array( 'status' => 400 ) );
			}
			if ( $parent && ! $this->folder_exists( $parent ) ) {
				return new WP_Error( 'mfo_invalid_parent', __( 'The parent folder does not exist.', 'media-folder-organizer' ), array( 'status' => 400 ) );
			}
			$args['parent'] = $parent;
		}

		$target_name   = isset( $args['name'] ) ? $args['name'] : $term->name;
		$target_parent = isset( $args['parent'] ) ? $args['parent'] : (int) $term->parent;
		$existing_id   = $this->term_exists_id( $target_name, $target_parent );
		if ( $existing_id && $existing_id !== $folder_id ) {
			return new WP_Error( 'mfo_duplicate_name', __( 'A folder with this name already exists at this level.', 'media-folder-organizer' ), array( 'status' => 409 ) );
		}

		if ( ! $args ) {
			return $folder_id;
		}

		$result = wp_update_term( $folder_id, self::TAXONOMY, $args );

		return is_wp_error( $result ) ? $result : $folder_id;
	}

	public function delete_folder( $folder_id ) {
		$folder_id = absint( $folder_id );
		if ( ! $this->folder_exists( $folder_id ) ) {
			return new WP_Error( 'mfo_folder_not_found', __( 'Folder not found.', 'media-folder-organizer' ), array( 'status' => 404 ) );
		}

		$children = get_term_children( $folder_id, self::TAXONOMY );
		if ( is_wp_error( $children ) ) {
			return $children;
		}

		$children = array_map( 'absint', $children );
		usort(
			$children,
			static function ( $left, $right ) {
				$left_depth  = count( get_ancestors( $left, self::TAXONOMY, 'taxonomy' ) );
				$right_depth = count( get_ancestors( $right, self::TAXONOMY, 'taxonomy' ) );
				return $right_depth <=> $left_depth;
			}
		);

		foreach ( $children as $child_id ) {
			$result = wp_delete_term( $child_id, self::TAXONOMY );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			if ( false === $result ) {
				return new WP_Error( 'mfo_delete_failed', __( 'A subfolder could not be deleted.', 'media-folder-organizer' ), array( 'status' => 500 ) );
			}
		}

		$result = wp_delete_term( $folder_id, self::TAXONOMY );
		if ( false === $result ) {
			return new WP_Error( 'mfo_delete_failed', __( 'The folder could not be deleted.', 'media-folder-organizer' ), array( 'status' => 500 ) );
		}

		return $result;
	}

	public function reorder( $items ) {
		if ( ! is_array( $items ) ) {
			return new WP_Error( 'mfo_invalid_order', __( 'Invalid folder order data.', 'media-folder-organizer' ), array( 'status' => 400 ) );
		}

		$terms        = $this->get_terms();
		$existing_ids = array_map( 'absint', wp_list_pluck( $terms, 'term_id' ) );
		$updates      = array();
		$parent_map   = array();

		foreach ( $items as $item ) {
			$id     = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
			$parent = isset( $item['parent'] ) ? absint( $item['parent'] ) : 0;
			$order  = isset( $item['order'] ) ? absint( $item['order'] ) : 0;

			if ( ! $id || ! in_array( $id, $existing_ids, true ) || ( $parent && ! in_array( $parent, $existing_ids, true ) ) ) {
				return new WP_Error( 'mfo_invalid_order', __( 'Folder order contains an unknown folder.', 'media-folder-organizer' ), array( 'status' => 400 ) );
			}
			if ( isset( $updates[ $id ] ) ) {
				return new WP_Error( 'mfo_invalid_order', __( 'Folder order contains a duplicate folder.', 'media-folder-organizer' ), array( 'status' => 400 ) );
			}
			if ( $id === $parent ) {
				return new WP_Error( 'mfo_invalid_parent', __( 'The requested folder structure contains a cycle.', 'media-folder-organizer' ), array( 'status' => 400 ) );
			}

			$updates[ $id ] = array(
				'id'     => $id,
				'parent' => $parent,
				'order'  => $order,
			);
			$parent_map[ $id ] = $parent;
		}

		$submitted_ids = array_keys( $updates );
		sort( $submitted_ids );
		sort( $existing_ids );
		if ( $submitted_ids !== $existing_ids ) {
			return new WP_Error( 'mfo_incomplete_order', __( 'Folder order must include every folder exactly once.', 'media-folder-organizer' ), array( 'status' => 400 ) );
		}

		foreach ( $submitted_ids as $id ) {
			$seen    = array();
			$current = $id;

			while ( $current ) {
				if ( isset( $seen[ $current ] ) ) {
					return new WP_Error( 'mfo_invalid_parent', __( 'The requested folder structure contains a cycle.', 'media-folder-organizer' ), array( 'status' => 400 ) );
				}
				$seen[ $current ] = true;
				$current          = isset( $parent_map[ $current ] ) ? $parent_map[ $current ] : 0;
			}
		}

		$term_map = array();
		foreach ( $terms as $term ) {
			$term_map[ (int) $term->term_id ] = $term;
		}

		$slugs_by_parent = array();
		foreach ( $updates as $update ) {
			$key = $update['parent'] . ':' . $term_map[ $update['id'] ]->slug;
			if ( isset( $slugs_by_parent[ $key ] ) ) {
				return new WP_Error( 'mfo_duplicate_name', __( 'Two folders with the same name cannot be placed at the same level.', 'media-folder-organizer' ), array( 'status' => 409 ) );
			}
			$slugs_by_parent[ $key ] = true;
		}

		$depths = array();
		foreach ( $submitted_ids as $id ) {
			$depth   = 0;
			$current = $parent_map[ $id ];
			while ( $current ) {
				++$depth;
				$current = $parent_map[ $current ];
			}
			$depths[ $id ] = $depth;
		}

		uasort(
			$updates,
			static function ( $left, $right ) use ( $depths ) {
				if ( $depths[ $left['id'] ] === $depths[ $right['id'] ] ) {
					return $left['order'] <=> $right['order'];
				}
				return $depths[ $left['id'] ] <=> $depths[ $right['id'] ];
			}
		);

		$applied = array();
		foreach ( $updates as $update ) {
			$id = $update['id'];
			if ( (int) $term_map[ $id ]->parent === $update['parent'] ) {
				continue;
			}

			$result = wp_update_term( $id, self::TAXONOMY, array( 'parent' => $update['parent'] ) );
			if ( is_wp_error( $result ) ) {
				foreach ( array_reverse( $applied ) as $applied_id ) {
					wp_update_term( $applied_id, self::TAXONOMY, array( 'parent' => (int) $term_map[ $applied_id ]->parent ) );
				}

				return new WP_Error( 'mfo_reorder_failed', __( 'The folder hierarchy could not be saved.', 'media-folder-organizer' ), array( 'status' => 409, 'cause' => $result->get_error_code() ) );
			}

			$applied[] = $id;
		}

		foreach ( $updates as $update ) {
			update_term_meta( $update['id'], self::ORDER_META, $update['order'] );
		}

		return true;
	}

	public function assign_attachments( $attachment_ids, $folder_id ) {
		$folder_id = (int) $folder_id;

		if ( $folder_id < 0 ) {
			return new WP_Error( 'mfo_invalid_folder', __( 'Choose a folder or Uncategorized as the destination.', 'media-folder-organizer' ), array( 'status' => 400 ) );
		}

		if ( $folder_id > 0 && ! $this->folder_exists( $folder_id ) ) {
			return new WP_Error( 'mfo_folder_not_found', __( 'Folder not found.', 'media-folder-organizer' ), array( 'status' => 404 ) );
		}

		$updated = array();
		foreach ( array_unique( array_map( 'absint', (array) $attachment_ids ) ) as $attachment_id ) {
			if ( 'attachment' !== get_post_type( $attachment_id ) || ! current_user_can( 'edit_post', $attachment_id ) ) {
				continue;
			}

			$result = wp_set_object_terms(
				$attachment_id,
				$folder_id > 0 ? array( $folder_id ) : array(),
				self::TAXONOMY,
				false
			);

			if ( ! is_wp_error( $result ) ) {
				$updated[] = $attachment_id;
			}
		}

		return $updated;
	}

	public function get_attachment_folder_id( $attachment_id ) {
		$term_ids = wp_get_object_terms(
			absint( $attachment_id ),
			self::TAXONOMY,
			array( 'fields' => 'ids' )
		);

		if ( is_wp_error( $term_ids ) || ! $term_ids ) {
			return 0;
		}

		return (int) reset( $term_ids );
	}

	public function folder_exists( $folder_id ) {
		$term = get_term( absint( $folder_id ), self::TAXONOMY );
		return $term && ! is_wp_error( $term );
	}

	private function next_order( $parent ) {
		$terms = get_terms(
			array(
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => false,
				'parent'     => absint( $parent ),
				'fields'     => 'ids',
			)
		);

		$max = -1;
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term_id ) {
				$max = max( $max, (int) get_term_meta( $term_id, self::ORDER_META, true ) );
			}
		}

		return $max + 1;
	}

	private function is_descendant( $candidate_id, $folder_id ) {
		if ( ! $candidate_id ) {
			return false;
		}

		$ancestors = get_ancestors( $candidate_id, self::TAXONOMY, 'taxonomy' );
		return in_array( absint( $folder_id ), array_map( 'absint', $ancestors ), true );
	}

	private function term_exists_id( $name, $parent ) {
		$existing = term_exists( $name, self::TAXONOMY, absint( $parent ) );

		if ( is_array( $existing ) && isset( $existing['term_id'] ) ) {
			return absint( $existing['term_id'] );
		}

		return is_numeric( $existing ) ? absint( $existing ) : 0;
	}
}
