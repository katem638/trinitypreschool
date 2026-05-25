<?php
// Pie Calendar start & end date shortcode(s)
add_shortcode('piecal-info', 'render_piecal_info');

function render_piecal_info( $atts ) {
    do_action( 'piecal_before_info', $atts );

    require_once( PIECAL_DIR . '/includes/utils/General.php' );
    require_once( PIECAL_DIR . '/includes/utils/Time.php' );

    wp_enqueue_style('piecalCSS');

    if( !get_post_meta( get_the_ID(), '_piecal_is_event', true ) ) 
        return;

    $allowedFragments = ['start', 'end', 'timezone', 'all', 'allday'];
    $allowedFragments = apply_filters( 'piecal_info_allowed_fragments', $allowedFragments );
    
    if( isset( $atts['fragments'] ) && !empty( $atts['fragments'] ) ) {
        $atts['fragments'] = Piecal\Utils\General::filterArrayByAllowlist( $atts['fragments'], $allowedFragments );
    } else {
        $atts['fragments'] = ['all'];
    }

    if( empty( $atts['fragments'] ) ) {
        $atts['fragments'] = ['all'];
    }

    /* Translators: This string is used to separate the date and time in the default format output by the piecal-info shortcode. Each letter must be escaped by a backslash. */
    $format = $atts['format'] ?? get_option('date_format') . __(' \a\t ', 'piecal') . get_option('time_format');
    $format_date_only = $atts['format'] ?? get_option('date_format');

    /* Translators: This string is for displaying the time zone via the Pie Calendar Info shortcode */
    $timezone = ( !isset( $atts['hidetimezone'] ) && apply_filters('piecal_use_adaptive_timezones', false) ) ? __( 'Events are listed in the following time zone: ', 'piecal' ) . wp_timezone_string() . ' (GMT ' . piecal_site_gmt_offset( piecal_get_gmt_offset_by_date( get_post_meta( get_the_ID(), '_piecal_start_date', true ) ) ) . ')' : null;
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a read-only operation for public-facing content
    $get_timezone = isset( $_GET['timezone'] ) ? sanitize_text_field( wp_unslash( $_GET['timezone'] ) ) : '';
    if( !empty( $get_timezone ) && apply_filters('piecal_use_adaptive_timezones', false) ) {
        /* Translators: This string is for displaying the viewer's time zone via the Pie Calendar Info shortcode */
        $timezone =  __( 'Event times are listed in your local time zone: ', 'piecal' ) . esc_html( $get_timezone );
    }

    $show_timezone = true;
    
    $startDate = Piecal\Utils\Time::getStartDate();
    $endDate = Piecal\Utils\Time::getEndDate();

    $start = $startDate ? date_i18n( $format, strtotime( $startDate ) ) : false;
    $start_date_only = $startDate ? date_i18n( $format_date_only, strtotime( $startDate ) ) : false;

    $end = $endDate ? date_i18n( $format, strtotime( $endDate ) ) : false;
    $end_date_only = $endDate ? date_i18n( $format_date_only, strtotime( $endDate ) ) : false;

    $allday = get_post_meta( get_the_ID(), '_piecal_is_allday', true ) ?? false;

    /* Translators: The 'Starts on' prepend text for the piecal-info shortcode */
    $start_prepend = apply_filters( 'piecal_info_start_prepend', __("Starts on", 'piecal') );

    /* Translators: This string is used for the start date/time output by the piecal-info shortcode. %1$s is the 'Starts on' prepend. %2$s is the start date & time. */
    $info_string_start = sprintf( esc_html__( '%1$s %2$s', 'piecal' ), esc_html( $start_prepend ), esc_html( $start ) );
    /* Translators: This string is used for the start date/time output by the piecal-info shortcode (date only). %1$s is the 'Starts on' prepend. %2$s is the start date. */
    $info_string_start_date_only = sprintf( esc_html__( '%1$s %2$s', 'piecal' ), esc_html( $start_prepend ), esc_html( $start_date_only ) );

    /* Translators: The 'Ends on' prepend text for the piecal-info shortcode. */
    $end_prepend = apply_filters( 'piecal_info_end_prepend', __( "Ends on", 'piecal' ) );

    /* Translators: This string is used for the end date/time output by the piecal-info shortcode. %1$s is the 'Ends on' prepend. %2$s is the end date & time. */
    $info_string_end = sprintf( esc_html__( ' %1$s %2$s', 'piecal' ), esc_html( $end_prepend ), esc_html( $end ) );
    /* Translators: This string is used for the end date/time output by the piecal-info shortcode (date only). %1$s is the 'Ends on' prepend. %2$s is the end date. */
    $info_string_end_date_only = sprintf( esc_html__( ' %1$s %2$s', 'piecal' ), esc_html( $end_prepend ), esc_html( $end_date_only ) );

    /* Translators: This string is output at the end of the start/end date/time output by the piecal-info shortcode if the event is marked as all day. */
    $info_string_allday = apply_filters( 'piecal_info_lasts_all_day', __( ' Lasts all day.', 'piecal' ) );

    ob_start();
    ?>
    <div class="piecal-info">
        <script>
            <?php do_action( 'piecal_info_scripts' ); ?>
        </script>
        <?php
        // Start date
        if( $start && Piecal\Utils\General::foundInArray( ['start', 'all'], $atts['fragments'] ?? [] ) ) {
          if( empty( $allday ) ) {
              echo "<p class='piecal-info__start'>" . wp_kses_post( $info_string_start ) . "</p>";
          } else {
              echo "<p class='piecal-info__start'>" . wp_kses_post( $info_string_start_date_only ) . "</p>";
          }
        }

        // End date
        if( $end && Piecal\Utils\General::foundInArray( ['end', 'all'], $atts['fragments'] ?? [] ) ) {
            if( empty( $allday ) ) {
                echo "<p class='piecal-info__end'>" . wp_kses_post( $info_string_end ) . "</p>";
            } else {
                echo "<p class='piecal-info__end'>" . wp_kses_post( $info_string_end_date_only ) . "</p>";
            }
        }

        // All day string
        if( $allday && Piecal\Utils\General::foundInArray( ['allday', 'all'], $atts['fragments'] ?? [] ) ) {
            echo "<p class='piecal-info__allday'>" . wp_kses_post( $info_string_allday ) . "</p>";
        }
        ?>
        <?php if( empty($allday) && $show_timezone === true && Piecal\Utils\General::foundInArray( ['timezone', 'all'], $atts['fragments'] ?? [] ) ) { ?>
            <p class="piecal-info__timezone">
                <?php echo wp_kses_post( $timezone ); ?>
            </p>
        <?php } ?>
    </div>
    <?php

    do_action( 'piecal_after_info', $atts );
    return ob_get_clean();
}