<?php

/*
 * Plugin Name: Publibalão
 * Description: Site specific code changes for site publibalao.pt
 * Domain Path: /languages
 * Text Domain: publibalao
 */
//http://www.sitepoint.com/including-javascript-in-plugins-or-themes/

// URL for the import map, DIR for plura_wp_enqueue: it versions local files with
// filemtime but treats anything matching ^https?:// as external and skips versioning.
define( 'PB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

$MODULES = [
	'p/p',
	'p/modules/p-revslider',
	'p/modules/p-wp',
	'p/modules/p-wpml',

	'includes/api',
	'includes/core',
	'includes/events',
	'includes/locations',
	'includes/media',
	'includes/data',
	'includes/import-map',
	'includes/teams-and-pilots',
];

foreach ($MODULES as $module) {

	$path = dirname(__FILE__) . "/" . $module . ".php";

	if (file_exists($path)) {

		include_once($path);
	}
}


function publibalao_styles_and_scripts() {
	global $post, $sitepress;

	// Only what plura_wp_data does not already expose. It carries home/pluginURL/
	// restURL/restNonce/lang, but its `lang` is the current code alone and the popup
	// routing below needs it paired with the default.
	$data = [];

	if ( isset($sitepress) && method_exists($sitepress, 'get_current_language') ) {
		$data['lang'] = [
			'current' => $sitepress->get_current_language(),
			'default' => $sitepress->get_default_language(),
		];
	}

	// Local assets, collected here and enqueued in one call at the end. CDN assets
	// stay on wp_enqueue_* below: plura_wp_enqueue has no in_footer option, and
	// moving those render-blocking UMD bundles into the head would be a regression.
	$assets = [
		PB_PLUGIN_DIR . 'includes/css/globals.css'       => [],
		PB_PLUGIN_DIR . 'includes/css/globals-theme.css' => [],
	];

	// Fancyapps CSS
	wp_enqueue_style(
		'fancybox',
		'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css',
		[],
		'5.0'
	);

	// --- Classic (UMD) scripts as globals ---
	wp_enqueue_script(
		'fancybox',
		'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js',
		[],
		'5.0',
		[ 'in_footer' => true ]
	);

	// Optionally load carousel stuff only when needed
	if ( (is_single() || is_page()) && $post instanceof WP_Post && pb_has_shortcode($post->ID, 'pb-carousel-images') ) {

		wp_enqueue_style(
			'carousel',
			'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/carousel/carousel.css',
			[ 'fancybox' ],
			'5.0'
		);

		wp_enqueue_script(
			'carousel',
			'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/carousel/carousel.umd.js',
			[ 'fancybox' ],
			'5.0',
			[ 'in_footer' => true ]
		);

		wp_enqueue_style(
			'carousel-thumbs',
			'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/carousel/carousel.thumbs.css',
			[ 'carousel' ],
			'5.0'
		);

		wp_enqueue_script(
			'carousel-thumbs',
			'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/carousel/carousel.thumbs.umd.js',
			[ 'carousel' ],
			'5.0',
			[ 'in_footer' => true ]
		);

		wp_enqueue_style(
			'carousel-autoplay',
			'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0.36/dist/carousel/carousel.autoplay.css',
			[ 'carousel' ],
			'5.0.36'
		);

		wp_enqueue_script(
			'carousel-autoplay',
			'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0.36/dist/carousel/carousel.autoplay.umd.js',
			[ 'carousel' ],
			'5.0.36',
			[ 'in_footer' => true ]
		);
	}

	if ( (is_single() || is_page()) && $post instanceof WP_Post && pb_has_shortcode($post->ID, 'pb-headings-nav') ) {
		$assets[ PB_PLUGIN_DIR . 'includes/js/plura-layout-headings-nav.js' ] = [ 'handle' => 'layout-headings-nav' ];
	}

	// Entry module. `module => true` replaces the hand-rolled script_loader_tag filter
	// this used to need; no classic deps, since the CDN globals it touches are plain
	// footer scripts and a module always executes after those.
	$assets[ PB_PLUGIN_DIR . 'includes/js/scripts.js' ] = [ 'handle' => 'core', 'module' => true ];

	plura_wp_enqueue( scripts: $assets, prefix: 'pb-' );

	// Printed inline immediately before the module tag, so it is set before it runs.
	if ( $data ) {
		wp_localize_script('pb-core', 'pb_data', $data);
	}
}
add_action('wp_enqueue_scripts', 'publibalao_styles_and_scripts');



/**
 * Publibalão admin menu + CPT grouping + submenu icons (participants+pilots)
 * Minimal version
 */

// Icons
$pb_icon_main_white = 'data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%23fff%22%20stroke-width%3D%221%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20class%3D%22icon%20icon-tabler%20icons-tabler-outline%20icon-tabler-air-balloon%22%3E%3Cpath%20stroke%3D%22none%22%20d%3D%22M0%200h24v24H0z%22%20fill%3D%22none%22%2F%3E%3Cpath%20d%3D%22M10%2019m0%201a1%201%200%200%201%201%20-1h2a1%201%200%200%201%201%201v1a1%201%200%200%201%20-1%201h-2a1%201%200%200%201%20-1%20-1z%22%2F%3E%3Cpath%20d%3D%22M12%2016c3.314%200%206%20-4.686%206%20-8a6%206%200%201%200%20-12%200c0%203.314%202.686%208%206%208z%22%2F%3E%3Cpath%20d%3D%22M12%209m-2%200a2%207%200%201%200%204%200a2%207%200%201%200%20-4%200%22%2F%3E%3C%2Fsvg%3E';

$pb_icon_participants_mask = 'data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22black%22%20stroke-width%3D%221%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20class%3D%22icon%20icon-tabler%20icons-tabler-outline%22%3E%3Cpath%20stroke%3D%22none%22%20d%3D%22M0%200h24v24H0z%22%20fill%3D%22none%22%2F%3E%3Cpath%20d%3D%22M10%2013a2%202%200%201%200%204%200a2%202%200%200%200%20-4%200%22%2F%3E%3Cpath%20d%3D%22M8%2021v-1a2%202%200%200%201%202%20-2h4a2%202%200%200%201%202%202v1%22%2F%3E%3Cpath%20d%3D%22M15%205a2%202%200%201%200%204%200a2%202%200%200%200%20-4%200%22%2F%3E%3Cpath%20d%3D%22M17%2010h2a2%202%200%200%201%202%202v1%22%2F%3E%3Cpath%20d%3D%22M5%205a2%202%200%201%200%204%200a2%202%200%200%200%20-4%200%22%2F%3E%3Cpath%20d%3D%22M3%2013v-1a2%202%200%200%201%202%20-2h2%22%2F%3E%3C%2Fsvg%3E';

$pb_icon_pilot_mask = 'data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22black%22%20stroke-width%3D%221%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20class%3D%22icon%20icon-tabler%20icons-tabler-outline%22%3E%3Cpath%20stroke%3D%22none%22%20d%3D%22M0%200h24v24H0z%22%20fill%3D%22none%22%2F%3E%3Cpath%20d%3D%22M8%207a4%204%200%201%200%208%200a4%204%200%200%200%20-8%200%22%2F%3E%3Cpath%20d%3D%22M6%2021v-2a4%204%200%200%201%204%20-4h4a4%204%200%200%201%204%204v2%22%2F%3E%3C%2Fsvg%3E';

// 1) Parent menu → default to Participants List
add_action('admin_menu', function () use ($pb_icon_main_white) {
	add_menu_page(
		'Publibalão',
		'Publibalão',
		'edit_posts',
		'publibalao',
		function () {
			if (current_user_can('edit_posts')) {
				wp_safe_redirect(admin_url('edit.php?post_type=pb_participants_list'));
				exit;
			}
			wp_die(__('You do not have permission to access this page.'));
		},
		$pb_icon_main_white,
		5
	);
});

// 2) Group CPTs under parent
add_filter('register_post_type_args', function ($args, $post_type) {
	if ($post_type === 'pb_participants_list' || $post_type === 'pb_pilot' || $post_type === 'pb_team') {
		$args['show_in_menu'] = 'publibalao';
	}
	return $args;
}, 10, 2);

// 3) Submenu icons via masks (white)
add_action('admin_head', function () use ($pb_icon_participants_mask, $pb_icon_pilot_mask) {
	?>
	<style>
		:root { --pb-admin-icon-color: #fff; }

		/* Participants List */
		#toplevel_page_publibalao .wp-submenu a[href$="edit.php?post_type=pb_participants_list"]::before,
		#toplevel_page_publibalao .wp-submenu a[href$="post-new.php?post_type=pb_participants_list"]::before {
			content: ""; display: inline-block; width: 14px; height: 14px; margin-right: 6px; vertical-align: text-bottom;
			background-color: var(--pb-admin-icon-color);
			-webkit-mask: url('<?php echo esc_attr($pb_icon_participants_mask); ?>') no-repeat center / contain;
			        mask: url('<?php echo esc_attr($pb_icon_participants_mask); ?>') no-repeat center / contain;
		}

		/* Pilots */
		#toplevel_page_publibalao .wp-submenu a[href$="edit.php?post_type=pb_pilot"]::before,
		#toplevel_page_publibalao .wp-submenu a[href$="post-new.php?post_type=pb_pilot"]::before {
			content: ""; display: inline-block; width: 14px; height: 14px; margin-right: 6px; vertical-align: text-bottom;
			background-color: var(--pb-admin-icon-color);
			-webkit-mask: url('<?php echo esc_attr($pb_icon_pilot_mask); ?>') no-repeat center / contain;
			        mask: url('<?php echo esc_attr($pb_icon_pilot_mask); ?>') no-repeat center / contain;
		}
	</style>
	<?php
});
