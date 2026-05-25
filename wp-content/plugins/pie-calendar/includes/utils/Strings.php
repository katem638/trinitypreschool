<?php

namespace Piecal\Utils;

class Strings {

    /**
     * Get the strings for the plugin.
     * 
     * Only used for Gutenberg controls for now.
     * 
     * @TODO: Maybe use this to handle all strings in the plugin.
     * @NOTE: This does not add the normal context, e.g. translator comments, because it's not possible to do so using this method.
     *
     * @param string $domain The domain of the plugin.
     * @return array The strings for the plugin.
     */
    public static function piecalStrings( $domain ) {

        $strings = [
            // Translators: Calendar label for Gutenberg controls.
            'Calendar' => __('Calendar', 'piecal'),
            // Translators: Show On Calendar label for Gutenberg controls.
            'Show_On_Calendar' => __('Show On Calendar', 'piecal'),
            // Translators: All Day Event label for Gutenberg controls.
            'All_Day_Event' => __('All Day Event', 'piecal'),
            // Translators: Start Date label for Gutenberg controls.
            'Start_Date' => __('Start Date', 'piecal'),
            // Translators: End Date label for Gutenberg controls.
            'End_Date' => __('End Date', 'piecal'),
            // Translators: Clear label for Gutenberg controls.
            'Clear' => __('Clear', 'piecal'),
            // Translators: Starts on label for Gutenberg controls.
            'Starts_on_' => __('Starts on ', 'piecal'),
            // Translators: Ends on label for Gutenberg controls.
            'Ends_on_' => __('Ends on ', 'piecal'),
        ];

        $strings = apply_filters( 'piecal_strings', $strings );

        return $strings;
    }

    /**
     * Get the key for the string.
     * 
     * @param string $string The string to get the key for.
     * @return string The key for the string.
     */
    public static function stringKey( $string ) {
        return str_replace( ' ', '_', $string );
    }
}