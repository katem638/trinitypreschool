<?php
/**
 * All functionality related to the custom block.
 */


/**
 * Register block type.
 *
 * @return void
 */
function piecal_register_block_type() {

	$block_path = PIECAL_DIR . 'build/blocks/calendar/block.json';
	register_block_type( $block_path );

	$block_path = PIECAL_DIR . 'build/blocks/event-info/block.json';
	register_block_type( $block_path );

	wp_register_style( 'piecal-block-inline-styles', false );
}
add_action( 'init', 'piecal_register_block_type' );

/**
 * Remove any default atts if they are empty.
 *
 * @param array $atts The calendar block attributes.
 * @return array
 */
function piecal_calendar_block_atts_filter( $atts, $attributes ) {
	if( empty( $atts ) ) {
		return $atts;
	};

	foreach ( $atts as $key => $value ) {
		if ( empty( $value ) ) {
			unset( $atts[ $key ] );
		}
	}
	return $atts;
}
add_filter( 'piecal_calendar_block_atts', 'piecal_calendar_block_atts_filter', 10, 2 );


/**
 * Remove any default atts if they are empty.
 *
 * @param array $atts The calendar block attributes.
 * @return array
 */
function piecal_info_block_atts_filter( $atts ) {
	foreach ( $atts as $key => $value ) {
		if ( empty( $value ) ) {
			unset( $atts[ $key ] );
		}
	}
	return $atts;
}
add_filter( 'piecal_info_block_atts', 'piecal_info_block_atts_filter' );


/**
 * Register the REST endpoints.
 *
 * @return void
 */
function piecal_register_rest_endpoints() {
	register_rest_route(
		'piecal/v1',
		'/events',
		array(
			'methods'             => 'GET',
			'callback'            => 'piecal_get_events',
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);

	register_rest_route(
		'piecal/v1',
		'/views',
		array(
			'methods'             => 'GET',
			'callback'            => 'piecal_get_views',
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);

	register_rest_route(
		'piecal/v1',
		'/views_array',
		array(
			'methods'             => 'GET',
			'callback'            => 'piecal_get_views_array',
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}
add_action( 'rest_api_init', 'piecal_register_rest_endpoints' );


/**
 * Get the events from the database.
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response
 */
function piecal_get_events( $request ) {

	// Check if request is from block editor
	$referer = isset($_SERVER['HTTP_REFERER']) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
	if (!$referer ||
        (strpos($referer, '/wp-admin/post.php') === false &&
         strpos($referer, '/wp-admin/post-new.php') === false &&
		 strpos($referer, '/wp-admin/site-editor.php') === false)) {
        return new WP_Error('unauthorized', 'This endpoint is only available within the block editor', array('status' => 403));
    }

	$atts = $request->get_params();

	$args = array(
		'post_type'      => isset($atts['allAttributes']['type']) ? 
			array_map('sanitize_key', (array)$atts['allAttributes']['type']) : 
			'any',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'no_found_rows'  => true,
		'meta_query'     => array(
			'relation' => 'AND',
			array(
				'key'   => '_piecal_is_event',
				'value' => '1',
			),
			array(
				'key'     => '_piecal_start_date',
				'value'   => '',
				'compare' => 'NOT IN',
			),
		),
	);

	// This should probably be fixed upstream in piecal-pro.php.
	if ( isset( $atts['allAttributes']['taxonomy'] ) && '' === $atts['allAttributes']['taxonomy'] ) {
		unset( $atts['allAttributes']['taxonomy'] );
		unset( $atts['allAttributes']['terms'] );
		unset( $atts['allAttributes']['operator'] );
	}

	$events = new WP_Query( apply_filters( 'piecal_event_query_args', $args, $atts['allAttributes'] ) );
	$events = array_map(
		function ( $event ) {

			global $post;
			$post = $event; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			setup_postdata( $post );

			$event = array(
				// 'id'     => $event->ID,
				'postId' => $event->ID,
				'title'  => $event->post_title,
				'start'  => get_post_meta( $event->ID, '_piecal_start_date', true ),
				'end'    => get_post_meta( $event->ID, '_piecal_end_date', true ),
				'allDay' => get_post_meta(get_the_ID(), '_piecal_is_allday') ? get_post_meta(get_the_ID(), '_piecal_is_allday', true) : "false",
				"details" => str_replace("&amp;", "&", htmlentities(get_the_excerpt(), ENT_QUOTES) ),
                "permalink" => get_permalink(),
			);

			$event = apply_filters( 'piecal_event_array_filter', $event );

			wp_reset_postdata();

			return $event;
		},
		$events->posts
	);

	$events = apply_filters( 'piecal_events_array_filter', $events, null, null, ( ! isset( $atts['allAttributes']['adaptivetimezone'] ) && apply_filters( 'piecal_use_adaptive_timezones', false ) ) );

	$eventSources = [
        $events
    ];

	foreach( $atts['allAttributes']['sources'] as $key => $value ) {
		if( !$value || $value == 'false' ) {
			unset( $atts['allAttributes']['sources'][$key] );
		}	
	}

	$atts['allAttributes']['sources'] = array_keys( $atts['allAttributes']['sources'] );

    $eventSources = apply_filters('piecal_event_sources', $eventSources, null, null, ( ! isset( $atts['allAttributes']['adaptivetimezone'] ) && apply_filters( 'piecal_use_adaptive_timezones', false ) ), $atts['allAttributes']);

	return rest_ensure_response( $eventSources );
}

function piecal_get_views( $request ) {
	require_once PIECAL_DIR . '/includes/utils/Views.php';

	$customViews = [
        "listUpcoming" => [
            "type" => "listMonth",
            "duration" => [
                "months" => isset( $atts['duration'] ) ? intval( $atts['duration'] ) : 2
            ],
            "customProps" => [
                /* Translators: String for Upcoming view in view picker dropdown. */
                "niceName" => __( 'List - Upcoming', 'piecal' )
            ]
        ]
    ];

    $customViews = apply_filters( 'piecal_custom_views', $customViews, [] );
    
    $allowedViews = ['dayGridMonth', 'listMonth', 'timeGridWeek', 'listWeek', 'dayGridWeek', 'listDay'];
    $allowedViews = apply_filters('piecal_allowed_views', $allowedViews);
    
    $viewLabels = [
        /* Translators: String for Month - Classic view in view picker dropdown. */
        "dayGridMonth" => __( 'Month - Classic', 'piecal' ),
        /* Translators: String for Month - List view in view picker dropdown. */
        "listMonth" => __( 'Month - List', 'piecal' ),
        /* Translators: String for Week - Time Grid view in view picker dropdown. */
        "timeGridWeek" => __( 'Week - Time Grid', 'piecal' ),
        /* Translators: String for Week - List view in view picker dropdown. */
        "listWeek" => __( 'Week - List', 'piecal' ),
        /* Translators: String for Week - Day Grid view in view picker dropdown. */
        "dayGridWeek" => __( 'Week - Classic', 'piecal' ),
        /* Translators: String for Day - List view in view picker dropdown. */
        "listDay" => __( 'Day - List', 'piecal' )
    ];
    $viewLabels = apply_filters('piecal_view_labels', $viewLabels);

	$reformattedViewLabels = [];

	foreach ($viewLabels as $view => $label) {
		$reformattedViewLabels[] = [
			'label' => $label,
			'value' => $view
		];
	}

	array_unshift($reformattedViewLabels, [
		'label' => __("Default", "piecal"),
		'value' => ""
	]);

	return rest_ensure_response( $reformattedViewLabels );
}

function piecal_get_views_array() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a read-only operation for public-facing content
	$get_duration = isset( $_GET['duration'] ) ? intval( sanitize_text_field( wp_unslash( $_GET['duration'] ) ) ) : 2;
	$customViews = [
        "listUpcoming" => [
            "type" => "listMonth",
            "duration" => [
                "months" => $get_duration
            ],
            "customProps" => [
                /* Translators: String for Upcoming view in view picker dropdown. */
                "niceName" => __( 'List - Upcoming', 'piecal' )
            ]
        ]
    ];

	$atts['duration'] = $get_duration;
	$atts['duration'] = $atts['duration'] > 24 ? 24 : $atts['duration'];
	$atts['duration'] = $atts['duration'] < 1 ? 1 : $atts['duration'];

	$atts['duration'] = apply_filters( 'piecal_override_view_duration', $atts['duration'] );

	$customViews = apply_filters( 'piecal_custom_views', $customViews, $atts );

	return rest_ensure_response( $customViews );
}
