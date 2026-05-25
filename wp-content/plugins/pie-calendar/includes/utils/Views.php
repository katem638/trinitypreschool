<?php

namespace Piecal\Utils;

class Views {

    public static function addCustomViews( $views = [], $atts = [] ) {
        if( !is_array( $views ) ) {
            return;
        }

        // Add in core views.
        $views = array_merge( 
            $views, 
            self::coreViewListUpcoming() 
        );

        $views = apply_filters( 'piecal_custom_views', $views, $atts );

        foreach( $views as $view => $viewData ) {
            add_filter( 'piecal_allowed_views', function( $views ) use ( $view ) {
                return self::addAllowedView( $views, $view );
            } );

            add_filter( 'piecal_view_labels', function( $viewLabels ) use ( $view, $viewData ) {
                return self::addViewLabel( $viewLabels, $view, $viewData );
            } );
        }

        self::printCustomViewStyles( $views );

        return $views;
    }

    private static function addAllowedView( $views, $view ) {
        $views = [...$views, $view];
        return $views;
    }

    private static function addViewLabel( $viewLabels, $view, $viewData ) {
        $viewLabels[ $view ] = $viewData['customProps']['niceName'];
        return $viewLabels;
    }

    public static function removeCustomProps( $customViews ) {
        foreach( $customViews as $view => &$viewData ) {
            unset( $viewData['customProps'] );
        }
        return $customViews;
    }

    public static function printCustomViewStyles( $customViews ) {
        // Print the styles on the frontend
        add_action( 'piecal_inline_styles', function() use ( $customViews ) {
            if ( is_admin() || wp_doing_ajax() || wp_is_json_request() ) {
                return; // don’t output during editor/REST
            }

            foreach( $customViews as $view => $viewData ) {
                if( isset( $viewData['customProps']['styles'] ) ) {
                    echo '<style>' . wp_kses_post( $viewData['customProps']['styles'] ) . '</style>';
                }
            }
        });

        add_action( 'enqueue_block_assets', function() use ( $customViews ) {
            if( is_admin() ) {
                wp_enqueue_style( 'piecal-block-inline-styles' );
                foreach( $customViews as $view => $viewData ) {
                    if ( isset( $viewData['customProps']['styles'] ) ) {
                        wp_add_inline_style( 'piecal-block-inline-styles', $viewData['customProps']['styles'] );
                    }
                }
            }
        });

    }

    public static function coreViewListUpcoming() {
        return [
            "listUpcoming" => [
                "type" => "listMonth",
                "duration" => [
                    "months" => $atts['duration'] ?? 2
                ],
                "customProps" => [
                    /* Translators: String for Upcoming view in view picker dropdown. */
                    "niceName" => __( 'List - Upcoming', 'piecal' )
                ]
            ]
        ];
    }
}