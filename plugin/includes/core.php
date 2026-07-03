<?php

/**
 *  . Layout
 *      - Button Content Popup
 *      - Headings Tree Nav
 *  . Post Data
 *  . Post Content
 *  . Utils
 *      - Is Divi Used
 *      - Has Shortcode
 */



 /* Layout: Button Content Popup */
 function pb_button_content_popup( int|string $id, int|string $popup, string $label = 'content', array|string|null $class = null ):string {

    global $sitepress;

    $target_id = empty( $sitepress ) ? $id : p_wpml_id($id, $sitepress->get_current_language());
    $popup_id = empty( $sitepress ) ? $popup : p_wpml_id($popup, $sitepress->get_current_language());

    $atts = [
        'label' => $label,
        'data-pb-target-id' => $target_id,
        'data-popup-id' => $popup_id,
        'title' => $label
    ];

    $classes = ['pb-button', 'pb-button-content-popup', 'popmake-' . $popup_id];

    if( !empty( $classes ) ) {

        $classes = array_merge( $classes, is_array( $class ) ? $class : explode(',', $class)  );

    }

    $atts['class'] = implode(' ', $classes);

    return "<a " . p_attributes( $atts ) . ">" . $label . "</a>";

 }

 add_shortcode('pb-button-content-popup', function( $args ) {

    $atts = shortcode_atts(['id' => '', 'popup' => '', 'label' => ''], $args);

    if( !empty( $atts['id'] ) && !empty( $atts['popup'] ) ) {

        return pb_button_content_popup(id: $atts['id'], popup: $atts['popup'], label: $atts['label'] );

    }

 } );




 /* Layout: Headings Tree Nav */
 add_shortcode('pb-headings-nav', function( $args ) {

    $atts = shortcode_atts(['class' => 'pb-headings-nav', 'id' => ''], $args);

    $a = ['class' => explode(' ', $atts['class'])];

    if( !empty( $atts['id'] ) ) {

        $a['id'] = $atts['id'];

    }

   return "<div " . p_attributes( $a ) . "></div>";

 });





 /* Post Data */
 function p_post_data( $id ) {

    $post = get_post( $id );

    if( $post ) {

        return [
            'id' => $post->ID,
            'title' => get_the_title( $post->ID ),
            'content' => pb_post_content( $post->ID )
        ];

    }

    return false;

 }




/* Post Content */
/* Post Content */
function pb_post_content( $id, $allow_draft = false ) {

	$post = get_post( $id );

	if ( ! $post ) {
		return '';
	}

	// Only allow non-published posts when explicitly requested
	if ( ! $allow_draft && $post->post_status !== 'publish' ) {
		return '';
	}

	// Ensure global $post context for filters/shortcodes inside content
	$prev_post = $GLOBALS['post'] ?? null;
	$GLOBALS['post'] = $post;
	setup_postdata( $post );

	$content = apply_filters( 'the_content', $post->post_content );

	// Restore previous global post
	if ( $prev_post ) {
		$GLOBALS['post'] = $prev_post;
		setup_postdata( $prev_post );
	} else {
		wp_reset_postdata();
	}

	return $content;
}

add_shortcode( 'pb-post-content', function( $args ) {

	$atts = shortcode_atts(
		[
			'id'    => '',
			'draft' => false, // accepts true/false, 1/0, "yes"/"no"
		],
		$args
	);

	// Normalize to boolean
	$allow_draft = filter_var( $atts['draft'], FILTER_VALIDATE_BOOLEAN );

	$id = $atts['id'];

		//echo 'id: ' . $id . ":" . p_wpml_id();

	if ( empty( $id ) ) {
		$id = get_field( 'pb-source-id', p_wpml_id() );
	}

	//echo 'id: ' . $id . ":" . p_wpml_id();

	if ( ! empty( $id ) ) {
		$id = p_wpml_id( $id, false );
		return pb_post_content( $id, $allow_draft );
	}

	return '';
});





 /* utils: Is Divi Used */
 function pb_is_divi_used_on_post( $post_id ) {
   
    // Check if the Divi Builder was used (via post meta)
    $divi_builder_used = get_post_meta($post_id, '_et_pb_use_builder', true);

    if ($divi_builder_used === 'on') {
        
        return true; // Divi Builder is used on this post/page
    
    }

    // Optionally, also check if the post content contains Divi shortcodes
    $post_content = get_post_field('post_content', $post_id);
    
    if (has_shortcode($post_content, 'et_pb_section')) {
        
        return true; // Divi shortcodes found in the post content
    
    }

    return false; // Divi was not used on this post/page

}



/* Utils: Has Shortcode */
function pb_has_shortcode($post_id, $shortcode) {
    // Get the post content via post ID
    $post = get_post( $post_id );

    if (!$post) {

        return false; // Return false if post does not exist
    
    }
  
    // Check if the post content contains the specified shortcode
    if (has_shortcode($post->post_content, $shortcode)) {

        return true; // Shortcode found in post content
   
    } 
    
    // If a custom filter for additional checking is available
    if ( has_filter('pb_shortcode_check') ) {
       
        // Apply custom filter for additional checking (if any)
        return apply_filters('pb_shortcode_check', false, $post_id, $shortcode);
    
    }

    return false; // Return false if neither the shortcode nor custom checks found it

}