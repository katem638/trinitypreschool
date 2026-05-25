<?php
/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

$atts = array(
	'locale'            => $attributes['locale'],
	'theme'             => $attributes['theme'],
	'widget'            => $attributes['widget'],
	'wraptitles'        => $attributes['wraptitles'],
	'view'              => $attributes['view'],
	'adaptivetimezone'  => $attributes['adaptivetimezone'] ?? null,
	'type'              => $attributes['type'],
	'hidetimezone'      => $attributes['hidetimezone'],
	'taxonomy'          => $attributes['taxonomy'] ?? null,
	'terms'             => $attributes['terms'] ?? null,
	'operator'          => $attributes['operator'] ?? null,
	'automaticenddates' => $attributes['automaticenddates'],
	'featuredimage'     => $attributes['featuredimage'] ? 'true' : 'false',
	'duration'          => $attributes['duration'] ?? 1,
	'hidepastevents'    => $attributes['hidepastevents'] ? 'true' : 'false',
	'sources'           => $attributes['sources'] ?? null,
);

/**
 * Adds an extra filter when passing the calendar block attributes.
 */
$atts = apply_filters( 'piecal_calendar_block_atts', $atts, $attributes );

// Get existing wrapper attributes
$wrapper_attributes = get_block_wrapper_attributes();

// Add theme class if theme is set
if (!empty($atts['theme'])) {
    $wrapper_attributes = str_replace('class="', 'class="piecal-theme-' . esc_attr($atts['theme']) . ' ', $wrapper_attributes);
}

?>
<div <?php echo wp_kses_post( $wrapper_attributes ); ?>>
	<?php echo piecal_render_calendar( $atts ); ?>
</div>
