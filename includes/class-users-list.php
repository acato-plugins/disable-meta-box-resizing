<?php
/**
 * Users list table column and filter.
 *
 * @package DisableMetaBoxResizing
 */

declare( strict_types=1 );

namespace DisableMetaBoxResizing;

defined( 'ABSPATH' ) || exit;

/**
 * Shows each user's preference in the users overview and lets you filter on it,
 * when enabled in the plugin settings.
 */
class Users_List {

	/**
	 * Key of the column in the list table.
	 *
	 * @var string
	 */
	private const COLUMN = 'dmbr_meta_box_resizing';

	/**
	 * Query argument holding the selected filter.
	 *
	 * @var string
	 */
	private const FILTER = 'dmbr_resizing';

	/**
	 * Hook the column and the filter into WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'manage_users_columns', array( $this, 'add_column' ) );
		add_filter( 'wpmu_users_columns', array( $this, 'add_column' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'render_column' ), 10, 3 );
		add_action( 'restrict_manage_users', array( $this, 'render_filter' ) );
		add_filter( 'views_users-network', array( $this, 'render_network_filter' ) );
		add_filter( 'users_list_table_query_args', array( $this, 'filter_query_args' ) );
	}

	/**
	 * Append the column to the users list table.
	 *
	 * @param array<string, string> $columns Existing columns.
	 *
	 * @return array<string, string>
	 */
	public function add_column( array $columns ): array {
		if ( ! Settings::get( 'show_in_user_table' ) ) {
			return $columns;
		}

		$columns[ self::COLUMN ] = __( 'Meta box resizing', 'disable-meta-box-resizing' );

		return $columns;
	}

	/**
	 * Render the cell for a single user.
	 *
	 * Parameters are left untyped on purpose: this is a filter callback in a
	 * file under strict_types, so a loosely typed argument from a third party
	 * would otherwise be fatal.
	 *
	 * @param mixed $output      Column output so far.
	 * @param mixed $column_name Column being rendered.
	 * @param mixed $user_id     ID of the user in this row.
	 *
	 * @return mixed
	 */
	public function render_column( $output, $column_name, $user_id ) {
		if ( self::COLUMN !== $column_name ) {
			return $output;
		}

		return esc_html( self::label( Preference::is_disabled_for( (int) $user_id ) ) );
	}

	/**
	 * Render the dropdown above the users list table.
	 *
	 * @param string $which Which tablenav is being rendered, "top" or "bottom".
	 *
	 * @return void
	 */
	public function render_filter( string $which ): void {
		if ( 'top' !== $which || ! Settings::get( 'show_in_user_table' ) ) {
			return;
		}

		$this->render_controls();
	}

	/**
	 * Render the same dropdown on the network users screen.
	 *
	 * WP_MS_Users_List_Table never fires `restrict_manage_users`, and its table
	 * sits inside a POST form, so a control placed there could not submit as a
	 * GET filter. This filter is the one hook that renders outside both forms,
	 * so the dropdown gets a small GET form of its own. The views are returned
	 * untouched.
	 *
	 * @param mixed $views The view links of the list table.
	 *
	 * @return mixed
	 */
	public function render_network_filter( $views ) {
		if ( ! Settings::get( 'show_in_user_table' ) ) {
			return $views;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only, carries the current search term over to the filtered result.
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		?>
		<form method="get" class="alignleft actions">
			<?php if ( '' !== $search ) : ?>
				<input type="hidden" name="s" value="<?php echo esc_attr( $search ); ?>" />
			<?php endif; ?>
			<?php $this->render_controls(); ?>
		</form>
		<?php

		return $views;
	}

	/**
	 * The dropdown and its submit button.
	 *
	 * @return void
	 */
	private function render_controls(): void {
		$selected = $this->get_requested_filter();
		?>
		<label class="screen-reader-text" for="<?php echo esc_attr( self::FILTER ); ?>">
			<?php esc_html_e( 'Filter by meta box resizing', 'disable-meta-box-resizing' ); ?>
		</label>
		<select name="<?php echo esc_attr( self::FILTER ); ?>" id="<?php echo esc_attr( self::FILTER ); ?>">
			<option value=""><?php esc_html_e( 'Meta box resizing: all', 'disable-meta-box-resizing' ); ?></option>
			<option value="disabled" <?php selected( $selected, 'disabled' ); ?>>
				<?php echo esc_html( self::label( true ) ); ?>
			</option>
			<option value="enabled" <?php selected( $selected, 'enabled' ); ?>>
				<?php echo esc_html( self::label( false ) ); ?>
			</option>
		</select>
		<?php
		submit_button( __( 'Filter', 'disable-meta-box-resizing' ), '', self::FILTER . '_submit', false );
	}

	/**
	 * Narrow the users list table down to the selected preference.
	 *
	 * This hooks the list table's own query arguments rather than
	 * `pre_get_users`, so unrelated user queries on the same screen are left
	 * alone.
	 *
	 * @param array<string, mixed> $args Query arguments for the list table.
	 *
	 * @return array<string, mixed>
	 */
	public function filter_query_args( array $args ): array {
		$selected = $this->get_requested_filter();

		if ( '' === $selected || ! Settings::get( 'show_in_user_table' ) ) {
			return $args;
		}

		$existing = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) && ! empty( $args['meta_query'] )
			? $args['meta_query']
			: array();

		$ours = $this->build_meta_query( 'disabled' === $selected );

		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Admin only, opt-in, and only when an administrator picks a value in the dropdown.
		$args['meta_query'] = $existing
			? array(
				'relation' => 'AND',
				$existing,
				$ours,
			)
			: $ours;

		return $args;
	}

	/**
	 * Build the meta query for one of the two states.
	 *
	 * A user without a stored value follows the site wide default, so that
	 * bucket also has to match the users who have no meta row at all.
	 *
	 * @param bool $disabled Whether to match users who have resizing disabled.
	 *
	 * @return array<int|string, mixed>
	 */
	private function build_meta_query( bool $disabled ): array {
		$stored_value = array(
			'key'     => Preference::META_KEY,
			'value'   => $disabled ? '1' : '',
			'compare' => '=',
		);

		if ( Settings::get( 'default_state' ) !== $disabled ) {
			return array( $stored_value );
		}

		return array(
			'relation' => 'OR',
			$stored_value,
			array(
				'key'     => Preference::META_KEY,
				'compare' => 'NOT EXISTS',
			),
		);
	}

	/**
	 * The filter currently selected, if it is one we know.
	 *
	 * @return string One of "disabled", "enabled" or an empty string.
	 */
	private function get_requested_filter(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only list table filter, the value is checked against a fixed list below.
		$selected = isset( $_GET[ self::FILTER ] ) ? sanitize_key( wp_unslash( $_GET[ self::FILTER ] ) ) : '';

		return in_array( $selected, array( 'disabled', 'enabled' ), true ) ? $selected : '';
	}

	/**
	 * Human readable label for a preference state.
	 *
	 * @param bool $disabled Whether resizing is disabled.
	 *
	 * @return string
	 */
	private static function label( bool $disabled ): string {
		return $disabled
			? __( 'Disabled', 'disable-meta-box-resizing' )
			: __( 'Enabled', 'disable-meta-box-resizing' );
	}
}
