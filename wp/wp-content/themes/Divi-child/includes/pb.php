<?php

function pb_social_icons( $atts ) {

	$atts = shortcode_atts([
		'mail'	=> 'reservas@publibalao.com',
		'fb' 	=> 'publibalao',
		'ig'	=> 'publi.balao'
	], $atts );

	if( !empty( $atts['mail'] ) || !empty( $atts['fb'] ) || !empty( $atts['ig'] ) ):

		$html = "<ul class=\"pb-social-icons\">";

		foreach( ['mail', 'fb', 'ig'] as $key ):

			if( !empty( $atts[ $key ] ) ):

				$li_atts = ['class' => 'pb-social-icon'];

				$a_atts = ['href' => ( $key === 'mail' ? 'mailto:' : ( $key === 'fb' ? 'https://facebook.com/' : 'https://instagram.com/' ) ) . $atts[ $key ]];

				$i_atts = ['class' => $key === 'mail' ? 'far fa-envelope' : ( $key === 'fb' ? 'fab fa-facebook-f' : 'fab fa-instagram' ) ];

				if( $key !== 'mail' ) {

					$a_atts['target'] = '_blank';

				}

				$html .= "<li " . p_attributes($li_atts) . "><a " . p_attributes($a_atts) . "><i " . p_attributes($i_atts) . "></i></a></li>";

			endif;
	
		endforeach;

		return $html . "</ul>";

	endif;

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