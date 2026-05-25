<?php

namespace Piecal\Utils;

class Scripts {
    public static function loadCoreScriptsAndStyles() {
        self::enqueueBundle( ['alpinefocus', 'alpinejs', 'fullcalendar', 'piecal-utils', 'piecalJS', 'piecalCSS'] );
    }
    
    public static function enqueueBundle( $bundle = [], $vars = [] ) {
        $additionalHandles = apply_filters('piecal_additional_script_handles', []);

        foreach( $bundle as $script ) {
            switch( $script ) {
                case 'piecalCSS':
                    wp_enqueue_style('piecalCSS');
                break;
                case 'piecalThemeDarkCSS':
                    wp_enqueue_style('piecalThemeDarkCSS');
                break;
                case 'piecalThemeDarkCSSAdaptive':
                    wp_enqueue_style('piecalThemeDarkCSSAdaptive');
                break;
                case 'piecal-utils':
                    wp_enqueue_script('piecal-utils');
                break;
                case 'piecalJS':
                    wp_enqueue_script('piecalJS');
                break;
                case 'alpinejs':
                    wp_enqueue_script('alpinejs');
                break;
                case 'alpinefocus':
                    wp_enqueue_script('alpinefocus');
                break;
                case 'fullcalendar':
                    wp_enqueue_script('fullcalendar');
                break;
                case 'fullcalendar-locales':
                    wp_enqueue_script('fullcalendar-locales');
                break;
                default:
                    if( isset( $additionalHandles[$script] ) ) {

                        if( !isset( $additionalHandles[$script]['type'] ) ) {
                            return;
                        }

                        if( $additionalHandles[$script]['type'] == 'script' ) {
                            wp_enqueue_script( $script );
                        } else {
                            wp_enqueue_style( $script );
                        }

                    }
                break;
            }
        }
    }

    public static function registerAndLocalizeBundle( $bundle = [] ) {
        $additionalHandles = apply_filters('piecal_additional_script_handles', []);

        foreach( $bundle as $script ) {
            switch( $script ) {
                case 'alpinejs':
                    wp_register_script( 'alpinejs', PIECAL_PATH . 'vendor/alpine.3.11.1.min.js', ['alpinefocus'] );
                break;
                case 'alpinefocus':
                    wp_register_script( 'alpinefocus', PIECAL_PATH . 'vendor/alpine.focus.3.11.1.js' );
                break;
                case 'fullcalendar':
                    wp_register_script( 'fullcalendar', PIECAL_PATH . 'vendor/fullcalendar.6.1.4.js' );
                break;
                case 'fullcalendar-locales':
                    wp_register_script( 'fullcalendar-locales', PIECAL_PATH . 'vendor/fullcalendar.locales-all.global.min.js' );
                break;
                case 'piecal-utils':
                    wp_register_script( 'piecal-utils', PIECAL_PATH . 'includes/js/piecal-utils.js', array(), PIECAL_VERSION );

                    $useAdaptiveTimezones = apply_filters('piecal_use_adaptive_timezones', false);

                    wp_localize_script( 'piecal-utils', 'piecalVars', [
                        'useAdaptiveTimezones' => $useAdaptiveTimezones,
                        'siteTimezoneString' => wp_timezone_string(),
                        'siteGMTOffset' => piecal_site_gmt_offset()
                    ] );
                break;
                case 'piecalJS':
                    wp_register_script( 'piecalJS', PIECAL_PATH . 'includes/js/piecal.js', array( 'wp-i18n' ), PIECAL_VERSION );
                break;
                case 'piecalCSS':
                    wp_register_style( 'piecalCSS', PIECAL_PATH . 'css/piecal.css', array(), PIECAL_VERSION );
                break;
                case 'piecalThemeDarkCSS':
                    wp_register_style( 'piecalThemeDarkCSS', PIECAL_PATH . 'css/piecal-theme-dark.css', array(), PIECAL_VERSION );
                break;
                case 'piecalThemeDarkCSSAdaptive':
                    wp_register_style( 'piecalThemeDarkCSSAdaptive', PIECAL_PATH . 'css/piecal-theme-dark-adaptive.css', array(), PIECAL_VERSION );
                break;
                default:
                    if( isset( $additionalHandles[$script] ) ) {

                        if( $additionalHandles[$script]['type'] == 'script' ) {
                            wp_register_script( $script, $additionalHandles[$script]['path'], $additionalHandles[$script]['deps'], $additionalHandles[$script]['ver'] );

                            if( isset( $additionalHandles[$script]['localizeCallback'] ) ) {
                                $additionalHandles[$script]['localizeCallback']();
                            }
                        } else {
                            wp_register_style( $script, $additionalHandles[$script]['path'], $additionalHandles[$script]['deps'], $additionalHandles[$script]['ver'] );
                        }

                    }
                break;
            }
        }  
    }
}