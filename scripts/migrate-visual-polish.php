<?php
/**
 * Remove obsolete decorative blocks superseded by reusable theme styling.
 *
 * Run with: ddev wp eval-file scripts/migrate-visual-polish.php
 */

if ( ! class_exists( 'WP_CLI' ) ) {
	exit( "This migration must run through WP-CLI.\n" );
}

$migration_key     = 'trinity_visual_polish_migration';
$migration_version = '2026-09-06-v1';
$installed_version = get_option( $migration_key );

if ( $migration_version === $installed_version ) {
	WP_CLI::success( 'The Trinity visual-polish migration is already complete.' );
	return;
}

if ( $installed_version ) {
	WP_CLI::error( 'Refusing to replace an unknown Trinity visual-polish migration version: ' . $installed_version );
}

if ( 'local' !== wp_get_environment_type() && 'true' !== getenv( 'IS_DDEV_PROJECT' ) ) {
	WP_CLI::error( 'This migration is intentionally restricted to the local environment.' );
}

/**
 * Recursively filter blocks while keeping innerContent placeholders in sync.
 */
function trinity_visual_polish_filter_blocks( array $blocks, $page_slug, array &$removed ) {
	$filtered = array();

	foreach ( $blocks as $block ) {
		$block_name = $block['blockName'] ?? null;
		$class_name = $block['attrs']['className'] ?? '';
		$remove     =
			( 'extended-days-program' === $page_slug && 'core/separator' === $block_name )
			|| ( 'drop-off-pick-up' === $page_slug && in_array( 'tp-pickup-image-placeholder', preg_split( '/\s+/', trim( $class_name ) ), true ) );

		if ( $remove ) {
			$removed[ $page_slug ]++;
			continue;
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			$original_children = $block['innerBlocks'];
			$kept_children     = array();
			$keep_child        = array();

			foreach ( $original_children as $child ) {
				$filtered_child = trinity_visual_polish_filter_blocks( array( $child ), $page_slug, $removed );
				$keep_child[]   = ! empty( $filtered_child );
				if ( $filtered_child ) {
					$kept_children[] = $filtered_child[0];
				}
			}

			$block['innerBlocks'] = $kept_children;

			if ( isset( $block['innerContent'] ) ) {
				$child_index = 0;
				$inner       = array();
				foreach ( $block['innerContent'] as $fragment ) {
					if ( null === $fragment ) {
						if ( $keep_child[ $child_index ] ?? false ) {
							$inner[] = null;
						}
						++$child_index;
					} else {
						$inner[] = $fragment;
					}
				}
				$block['innerContent'] = $inner;
			}
		}

		$filtered[] = $block;
	}

	return $filtered;
}

$removed = array(
	'extended-days-program' => 0,
	'drop-off-pick-up'      => 0,
);

foreach ( array_keys( $removed ) as $slug ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );

	if ( ! $page || 'publish' !== $page->post_status ) {
		WP_CLI::error( 'Missing published Page required by the migration: ' . $slug );
	}

	$blocks = trinity_visual_polish_filter_blocks( parse_blocks( $page->post_content ), $slug, $removed );

	if ( 1 !== $removed[ $slug ] ) {
		WP_CLI::error( sprintf( 'Expected to remove one obsolete block from %s; found %d.', $slug, $removed[ $slug ] ) );
	}

	wp_update_post(
		array(
			'ID'           => $page->ID,
			'post_content' => serialize_blocks( $blocks ),
		)
	);
}

update_option( $migration_key, $migration_version, false );

WP_CLI::log( 'Removed the Extended Days separator block.' );
WP_CLI::log( 'Removed the Drop-off & Pick-up image-placeholder block.' );
WP_CLI::success( 'Trinity visual-polish migration completed.' );
