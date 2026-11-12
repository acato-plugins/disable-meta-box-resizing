<?php
/**
 * Removes the plugin data when it is uninstalled.
 *
 * Only runs when "Remove all data of this plugin when it is uninstalled" is
 * enabled on the settings screen.
 *
 * @package DisableMetaBoxResizing
 */

declare( strict_types=1 );

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/includes/class-settings.php';
require_once __DIR__ . '/includes/class-preference.php';
require_once __DIR__ . '/includes/class-tour.php';

use DisableMetaBoxResizing\Preference;
use DisableMetaBoxResizing\Settings;
use DisableMetaBoxResizing\Tour;

if ( ! Settings::get( 'remove_data_on_uninstall' ) ) {
	return;
}

delete_metadata( 'user', 0, Preference::META_KEY, '', true );
delete_metadata( 'user', 0, Tour::USER_META, '', true );

if ( is_multisite() ) {
	delete_site_option( Settings::OPTION );
} else {
	delete_option( Settings::OPTION );
}
