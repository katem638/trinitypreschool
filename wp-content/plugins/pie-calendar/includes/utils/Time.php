<?php

namespace Piecal\Utils;

use \DateTime, \DateTimeZone;

class Time {

    /**
     * Receives a datetime object and converts it into a string that matches the format expected by Pie Calendar generally
     * 
     * @param DateTime DateTime object
     * 
     * @return string DateTime formatted into a string as 'Y-m-d\TH:i:s'
     */
    public static function getStandardDateTimeString( $dateTime, $appendOffset ) {
        if( is_string( $dateTime ) ) {
            try {
                $dateTime = new DateTime( $dateTime );
            } catch( Exception $e ) {
                error_log( 'Invalid string, cannot convert to DateTime: ' . $e );
                return null;
            }
        }

        $formatted = $dateTime->format('Y-m-d\TH:i:s');

        // Calculate offset, but only added it optionally.
        $offset = self::gmtOffset( self::offsetOfGivenDate( $formatted ) );

        //Add offset to end date
        if( $appendOffset ) {
            $formatted .= $offset;
        }

        return $formatted;
    }

    /**
     * Receives a start datetime and end datetime, returns a duration in milliseconds.
     * 
     * @param string Start datetime
     * @param string End datetime
     * 
     * @return int Duration of time between start & end provided
     */
    public static function getDuration( $start, $end ) {
        $start = new DateTime($start);
        $end = new DateTime($end);

        return $start->diff($end);
    }

    /**
     * Returns the offset in MS for a given date. Relies on the current timezone of the WordPress site
     * to determine the offset.
     * 
     * @param Date date string formatted as 'Y-m-d\TH:i:s'
     * @return Int offset of given date in milliseconds
     */
    public static function offsetOfGivenDate( $date ) {
        $start = new DateTime($date);
        $timezone = new DateTimeZone( wp_timezone_string() );
        $offset = $timezone->getOffset($start) / 60 / 60;

        return $offset;
    }

    /**
     * Returns the appropriately formatted GMT offset from passed millisecond offset.
     * 
     * @param Int Offset in milliseconds
     * @return String Offset formatted in hours, e.g. 05:00
     */
    public static function gmtOffset( $offset = null ) {
        // Get the gmt_offset option, or fallback to +00:00 if the option isn't set.
        $gmt_offset = null;
        
        if( $offset !== null ) {
            $gmt_offset = $offset;
        } else {
            $gmt_offset = get_option( 'gmt_offset' ) ?? '+00:00';
        }

        // Early return for if the gmt_offset option is missing.
        if( $gmt_offset === '+00:00' ) {
            return $gmt_offset;
        }

        // Get the GMT offset as an interval. This conveniently excludes any decimal values.
        $gmt_offset_int = intval($gmt_offset);

        // Next, we get our GMT offset number only without any +/- or decimal values.
        $gmt_offset_number_only = abs($gmt_offset_int);

        // GMT offsets in WordPress are returned as decimal representations, e.g. 5.5 = 05:30, so we have to get the decimal value alone here.
        // We subtract the $gmt_offset_int from $gmt_offset to get the remaining decimal value.
        $gmt_offset_decimal = $gmt_offset_int - $gmt_offset;

        // Finally, we convert the isolated decimal value to a representation of minutes, e.g. .5 becomes 30 and .75 becomes 45
        $gmt_offset_decimal_as_minutes = abs($gmt_offset_decimal * 60);

        // Here we determine whether to use a + or - symbol by checking the gmt_offset_int's value against 0.
        $gmt_offset_plus_or_minus = $gmt_offset_int > 0 ? '+' : '-';

        // Now we can combine all of the parts to get a properly formatted offset for use in setting our event times
        $gmt_final_offset = sprintf('%s%02d:%02d', $gmt_offset_plus_or_minus, $gmt_offset_number_only, $gmt_offset_decimal_as_minutes);

        return $gmt_final_offset;
    }

    // Helper function to get start date from pass-through (URL) or meta key depending on what's available.
    public static function getStartDate() {
        $tz_string = isset( $_GET['timezone'] ) ? sanitize_text_field( wp_unslash( $_GET['timezone'] ) ) : wp_timezone_string();

        if( in_array( $tz_string, timezone_identifiers_list(), true ) ) {
            try {
                $timezoneObj = new DateTimeZone( $tz_string );
            } catch( Exception $e ) {
                $timezoneObj = new DateTimeZone( wp_timezone_string() ?: 'UTC' );
            }
        } else {
            $timezoneObj = new DateTimeZone( wp_timezone_string() ?: 'UTC' );
        }
        
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a read-only operation for public-facing content
        $get_timezone = isset( $_GET['timezone'] ) ? sanitize_text_field( wp_unslash( $_GET['timezone'] ) ) : wp_timezone_string();
        $timezoneObj = new DateTimeZone( $get_timezone );
        $startDate = null;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a read-only operation for public-facing content
        $get_eventstart = isset( $_GET['eventstart'] ) ? sanitize_text_field( wp_unslash( $_GET['eventstart'] ) ) : null;
        if ( isset( $get_eventstart ) && is_numeric( $get_eventstart ) && $timezoneObj ) {
            $startDate = $get_eventstart;
            
            if ( $date = new DateTime( '@' . $startDate ) ) {
                $date->setTimezone( $timezoneObj );
                $startDate     = $date->format( "Y-m-d H:i:s" );
            }
        } else {
            $startDate = get_post_meta(get_the_ID(), apply_filters( 'piecal_start_date_meta_key', '_piecal_start_date' ), true );
        }

        return $startDate;
    }

    // Helper function to get end date from pass-through (URL) or meta key depending on what's available.
    public static function getEndDate() {
        $tz_string = isset( $_GET['timezone'] ) ? sanitize_text_field( wp_unslash( $_GET['timezone'] ) ) : wp_timezone_string();

        if( in_array( $tz_string, timezone_identifiers_list(), true ) ) {
            try {
                $timezoneObj = new DateTimeZone( $tz_string );
            } catch( Exception $e ) {
                $timezoneObj = new DateTimeZone( wp_timezone_string() ?: 'UTC' );
            }
        } else {
            $timezoneObj = new DateTimeZone( wp_timezone_string() ?: 'UTC' );
        }
        
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a read-only operation for public-facing content
        $get_timezone = isset( $_GET['timezone'] ) ? sanitize_text_field( wp_unslash( $_GET['timezone'] ) ) : wp_timezone_string();
        $timezoneObj = new DateTimeZone( $get_timezone );
        $endDate = null;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a read-only operation for public-facing content
        $get_eventend = isset( $_GET['eventend'] ) ? sanitize_text_field( wp_unslash( $_GET['eventend'] ) ) : null;
        if ( isset( $get_eventend ) && is_numeric( $get_eventend ) && $get_eventend != 0 && $timezoneObj ) {
            $endDate = $get_eventend;
            
            if ( $date = new DateTime( '@' . $endDate ) ) {
                $date->setTimezone( $timezoneObj );
                $endDate       = $date->format( "Y-m-d H:i:s" );
            }
        } else {
            $endDate = get_post_meta(get_the_ID(), apply_filters( 'piecal_end_date_meta_key', '_piecal_end_date' ), true );
        }

        return $endDate;
    }
}