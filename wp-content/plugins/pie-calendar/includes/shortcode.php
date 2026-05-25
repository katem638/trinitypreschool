<?php

require_once PIECAL_DIR . '/includes/utils/Scripts.php';
require_once PIECAL_DIR . '/includes/utils/Views.php';

add_shortcode( 'piecal', 'piecal_render_calendar' );

if ( ! function_exists( 'piecal_render_calendar' ) ) {

	function piecal_render_calendar( $atts ) {
		do_action( 'piecal_render_calendar', $atts );
        do_action( 'piecal_inline_styles' );

        include_once PIECAL_DIR . '/includes/utils/General.php';

		Piecal\Utils\Scripts::loadCoreScriptsAndStyles();

		$atts = apply_filters( 'piecal_shortcode_atts', $atts );

		$theme = $atts['theme'] ?? false;

		if ( $theme && $theme == 'dark' ) {
			Piecal\Utils\Scripts::enqueueBundle( array( 'piecalThemeDarkCSS' ) );
		}

		if ( $theme && $theme == 'adaptive' ) {
			Piecal\Utils\Scripts::enqueueBundle( array( 'piecalThemeDarkCSSAdaptive' ) );
		}

		// Conditional loading of locales
		$locale = $atts['locale'] ?? get_bloginfo('language');

		if( $locale != 'en-US' )
			Piecal\Utils\Scripts::enqueueBundle( ['fullcalendar-locales'] );

        // Support multiple post types
        $types = 'any';
    
        if (isset($atts['type'])) {
            $types = is_array($atts['type']) ? $atts['type'] : explode(',', $atts['type']);
            $types = array_map('trim', $types);
            $atts['type'] = $types;
        }
        
        $args = [
            'post_type'     => $types,
            'post_status'   => 'publish',
            'posts_per_page' => -1,
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'      => '_piecal_is_event',
                    'value'    => '1',
                ],
                [
                    'key'      => '_piecal_start_date',
                    'value'    => '',
                    'compare'  => 'NOT IN'
                ]
            ],
        ];

        $events = new WP_Query( apply_filters('piecal_event_query_args', $args, $atts ) );
        $eventsArray = [];

        // Append GMT offset
        $appendOffset = false;

        if( !isset( $atts['adaptivetimezone'] ) && apply_filters('piecal_use_adaptive_timezones', false) ) {
            $appendOffset = true;
        }

        $automaticEndDates = true;
        if( isset( $atts['automaticenddates'] ) ) {
            $automaticEndDates = false;
        }

        // Temporarily remove read more links for our post details
        add_filter('excerpt_more', 'piecal_replace_read_more', 99);

        while( $events->have_posts() ) {
            $events->the_post();
            $startDate = get_post_meta( get_the_ID(), apply_filters( 'piecal_start_date_meta_key', '_piecal_start_date' ), true );
            $endDate = get_post_meta( get_the_ID(), apply_filters( 'piecal_end_date_meta_key', '_piecal_end_date' ), true );
            $postType = get_post_type_object( get_post_type() );
            $allday = get_post_meta(get_the_ID(), '_piecal_is_allday') ? get_post_meta(get_the_ID(), '_piecal_is_allday', true) : "false";

            // Force all day events to start at 12:00.
            if( $allday == true && $allday != "false" ) {
                $startDate = new DateTime( $startDate );
                $startDate = $startDate->setTime( 12, 0, 0 );
                $startDate = $startDate->format('Y-m-d\TH:i:s');
            }

            if( ( !isset( $endDate ) || !$endDate ) &&
            $automaticEndDates ) {
                $automaticEndDate = new DateTime( $startDate );
                $automaticEndDate = $automaticEndDate->add( new DateInterval('PT1H' ) );

                $endDate = $automaticEndDate->format('Y-m-d\TH:i:s');
            } else if( $automaticEndDates ) {
                // Ensure end date is in required format
                $endDate = new DateTime( $endDate );
                $endDate = $endDate->format('Y-m-d\TH:i:s');
            }

            // Ensure start date is in required format
            $startDate = new DateTime( $startDate );
            $startDate = $startDate->format('Y-m-d\TH:i:s');

            if( $appendOffset ) {
                $startDate .= piecal_site_gmt_offset( piecal_get_gmt_offset_by_date( $startDate ) );
                
                if( isset( $endDate ) && !empty( $endDate ) ) {
                    $endDate = $endDate . piecal_site_gmt_offset( piecal_get_gmt_offset_by_date( $endDate ) );
                }
            }

            $event = [
                "title" => str_replace("&amp;", "&", htmlentities(get_the_title(), ENT_QUOTES)),
                "start" => $startDate,
                "end" => $endDate ?? null,
                "details" => Piecal\Utils\General::getExcerpt(),//str_replace("&amp;", "&", htmlentities(get_the_excerpt(), ENT_QUOTES) ),
                "permalink" => get_permalink(),
                "postType" => $postType->labels->singular_name ?? null,
                "postId" => get_the_ID()
            ];

            if( $allday == true &&
                $allday != "false") {
                $event["allDay"] = $allday;
            }

            $event = apply_filters('piecal_event_array_filter', $event);

            if( $event['postType'] != "null" ) {
                array_push( $eventsArray, $event );
            }
        }

    $eventsArray = apply_filters('piecal_events_array_filter', $eventsArray, $rangeStart = null, $rangeEnd = null, $appendOffset, $atts);

    $eventSources = [
        $eventsArray
    ];

    $eventSources = apply_filters('piecal_event_sources', $eventSources, $rangeStart = null, $rangeEnd = null, $appendOffset, $atts);

    $eventSources = [
        $eventsArray
    ];

    $eventSources = apply_filters('piecal_event_sources', $eventSources, $rangeStart = null, $rangeEnd = null, $appendOffset, $atts);

    remove_filter('excerpt_more', 'piecal_replace_read_more', 99);

    $duration = isset( $atts['duration'] ) ? intval( $atts['duration'] ) : 2;
    $duration = $duration > 24 ? 24 : $duration;
    $duration = $duration < 1 ? 1 : $duration;

    $atts['duration'] = apply_filters( 'piecal_override_view_duration', $duration );
    
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
        "dayGridWeek" => __( 'Week - Day Grid', 'piecal' ),
        /* Translators: String for Day - List view in view picker dropdown. */
        "listDay" => __( 'Day - List', 'piecal' )
    ];
    $viewLabels = apply_filters('piecal_view_labels', $viewLabels);

    $initialView = $atts['view'] ?? 'dayGridMonth';
    
    if( !in_array($initialView, $allowedViews) ) {
        $initialView = 'dayGridMonth';
    }

    do_action('piecal_before_core_frontend_scripts');

    do_action('piecal_after_core_frontend_scripts');

    $locale = $atts['locale'] ?? get_bloginfo('language');

    $localeDateStringFormat = [
        'hour' => '2-digit',
        'minute' => '2-digit'
    ];

    $localeDateStringFormat = apply_filters( 'piecal_locale_date_string_format', $localeDateStringFormat );

    $allDayLocaleDateStringFormat = [];

    $allDayLocaleDateStringFormat = apply_filters( 'piecal_allday_locale_date_string_format', $allDayLocaleDateStringFormat );

    $wrapperClass = 'piecal-wrapper';
    $wrapperViewAttribute = $atts['view'] ?? 'dayGridMonth';

    if( isset( $atts['wraptitles'] ) ) {
        $wrapperClass .= ' piecal-wrap-event-titles';
    }

    $allowedThemes = ['dark',  'adaptive'];

    if( isset( $atts['theme'] ) && in_array( $atts['theme'], $allowedThemes ) ) {
        $wrapperClass .= ' piecal-theme-' . esc_attr( $atts['theme'] );
    }

    if( isset( $atts['widget'] ) && $atts['widget'] == 'true' ) {
        $wrapperClass .= ' piecal-wrapper--widget';
        $initialView = 'dayGridMonth';
    }

    if( isset( $atts['widget'] ) && $atts['widget'] == 'responsive' ) {
        $wrapperClass .= ' piecal-wrapper--responsive-widget';
    }

    $wrapperClass .= apply_filters( 'piecal_wrapper_class', null ) ? " " . apply_filters( 'piecal_wrapper_class', null ) : null;

    $customCalendarProps = [];
    $customCalendarProps = apply_filters('piecal_calendar_object_properties', $customCalendarProps, $eventsArray, $appendOffset, $atts);

    $views = Piecal\Utils\Views::addCustomViews( [] );
    
    ob_start();

    ?>
    <script>
            let piecalAJAX = {
            ajaxURL: "<?php echo esc_url( admin_url('admin-ajax.php') ); ?>",
            ajaxNonce: "<?php echo esc_js( wp_create_nonce('piecal_ajax_nonce') ); ?>"
            }

            let alreadyExpandedOccurrences = [];

            function piecalPrepareCustomViewsForCalendar( views ) {
                let supportedEventHandlers = [
                    'eventDataTransform',
                    'dateClick',
                    'eventClick', 
                    'eventDidMount', 
                    'dayCellDidMount', 
                    'viewDidMount', 
                    'viewWillUnmount',
                    'dayHeaderContent',
                    'dayHeaderDidMount'
                ];

                for( let view in views ) {
                    let viewProps = views[view];

                    for( let prop in viewProps ) {
                        if( supportedEventHandlers.includes( prop ) ) {
                            let eventHandlerCode = viewProps[prop];

                            let handlerFunction = new Function( 'info', eventHandlerCode );

                            viewProps[prop] = handlerFunction;
                        }
                    }

                    // Remove customProps since we don't want those output inside the calendar object
                    delete viewProps.customProps;

                    // Add in the $atts['duration'] value if the view has a duration property.
                    if( viewProps.duration ) {
                        viewProps.duration = {
                            months: <?php echo intval($duration); ?>
                        };
                    }
                }

                return views;
            }
            
            document.addEventListener('DOMContentLoaded', function() {
                var pieCalendarFirstLoad = true;
                var calendarEl = document.getElementById('calendar');
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    headerToolbar: false,
                    initialView: "<?php echo esc_attr( $initialView ); ?>",
                    editable: false,
                    eventSources: <?php echo json_encode($eventSources); ?>,
                    direction: "<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>",
                    contentHeight: "auto",
                    locale: "<?php echo esc_attr( $locale ); ?>",
                    eventTimeFormat: <?php echo json_encode($localeDateStringFormat); ?>,
                    dayHeaderFormat: { weekday: 'long' },
                    views: piecalPrepareCustomViewsForCalendar(<?php echo json_encode($views); ?>),
                    eventClick: function( info ) {
                        info = piecalJS.eventClick( info, {
                            appendOffset: <?php echo $appendOffset ? 'true' : 'false'; ?>
                        } );

                        <?php do_action( 'piecal_additional_event_click_js' ); ?>
                    },
                    eventDataTransform: function(event) {  
                        event = piecalJS.eventDataTransform( event );

                        <?php do_action( 'piecal_additional_event_data_transform_js' ); ?>

                        return event;  
                    },
                    dateClick: function( info ) {
                        info = piecalJS.dateClick( info );

                        <?php do_action( 'piecal_additional_date_click_js' ); ?>
                    },
                    eventDidMount: function( info ) {
                        info = piecalJS.eventDidMount( info );

                        <?php do_action( 'piecal_additional_event_did_mount_js' ); ?>
                    },
                    dayCellDidMount: function( info ) {
                        info = piecalJS.dayCellDidMount( info );

                        <?php do_action( 'piecal_additional_day_cell_did_mount_js' ); ?>
                    },
                    dayHeaderContent: function( info ) {
                        info = piecalJS.dayHeaderContent( info );

                        <?php do_action( 'piecal_additional_day_header_content_js' ); ?>

                        return info.text;
                    },
                    dayHeaderDidMount: function( info ) {
                        let defaultOptions = {
                            showDates: <?php echo apply_filters('piecal_day_header_did_mount_showdates', 'true') ? 'true' : 'false' ?>,
                            locale: "<?php echo esc_attr( $locale ); ?>"
                        };

                        info = piecalJS.dayHeaderDidMount( info, defaultOptions );

                        <?php do_action( 'piecal_additional_day_header_did_mount_js' ); ?>
                    },
                    <?php
                    foreach( $customCalendarProps as $prop ) echo $prop;
                    ?>
                });
                    calendar.render();
                    window.calendar = calendar;
            });

            function piecalChangeView( view ) {
                piecalCleanView( document.querySelector('.piecal-wrapper').getAttribute('data-view'), view );
                document.querySelector('.piecal-wrapper').setAttribute('data-view', view);
                window.calendar.changeView(view);
                Alpine.store('calendarEngine').calendarView = view;
                Alpine.store('calendarEngine').viewTitle = window.calendar.currentData.viewTitle;
                Alpine.store('calendarEngine').viewSpec = window.calendar.currentData.viewSpec.buttonTextDefault;
            }

            // This function forces the calendar to re-render events when the view is changed, but only
            // when necessary. This prevents artifacts from custom views from persisting between view changes
            // when those views have the same or similar types, e.g. listMonth and listUpcoming.
            function piecalCleanView( oldView, newView ) {
                if( oldView.toLowerCase().includes( 'list' ) && newView.toLowerCase().includes( 'grid' ) ) {
                    return false;
                }

                if( oldView.toLowerCase().includes( 'list' ) && newView.toLowerCase().includes( 'list' ) ) {
                    window.calendar.changeView('dayGridMonth');
                }

                if( oldView.toLowerCase().includes( 'grid' ) && newView.toLowerCase().includes( 'grid' ) ) {
                    window.calendar.changeView('listMonth');
                }
            }

            function piecalGotoToday() {
                console.log('today');
            }

            function piecalNextInView() {
                window.calendar.next();
            }

            function piecalPreviousInView() {
                console.log('prev');
            }

            function piecalSkipCalendar() {
                let focusedCalendar = document.querySelector('.piecal-wrapper:focus-within');
                let focusablesInCalendar = focusedCalendar.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"]');
                let lastFocusable = focusablesInCalendar[focusablesInCalendar.length - 1];

                let focusablesInDocument = document.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"]');
                let targetFocusable = Array.prototype.indexOf.call(focusablesInDocument, lastFocusable) + 1;

                focusablesInDocument[targetFocusable].focus();
            }

            document.addEventListener('alpine:init', () => {
                Alpine.store('calendarEngine', {
                    viewTitle: "Loading",
                    viewSpec: "Loading",
                    buttonText: {},
                    showPopover: false,
                    locale: "<?php echo esc_attr( $locale ); ?>",
                    localeDateStringFormat: <?php echo json_encode( $localeDateStringFormat ); ?>,
                    allDayLocaleDateStringFormat: <?php echo json_encode( $allDayLocaleDateStringFormat ); ?>,
                    calendarView: "<?php echo esc_attr( $initialView ); ?>",
                    eventTitle: "Loading...",
                    eventDetails: "Loading...",
                    eventType: "Loading...",
                    eventStart: "Loading...",
                    eventAllDay: false,
                    eventActualEnd: null,
                    eventEnd: "Loading...",
                    eventUrl: "/",
                    safeOutput( input ) {
                        let scrubber = document.createElement('textarea');
                        scrubber.innerHTML = input;
                        return scrubber.value;
                    }
                })
            })

            window.addEventListener('DOMContentLoaded', () => {
                Alpine.store('calendarEngine').viewTitle = window.calendar.currentData.viewTitle;
                Alpine.store('calendarEngine').viewSpec = window.calendar.currentData.viewSpec.buttonTextDefault;
                Alpine.store('calendarEngine').buttonText = window.calendar.currentData.localeDefaults.buttonText;
            })

            window.addEventListener('keydown', (e) => {
                if( e.keyCode == 27 || e.key == 'Escape' ) Alpine.store('calendarEngine').showPopover = false;

            })
        </script>
        <div
        class="<?php echo esc_attr( $wrapperClass ); ?>"
        data-view="<?php echo esc_attr( $wrapperViewAttribute ); ?>";
        x-data
        >
            <div class="piecal-controls fc">
                <button
                    class="piecal-controls__skip-calendar fc-button fc-button-primary"
                    onClick="piecalSkipCalendar()">
                        <?php esc_html_e('Skip Calendar', 'piecal'); ?>
                </button>
                <div
                class="piecal-controls__view-title" 
                aria-live="polite"
                role="status"
                >
                  <span class="visually-hidden" x-text="$store.calendarEngine.viewTitle + ' - current view is ' + $store.calendarEngine.calendarView"></span>
                  <span aria-hidden="true" x-text="$store.calendarEngine.viewTitle"></span>
                </div>
                <label class="piecal-controls__view-chooser">
                    <?php
                    /* Translators: Label for calendar view chooser. */
                    esc_html_e('Choose View', 'piecal')
                    ?>
                    <select x-model="$store.calendarEngine.calendarView" @change="piecalChangeView($store.calendarEngine.calendarView)">
                        <?php foreach( $allowedViews as $view ) { ?>
                            <option value="<?php echo esc_attr( $view ); ?>">
                                <?php echo esc_html( $viewLabels[$view] ); ?>
                            </option>
                        <?php } ?>
                    </select>
                </label>
                <div class="piecal-controls__navigation-button-group">
                    <button 
                        class="piecal-controls__back-to-month fc-button fc-button-primary"
                        aria-label="<?php esc_attr_e( 'Back to full month view.', 'piecal' ); ?>"
                        onClick="piecalChangeView('dayGridMonth')">
                            <?php esc_html_e('Back To Full Month', 'piecal'); ?>
                    </button>
                    <button 
                    class="fc-button fc-button-primary piecal-controls__today-button"
                    @click="window.calendar.today(); $store.calendarEngine.viewTitle = window.calendar.currentData.viewTitle"
                    x-text="$store.calendarEngine.buttonText.today ?? 'Today'">
                    </button>
                    <button 
                    class="fc-button fc-button-primary piecal-controls__prev-button"
                    @click="window.calendar.prev(); $store.calendarEngine.viewTitle = window.calendar.currentData.viewTitle"
                    :aria-label="$store.calendarEngine.buttonText.prev + ' ' + $store.calendarEngine.viewSpec"><</button>
                    <button 
                    class="fc-button fc-button-primary piecal-controls__next-button"
                    @click="window.calendar.next(); $store.calendarEngine.viewTitle = window.calendar.currentData.viewTitle" 
                    :aria-label="$store.calendarEngine.buttonText.next + ' ' + $store.calendarEngine.viewSpec">></button>
                </div>
            </div>
            <div id="calendar"></div>
            <div 
                class="piecal-popover" 
                x-show="$store.calendarEngine.showPopover"
                style="display: none;">
                    <div 
                    class="piecal-popover__inner" 
                    role="dialog"
                    aria-labelledby="piecal-popover__title--01"
                    aria-describedby="piecal-popover__details--01"
                    @click.outside="$store.calendarEngine.showPopover = false"
                    x-trap.noscroll="$store.calendarEngine.showPopover">
                        <button 
                        class="piecal-popover__close-button" 
                        title="<?php
                        /* Translators: Label for close button in Pie Calendar popover. */
                        esc_attr_e( 'Close event details', 'piecal' )
                        ?>"
                        @click="$store.calendarEngine.showPopover = false">
                        </button>
                        <?php do_action('piecal_popover_before_title', $atts); ?>
                        <p class="piecal-popover__title" id="piecal-popover__title--01" x-text="$store.calendarEngine.safeOutput( $store.calendarEngine.eventTitle )">Event Title</p>
                        <?php do_action('piecal_popover_after_title', $atts); ?>
                        <hr>
                        <div class="piecal-popover__meta">
                            <?php do_action('piecal_popover_before_meta', $atts); ?>
                            <p>
                            <?php
                            /* Translators: Label for event start date in Pie Calendar popover. */
                            esc_html_e('Starts', 'piecal')
                            ?>
                            </p>
                            <p 
                            aria-labelledby="piecal-event-start-date" 
                            x-text="!$store.calendarEngine.eventAllDay ? new Date($store.calendarEngine.eventStart).toLocaleDateString( $store.calendarEngine.locale, $store.calendarEngine.localeDateStringFormat ) : new Date($store.calendarEngine.eventStart).toLocaleDateString( $store.calendarEngine.locale, $store.calendarEngine.allDayLocaleDateStringFormat )"></p>
                            <p x-show="$store.calendarEngine.eventEnd">
                            <?php
                            /* Translators: Label for event end date in Pie Calendar popover. */
                            esc_html_e('Ends', 'piecal')
                            ?>
                            </p>
                            <p 
                            x-show="$store.calendarEngine.eventEnd" 
                            x-text="!$store.calendarEngine.eventAllDay ? new Date($store.calendarEngine.eventEnd).toLocaleDateString( $store.calendarEngine.locale, $store.calendarEngine.localeDateStringFormat ) : new Date($store.calendarEngine.eventActualEnd).toLocaleDateString( $store.calendarEngine.locale, $store.calendarEngine.allDayLocaleDateStringFormat )"></p>
                            <?php do_action('piecal_popover_after_meta', $atts); ?>
                        </div>
                        <hr>
                        <?php do_action('piecal_popover_before_details', $atts); ?>
                        <?php echo apply_filters('piecal_popover_details', '<p x-show="$store.calendarEngine.eventDetails" class="piecal-popover__details" id="piecal-popover__details--01" x-text="$store.calendarEngine.safeOutput( $store.calendarEngine.eventDetails )"></p>'); ?>
                        <?php do_action('piecal_popover_after_details', $atts); ?>
                        <?php do_action('piecal_popover_before_view_link', $atts); ?>
                        <a x-show="$store.calendarEngine.eventUrl" class="piecal-popover__view-link" :href="<?php echo esc_attr( apply_filters( 'piecal_popover_link_url', '$store.calendarEngine.eventUrl' ) ); ?>">
                        <?php
                        $filtered_popover_link = apply_filters( 'piecal_popover_link_text', null );

                        if( $filtered_popover_link == null ) {
                        /* Translators: Label for "View <Post Type>" in Pie Calendar popover. */
                            esc_html_e('View ', 'piecal');
                            ?>
                            <span x-text="$store.calendarEngine.eventType"></span>
                            <?php
                        } else {
                            echo $filtered_popover_link;
                        }
                        ?>
                        </a>
                        <?php
                        echo wp_kses_post( apply_filters('piecal_popover_after_view_link', null) );
                        ?>
                    </div>
            </div>
        </div>
        <div class="piecal-footer">
            <?php
            if( !isset( $atts['hidetimezone'] ) && !isset($atts['adaptivetimezone']) && apply_filters('piecal_use_adaptive_timezones', false) ) {
                /* Translators: This string is for displaying the viewer's time zone via the Pie Calendar Info shortcode */
                $footer_text = __( 'Event times are listed in your local time zone: ', 'piecal' );

                echo apply_filters('piecal-footer', $footer_text . "<span x-data x-text='Intl.DateTimeFormat().resolvedOptions().timeZone'></span>");
            }
            ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }
}

if ( ! function_exists( 'piecal_replace_read_more' ) ) {
	function piecal_replace_read_more( $more ) {
		return '...';
	}
}