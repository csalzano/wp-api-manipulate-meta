<?php

namespace WP_API_Manipulate_Meta
{
	class Errors
	{
		public static function cannot_determine_object_type( $rest_base )
		{
			$message = sprintf(
				'%s `%s` %s',
				__( 'Could not determine whether ', 'wp-api-manipulate-meta' ),
				$rest_base,
				__( ' is a post or a taxonomy. Is the post or taxonomy enabled in the REST API? Does it\'s registration specify a `rest_base`?', 'wp-api-manipulate-meta' )
			);
			return new \WP_Error(
				'rest_cannot_determine_object_type',
				$message,
				array( 'status' => 400 )
			);
		}

		public static function invalid_keys_array()
		{
			return new \WP_Error(
				'rest_invalid_keys_array',
				__( 'The body of the request is missing an array of meta keys to delete called `keys`.', 'wp-api-manipulate-meta' ),
				array( 'status' => 400 )
			);
		}
	}
}
