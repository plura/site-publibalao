<?php

/**
 * in order for this function to work with revslider it's necessary to hack the add_slide_main_image
 *
 * hack:
 * 
 * 	- include the following in the revslider/includes/output.class.php add_slide_main_image method,
 *
 * 		after this line:
 *		
 *			$img = $this->get_image_data();
 *
 * 		add:
 * 		
 * 			//PLURA
 *		  	if( function_exists('p_revslider_bg_img') ) {
 *
 *				$img = p_revslider_bg_img( $img, $this->slider_id );
 *
 *			} 
 *			
 * For categories, the ACF plugin and an id value returned by a 'featured_image' image field are required by default.
 * 
 * @param  string $img 	revolution image src (if it exists) 
 * @return array|null
 */
function p_revslider_bg_img( $imgData, $sliderID = false ) {

	$obj = get_queried_object();

	//check if filter function exists
    if( has_filter('p_revslider_bg_img') ) {

		$data = apply_filters('p_revslider_bg_img', $imgData, intval( $sliderID ) );

		//if image data is returned
		if( $data && is_array( $data ) ) {

			return $data;

		//othewise it uses the functions default formula for pages/posts/categories
		//if a data "false" value is returned, no modification to revslider bg formula should be made
		} else if( $data && !empty( $imgData ) && is_array( $imgData ) ) {

			if( is_int( $data ) ) {

				$id = $data;

			} else if( is_singular() && ( has_post_thumbnail() || ( function_exists('p_wpml_id') && has_post_thumbnail( p_wpml_id() ) ) ) ) {

				$id = function_exists('p_wpml_id') ? get_post_thumbnail_id( p_wpml_id() ) : get_post_thumbnail_id();

			//categories should use ACF in order to retrieve a 'featured_image' field value
			} else if( class_exists('ACF') && isset( $obj->term_id ) ) {

				$id = get_field('featured_image', $obj );
			
			}

			if( isset( $id ) ) {

				$src = wp_get_attachment_image_src( $id, 'full' );

				if( $src ) {

					preg_match('/\/([^\/]+)\.[0-9a-z]+$/', $src[0], $matches);

					$imgData = array_merge( $imgData, [
						'src' => $src[0],
						'width' => $src[1],
						'height' => $src[2],
						'title' => $matches[1],
						'data-lazyload' => $src[0]
					]);

				}

			}

		}

	}

	return $imgData;

}


/**
 * 
 * in order to use this, it's necessary to create a "featured_video" custom field (ie. post, cpt, page, term)
 * 
 * hack:
 * 
 * 	- include the following in the revslider/includes/output.class.php add_background_video method,
 *
 * 		after this line:
 *		
 *			$type = $slide->get_param(array('bg', 'type'), 'trans'); 
 *
 * 		add:
 * 		
 * 			//PLURA
 *		  	if( function_exists('p_revslider_bg_video') && p_revslider_bg_video() ) {
 *
 *				$this->add_html_background_video();
 *
 *				return;
 *
 *			}
 *
 *	- include the following in the revslider/includes/output.class.php add_html_background_video method,
 *
 * 		before this line:
 *
 * 			echo $this->ld().RS_T7.'<rs-bgvideo '."\n";
 *
 * 		add:
 *
 *			//PLURA
 *		 	if( function_exists('p_revslider_bg_video') && p_revslider_bg_video() ) {
 *
 *				$data = p_revslider_bg_video_data();
 *
 *			}
 *
 *  
 * @return null
 */
function p_revslider_bg_video_data() {

    $id = function_exists('p_wpml_id') ? p_wpml_id( get_the_ID() ) : get_the_ID();

	$file = get_field('featured_video', $id );

	if( $file ) {

		return [

			'video' => [
				'w' => '100%',
				'h' => '100%',
				'nse' => 0,
				'l' => 1,
				'ptimer' => null,
				'vfc' => 1
			],

			'mp4' => preg_replace('/https?:/', '', $file['url'])
		];

	}

}


function p_revslider_bg_video() {

	$obj = get_queried_object();

	if( class_exists('ACF') && ( is_singular() || isset( $obj->term_id )  ) ) {

        if( isset( $obj->term_id ) ) {

            $id = $obj;

        } else {

            $id = function_exists('p_wpml_id') ? p_wpml_id( $obj->ID ) : $obj->ID;

        }

		return get_field('featured_video', $id );

	}

	return false;

}



/*
(
    [video] => Array
        (
            [w] => 100%
            [h] => 100%
            [nse] => false
            [l] => 1 //loop
            [ptimer] => 
            [vfc] => 1 //video fit cover
        )

    [mp4] => //amaro-vanveggel.com/wp/wp-content/uploads/2023/03/Taryn_Elliott_video1_optimized.mp4
)
 */
