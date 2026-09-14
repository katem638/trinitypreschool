<?php
/**
 * Update and reorder the Extended Days Program class list.
 *
 * Run with: ddev wp eval-file scripts/update-extended-day-classes.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return whether a parsed block has a CSS class.
 */
function tp_extended_block_has_class( array $block, string $class_name ): bool {
	$classes = $block['attrs']['className'] ?? '';
	return in_array( $class_name, preg_split( '/\s+/', trim( $classes ) ), true );
}

/**
 * Remove the decorative letter block from an individual class row.
 */
function tp_extended_remove_row_letter( array $block ): array {
	$serialized = serialize_block( $block );
	$serialized = preg_replace(
		'/<!-- wp:html -->\s*<span aria-hidden="true">[^<]*<\/span>\s*<!-- \/wp:html -->\s*/',
		'',
		$serialized
	);

	$parsed = parse_blocks( $serialized );
	return $parsed[0];
}

/**
 * Find the class-list block, update its rows, and rebuild its inner content.
 */
function tp_extended_update_class_list( array &$blocks ): bool {
	foreach ( $blocks as &$block ) {
		if ( tp_extended_block_has_class( $block, 'tp-extended-class-list' ) ) {
			$children       = $block['innerBlocks'];
			$outside_index  = null;
			$readers_exists = false;

			foreach ( $children as $index => $child ) {
				if ( tp_extended_block_has_class( $child, 'tp-class-group-head-outside' ) ) {
					$outside_index = $index;
				}

				if ( tp_extended_block_has_class( $child, 'tp-row-readers' ) ) {
					$readers_exists = true;
				}

				if ( tp_extended_block_has_class( $child, 'tp-extended-row' ) ) {
					$children[ $index ] = tp_extended_remove_row_letter( $child );
				}
			}

			if ( null === $outside_index ) {
				WP_CLI::error( 'Could not find the Visiting Instructor Classes heading.' );
			}

			if ( ! $readers_exists ) {
				$readers_markup = <<<'HTML'
<!-- wp:group {"tagName":"article","metadata":{"name":"Extended Row"},"className":"tp-extended-row tp-row-readers","layout":{"type":"default"}} -->
<article class="wp-block-group tp-extended-row tp-row-readers"><!-- wp:group {"tagName":"header","layout":{"type":"default"}} -->
<header class="wp-block-group"><!-- wp:group {"metadata":{"name":"Row Title"},"className":"tp-row-title","layout":{"type":"default"}} -->
<div class="wp-block-group tp-row-title"><!-- wp:heading -->
<h2 class="wp-block-heading">Little Readers</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Interactive storytime and crafts</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></header>
<!-- /wp:group -->

<!-- wp:group {"metadata":{"name":"Row Body"},"className":"tp-row-body","layout":{"type":"default"}} -->
<div class="wp-block-group tp-row-body"><!-- wp:paragraph -->
<p>Little Readers fosters a love of reading through interactive Storytime and crafts that coincide with the story of the week. This program, for ages 3 and up, fosters a love of reading and builds confidence through creativity.</p>
<!-- /wp:paragraph -->

<!-- wp:group {"metadata":{"name":"Description List"},"className":"tp-description-list","layout":{"type":"default"}} -->
<div class="wp-block-group tp-description-list"><!-- wp:group {"templateLock":"contentOnly","metadata":{"name":"Description Item"},"className":"tp-description-item","layout":{"type":"default"}} -->
<div class="wp-block-group tp-description-item"><!-- wp:paragraph {"className":"tp-description-term"} -->
<p class="tp-description-term">Schedule</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"tp-description-value"} -->
<p class="tp-description-value">Tuesdays</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"templateLock":"contentOnly","metadata":{"name":"Description Item"},"className":"tp-description-item","layout":{"type":"default"}} -->
<div class="wp-block-group tp-description-item"><!-- wp:paragraph {"className":"tp-description-term"} -->
<p class="tp-description-term">Ages</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"tp-description-value"} -->
<p class="tp-description-value">Ages 3+</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"templateLock":"contentOnly","metadata":{"name":"Description Item"},"className":"tp-description-item","layout":{"type":"default"}} -->
<div class="wp-block-group tp-description-item"><!-- wp:paragraph {"className":"tp-description-term"} -->
<p class="tp-description-term">Length</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"tp-description-value"} -->
<p class="tp-description-value">45 min</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"templateLock":"contentOnly","metadata":{"name":"Description Item"},"className":"tp-description-item","layout":{"type":"default"}} -->
<div class="wp-block-group tp-description-item"><!-- wp:paragraph {"className":"tp-description-term"} -->
<p class="tp-description-term">Teacher</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"tp-description-value"} -->
<p class="tp-description-value">Trinity Staff</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></article>
<!-- /wp:group -->
HTML;
				$readers_block  = parse_blocks( $readers_markup )[0];
				array_splice( $children, $outside_index, 0, array( $readers_block ) );
				++$outside_index;
			}

			$heading    = array_shift( $children );
			$enrichment = array_splice( $children, 0, $outside_index - 1 );
			$day_order  = array(
				'tp-row-bakers'   => 1,
				'tp-row-readers'  => 2,
				'tp-row-building' => 3,
				'tp-row-yoga'     => 4,
				'tp-row-science'  => 5,
			);

			usort(
				$enrichment,
				static function ( array $left, array $right ) use ( $day_order ): int {
					$left_classes  = preg_split( '/\s+/', $left['attrs']['className'] ?? '' );
					$right_classes = preg_split( '/\s+/', $right['attrs']['className'] ?? '' );
					$left_day      = 99;
					$right_day     = 99;

					foreach ( $day_order as $class_name => $day ) {
						if ( in_array( $class_name, $left_classes, true ) ) {
							$left_day = $day;
						}
						if ( in_array( $class_name, $right_classes, true ) ) {
							$right_day = $day;
						}
					}

					return $left_day <=> $right_day;
				}
			);

			$block['innerBlocks'] = array_merge( array( $heading ), $enrichment, $children );
			$opening              = $block['innerContent'][0];
			$closing              = $block['innerContent'][ count( $block['innerContent'] ) - 1 ];
			$block['innerContent'] = array( $opening );

			foreach ( $block['innerBlocks'] as $index => $_child ) {
				$block['innerContent'][] = null;
				if ( $index < count( $block['innerBlocks'] ) - 1 ) {
					$block['innerContent'][] = "\n\n";
				}
			}

			$block['innerContent'][] = $closing;
			return true;
		}

		if ( ! empty( $block['innerBlocks'] ) && tp_extended_update_class_list( $block['innerBlocks'] ) ) {
			return true;
		}
	}

	return false;
}

$page = get_page_by_path( 'extended-days-program', OBJECT, 'page' );
if ( ! $page ) {
	WP_CLI::error( 'Could not find the Extended Days Program page.' );
}

$blocks = parse_blocks( $page->post_content );
if ( ! tp_extended_update_class_list( $blocks ) ) {
	WP_CLI::error( 'Could not find the Extended Class List block.' );
}

$updated_content = serialize_blocks( $blocks );
$result          = wp_update_post(
	wp_slash(
		array(
			'ID'           => $page->ID,
			'post_content' => $updated_content,
		)
	),
	true
);

if ( is_wp_error( $result ) ) {
	WP_CLI::error( $result->get_error_message() );
}

WP_CLI::success( 'Updated Extended Days classes and weekday order.' );
