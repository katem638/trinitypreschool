<?php

namespace Piecal\Utils;

Class General {

    /**
     * Formats and returns a string representation of the given value, encapsulated within a styled HTML block.
     * 
     * This method captures output buffering to construct an HTML string that represents the given value,
     * along with some contextual information, formatted for easier readability.
     * 
     * @param mixed $value The value to be formatted and displayed.
     * @param string $context A string representing the context in which this method is called.
     */
    public static function pretty( $value, $context = 'pretty() util function' ) {
        ?>
        <div style="padding: 32px; background: #EFEFEF; border-radius: 8px">
            <p>Data from <?php echo esc_html( $context ); ?></p>
            <hr>
            <pre style="max-height: 100vh; overflow: auto">
                <?php var_dump( $value ); ?>
            </pre>
        </div>
        <?php
    }

    /**
     * Deduplicates an array of associative arrays based on a unique identifier.
     * The unique identifier is created from the 'title', 'start', and 'end' values of each sub-array, 
     * after removing spaces, hyphens, and colons, and converting the characters to lowercase.
     *
     * This method will preserve the original array's keys.
     *
     * @param array $array The array of associative arrays to deduplicate. Each sub-array should have at least the keys 'title', 'start', and 'end'.
     * 
     * @return array The deduplicated array, with re-indexed numeric keys.
     *
     */
    public static function deduplicateArray( $array ) {
        $index = [];

        foreach( $array as $key => $value ) {
            $id = strtolower( str_replace( [' ', '-', ':'], '', $value['title'].$value['start'].$value['postId'] ) );

            if( in_array( $id, $index ) ) {
                unset( $array[$key] );
            } else {
                $index = [...$index, $id];
            }
        }

        return array_values( $array );
    }

    public static function filterArrayByAllowlist( $array, $allowlist ) {
        if( !isset( $array ) )
            return null;

        if (!is_array($array) && strpos($array, ',') !== false) {
            $array = array_map('trim', explode(',', $array));
        } else if( !is_array($array) && strpos($array, ',') === false ) {
            $array = [$array];
        }

        if( !isset( $allowlist ) )
            return $array;

        $array = array_intersect( $allowlist, $array );

        return $array;
    }

    public static function foundInArray( $needleArray, $haystackArray ) {
        $needleArray = array_map('strtolower', $needleArray);
        $haystackArray = array_map('strtolower', $haystackArray);

        return count(array_intersect($needleArray, $haystackArray)) > 0;
    }

    public static function shouldAddMetabox( $post_type ) {
        $hidePiecalControls = apply_filters( 'piecal_hide_controls', false );

        if( $hidePiecalControls )
            return false;

        $unsupported_post_types = [];

        $explicitAllowedPostTypes = apply_filters( 'piecal_explicit_allowed_post_types', [] );

        if( is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ) )
            $unsupported_post_types = [...$unsupported_post_types, 'download'];

        if( is_plugin_active( 'woocommerce/woocommerce.php' ) )
            $unsupported_post_types = [...$unsupported_post_types, 'product'];

        if( is_plugin_active( 'easy-digital-downloads-pro/easy-digital-downloads.php' ) )
            $unsupported_post_types = [...$unsupported_post_types, 'download'];

        if( is_plugin_active( 'surecart/surecart.php' ) )
            $unsupported_post_types = [...$unsupported_post_types, 'sc_product'];

        $unsupported_post_types = apply_filters( 'piecal_unsupported_post_types', $unsupported_post_types );

        // Add this post type to the unsupported post types list if it's not in the explicit allowed post types list and at least one post type has been explicitly allowed
        if( count( $explicitAllowedPostTypes ) > 0 && !in_array( $post_type, $explicitAllowedPostTypes ) ) {
            $unsupported_post_types = [...$unsupported_post_types, $post_type];   
        }

        if( in_array( $post_type, $unsupported_post_types ) ) {
            return false;
        }

        // Check if block editor is actually loaded by looking for its core assets
        if (wp_script_is('wp-block-editor') || wp_script_is('wp-blocks')) {
            return false;
        }

        if( is_plugin_active( 'woocommerce/woocommerce.php' ) && $post_type == 'product' ) {
            return !in_array( $post_type, $unsupported_post_types );
        }

        if( is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ) && $post_type == 'download' ) {
            return !in_array( $post_type, $unsupported_post_types );
        }

        if( is_plugin_active( 'easy-digital-downloads-pro/easy-digital-downloads.php' ) && $post_type == 'download' ) {
            return !in_array( $post_type, $unsupported_post_types );
        }

        if( ( is_plugin_active( 'surecart/surecart.php' ) && $post_type == 'sc_product' ) ) {
            return !in_array( $post_type, $unsupported_post_types );
        }

        return true;
    }

    public static function getExcerpt( $post = null, $allowHTML = false, $length = 200 ) {
        if( $post == null ) {
            $post = get_post();
        }

        if( $post == null ) {
            return '';
        }

        $length = apply_filters( 'piecal_excerpt_length', $length );
        $allowHTML = apply_filters( 'piecal_excerpt_allow_html', $allowHTML );

        $excerpt = isset($post->post_excerpt) && $post->post_excerpt != '' ? $post->post_excerpt : get_the_content( $post->ID );

        $excerpt = strip_shortcodes( $excerpt );

        $excerpt = $allowHTML ? wp_kses_post( $excerpt ) : wp_strip_all_tags( $excerpt );

        if( strlen( $excerpt ) > $length ) {
            $excerpt = mb_substr( $excerpt, 0, $length ) . '...';
        }

        return $excerpt;
    }
}