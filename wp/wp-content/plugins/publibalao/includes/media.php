<?php


/**
 * 		. Grid
 * 			- Grid Item 
 * 		. Images Carousel
 */


//Grid
function pb_grid( $args ) {

	$query_vars = ['post_type' => $args['type']];

	if( !empty( $args['ids'] ) ) {

		$query_vars = array_merge( $query_vars, [
			'orderby' => 'post__in',
			'post__in' => explode(',', $args['ids'])
		]);

	}

	if( !empty( $args['rand'] ) && preg_match('/(true|1)/', $args['rand'] ) ) {

		$query_vars['orderby'] = 'rand';

	} 

	if( !empty( $args['exclude'] ) ) {

		if( preg_match('/(true|1)/', $args['exclude'] ) ) {

			$query_vars['post__not_in'] = [ get_the_ID() ];

		} else {

			$query_vars['post__not_in'] = explode(',', $args['exclude']);

		}

	}


	$query = new WP_Query( $query_vars );

	if( count( $query->posts ) ) {

		$classes = array_merge( ['pb-grid', 'pb-el'], explode(' ', $args['class'] ) );

		$type = preg_match('/pb_/', $args['type']) ? preg_replace('/pb_/', '', $args['type']) : $args['type'];

		$atts = ['class' => implode(' ', $classes), 'data-type' => $type ];

		foreach( ['layout'] as $arg ) {

			if( !empty( $args[ $arg ] ) ) {

				$atts[ 'data-' . $arg ] = $args[ $arg ];

			}

		}

		$html = "<div " . p_attributes( $atts ) . ">";

		foreach( $query->posts as $post ) {

			$html .= pb_grid_item( $post );

		}

		return $html . "</div>";

	}

}


function pb_grid_shortcode( $args ) {

	$atts = shortcode_atts( array(
		'class' => '',
		'exclude' => '',
		'ids' => '',
		'layout' => '',
		'rand' => '',
		'type' => 'any'

	), $args );

	return pb_grid( $atts );

}

add_shortcode('pb-grid', 'pb_grid_shortcode');



//Grid
function pb_grid_item( $id ) {

	$post = is_int( $id ) || ctype_digit( $id ) ? get_post( $id ) : $id;

	$post_label = get_field( get_post_type( $post ) . '_label', $post->ID );

	$atts = ['class' => 'pb-grid-item pb-el', 'data-id' => $post->ID, 'href' => get_permalink( $post->ID )];

	$atts_label = ['class' => 'pb-grid-item-label pb-el'];

	$atts_title = ['class' => 'pb-grid-item-title pb-el'];

	$img = p_thumbnail( $post->ID );

	if( $img ) {

		$atts = array_merge($atts, ['style' => "background-image: url('" . $img[0] . "');"]);

	}

	$html = "<a " . p_attributes( $atts ) . ">";

		$html .= "<h3 " . p_attributes($atts_title) . ">" . $post->post_title . "</h3>";

	if( $post_label ) {

		$html .= "<div " . p_attributes( $atts_label ) . ">" . $post_label . "</div>";

	}

	return  $html . "</a>";

}

function pb_grid_item_shortcode( $args ) {

	$atts = shortcode_atts( array('id' => '', 'type' => ''), $args );


	if( !empty( $atts['id'] ) ) {

		return pb_grid_item( $atts['id'] );

	}

}

add_shortcode('pb-grid-item', 'pb_grid_item_shortcode');



//Images Carousel
function pb_carousel_images( array|string|int $ids ): string|null {

	if( !is_array( $ids ) ) {

		if( is_string( $ids ) ) {
		
			$ids = array_map('intval', array_map('trim', explode(',', $ids)));

		} else {

			$ids = [ $ids ];

		}

	}

	$query = new WP_Query([
		'post_type'      => 'attachment',        // Ensures you're querying attachments (images)
		'post_mime_type' => 'image',         // Retrieves all image formats (JPEG, PNG, GIF, etc.)
		'post__in'       => $ids, 			// Array of specific image IDs
		'posts_per_page' => -1,             // Retrieve all images matching the query, no limit on the number
		'orderby'        => 'post__in',          // Order the images according to the array provided in 'post__in'
		'post_status'    => 'any',               // Include all statuses (published, inherit, draft, etc.)
	]);

	if ( $query->have_posts() ) {

		$html = [];
		
		foreach( $query->posts as $post ) {
						
			$img = wp_get_attachment_image_src($post->ID, 'full'); // Get the image URL
		
			$html[] = "<img src=\"" . $img[0] . "\" width=\"" . $img[1] . "\" height=\"" . $img[2] . "\" class=\"pb-carousel-image\" />";

		}
		
		wp_reset_postdata(); // Reset after custom query

		return "<div class=\"pb-carousel-images\">" . implode('', $html) . "</div>";

	}

	return null;

}

add_shortcode('pb-carousel-images', function( $atts ) {

	$args = shortcode_atts(['class' => '', 'ids' => ''], $atts);

	if( !empty( $args['ids'] ) ) {

		return pb_carousel_images( $args['ids'] );

	}

});