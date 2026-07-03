<?php

function pwp_enqueue( $scripts, $prefix = '' ) {

	foreach($scripts as $key) {

		foreach( ['css', 'js'] as $type ) {

			$path = "includes/{$type}/{$key}.{$type}"; 

			if( file_exists( dirname( __FILE__, 3 ) . "/" . $path ) ) {

				if( $type === 'css' ) {

					wp_enqueue_style( $prefix . $key, plugins_url( $path, dirname( __FILE__, 2 ) ), [], time() );

				} else {

					wp_enqueue_script( $prefix . $key, plugins_url( $path, dirname( __FILE__, 2 ) ), [], time() );
				
				}

			}

		}

	}

}