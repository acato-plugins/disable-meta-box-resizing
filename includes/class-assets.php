<?php
/**
 * Loads the built admin assets.
 *
 * @package DisableMetaBoxResizing
 */

declare( strict_types=1 );

namespace DisableMetaBoxResizing;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues a build entry point, its script and its stylesheet, under one
 * handle, with the dependencies and version wp-scripts wrote next to it.
 */
final class Assets {

	/**
	 * The handle an entry point is registered under.
	 *
	 * @param string $entry Name of the entry point, e.g. `settings`.
	 *
	 * @return non-empty-string
	 */
	public static function handle( string $entry ): string {
		return 'dmbr-' . $entry;
	}

	/**
	 * Enqueue the script and stylesheet built for an entry point.
	 *
	 * @param string   $entry        Name of the entry point, e.g. `settings`.
	 * @param string[] $script_deps  Dependencies on top of those the build found, such as scripts read off `window`.
	 * @param string[] $style_deps   Stylesheets the entry's stylesheet builds on.
	 *
	 * @return bool False when nothing was built for the entry point.
	 */
	public static function enqueue( string $entry, array $script_deps = array(), array $style_deps = array() ): bool {
		$asset_file = DMBR_PLUGIN_DIR . 'build/' . $entry . '.asset.php';

		if ( ! is_readable( $asset_file ) ) {
			return false;
		}

		$asset  = require $asset_file;
		$handle = self::handle( $entry );

		wp_enqueue_script(
			$handle,
			plugins_url( 'build/' . $entry . '.js', DMBR_PLUGIN_FILE ),
			array_merge( $asset['dependencies'], $script_deps ),
			$asset['version'],
			true
		);

		wp_set_script_translations( $handle, 'disable-meta-box-resizing' );

		if ( is_readable( DMBR_PLUGIN_DIR . 'build/' . $entry . '.css' ) ) {
			// The asset version is a hash of the whole entry point, so it
			// changes when the stylesheet changes too.
			wp_enqueue_style(
				$handle,
				plugins_url( 'build/' . $entry . '.css', DMBR_PLUGIN_FILE ),
				$style_deps,
				$asset['version']
			);
		}

		return true;
	}
}
