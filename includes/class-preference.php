<?php
/**
 * The per-user preference itself.
 *
 * @package DisableMetaBoxResizing
 */

declare( strict_types=1 );

namespace DisableMetaBoxResizing;

defined( 'ABSPATH' ) || exit;

/**
 * Single source of truth for the "disable meta box resizing" user preference.
 */
class Preference {

	/**
	 * User meta key holding the preference.
	 *
	 * @var string
	 */
	public const META_KEY = 'disable_meta_box_resizing';

	/**
	 * Body class added when the preference is enabled.
	 *
	 * @var string
	 */
	public const BODY_CLASS = 'dmbr-meta-box-resizing-disabled';

	/**
	 * Hook the preference into WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_meta' ) );
	}

	/**
	 * Register the user meta so it is available through the REST API.
	 *
	 * @return void
	 */
	public function register_meta(): void {
		register_meta(
			'user',
			self::META_KEY,
			array(
				'type'              => 'boolean',
				'description'       => __( 'Disable the resizable meta box panel in the post editor.', 'disable-meta-box-resizing' ),
				'single'            => true,
				// Users who never chose fall back to the site wide default, so
				// get_user_meta() and the REST API both report the right value.
				'default'           => Settings::get( 'default_state' ),
				'show_in_rest'      => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'auth_callback'     => array( $this, 'can_edit_preference' ),
			)
		);
	}

	/**
	 * Only the user themselves, or someone who may edit them, can change the preference.
	 *
	 * Parameters are left untyped on purpose: this is a filter callback in a
	 * file under strict_types, and WordPress does not guarantee the argument
	 * types it passes here.
	 *
	 * @param mixed $allowed   Whether the user can add or edit the meta. Unused.
	 * @param mixed $meta_key  The meta key being checked. Unused.
	 * @param mixed $object_id ID of the user the meta belongs to.
	 * @param mixed $user_id   ID of the user performing the request.
	 *
	 * @return bool
	 */
	public function can_edit_preference( $allowed, $meta_key, $object_id, $user_id ): bool {
		return user_can( (int) $user_id, 'edit_user', (int) $object_id );
	}

	/**
	 * Whether meta box resizing is disabled for the given user.
	 *
	 * @param int $user_id ID of the user to check.
	 *
	 * @return bool
	 */
	public static function is_disabled_for( int $user_id ): bool {
		return (bool) get_user_meta( $user_id, self::META_KEY, true );
	}

	/**
	 * Store the preference for the given user.
	 *
	 * @param int  $user_id     ID of the user to update.
	 * @param bool $is_disabled Whether resizing should be disabled.
	 *
	 * @return void
	 */
	public static function update_for( int $user_id, bool $is_disabled ): void {
		update_user_meta( $user_id, self::META_KEY, $is_disabled );
	}
}
