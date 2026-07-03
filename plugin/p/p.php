<?php

function p_attributes( $atts, $prefix = false ) {

	$a = [];

	foreach($atts as $k => $v) {

		if( $k === 'class' && is_array( $v ) ) {

			$v = implode(' ', $v);

		}

		$value = $k . "=\"" . $v . "\"";

		if( $prefix ) {

			$value = "data-" . $value;

		}

		$a[] = $value;

	}

	return implode(' ', $a);

}


function p_thumbnail( $postID, $size = 'large' ) {

	$img = has_post_thumbnail( $postID );

	if( $img ) {

		return wp_get_attachment_image_src( get_post_thumbnail_id( $postID ), $size);

	}

	return false;

}


/**
 * get all the breadcrumbs for an object (post, page or term)
 * @param  boolean $object object (post, page or term)
 * @param  boolean $self   includes self as 'crumb'
 * @param  boolean $id     optional id attribute
 * @param  boolean $html   return type
 * @return object          returns an array
 */
function p_breadcrumbs( $object = false, $self = false, $id = false, $html = true ) {

	$crumbs = [];

	if( is_archive() && !$object ) {

		$crumb = p_breadcrumbs_terms( get_queried_object()->term_id, get_queried_object()->taxonomy, $self );

		if( $crumb ) {
  
			$crumbs[] = $crumb;

		}

	} else {

		if( $object && is_int( $object ) ) {

			$object = get_post( $object );
		
		} else if(!$object) {

			$object = get_post();

		}

		if( ( is_single() && !$object ) || $object->post_type !== 'page' ) {

			$post_taxonomies = get_object_taxonomies( $object );

			if( !empty( $post_taxonomies ) ) {

				$terms = get_the_terms( $object, $post_taxonomies[0] );

				if( !empty( $terms ) ) {

					foreach( $terms as $term ) {

						$crumbs[] = p_breadcrumbs_terms( $term->term_id, $term->taxonomy, true );

					}

				}

			}

		} else if( ( is_page() && !$object ) || $object->post_type === 'page' ) {

			$ancestors = get_ancestors( $object->ID, get_post_type(), 'post_type' );

			if( !empty( $ancestors ) ) {

				$group = [];

				foreach( $ancestors as $ancestor ) {

					$group[] = p_breadcrumb( $ancestor );

				}

				$crumbs[] = $group;

			}

		}

	}


	if( has_filter('p_breadcrumbs') ) {

		$crumbs = apply_filters('p_breadcrumbs', $crumbs, $id );

	}


	if( !empty( $crumbs ) ) {

		if( $html ) {

			$return = [];

			if( !is_array( $crumbs[0] ) || !array_key_exists(0, $crumbs[0]) ) {

				$crumbs = [ $crumbs ];

			}

			foreach( $crumbs as $group ) {

				$g = [];

				foreach( $group as $crumb ) {

					$classes = ['p-breadcrumb'];

					if( !is_array( $crumb ) ) {

						$c = $crumb;

					} else { 

						$classes[] = 'has-link';

						$c = "<a href=\"" . $crumb['link'] . "\" title=\"" . $crumb['name'] . "\">" . $crumb['name'] . "</a>";

					}

					$atts = ['class' => implode(' ', $classes)];

					$g[] = "<li " . p_attributes( $atts ) . ">" . $c . "</li>";

				}

				$return[] = "<ul class=\"p-breadcrumbs-group\">" . implode('', $g) . "</ul>";

			}

			$atts = ['class' => 'p-breadcrumbs'];

			if( $id ) {

				$atts['data-id'] = $id;

			}

			return "<div " . p_attributes( $atts ) . ">" . implode('', $return) . "</div>";

		}

		return $crumbs;

	}

}


function p_breadcrumbs_terms( $termID, $taxonomy, $include = false ) {

	$crumbs = [];

	$ancestors = get_ancestors( $termID, $taxonomy );

	if( !empty( $ancestors ) ) {

		foreach( array_reverse( $ancestors ) as $ancestor ) {

			$crumbs[] = p_breadcrumb( $ancestor, $taxonomy );

		}

	}

	if( $include ) {

		$crumbs[] = p_breadcrumb( $termID, $taxonomy );

	}

	return $crumbs;

}


function p_breadcrumb( $id, $taxonomy = false ) {

	if( $taxonomy ) {

		return ['type' => 'term', 'link' => get_term_link( $id, $taxonomy ), 'name' => get_term( $id, $taxonomy )->name, 'id' => $id ];

	} else if( !is_int( $id ) ) {

		return ['type' => 'single', 'name' => $id, 'id' => $id];

	}

	return ['type' => 'single', 'link' => get_permalink( $id ), 'name' => get_the_title( $id ) ];

}

function p_breadcrumbs_shortcode( $args ) {

	$atts = shortcode_atts([
		'id' => 0,
		'object' => 0,
		'self' => 0
	], $args);

	return p_breadcrumbs( $atts['object'], $atts['self'], $atts['id'] );

}

add_shortcode('p-breadcrumbs', 'p_breadcrumbs_shortcode');



function p_tags( $post, $html = true ) {

	if( is_int( $post ) ) {

		$post = get_post( $post );

	}

	$post_taxonomies = get_object_taxonomies( $post );

	if( !empty( $post_taxonomies ) ) {

		$tags = [];

		foreach( $post_taxonomies as $taxonomy ) {

			$terms = get_the_terms( $post, $taxonomy );

			foreach( $terms as $term ) {

				if( $html ) {

					$atts = ['class' => 'p-tag'];

					$atts_link = ['title' => $term->name, 'href' => get_term_link( $term )];

					$tags[] = "<li " . p_attributes( $atts ) . "><a " . p_attributes( $atts_link ) . ">" . $term->name . "</a></li>";

				} else {

					$tags[] = $term;

				}

			}

			if( $html ) {

				$atts = ['class' => 'p-tags', 'data-taxonomy' => $post_taxonomies[0]];

				return "<ul " . p_attributes( $atts ) . ">" . implode('', $tags) . "</ul>";

			}

		}

		return $tags;

	}

}

function p_tags_shortcode( $args ) {

	$atts = shortcode_atts(['post' => ''], $args);

	$id = empty( $atts['post'] ) ? get_the_ID() : $atts['id'];

	return p_tags( $atts['post'] );

}

add_shortcode('p-tags', 'p_tags_shortcode');


$P_TITLE_ARGS = ['html' => 1];

function p_title( array $args = [] ) {

	global $P_TITLE_ARGS;

	$args = array_merge( $P_TITLE_ARGS, $args );

	if( is_page() || is_single() ) {

		$text = get_the_title();

	} else if( is_archive() ) {

		//https://www.binarymoon.co.uk/2017/02/hide-archive-title-prefix-wordpress/
		$title_parts = explode( ': ', get_the_archive_title(), 2 );

		$text = $title_parts[1];

	}

	if( empty($text) ) {

		$text = 'no title';

	}

	if( has_filter('p_title') ) {

		$text = apply_filters('p_title', $text);

	}

	if( $args['html'] ) {

		return "<div class=\"p-title\">" . $text . "</div>";

	}

	return $text;

}

add_shortcode('p-title', function( $atts ) {

	global $P_TITLE_ARGS;

	$args = shortcode_atts($P_TITLE_ARGS, $atts);

	return p_title( $args );

});



$P_POST_ARGS = [
	'date' 					=> 1,
	'date_format'			=> "l, j F Y",
	'excerpt'				=> 0,
	'featured_image' 		=> 1,
	'featured_image_size'	=> 'medium',
	'layout'				=> 'div',
	'nav'					=> 1,
	'source'				=> '',
	'tags'					=> 0
];



/**
 * [p_posts description]
 * @param  array  $args [description]
 *				$args['date']					bool	indicates if date is to be added
 *				$args['date_format']			string	date format
 *				$args['excerpt']				bool	indicates if excerpt should be visibile
 *				$args['featured_image']			bool	indicates if function should return featured image
 *				$args['featured_image_size']	string	the size of the featured image
*				$args['layout']					string	'div' or 'list'
 *				$args['limit']					number	indicates number of posts
 *				$args['nav']					nav		include navigation (WP-PAGENAVI plugin is required)
 *				$args['type']					string	post type
 *				$args['tax']					string	taxonomy
 *				$args['tax_id']					number	term id
 * @return [type] [description]
 */
function p_posts( array $args ) {

	global $P_POSTS_QUERY_ARGS, $P_POST_ARGS;

	$args = array_merge($P_POST_ARGS, $P_POSTS_QUERY_ARGS, $args);

	if( !empty( $args['source'] ) ) {

		return p_posts_remote( $args );
	
	}

	$params = p_posts_query( $args );

	$query = new WP_Query( $params );

	$html = "";

	if( $query->have_posts() ) {

		$html_classes = ["p-posts"];

		if( $args['featured_image'] ) {

			$html_classes[] = 'has-thumbnails';

		}

		if( !$args['layout'] !== 'list' ) {

			$tag = $subtag = 'div';

		} else {

			$tag = 'ul';

			$subtag = 'li';

			$html_classes[] = 'list';

		}

		$html = [];

		foreach( $query->posts as $post ) {

			$img = p_thumbnail( $post->ID, $args['featured_image_size'] );

			$classes = ["p-post"];

			if( $img ) {

				$classes[] = 'has-thumbnail';

			}

			$atts = ['class' => implode(' ', $classes)];

			$html[] = "<$subtag " . p_attributes( $atts ) . "\">\n";


				if( $img ) {

					$html[] = p_post_link($post, "<img src=\"" . $img[0] . "\" width=\"" . $img[1] . "\" height=\"" . $img[2] . "\" class=\"p-post-featured-image\" />", ['p-post-link', 'p-post-featured-image-link']);

				} else {

					$html[] = "<div class=\"p-post-featured-image-placeholder\"></div>";

				}

				$html[] = "<div class=\"p-post-title\">" . p_post_link($post, $post->post_title, 'p-post-link') . "</div>";

				//$html[] = "<div class=\"p-post-meta\">";
				
				$html_meta = [];

					if( !empty( $args['date'] ) ) {

						$html_meta[] = "<div class=\"p-post-date\">" . date_i18n( $args['date_format'], strtotime( get_the_date('Y/n/j H:i:s', $post ) ) ) . "</div>";

					}

					if( !empty( $args['tags'] ) ) {

						$html_meta[] = p_tags( $post->ID );

					}

				if( !empty( $html_meta ) ) {

					$html[] = "<div class=\"p-post-meta\">" . implode('', $html_meta) . "</div>";

				}


				//$html[] = "</div>\n";

			if( $args['excerpt'] ) {

				$html[] = "<div class=\"p-post-excerpt\">" . $post->post_excerpt . "</div>";

			}

			$html[] = "</$subtag>\n";

		} 
		
		$html = array_merge(["<$tag class=\"p-posts-holder\">"], $html, ["</$tag>"]);

		if( !empty( $args['nav'] ) && $query->found_posts > $query->post_count ) {

			$html[] = "<div class=\"p-posts-nav\">" . p_posts_nav( $query ) . "</div>";

		}

		$atts = ['class' => implode(' ', $html_classes) ];

		return "<div " . p_attributes( $atts ) . ">" . implode('', $html) . "</div>";

	}

	return $html;

}


function p_posts_remote( $args ) {

	$url = $args['source'];

	unset( $args['source'] );

	$response = wp_remote_get( $url . '?' . http_build_query( $args ) );

	if( is_wp_error( $response ) ) {

		return __('Loading Failed...');

	}

	return json_decode( $response['body'] );
	
}


function p_posts_nav( $query ) {

	global $wp_query;

	$tmp_query = $wp_query;

	$wp_query = $query;

	if( function_exists('wp_pagenavi') ) {

		$result = wp_pagenavi( array(
			'query' => $query,
			'echo' => false
		));

	} else {

		$result = paginate_links( array(
		           	
			'format'  	=> 'page/%#%',
			'current' 	=> get_query_var( 'paged' ),
			'total'   	=> $query->max_num_pages,
			'mid_size'	=> 2,
			'prev_text'	=> __('&laquo;'),
			'next_text'	=> __('&raquo;')		
			//'prev_text'	=> __('&laquo; Prev Page'),
			//'next_text'	=> __('Next Page &raquo;')
		       		
		) );

	}

	wp_reset_postdata();

	// Restore original query object
	$wp_query = null;
	$wp_query = $tmp_query;

	return $result;

}


function p_post_link($post, $html, $classes = false) {

	$atts = ['href' => get_permalink( $post->ID ), 'title' => $post->post_title];

	if( $classes ) {

		$atts['class'] = is_array( $classes ) ? implode(' ', $classes) : $classes;

	}

	return "<a " . p_attributes( $atts ) . ">\n" . $html . "</a>";

} 


$P_POSTS_QUERY_ARGS = [
	'auto' => 0,
	'exclude' => 0,
	'ids' => '',
	'limit' => 12,
	'rand' => 0,
	'tax' => '',
	'taxonomy' => '',
	'term' => '',
	'type' => ''
	//'taxonomy' => 'category',
	//'term' => '',
	//'type' => 'post'
];

function p_posts_query( $args ) {

	global $P_POSTS_QUERY_ARGS;

	$args = array_merge($P_POSTS_QUERY_ARGS, $args);

	if( !empty( $args['auto'] ) ) {

	    if( is_archive() && empty( $args['term'] ) ) {

	        $args['taxonomy'] = get_queried_object()->taxonomy;
	        $args['term'] = get_queried_object()->term_id;

	    } /*else if( is_single() && $args['exclude'] === 'true' ) {

	        $args['exclude'] = get_the_ID();

	    }*/

	}

	$params = array(
		'posts_per_page' => $args['limit'],
		'post_type' => $args['type']
	);


	/*if( !empty( $args['tax'] ) ) {

		if( is_numeric( $args['tax'] ) ) {

			$params['cat'] = $args['tax'];

		} else {

			$params['tax_query'] = [
				[
					'taxonomy' => $args['tax'],
					'field' => 'term_id',
					'terms' => $args['tax_id']
				]
			];

		}

	}*/

	if( $args['rand'] ) {

		$params = array_merge($params, [
		    'orderby' => 'rand'
		]);

	}

	if( $args['exclude'] && ( $args['exclude'] !== 'true' || is_single() ) ) {

		if( $args['exclude'] === 'true' ) {

			$args['exclude'] = get_the_ID();

		}

	    $params['post__not_in'] = is_array( $args['exclude'] ) ? $args['exclude'] : explode(',', $args['exclude']);

	}

    if( !empty( $args['ids'] ) ) {

        $params = array_merge( $params, [
            'orderby' => 'post__in',
            'post__in' => is_array( $args['ids'] ) ? $args['ids'] : explode(',', $args['ids'])
        ]);

    }

    if( !empty( $args['term'] ) || !empty( $args['tax'] ) ) {

        if( !empty( $args['tax'] ) ) {

            $params['tax_query'] = $args['tax'];

        } else {

            $params['tax_query'] = [

                [
                    'taxonomy' => $args['taxonomy'],
                    'field'    => 'term_id',
                    'terms'    => $args['term']
                ]

            ];

        }

    }

	if( get_query_var( 'paged' ) ) {

		$params['paged'] = get_query_var( 'paged' );

	}

	return $params;

}


add_shortcode('p-posts', function( $atts ) {

	global $P_POSTS_QUERY_ARGS, $P_POST_ARGS;

	$args = shortcode_atts( array_merge($P_POST_ARGS, $P_POSTS_QUERY_ARGS), $atts );

	/*$args = shortcode_atts( array(
		'date' 					=> '1',
		'date_format'			=> "l, j F Y",
		'excerpt'				=> '',
		'featured_image' 		=> '1',
		'featured_image_size'	=> 'medium',
		'layout'				=> 'div',
		'limit'					=> '',
		'nav'					=> '1',
	    'type' 					=> 'post',
	    'tax'					=> '',
	    'tax_id'				=> ''
	 ), $atts ); */

	return p_posts( $args );

});




function p_date_archive() {

	if( is_archive() ) {

		if( is_post_type_archive() ) {

			$post_type = get_queried_object()->name;

			$atts = [
				'data-archive-request-obj' => 'is-archive',
				'data-archive-post-type' => $post_type
			];

		} else if( isset( get_queried_object()->term_id ) ) {

			$post_type = get_taxonomy( get_queried_object()->taxonomy )->object_type[0];

			$term_id = get_queried_object()->term_id;
			
			$atts = [
				'data-archive-request-obj' => 'term',
				'data-archive-post-type' => $post_type
			];

		}		

	} else if( is_singular() ) {

		$post_type = get_post_type();

		$atts = [
			'data-archive-request-obj' => 'single',
			'data-archive-post-type' => $post_type
		];

	}

	if( !empty( $post_type ) ) {


		$atts['class'] = 'p-date-archive';

		return "<ul " . p_attributes( $atts ) . ">" . wp_get_archives( array('echo' => 0, 'type' => 'yearly', 'post_type' => $post_type ) ) . "</ul>";

	}

}

add_shortcode('p-date-archive', 'p_date_archive');
