<?php
/**
 * Plugin Name:       Disable Meta Box Resizing
 * Plugin URI:        https://github.com/acato-plugins/disable-meta-box-resizing
 * Description:       Lets each user turn off the resizable meta box panel that WordPress 7.0 added to the bottom of the post editor, restoring the classic always-expanded panel.
 * Version:           1.0.5
 * Requires at least: 7.0
 * Requires PHP:      8.2
 * Author:            Acato
 * Author URI:        https://acato.nl
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       disable-meta-box-resizing
 *
 * @package DisableMetaBoxResizing
 */

declare( strict_types=1 );

namespace DisableMetaBoxResizing;

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'DMBR_VERSION' ) ) {
	define( 'DMBR_VERSION', '1.0.5' );
}

if ( ! defined( 'DMBR_PLUGIN_FILE' ) ) {
	define( 'DMBR_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'DMBR_PLUGIN_DIR' ) ) {
	define( 'DMBR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) ?? __DIR__ );
}

/**
 * Autoload the plugin classes.
 *
 * Maps `DisableMetaBoxResizing\Some_Class` to `includes/class-some-class.php`.
 *
 * @param string $class_name Fully qualified class name being loaded.
 *
 * @return void
 */
spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = __NAMESPACE__ . '\\';

		if ( ! str_starts_with( $class_name, $prefix ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( $prefix ) );
		$file     = DMBR_PLUGIN_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', $relative ) ) . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

register_activation_hook( DMBR_PLUGIN_FILE, array( Requirements::class, 'block_activation' ) );

// Nothing below WordPress 7 has a resizable meta box panel to switch off, so
// the plugin deactivates itself there instead of running against nothing.
if ( ! Requirements::are_met() ) {
	( new Requirements() )->register();

	return;
}

Plugin::instance()->boot();
