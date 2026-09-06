<?php
/**
 * Move each teacher biography below the portrait/introduction row.
 *
 * Run with: ddev wp eval-file scripts/migrate-teacher-card-layout.php
 */

$page = get_page_by_path( 'meet-the-teachers', OBJECT, 'page' );

if ( ! $page ) {
	WP_CLI::error( 'The Meet The Teachers page was not found.' );
}

$migrated = 0;
$blocks   = parse_blocks( $page->post_content );

$remove_inner_block = static function ( array &$block, int $child_index ): array {
	$removed    = array_splice( $block['innerBlocks'], $child_index, 1 );
	$null_index = -1;
	$seen       = -1;

	foreach ( $block['innerContent'] as $content_index => $content ) {
		if ( null === $content ) {
			++$seen;

			if ( $seen === $child_index ) {
				$null_index = $content_index;
				break;
			}
		}
	}

	if ( -1 !== $null_index ) {
		array_splice( $block['innerContent'], $null_index, 1 );
	}

	return $removed[0] ?? array();
};

$migrate_blocks = static function ( array &$candidate_blocks ) use ( &$migrate_blocks, &$migrated, $remove_inner_block ): void {
	foreach ( $candidate_blocks as &$block ) {
		$class_name = $block['attrs']['className'] ?? '';
		$classes    = preg_split( '/\s+/', trim( $class_name ) );

		if ( 'core/group' === ( $block['blockName'] ?? '' ) && in_array( 'tp-teacher-card', $classes, true ) ) {
			$has_profile = false;

			foreach ( $block['innerBlocks'] as $child ) {
				$child_classes = preg_split( '/\s+/', trim( $child['attrs']['className'] ?? '' ) );

				if ( in_array( 'tp-teacher-profile', $child_classes, true ) ) {
					$has_profile = true;
					break;
				}
			}

			if ( $has_profile || count( $block['innerBlocks'] ) < 2 ) {
				continue;
			}

			$portrait = $block['innerBlocks'][0];
			$body     = $block['innerBlocks'][1];
			$bio      = array();

			foreach ( $body['innerBlocks'] as $body_index => $body_child ) {
				if ( 'core/details' === ( $body_child['blockName'] ?? '' ) ) {
					$bio = $remove_inner_block( $body, $body_index );
					break;
				}
			}

			if ( empty( $bio ) ) {
				continue;
			}

			$profile = array(
				'blockName'    => 'core/group',
				'attrs'        => array(
					'className' => 'tp-teacher-profile',
					'metadata'  => array( 'name' => 'Portrait and introduction' ),
					'layout'    => array( 'type' => 'default' ),
				),
				'innerBlocks'  => array( $portrait, $body ),
				'innerHTML'    => '<div class="wp-block-group tp-teacher-profile"></div>',
				'innerContent' => array(
					'<div class="wp-block-group tp-teacher-profile">',
					null,
					"\n\n",
					null,
					'</div>',
				),
			);

			$first_content = reset( $block['innerContent'] );
			$last_content  = end( $block['innerContent'] );

			$block['innerBlocks']  = array( $profile, $bio );
			$block['innerContent'] = array( $first_content, null, "\n\n", null, $last_content );
			$block['innerHTML']    = $first_content . "\n\n" . $last_content;
			++$migrated;
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			$migrate_blocks( $block['innerBlocks'] );
		}
	}
};

$migrate_blocks( $blocks );

if ( 0 === $migrated ) {
	WP_CLI::success( 'No teacher cards needed migration.' );
	return;
}

$result = wp_update_post(
	array(
		'ID'           => $page->ID,
		'post_content' => serialize_blocks( $blocks ),
	),
	true
);

if ( is_wp_error( $result ) ) {
	WP_CLI::error( $result->get_error_message() );
}

WP_CLI::success( sprintf( 'Migrated %d teacher cards on page %d.', $migrated, $page->ID ) );
