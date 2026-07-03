<?php


add_action( 'rest_api_init', function () {
	
    //content
    register_rest_route( 'pb/v1', '/content/', array(
		'methods' => 'GET',
		'callback' => 'pb_content',
  	) );

    //locations
	register_rest_route( 'pb/v1', '/location/', array(
		'methods' => 'GET',
		'callback' => 'pb_locations',
  	) );

} );


function pb_content( WP_REST_Request $request ) {

    $parameters = $request->get_params();

  	if( !empty( $parameters['id'] ) ) {

		$data = [];

		$ids = array_map('intval', array_map('trim', explode(',', $parameters['id'])));

		foreach( $ids as $id ) {
			
			$source_id = empty( $parameters['lang'] ) ? $id : p_wpml_id($id, $parameters['lang']);

			$content = p_post_data( $source_id );

			if( $content ) {

				$data[] = $content;

			}

		}

		return $data;

	}

}