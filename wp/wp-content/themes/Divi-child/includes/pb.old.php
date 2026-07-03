<?php


function pb_post_content($atts) {

	$atts = shortcode_atts(['id' => ''], $atts );

	if( !empty( $atts['id'] ) ) {

		return apply_filters('the_content', get_post( $atts['id'] )->post_content );

	}

}

add_shortcode('pb-post-content', 'pb_post_content');


function pb_social_icons() {

	ob_start();
	
?>

	<ul class="pb-social-icons">
		<li class="pb-social-icon"><a href="mailto:reservas@publibalao.com"><i class="far fa-envelope"></i></a></li>
		<li class="pb-social-icon"><a href="https://www.facebook.com/publibalao" target="_blank"><i class="fab fa-facebook-f"></i></a></li>
		<li class="pb-social-icon"><a href="https://www.instagram.com/publi.balao/" target="_blank"><i class="fab fa-instagram"></i></a></li>
	</ul>
	
	<?php

	$content = ob_get_clean();

	return $content;

}

add_shortcode('pb-social-icons', 'pb_social_icons');




function pb_date( $atts ) {

	$atts = shortcode_atts(['id' => ''], $atts );

	$post = get_post( !empty( $atts['id'] ) ? $atts['id'] : get_the_ID() );

	if( $post ) {

		$date = get_field('pb_date', $post->ID);

		$d = new DateTime( $date );

		$values = [
			'%Y' => $d->format('Y'),
			'%d' => $d->format('d'),
			'%j' => $d->format('j'),
			'%F' => date_i18n('F', $d->getTimeStamp())
		];

		if( !isset( $atts['format'] ) ) {

			$format = "<div class=\"pb-date\"><div class=\"pb-date-item pb-date-item-day\">%d</div><div class=\"pb-date-item pb-date-item-month\">%F</div></div>";

		} else {

			$format = $atts['format'];

		}

		return str_replace( array_keys($values), array_values($values), $format);

	}

}

add_shortcode('pb-date', 'pb_date');




//allow cpt to be used as front page (options reading tab page dropdown)
//https://wordpress.stackexchange.com/a/42468

function pb_add_pages_to_dropdown( $pages, $r ){
    
    if ( ! isset( $r[ 'name' ] ) )
    
        return $pages;

    if ( 'page_on_front' == $r[ 'name' ] ) {
    
        $args = ['post_type' => 'pb_event'];
    
        $portfolios = get_posts( $args );
    
        $pages = array_merge( $pages, $portfolios );
    
    }

    return $pages;

}

add_filter( 'get_pages', 'pb_add_pages_to_dropdown', 10, 2 );