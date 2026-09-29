<?php

/**
 *	. Countdown
 *	. Banner
 *	. Locations
 * 
 */

function pb_event_countdown( $id ) {

	//the timer field may hand back a post rather than an ID
	$id = $id instanceof WP_Post ? $id->ID : absint( $id );

	return do_shortcode( sprintf( '[wpcdt-countdown id="%d"]', $id ) );

}


function pb_event_countdown_shortcode( $args ) {

	$atts = shortcode_atts( array(
		'id' => '',
	), $args );

	if( ( is_singular('pb_event') && get_field('timer') ) || !empty( $atts['id'] ) ) {

		$id = !empty( $atts['id'] ) ? $atts['id'] : get_field('timer');

		return pb_event_countdown( $id );

	}

	return '';

}

add_shortcode('pb-event-countdown', 'pb_event_countdown_shortcode');



function pb_event_banner_data( $id ) {

	return get_field('banner_data', $id);

}


function pb_event_banner_data_shortcode( $args ) {

	$atts = shortcode_atts( array(
		'id' => '',
	), $args );

	if( 

		( !empty( $atts['id'] ) && get_post_type( $atts['id'] ) === 'pb_event' && get_field('banner_data', $atts['id'] ) )  || 

		( is_singular('pb_event') && get_field('banner_data') )

	) {

		$data = pb_event_banner_data( !empty( $atts['id'] ) ? $atts['id'] : get_the_ID() );

		return is_scalar( $data ) ? (string) $data : '';

	}

	return '';

}

add_shortcode('pb-event-banner-data', 'pb_event_banner_data_shortcode');




function pb_event_locations( $id, string|int|bool $link = false ) {

	if( have_rows('locations', $id) ) {

		$html = [];

		while ( have_rows('locations', $id) ): the_row();

			$location = get_sub_field('location');

			$atts = [
				'class' => 'pb-location',
				'title' => $location->post_title
			];

			if( $link ) {

				$url = get_field('url', $location->ID);

				if( $url ) {

					$atts['href'] = $url;

				}

			} else {

				$atts['href'] = get_permalink( $location );

			}

			$img = plura_wp_thumbnail( $location->ID );

			if( $img ) {

				$atts['style'] = "background-image: url('" . $img[0] . "');";

			}

			$html[] = "<a " . plura_attributes($atts) . ">" . $location->post_title . "</a>";

		endwhile;

		return "<div class=\"pb-locations\">" . implode('', $html) . "</div>";

	}

}


function pb_event_locations_shortcode( $args ) {

	$atts = shortcode_atts( [
		'id' => '',
		'link' => 0
	], $args );

	if( is_page() || !empty( $atts['id'] ) ) {

		$id = !empty( $atts['id'] ) ? $atts['id'] : get_the_ID();

		return pb_event_locations( id: $id, link: $atts['link'] );

	}	

}

add_shortcode('pb-event-locations', 'pb_event_locations_shortcode');