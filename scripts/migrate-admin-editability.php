<?php
/**
 * One-time consistency pass for editor-friendly page structures.
 *
 * Run with: ddev wp eval-file scripts/migrate-admin-editability.php
 */

if ( ! class_exists( 'WP_CLI' ) ) {
	exit( "This migration must run through WP-CLI.\n" );
}

const TP_ADMIN_EDITABILITY_VERSION = '2026-09-14-v1';

if ( TP_ADMIN_EDITABILITY_VERSION === get_option( 'trinity_admin_editability_migration' ) ) {
	WP_CLI::error( 'The admin-editability migration has already completed.' );
}

if ( 'local' !== wp_get_environment_type() && 'true' !== getenv( 'IS_DDEV_PROJECT' ) ) {
	WP_CLI::error( 'This migration is intentionally restricted to the local environment.' );
}

function tp_admin_editability_has_class( array $block, $class_name ) {
	$classes = preg_split( '/\s+/', trim( $block['attrs']['className'] ?? '' ) );
	return in_array( $class_name, $classes, true );
}

function tp_admin_editability_first_text( array $block, $block_name ) {
	if ( $block_name === ( $block['blockName'] ?? '' ) ) {
		return trim(
			preg_replace(
				'/\s+/',
				' ',
				html_entity_decode( wp_strip_all_tags( $block['innerHTML'] ?? '' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' )
			)
		);
	}

	foreach ( $block['innerBlocks'] ?? array() as $inner_block ) {
		$text = tp_admin_editability_first_text( $inner_block, $block_name );
		if ( $text ) {
			return $text;
		}
	}

	return '';
}

function tp_admin_editability_short_label( $text, $words = 8 ) {
	$parts = preg_split( '/\s+/', trim( $text ) );
	return implode( ' ', array_slice( $parts, 0, $words ) );
}

function tp_admin_editability_group_name( array $block, $page_slug, array $ancestor_classes, $sibling_index ) {
	$parent_class = $ancestor_classes ? end( $ancestor_classes ) : '';
	$heading      = tp_admin_editability_first_text( $block, 'core/heading' );
	$paragraph    = tp_admin_editability_first_text( $block, 'core/paragraph' );
	$tag_name     = $block['attrs']['tagName'] ?? '';

	if ( 'home' === $page_slug && str_contains( $parent_class, 'tp-teacher-preview-grid' ) ) {
		return 0 === $sibling_index ? 'Teacher Preview Heading' : 'Teacher Preview Introduction';
	}
	if ( str_contains( $parent_class, 'tp-class-card' ) ) {
		return 'Program Heading';
	}
	if ( str_contains( $parent_class, 'tp-tuition-cta' ) ) {
		return 'Tour Invitation';
	}
	if ( str_contains( $parent_class, 'tp-lunch-details' ) && $heading ) {
		return 'Program Detail: ' . tp_admin_editability_short_label( $heading );
	}
	if ( str_contains( $parent_class, 'tp-lunch-alert' ) ) {
		return 'Eligibility Notice';
	}
	if ( str_contains( $parent_class, 'tp-lunch-fee' ) ) {
		return 'Fee Details';
	}
	if ( str_contains( $parent_class, 'tp-forms-section-head' ) ) {
		return 'Section Heading';
	}
	if ( str_contains( $parent_class, 'tp-forms-return' ) ) {
		return 'Return Instructions';
	}
	if ( 'header' === $tag_name && $heading ) {
		return 'Header: ' . tp_admin_editability_short_label( $heading );
	}
	if ( $heading ) {
		return 'Section: ' . tp_admin_editability_short_label( $heading );
	}
	if ( $paragraph ) {
		return 'Content: ' . tp_admin_editability_short_label( $paragraph, 6 );
	}

	return 'Layout Group';
}

function tp_admin_editability_set_image_alt( array $block, $alt_text ) {
	$escaped_alt = esc_attr( $alt_text );
	$replace_alt = static function ( $html ) use ( $escaped_alt ) {
		if ( null === $html ) {
			return null;
		}
		if ( preg_match( '/\s+alt=(["\']).*?\1/i', $html ) ) {
			return preg_replace( '/\s+alt=(["\']).*?\1/i', ' alt="' . $escaped_alt . '"', $html, 1 );
		}

		return preg_replace( '/<img\b/i', '<img alt="' . $escaped_alt . '"', $html, 1 );
	};

	$block['innerHTML']    = $replace_alt( $block['innerHTML'] ?? '' );
	$block['innerContent'] = array_map( $replace_alt, $block['innerContent'] ?? array() );
	return $block;
}

function tp_admin_editability_contact_methods_block() {
	$markup = <<<'BLOCKS'
<!-- wp:group {"className":"tp-contact-actions","metadata":{"name":"Contact Methods"},"layout":{"type":"default"}} -->
<div class="wp-block-group tp-contact-actions">
	<!-- wp:paragraph {"metadata":{"name":"Phone"}} -->
	<p><a href="tel:+18562351840">856-235-1840</a></p>
	<!-- /wp:paragraph -->
	<!-- wp:paragraph {"metadata":{"name":"Email"}} -->
	<p><a href="mailto:candonie@trinitymoorestown.org">candonie@trinitymoorestown.org</a></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
BLOCKS;

	return parse_blocks( $markup )[0];
}

function tp_admin_editability_transform_blocks( array $blocks, $page_slug, array $ancestor_classes = array() ) {
	foreach ( $blocks as $index => $block ) {
		if (
			'contact' === $page_slug
			&& 'core/paragraph' === ( $block['blockName'] ?? '' )
			&& tp_admin_editability_has_class( $block, 'tp-contact-actions' )
		) {
			$blocks[ $index ] = tp_admin_editability_contact_methods_block();
			continue;
		}

		$block_name = $block['blockName'] ?? '';
		$class_name = trim( $block['attrs']['className'] ?? '' );
		if (
			in_array( $block_name, array( 'core/group', 'core/columns', 'core/column' ), true )
			&& empty( $block['attrs']['metadata']['name'] )
		) {
			$block['attrs']['metadata']['name'] = tp_admin_editability_group_name(
				$block,
				$page_slug,
				$ancestor_classes,
				$index
			);
		}

		if (
			'meet-the-director' === $page_slug
			&& 'core/image' === $block_name
			&& tp_admin_editability_has_class( $block, 'tp-director-photo' )
		) {
			$block = tp_admin_editability_set_image_alt( $block, 'Christine Andonie, Preschool Director' );
			$image_id = (int) ( $block['attrs']['id'] ?? 0 );
			if ( $image_id ) {
				update_post_meta( $image_id, '_wp_attachment_image_alt', 'Christine Andonie, Preschool Director' );
			}
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			$next_ancestors = $ancestor_classes;
			$next_ancestors[] = $class_name;
			$block['innerBlocks'] = tp_admin_editability_transform_blocks(
				$block['innerBlocks'],
				$page_slug,
				$next_ancestors
			);
		}

		$blocks[ $index ] = $block;
	}

	return $blocks;
}

$updated_pages = array();
$pages = get_posts(
	array(
		'post_type'      => 'page',
		'post_status'    => array( 'publish', 'draft', 'private' ),
		'posts_per_page' => -1,
	)
);

foreach ( $pages as $page ) {
	$content = $page->post_content;
	if ( 'home' === $page->post_name ) {
		$content = str_replace( 'office. <br><br></p>', 'office.</p>', $content );
	}

	$content = serialize_blocks(
		tp_admin_editability_transform_blocks(
			parse_blocks( $content ),
			$page->post_name
		)
	);

	if ( $content === $page->post_content ) {
		continue;
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

	$updated_pages[] = $page->post_name . '#' . $page->ID;
}

update_option( 'trinity_admin_editability_migration', TP_ADMIN_EDITABILITY_VERSION, false );

WP_CLI::success(
	$updated_pages
		? 'Updated editor structures: ' . implode( ', ', $updated_pages )
		: 'No page structures needed changes.'
);
