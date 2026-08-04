<?php
/**
 * User profile integration.
 *
 * @package DisableMetaBoxResizing
 */

declare( strict_types=1 );

namespace DisableMetaBoxResizing;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the preference to the user profile screen.
 */
class Profile {

	/**
	 * Name of the nonce field on the profile form.
	 *
	 * @var string
	 */
	private const NONCE_NAME = 'dmbr_profile_nonce';

	/**
	 * Action of the nonce on the profile form.
	 *
	 * @var string
	 */
	private const NONCE_ACTION = 'dmbr_save_profile_preference';

	/**
	 * Hook the profile field into WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'show_user_profile', array( $this, 'render_field' ) );
		add_action( 'edit_user_profile', array( $this, 'render_field' ) );
		add_action( 'personal_options_update', array( $this, 'save_field' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_field' ) );
	}

	/**
	 * Render the preference on the profile screen.
	 *
	 * @param \WP_User $user The user being edited.
	 *
	 * @return void
	 */
	public function render_field( \WP_User $user ): void {
		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<h2><?php esc_html_e( 'Editor meta boxes', 'disable-meta-box-resizing' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Meta box panel', 'disable-meta-box-resizing' ); ?></th>
				<td>
					<label for="<?php echo esc_attr( Preference::META_KEY ); ?>">
						<input
							type="checkbox"
							name="<?php echo esc_attr( Preference::META_KEY ); ?>"
							id="<?php echo esc_attr( Preference::META_KEY ); ?>"
							value="1"
							<?php checked( Preference::is_disabled_for( $user->ID ) ); ?>
						/>
						<?php esc_html_e( 'Disable resizing of the meta box panel', 'disable-meta-box-resizing' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'Hides the drag handle at the bottom of the post editor and keeps the meta box panel expanded, the way it worked before WordPress 7.0.', 'disable-meta-box-resizing' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Persist the preference when the profile form is submitted.
	 *
	 * @param int $user_id ID of the user being saved.
	 *
	 * @return void
	 */
	public function save_field( int $user_id ): void {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		$nonce = isset( $_POST[ self::NONCE_NAME ] ) ? sanitize_key( wp_unslash( $_POST[ self::NONCE_NAME ] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		Preference::update_for( $user_id, isset( $_POST[ Preference::META_KEY ] ) );
	}
}
