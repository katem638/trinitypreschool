<?php
/**
 * Bring the Drop-off & Pick-up hero into the shared page-header system.
 *
 * Run with: ddev wp eval-file scripts/migrate-pickup-header.php
 */

if ( ! class_exists( 'WP_CLI' ) ) {
	exit( "This migration must run through WP-CLI.\n" );
}

$migration_key     = 'trinity_pickup_header_migration';
$migration_version = '2026-09-06-v2';
$installed_version = get_option( $migration_key );

if ( $migration_version === $installed_version ) {
	WP_CLI::success( 'The Drop-off & Pick-up header migration is already complete.' );
	return;
}

if ( $installed_version && '2026-09-06-v1' !== $installed_version ) {
	WP_CLI::error( 'Refusing to replace an unknown Drop-off & Pick-up header migration version: ' . $installed_version );
}

if ( 'local' !== wp_get_environment_type() && 'true' !== getenv( 'IS_DDEV_PROJECT' ) ) {
	WP_CLI::error( 'This migration is intentionally restricted to the local environment.' );
}

$page = get_page_by_path( 'drop-off-pick-up', OBJECT, 'page' );

if ( ! $page || 'publish' !== $page->post_status ) {
	WP_CLI::error( 'The published Drop-off & Pick-up page is unavailable.' );
}

$replacements = '2026-09-06-v1' === $installed_version
	? array(
		'"className":"tp-pickup-hero tp-page-header"' => '"className":"tp-pickup-hero tp-page-header tp-page-header-wide"',
		'class="wp-block-group tp-pickup-hero tp-page-header"' => 'class="wp-block-group tp-pickup-hero tp-page-header tp-page-header-wide"',
	)
	: array(
	'"className":"tp-pickup-hero"' => '"className":"tp-pickup-hero tp-page-header tp-page-header-wide"',
	'class="wp-block-group tp-pickup-hero"' => 'class="wp-block-group tp-pickup-hero tp-page-header tp-page-header-wide"',
	'"className":"tp-pickup-hero-copy"' => '"className":"tp-pickup-hero-copy tp-page-header-inner"',
	'class="wp-block-group tp-pickup-hero-copy"' => 'class="wp-block-group tp-pickup-hero-copy tp-page-header-inner"',
	'"className":"tp-pickup-eyebrow"' => '"className":"tp-pickup-eyebrow tp-page-header-eyebrow"',
	'class="tp-pickup-eyebrow"' => 'class="tp-pickup-eyebrow tp-page-header-eyebrow"',
	'Schedule - Programs' => 'Schedule · Programs',
	'<span>Drop off.</span>' => '<span>Drop-off &amp;</span>',
	'<span>Pick up.</span>' => '<span>Pick-up</span>',
	'<mark>Easy.</mark>' => '',
	);

$content = $page->post_content;
foreach ( $replacements as $search => $replacement ) {
	$position = strpos( $content, $search );
	if ( false === $position ) {
		WP_CLI::error( 'Unexpected page structure: an expected header fragment is missing.' );
	}
	$content = substr_replace( $content, $replacement, $position, strlen( $search ) );
}

$result = wp_update_post(
	array(
		'ID'           => $page->ID,
		'post_content' => $content,
	),
	true
);

if ( is_wp_error( $result ) ) {
	WP_CLI::error( $result->get_error_message() );
}

update_option( $migration_key, $migration_version, false );
WP_CLI::success( 'The Drop-off & Pick-up page now uses the shared page-header system.' );
