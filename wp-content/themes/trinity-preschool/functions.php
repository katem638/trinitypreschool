<?php
/**
 * Trinity Preschool theme setup.
 */

if ( ! function_exists( 'trinity_preschool_setup' ) ) {
	function trinity_preschool_setup() {
		add_editor_style( 'style.css' );
	}
}
add_action( 'after_setup_theme', 'trinity_preschool_setup' );

if ( ! function_exists( 'trinity_preschool_register_block_patterns' ) ) {
	function trinity_preschool_register_block_patterns() {
		$extended_day_pattern = get_theme_file_path( '/patterns/extended-day-program.php' );

		if ( file_exists( $extended_day_pattern ) ) {
			ob_start();
			include $extended_day_pattern;
			$extended_day_content = ob_get_clean();

			register_block_pattern(
				'trinity-preschool/extended-day-program',
				array(
					'title'      => __( 'Extended Day Program', 'trinity-preschool' ),
					'categories' => array( 'trinity-preschool' ),
					'inserter'   => false,
					'content'    => $extended_day_content,
				)
			);
		}
	}
}
add_action( 'init', 'trinity_preschool_register_block_patterns' );

if ( ! function_exists( 'trinity_preschool_register_event_post_type' ) ) {
	function trinity_preschool_register_event_post_type() {
		$labels = array(
			'name'                  => _x( 'Events', 'Post type general name', 'trinity-preschool' ),
			'singular_name'         => _x( 'Event', 'Post type singular name', 'trinity-preschool' ),
			'menu_name'             => _x( 'Events', 'Admin menu label', 'trinity-preschool' ),
			'name_admin_bar'        => _x( 'Event', 'Add new on toolbar', 'trinity-preschool' ),
			'add_new'               => __( 'Add New', 'trinity-preschool' ),
			'add_new_item'          => __( 'Add New Event', 'trinity-preschool' ),
			'new_item'              => __( 'New Event', 'trinity-preschool' ),
			'edit_item'             => __( 'Edit Event', 'trinity-preschool' ),
			'view_item'             => __( 'View Event', 'trinity-preschool' ),
			'all_items'             => __( 'All Events', 'trinity-preschool' ),
			'search_items'          => __( 'Search Events', 'trinity-preschool' ),
			'not_found'             => __( 'No events found.', 'trinity-preschool' ),
			'not_found_in_trash'    => __( 'No events found in Trash.', 'trinity-preschool' ),
			'featured_image'        => __( 'Event Image', 'trinity-preschool' ),
			'set_featured_image'    => __( 'Set event image', 'trinity-preschool' ),
			'remove_featured_image' => __( 'Remove event image', 'trinity-preschool' ),
			'use_featured_image'    => __( 'Use as event image', 'trinity-preschool' ),
		);

		register_post_type(
			'tp_event',
			array(
				'labels'       => $labels,
				'description'  => __( 'School calendar events displayed on the public events page.', 'trinity-preschool' ),
				'public'       => true,
				'has_archive'  => false,
				'menu_icon'    => 'dashicons-calendar-alt',
				'menu_position' => 21,
				'rewrite'      => array(
					'slug'       => 'events',
					'with_front' => false,
				),
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions' ),
			)
		);
	}
}
add_action( 'init', 'trinity_preschool_register_event_post_type' );

if ( ! function_exists( 'trinity_preschool_limit_pie_calendar_post_types' ) ) {
	function trinity_preschool_limit_pie_calendar_post_types( $post_types ) {
		return array( 'tp_event' );
	}
}
add_filter( 'piecal_explicit_allowed_post_types', 'trinity_preschool_limit_pie_calendar_post_types' );

if ( ! function_exists( 'trinity_preschool_empty_pie_calendar_popover_after_link' ) ) {
	function trinity_preschool_empty_pie_calendar_popover_after_link() {
		return '';
	}
}
add_filter( 'piecal_popover_after_view_link', 'trinity_preschool_empty_pie_calendar_popover_after_link' );

if ( ! function_exists( 'trinity_preschool_pie_calendar_popover_link_text' ) ) {
	function trinity_preschool_pie_calendar_popover_link_text() {
		return esc_html__( 'View Event', 'trinity-preschool' );
	}
}
add_filter( 'piecal_popover_link_text', 'trinity_preschool_pie_calendar_popover_link_text' );

if ( ! function_exists( 'trinity_preschool_enqueue_styles' ) ) {
	function trinity_preschool_enqueue_styles() {
		$stylesheet_path = get_stylesheet_directory() . '/style.css';

		wp_enqueue_style(
			'trinity-preschool-fonts',
			'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&family=Nunito+Sans:wght@400;500;600;700;800&display=swap',
			array(),
			null
		);

		wp_enqueue_style(
			'trinity-preschool-style',
			get_stylesheet_uri(),
			array( 'trinity-preschool-fonts' ),
			file_exists( $stylesheet_path ) ? filemtime( $stylesheet_path ) : wp_get_theme()->get( 'Version' )
		);

		if ( is_page( 'schedule-a-tour' ) ) {
			wp_register_script(
				'trinity-preschool-tour-form',
				false,
				array(),
				file_exists( $stylesheet_path ) ? filemtime( $stylesheet_path ) : wp_get_theme()->get( 'Version' ),
				true
			);
			wp_enqueue_script( 'trinity-preschool-tour-form' );
			wp_add_inline_script(
				'trinity-preschool-tour-form',
				<<<'JS'
document.addEventListener('DOMContentLoaded', function () {
	document.querySelectorAll('.tp-tour-add-sibling').forEach(function (button) {
		var form = button.closest('form');
		var siblingFields = form ? form.querySelector('#tp-tour-sibling-fields') : null;

		if (!siblingFields) {
			return;
		}

		var controls = siblingFields.querySelectorAll('input, select, textarea');
		var setExpanded = function (expanded) {
			siblingFields.hidden = !expanded;
			button.classList.toggle('is-expanded', expanded);
			button.setAttribute('aria-expanded', expanded ? 'true' : 'false');

			controls.forEach(function (control) {
				control.disabled = !expanded;
			});
		};

		setExpanded(false);

		button.addEventListener('click', function () {
			setExpanded(siblingFields.hidden);

			if (!siblingFields.hidden) {
				var firstField = siblingFields.querySelector('input, select, textarea');

				if (firstField) {
					firstField.focus();
				}
			}
		});
	});
});
JS
			);
		}

	}
}
add_action( 'wp_enqueue_scripts', 'trinity_preschool_enqueue_styles' );

if ( ! function_exists( 'trinity_preschool_disable_cf7_form_autop' ) ) {
	function trinity_preschool_disable_cf7_form_autop( $autop, $options ) {
		if ( isset( $options['for'] ) && 'form' === $options['for'] ) {
			return false;
		}

		return $autop;
	}
}
add_filter( 'wpcf7_autop_or_not', 'trinity_preschool_disable_cf7_form_autop', 10, 2 );

if ( ! function_exists( 'trinity_preschool_register_weekly_events_block' ) ) {
	function trinity_preschool_register_weekly_events_block() {
		register_block_type(
			'trinity-preschool/weekly-events',
			array(
				'render_callback' => 'trinity_preschool_render_weekly_events_block',
			)
		);
	}
}
add_action( 'init', 'trinity_preschool_register_weekly_events_block' );

if ( ! function_exists( 'trinity_preschool_get_week_window' ) ) {
	function trinity_preschool_get_week_window() {
		$timezone = wp_timezone();
		$today    = new DateTimeImmutable( 'today', $timezone );
		$weekday  = (int) $today->format( 'N' );

		$monday = 7 === $weekday ? $today->modify( 'next monday' ) : $today->modify( 'monday this week' );

		return array(
			'start' => $monday->setTime( 0, 0, 0 ),
			'end'   => $monday->modify( '+5 days' )->setTime( 23, 59, 59 ),
		);
	}
}

if ( ! function_exists( 'trinity_preschool_parse_piecal_datetime' ) ) {
	function trinity_preschool_parse_piecal_datetime( $value ) {
		if ( empty( $value ) ) {
			return null;
		}

		try {
			return new DateTimeImmutable( $value, wp_timezone() );
		} catch ( Exception $exception ) {
			return null;
		}
	}
}

if ( ! function_exists( 'trinity_preschool_get_week_events' ) ) {
	function trinity_preschool_get_week_events( DateTimeImmutable $week_start, DateTimeImmutable $week_end ) {
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
							'value'   => array(
								$week_start->format( 'Y-m-d\TH:i:s' ),
								$week_end->format( 'Y-m-d\TH:i:s' ),
							),
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
			$start = trinity_preschool_parse_piecal_datetime( get_post_meta( $event_post->ID, '_piecal_start_date', true ) );

			if ( ! $start ) {
				continue;
			}

			$end = trinity_preschool_parse_piecal_datetime( get_post_meta( $event_post->ID, '_piecal_end_date', true ) );

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
}

if ( ! function_exists( 'trinity_preschool_event_overlaps_day' ) ) {
	function trinity_preschool_event_overlaps_day( array $event, DateTimeImmutable $day ) {
		$day_start = $day->setTime( 0, 0, 0 );
		$day_end   = $day->setTime( 23, 59, 59 );

		return $event['start'] <= $day_end && $event['end'] >= $day_start;
	}
}

if ( ! function_exists( 'trinity_preschool_format_week_range' ) ) {
	function trinity_preschool_format_week_range( DateTimeImmutable $week_start, DateTimeImmutable $week_end ) {
		if ( $week_start->format( 'Y' ) === $week_end->format( 'Y' ) && $week_start->format( 'm' ) === $week_end->format( 'm' ) ) {
			return sprintf(
				'%s %s-%s',
				wp_date( 'M', $week_start->getTimestamp(), wp_timezone() ),
				wp_date( 'j', $week_start->getTimestamp(), wp_timezone() ),
				wp_date( 'j', $week_end->getTimestamp(), wp_timezone() )
			);
		}

		if ( $week_start->format( 'Y' ) === $week_end->format( 'Y' ) ) {
			return sprintf(
				'%s %s-%s %s',
				wp_date( 'M', $week_start->getTimestamp(), wp_timezone() ),
				wp_date( 'j', $week_start->getTimestamp(), wp_timezone() ),
				wp_date( 'M', $week_end->getTimestamp(), wp_timezone() ),
				wp_date( 'j', $week_end->getTimestamp(), wp_timezone() )
			);
		}

		return sprintf(
			'%s-%s',
			wp_date( 'M j, Y', $week_start->getTimestamp(), wp_timezone() ),
			wp_date( 'M j, Y', $week_end->getTimestamp(), wp_timezone() )
		);
	}
}

if ( ! function_exists( 'trinity_preschool_format_event_time' ) ) {
	function trinity_preschool_format_event_time( array $event ) {
		if ( $event['all_day'] ) {
			return __( 'All day', 'trinity-preschool' );
		}

		$start = wp_date( 'g:i A', $event['start']->getTimestamp(), wp_timezone() );

		if ( $event['end'] > $event['start'] && $event['start']->format( 'Y-m-d' ) === $event['end']->format( 'Y-m-d' ) ) {
			return sprintf(
				'%s - %s',
				$start,
				wp_date( 'g:i A', $event['end']->getTimestamp(), wp_timezone() )
			);
		}

		return $start;
	}
}

if ( ! function_exists( 'trinity_preschool_get_event_label' ) ) {
	function trinity_preschool_get_event_label( array $event ) {
		$label = get_post_meta( $event['post']->ID, 'tp_event_label', true );

		if ( $label ) {
			return $label;
		}

		return $event['all_day'] ? __( 'All School', 'trinity-preschool' ) : __( 'Event', 'trinity-preschool' );
	}
}

if ( ! function_exists( 'trinity_preschool_get_event_tone' ) ) {
	function trinity_preschool_get_event_tone( array $event, $day_index ) {
		$tone    = sanitize_key( get_post_meta( $event['post']->ID, 'tp_event_tone', true ) );
		$allowed = array( 'coral', 'gold', 'sky', 'navy' );

		if ( in_array( $tone, $allowed, true ) ) {
			return $tone;
		}

		return $allowed[ $day_index % count( $allowed ) ];
	}
}

if ( ! function_exists( 'trinity_preschool_render_week_event' ) ) {
	function trinity_preschool_render_week_event( array $event ) {
		$excerpt = has_excerpt( $event['post'] ) ? get_the_excerpt( $event['post'] ) : wp_trim_words( wp_strip_all_tags( $event['post']->post_content ), 9, '' );

		ob_start();
		?>
		<article class="tp-week-event">
			<h3 class="tp-week-event-title"><a href="<?php echo esc_url( get_permalink( $event['post'] ) ); ?>"><?php echo esc_html( get_the_title( $event['post'] ) ); ?></a></h3>
			<?php if ( $excerpt ) : ?>
				<p class="tp-week-event-summary"><?php echo esc_html( $excerpt ); ?></p>
			<?php endif; ?>
			<p class="tp-week-event-time"><?php echo esc_html( trinity_preschool_format_event_time( $event ) ); ?></p>
		</article>
		<?php
		return ob_get_clean();
	}
}

if ( ! function_exists( 'trinity_preschool_render_weekly_events_block' ) ) {
	function trinity_preschool_render_weekly_events_block() {
		$week      = trinity_preschool_get_week_window();
		$events    = trinity_preschool_get_week_events( $week['start'], $week['end'] );
		$days      = array();
		$day_cursor = $week['start'];

		for ( $index = 0; $index < 6; $index++ ) {
			$day_events = array_values(
				array_filter(
					$events,
					function ( $event ) use ( $day_cursor ) {
						return trinity_preschool_event_overlaps_day( $event, $day_cursor );
					}
				)
			);

			$days[] = array(
				'date'   => $day_cursor,
				'events' => $day_events,
			);

			$day_cursor = $day_cursor->modify( '+1 day' );
		}

		ob_start();
		?>
		<section class="tp-week-events alignfull" aria-labelledby="tp-week-events-heading">
			<div class="tp-week-events-inner alignwide">
				<div class="tp-week-events-header">
					<div class="tp-week-events-title">
						<p class="tp-section-eyebrow">This week at Trinity</p>
						<h2 id="tp-week-events-heading">What&apos;s <em>happening</em><br><?php echo esc_html( trinity_preschool_format_week_range( $week['start'], $week['end'] ) ); ?>.</h2>
					</div>
					<div class="wp-block-buttons tp-week-events-actions">
						<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/events/' ) ); ?>"><?php esc_html_e( 'Full calendar', 'trinity-preschool' ); ?></a></div>
					</div>
				</div>

				<div class="tp-week-grid">
					<?php foreach ( $days as $day_index => $day ) : ?>
						<?php
						$first_event = $day['events'][0] ?? null;
						$tone        = $first_event ? trinity_preschool_get_event_tone( $first_event, $day_index ) : 'navy';
						?>
						<div class="tp-week-day-card tp-week-day-card--<?php echo esc_attr( $tone ); ?><?php echo empty( $day['events'] ) ? ' is-empty' : ''; ?>">
							<div class="tp-week-day-heading">
								<div>
									<p class="tp-week-day-name"><?php echo esc_html( wp_date( 'D', $day['date']->getTimestamp(), wp_timezone() ) ); ?></p>
									<p class="tp-week-day-date"><?php echo esc_html( wp_date( 'M j', $day['date']->getTimestamp(), wp_timezone() ) ); ?></p>
								</div>
								<?php if ( $first_event ) : ?>
									<span class="tp-week-day-label"><?php echo esc_html( trinity_preschool_get_event_label( $first_event ) ); ?></span>
								<?php endif; ?>
							</div>

							<?php if ( $day['events'] ) : ?>
								<div class="tp-week-day-events">
									<?php foreach ( $day['events'] as $event ) : ?>
										<?php echo trinity_preschool_render_week_event( $event ); ?>
									<?php endforeach; ?>
								</div>
							<?php else : ?>
								<p class="tp-week-empty"><?php esc_html_e( 'No events scheduled', 'trinity-preschool' ); ?></p>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}
}
