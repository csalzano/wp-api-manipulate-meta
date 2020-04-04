<?php
/**
 * Plugin Name: Manipulate Meta with the WP API
 * Plugin URI: https://github.com/csalzano/wp-api-add-post-parent
 * Description: Adds routes to the REST API to read, write, and delete post and term meta values separately from posts.
 * Version: 1.4.2
 * Author: Corey Salzano
 * Author URI: https://profiles.wordpress.org/salzano
 * Text Domain: wp-api-manipulate-meta
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

class WP_API_Manipulate_Meta_Registrant
{
	function hooks()
	{
		//Allow translations of strings
		add_action( 'plugins_loaded', function() {
			load_plugin_textdomain( 'wp-api-manipulate-meta', false, __DIR__ );
		} );

		//Setup a class auto-loader
		spl_autoload_register( array( $this, 'autoloader' ) );

		add_action( 'rest_api_init', array( $this, 'add_routes' ) );
	}


	function add_routes()
	{
		$object_types = $this->public_api_post_types() + $this->public_api_taxonomies();
		foreach( $object_types as $object_type )
		{
			$rest_base = $this->find_rest_base( $object_type );

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
					'callback' => array( $this, 'get_meta' ),
				)
			);

			register_rest_route(
				'wp/v2',
				$route,
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_meta' ),
					'permission_callback' => array( $this, 'have_create_permission'),
					'args'                => array(
						'value' => array(
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				)
			);

			register_rest_route(
				'wp/v2',
				$route,
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_meta' ),
					'permission_callback' => array( $this, 'have_delete_permission' ),
				)
			);

			/**
			 * Create a route that allows one request to delete any number of
			 * meta values.
			 */
			$route = '/' . $rest_base . '/([0-9]+)/meta';
			register_rest_route(
				'wp/v2',
				$route,
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_meta_bulk' ),
					'permission_callback' => array( $this, 'have_delete_permission' ),
					'args'                => array(
						'keys' => array(
							'validate_callback' => function( $param, $request, $key )
							{
								return is_array( $param );
							},
						),
					),
				)
			);
		}
	}

	/**
	 * A last-chance class autoloader for spl_autoload_register()
	 *
	 * Takes a class name Errors and includes a file with the path includes/errors.php
	 *
	 * @param string The name of a class that is being instantiated
	 * @return void
	 */
	function autoloader( $class_path )
	{
		$namespace = 'WP_API_Manipulate_Meta';
		if( $namespace != substr( $class_path, 0, strlen( $namespace ) ) )
		{
			return;
		}

		$class_pieces = explode( '\\', $class_path );
		array_shift( $class_pieces );
		$path = '\\includes\\' . strtolower( implode( '\\', $class_pieces ) ) . '.php';
		$path = untrailingslashit( plugin_dir_path( __FILE__ ) ) . str_replace( '_', '-', str_replace( '\\', DIRECTORY_SEPARATOR, strtolower( $path ) ) );

		file_exists( $path ) && require $path;
	}

	/**
	 * Deletes a single meta value and returns the API response to the client.
	 * REST API route callback method.
	 *
	 * @param WP_REST_Request $request
	 */
	function delete_meta( $request )
	{
		$rest_base = $this->get_rest_base( $request );
		if( $this->object_is_post( $rest_base ) )
		{
			return rest_ensure_response( delete_post_meta( $this->get_object_id( $request ), $this->get_meta_key( $request ) ) );
		}

		if( $this->object_is_term( $rest_base ) )
		{
			return rest_ensure_response( delete_term_meta( $this->get_object_id( $request ), $this->get_meta_key( $request ) ) );
		}

		return rest_ensure_response(  WP_API_Manipulate_Meta\Errors::cannot_determine_object_type( $rest_base ) );
	}

	/**
	 * Deletes multiple meta values identified by an array of meta keys in the
	 * request body. Returns the API response to the client. REST API route
	 * callback method.
	 *
	 * @param WP_REST_Request $request
	 */
	function delete_meta_bulk( $request )
	{
		$keys_to_delete = $request->get_param( 'keys' );
		if( empty( $keys_to_delete ) )
		{
			//bad request
			return rest_ensure_response( WP_API_Manipulate_Meta\Errors::invalid_keys_array() );
		}

		$rest_base = $this->get_rest_base( $request );
		$object_is_post = false;
		$object_is_term = false;
		if( $this->object_is_post( $rest_base ) )
		{
			$object_is_post = true;
		}
		elseif( $this->object_is_term( $rest_base ) )
		{
			$object_is_term = true;
		}
		else
		{
			return rest_ensure_response(  WP_API_Manipulate_Meta\Errors::cannot_determine_object_type( $rest_base ) );
		}

		$post_id = $this->get_object_id( $request );
		$results = array();

		foreach( $keys_to_delete as $key )
		{
			if( $object_is_post )
			{
				$results[] = delete_post_meta( $post_id, $key );
			}
			elseif( $object_is_term )
			{
				$results[] = delete_term_meta( $term_id, $key );
			}
		}
		return rest_ensure_response( $results );
	}

	private function find_object_capability( $rest_base, $cap_slug )
	{
		$object_type = $this->find_object_type( $rest_base );
		if( ! $object_type )
		{
			return '';
		}

		if( ! empty( $object_type->cap->$cap_slug ) )
		{
			return $object_type->cap->$cap_slug;
		}

		return '';
	}

	/**
	 * @return WP_Post|WP_Taxonomy|false
	 */
	private function find_object_type( $rest_base )
	{
		$post_types_by_rest_base = $this->public_api_post_types( array( 'rest_base' => $rest_base ) );
		if( ! empty( $post_types_by_rest_base ) )
		{
			return array_values( $post_types_by_rest_base )[0];
		}

		$post_types_by_name = $this->public_api_post_types( array( 'name' => $rest_base ) );
		if( ! empty( $post_types_by_name ) )
		{
			return array_values( $post_types_by_name )[0];
		}

		$taxonomies_by_rest_base = $this->public_api_taxonomies( array( 'rest_base' => $rest_base ) );
		if( ! empty( $taxonomies_by_rest_base ) )
		{
			return array_values( $taxonomies_by_rest_base )[0];
		}

		$taxonomies_by_name = $this->public_api_taxonomies( array( 'name' => $rest_base ) );
		if( ! empty( $taxonomies_by_name ) )
		{
			return array_values( $taxonomies_by_name )[0];
		}

		return false;
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
	function get_meta( $request )
	{
		$rest_base = $this->get_rest_base( $request );
		if( $this->object_is_post( $rest_base ) )
		{
			return rest_ensure_response( get_post_meta( $this->get_object_id( $request ), $this->get_meta_key( $request ), true ) );
		}

		if( $this->object_is_term( $rest_base ) )
		{
			return rest_ensure_response( get_term_meta( $this->get_object_id( $request ), $this->get_meta_key( $request ), true ) );
		}

		return rest_ensure_response( WP_API_Manipulate_Meta\Errors::cannot_determine_object_type( $rest_base ) );
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
	private function get_rest_base( $request )
	{
		//$request->get_route() = /wp/v2/{object_type}/{object_id}/meta/{meta_key}
		$route_pieces = explode( '/', $request->get_route() );
		return isset( $route_pieces[3] ) ? $route_pieces[3] : '';
	}

	/**
	 * Check if a given request has access to create items
	 *
	 * @param WP_REST_Request $request
	 * @return WP_Error|bool
	 */
	function have_create_permission( $request )
	{
		$rest_base = $this->get_rest_base( $request );
		$cap_slug = '';
		if( $this->object_is_post( $rest_base ) )
		{
			$cap_slug = 'edit_post';
		}
		elseif( $this->object_is_term( $rest_base ) )
		{
			$cap_slug = 'edit_terms';
		}
		else
		{
			return false;
		}

		//Translate $cap_slug into this object's capability name
		$specific_slug = $this->find_object_capability( $rest_base, $cap_slug );
		if( empty( $specific_slug ) )
		{
			return false;
		}
		return current_user_can( $specific_slug, $this->get_object_id( $request ) );
	}

	/**
	 * Check if a given request has access to delete items
	 *
	 * @param WP_REST_Request $request
	 * @return WP_Error|bool
	 */
	function have_delete_permission( $request )
	{
		$rest_base = $this->get_rest_base( $request );
		$cap_slug = '';
		if( $this->object_is_post( $rest_base ) )
		{
			$cap_slug = 'delete_post';
		}
		elseif( $this->object_is_term( $rest_base ) )
		{
			$cap_slug = 'delete_terms';
		}
		else
		{
			return false;
		}

		//Translate $cap_slug into this object's capability name
		$specific_slug = $this->find_object_capability( $rest_base, $cap_slug );
		if( empty( $specific_slug ) )
		{
			return false;
		}
		return current_user_can( $specific_slug, $this->get_object_id( $request ) );
	}

	/**
	 * Using the base string, determine whether the object being queried in the REST API is a post.
	 *
	 * @param string $rest_base The rest_base attribute of a post type definition, or it's name if a rest_base was not provided.
	 * @return boolean True if the object belonging to the provided $rest_base is a Post
	 */
	private function object_is_post( $rest_base )
	{
		if( empty( $rest_base ) )
		{
			return false;
		}

		return ! empty( $this->public_api_post_types( array( 'rest_base' => $rest_base ) ) )
			|| ! empty( $this->public_api_post_types( array( 'name' => $rest_base ) ) );
	}

	/**
	 * Using the base string, determine whether the object being queried in the REST API is a term.
	 *
	 * @param string $rest_base The rest_base attribute of a taxonomy definition, or it's name if a rest_base was not provided.
	 * @return boolean True if the object belonging to the provided $rest_base is a Term
	 */
	private function object_is_term( $rest_base )
	{
		if( empty( $rest_base ) )
		{
			return false;
		}
		return ! empty( $this->public_api_taxonomies( array( 'rest_base' => $rest_base ) ) )
			|| ! empty( $this->public_api_taxonomies( array( 'name' => $rest_base ) ) );
	}

	/**
	 * @param Array $additional_args An associative array of additional arguments to pass into get_post_types()
	 * @return Array An array of all public and API-exposted post type objects
	 */
	private function public_api_post_types( $additional_args = array() )
	{
		if( ! is_array( $additional_args ) )
		{
			$additional_args = array();
		}

		$args = wp_parse_args( $additional_args, array(
			'public'       => true,
			'show_in_rest' => true,
		) );
		return get_post_types( $args, 'objects' );
	}

	/**
	 * @param Array $additional_args An associative array of additional arguments to pass into get_taxonomies()
	 * @return Array An array of all public and API-exposed taxonomy objects
	 */
	private function public_api_taxonomies( $additional_args = array() )
	{
		if( ! is_array( $additional_args ) )
		{
			$additional_args = array();
		}

		$args = wp_parse_args( $additional_args, array(
			'public'       => true,
			'show_in_rest' => true,
		) );
		return get_taxonomies( $args, 'objects' );
	}

	/**
	 * @param WP_REST_Request $request
	 */
	function update_meta( $request )
	{
		$rest_base = $this->get_rest_base( $request );
		if( $this->object_is_post( $rest_base ) )
		{
			return rest_ensure_response( update_post_meta( $this->get_object_id( $request ), $this->get_meta_key( $request ), $request->get_param( 'value' ) ) );
		}

		if( $this->object_is_term( $rest_base ) )
		{
			return rest_ensure_response( update_term_meta( $this->get_object_id( $request ), $this->get_meta_key( $request ), $request->get_param( 'value' ) ) );
		}

		return rest_ensure_response( WP_API_Manipulate_Meta\Errors::cannot_determine_object_type( $rest_base ) );
	}
}
$manipulate_meta_2934870234723 = new WP_API_Manipulate_Meta_Registrant();
$manipulate_meta_2934870234723->hooks();
