<?php
/**
 * Move utility pages onto the default template and its shared page header.
 *
 * Run with: ddev wp eval-file scripts/migrate-page-header-system.php
 */

if ( ! class_exists( 'WP_CLI' ) ) {
	exit( "This migration must run through WP-CLI.\n" );
}

$migration_key     = 'trinity_page_header_system_migration';
$migration_version = '2026-09-06-v1';
$installed_version = get_option( $migration_key );

if ( $migration_version === $installed_version ) {
	WP_CLI::success( 'The Trinity page-header migration is already complete.' );
	return;
}

if ( $installed_version ) {
	WP_CLI::error( 'Refusing to replace an unknown Trinity page-header migration version: ' . $installed_version );
}

if ( 'local' !== wp_get_environment_type() && 'true' !== getenv( 'IS_DDEV_PROJECT' ) ) {
	WP_CLI::error( 'This migration is intentionally restricted to the local environment.' );
}

$page_slugs = array(
	'privacy-policy',
	'accessibility-statement',
	'parent-portal',
	'pay-tuition',
);

foreach ( $page_slugs as $page_slug ) {
	$page = get_page_by_path( $page_slug, OBJECT, 'page' );

	if ( ! $page || 'publish' !== $page->post_status ) {
		WP_CLI::error( 'Missing published Page required by the migration: ' . $page_slug );
	}

	$blocks = array_values(
		array_filter(
			parse_blocks( $page->post_content ),
			static function ( $block ) {
				return ! empty( $block['blockName'] );
			}
		)
	);

	if ( 1 !== count( $blocks ) || 'core/group' !== $blocks[0]['blockName'] ) {
		WP_CLI::error( 'Unexpected outer content structure on ' . $page_slug . '.' );
	}

	$outer_classes = preg_split( '/\s+/', trim( $blocks[0]['attrs']['className'] ?? '' ) );
	if ( ! in_array( 'tp-standard-page', $outer_classes, true ) ) {
		WP_CLI::error( 'Missing standard-page wrapper on ' . $page_slug . '.' );
	}

	$outer_children = $blocks[0]['innerBlocks'] ?? array();
	if ( 1 !== count( $outer_children ) || 'core/group' !== $outer_children[0]['blockName'] ) {
		WP_CLI::error( 'Unexpected inner content structure on ' . $page_slug . '.' );
	}

	$inner_classes = preg_split( '/\s+/', trim( $outer_children[0]['attrs']['className'] ?? '' ) );
	if ( ! in_array( 'tp-standard-inner', $inner_classes, true ) ) {
		WP_CLI::error( 'Missing standard-inner wrapper on ' . $page_slug . '.' );
	}

	$content_blocks = $outer_children[0]['innerBlocks'] ?? array();
	$title_block    = array_shift( $content_blocks );

	if ( ! $title_block || 'core/heading' !== $title_block['blockName'] || 1 !== ( $title_block['attrs']['level'] ?? 2 ) ) {
		WP_CLI::error( 'Expected the first content block to be an H1 on ' . $page_slug . '.' );
	}

	$title_text = trim( wp_strip_all_tags( render_block( $title_block ) ) );
	if ( html_entity_decode( $page->post_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) !== html_entity_decode( $title_text, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) {
		WP_CLI::error( 'The content H1 does not match the Page title on ' . $page_slug . '.' );
	}

	$result = wp_update_post(
		array(
			'ID'           => $page->ID,
			'post_content' => serialize_blocks( $content_blocks ),
		),
		true
	);

	if ( is_wp_error( $result ) ) {
		WP_CLI::error( $result->get_error_message() );
	}

	delete_post_meta( $page->ID, '_wp_page_template' );
	WP_CLI::log( 'Migrated ' . $page->post_title . ' to the default Page template.' );
}

update_option( $migration_key, $migration_version, false );
WP_CLI::success( 'Trinity utility pages now use the shared page header.' );
