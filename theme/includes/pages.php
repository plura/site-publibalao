<?php

/**
 * Canonical page map.
 *
 * IDs were previously written out at every call site, in PHP as translation pairs
 * (2065 and 3614) and in JS as the default-language one only (wpmlobj-id-2065). Two
 * lists that had to be kept in step by hand, where forgetting a translation broke one
 * language silently.
 *
 * Entries hold the default-language ID alone: lookups normalise the current post with
 * plura_wpml_id() first, so a page needs one entry whichever language it is viewed in.
 * Each match is also emitted as a `pb-page-<key>` body class, which is how the JS and
 * the CSS read the same map without carrying any IDs of their own.
 *
 * Names are descriptive only — nothing keys off them but the call sites, so renaming
 * one is safe as long as both sides move together.
 */

const PB_PAGES = [

	'home'                => [2851],
	'contacts-publibalao' => [2065],
	'contacts-fibaq'      => [5639],
	'packages'            => [2869],

	'fibaq'               => [14],
	'fibaq-section'       => [3206],
	'fibaq-registration'  => [3066],

	//pages whose content carries [pb-headings-nav]
	'headings-nav'        => [4442, 4443, 4444],

	'legacy'              => [2109],

];


/**
 * Map keys matching a post.
 *
 * @param int|null $id Post ID. Defaults to the current post.
 * @return string[] Every key the post appears under; empty when it is not mapped.
 */
function pb_page_keys( ?int $id = null ): array {

	$id = $id ?: get_the_ID();

	if ( ! $id ) {

		return [];

	}

	//plura's modules are frontend-only, so this has to survive their absence rather
	//than fatal the way the enqueue migration did
	if ( function_exists('plura_wpml_id') ) {

		$id = plura_wpml_id( $id );

	}

	return array_keys( array_filter( PB_PAGES, fn( array $ids ): bool => in_array( $id, $ids, true ) ) );

}


/**
 * Whether a post is one of the named pages.
 *
 * @param string|string[] $keys One or more map keys.
 * @param int|null $id Post ID. Defaults to the current post.
 * @return bool
 */
function pb_page_is( string|array $keys, ?int $id = null ): bool {

	return (bool) array_intersect( (array) $keys, pb_page_keys( $id ) );

}


/**
 * Whether the current post's parent is one of the named pages.
 *
 * @param string|string[] $keys One or more map keys.
 * @return bool
 */
function pb_page_parent_is( string|array $keys ): bool {

	$parent = wp_get_post_parent_id( get_the_ID() );

	return $parent ? pb_page_is( $keys, $parent ) : false;

}


add_filter( 'body_class', function ( array $classes ): array {

	return array_merge( $classes, array_map( fn( string $key ): string => "pb-page-{$key}", pb_page_keys() ) );

} );
