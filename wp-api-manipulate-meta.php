<?php
/**
 * Plugin Name: Manipulate Meta with the WP API
 * Plugin URI: https://github.com/csalzano/wp-api-add-post-parent
 * Description: Adds routes to the REST API to read, write, and delete post and term meta values separately from posts.
 * Version: 1.0.0
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

	function delete_post_meta( $request )
	{
		return rest_ensure_response( delete_post_meta( $this->get_object_id( $request ), $this->get_meta_key( $request ) ) );
	}

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

	private function get_meta_key( $request )
	{
		//$request->get_route() = /wp/v2/posts/57244/meta/test_meta_key
		$route_pieces = explode( '/', $request->get_route() );
		return isset( $route_pieces[6] ) ? $route_pieces[6] : '';
	}

	private function get_object_id( $request )
	{
		//$request->get_route() = /wp/v2/{object}/57244/meta/test_meta_key
		$route_pieces = explode( '/', $request->get_route() );
		return isset( $route_pieces[4] ) ? $route_pieces[4] : 0;
	}

	function get_post_meta( $request )
	{
		return rest_ensure_response( get_post_meta( $this->get_object_id( $request ), $this->get_meta_key( $request ), true ) );
	}

	function get_term_meta( $request )
	{
		return rest_ensure_response( get_term_meta( $this->get_object_id( $request ), $this->get_meta_key( $request ), true ) );
	}

	function add_post_meta_routes()
	{
		foreach( $this->public_api_post_types() as $post_type )
		{
			$rest_base = $this->find_rest_base( $post_type );
			$route = '/' . $rest_base . '/([0-9]+)/meta/([a-zA-Z0-9\-_]+)';

			error_log( '$route = ' . $route );

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

	function update_post_meta( $request )
	{
		//TODO check has_param
		return rest_ensure_response( update_post_meta( $this->get_object_id( $request ), $this->get_meta_key( $request ), $request->get_param( 'value' ) ) );
	}

	function update_term_meta( $request )
	{
		//TODO check has_param
		return rest_ensure_response( update_term_meta( $this->get_object_id( $request ), $this->get_meta_key( $request ), $request->get_param( 'value' ) ) );
	}
}
$manipulate_meta_2934870234723 = new WP_API_Manipulate_Meta();
$manipulate_meta_2934870234723->hooks();
