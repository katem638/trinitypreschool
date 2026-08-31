<?php
/**
 * Plugin Name: Trinity Site Functionality
 * Description: Site-owned event content, Pie Calendar integration, and dynamic event blocks for Trinity Episcopal Preschool.
 * Version: 1.0.0
 * Author: Trinity Episcopal Preschool
 * Text Domain: trinity-site-functionality
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TRINITY_SITE_FUNCTIONALITY_VERSION', '1.0.0' );

function trinity_site_register_event_content() {
	$labels = array(
		'name'                  => _x( 'Events', 'Post type general name', 'trinity-site-functionality' ),
		'singular_name'         => _x( 'Event', 'Post type singular name', 'trinity-site-functionality' ),
		'menu_name'             => _x( 'Events', 'Admin menu label', 'trinity-site-functionality' ),
		'name_admin_bar'        => _x( 'Event', 'Add new on toolbar', 'trinity-site-functionality' ),
		'add_new'               => __( 'Add New', 'trinity-site-functionality' ),
		'add_new_item'          => __( 'Add New Event', 'trinity-site-functionality' ),
		'new_item'              => __( 'New Event', 'trinity-site-functionality' ),
		'edit_item'             => __( 'Edit Event', 'trinity-site-functionality' ),
		'view_item'             => __( 'View Event', 'trinity-site-functionality' ),
		'all_items'             => __( 'All Events', 'trinity-site-functionality' ),
		'search_items'          => __( 'Search Events', 'trinity-site-functionality' ),
		'not_found'             => __( 'No events found.', 'trinity-site-functionality' ),
		'not_found_in_trash'    => __( 'No events found in Trash.', 'trinity-site-functionality' ),
		'featured_image'        => __( 'Event Image', 'trinity-site-functionality' ),
		'set_featured_image'    => __( 'Set event image', 'trinity-site-functionality' ),
		'remove_featured_image' => __( 'Remove event image', 'trinity-site-functionality' ),
		'use_featured_image'    => __( 'Use as event image', 'trinity-site-functionality' ),
	);

	register_post_type(
		'tp_event',
		array(
			'labels'        => $labels,
			'description'   => __( 'School calendar events displayed on the public events page.', 'trinity-site-functionality' ),
			'public'        => true,
			'has_archive'   => false,
			'menu_icon'     => 'dashicons-calendar-alt',
			'menu_position' => 21,
			'rewrite'       => array(
				'slug'       => 'events',
				'with_front' => false,
			),
			'show_in_rest'  => true,
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions' ),
		)
	);

	register_taxonomy(
		'tp_event_type',
		array( 'tp_event' ),
		array(
			'labels'            => array(
				'name'          => __( 'Event Types', 'trinity-site-functionality' ),
				'singular_name' => __( 'Event Type', 'trinity-site-functionality' ),
				'add_new_item'  => __( 'Add New Event Type', 'trinity-site-functionality' ),
				'edit_item'     => __( 'Edit Event Type', 'trinity-site-functionality' ),
			),
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'hierarchical'      => false,
			'rewrite'           => false,
		)
	);

	register_term_meta(
		'tp_event_type',
		'trinity_event_tone',
		array(
			'type'              => 'string',
			'single'            => true,
			'sanitize_callback' => 'trinity_site_sanitize_event_tone',
			'auth_callback'     => function () {
				return current_user_can( 'manage_categories' );
			},
			'show_in_rest'      => true,
			'default'           => 'navy',
		)
	);
}
add_action( 'init', 'trinity_site_register_event_content' );

/**
 * Let the Editor role maintain the designated Privacy Policy page without
 * granting access to WordPress's personal-data export and erasure tools.
 */
function trinity_site_allow_editor_privacy_page_updates( $required_caps, $requested_cap, $user_id, $args ) {
	if ( ! in_array( $requested_cap, array( 'edit_post', 'edit_page' ), true ) || empty( $args[0] ) ) {
		return $required_caps;
	}

	$privacy_page_id = (int) get_option( 'wp_page_for_privacy_policy' );
	if ( ! $privacy_page_id || $privacy_page_id !== (int) $args[0] || 'page' !== get_post_type( $privacy_page_id ) ) {
		return $required_caps;
	}

	return array( 'edit_pages' );
}
add_filter( 'map_meta_cap', 'trinity_site_allow_editor_privacy_page_updates', 10, 4 );

function trinity_site_sanitize_event_tone( $tone ) {
	$tone = sanitize_key( $tone );
	return in_array( $tone, array( 'coral', 'gold', 'sky', 'navy' ), true ) ? $tone : 'navy';
}

function trinity_site_event_type_add_tone_field() {
	?>
	<div class="form-field term-trinity-event-tone-wrap">
		<label for="trinity-event-tone"><?php esc_html_e( 'Card color', 'trinity-site-functionality' ); ?></label>
		<select name="trinity_event_tone" id="trinity-event-tone">
			<?php foreach ( array( 'navy' => 'Navy', 'coral' => 'Coral', 'gold' => 'Gold', 'sky' => 'Sky blue' ) as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<p><?php esc_html_e( 'Controls this event type’s card color in the weekly grid.', 'trinity-site-functionality' ); ?></p>
	</div>
	<?php
}
add_action( 'tp_event_type_add_form_fields', 'trinity_site_event_type_add_tone_field' );

function trinity_site_event_type_edit_tone_field( WP_Term $term ) {
	$current = trinity_site_sanitize_event_tone( get_term_meta( $term->term_id, 'trinity_event_tone', true ) );
	?>
	<tr class="form-field term-trinity-event-tone-wrap">
		<th scope="row"><label for="trinity-event-tone"><?php esc_html_e( 'Card color', 'trinity-site-functionality' ); ?></label></th>
		<td>
			<select name="trinity_event_tone" id="trinity-event-tone">
				<?php foreach ( array( 'navy' => 'Navy', 'coral' => 'Coral', 'gold' => 'Gold', 'sky' => 'Sky blue' ) as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<p class="description"><?php esc_html_e( 'Controls this event type’s card color in the weekly grid.', 'trinity-site-functionality' ); ?></p>
		</td>
	</tr>
	<?php
}
add_action( 'tp_event_type_edit_form_fields', 'trinity_site_event_type_edit_tone_field' );

function trinity_site_save_event_type_tone( $term_id ) {
	if ( isset( $_POST['trinity_event_tone'] ) && current_user_can( 'manage_categories' ) ) {
		update_term_meta( $term_id, 'trinity_event_tone', trinity_site_sanitize_event_tone( wp_unslash( $_POST['trinity_event_tone'] ) ) );
	}
}
add_action( 'created_tp_event_type', 'trinity_site_save_event_type_tone' );
add_action( 'edited_tp_event_type', 'trinity_site_save_event_type_tone' );

function trinity_site_register_blocks() {
	register_block_type( __DIR__ . '/blocks/weekly-events' );
}
add_action( 'init', 'trinity_site_register_blocks' );

function trinity_site_activate() {
	trinity_site_register_event_content();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'trinity_site_activate' );

function trinity_site_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'trinity_site_deactivate' );

function trinity_site_limit_pie_calendar_post_types() {
	return array( 'tp_event' );
}
add_filter( 'piecal_explicit_allowed_post_types', 'trinity_site_limit_pie_calendar_post_types' );

function trinity_site_empty_pie_calendar_popover_after_link() {
	return '';
}
add_filter( 'piecal_popover_after_view_link', 'trinity_site_empty_pie_calendar_popover_after_link' );

function trinity_site_pie_calendar_popover_link_text() {
	return esc_html__( 'View Event', 'trinity-site-functionality' );
}
add_filter( 'piecal_popover_link_text', 'trinity_site_pie_calendar_popover_link_text' );

function trinity_site_pie_calendar_notice() {
	if ( ! current_user_can( 'activate_plugins' ) || defined( 'PIECAL_VERSION' ) || function_exists( 'piecal_get_event' ) ) {
		return;
	}

	echo '<div class="notice notice-warning"><p>' . esc_html__( 'Trinity Events remains available, but calendar dates and the public calendar require the Pie Calendar plugin to be active.', 'trinity-site-functionality' ) . '</p></div>';
}
add_action( 'admin_notices', 'trinity_site_pie_calendar_notice' );

function trinity_site_get_week_window() {
	$timezone = wp_timezone();
	$today    = new DateTimeImmutable( 'today', $timezone );
	$monday   = $today->modify( 'monday this week' );
	$days     = max( 1, (int) apply_filters( 'trinity_site_week_days', 6 ) );

	return array(
		'start' => $monday->setTime( 0, 0, 0 ),
		'end'   => $monday->modify( '+' . ( $days - 1 ) . ' days' )->setTime( 23, 59, 59 ),
		'days'  => $days,
	);
}

function trinity_site_parse_piecal_datetime( $value ) {
	if ( empty( $value ) ) {
		return null;
	}

	try {
		return new DateTimeImmutable( $value, wp_timezone() );
	} catch ( Exception $exception ) {
		return null;
	}
}

function trinity_site_get_week_events( DateTimeImmutable $week_start, DateTimeImmutable $week_end ) {
	$query = new WP_Query(
		array(
			'post_type'           => 'tp_event',
			'post_status'         => 'publish',
			'posts_per_page'      => 50,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'meta_key'            => '_piecal_start_date',
			'orderby'             => 'meta_value',
			'order'               => 'ASC',
			'meta_query'          => array(
				'relation' => 'AND',
				array(
					'key'   => '_piecal_is_event',
					'value' => '1',
				),
				array(
					'key'     => '_piecal_start_date',
					'value'   => '',
					'compare' => '!=',
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => '_piecal_start_date',
						'value'   => array( $week_start->format( 'Y-m-d\TH:i:s' ), $week_end->format( 'Y-m-d\TH:i:s' ) ),
						'compare' => 'BETWEEN',
						'type'    => 'CHAR',
					),
					array(
						'relation' => 'AND',
						array(
							'key'     => '_piecal_start_date',
							'value'   => $week_end->format( 'Y-m-d\TH:i:s' ),
							'compare' => '<=',
							'type'    => 'CHAR',
						),
						array(
							'key'     => '_piecal_end_date',
							'value'   => $week_start->format( 'Y-m-d\TH:i:s' ),
							'compare' => '>=',
							'type'    => 'CHAR',
						),
					),
				),
			),
		)
	);

	$events = array();

	foreach ( $query->posts as $event_post ) {
		$start = trinity_site_parse_piecal_datetime( get_post_meta( $event_post->ID, '_piecal_start_date', true ) );
		if ( ! $start ) {
			continue;
		}

		$end = trinity_site_parse_piecal_datetime( get_post_meta( $event_post->ID, '_piecal_end_date', true ) );
		if ( ! $end || $end < $start ) {
			$end = $start;
		}

		$events[] = array(
			'post'    => $event_post,
			'start'   => $start,
			'end'     => $end,
			'all_day' => rest_sanitize_boolean( get_post_meta( $event_post->ID, '_piecal_is_allday', true ) ),
		);
	}

	return $events;
}

function trinity_site_event_overlaps_day( array $event, DateTimeImmutable $day ) {
	return $event['start'] <= $day->setTime( 23, 59, 59 ) && $event['end'] >= $day->setTime( 0, 0, 0 );
}

function trinity_site_format_week_range( DateTimeImmutable $week_start, DateTimeImmutable $week_end ) {
	if ( $week_start->format( 'Y-m' ) === $week_end->format( 'Y-m' ) ) {
		return sprintf(
			'%s %s–%s',
			wp_date( 'M', $week_start->getTimestamp(), wp_timezone() ),
			wp_date( 'j', $week_start->getTimestamp(), wp_timezone() ),
			wp_date( 'j', $week_end->getTimestamp(), wp_timezone() )
		);
	}

	if ( $week_start->format( 'Y' ) === $week_end->format( 'Y' ) ) {
		return sprintf(
			'%s %s–%s %s',
			wp_date( 'M', $week_start->getTimestamp(), wp_timezone() ),
			wp_date( 'j', $week_start->getTimestamp(), wp_timezone() ),
			wp_date( 'M', $week_end->getTimestamp(), wp_timezone() ),
			wp_date( 'j', $week_end->getTimestamp(), wp_timezone() )
		);
	}

	return sprintf(
		'%s–%s',
		wp_date( 'M j, Y', $week_start->getTimestamp(), wp_timezone() ),
		wp_date( 'M j, Y', $week_end->getTimestamp(), wp_timezone() )
	);
}

function trinity_site_format_event_time( array $event ) {
	if ( $event['all_day'] ) {
		return __( 'All day', 'trinity-site-functionality' );
	}

	$start = wp_date( 'g:i A', $event['start']->getTimestamp(), wp_timezone() );
	if ( $event['end'] > $event['start'] && $event['start']->format( 'Y-m-d' ) === $event['end']->format( 'Y-m-d' ) ) {
		return sprintf( '%s – %s', $start, wp_date( 'g:i A', $event['end']->getTimestamp(), wp_timezone() ) );
	}

	return $start;
}

function trinity_site_get_event_presentation( WP_Post $event_post, $day_index ) {
	$terms = get_the_terms( $event_post, 'tp_event_type' );
	$term  = is_array( $terms ) && $terms ? reset( $terms ) : null;
	$tones = array( 'coral', 'gold', 'sky', 'navy' );
	$tone  = $term ? sanitize_key( get_term_meta( $term->term_id, 'trinity_event_tone', true ) ) : '';

	return array(
		'label' => $term ? $term->name : __( 'Event', 'trinity-site-functionality' ),
		'tone'  => in_array( $tone, $tones, true ) ? $tone : $tones[ $day_index % count( $tones ) ],
	);
}

function trinity_site_render_week_event( array $event ) {
	$excerpt = has_excerpt( $event['post'] ) ? get_the_excerpt( $event['post'] ) : wp_trim_words( wp_strip_all_tags( $event['post']->post_content ), 9, '' );

	ob_start();
	?>
	<article class="tp-week-event">
		<h3 class="tp-week-event-title"><a href="<?php echo esc_url( get_permalink( $event['post'] ) ); ?>"><?php echo esc_html( get_the_title( $event['post'] ) ); ?></a></h3>
		<?php if ( $excerpt ) : ?>
			<p class="tp-week-event-summary"><?php echo esc_html( $excerpt ); ?></p>
		<?php endif; ?>
		<p class="tp-week-event-time"><?php echo esc_html( trinity_site_format_event_time( $event ) ); ?></p>
	</article>
	<?php
	return ob_get_clean();
}

function trinity_site_render_weekly_events_grid() {
	$week       = trinity_site_get_week_window();
	$events     = trinity_site_get_week_events( $week['start'], $week['end'] );
	$day_cursor = $week['start'];
	$wrapper    = get_block_wrapper_attributes( array( 'class' => 'tp-week-events-grid-block' ) );

	ob_start();
	?>
	<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-week-start="<?php echo esc_attr( $week['start']->format( 'Y-m-d' ) ); ?>" data-week-end="<?php echo esc_attr( $week['end']->format( 'Y-m-d' ) ); ?>">
		<p class="tp-week-current-range"><?php echo esc_html( trinity_site_format_week_range( $week['start'], $week['end'] ) ); ?></p>
		<div class="tp-week-grid">
			<?php for ( $day_index = 0; $day_index < $week['days']; $day_index++ ) : ?>
				<?php
				$day_events  = array_values(
					array_filter(
						$events,
						function ( $event ) use ( $day_cursor ) {
							return trinity_site_event_overlaps_day( $event, $day_cursor );
						}
					)
				);
				$first_event = $day_events[0] ?? null;
				$presentation = $first_event ? trinity_site_get_event_presentation( $first_event['post'], $day_index ) : array( 'label' => '', 'tone' => 'navy' );
				?>
				<div class="tp-week-day-card tp-week-day-card--<?php echo esc_attr( $presentation['tone'] ); ?><?php echo empty( $day_events ) ? ' is-empty' : ''; ?>">
					<div class="tp-week-day-heading">
						<div>
							<p class="tp-week-day-name"><?php echo esc_html( wp_date( 'D', $day_cursor->getTimestamp(), wp_timezone() ) ); ?></p>
							<p class="tp-week-day-date"><?php echo esc_html( wp_date( 'M j', $day_cursor->getTimestamp(), wp_timezone() ) ); ?></p>
						</div>
						<?php if ( $presentation['label'] ) : ?>
							<span class="tp-week-day-label"><?php echo esc_html( $presentation['label'] ); ?></span>
						<?php endif; ?>
					</div>
					<?php if ( $day_events ) : ?>
						<div class="tp-week-day-events">
							<?php foreach ( $day_events as $event ) : ?>
								<?php echo trinity_site_render_week_event( $event ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<p class="tp-week-empty"><?php esc_html_e( 'No events scheduled', 'trinity-site-functionality' ); ?></p>
					<?php endif; ?>
				</div>
				<?php $day_cursor = $day_cursor->modify( '+1 day' ); ?>
			<?php endfor; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
