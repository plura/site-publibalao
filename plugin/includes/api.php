<?php

/**
 * Plugin Name: Publibalao REST: Page/ACF Resolver
 * Version: 0.2.0
 */

if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
	register_rest_route('publibalao/v1', '/page', [
		'methods'  => 'GET',
		'callback' => 'pb_resolve_page',
		'permission_callback' => '__return_true',
		'args' => [
			'id' => [
				'required' => false,
				'type'     => 'integer',
				'description' => 'WP page/post ID.',
			],
			'url' => [
				'required' => false,
				'type'     => 'string',
				'description' => 'Absolute or relative URL to a page.',
			],
			'page_path' => [
				'required' => false,
				'type'     => 'string',
				'description' => 'Path for get_page_by_path, e.g. "section/terms".',
			],
			'acf_field' => [
				'required' => false,
				'type'     => 'string',
				'description' => 'ACF field on the resolved page that stores a Post (ID/Object).',
			],
			'fallback_to_page' => [
				'required' => false,
				'type'     => 'boolean',
				'description' => 'If ACF value is empty or field absent, return the page’s own HTML content.',
			],
			'lang_current' => [
				'required' => false,
				'type'     => 'string', // e.g., 'en' or 'pt-PT'
				'description' => 'Language to resolve/return the page in.',
			],
			'lang_default' => [
				'required' => false,
				'type'     => 'string',
				'description' => 'Language to read the ACF field from (often your site default).',
			],
		],
	]);
});

function pb_resolve_page(WP_REST_Request $req): WP_REST_Response
{
	$id               = (int) ($req->get_param('id') ?? 0);
	$url              = (string) ($req->get_param('url') ?? '');
	$page_path        = trim((string) ($req->get_param('page_path') ?? ''), "/");
	$acf_field        = (string) ($req->get_param('acf_field') ?? '');
	$fallback_to_page = filter_var($req->get_param('fallback_to_page') ?? false, FILTER_VALIDATE_BOOLEAN);

	$page_id = 0;

	$lang_default = $req->get_param('lang_default');
	$lang_current = $req->get_param('lang_current');

	// Priority: id > url > page_path
	if ($id > 0) {
		$page_id = $id;
	} elseif ($url !== '') {
		$page_id = url_to_postid(esc_url_raw($url));
		if (!$page_id && !$page_path) {
			$path = wp_parse_url($url, PHP_URL_PATH);
			if (is_string($path)) $page_path = trim($path, '/');
		}
	}

	if (!$page_id && $page_path !== '') {
		$page_obj = get_page_by_path($page_path, OBJECT, 'page');
		if ($page_obj instanceof WP_Post) {
			$page_id = (int) $page_obj->ID;
		}
	}

	if (!$page_id) {
		return new WP_REST_Response([
			'ok'    => false,
			'error' => 'Could not resolve a page from id|url|page_path.',
			'input' => compact('id', 'url', 'page_path'),
		], 400);
	}

	$page = get_post($page_id);
	if (!$page /* || 'publish' !== $page->post_status */) {
		return new WP_REST_Response([
			'ok'      => false,
			'error'   => 'Page not found or not published.',
			'page_id' => $page_id,
		], 404);
	}

	$response = [
		'ok'          => true,
		'page_id'     => $page_id,
		'page_type'   => $page->post_type,
		'page_title'  => get_the_title($page),
		'page_link'   => get_permalink($page),
		'content_src' => null, // 'linked_post' or 'page'
		'content_html' => null, // always full HTML (the_content)
	];

	// If no acf_field requested, just return the page HTML
	if ($acf_field === '') {
		$response['content_src']  = 'page';
		$response['content_html'] = apply_filters('the_content', $page->post_content ?? '');
		return new WP_REST_Response($response, 200);
	}

	// ACF requested: try to read it
	if (!function_exists('get_field')) {
		// No ACF available; optionally fall back
		if ($fallback_to_page) {
			$response['content_src']  = 'page';
			$response['content_html'] = apply_filters('the_content', $page->post_content ?? '');
			return new WP_REST_Response($response, 200);
		}
		return new WP_REST_Response([
			'ok'    => false,
			'error' => 'ACF not available (get_field missing).',
		], 500);
	}


	$acf_target_id = $page_id;

	// your wrapper mapping to default language ID
	if ($lang_default && $lang_default !== $lang_current) {

		$acf_target_id_default = p_wpml_id($page_id, $lang_default);

		if ($acf_target_id_default) {
			$acf_target_id = $acf_target_id_default;
		}
	}

	$acf_value = get_field($acf_field, $acf_target_id);


	// If ACF empty/missing, optionally fall back to page HTML
	if (empty($acf_value)) {
		if ($fallback_to_page) {
			$response['content_src']  = 'page';
			$response['content_html'] = apply_filters('the_content', $page->post_content ?? '');
			return new WP_REST_Response($response, 200);
		}
		return new WP_REST_Response([
			'ok'    => false,
			'error' => 'ACF field empty or not found on page.',
			'meta'  => ['acf_field' => $acf_field],
		], 404);
	}

	// Extract linked post ID from common ACF return types
	$linked_id = 0;
	if (is_numeric($acf_value)) {
		$linked_id = (int) $acf_value;
	} elseif (is_array($acf_value) && isset($acf_value['ID'])) {
		$linked_id = (int) $acf_value['ID'];
	} elseif ($acf_value instanceof WP_Post) {
		$linked_id = (int) $acf_value->ID;
	}

	// If linked_id was translated to the current language, use that
	if ($lang_current && $lang_current === 'en' && $lang_current !== $lang_default) {

		$linked_id_current = p_wpml_id($linked_id, $lang_current);

		if ($linked_id_current) {
			$linked_id = $linked_id_current;
		}
	}

	if ($linked_id > 0) {
		$linked = get_post($linked_id);
		if ($linked && 'publish' === $linked->post_status) {
			$response['content_src']  = 'linked_post';
			$response['content_html'] = apply_filters('the_content', $linked->post_content ?? '');
			$response['linked_post']  = [
				'id'        => $linked_id,
				'type'      => $linked->post_type,
				'title'     => get_the_title($linked),
				'permalink' => get_permalink($linked),
			];
			return new WP_REST_Response($response, 200);
		}
		// Linked post invalid → fallback?
		if ($fallback_to_page) {
			$response['content_src']  = 'page';
			$response['content_html'] = apply_filters('the_content', $page->post_content ?? '');
			$response['linked_post']  = ['id' => $linked_id, 'error' => 'Linked post not found/published.'];
			return new WP_REST_Response($response, 200);
		}
		return new WP_REST_Response([
			'ok'    => false,
			'error' => 'Linked post not found or not published.',
			'meta'  => ['linked_id' => $linked_id],
		], 404);
	}

	// ACF value present but not a post → fallback or error
	if ($fallback_to_page) {
		$response['content_src']  = 'page';
		$response['content_html'] = apply_filters('the_content', $page->post_content ?? '');
		$response['acf_raw']      = $acf_value;
		return new WP_REST_Response($response, 200);
	}

	return new WP_REST_Response([
		'ok'    => false,
		'error' => 'ACF field did not resolve to a valid post.',
		'meta'  => ['acf_field' => $acf_field],
	], 422);
}
