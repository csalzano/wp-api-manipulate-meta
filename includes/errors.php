<?php

namespace WP_API_Manipulate_Meta
{
	class Errors
	{
		public static function cannot_determine_object_type( $rest_base )
		{
			return new \WP_Error(
				'rest_cannot_determine_object_type',
				__( 'Could not determine whether `' . $rest_base . '` is a post or a taxonomy. Is the post or taxonomy enabled in the REST API? Does it\'s registration specify a `rest_base`?' ),
				array( 'status' => 400 )
			);
		}

		public static function invalid_keys_array()
		{
			return new \WP_Error(
				'rest_invalid_keys_array',
				__( 'The body of the request is missing an array of meta keys to delete called `keys`.' ),
				array( 'status' => 400 )
			);
		}
	}
}
