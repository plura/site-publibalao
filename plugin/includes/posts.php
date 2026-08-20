<?php

/**
 * Site shaping for posts rendered through [plura-wp-posts], one context at a time as
 * the [pb-grid] usages are migrated.
 *
 * pb-services: the services grid. It showed a title, an ACF label and a cropped
 * featured image and nothing else, so the default entry — which always carries a date
 * and an excerpt — has to be trimmed back to match.
 */

if ( ! defined( 'ABSPATH' ) ) exit;


add_filter('plura_wp_post', function (array $entry, WP_Post $post, ?string $context = null): array {

	if ($context !== 'pb-services') {

		return $entry;

	}

	// plura_wp_post only drops the full post body when a filter leaves the entry
	// untouched, so altering it here means unsetting 'content' by hand.
	unset($entry['content'], $entry['datetime'], $entry['excerpt']);

	$label = get_field(get_post_type($post) . '_label', $post->ID);

	if ($label) {

		// Appended after the ordering pass, so it lands below the title. The call to
		// action used to be a hardcoded ::after in CSS, which left it Portuguese on
		// the English site.
		$entry['label'] = sprintf(
			'<div %s>%s<span %s>%s</span></div>',
			plura_attributes(['class' => 'pb-services-label']),
			$label,
			plura_attributes(['class' => 'pb-services-label-more']),
			esc_html__('Saber Mais', 'publibalao')
		);

	}

	return $entry;

}, 10, 3);
