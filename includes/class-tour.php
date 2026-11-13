<?php
/**
 * A short walkthrough of the settings screen, shown again when it grows.
 *
 * @package DisableMetaBoxResizing
 */

declare( strict_types=1 );

namespace DisableMetaBoxResizing;

defined( 'ABSPATH' ) || exit;

/**
 * Walks those who manage the plugin through the settings screen, on
 * WordPress's own admin pointers.
 *
 * Every step says in which version it was added, and the newest of those is
 * the walkthrough's version. Each user's last completed or skipped version is
 * kept in their user meta. Somebody who never took the walkthrough gets all of
 * it; after an update that adds steps, somebody who took an older one gets
 * only the new steps, so it reads as what changed. Adding a step with a newer
 * `since` is all a release has to do: there is no version to bump next to it.
 *
 * Until then a card beside the Settings menu says there is something to see,
 * on every admin screen but the plugin's own, until it is taken or put off
 * with "Not now". It has the plugin's own look, the banner's dark glow and its
 * Wapuu, so it reads as this plugin's and not as one of core's.
 */
class Tour {

	/**
	 * User meta holding the walkthrough version this user last completed or skipped.
	 *
	 * @var string
	 */
	public const USER_META = 'dmbr_tour';

	/**
	 * AJAX action the script reports a finished walkthrough to.
	 *
	 * @var string
	 */
	private const ACTION = 'dmbr_tour_seen';

	/**
	 * Hook the walkthrough into WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'menu_pointer' ) );
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'mark_seen' ) );
	}

	/**
	 * Every step, oldest first.
	 *
	 * A step whose target is not on the screen is left out. `target` takes a
	 * selector list; the first match is used.
	 *
	 * @return array<int, array{since: string, target: string, title: string, text: string}>
	 */
	public static function steps(): array {
		return array(
			array(
				'since'  => '1.1.0',
				'target' => '.dmbr-intro',
				'title'  => _x( 'Per user, not per site', 'walkthrough step title', 'disable-meta-box-resizing' ),
				'text'   => _x( 'Every user decides for themselves whether the metabox panel can be resized: from the editor\'s Options menu, its Preferences, or their profile. This screen only sets the starting point.', 'walkthrough step', 'disable-meta-box-resizing' ),
			),
			array(
				'since'  => '1.1.0',
				'target' => '#dmbr-default_state',
				'title'  => _x( 'The starting point', 'walkthrough step title', 'disable-meta-box-resizing' ),
				'text'   => _x( 'Whether resizing starts out switched off for users who have not chosen yet. Anybody who did choose keeps their own choice.', 'walkthrough step', 'disable-meta-box-resizing' ),
			),
			array(
				'since'  => '1.1.0',
				'target' => '#dmbr-show_in_user_table',
				'title'  => _x( 'Who has it off', 'walkthrough step title', 'disable-meta-box-resizing' ),
				'text'   => _x( 'Adds a column to the Users screen with each user\'s setting, and a dropdown to filter the list on it.', 'walkthrough step', 'disable-meta-box-resizing' ),
			),
			array(
				'since'  => '1.1.0',
				'target' => '#dmbr-remove_data_on_uninstall',
				'title'  => _x( 'A clean removal', 'walkthrough step title', 'disable-meta-box-resizing' ),
				'text'   => _x( 'Off, every user keeps their preference when the plugin is reinstalled. On, uninstalling deletes these settings and every stored preference.', 'walkthrough step', 'disable-meta-box-resizing' ),
			),
			array(
				'since'  => '1.1.0',
				'target' => '.dmbr-settings #submit',
				'title'  => _x( 'Save, and you are done', 'walkthrough step title', 'disable-meta-box-resizing' ),
				'text'   => _x( 'Nothing changes until you save. To set it for one particular user, open their profile and look for Editor metaboxes.', 'walkthrough step', 'disable-meta-box-resizing' ),
			),
		);
	}

	/**
	 * The walkthrough's version: the newest step's.
	 *
	 * @return string
	 */
	public static function version(): string {
		return array_reduce(
			array_column( self::steps(), 'since' ),
			static fn( string $newest, string $since ): string => version_compare( $since, $newest, '>' ) ? $since : $newest,
			'0'
		);
	}

	/**
	 * The version this user last completed or skipped, or '' when they never did.
	 *
	 * @return string
	 */
	public static function seen(): string {
		return (string) get_user_meta( get_current_user_id(), self::USER_META, true );
	}

	/**
	 * Whether the current user manages the plugin and has steps they have not seen yet.
	 *
	 * @return bool
	 */
	public static function is_pending(): bool {
		if ( ! current_user_can( Settings::capability() ) ) {
			return false;
		}

		return version_compare( '' === self::seen() ? '0' : self::seen(), self::version(), '<' );
	}

	/**
	 * The steps added after a version, or every step when nothing was seen.
	 *
	 * @param string $seen Version last seen, '' for none.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function steps_since( string $seen ): array {
		if ( '' === $seen ) {
			return self::steps();
		}

		return array_values(
			array_filter(
				self::steps(),
				static fn( array $step ): bool => version_compare( $step['since'], $seen, '>' )
			)
		);
	}

	/**
	 * Load the walkthrough on the settings screen, for those who manage the plugin.
	 *
	 * Left untyped on purpose: this is an action callback in a file under
	 * strict_types, and other code may fire the hook with anything.
	 *
	 * @param mixed $hook_suffix Screen the hook fired for.
	 *
	 * @return void
	 */
	public function enqueue( $hook_suffix ): void {
		if ( ! Settings::is_settings_screen( $hook_suffix ) || ! current_user_can( Settings::capability() ) ) {
			return;
		}

		if ( ! Assets::enqueue( 'settings', array( 'wp-pointer', 'jquery' ), array( 'wp-pointer' ) ) ) {
			return;
		}

		wp_add_inline_script(
			Assets::handle( 'settings' ),
			'window.dmbrTour = ' . wp_json_encode(
				array(
					'steps'   => self::steps(),
					'pending' => self::is_pending() ? self::steps_since( self::seen() ) : array(),
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'action'  => self::ACTION,
					'nonce'   => wp_create_nonce( self::ACTION ),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * Core's name for this version's menu pointer, dismissed per user through
	 * core's own `dismiss-wp-pointer`.
	 *
	 * @return string
	 */
	private static function pointer_name(): string {
		return 'dmbr_tour_' . str_replace( '.', '_', self::version() );
	}

	/**
	 * The card beside the Settings menu, until the walkthrough is taken or put off.
	 *
	 * @param mixed $hook_suffix Screen the hook fired for.
	 *
	 * @return void
	 */
	public function menu_pointer( $hook_suffix ): void {
		if ( Settings::is_settings_screen( $hook_suffix ) || ! self::is_pending() ) {
			return;
		}

		$dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );

		if ( in_array( self::pointer_name(), $dismissed, true ) ) {
			return;
		}

		if ( ! Assets::enqueue( 'menu-pointer' ) ) {
			return;
		}

		$first = '' === self::seen();

		wp_add_inline_script(
			Assets::handle( 'menu-pointer' ),
			'window.dmbrMenuPointer = ' . wp_json_encode(
				array(
					'name'     => self::pointer_name(),
					'title'    => __( 'Disable Meta Box Resizing', 'disable-meta-box-resizing' ),
					'text'     => $first
						? _x( 'Let every user switch off the resizable metabox panel. A short walkthrough shows you around the settings.', 'walkthrough pointer', 'disable-meta-box-resizing' )
						: _x( 'Disable Meta Box Resizing has changed. A short walkthrough shows what is new.', 'walkthrough pointer', 'disable-meta-box-resizing' ),
					'link'     => Settings::page_url(),
					'linkText' => _x( 'Take the walkthrough', 'walkthrough pointer', 'disable-meta-box-resizing' ),
					'dismiss'  => _x( 'Not now', 'walkthrough pointer', 'disable-meta-box-resizing' ),
					'wapuu'    => plugins_url( 'src/images/wapuu.png', DMBR_PLUGIN_FILE ),
					'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * Store that the current user completed or skipped this version.
	 *
	 * @return void
	 */
	public function mark_seen(): void {
		if ( ! current_user_can( Settings::capability() ) ) {
			wp_send_json_error( null, 403 );
		}

		check_ajax_referer( self::ACTION );

		update_user_meta( get_current_user_id(), self::USER_META, self::version() );

		wp_send_json_success( array( 'version' => self::version() ) );
	}
}
