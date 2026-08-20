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

/**
 * Bootstrap once every plugin is loaded rather than at file scope: this plugin now
 * depends on the Plura plugin for plura_includes/plura_wp_enqueue/plura_wp_posts/
 * plura_attributes, and it only resolves today because "plura" happens to sort before
 * "publibalao" in active_plugins. Shortcodes and REST routes are consumed later, so
 * waiting costs nothing.
 */
add_action('plugins_loaded', function () {

	if (!function_exists('plura_includes')) {

		add_action('admin_notices', fn() => printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__('Publibalão requires the Plura plugin to be active.', 'publibalao')
		));

		return;

	}

	//admin: true keeps the behaviour of the foreach this replaces — Divi fetches
	//rendered content over admin-ajax, where is_admin() is true and the modules'
	//shortcodes would otherwise go unregistered
	plura_includes([

		//all that survives of the old vendored plura copy: five [p-*] shortcodes that
		//may still be in post content. Drop it once that is confirmed.
		'p/p',

		'includes/api',
		'includes/core',
		'includes/events',
		'includes/locations',
		'includes/media',
		'includes/data',
		'includes/import-map',
		'includes/teams-and-pilots',

	], __DIR__, admin: true);

	add_action('wp_enqueue_scripts', 'publibalao_styles_and_scripts');

});


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

	// CDN bundles, enqueued unprefixed: the handles are referenced as deps here and
	// matched by name in the theme's integrity filter. plura_wp_enqueue leaves
	// external URLs unversioned, which is right — the URL already pins the version.
	$cdn = [
		'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css'    => [ 'handle' => 'fancybox' ],
		'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js' => [ 'handle' => 'fancybox' ],
	];

	// Local assets. Paths rather than URLs, so filemtime versioning applies.
	$assets = [
		PB_PLUGIN_DIR . 'includes/css/globals.css'       => [],
		PB_PLUGIN_DIR . 'includes/css/globals-theme.css' => [],
	];

	// Optionally load carousel stuff only when needed
	if ( (is_single() || is_page()) && $post instanceof WP_Post && pb_has_shortcode($post->ID, 'pb-carousel-images') ) {

		$cdn += [
			'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/carousel/carousel.css'                 => [ 'handle' => 'carousel',          'deps' => [ 'fancybox' ] ],
			'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/carousel/carousel.umd.js'              => [ 'handle' => 'carousel',          'deps' => [ 'fancybox' ] ],
			'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/carousel/carousel.thumbs.css'          => [ 'handle' => 'carousel-thumbs',   'deps' => [ 'carousel' ] ],
			'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/carousel/carousel.thumbs.umd.js'       => [ 'handle' => 'carousel-thumbs',   'deps' => [ 'carousel' ] ],
			'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0.36/dist/carousel/carousel.autoplay.css'     => [ 'handle' => 'carousel-autoplay', 'deps' => [ 'carousel' ] ],
			'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0.36/dist/carousel/carousel.autoplay.umd.js'  => [ 'handle' => 'carousel-autoplay', 'deps' => [ 'carousel' ] ],
		];

	}

	// Entry module. `module => true` replaces the hand-rolled script_loader_tag filter
	// this used to need. plura-layout-headings-nav.js is no longer enqueued here —
	// scripts.js imports it once it has found a holder, so the shortcode check that
	// used to gate it is not duplicated in PHP.
	$assets[ PB_PLUGIN_DIR . 'includes/js/scripts.js' ] = [ 'handle' => 'core', 'module' => true ];

	plura_wp_enqueue( scripts: $cdn );

	plura_wp_enqueue( scripts: $assets, prefix: 'pb-' );

	// Printed inline immediately before the module tag, so it is set before it runs.
	if ( $data ) {
		wp_localize_script('pb-core', 'pb_data', $data);
	}
}



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
