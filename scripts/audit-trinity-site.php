<?php
/**
 * Read-only release audit for the Trinity editable-content architecture.
 *
 * Run with: ddev wp eval-file scripts/audit-trinity-site.php
 */

if ( ! class_exists( 'WP_CLI' ) ) {
	exit( "This audit must run through WP-CLI.\n" );
}

function trinity_audit_walk_blocks( array $blocks, callable $callback ) {
	foreach ( $blocks as $block ) {
		$callback( $block );
		if ( ! empty( $block['innerBlocks'] ) ) {
			trinity_audit_walk_blocks( $block['innerBlocks'], $callback );
		}
	}
}

$expected_pages = array(
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

$errors = array();
$notes  = array();

foreach ( $expected_pages as $slug ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	if ( ! $page || 'publish' !== $page->post_status ) {
		$errors[] = 'Missing published Page: ' . $slug;
		continue;
	}

	$h1_count       = 0;
	$shortcode_count = 0;
	trinity_audit_walk_blocks(
		parse_blocks( $page->post_content ),
		function ( $block ) use ( &$h1_count, &$shortcode_count ) {
			if ( 'core/heading' === ( $block['blockName'] ?? '' ) && 1 === (int) ( $block['attrs']['level'] ?? 2 ) ) {
				++$h1_count;
			}
			if ( 'core/shortcode' === ( $block['blockName'] ?? '' ) ) {
				++$shortcode_count;
			}
		}
	);

	if ( 1 !== $h1_count ) {
		$errors[] = sprintf( '%s has %d H1 blocks; expected exactly one.', $slug, $h1_count );
	}
	if ( $shortcode_count ) {
		$errors[] = sprintf( '%s still contains %d Shortcode blocks.', $slug, $shortcode_count );
	}
	if ( ! wp_get_post_revisions( $page->ID ) ) {
		$errors[] = $slug . ' has no revision history.';
	}
	if ( 'page-landing' !== get_page_template_slug( $page->ID ) ) {
		$errors[] = $slug . ' is not assigned to the Designed Page template.';
	}

	$slug_template = get_stylesheet_directory() . '/templates/page-' . $slug . '.html';
	if ( file_exists( $slug_template ) ) {
		$errors[] = $slug . ' still has a slug-specific Page template.';
	}
}

foreach ( array( 'page.html', 'page-landing.html', 'front-page.html' ) as $template_file ) {
	$template_path = get_stylesheet_directory() . '/templates/' . $template_file;
	if ( ! file_exists( $template_path ) || ! str_contains( file_get_contents( $template_path ), 'wp:post-content' ) ) {
		$errors[] = $template_file . ' is missing its Post Content block.';
	}
}

$default_page_template = file_get_contents( get_stylesheet_directory() . '/templates/page.html' );
if ( ! str_contains( $default_page_template, 'wp:post-title' ) || ! str_contains( $default_page_template, 'tp-page-title-band' ) ) {
	$errors[] = 'page.html is missing its automatic branded Page title.';
}

$landing_page_template = file_get_contents( get_stylesheet_directory() . '/templates/page-landing.html' );
if ( str_contains( $landing_page_template, 'wp:post-title' ) ) {
	$errors[] = 'page-landing.html must not add a second Page title.';
}

foreach ( array( '404.html', 'archive.html', 'search.html', 'single.html' ) as $fallback_template ) {
	$template_path = get_stylesheet_directory() . '/templates/' . $fallback_template;
	if ( ! file_exists( $template_path ) || ! str_contains( file_get_contents( $template_path ), 'level":1' ) ) {
		$errors[] = $fallback_template . ' is missing or does not provide an H1-level title.';
	}
}

$theme_slug = get_stylesheet();
$forks      = get_posts(
	array(
		'post_type'      => array( 'wp_template', 'wp_template_part' ),
		'post_status'    => array( 'publish', 'draft' ),
		'posts_per_page' => -1,
		'tax_query'      => array(
			array(
				'taxonomy' => 'wp_theme',
				'field'    => 'slug',
				'terms'    => array( $theme_slug ),
			),
		),
	)
);
foreach ( $forks as $fork ) {
	$errors[] = sprintf( 'Active-theme database template fork: %s#%d (%s).', $fork->post_type, $fork->ID, $fork->post_name );
}

if ( '2026-08-17-v4' !== get_option( 'trinity_editable_content_migration' ) ) {
	$errors[] = 'The expected editable-content migration version is not recorded.';
}
if ( '2026-08-30-v1' !== get_option( 'trinity_theme_hardening_migration' ) ) {
	$errors[] = 'The expected theme-hardening migration version is not recorded.';
}
if ( ! post_type_exists( 'tp_event' ) || ! taxonomy_exists( 'tp_event_type' ) ) {
	$errors[] = 'The portable Event post type or Event Type taxonomy is unavailable.';
}
if ( ! WP_Block_Type_Registry::get_instance()->is_registered( 'trinity-preschool/weekly-events' ) ) {
	$errors[] = 'The Trinity Weekly Events block is not registered.';
}

$home = get_page_by_path( 'home', OBJECT, 'page' );
if ( ! $home || ! has_block( 'trinity-preschool/weekly-events', $home ) || ! has_block( 'contact-form-7/contact-form-selector', $home ) ) {
	$errors[] = 'Home is missing its weekly-events or Contact Form 7 selector block.';
}
foreach ( array( 'contact', 'schedule-a-tour' ) as $form_page_slug ) {
	$form_page = get_page_by_path( $form_page_slug, OBJECT, 'page' );
	if ( ! $form_page || ! has_block( 'contact-form-7/contact-form-selector', $form_page ) ) {
		$errors[] = $form_page_slug . ' is missing its Contact Form 7 selector block.';
	}
}

$navigation = get_page_by_path( 'primary-navigation', OBJECT, 'wp_navigation' );
$header     = file_get_contents( get_stylesheet_directory() . '/parts/header.html' );
if (
	! $navigation
	|| 'wp_navigation' !== $navigation->post_type
	|| 'Primary Navigation' !== $navigation->post_title
	|| ! str_contains( $header, 'tp-primary-nav' )
	|| str_contains( $header, '"ref":' )
	|| str_contains( $header, 'wp:navigation-link' )
	|| str_contains( $navigation->post_content, '"url":"#"' )
) {
	$errors[] = 'The Header is not connected portably to the named Primary Navigation entity or still contains inline/placeholder links.';
}

$footer = file_get_contents( get_stylesheet_directory() . '/parts/footer.html' );
foreach ( array( 'mailto:trinityepiscopalpreschool.org', 'https://www.instagram.com/', 'https://www.facebook.com/' ) as $placeholder_link ) {
	if ( str_contains( $footer, $placeholder_link ) ) {
		$errors[] = 'The Footer still contains a placeholder or malformed link: ' . $placeholder_link;
	}
}

foreach ( array( 'home', 'parent-corner' ) as $payment_page_slug ) {
	$payment_page = get_page_by_path( $payment_page_slug, OBJECT, 'page' );
	if ( $payment_page && str_contains( $payment_page->post_content, 'Make a secure online tuition payment through our parent portal.' ) ) {
		$errors[] = $payment_page_slug . ' still promises direct online tuition payment.';
	}
}

$hardening_css = file_get_contents( get_stylesheet_directory() . '/assets/css/theme-hardening.css' );
foreach ( array( '.tp-pattern-hero', '.tp-program-page', '.tp-resource-page', '.tp-contact-cta' ) as $starter_selector ) {
	if ( ! str_contains( $hardening_css, $starter_selector ) ) {
		$errors[] = 'Starter-pattern stylesheet is missing ' . $starter_selector . '.';
	}
}

$remaining_page_stats = array();
foreach ( array( 'get-involved', 'meet-the-director', 'meet-the-teachers' ) as $slug ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	if ( ! $page ) {
		continue;
	}

	$stats = array(
		'custom_html'        => 0,
		'content_only_locks' => 0,
		'named_roots'        => 0,
		'teacher_cards'      => 0,
		'named_teachers'     => 0,
		'teacher_bios'       => 0,
		'title_dividers'     => 0,
		'director_image_id'  => 0,
	);
	trinity_audit_walk_blocks(
		parse_blocks( $page->post_content ),
		function ( $block ) use ( &$stats, $slug ) {
			$block_name = $block['blockName'] ?? '';
			$class_name = $block['attrs']['className'] ?? '';
			$name       = $block['attrs']['metadata']['name'] ?? '';
			$classes    = preg_split( '/\s+/', trim( $class_name ) );

			if ( 'core/html' === $block_name ) {
				++$stats['custom_html'];
			}
			if ( 'contentOnly' === ( $block['attrs']['templateLock'] ?? '' ) ) {
				++$stats['content_only_locks'];
			}
			if (
				( 'get-involved' === $slug && in_array( 'tp-involved-page', $classes, true ) && 'Get Involved page' === $name )
				|| ( 'meet-the-director' === $slug && in_array( 'tp-director-band', $classes, true ) && 'Meet Our Director' === $name )
				|| ( 'meet-the-teachers' === $slug && in_array( 'tp-teachers-page', $classes, true ) && 'Meet The Teachers page' === $name )
			) {
				++$stats['named_roots'];
			}
			if ( 'meet-the-teachers' === $slug && in_array( 'tp-teacher-card', $classes, true ) ) {
				++$stats['teacher_cards'];
				if ( str_starts_with( $name, 'Teacher: ' ) ) {
					++$stats['named_teachers'];
				}
			}
			if ( 'meet-the-teachers' === $slug && 'core/details' === $block_name && in_array( 'tp-teacher-bio', $classes, true ) ) {
				++$stats['teacher_bios'];
			}
			if ( 'meet-the-teachers' === $slug && 'core/separator' === $block_name && in_array( 'tp-title-rule', $classes, true ) ) {
				++$stats['title_dividers'];
			}
			if ( 'meet-the-director' === $slug && 'core/image' === $block_name && in_array( 'tp-director-photo', $classes, true ) ) {
				$stats['director_image_id'] = (int) ( $block['attrs']['id'] ?? 0 );
			}
		}
	);

	$remaining_page_stats[ $slug ] = $stats;
	if ( $stats['custom_html'] ) {
		$errors[] = sprintf( '%s still contains %d Custom HTML block(s).', $slug, $stats['custom_html'] );
	}
	if ( $stats['content_only_locks'] ) {
		$errors[] = sprintf( '%s still contains %d broad content-only lock(s).', $slug, $stats['content_only_locks'] );
	}
	if ( 1 !== $stats['named_roots'] ) {
		$errors[] = $slug . ' is missing its editor-friendly root name.';
	}
}

$get_involved = get_page_by_path( 'get-involved', OBJECT, 'page' );
if ( $get_involved ) {
	$get_involved_html = do_blocks( $get_involved->post_content );
	foreach ( array( 'pfec', 'room-parent', 'preschool-board' ) as $anchor ) {
		if ( ! str_contains( $get_involved_html, 'id="' . $anchor . '"' ) || ! str_contains( $get_involved_html, 'href="#' . $anchor . '"' ) ) {
			$errors[] = 'get-involved is missing the matching link and section anchor for #' . $anchor . '.';
		}
	}
}

$teacher_stats = $remaining_page_stats['meet-the-teachers'] ?? array();
if (
	empty( $teacher_stats['teacher_cards'] )
	|| $teacher_stats['teacher_cards'] !== $teacher_stats['named_teachers']
	|| $teacher_stats['teacher_cards'] !== $teacher_stats['teacher_bios']
) {
	$errors[] = 'meet-the-teachers does not have one named native-block biography for every teacher card.';
}
if ( 1 !== ( $teacher_stats['title_dividers'] ?? 0 ) ) {
	$errors[] = 'meet-the-teachers is missing its native Separator title divider.';
}

$director_image_id = (int) ( $remaining_page_stats['meet-the-director']['director_image_id'] ?? 0 );
if ( ! $director_image_id || ! trim( get_post_meta( $director_image_id, '_wp_attachment_image_alt', true ) ) ) {
	$errors[] = 'meet-the-director is missing Media Library alt text for the Director portrait.';
}

$notes[] = sprintf( '%d published Pages checked.', count( $expected_pages ) );
$notes[] = sprintf( '%d published Events available.', (int) wp_count_posts( 'tp_event' )->publish );
$notes[] = sprintf( '%d active-theme database template forks found.', count( $forks ) );

foreach ( $notes as $note ) {
	WP_CLI::log( $note );
}

if ( $errors ) {
	foreach ( $errors as $error ) {
		WP_CLI::warning( $error );
	}
	WP_CLI::error( sprintf( 'Trinity release audit failed with %d issue(s).', count( $errors ) ) );
}

WP_CLI::success( 'Trinity release audit passed.' );
