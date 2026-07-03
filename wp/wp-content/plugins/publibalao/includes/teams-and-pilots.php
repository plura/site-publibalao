<?php



add_shortcode('pb-pilots', function( $atts ) {

	$args = shortcode_atts( [
		'ids' => '',
	], $atts );

	$ids = array_filter(array_map('intval', explode(',', $args['ids'])));

	if( !empty( $ids ) ) {

		return plura_wp_posts(type: 'pb_pilots', ids: $ids);

	}

	return "";

} );



add_shortcode('pb-participants-list', function( $atts ) {

	$args = shortcode_atts( [
		'class' => '',
		'id'    => '',
	], $atts );

	// validate `id` as positive int
	$post_id = filter_var( $args['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]] );
	if ( !$post_id ) {
        return '';
    }

	$participants = get_field('pb_participants_list_items', $post_id );

	if ( $participants ) {

		$posts = array_map( function( $participant ) {
			return $participant['pb_participants_list_item'];
		}, array_filter( $participants, function( $participant ) {
			return $participant['pb_participants_list_item'] instanceof WP_Post
				|| is_int( $participant['pb_participants_list_item'] );
		}));

		$classes = 'pb-participants-list';
		if ( !empty( $args['class'] ) ) {
			$classes .= ' ' . esc_attr( $args['class'] );
		}

		if( !empty( $posts ) && function_exists('plura_wp_posts') ) {

			return plura_wp_posts( posts: $posts, link: -1, class: $classes );
		
		}

	}	

	return '';
});






// Filter to modify the post entry for 'rg_artist' post type
add_filter('plura_wp_post', function( array $entry, WP_Post $post, ?string $context = null, ?int $index = null ): array {

	if( get_post_type( $post ) === 'pb_pilot' ) {

		$a = [];

		foreach ( ['featured-image', 'title'] as $key) {

			if( array_key_exists( $key, $entry ) ) {

				$a[] = $entry[ $key ];

			}

		}


		$a = array_merge( $a, [

			plura_wp_post_meta(post: $post, meta: [['key' => 'pb_country','raw_html' => true, 'type' => 'country']]),

			sprintf( '<div %s>%s</div>', plura_attributes(['class' => 'pb-team-nr', 'data-pb-team-nr' => $index + 1]),  sprintf( __('Team %s', 'publibalao'), $index + 1 ))
			
		]);

		return $a;

	}

	return $entry;

}, 10, 4 );

add_filter('plura_wp_post_meta_item_value', function ($value, WP_Post $post, string $item_meta_key, ?string $context = null) {

	if( $item_meta_key === 'pb_country' ) {

		return sprintf(
			"<img alt=\"%s\" src=\"http://purecatamphetamine.github.io/country-flag-icons/3x2/%s.svg\"/><span %s>%s</span>",
			$value['label'],
			esc_html( $value['value'] ),
			plura_attributes(['data-country-code' => esc_html( $value['value'] )]),
			$value['label']
		);

	}

	return $value;
	
}, 10, 4);