<?php

function pb_locations( WP_REST_Request $request ) {

	$parameters = $request->get_params();

	$locations = new WP_Query([
		'post_type' => 'pb_location',
		'posts_per_page' => -1,
   		'order' => 'ASC',
    	'orderby' => 'title'
	]);

	if( !empty( $locations->posts ) ) {

		$locations_data = [];

		foreach($locations->posts as $location) {

			if( get_field('map', $location->ID) ) {

				$location_data = pb_location( $location );

				if( $location_data ) {

					$locations_data[] = $location_data;

				}

			}

		}

		if( !empty( $locations_data ) ) {

			return $locations_data;

		}

	}

	return false;

}


function pb_location( $location ) {

	$data = ['id' => $location->ID];

	$lat = get_field('lat', $location->ID);

	$lng = get_field('lng', $location->ID);

	$url = get_field('url', $location->ID);

	if( $url || ( $lat && $lng ) ) {

		if( $url ) {

			$data['url'] = $url;

		}

		if( $lat && $lng ) {

			$data = array_merge($data, ['lat' => $lat, 'lng' => $lng]);

		}

		return $data;

	}

	return false;

}


