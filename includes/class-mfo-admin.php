<?php

defined( 'ABSPATH' ) || exit;

class MFO_Admin {
	/**
	 * @var MFO_Taxonomy
	 */
	private $taxonomy;

	public function __construct( MFO_Taxonomy $taxonomy ) {
		$this->taxonomy = $taxonomy;
	}

	public function register_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'ajax_query_attachments_args', array( $this, 'filter_media_modal_query' ), 20 );
		add_action( 'pre_get_posts', array( $this, 'filter_media_list_query' ) );
		add_action( 'restrict_manage_posts', array( $this, 'render_list_filter' ), 10, 2 );
		add_action( 'pre-upload-ui', array( $this, 'render_upload_filter' ) );
		add_action( 'add_attachment', array( $this, 'assign_uploaded_attachment' ) );
		add_filter( 'wp_prepare_attachment_for_js', array( $this, 'prepare_attachment' ), 10, 3 );
		add_filter( 'attachment_fields_to_edit', array( $this, 'attachment_fields_to_edit' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( $this, 'attachment_fields_to_save' ), 10, 2 );
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
	}

	public function enqueue_assets( $hook_suffix ) {
		if ( ! current_user_can( 'upload_files' ) ) {
			return;
		}

		$is_media_page = 'upload.php' === $hook_suffix || 'media-new.php' === $hook_suffix;
		$is_post_page  = in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true );

		if ( ! $is_media_page && ! $is_post_page ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style(
			'mfo-admin',
			MFO_URL . 'assets/css/admin.css',
			array(),
			MFO_VERSION
		);
		wp_enqueue_script(
			'mfo-admin',
			MFO_URL . 'assets/js/admin.js',
			array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable', 'media-views', 'wp-api-fetch' ),
			MFO_VERSION,
			true
		);

		$tree = $this->taxonomy->get_tree_data();
		wp_localize_script(
			'mfo-admin',
			'MFO_DATA',
			array(
				'version'             => MFO_VERSION,
				'restPath'            => '/mfo/v1',
				'restNonce'           => wp_create_nonce( 'wp_rest' ),
				'mediaUrl'            => admin_url( 'upload.php' ),
				'isMediaPage'         => 'upload.php' === $hook_suffix,
				'isListMode'          => 'list' === get_user_option( 'media_library_mode', get_current_user_id() ),
				'tree'                => $tree,
				'flat'                => $this->taxonomy->flatten_tree( $tree ),
				'totalCount'          => $this->taxonomy->get_total_count(),
				'uncategorizedCount'  => $this->taxonomy->get_uncategorized_count(),
				'strings'             => array(
					'allFolders'       => __( 'All media', 'media-folder-organizer' ),
					'uncategorized'    => __( 'Uncategorized', 'media-folder-organizer' ),
					'folders'          => __( 'Media folders', 'media-folder-organizer' ),
					'newFolder'        => __( 'New folder', 'media-folder-organizer' ),
					'newSubfolder'     => __( 'New subfolder', 'media-folder-organizer' ),
					'rename'           => __( 'Rename', 'media-folder-organizer' ),
					'delete'           => __( 'Delete', 'media-folder-organizer' ),
					'refresh'          => __( 'Refresh', 'media-folder-organizer' ),
					'moveSelected'     => __( 'Move selected media here', 'media-folder-organizer' ),
					'folderName'       => __( 'Folder name', 'media-folder-organizer' ),
					'createFolderTitle' => __( 'Create media folder', 'media-folder-organizer' ),
					'createChildTitle'  => __( 'Create subfolder', 'media-folder-organizer' ),
					'renameFolderTitle' => __( 'Rename media folder', 'media-folder-organizer' ),
					'deleteFolderTitle' => __( 'Delete media folder', 'media-folder-organizer' ),
					'deleteConfirm'     => __( 'Delete "%s" and all of its subfolders? Media files will become uncategorized.', 'media-folder-organizer' ),
					'folderNameRequired' => __( 'Enter a folder name.', 'media-folder-organizer' ),
					'cancel'             => __( 'Cancel', 'media-folder-organizer' ),
					'create'             => __( 'Create folder', 'media-folder-organizer' ),
					'saveChanges'        => __( 'Save changes', 'media-folder-organizer' ),
					'confirmDelete'      => __( 'Delete folder', 'media-folder-organizer' ),
					'selectMedia'        => __( 'Select one or more media items first.', 'media-folder-organizer' ),
					'selectDestination'  => __( 'Choose a folder or Uncategorized as the destination.', 'media-folder-organizer' ),
					'moved'              => __( 'Media moved.', 'media-folder-organizer' ),
					'saved'              => __( 'Folder order saved.', 'media-folder-organizer' ),
					'error'            => __( 'The operation could not be completed.', 'media-folder-organizer' ),
					'uploadTo'         => __( 'Upload to folder', 'media-folder-organizer' ),
					'rootDrop'         => __( 'Drop here to move to the top level', 'media-folder-organizer' ),
					'openFolders'      => __( 'Open media folders', 'media-folder-organizer' ),
					'closeFolders'     => __( 'Close media folders', 'media-folder-organizer' ),
				),
			)
		);
	}

	public function filter_media_modal_query( $query ) {
		$folder = null;

		if ( isset( $query['mfo_folder'] ) && is_scalar( $query['mfo_folder'] ) ) {
			$folder = (int) sanitize_text_field( wp_unslash( $query['mfo_folder'] ) );
		} elseif (
			isset( $_REQUEST['query'] ) &&
			is_array( $_REQUEST['query'] ) &&
			isset( $_REQUEST['query']['mfo_folder'] ) &&
			is_scalar( $_REQUEST['query']['mfo_folder'] )
		) {
			// WordPress removes custom fields before ajax_query_attachments_args runs.
			$folder = (int) sanitize_text_field( wp_unslash( $_REQUEST['query']['mfo_folder'] ) );
		}

		if ( null === $folder ) {
			return $query;
		}

		unset( $query['mfo_folder'] );

		return $this->apply_folder_query_args( $query, $folder );
	}

	public function filter_media_list_query( $query ) {
		$post_type = $query->get( 'post_type' );
		$is_media  = 'attachment' === $post_type || ( is_array( $post_type ) && in_array( 'attachment', $post_type, true ) );

		if ( ! is_admin() || ! $query->is_main_query() || ! $is_media ) {
			return;
		}

		if ( ! isset( $_GET['mfo_folder'] ) ) {
			return;
		}

		$folder = (int) sanitize_text_field( wp_unslash( $_GET['mfo_folder'] ) );
		if ( MFO_Taxonomy::ALL_FOLDER === $folder ) {
			return;
		}

		$query->set( 'tax_query', $this->merge_tax_queries( $query->get( 'tax_query' ), $this->folder_tax_query( $folder ) ) );
	}

	private function apply_folder_query_args( $query, $folder ) {
		if ( MFO_Taxonomy::ALL_FOLDER === $folder ) {
			return $query;
		}

		$existing_tax_query = isset( $query['tax_query'] ) ? $query['tax_query'] : array();
		$query['tax_query']  = $this->merge_tax_queries( $existing_tax_query, $this->folder_tax_query( $folder ) );
		return $query;
	}

	private function merge_tax_queries( $existing, $folder_query ) {
		if ( ! is_array( $existing ) || ! $existing ) {
			return $folder_query;
		}

		return array(
			'relation' => 'AND',
			$existing,
			$folder_query,
		);
	}

	private function folder_tax_query( $folder ) {
		if ( MFO_Taxonomy::UNCATEGORIZED === $folder ) {
			return array(
				array(
					'taxonomy' => MFO_Taxonomy::TAXONOMY,
					'operator' => 'NOT EXISTS',
				),
			);
		}

		return array(
			array(
				'taxonomy'         => MFO_Taxonomy::TAXONOMY,
				'field'            => 'term_id',
				'terms'            => array( absint( $folder ) ),
				'include_children' => true,
			),
		);
	}

	public function render_list_filter( $post_type, $which ) {
		if ( 'attachment' !== $post_type || 'bar' !== $which ) {
			return;
		}

		$current = isset( $_GET['mfo_folder'] ) ? (int) sanitize_text_field( wp_unslash( $_GET['mfo_folder'] ) ) : MFO_Taxonomy::ALL_FOLDER;
		echo '<label class="screen-reader-text" for="mfo-folder-filter">' . esc_html__( 'Filter by media folder', 'media-folder-organizer' ) . '</label>';
		echo '<select name="mfo_folder" id="mfo-folder-filter">';
		echo '<option value="-1"' . selected( -1, $current, false ) . '>' . esc_html__( 'All media folders', 'media-folder-organizer' ) . '</option>';
		echo '<option value="0"' . selected( 0, $current, false ) . '>' . esc_html__( 'Uncategorized', 'media-folder-organizer' ) . '</option>';
		foreach ( $this->taxonomy->flatten_tree() as $folder ) {
			printf(
				'<option value="%1$d"%2$s>%3$s%4$s</option>',
				(int) $folder['id'],
				selected( (int) $folder['id'], $current, false ),
				esc_html( str_repeat( '— ', (int) $folder['level'] ) ),
				esc_html( $folder['name'] )
			);
		}
		echo '</select>';
	}

	public function render_upload_filter() {
		if ( ! current_user_can( 'upload_files' ) ) {
			return;
		}

		echo '<div class="mfo-upload-target">';
		echo '<label for="mfo-upload-folder">' . esc_html__( 'Upload to folder', 'media-folder-organizer' ) . '</label>';
		echo '<select id="mfo-upload-folder" name="mfo_folder">';
		echo '<option value="0">' . esc_html__( 'Uncategorized', 'media-folder-organizer' ) . '</option>';
		foreach ( $this->taxonomy->flatten_tree() as $folder ) {
			printf(
				'<option value="%1$d">%2$s%3$s</option>',
				(int) $folder['id'],
				esc_html( str_repeat( '— ', (int) $folder['level'] ) ),
				esc_html( $folder['name'] )
			);
		}
		echo '</select>';
		echo '</div>';
	}

	public function assign_uploaded_attachment( $attachment_id ) {
		if ( ! current_user_can( 'upload_files' ) || ! isset( $_REQUEST['mfo_folder'] ) ) {
			return;
		}

		$folder_id = (int) sanitize_text_field( wp_unslash( $_REQUEST['mfo_folder'] ) );
		$this->taxonomy->assign_attachments( array( $attachment_id ), $folder_id );
	}

	public function prepare_attachment( $response, $attachment, $meta ) {
		$folder_id             = $this->taxonomy->get_attachment_folder_id( $attachment->ID );
		$response['mfoFolder'] = $folder_id;

		if ( $folder_id > 0 ) {
			$term                      = get_term( $folder_id, MFO_Taxonomy::TAXONOMY );
			$response['mfoFolderName'] = $term && ! is_wp_error( $term ) ? $term->name : '';
		} else {
			$response['mfoFolderName'] = __( 'Uncategorized', 'media-folder-organizer' );
		}

		return $response;
	}

	public function attachment_fields_to_edit( $fields, $post ) {
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return $fields;
		}

		$current = $this->taxonomy->get_attachment_folder_id( $post->ID );
		$html    = '<select name="attachments[' . (int) $post->ID . '][mfo_folder]">';
		$html   .= '<option value="0">' . esc_html__( 'Uncategorized', 'media-folder-organizer' ) . '</option>';

		foreach ( $this->taxonomy->flatten_tree() as $folder ) {
			$html .= sprintf(
				'<option value="%1$d"%2$s>%3$s%4$s</option>',
				(int) $folder['id'],
				selected( (int) $folder['id'], $current, false ),
				esc_html( str_repeat( '— ', (int) $folder['level'] ) ),
				esc_html( $folder['name'] )
			);
		}
		$html .= '</select>';

		$fields['mfo_folder'] = array(
			'label' => __( 'Media folder', 'media-folder-organizer' ),
			'input' => 'html',
			'html'  => $html,
		);

		return $fields;
	}

	public function attachment_fields_to_save( $post, $attachment ) {
		if ( isset( $attachment['mfo_folder'] ) && current_user_can( 'edit_post', $post['ID'] ) ) {
			$this->taxonomy->assign_attachments( array( $post['ID'] ), (int) $attachment['mfo_folder'] );
		}

		return $post;
	}

	public function admin_body_class( $classes ) {
		$screen = get_current_screen();
		if ( $screen && 'upload' === $screen->id ) {
			$classes .= ' mfo-media-library';
		}

		return $classes;
	}
}
