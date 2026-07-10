<?php

defined( 'ABSPATH' ) || exit;

class MFO_REST_Controller {
	const NAMESPACE = 'mfo/v1';

	/**
	 * @var MFO_Taxonomy
	 */
	private $taxonomy;

	public function __construct( MFO_Taxonomy $taxonomy ) {
		$this->taxonomy = $taxonomy;
	}

	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/folders',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_folders' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_folder' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'name'   => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'parent' => array(
							'default'           => 0,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/folders/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_folder' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_folder' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/folders/reorder',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'reorder_folders' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => array(
					'items' => array(
						'required' => true,
						'type'     => 'array',
						'items'    => array(
							'type'       => 'object',
							'properties' => array(
								'id'     => array( 'type' => 'integer' ),
								'parent' => array( 'type' => 'integer' ),
								'order'  => array( 'type' => 'integer' ),
							),
						),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/assign',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'assign_attachments' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => array(
					'attachment_ids' => array(
						'required' => true,
						'type'     => 'array',
						'items'    => array( 'type' => 'integer' ),
					),
					'folder_id'      => array(
						'required' => true,
						'type'     => 'integer',
						'minimum'  => 0,
					),
				),
			)
		);
	}

	public function permissions_check() {
		return current_user_can( 'upload_files' );
	}

	public function get_folders() {
		return rest_ensure_response( $this->response_data() );
	}

	public function create_folder( WP_REST_Request $request ) {
		$result = $this->taxonomy->create_folder(
			$request->get_param( 'name' ),
			$request->get_param( 'parent' )
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $this->response_data(), 201 );
	}

	public function update_folder( WP_REST_Request $request ) {
		$name   = $request->has_param( 'name' ) ? $request->get_param( 'name' ) : null;
		$parent = $request->has_param( 'parent' ) ? $request->get_param( 'parent' ) : null;
		$result = $this->taxonomy->update_folder( $request['id'], $name, $parent );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $this->response_data() );
	}

	public function delete_folder( WP_REST_Request $request ) {
		$result = $this->taxonomy->delete_folder( $request['id'] );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $this->response_data() );
	}

	public function reorder_folders( WP_REST_Request $request ) {
		$result = $this->taxonomy->reorder( $request->get_param( 'items' ) );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $this->response_data() );
	}

	public function assign_attachments( WP_REST_Request $request ) {
		$attachment_ids = $request->get_param( 'attachment_ids' );
		$folder_id      = (int) $request->get_param( 'folder_id' );

		if ( ! is_array( $attachment_ids ) || ! $attachment_ids ) {
			return new WP_Error( 'mfo_no_attachments', __( 'Select at least one media item.', 'media-folder-organizer' ), array( 'status' => 400 ) );
		}

		$result = $this->taxonomy->assign_attachments( $attachment_ids, $folder_id );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'updated' => $result,
				'folders' => $this->response_data(),
			)
		);
	}

	private function response_data() {
		$tree = $this->taxonomy->get_tree_data();

		return array(
			'tree'                => $tree,
			'flat'                => $this->taxonomy->flatten_tree( $tree ),
			'total_count'         => $this->taxonomy->get_total_count(),
			'uncategorized_count' => $this->taxonomy->get_uncategorized_count(),
		);
	}
}
