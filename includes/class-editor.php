<?php
/**
 * Block editor integration.
 *
 * @package DisableMetaBoxResizing
 */

declare( strict_types=1 );

namespace DisableMetaBoxResizing;

defined( 'ABSPATH' ) || exit;

/**
 * Loads the editor assets and reflects the preference in the admin body class.
 */
class Editor {

	/**
	 * Handle used for both the script and the stylesheet.
	 *
	 * @var string
	 */
	private const HANDLE = 'disable-meta-box-resizing';

	/**
	 * Hook the editor integration into WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_assets' ) );
		add_filter( 'admin_body_class', array( $this, 'filter_admin_body_class' ) );
	}

	/**
	 * Enqueue the preference toggle and the stylesheet that hides the resizer.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		if ( ! $this->is_post_editor() ) {
			return;
		}

		$asset_file = DMBR_PLUGIN_DIR . 'build/index.asset.php';

		if ( ! is_readable( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			self::HANDLE,
			plugins_url( 'build/index.js', DMBR_PLUGIN_FILE ),
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_set_script_translations( self::HANDLE, 'disable-meta-box-resizing' );

		// The asset version is a hash of the whole build entry point, so it
		// changes when the stylesheet changes too.
		wp_enqueue_style(
			self::HANDLE,
			plugins_url( 'build/style-index.css', DMBR_PLUGIN_FILE ),
			array(),
			$asset['version']
		);
	}

	/**
	 * Add the body class the stylesheet keys off when the preference is enabled.
	 *
	 * Left untyped on purpose: this is a filter callback in a file under
	 * strict_types, and other plugins on the same filter are not guaranteed to
	 * hand back a string.
	 *
	 * @param mixed $classes Space separated list of admin body classes.
	 *
	 * @return string
	 */
	public function filter_admin_body_class( $classes ): string {
		$classes = is_string( $classes ) ? $classes : '';

		if ( ! $this->is_post_editor() || ! Preference::is_disabled_for( get_current_user_id() ) ) {
			return $classes;
		}

		return trim( $classes . ' ' . Preference::BODY_CLASS );
	}

	/**
	 * Whether the current screen is the block editor for a single post.
	 *
	 * The site editor has no meta boxes, so the plugin stays out of it.
	 *
	 * @return bool
	 */
	private function is_post_editor(): bool {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();

		return $screen instanceof \WP_Screen && 'post' === $screen->base && $screen->is_block_editor();
	}
}
