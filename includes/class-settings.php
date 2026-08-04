<?php
/**
 * Site wide settings.
 *
 * @package DisableMetaBoxResizing
 */

declare( strict_types=1 );

namespace DisableMetaBoxResizing;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the settings screen under Settings, or under Network Settings on a
 * multisite install, and reads the stored values back out.
 */
class Settings {

	/**
	 * Option name holding the settings array.
	 *
	 * @var string
	 */
	public const OPTION = 'dmbr_settings';

	/**
	 * Slug of the settings screen.
	 *
	 * @var string
	 */
	public const PAGE_SLUG = 'disable-meta-box-resizing';

	/**
	 * Settings API group, used on single site installs only.
	 *
	 * @var string
	 */
	private const GROUP = 'dmbr_settings_group';

	/**
	 * Settings API section.
	 *
	 * @var string
	 */
	private const SECTION = 'dmbr_settings_section';

	/**
	 * Action the network settings form posts to.
	 *
	 * @var string
	 */
	private const NETWORK_ACTION = 'dmbr_update_network_settings';

	/**
	 * Hook the settings screen into WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'register_fields' ) );

		if ( is_multisite() ) {
			add_action( 'network_admin_menu', array( $this, 'add_network_page' ) );
			add_action( 'network_admin_edit_' . self::NETWORK_ACTION, array( $this, 'save_network_settings' ) );
			add_action( 'admin_menu', array( $this, 'add_network_shortcut' ) );

			return;
		}

		add_action( 'admin_menu', array( $this, 'add_page' ) );
	}

	/**
	 * The settings and their default values.
	 *
	 * @return array<string, bool>
	 */
	public static function defaults(): array {
		return array(
			'default_state'            => false,
			'remove_data_on_uninstall' => false,
			'show_in_user_table'       => false,
		);
	}

	/**
	 * Read a single setting.
	 *
	 * @param string $key Key of the setting, see self::defaults().
	 *
	 * @return bool
	 */
	public static function get( string $key ): bool {
		$stored = is_multisite()
			? get_site_option( self::OPTION, array() )
			: get_option( self::OPTION, array() );

		$settings = wp_parse_args( is_array( $stored ) ? $stored : array(), self::defaults() );

		return ! empty( $settings[ $key ] );
	}

	/**
	 * Add the settings screen below Settings on a single site install.
	 *
	 * @return void
	 */
	public function add_page(): void {
		add_options_page(
			__( 'Meta Box Resizing', 'disable-meta-box-resizing' ),
			__( 'Meta Box Resizing', 'disable-meta-box-resizing' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Add the settings screen below Network Settings on a multisite install.
	 *
	 * @return void
	 */
	public function add_network_page(): void {
		add_submenu_page(
			'settings.php',
			__( 'Meta Box Resizing', 'disable-meta-box-resizing' ),
			__( 'Meta Box Resizing', 'disable-meta-box-resizing' ),
			'manage_network_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * On multisite, put an entry below Settings in each site's admin that leads
	 * to the network settings screen where the options actually live.
	 *
	 * A menu slug holding a full URL does not survive add_submenu_page(), so
	 * this registers a real page and redirects before anything is rendered.
	 *
	 * @return void
	 */
	public function add_network_shortcut(): void {
		$hook = add_options_page(
			__( 'Meta Box Resizing', 'disable-meta-box-resizing' ),
			__( 'Meta Box Resizing', 'disable-meta-box-resizing' ),
			'manage_network_options',
			self::PAGE_SLUG,
			'__return_null'
		);

		if ( ! $hook ) {
			return;
		}

		add_action( 'load-' . $hook, array( $this, 'redirect_to_network_page' ) );
	}

	/**
	 * Send the site admin entry to the network settings screen.
	 *
	 * @return void
	 */
	public function redirect_to_network_page(): void {
		wp_safe_redirect( network_admin_url( 'settings.php?page=' . self::PAGE_SLUG ) );

		exit;
	}

	/**
	 * Register the setting and build the form fields.
	 *
	 * The fields are shared between both screens; only the way the form is
	 * saved differs.
	 *
	 * @return void
	 */
	public function register_fields(): void {
		if ( ! is_multisite() ) {
			register_setting(
				self::GROUP,
				self::OPTION,
				array(
					'type'              => 'array',
					'sanitize_callback' => array( $this, 'sanitize' ),
					'default'           => self::defaults(),
					'show_in_rest'      => false,
				)
			);
		}

		add_settings_section(
			self::SECTION,
			'',
			array( $this, 'render_section_description' ),
			self::PAGE_SLUG
		);

		foreach ( $this->get_field_definitions() as $key => $field ) {
			add_settings_field(
				$key,
				$field['label'],
				array( $this, 'render_checkbox' ),
				self::PAGE_SLUG,
				self::SECTION,
				array(
					'key'       => $key,
					'text'      => $field['text'],
					'help'      => $field['help'],
					'label_for' => 'dmbr-' . $key,
				)
			);
		}
	}

	/**
	 * Label, checkbox text and description for every setting.
	 *
	 * @return array<string, array<string, string>>
	 */
	private function get_field_definitions(): array {
		return array(
			'default_state'            => array(
				'label' => __( 'Default state', 'disable-meta-box-resizing' ),
				'text'  => __( 'Disable meta box resizing by default', 'disable-meta-box-resizing' ),
				'help'  => __( 'Applies to users who have not made a choice of their own yet. Users who did keep their own setting.', 'disable-meta-box-resizing' ),
			),
			'remove_data_on_uninstall' => array(
				'label' => __( 'Uninstall', 'disable-meta-box-resizing' ),
				'text'  => __( 'Remove all data of this plugin when it is uninstalled', 'disable-meta-box-resizing' ),
				'help'  => __( 'Deletes these settings and every stored user preference. Leave this off to keep the preferences when reinstalling.', 'disable-meta-box-resizing' ),
			),
			'show_in_user_table'       => array(
				'label' => __( 'Users list', 'disable-meta-box-resizing' ),
				'text'  => __( 'Show a column with each user\'s setting', 'disable-meta-box-resizing' ),
				'help'  => __( 'Adds a read only column to the users overview so you can see who has resizing disabled.', 'disable-meta-box-resizing' ),
			),
		);
	}

	/**
	 * Short introduction above the fields.
	 *
	 * @return void
	 */
	public function render_section_description(): void {
		printf(
			'<p>%s</p>',
			esc_html__( 'Meta box resizing is a per user preference. These settings decide the starting point for that preference and how the plugin behaves around it.', 'disable-meta-box-resizing' )
		);
	}

	/**
	 * Render one checkbox field.
	 *
	 * @param array<string, string> $args Field arguments, see register_fields().
	 *
	 * @return void
	 */
	public function render_checkbox( array $args ): void {
		printf(
			'<label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s[%3$s]" value="1"%4$s /> %5$s</label>',
			esc_attr( $args['label_for'] ),
			esc_attr( self::OPTION ),
			esc_attr( $args['key'] ),
			checked( self::get( $args['key'] ), true, false ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- checked() returns a fixed, safe attribute string.
			esc_html( $args['text'] )
		);

		printf( '<p class="description">%s</p>', esc_html( $args['help'] ) );
	}

	/**
	 * Render the settings screen for both single site and multisite.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( $this->get_capability() ) ) {
			return;
		}

		$is_network = is_multisite();
		$action     = $is_network
			? network_admin_url( 'edit.php?action=' . self::NETWORK_ACTION )
			: admin_url( 'options.php' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only flag set by our own redirect after a nonce checked save.
		$is_updated = $is_network && isset( $_GET['updated'] );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( $is_updated ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved.', 'disable-meta-box-resizing' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( $action ); ?>">
				<?php
				if ( $is_network ) {
					wp_nonce_field( self::NETWORK_ACTION );
				} else {
					settings_fields( self::GROUP );
				}

				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Persist the settings posted from the network settings screen.
	 *
	 * Network screens cannot use options.php, so this handles the save itself.
	 *
	 * @return void
	 */
	public function save_network_settings(): void {
		if ( ! current_user_can( $this->get_capability() ) ) {
			wp_die( esc_html__( 'You are not allowed to change these settings.', 'disable-meta-box-resizing' ) );
		}

		check_admin_referer( self::NETWORK_ACTION );

		// sanitize() reduces the input to a fixed set of booleans, so the raw
		// value never reaches the database.
		$raw = isset( $_POST[ self::OPTION ] ) && is_array( $_POST[ self::OPTION ] )
			? wp_unslash( $_POST[ self::OPTION ] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized on the next line.
			: array();

		update_site_option( self::OPTION, $this->sanitize( $raw ) );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::PAGE_SLUG,
					'updated' => 'true',
				),
				network_admin_url( 'settings.php' )
			)
		);

		exit;
	}

	/**
	 * Reduce submitted input to the known settings, as booleans.
	 *
	 * @param mixed $input Raw submitted value.
	 *
	 * @return array<string, bool>
	 */
	public function sanitize( $input ): array {
		$input     = is_array( $input ) ? $input : array();
		$sanitized = array();

		foreach ( array_keys( self::defaults() ) as $key ) {
			$sanitized[ $key ] = ! empty( $input[ $key ] );
		}

		return $sanitized;
	}

	/**
	 * Capability required to manage the settings.
	 *
	 * @return string
	 */
	private function get_capability(): string {
		return is_multisite() ? 'manage_network_options' : 'manage_options';
	}
}
