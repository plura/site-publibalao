<?php

add_action( 'after_setup_theme', function() {

  load_child_theme_textdomain( 'divi', get_stylesheet_directory() . '/languages' ); 

} );



$MODULES = ['lang', /*'locations', */ 'pb'];



function my_theme_enqueue_styles() {

	$dir = get_stylesheet_directory();

	// plura_wp_data (plura plugin) already provides lang/restURL/restNonce, so pbobj
	// is left carrying only what is genuinely publibalao-specific.
	$localize_script_data = [];

	// Parent stylesheet keeps its conventional unprefixed handle.
	plura_wp_enqueue([ get_template_directory() . '/style.css' => ['handle' => 'parent-style'] ]);

	$assets = [
		$dir . '/includes/css/globals.css'        => [],
		$dir . '/includes/css/globals-header.css' => [],
		$dir . '/includes/css/fibaq-onepage.css'  => [],

		// Single entry module: the page-scoped scripts are pulled in from here with
		// dynamic import() instead of each getting its own conditional enqueue, so
		// page detection lives in one place (body classes) rather than two.
		// No fancybox dep — a module always executes after the classic footer
		// scripts that define the global.
		$dir . '/includes/js/scripts.js' => ['handle' => 'core', 'module' => true],
	];

	if( is_page( [3206, 3699] ) || ( is_page() && !in_array( get_the_ID(), [5639, 5697] ) && in_array( wp_get_post_parent_id(), [3206, 3699] ) ) ) {

		$assets[ $dir . '/includes/css/fibaq.css' ] = [];

		$localize_script_data['fibaq'] = pb_lang();

		if( is_page( [3066, 3644] ) ) {

			$assets[ $dir . '/includes/css/fibaq-register.css' ] = [];

		}

	} else if( pb_single( [2065,3614, 2851,4355, 5639] ) || is_singular('pb_event') ) {

		// Unprefixed: pb_add_integrity() below matches the 'leaflet' handle by name.
		plura_wp_enqueue([
			'https://unpkg.com/leaflet@1.7.1/dist/leaflet.css' => [ 'handle' => 'leaflet' ],
			'https://unpkg.com/leaflet@1.7.1/dist/leaflet.js'  => [ 'handle' => 'leaflet' ],
		]);

	} else if( pb_single( [2109] ) ) {

		wp_enqueue_style('pb-theme-old', get_stylesheet_directory_uri() . '/includes/old/old.css' );

		//wp_enqueue_script('pb-theme-old', get_stylesheet_directory_uri() . '/includes/js/scripts.js', $deps, time() );

	} else if( is_singular('mec-events') || is_tax('mec_category') ) {

		$assets[ $dir . '/includes/css/fibaq-mec-event-pre.css' ] = [];

		if( is_singular('mec-events') ) {

			$assets[ $dir . '/includes/css/fibaq-mec-event.css' ] = [];

			// plura_wp_enqueue silently skips local files that do not exist, so the
			// year stylesheet needs no file_exists() guard of its own.
			$assets[ $dir . '/includes/css/fibaq-mec-event/' . date('Y') . '.css' ] = ['handle' => 'fibaq-mec-event-year'];

		}

	}

	plura_wp_enqueue( scripts: $assets, prefix: 'pb-theme-' );

	if( $localize_script_data ) {

		wp_localize_script('pb-theme-core', 'pbobj', $localize_script_data);

	}

}

add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );








function pb_single( $id ) {

	$ids = is_array( $id ) ? $id : [$id];

	if( ( is_page() || is_singular() ) && in_array( get_the_ID(), $ids ) ) {

		return true;

	}

	return false;

}



function pb_add_integrity($html, $handle, $src = "", $media = "") {

	if( $handle === 'leaflet' ) {

		if( preg_match('/\.js/', $html) ) {

			return preg_replace('/(src)/', 'integrity="sha512-XQoYMqMTK8LvdxXYG3nZ448hOEQiglfqkJs1NOQV44cWnUrBc8PkAOcXy20w0vlaXaVUearIOBhiXZ5V3ynxwA==" crossorigin="" $1', $html);

		} elseif( preg_match('/\.css/', $html) ) {

			return preg_replace('/(href)/', 'integrity="sha512-xodZBNTC5n17Xt2atTPuE1HxjVMSvLVW9ocqUKLsCC5CXdbqCmblAshOMAS6/keqq/sMZMZ19scR4PsZChSR7A==" crossorigin="" $1', $html);

		}

	}
   
	return $html;

}

add_filter('style_loader_tag', 'pb_add_integrity', 10, 2 );

add_filter('script_loader_tag', 'pb_add_integrity', 10, 4);



//add wpml body class
add_filter('body_class', function( $classes ) {

    global $sitepress;

    $c = [];

    /*if( class_exists('sitepress') && is_singular() ) {

		$wpmlID = apply_filters( 'wpml_object_id', get_the_ID(), get_post_type( get_the_ID() ), true, $sitepress->get_default_language() );

        $c[] = 'wpmlobj-id-' . $wpmlID;

        $c[] = 'wpml-lang-' . strtolower( $sitepress->get_current_language() );

    }*/


	if ( post_password_required() )  {
   		
   		$c[] = 'pb-password-protected';

	}

    return array_merge($classes, $c);

} );



foreach( $MODULES as $module ) {

	$fpath = dirname( __FILE__ ) . "/includes/" . $module . ".php";

	if( file_exists( $fpath ) ) {

		include_once( $fpath );

	}

}


function divi_child_revslider_bg_img( $imgData, $sliderID = false ) {

	if( $sliderID === 5 ) {

		return true;

	}

	return $imgData;

}



//CF7: remove validation for select (a bug returns an undefined alert)
remove_action( 'wpcf7_swv_create_schema', 'wpcf7_swv_add_select_enum_rules', 20, 2 );




// Hook the function to 'pb_shortcode_check' filter
add_filter('pb_shortcode_check', function ($has_shortcode, $post_id, $shortcode) {

	global $post;

	if( ( is_single() || is_page() ) &&  has_shortcode($post->post_content, 'pb-post-content') ) {

		$source_id = get_field('pb-source-id', plura_wpml_id() );

		if( $source_id ) {

			$source = get_post( $source_id );

			if( $source && $shortcode === 'pb-carousel-images' && has_shortcode($source->post_content, 'pb-carousel-images') ) {

				return true;

			} else if( $shortcode === 'pb-headings-nav' && in_array($post->ID,[4442,13144, 4443,13140, 4444,13146]) ) {
				
				return true;

			}

		}

	}

    return $has_shortcode; // Return the original value if not found

}, 10, 3);



/**
 * Add MEC category classes to the <body> on single event pages.
 * Works even if the template doesn't use post_class().
 */
add_filter('body_class', function ($classes) {
    if (is_singular('mec-events')) {
        $terms = get_the_terms(get_the_ID(), 'mec_category');
        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $term) {
                $classes[] = 'mec-category-id-' . (int) $term->term_id;
                $classes[] = 'mec-category-' . sanitize_html_class($term->slug);
            }
        }
    }
    return $classes;
});

