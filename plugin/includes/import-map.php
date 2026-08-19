<?php

/**
 * Import map for plugin JS modules — lets the theme (or any consumer) import
 * plugin scripts by bare specifier instead of a hardcoded URL:
 *
 *   import { fillForm } from 'pb/dev/form.js';
 *
 * Built through the `pb_import_map` filter rather than printed directly, so the
 * plugin and the theme can both contribute to a single <script type="importmap">
 * — outside Chrome 133+ only the first map in the document is honoured.
 */

if ( ! defined( 'ABSPATH' ) ) exit;


add_action( 'wp_head', function () {

	$imports = apply_filters( 'pb_import_map', [] );

	if ( $imports ) {

		printf(
			'<script type="importmap">%s</script>' . "\n",
			wp_json_encode( [ 'imports' => $imports ], JSON_UNESCAPED_SLASHES )
		);

	}

}, 1 );


add_filter( 'pb_import_map', function ( array $imports ): array {

	$dir = PB_PLUGIN_DIR . 'includes/js/';

	// A trailing-slash prefix mapping cannot carry ?ver= (the specifier remainder
	// is appended after the mapped value), so imported modules would be cached
	// indefinitely. Under WP_DEBUG map each file individually to get filemtime
	// busting; in production the prefix mapping keeps new files reachable with no
	// map update and no per-request directory walk.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {

		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS )
		);

		foreach ( $files as $file ) {

			if ( $file->getExtension() !== 'js' ) {
				continue;
			}

			$path = wp_normalize_path( $file->getPathname() );

			$imports[ 'pb/' . substr( $path, strlen( wp_normalize_path( $dir ) ) ) ] =
				plura_wp_file_url( $path ) . '?ver=' . $file->getMTime();

		}

		return $imports;

	}

	return $imports + [ 'pb/' => PB_PLUGIN_URL . 'includes/js/' ];

} );
