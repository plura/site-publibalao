<?php

add_action( 'after_setup_theme', function() {

  load_child_theme_textdomain( 'divi', get_stylesheet_directory() . '/languages' ); 

} );



$MODULES = ['lang', /*'locations', */ 'pages', 'pb'];



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

	if( pb_page_is('fibaq-section') || ( is_page() && !pb_page_is('contacts-fibaq') && pb_page_parent_is('fibaq-section') ) ) {

		$assets[ $dir . '/includes/css/fibaq.css' ] = [];

		$localize_script_data['fibaq'] = pb_lang();

		if( pb_page_is('fibaq-registration') ) {

			$assets[ $dir . '/includes/css/fibaq-register.css' ] = [];

		}

	} else if( ( is_singular() && pb_page_is( ['contacts-publibalao', 'home', 'contacts-fibaq'] ) ) || is_singular('pb_event') ) {

		// Unprefixed: pb_add_integrity() below matches the 'leaflet' handle by name.
		plura_wp_enqueue([
			'https://unpkg.com/leaflet@1.7.1/dist/leaflet.css' => [ 'handle' => 'leaflet' ],
			'https://unpkg.com/leaflet@1.7.1/dist/leaflet.js'  => [ 'handle' => 'leaflet' ],
		]);

	} else if( is_singular() && pb_page_is('legacy') ) {

		wp_enqueue_style('pb-theme-old', get_stylesheet_directory_uri() . '/includes/old/old.css' );

		//wp_enqueue_script('pb-theme-old', get_stylesheet_directory_uri() . '/includes/js/scripts.js', $deps, time() );

	} else if( is_singular('mec-events') || is_tax('mec_category') ) {

		$assets[ $dir . '/includes/css/fibaq-mec-event-pre.css' ] = [];

		if( is_singular('mec-events') ) {

			$assets[ $dir . '/includes/css/fibaq-mec-event.css' ] = [];

			// Booking form layout shared by every edition.
			$assets[ $dir . '/includes/css/fibaq-mec-event-booking.css' ] = [];

			// Per-event field numbers for that edition's Cativo forms. The year is the
			// event's own (MEC stores Y-m-d), so a past edition keeps its file after
			// January. plura_wp_enqueue skips missing files, so no file_exists() here.
			$start = (string) get_post_meta( get_the_ID(), 'mec_start_date', true );
			$year  = preg_match( '/^\d{4}/', $start, $m ) ? $m[0] : wp_date('Y');

			$assets[ $dir . '/includes/css/fibaq-mec-event-booking/' . $year . '.css' ] = ['handle' => 'fibaq-mec-event-booking-year'];

		}

	}

	plura_wp_enqueue( scripts: $assets, prefix: 'pb-theme-' );

	if( $localize_script_data ) {

		wp_localize_script('pb-theme-core', 'pbobj', $localize_script_data);

	}

}

add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );











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

			} else if( $shortcode === 'pb-headings-nav' && pb_page_is('headings-nav', $post->ID) ) {
				
				return true;

			}

		}

	}

    return $has_shortcode; // Return the original value if not found

}, 10, 3);



/**
 * MEC categories the booking form styles tell apart, as PT and EN term IDs. Each match
 * is emitted as a `pb-mec-event-category-<key>` body class, so the CSS carries no IDs
 * and no per-language pair.
 */
const PB_MEC_CATEGORIES = [

	'solidario' => [78, 79],
	'cativo'    => [74, 80],

];


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

                foreach (PB_MEC_CATEGORIES as $key => $ids) {
                    if (in_array((int) $term->term_id, $ids, true)) {
                        $classes[] = 'pb-mec-event-category-' . $key;
                    }
                }
            }
        }
    }
    return $classes;
});

