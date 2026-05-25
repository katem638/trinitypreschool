<?php
/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

$atts = array(
	'format'       => $attributes['format'],
	'hidetimezone' => $attributes['hidetimezone'],
	'fragments'    => $attributes['fragments'],
);

// Update the format to use single backslashes.
$atts['format'] = str_replace( '\\\\', '\\', $atts['format'] );

/**
 * Adds an extra filter when passing the calendar block attributes.
 */
$atts = apply_filters( 'piecal_info_block_atts', $atts );

// Add custom text filters if set
$custom_filters = [];
if ( !empty( $attributes['startText'] ) ) {
    $callback = function() use ( $attributes ) {
        return $attributes['startText'];
    };
    add_filter( 'piecal_info_start_prepend', $callback );
    $custom_filters['start'] = $callback;
}
if ( !empty( $attributes['endText'] ) ) {
    $callback = function() use ( $attributes ) {
        return $attributes['endText'];
    };
    add_filter( 'piecal_info_end_prepend', $callback );
    $custom_filters['end'] = $callback;
}
if ( !empty( $attributes['allDayText'] ) ) {
    $callback = function() use ( $attributes ) {
        return $attributes['allDayText'];
    };
    add_filter( 'piecal_info_lasts_all_day', $callback );
    $custom_filters['allday'] = $callback;
}

/*
 * Hide the prepend text, could be shortcode attribute in the future.
 */
if ( isset( $attributes['hidePrependText'] ) && $attributes['hidePrependText'] ) {
	add_filter( 'piecal_info_start_prepend', '__return_empty_string' );
	add_filter( 'piecal_info_end_prepend', '__return_empty_string' );
}

?>
<div <?php echo wp_kses_post( get_block_wrapper_attributes() ); ?>>
	<?php echo render_piecal_info( $atts ); ?>
</div>

<?php
// Remove all filters we added
if ( isset( $attributes['hidePrependText'] ) && $attributes['hidePrependText'] ) {
	remove_filter( 'piecal_info_start_prepend', '__return_empty_string' );
	remove_filter( 'piecal_info_end_prepend', '__return_empty_string' );
}

// Remove custom text filters if they were added
if ( !empty( $custom_filters['start'] ) ) {
    remove_filter( 'piecal_info_start_prepend', $custom_filters['start'] );
}
if ( !empty( $custom_filters['end'] ) ) {
    remove_filter( 'piecal_info_end_prepend', $custom_filters['end'] );
}
if ( !empty( $custom_filters['allday'] ) ) {
    remove_filter( 'piecal_info_lasts_all_day', $custom_filters['allday'] );
}
