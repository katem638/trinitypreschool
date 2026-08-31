<?php
/**
 * One-time migration for the resilient Page template and editor-managed menu.
 *
 * Run with: ddev wp eval-file scripts/migrate-theme-hardening.php
 */

if ( ! class_exists( 'WP_CLI' ) ) {
	exit( "This migration must run through WP-CLI.\n" );
}

$migration_key     = 'trinity_theme_hardening_migration';
$migration_version = '2026-08-30-v1';
$installed_version = get_option( $migration_key );

if ( $migration_version === $installed_version ) {
	WP_CLI::success( 'The Trinity theme-hardening migration is already complete.' );
	return;
}

if ( $installed_version ) {
	WP_CLI::error( 'Refusing to replace an unknown Trinity theme-hardening migration version: ' . $installed_version );
}

$page_slugs = array(
	'home',
	'parent-corner',
	'parent-portal',
	'tuition-plans-pricing',
	'pay-tuition',
	'drop-off-pick-up',
	'lunch-bunch',
	'get-involved',
	'registration-forms',
	'extended-days-program',
	'schedule-a-tour',
	'meet-the-director',
	'meet-the-teachers',
	'events',
	'contact',
	'accessibility-statement',
	'privacy-policy',
);

foreach ( $page_slugs as $slug ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );

	if ( ! $page || 'publish' !== $page->post_status ) {
		WP_CLI::error( 'Missing published Page required by the migration: ' . $slug );
	}

	update_post_meta( $page->ID, '_wp_page_template', 'page-landing' );
}

$payment_copy_updates = array(
	'Make a secure online tuition payment through our parent portal.' => 'Request the approved tuition-payment link or get help from the preschool office.',
	'>Pay now<' => '>Payment help<',
);

foreach ( array( 'home', 'parent-corner' ) as $slug ) {
	$page    = get_page_by_path( $slug, OBJECT, 'page' );
	$content = $page->post_content;

	foreach ( $payment_copy_updates as $old => $new ) {
		if ( ! str_contains( $content, $old ) ) {
			WP_CLI::error( sprintf( 'Expected payment copy was not found on %s: %s', $slug, $old ) );
		}

		$content = str_replace( $old, $new, $content );
	}

	wp_update_post(
		array(
			'ID'           => $page->ID,
			'post_content' => $content,
		)
	);
}

$navigation = get_page_by_path( 'primary-navigation', OBJECT, 'wp_navigation' );

if ( ! $navigation ) {
	$navigation_candidates = get_posts(
		array(
			'post_type'      => 'wp_navigation',
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => 1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);
	$navigation = $navigation_candidates[0] ?? null;
}

$navigation_content = <<<'BLOCKS'
<!-- wp:navigation-link {"label":"Home","url":"/","kind":"custom","isTopLevelLink":true} /-->
<!-- wp:navigation-submenu {"label":"Programs","url":"/extended-days-program/","kind":"custom","isTopLevelItem":true} -->
	<!-- wp:navigation-link {"label":"Tuition & Classes","url":"/tuition-plans-pricing/","kind":"custom"} /-->
	<!-- wp:navigation-link {"label":"Extended Days Program","url":"/extended-days-program/","kind":"custom"} /-->
	<!-- wp:navigation-link {"label":"Lunch Bunch","url":"/lunch-bunch/","kind":"custom"} /-->
	<!-- wp:navigation-link {"label":"Drop-off & Pick-up","url":"/drop-off-pick-up/","kind":"custom"} /-->
<!-- /wp:navigation-submenu -->
<!-- wp:navigation-link {"label":"Forms","url":"/registration-forms/","kind":"custom","isTopLevelLink":true} /-->
<!-- wp:navigation-link {"label":"Schedule a Tour","url":"/schedule-a-tour/","kind":"custom","isTopLevelLink":true} /-->
<!-- wp:navigation-submenu {"label":"Parent Corner","url":"/parent-corner/","kind":"custom","isTopLevelItem":true} -->
	<!-- wp:navigation-link {"label":"Parent Corner","url":"/parent-corner/","kind":"custom"} /-->
	<!-- wp:navigation-link {"label":"Pay Tuition","url":"/pay-tuition/","kind":"custom"} /-->
	<!-- wp:navigation-link {"label":"Get Involved","url":"/get-involved/","kind":"custom"} /-->
<!-- /wp:navigation-submenu -->
<!-- wp:navigation-submenu {"label":"About","url":"/meet-the-director/","kind":"custom","isTopLevelItem":true} -->
	<!-- wp:navigation-link {"label":"Meet Our Director","url":"/meet-the-director/","kind":"custom"} /-->
	<!-- wp:navigation-link {"label":"Meet The Teachers","url":"/meet-the-teachers/","kind":"custom"} /-->
<!-- /wp:navigation-submenu -->
<!-- wp:navigation-link {"label":"Events","url":"/events/","kind":"custom","isTopLevelLink":true} /-->
<!-- wp:navigation-link {"label":"Contact","url":"/contact/","kind":"custom","isTopLevelLink":true} /-->
BLOCKS;

$navigation_values = array(
		'post_title'   => 'Primary Navigation',
		'post_name'    => 'primary-navigation',
		'post_content' => $navigation_content,
		'post_type'    => 'wp_navigation',
		'post_status'  => 'publish',
	);

if ( $navigation ) {
	$navigation_values['ID'] = $navigation->ID;
}

$navigation_update = wp_insert_post( $navigation_values, true );

if ( is_wp_error( $navigation_update ) ) {
	WP_CLI::error( $navigation_update->get_error_message() );
}

update_option( $migration_key, $migration_version, false );
clean_post_cache( (int) $navigation_update );

WP_CLI::log( sprintf( '%d existing Pages assigned to the Designed Page template.', count( $page_slugs ) ) );
WP_CLI::log( 'Home and Parent Corner payment language now matches the Pay Tuition page.' );
WP_CLI::log( 'Primary Navigation is now a named WordPress Navigation entity.' );
WP_CLI::success( 'Trinity theme-hardening migration completed.' );
