<?php
/**
 * Plugin Name: Manipulate Meta with the WP API
 * Plugin URI: https://github.com/csalzano/wp-api-add-post-parent
 * Description: Adds routes to the REST API to read, write, and delete post and term meta values separately from posts.
 * Version: 1.2.0
 * Author: Corey Salzano
 * Author URI: https://profiles.wordpress.org/salzano
 * Text Domain: wp-api-manipulate-meta
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */


class WP_API_Manipulate_Meta
{
	function hooks()
	{
		add_action( 'rest_api_init', array( $this, 'add_post_meta_routes' ) );
		add_action( 'rest_api_init', array( $this, 'add_term_meta_routes' ) );
	}

	/**
	 * Deletes a single post meta value and returns the API response to the
	 * client. REST API route callback method.
	 *
	 * @param WP_REST_Request $request
	 */
	function delete_post_meta( $request )
	{
		return rest_ensure_response( delete_post_meta( $this->get_object_id( $request ), $this->get_meta_key( $request ) ) );
	}

	/**
	 * Deletes multiple post meta values identified by an array of post meta
	 * keys in the request body. Returns the API response to the client. REST
	 * API route callback method.
	 *
	 * @param WP_REST_Request $request
	 */
	function delete_post_meta_bulk( $request )
	{
		$keys_to_delete = $request->get_param( 'keys' );
		if( empty( $keys_to_delete ) )
		{
			//bad request
			return rest_ensure_response( new WP_Error(
				'rest_invalid_keys_array',
				__( 'The body of the request is missing an array of post meta keys to delete in a member called `keys`.' ),
				array( 'status' => 400 )
			) );
		}

		$post_id = $this->get_object_id( $request );
		$results = array();

		foreach( $keys_to_delete as $key )
		{
			$results[] = delete_post_meta( $post_id, $key );
		}
		return rest_ensure_response( $results );
	}

	/**
	 * @param WP_REST_Request $request
	 */
	function delete_term_meta( $request )
	{
		return rest_ensure_response( delete_term_meta( $this->get_object_id( $request ), $this->get_meta_key( $request ) ) );
	}

	/**
	 * @param Object $object A WP_Post_Type or WP_Taxonomy
	 * @return string The route base name in the REST API
	 */
	private function find_rest_base( $object )
	{
		if( ! empty( $object->rest_base ) )
		{
			return $object->rest_base;
		}

		if( ! empty( $object->name ) )
		{
			return $object->name;
		}

		return '';
	}

	/**
	 * @param WP_REST_Request $request
	 */
	private function get_meta_key( $request )
	{
		//$request->get_route() = /wp/v2/{object_type}/{object_id}/meta/{meta_key}
		$route_pieces = explode( '/', $request->get_route() );
		return isset( $route_pieces[6] ) ? $route_pieces[6] : '';
	}

	/**
	 * @param WP_REST_Request $request
	 */
	private function get_object_id( $request )
	{
		//$request->get_route() = /wp/v2/{object_type}/{object_id}/meta/{meta_key}
		$route_pieces = explode( '/', $request->get_route() );
		return isset( $route_pieces[4] ) ? $route_pieces[4] : 0;
	}

	/**
	 * @param WP_REST_Request $request
	 */
	function get_post_meta( $request )
	{
		return rest_ensure_response( get_post_meta( $this->get_object_id( $request ), $this->get_meta_key( $request ), true ) );
	}

	/**
	 * @param WP_REST_Request $request
	 */
	function get_term_meta( $request )
	{
		return rest_ensure_response( get_term_meta( $this->get_object_id( $request ), $this->get_meta_key( $request ), true ) );
	}

	function add_post_meta_routes()
	{
		foreach( $this->public_api_post_types() as $post_type )
		{
			$rest_base = $this->find_rest_base( $post_type );

			/**
			 * Create read, write, delete routes that modify one meta key per
			 * request.
			 */
			$route = '/' . $rest_base . '/([0-9]+)/meta/([a-zA-Z0-9\-_]+)';

			register_rest_route(
				'wp/v2',
				$route,
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_post_meta' ),
				)
			);

			register_rest_route(
				'wp/v2',
				$route,
				array(
					'methods'  => WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'update_post_meta' ),
					'args'     => array(
						'value' => array(
							'validate_callback' => 'sanitize_text_field',
						),
					),
				)
			);

			register_rest_route(
				'wp/v2',
				$route,
				array(
					'methods'  => WP_REST_Server::DELETABLE,
					'callback' => array( $this, 'delete_post_meta' ),
				)
			);

			/**
			 * Create a route that allows one request to delete any number of
			 * meta values on a post.
			 */
			$route = '/' . $rest_base . '/([0-9]+)/meta';
			register_rest_route(
				'wp/v2',
				$route,
				array(
					'methods'  => WP_REST_Server::DELETABLE,
					'callback' => array( $this, 'delete_post_meta_bulk' ),
				)
			);
		}
	}

	function add_term_meta_routes()
	{
		foreach( $this->public_api_taxonomies() as $taxonomy )
		{
			$rest_base = $this->find_rest_base( $taxonomy );
			$route = '/' . $rest_base . '/([0-9]+)/meta/([a-zA-Z0-9\-_]+)';

			register_rest_route(
				'wp/v2',
				$route,
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_term_meta' ),
				)
			);

			register_rest_route(
				'wp/v2',
				$route,
				array(
					'methods'  => WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'update_term_meta' ),
					'args'     => array(
						'value' => array(
							'validate_callback' => 'sanitize_text_field',
						),
					),
				)
			);

			register_rest_route(
				'wp/v2',
				$route,
				array(
					'methods'  => WP_REST_Server::DELETABLE,
					'callback' => array( $this, 'delete_term_meta' ),
				)
			);
		}
	}

	/**
	 * @return Array An array of all public and API-exposted post type objects
	 */
	private function public_api_post_types()
	{
		return get_post_types( array(
			'public'       => true,
			'show_in_rest' => true,
		), 'objects' );
	}

	/**
	 * @return Array An array of all public and API-exposed taxonomy objects
	 */
	private function public_api_taxonomies()
	{
		return get_taxonomies( array(
			'public'       => true,
			'show_in_rest' => true,
		), 'objects' );
	}

	/**
	 * @param WP_REST_Request $request
	 */
	function update_post_meta( $request )
	{
		return rest_ensure_response( update_post_meta( $this->get_object_id( $request ), $this->get_meta_key( $request ), $request->get_param( 'value' ) ) );
	}

	/**
	 * @param WP_REST_Request $request
	 */
	function update_term_meta( $request )
	{
		return rest_ensure_response( update_term_meta( $this->get_object_id( $request ), $this->get_meta_key( $request ), $request->get_param( 'value' ) ) );
	}
}
$manipulate_meta_2934870234723 = new WP_API_Manipulate_Meta();
$manipulate_meta_2934870234723->hooks();
