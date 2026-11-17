<?php
/**
 * A short walkthrough of the settings screen, taken when somebody asks for it.
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
 * It never starts by itself. It is started from the "Start walkthrough"
 * link beside Settings on the Plugins screen, tagged "New" while this user
 * has steps they have not seen, or from the link at the foot of the settings
 * screen. The site, or the network on multisite, can switch it off in the
 * settings, and code can with the `disable_meta_box_resizing_walkthrough`
 * filter or the DMBR_DISABLE_WALKTHROUGH constant; then neither link shows.
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
	 * Query argument on the settings screen's URL that starts the walkthrough.
	 *
	 * @var string
	 */
	public const START_ARG = 'dmbr-walkthrough';

	/**
	 * Hook the walkthrough into WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		$basename = plugin_basename( DMBR_PLUGIN_FILE );

		// After Settings' own link, at 10, so this one comes next to it.
		add_filter( 'plugin_action_links_' . $basename, array( $this, 'add_action_link' ), 11 );
		add_filter( 'network_admin_plugin_action_links_' . $basename, array( $this, 'add_action_link' ), 11 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'tag_style' ) );
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
	 * Whether the walkthrough is offered at all.
	 *
	 * The setting lives with the others: per site, or for the whole network
	 * on multisite. Code has the last word: the DMBR_DISABLE_WALKTHROUGH
	 * constant switches it off everywhere, and the
	 * `disable_meta_box_resizing_walkthrough` filter decides per request.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		if ( defined( 'DMBR_DISABLE_WALKTHROUGH' ) && DMBR_DISABLE_WALKTHROUGH ) {
			return false;
		}

		/**
		 * Whether the walkthrough of the settings screen is offered.
		 *
		 * @param bool $enabled Whether the settings switch it on.
		 */
		return (bool) apply_filters( 'disable_meta_box_resizing_walkthrough', Settings::get( 'walkthrough' ) );
	}

	/**
	 * Whether the current user manages the plugin and has steps they have not seen yet.
	 *
	 * @return bool
	 */
	public static function is_pending(): bool {
		if ( ! current_user_can( Settings::capability() ) || ! self::is_enabled() ) {
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
	 * The screen's stylesheet is built with the walkthrough, so with the
	 * walkthrough switched off the stylesheet still loads, without the script.
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

		if ( ! self::is_enabled() ) {
			Assets::enqueue_style( 'settings' );

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
					'start'   => self::START_ARG,
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'action'  => self::ACTION,
					'nonce'   => wp_create_nonce( self::ACTION ),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * The "Start walkthrough" link beside Settings on the Plugins screen,
	 * tagged "New" while this user has steps they have not seen.
	 *
	 * Left untyped on purpose, like the other filter callbacks.
	 *
	 * @param mixed $links Action links of the plugin's row.
	 *
	 * @return mixed
	 */
	public function add_action_link( $links ) {
		// Only for those who can open the settings screen, like Settings.
		if ( ! is_array( $links ) || ! current_user_can( Settings::capability() ) || ! self::is_enabled() ) {
			return $links;
		}

		$tag = self::is_pending()
			? ' <span class="dmbr-walkthrough-tag">' . esc_html_x( 'New', 'walkthrough tag on the Plugins screen', 'disable-meta-box-resizing' ) . '</span>'
			: '';

		$link = sprintf(
			'<a href="%s">%s</a>%s',
			esc_url( add_query_arg( self::START_ARG, '1', Settings::page_url() ) ),
			esc_html_x( 'Start walkthrough', 'plugin action link', 'disable-meta-box-resizing' ),
			$tag
		);

		// Right after Settings, which Settings::add_action_link() puts first.
		array_splice( $links, 1, 0, array( $link ) );

		return $links;
	}

	/**
	 * The look of the "New" tag, on the Plugins screen only, while it shows.
	 *
	 * @param mixed $hook_suffix Screen the hook fired for.
	 *
	 * @return void
	 */
	public function tag_style( $hook_suffix ): void {
		if ( 'plugins.php' !== $hook_suffix || ! self::is_pending() ) {
			return;
		}

		// Shaped like core's update count bubbles, in core's red, white on it.
		wp_register_style( 'dmbr-walkthrough-tag', false, array(), DMBR_VERSION );
		wp_enqueue_style( 'dmbr-walkthrough-tag' );
		wp_add_inline_style(
			'dmbr-walkthrough-tag',
			'.dmbr-walkthrough-tag{display:inline-block;margin-inline-start:4px;padding:0 6px;border-radius:9px;background:#d63638;color:#fff;font-size:9px;font-weight:600;line-height:17px;text-transform:uppercase;vertical-align:text-top}@media (forced-colors:active){.dmbr-walkthrough-tag{border:1px solid CanvasText}}'
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
