<?php

/**
 *
 * @link              https://piecalendar.com
 * @since             1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       Pie Calendar
 * Plugin URI:        https://piecalendar.com
 * Description:       Turn any post type into a calendar event and display it on a calendar.
 * Version:           1.3.1
 * Author:            Elijah Mills & Jonathan Jernigan
 * Author URI:        https://piecalendar.com/about
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       piecal
 * Domain Path:       /languages
 * Requires PHP: 7.4
 * Requires at least: 5.9
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'PIECAL_VERSION', '1.3.1' );
define( 'PIECAL_PATH', plugin_dir_url( __FILE__ ) );
define( 'PIECAL_DIR', plugin_dir_path( __FILE__ ) );

// Includes
require_once PIECAL_DIR . 'includes/metabox.php';
require_once PIECAL_DIR . '/includes/utils/Scripts.php';

// File for registering & rendering shortcode.
require_once PIECAL_DIR . '/includes/shortcode.php';
require_once PIECAL_DIR . '/includes/piecal-info-shortcode.php';

// Blocks
require_once PIECAL_DIR . '/includes/block.php';

// Register scripts & styles
function piecal_register_scripts_and_styles() {

	$bundle = array(
		'alpinejs',
		'alpinefocus',
		'fullcalendar',
		'fullcalendar-locales',
		'piecal-utils',
		'piecalJS',
		'piecalCSS',
		'piecalThemeDarkCSS',
		'piecalThemeDarkCSSAdaptive',
	);

	Piecal\Utils\Scripts::registerAndLocalizeBundle( $bundle );
}
add_action( 'wp_enqueue_scripts', 'piecal_register_scripts_and_styles' );

// Defer Alpine script
add_filter(
	'script_loader_tag',
	function ( $tag, $handle ) {

		if ( ! in_array( $handle, array( 'alpinejs', 'alpinefocus', 'fullcalendar-locales' ) ) ) {
			return $tag;
		}

		return str_replace( ' src', ' defer="defer" src', $tag );
	},
	10,
	2
);


// Register required post meta fields.
add_action( 'init', 'piecal_register_post_meta' );

function piecal_register_post_meta() {
	register_post_meta(
		'',
		'_piecal_is_event',
		array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'boolean',
			'auth_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
	register_post_meta(
		'',
		'_piecal_start_date',
		array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'string',
			'auth_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
	register_post_meta(
		'',
		'_piecal_end_date',
		array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'string',
			'auth_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
	register_post_meta(
		'',
		'_piecal_is_allday',
		array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'boolean',
			'auth_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);

	add_action( 'admin_notices', 'piecal_admin_notice' );
}

function piecal_admin_notice() {
	if ( isset( $_GET['piecal-dismiss-notice'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'piecal_dismiss_notice' ) ) {
		update_option( 'piecal_hide_onboarding_notice', true );
		return;
	}

	if ( get_option( 'piecal_hide_onboarding_notice' ) ) {
		return;
	}

	?>
	<div class="notice notice-success">
		<p>
			<?php /* translators: Pie Calendar activation message */ ?>
			<?php esc_html_e( 'Pie Calendar has been activated.', 'piecal' ); ?>
		</p>
		<details>
			<summary>
				<?php /* translators: Quick start guide title */ ?>
				<?php esc_html_e( 'Quick Start Guide', 'piecal' ); ?>
			</summary>
			<ul>
				<li>
					<?php /* translators: Step 1 of the quick start guide */ ?>
					<strong><?php esc_html_e( 'Step 1:', 'piecal' ); ?></strong> <?php echo wp_kses( __( 'Edit any post, page, or custom post type and enable the <strong>Show on Calendar</strong> toggle.', 'piecal' ), array( 'strong' => array() ) ); ?>
				</li>
				<li>
					<?php /* translators: Step 2 of the quick start guide */ ?>
					<strong><?php esc_html_e( 'Step 2:', 'piecal' ); ?></strong> <?php esc_html_e( 'Set a start date and time.', 'piecal' ); ?>
				</li>
				<li>
					<?php /* translators: Step 3 of the quick start guide */ ?>
					<strong><?php esc_html_e( 'Step 3:', 'piecal' ); ?></strong> <?php echo wp_kses( __( 'Add the <code>[piecal]</code> shortcode wherever you want to display your calendar.', 'piecal' ), array( 'code' => array() ) ); ?>
				</li>
			</ul>
			<p>
				<?php /* translators: Encouragement to watch the get started video. %s: link to the video */ ?>
				<?php printf(
					wp_kses(
						__( "That's it! Check out %s to learn how get started in <strong>under 4 minutes.</strong>", 'piecal' ),
						array( 'strong' => array() )
					),
					'<a href="https://www.youtube.com/watch?v=ncdab1v_B1M">' . esc_html__( 'this video', 'piecal' ) . '</a>'
				); ?>
			</p>
			<p>
				<?php /* translators: Link to documentation. %s: link to documentation */ ?>
				<?php printf(
					esc_html__( 'Or %s to view our extensive documentation.', 'piecal' ),
					'<a href="https://docs.piecalendar.com/">' . esc_html__( 'click here', 'piecal' ) . '</a>'
				); ?>
			</p>
		</details>
		<p>
			<a href="<?php echo esc_url( wp_nonce_url( '?piecal-dismiss-notice=true', 'piecal_dismiss_notice' ) ); ?>">
				<?php /* translators: Dismiss notice link text */ ?>
				<?php esc_html_e( 'Dismiss this notice.', 'piecal' ); ?>
			</a>
		</p>
	</div>
	<?php
}

// Load our custom meta script for Gutenbergf
add_action(
	'enqueue_block_editor_assets',
	function () {
		if ( ! post_type_supports( get_post_type(), 'custom-fields' ) ) {
			return;
		}

		wp_enqueue_script(
			'piecalendar-custom-meta-plugin',
			PIECAL_PATH . '/build/index.js',
			array( 'wp-edit-post' ),
			PIECAL_VERSION,
			false
		);

		// Register piecalJS if not already registered. We need this in the block editor environment.
		if ( ! wp_script_is( 'piecalJS', 'registered' ) ) {
			wp_register_script( 'piecalJS', PIECAL_PATH . 'includes/js/piecal.js', array( 'wp-i18n' ), PIECAL_VERSION );
		}
				
		wp_enqueue_script('piecalJS');
	}
);

// Localize some information in Gutenberg for access in our custom meta script & blocks
function piecal_gutenberg_vars() {
	global $wp_scripts;
	$enqueued_scripts = array();

	if ($wp_scripts && !empty($wp_scripts->queue)) {
		foreach ($wp_scripts->queue as $handle) {
			$enqueued_scripts[] = $handle;
		}
	}

	require_once PIECAL_DIR . '/includes/utils/Strings.php';

	// Vars for both localization calls, primary and fallback.
	$vars = array(
		'isWooActive'              => is_plugin_active( 'woocommerce/woocommerce.php' ),
		'isEddActive'              => is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ),
		'explicitAllowedPostTypes' => apply_filters( 'piecal_explicit_allowed_post_types', array() ),
		'dateFormat'              => get_option( 'date_format' ),
		'timeFormat'              => get_option( 'time_format' ),
		'hidePiecalControls'      => apply_filters( 'piecal_hide_controls', false ),
		'strings'                 => Piecal\Utils\Strings::piecalStrings( 'piecal'),
	);

	wp_localize_script(
		'piecalendar-custom-meta-plugin',
		'piecalGbVars',
		$vars
	);

	// Fallback for when the custom meta script is not enqueued, but we still need these vars for the blocks.
	if( ! in_array( 'piecalendar-custom-meta-plugin', $enqueued_scripts ) ) {
		wp_localize_script(
			'piecal-calendar-editor-script',
			'piecalGbVars',
			$vars
		);
	}

	
}
add_action( 'enqueue_block_editor_assets', 'piecal_gutenberg_vars' );

// Add link for Pro on plugins page
function piecal_add_plugin_row_meta( $plugin_meta, $plugin_file ) {

	// If we are not on the correct plugin, abort.
	if ( 'pie-calendar/plugin.php' !== $plugin_file ) {
		return $plugin_meta;
	}

	$get_pro  = '<a href="https://piecalendar.com/?utm_campaign=upgrade&utm_source=plugin-page&utm_medium=upgrade-to-pro" aria-label="' . esc_attr( __( 'Navigate to the Pie Calendar website to purchase the Pro version.', 'piecal' ) ) . '" target="_blank" style="color: #D53637; font-weight: bold">';
	$get_pro .= __( 'Upgrade to Pro', 'piecal' );
	$get_pro .= '</a>';

	$row_meta = array(
		'get_pro' => apply_filters( 'piecal_get_pro_plugin_meta_link', $get_pro ),
	);

	$plugin_meta = array_merge( $plugin_meta, $row_meta );

	return $plugin_meta;
}
add_filter( 'plugin_row_meta', 'piecal_add_plugin_row_meta', 10, 2 );

/**
 * Load plugin textdomain.
 */
function piecal_load_textdomain() {
	load_plugin_textdomain( 'piecal', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

add_action( 'init', 'piecal_load_textdomain' );

/**
 * Set script translations
 */
function piecal_set_script_translations() {
	wp_set_script_translations( 'piecalJS', 'piecal', plugin_dir_path( __FILE__ ) . 'languages' );
}
add_action( 'wp_enqueue_scripts', 'piecal_set_script_translations', 20 );

/**
 * Get offset in seconds by a given date. This is used to detect DST and output the proper offset.
 */
function piecal_get_gmt_offset_by_date( $date ) {
	$piecalTZCheckStart = new DateTime( $date );
	$piecalTZ           = new DateTimeZone( wp_timezone_string() );
	$piecalTZOffset     = $piecalTZ->getOffset( $piecalTZCheckStart ) / 60 / 60;

	return $piecalTZOffset;
}
/**
 * GMT Offset Utility
 * WordPress's get_option('gmt_offset') doesn't have the proper +/-00:00 format, so we have to transform it here.
 */
function piecal_site_gmt_offset( $offset = null ) {
	// Get the gmt_offset option, or fallback to +00:00 if the option isn't set.
	$gmt_offset = null;

	if ( $offset !== null ) {
		$gmt_offset = $offset;
	} else {
		$gmt_offset = get_option( 'gmt_offset' ) ?? '+00:00';
	}

	// Early return for if the gmt_offset option is missing.
	if ( $gmt_offset === '+00:00' ) {
		return $gmt_offset;
	}

	// Get the GMT offset as an interval. This conveniently excludes any decimal values.
	$gmt_offset_int = intval( $gmt_offset );

	// Next, we get our GMT offset number only without any +/- or decimal values.
	$gmt_offset_number_only = abs( $gmt_offset_int );

	// GMT offsets in WordPress are returned as decimal representations, e.g. 5.5 = 05:30, so we have to get the decimal value alone here.
	// We subtract the $gmt_offset_int from $gmt_offset to get the remaining decimal value.
	$gmt_offset_decimal = $gmt_offset_int - $gmt_offset;

	// Finally, we convert the isolated decimal value to a representation of minutes, e.g. .5 becomes 30 and .75 becomes 45
	$gmt_offset_decimal_as_minutes = abs( $gmt_offset_decimal * 60 );

	// Here we determine whether to use a + or - symbol by checking the gmt_offset_int's value against 0.
	$gmt_offset_plus_or_minus = $gmt_offset_int > 0 ? '+' : '-';

	// Now we can combine all of the parts to get a properly formatted offset for use in setting our event times
	$gmt_final_offset = sprintf( '%s%02d:%02d', $gmt_offset_plus_or_minus, $gmt_offset_number_only, $gmt_offset_decimal_as_minutes );

	return $gmt_final_offset;
}

// Custom views API
require_once PIECAL_DIR . '/includes/utils/Views.php';
add_action( 'init', function() {
	Piecal\Utils\Views::addCustomViews( [], [] );
});