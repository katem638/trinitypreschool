<?php
/**
 * One-time, guarded migration from content-heavy templates/HTML to editable blocks.
 *
 * Run with: ddev wp eval-file scripts/migrate-editable-content.php
 */

if ( ! class_exists( 'WP_CLI' ) ) {
	exit( "This migration must run through WP-CLI.\n" );
}

const TP_EDITABLE_MIGRATION_VERSION = '2026-08-17-v4';

if ( get_option( 'trinity_editable_content_migration' ) === TP_EDITABLE_MIGRATION_VERSION ) {
	WP_CLI::error( 'The editable-content migration has already completed. Restore the backup to run it again.' );
}

$tp_migration_previous_version = get_option( 'trinity_editable_content_migration' );
$tp_migration_cf7_repair_only  = in_array( $tp_migration_previous_version, array( '2026-08-17-v1', '2026-08-17-v2' ), true );
$tp_migration_p2_repair_only   = '2026-08-17-v3' === $tp_migration_previous_version;
$tp_migration_repair_only      = $tp_migration_cf7_repair_only || $tp_migration_p2_repair_only;

if ( $tp_migration_previous_version && ! $tp_migration_repair_only ) {
	WP_CLI::error( 'An unrecognized editable-content migration version is already recorded. Restore the backup before continuing.' );
}

if ( 'local' !== wp_get_environment_type() && 'true' !== getenv( 'IS_DDEV_PROJECT' ) ) {
	WP_CLI::error( 'This migration is intentionally restricted to the local environment.' );
}

if ( ! class_exists( 'DOMDocument' ) ) {
	WP_CLI::error( 'The PHP DOM extension is required for the HTML-to-block conversion.' );
}

function tp_migration_comment_name( $block_name ) {
	return str_starts_with( $block_name, 'core/' ) ? substr( $block_name, 5 ) : $block_name;
}

function tp_migration_attr_json( array $attributes ) {
	if ( empty( $attributes ) ) {
		return '';
	}

	return ' ' . wp_json_encode( $attributes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
}

function tp_migration_wrap_block( $block_name, array $attributes, $html ) {
	$name = tp_migration_comment_name( $block_name );
	return sprintf(
		"<!-- wp:%1\$s%2\$s -->\n%3\$s\n<!-- /wp:%1\$s -->",
		$name,
		tp_migration_attr_json( $attributes ),
		$html
	);
}

function tp_migration_self_closing_block( $block_name, array $attributes ) {
	return sprintf(
		'<!-- wp:%1$s%2$s /-->',
		tp_migration_comment_name( $block_name ),
		tp_migration_attr_json( $attributes )
	);
}

function tp_migration_dom_inner_html( DOMNode $node ) {
	$html = '';
	foreach ( $node->childNodes as $child ) {
		$html .= $node->ownerDocument->saveHTML( $child );
	}

	return trim( $html );
}

function tp_migration_dom_outer_html( DOMNode $node ) {
	return trim( $node->ownerDocument->saveHTML( $node ) );
}

function tp_migration_class_list( DOMElement $element ) {
	return trim( preg_replace( '/\s+/', ' ', $element->getAttribute( 'class' ) ) );
}

function tp_migration_metadata_name( $class_name, $fallback = '' ) {
	$classes = preg_split( '/\s+/', trim( $class_name ) );
	foreach ( $classes as $class ) {
		if ( str_starts_with( $class, 'tp-' ) && ! in_array( $class, array( 'tp-main', 'tp-inner' ), true ) ) {
			return ucwords( str_replace( '-', ' ', substr( $class, 3 ) ) );
		}
	}

	return $fallback;
}

function tp_migration_group_block( DOMElement $element ) {
	$original_tag = strtolower( $element->tagName );
	$tag_name     = in_array( $original_tag, array( 'section', 'article', 'aside', 'header', 'footer', 'nav', 'main' ), true ) ? $original_tag : 'div';
	$class_name   = tp_migration_class_list( $element );
	$class_parts  = array_filter( preg_split( '/\s+/', $class_name ) );
	$align        = '';

	if ( in_array( 'alignfull', $class_parts, true ) ) {
		$align       = 'full';
		$class_parts = array_diff( $class_parts, array( 'alignfull' ) );
	} elseif ( in_array( 'alignwide', $class_parts, true ) ) {
		$align       = 'wide';
		$class_parts = array_diff( $class_parts, array( 'alignwide' ) );
	}

	$class_name = implode( ' ', $class_parts );
	$name       = tp_migration_metadata_name( $class_name );
	$attributes = array();
	if ( 'div' !== $tag_name ) {
		$attributes['tagName'] = $tag_name;
	}
	if ( $align ) {
		$attributes['align'] = $align;
	}
	if ( $class_name ) {
		$attributes['className'] = $class_name;
	}
	if ( $element->hasAttribute( 'id' ) ) {
		$attributes['anchor'] = sanitize_title( $element->getAttribute( 'id' ) );
	}
	if ( $name ) {
		$attributes['metadata'] = array( 'name' => $name );
	}
	if ( $class_name && preg_match( '/(?:^|\s)tp-[^\s]*(?:card|hero)(?:\s|$)/', $class_name ) && ! preg_match( '/(?:copy|media|grid|inner|panel)/', $class_name ) ) {
		$attributes['templateLock'] = 'contentOnly';
	}
	$attributes['layout'] = array( 'type' => 'default' );

	$classes = trim( 'wp-block-group ' . ( $align ? 'align' . $align . ' ' : '' ) . $class_name );
	$id      = $element->hasAttribute( 'id' ) ? ' id="' . esc_attr( $element->getAttribute( 'id' ) ) . '"' : '';
	$inner   = '';

	foreach ( $element->childNodes as $child ) {
		$inner .= tp_migration_dom_node_to_blocks( $child );
	}

	$html = sprintf( '<%1$s class="%2$s"%3$s>%4$s</%1$s>', $tag_name, esc_attr( $classes ), $id, $inner );
	return tp_migration_wrap_block( 'core/group', $attributes, $html );
}

function tp_migration_rich_text_block( DOMElement $element ) {
	$tag        = strtolower( $element->tagName );
	$class_name = tp_migration_class_list( $element );
	$id         = $element->hasAttribute( 'id' ) ? sanitize_title( $element->getAttribute( 'id' ) ) : '';
	$inner      = tp_migration_dom_inner_html( $element );

	if ( preg_match( '/^h([1-6])$/', $tag, $matches ) ) {
		$level      = (int) $matches[1];
		$attributes = array();
		if ( 2 !== $level ) {
			$attributes['level'] = $level;
		}
		if ( $class_name ) {
			$attributes['className'] = $class_name;
		}
		if ( $id ) {
			$attributes['anchor'] = $id;
		}
		$heading_class = trim( 'wp-block-heading ' . $class_name );
		$html = sprintf( '<h%1$d class="%2$s"%3$s>%4$s</h%1$d>', $level, esc_attr( $heading_class ), $id ? ' id="' . esc_attr( $id ) . '"' : '', $inner );
		return tp_migration_wrap_block( 'core/heading', $attributes, $html );
	}

	$attributes = array();
	if ( $class_name ) {
		$attributes['className'] = $class_name;
	}
	$html = '<p' . ( $class_name ? ' class="' . esc_attr( $class_name ) . '"' : '' ) . '>' . $inner . '</p>';
	return tp_migration_wrap_block( 'core/paragraph', $attributes, $html );
}

function tp_migration_inline_element_block( DOMElement $element ) {
	if ( 'true' === strtolower( $element->getAttribute( 'aria-hidden' ) ) ) {
		return tp_migration_wrap_block( 'core/html', array(), tp_migration_dom_outer_html( $element ) );
	}

	$class_name = tp_migration_class_list( $element );
	$attributes = $class_name ? array( 'className' => $class_name ) : array();
	$html       = '<p' . ( $class_name ? ' class="' . esc_attr( $class_name ) . '"' : '' ) . '>' . tp_migration_dom_outer_html( $element ) . '</p>';
	return tp_migration_wrap_block( 'core/paragraph', $attributes, $html );
}

function tp_migration_list_block( DOMElement $element ) {
	$ordered    = 'ol' === strtolower( $element->tagName );
	$class_name = tp_migration_class_list( $element );
	$attributes = array();
	if ( $ordered ) {
		$attributes['ordered'] = true;
	}
	if ( $class_name ) {
		$attributes['className'] = $class_name;
	}

	$list_tag = $ordered ? 'ol' : 'ul';
	$items    = '';
	foreach ( $element->childNodes as $child ) {
		if ( $child instanceof DOMElement && 'li' === strtolower( $child->tagName ) ) {
			$items .= tp_migration_wrap_block( 'core/list-item', array(), '<li>' . tp_migration_dom_inner_html( $child ) . '</li>' );
		}
	}

	$html = sprintf( '<%1$s class="%2$s">%3$s</%1$s>', $list_tag, esc_attr( trim( 'wp-block-list ' . $class_name ) ), $items );
	return tp_migration_wrap_block( 'core/list', $attributes, $html );
}

function tp_migration_details_block( DOMElement $element ) {
	$class_name = tp_migration_class_list( $element );
	$attributes = $class_name ? array( 'className' => $class_name ) : array();
	$summary    = '';
	$inner      = '';

	foreach ( $element->childNodes as $child ) {
		if ( $child instanceof DOMElement && 'summary' === strtolower( $child->tagName ) ) {
			$summary = tp_migration_dom_inner_html( $child );
			continue;
		}
		$inner .= tp_migration_dom_node_to_blocks( $child );
	}

	if ( $summary ) {
		$attributes['metadata'] = array( 'name' => wp_strip_all_tags( $summary ) );
	}
	$html = '<details class="' . esc_attr( trim( 'wp-block-details ' . $class_name ) ) . '"><summary>' . $summary . '</summary>' . $inner . '</details>';
	return tp_migration_wrap_block( 'core/details', $attributes, $html );
}

function tp_migration_description_list_block( DOMElement $element ) {
	$class_name = trim( tp_migration_class_list( $element ) . ' tp-description-list' );
	$items      = '';
	foreach ( $element->childNodes as $child ) {
		if ( ! $child instanceof DOMElement ) {
			continue;
		}
		$item_inner = '';
		foreach ( $child->childNodes as $value_node ) {
			if ( ! $value_node instanceof DOMElement ) {
				continue;
			}
			if ( 'dt' === strtolower( $value_node->tagName ) ) {
				$item_inner .= tp_migration_wrap_block( 'core/paragraph', array( 'className' => 'tp-description-term' ), '<p class="tp-description-term">' . tp_migration_dom_inner_html( $value_node ) . '</p>' );
			} elseif ( 'dd' === strtolower( $value_node->tagName ) ) {
				$item_inner .= tp_migration_wrap_block( 'core/paragraph', array( 'className' => 'tp-description-value' ), '<p class="tp-description-value">' . tp_migration_dom_inner_html( $value_node ) . '</p>' );
			}
		}
		$items .= tp_migration_wrap_block(
			'core/group',
			array( 'className' => 'tp-description-item', 'metadata' => array( 'name' => 'Detail' ), 'templateLock' => 'contentOnly', 'layout' => array( 'type' => 'default' ) ),
			'<div class="wp-block-group tp-description-item">' . $item_inner . '</div>'
		);
	}

	return tp_migration_wrap_block(
		'core/group',
		array( 'className' => $class_name, 'metadata' => array( 'name' => 'Details' ), 'layout' => array( 'type' => 'default' ) ),
		'<div class="wp-block-group ' . esc_attr( $class_name ) . '">' . $items . '</div>'
	);
}

function tp_migration_attachment_for_url( $url ) {
	$attachment_id = attachment_url_to_postid( $url );
	if ( $attachment_id ) {
		return get_post( $attachment_id );
	}

	$path = wp_parse_url( $url, PHP_URL_PATH );
	$slug = pathinfo( basename( (string) $path ), PATHINFO_FILENAME );
	$posts = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'name'           => sanitize_title( $slug ),
			'posts_per_page' => 1,
		)
	);

	return $posts ? $posts[0] : null;
}

function tp_migration_file_block( DOMElement $element ) {
	$url        = $element->getAttribute( 'href' );
	$attachment = tp_migration_attachment_for_url( $url );
	$class_name = tp_migration_class_list( $element );
	$label      = trim( preg_replace( '/\s+/', ' ', $element->textContent ) );
	$title      = $attachment ? get_the_title( $attachment ) : $label;
	$attributes = array(
		'href'               => $attachment ? wp_get_attachment_url( $attachment->ID ) : $url,
		'showDownloadButton' => true,
		'displayPreview'     => false,
		'className'          => $class_name,
		'metadata'           => array( 'name' => $title ),
	);
	if ( $attachment ) {
		$attributes['id'] = $attachment->ID;
	}
	$href = esc_url( $attributes['href'] );
	$html = '<div class="wp-block-file ' . esc_attr( $class_name ) . '"><a href="' . $href . '">' . esc_html( $title ) . '</a><a href="' . $href . '" class="wp-block-file__button wp-element-button" download>' . esc_html( $label ?: __( 'Download', 'trinity-preschool' ) ) . '</a></div>';
	return tp_migration_wrap_block( 'core/file', $attributes, $html );
}

function tp_migration_dom_node_to_blocks( DOMNode $node ) {
	if ( XML_TEXT_NODE === $node->nodeType ) {
		$text = trim( $node->textContent );
		return '' === $text ? '' : tp_migration_wrap_block( 'core/paragraph', array(), '<p>' . esc_html( $text ) . '</p>' );
	}

	if ( ! $node instanceof DOMElement ) {
		return '';
	}

	if ( $node->hasAttribute( 'data-tp-form-placeholder' ) ) {
		return tp_migration_cf7_block_markup( 'tour' === $node->getAttribute( 'data-tp-form-placeholder' ) ? 22 : 21 );
	}

	$tag = strtolower( $node->tagName );
	if ( 'true' === strtolower( $node->getAttribute( 'aria-hidden' ) ) && in_array( $tag, array( 'div', 'span', 'svg' ), true ) ) {
		return tp_migration_wrap_block( 'core/html', array(), tp_migration_dom_outer_html( $node ) );
	}

	if ( in_array( $tag, array( 'div', 'section', 'article', 'aside', 'header', 'footer', 'nav', 'main' ), true ) ) {
		return tp_migration_group_block( $node );
	}
	if ( 'figure' === $tag ) {
		return tp_migration_group_block( $node );
	}
	if ( 'p' === $tag || preg_match( '/^h[1-6]$/', $tag ) ) {
		return tp_migration_rich_text_block( $node );
	}
	if ( in_array( $tag, array( 'span', 'small', 'strong', 'figcaption', 'mark' ), true ) ) {
		return tp_migration_inline_element_block( $node );
	}
	if ( in_array( $tag, array( 'ul', 'ol' ), true ) ) {
		return tp_migration_list_block( $node );
	}
	if ( 'details' === $tag ) {
		return tp_migration_details_block( $node );
	}
	if ( 'dl' === $tag ) {
		return tp_migration_description_list_block( $node );
	}
	if ( 'hr' === $tag ) {
		return tp_migration_wrap_block( 'core/separator', array(), '<hr class="wp-block-separator has-alpha-channel-opacity"/>' );
	}
	if ( 'a' === $tag && ( $node->hasAttribute( 'download' ) || str_contains( tp_migration_class_list( $node ), 'tp-form-download' ) ) ) {
		return tp_migration_file_block( $node );
	}
	if ( 'a' === $tag ) {
		return tp_migration_wrap_block( 'core/paragraph', array(), '<p>' . tp_migration_dom_outer_html( $node ) . '</p>' );
	}

	return tp_migration_wrap_block( 'core/html', array(), tp_migration_dom_outer_html( $node ) );
}

function tp_migration_html_to_blocks( $html ) {
	$document = new DOMDocument();
	$previous = libxml_use_internal_errors( true );
	$document->loadHTML(
		'<!doctype html><html><head><meta charset="utf-8"></head><body><div id="tp-migration-root">' . $html . '</div></body></html>',
		LIBXML_HTML_NODEFDTD
	);
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );
	$root   = $document->getElementById( 'tp-migration-root' );
	$blocks = '';
	foreach ( $root->childNodes as $child ) {
		$blocks .= tp_migration_dom_node_to_blocks( $child );
	}

	return trim( $blocks );
}

function tp_migration_cf7_block_markup( $form_id ) {
	$form = function_exists( 'wpcf7_contact_form' ) ? wpcf7_contact_form( $form_id ) : null;
	if ( ! $form ) {
		WP_CLI::error( sprintf( 'Contact Form 7 form %d is unavailable.', $form_id ) );
	}

	$attributes = array(
		'id'        => (int) $form->id(),
		'hash'      => $form->hash(),
		'title'     => $form->title(),
		'htmlClass' => 22 === (int) $form_id ? 'tp-tour-cf7' : 'tp-contact-cf7',
	);

	$shortcode = sprintf(
		'[contact-form-7 id="%1$s" title="%2$s" html_class="%3$s"]',
		esc_attr( $attributes['hash'] ),
		esc_attr( $attributes['title'] ),
		esc_attr( $attributes['htmlClass'] )
	);

	return tp_migration_wrap_block(
		'contact-form-7/contact-form-selector',
		$attributes,
		'<div class="wp-block-contact-form-7-contact-form-selector">' . $shortcode . '</div>'
	);
}

function tp_migration_cf7_block_array( $form_id ) {
	$blocks = parse_blocks( tp_migration_cf7_block_markup( $form_id ) );
	return $blocks[0];
}

function tp_migration_extract_main_blocks( $content ) {
	foreach ( parse_blocks( $content ) as $block ) {
		if ( 'core/group' === $block['blockName'] && ( 'main' === ( $block['attrs']['tagName'] ?? '' ) || str_contains( $block['attrs']['className'] ?? '', 'tp-main' ) ) ) {
			return $block['innerBlocks'];
		}
	}

	WP_CLI::error( 'Could not find the main content group in a template.' );
}

function tp_migration_template_content( $filename ) {
	$path = __DIR__ . '/migration-source/' . basename( $filename );
	if ( ! file_exists( $path ) ) {
		WP_CLI::error( 'Missing frozen migration source: ' . $path );
	}

	return file_get_contents( $path );
}

function tp_migration_week_section_block() {
	$markup = <<<'BLOCKS'
<!-- wp:group {"align":"full","tagName":"section","className":"tp-week-events","metadata":{"name":"This week at Trinity"},"layout":{"type":"default"}} -->
<section class="wp-block-group alignfull tp-week-events">
	<!-- wp:group {"align":"wide","className":"tp-week-events-inner","metadata":{"name":"Weekly events content"},"layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide tp-week-events-inner">
		<!-- wp:group {"className":"tp-week-events-header","metadata":{"name":"Weekly events heading"},"templateLock":"contentOnly","layout":{"type":"default"}} -->
		<div class="wp-block-group tp-week-events-header">
			<!-- wp:group {"className":"tp-week-events-title","layout":{"type":"default"}} -->
			<div class="wp-block-group tp-week-events-title">
				<!-- wp:paragraph {"className":"tp-section-eyebrow"} -->
				<p class="tp-section-eyebrow">This week at Trinity</p>
				<!-- /wp:paragraph -->
				<!-- wp:heading {"anchor":"tp-week-events-heading"} -->
				<h2 class="wp-block-heading" id="tp-week-events-heading">What’s <em>happening</em> this week.</h2>
				<!-- /wp:heading -->
			</div>
			<!-- /wp:group -->
			<!-- wp:buttons {"className":"tp-week-events-actions"} -->
			<div class="wp-block-buttons tp-week-events-actions"><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/events/">Full calendar</a></div><!-- /wp:button --></div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->
		<!-- wp:trinity-preschool/weekly-events /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
BLOCKS;
	return parse_blocks( $markup )[0];
}

function tp_migration_update_image_block( array $block ) {
	if ( 'core/image' !== $block['blockName'] || ! preg_match( '/<img[^>]+src=["\']([^"\']+)/', $block['innerHTML'], $matches ) ) {
		return $block;
	}

	$attachment = tp_migration_attachment_for_url( html_entity_decode( $matches[1] ) );
	if ( ! $attachment ) {
		return $block;
	}

	$url = wp_get_attachment_url( $attachment->ID );
	$block['attrs']['id'] = $attachment->ID;
	$block['attrs']['sizeSlug'] = $block['attrs']['sizeSlug'] ?? 'full';
	$block['attrs']['linkDestination'] = $block['attrs']['linkDestination'] ?? 'none';
	$block['innerHTML'] = str_replace( $matches[1], esc_url( $url ), $block['innerHTML'] );
	$block['innerContent'] = array_map(
		function ( $fragment ) use ( $matches, $url, $attachment ) {
			if ( null === $fragment ) {
				return null;
			}
			$fragment = str_replace( $matches[1], esc_url( $url ), $fragment );
			if ( ! str_contains( $fragment, 'wp-image-' . $attachment->ID ) ) {
				$fragment = preg_replace( '/<img\s+/', '<img class="wp-image-' . $attachment->ID . '" ', $fragment, 1 );
			}
			return $fragment;
		},
		$block['innerContent']
	);
	if ( ! str_contains( $block['innerHTML'], 'wp-image-' . $attachment->ID ) ) {
		$block['innerHTML'] = preg_replace( '/<img\s+/', '<img class="wp-image-' . $attachment->ID . '" ', $block['innerHTML'], 1 );
	}

	return $block;
}

function tp_migration_transform_block( array $block, $context ) {
	if (
		'home' === $context
		&& 'core/group' === $block['blockName']
		&& in_array( 'tp-week-events', preg_split( '/\s+/', $block['attrs']['className'] ?? '' ), true )
	) {
		return tp_migration_week_section_block();
	}
	if ( 'contact-form-7/contact-form-selector' === $block['blockName'] ) {
		return tp_migration_cf7_block_array( (int) ( $block['attrs']['id'] ?? 0 ) );
	}
	if ( 'core/shortcode' === $block['blockName'] && str_contains( $block['innerHTML'], 'contact-form-7' ) ) {
		return tp_migration_cf7_block_array( str_contains( $block['innerHTML'], 'Schedule a Tour' ) ? 22 : 21 );
	}
	if ( 'trinity-preschool/weekly-events' === $block['blockName'] && 'home' === $context ) {
		return tp_migration_week_section_block();
	}

	$block = tp_migration_update_image_block( $block );
	if ( ! empty( $block['innerBlocks'] ) ) {
		foreach ( $block['innerBlocks'] as $index => $inner_block ) {
			$block['innerBlocks'][ $index ] = tp_migration_transform_block( $inner_block, $context );
		}
	}

	$class_name = $block['attrs']['className'] ?? '';
	if ( $class_name && in_array( $block['blockName'], array( 'core/group', 'core/columns', 'core/column' ), true ) ) {
		$name = tp_migration_metadata_name( $class_name );
		if ( $name ) {
			$block['attrs']['metadata']['name'] = $name;
		}
		if ( preg_match( '/(?:^|\s)tp-[^\s]*(?:card|hero)(?:\s|$)/', $class_name ) && ! preg_match( '/(?:copy|media|grid|inner|panel)/', $class_name ) ) {
			$block['attrs']['templateLock'] = 'contentOnly';
		}
	}

	return $block;
}

function tp_migration_transform_top_level_blocks( array $blocks, $context ) {
	$expanded = array();
	foreach ( $blocks as $block ) {
		if ( 'core/html' === $block['blockName'] && trim( $block['innerHTML'] ) ) {
			foreach ( parse_blocks( tp_migration_html_to_blocks( $block['innerHTML'] ) ) as $converted ) {
				if ( $converted['blockName'] ) {
					$expanded[] = tp_migration_transform_block( $converted, $context );
				}
			}
			continue;
		}
		$expanded[] = tp_migration_transform_block( $block, $context );
	}

	return $expanded;
}

function tp_migration_block_has_class( array $block, $class_name ) {
	$classes = preg_split( '/\s+/', trim( $block['attrs']['className'] ?? '' ) );
	return in_array( $class_name, $classes, true );
}

function tp_migration_first_descendant_text( array $block, $block_name ) {
	if ( $block_name === ( $block['blockName'] ?? '' ) ) {
		return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $block['innerHTML'] ?? '' ) ) );
	}

	foreach ( $block['innerBlocks'] ?? array() as $inner_block ) {
		$text = tp_migration_first_descendant_text( $inner_block, $block_name );
		if ( $text ) {
			return $text;
		}
	}

	return '';
}

function tp_migration_name_block( array $block, $name ) {
	if ( $name ) {
		$block['attrs']['metadata']['name'] = $name;
	}

	return $block;
}

function tp_migration_set_image_alt( array $block, $alt_text ) {
	$escaped_alt = esc_attr( $alt_text );
	$replace_alt = static function ( $html ) use ( $escaped_alt ) {
		if ( null === $html ) {
			return null;
		}
		if ( preg_match( '/\salt=(["\']).*?\1/i', $html ) ) {
			return preg_replace( '/\salt=(["\']).*?\1/i', ' alt="' . $escaped_alt . '"', $html, 1 );
		}

		return preg_replace( '/<img\b/i', '<img alt="' . $escaped_alt . '"', $html, 1 );
	};

	$block['innerHTML']    = $replace_alt( $block['innerHTML'] ?? '' );
	$block['innerContent'] = array_map( $replace_alt, $block['innerContent'] ?? array() );
	return $block;
}

function tp_migration_editor_page_block( array $block, $context ) {
	if (
		'meet-the-teachers' === $context
		&& 'core/html' === ( $block['blockName'] ?? '' )
		&& str_contains( $block['innerHTML'] ?? '', 'tp-title-rule' )
	) {
		return parse_blocks(
			'<!-- wp:separator {"className":"tp-title-rule","metadata":{"name":"Title divider"}} --><hr class="wp-block-separator has-alpha-channel-opacity tp-title-rule"/><!-- /wp:separator -->'
		)[0];
	}

	foreach ( $block['innerBlocks'] ?? array() as $index => $inner_block ) {
		$block['innerBlocks'][ $index ] = tp_migration_editor_page_block( $inner_block, $context );
	}

	// These three pages need ordinary block selection, insertion, and removal in
	// addition to rich-text editing. Their earlier broad content-only locks made
	// routine staff and volunteer updates unnecessarily difficult.
	unset( $block['attrs']['templateLock'] );

	if ( 'get-involved' === $context ) {
		if ( tp_migration_block_has_class( $block, 'tp-involved-page' ) ) {
			return tp_migration_name_block( $block, 'Get Involved page' );
		}
		if ( tp_migration_block_has_class( $block, 'tp-involved-hero' ) ) {
			return tp_migration_name_block( $block, 'Hero: Get Involved' );
		}
		if ( tp_migration_block_has_class( $block, 'tp-involved-quick-links' ) ) {
			return tp_migration_name_block( $block, 'Ways to get involved' );
		}
		if ( tp_migration_block_has_class( $block, 'tp-involved-path-card' ) ) {
			return tp_migration_name_block( $block, 'Path: ' . tp_migration_first_descendant_text( $block, 'core/heading' ) );
		}
		if ( tp_migration_block_has_class( $block, 'tp-involved-pfec' ) ) {
			return tp_migration_name_block( $block, 'Section: PFEC' );
		}
		if ( tp_migration_block_has_class( $block, 'tp-involved-room-parent' ) ) {
			return tp_migration_name_block( $block, 'Section: Room Parent' );
		}
		if ( tp_migration_block_has_class( $block, 'tp-involved-board' ) ) {
			return tp_migration_name_block( $block, 'Section: Preschool Board' );
		}
		if ( tp_migration_block_has_class( $block, 'tp-involved-story-card' ) ) {
			return tp_migration_name_block( $block, 'PFEC overview' );
		}
		if ( tp_migration_block_has_class( $block, 'tp-involved-contact-coral' ) ) {
			return tp_migration_name_block( $block, 'PFEC contact' );
		}
		if ( tp_migration_block_has_class( $block, 'tp-involved-contact-blue' ) ) {
			return tp_migration_name_block( $block, 'Room Parent contact' );
		}
		if ( tp_migration_block_has_class( $block, 'tp-involved-list-card' ) ) {
			return tp_migration_name_block( $block, tp_migration_first_descendant_text( $block, 'core/heading' ) );
		}
		if ( tp_migration_block_has_class( $block, 'tp-involved-board-card' ) ) {
			return tp_migration_name_block( $block, 'Preschool Board overview' );
		}
	}

	if ( 'meet-the-director' === $context ) {
		if ( tp_migration_block_has_class( $block, 'tp-director-band' ) ) {
			$block = tp_migration_name_block( $block, 'Meet Our Director' );
			$block['attrs']['lock'] = array( 'remove' => true );
			return $block;
		}
		if ( tp_migration_block_has_class( $block, 'tp-director-card' ) ) {
			return tp_migration_name_block( $block, 'Director profile' );
		}
		if ( tp_migration_block_has_class( $block, 'tp-director-copy' ) ) {
			return tp_migration_name_block( $block, 'Director biography' );
		}
		if ( 'core/image' === ( $block['blockName'] ?? '' ) && tp_migration_block_has_class( $block, 'tp-director-photo' ) ) {
			$block = tp_migration_name_block( $block, 'Director portrait' );
			return tp_migration_set_image_alt( $block, 'Christine Andonie, Preschool Director' );
		}
	}

	if ( 'meet-the-teachers' === $context ) {
		if ( tp_migration_block_has_class( $block, 'tp-teachers-page' ) ) {
			return tp_migration_name_block( $block, 'Meet The Teachers page' );
		}
		if ( tp_migration_block_has_class( $block, 'tp-teacher-grid' ) ) {
			return tp_migration_name_block( $block, 'Classrooms and teachers' );
		}
		if ( tp_migration_block_has_class( $block, 'tp-classroom-section' ) ) {
			$room = tp_migration_first_descendant_text( $block, 'core/heading' );
			return tp_migration_name_block( $block, 'Classroom: ' . $room );
		}
		if ( tp_migration_block_has_class( $block, 'tp-classroom-head' ) ) {
			return tp_migration_name_block( $block, 'Classroom heading' );
		}
		if ( tp_migration_block_has_class( $block, 'tp-classroom-teachers' ) ) {
			return tp_migration_name_block( $block, 'Teacher cards' );
		}
		if ( tp_migration_block_has_class( $block, 'tp-teacher-card' ) ) {
			$teacher_name = tp_migration_first_descendant_text( $block, 'core/heading' );
			return tp_migration_name_block( $block, 'Teacher: ' . $teacher_name );
		}
		if ( tp_migration_block_has_class( $block, 'tp-teacher-placeholder' ) ) {
			return tp_migration_name_block( $block, 'Headshot placeholder' );
		}
		if ( tp_migration_block_has_class( $block, 'tp-teacher-card-body' ) ) {
			return tp_migration_name_block( $block, 'Profile content' );
		}
		if ( tp_migration_block_has_class( $block, 'tp-teacher-bio' ) ) {
			return tp_migration_name_block( $block, 'Biography / Q&A' );
		}
		if ( tp_migration_block_has_class( $block, 'tp-teacher-qa' ) ) {
			return tp_migration_name_block( $block, 'Biography answers' );
		}
		if ( 'core/image' === ( $block['blockName'] ?? '' ) ) {
			return tp_migration_name_block( $block, 'Teacher portrait' );
		}
	}

	return $block;
}

function tp_migration_editor_page_content( $content, $context ) {
	$blocks = parse_blocks( $content );
	foreach ( $blocks as $index => $block ) {
		$blocks[ $index ] = tp_migration_editor_page_block( $block, $context );
	}

	return serialize_blocks( $blocks );
}

function tp_migration_update_director_media_alt( array &$results ) {
	$page = get_page_by_path( 'meet-the-director', OBJECT, 'page' );
	if ( ! $page ) {
		WP_CLI::error( 'Missing page while updating the Director portrait alt text.' );
	}

	$attachment_id = 0;
	foreach ( parse_blocks( $page->post_content ) as $block ) {
		$stack = array( $block );
		while ( $stack ) {
			$current = array_pop( $stack );
			if ( 'core/image' === ( $current['blockName'] ?? '' ) && tp_migration_block_has_class( $current, 'tp-director-photo' ) ) {
				$attachment_id = (int) ( $current['attrs']['id'] ?? 0 );
				break 2;
			}
			foreach ( $current['innerBlocks'] ?? array() as $inner_block ) {
				$stack[] = $inner_block;
			}
		}
	}

	if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
		WP_CLI::error( 'The Director portrait is not connected to a Media Library attachment.' );
	}

	update_post_meta( $attachment_id, '_wp_attachment_image_alt', 'Christine Andonie, Preschool Director' );
	$results[] = sprintf( 'media:director-portrait#%d', $attachment_id );
}

function tp_migration_content_from_template( $relative_path, $context ) {
	$blocks = tp_migration_extract_main_blocks( tp_migration_template_content( $relative_path ) );
	return serialize_blocks( tp_migration_transform_top_level_blocks( $blocks, $context ) );
}

function tp_migration_schedule_content() {
	$blocks = tp_migration_extract_main_blocks( tp_migration_template_content( 'page-schedule-a-tour.html' ) );
	$html   = '';
	foreach ( $blocks as $block ) {
		if ( 'core/html' === $block['blockName'] ) {
			$html .= $block['innerHTML'];
		} elseif ( 'core/shortcode' === $block['blockName'] ) {
			$html .= '<div data-tp-form-placeholder="tour"></div>';
		}
	}

	return serialize_blocks( tp_migration_transform_top_level_blocks( parse_blocks( tp_migration_html_to_blocks( $html ) ), 'schedule-a-tour' ) );
}

function tp_migration_standard_page( $title, array $sections, array $buttons = array() ) {
	$content = '<!-- wp:group {"align":"full","className":"tp-standard-page","metadata":{"name":"' . esc_attr( $title ) . '"},"layout":{"type":"constrained"}} --><div class="wp-block-group alignfull tp-standard-page"><!-- wp:group {"className":"tp-standard-inner","metadata":{"name":"Page content"},"layout":{"type":"default"}} --><div class="wp-block-group tp-standard-inner">';
	$content .= '<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">' . esc_html( $title ) . '</h1><!-- /wp:heading -->';
	foreach ( $sections as $section ) {
		if ( ! empty( $section['heading'] ) ) {
			$content .= '<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html( $section['heading'] ) . '</h2><!-- /wp:heading -->';
		}
		foreach ( $section['paragraphs'] as $paragraph ) {
			$content .= '<!-- wp:paragraph --><p>' . wp_kses_post( $paragraph ) . '</p><!-- /wp:paragraph -->';
		}
	}
	if ( $buttons ) {
		$content .= '<!-- wp:buttons --><div class="wp-block-buttons">';
		foreach ( $buttons as $button ) {
			$content .= '<!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $button['url'] ) . '">' . esc_html( $button['label'] ) . '</a></div><!-- /wp:button -->';
		}
		$content .= '</div><!-- /wp:buttons -->';
	}
	$content .= '</div><!-- /wp:group --></div><!-- /wp:group -->';
	return $content;
}

function tp_migration_prepend_h1_if_missing( $content, $title ) {
	if ( preg_match( '/<h1\b/i', do_blocks( $content ) ) ) {
		return $content;
	}

	$heading = '<!-- wp:group {"align":"full","className":"tp-page-title-band","metadata":{"name":"Page title"},"templateLock":"contentOnly","layout":{"type":"constrained"}} --><div class="wp-block-group alignfull tp-page-title-band"><!-- wp:heading {"level":1,"align":"wide"} --><h1 class="wp-block-heading alignwide">' . esc_html( $title ) . '</h1><!-- /wp:heading --></div><!-- /wp:group -->';
	return $heading . "\n" . $content;
}

function tp_migration_update_page( $slug, $content, array &$results ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	if ( ! $page ) {
		WP_CLI::error( 'Missing page: ' . $slug );
	}

	$content = tp_migration_prepend_h1_if_missing( $content, $page->post_title );
	$result  = wp_update_post(
		array(
			'ID'           => $page->ID,
			'post_content' => $content,
		),
		true
	);
	if ( is_wp_error( $result ) ) {
		WP_CLI::error( $result->get_error_message() );
	}

	$results[] = sprintf( 'page:%s#%d', $slug, $page->ID );
}

function tp_migration_update_tour_form( array &$results ) {
	$form = function_exists( 'wpcf7_contact_form' ) ? wpcf7_contact_form( 22 ) : null;
	if ( ! $form ) {
		WP_CLI::error( 'Schedule a Tour Request form #22 is unavailable.' );
	}

	$properties = $form->get_properties();
	$markup     = $properties['form'];
	$markup     = preg_replace( '/\s+aria-controls="tp-tour-sibling-fields"/', '', $markup );
	$markup     = str_replace( 'tp-tour-child-card-sibling" id="tp-tour-sibling-fields" hidden', 'tp-tour-child-card-sibling tp-tour-sibling-fields"', $markup );
	$markup     = str_replace( '"Book my tour"', '"Request my tour"', $markup );
	$properties['form'] = $markup;
	$form->set_properties( $properties );
	$form->save();
	$results[] = 'form:schedule-a-tour#22';
}

function tp_migration_event_types( array &$results ) {
	$default_tones = array(
		'closed'     => 'coral',
		'classroom'  => 'gold',
		'all-school' => 'sky',
		'parents'    => 'coral',
		'special'    => 'coral',
		'tour'       => 'gold',
		'family'     => 'sky',
		'event'      => 'navy',
	);
	$events = get_posts( array( 'post_type' => 'tp_event', 'post_status' => 'any', 'posts_per_page' => -1 ) );
	foreach ( $events as $event ) {
		$label = trim( get_post_meta( $event->ID, 'tp_event_label', true ) );
		$tone  = sanitize_key( get_post_meta( $event->ID, 'tp_event_tone', true ) );
		if ( ! $label ) {
			$label = str_contains( strtolower( $event->post_title ), 'picnic' ) ? 'Family' : ( str_contains( strtolower( $event->post_title ), 'summer fun' ) ? 'Special' : 'Event' );
		}
		$term = term_exists( $label, 'tp_event_type' );
		if ( ! $term ) {
			$term = wp_insert_term( $label, 'tp_event_type' );
		}
		if ( is_wp_error( $term ) ) {
			WP_CLI::error( $term->get_error_message() );
		}
		$term_id = (int) ( is_array( $term ) ? $term['term_id'] : $term );
		$term_slug = get_term( $term_id, 'tp_event_type' )->slug;
		$tone = in_array( $tone, array( 'coral', 'gold', 'sky', 'navy' ), true ) ? $tone : ( $default_tones[ $term_slug ] ?? 'navy' );
		update_term_meta( $term_id, 'trinity_event_tone', $tone );
		wp_set_object_terms( $event->ID, array( $term_id ), 'tp_event_type', false );
		delete_post_meta( $event->ID, 'tp_event_label' );
		delete_post_meta( $event->ID, 'tp_event_tone' );
	}
	$results[] = 'events:taxonomy-migrated';
}

function tp_migration_delete_template_post( $post_id, $post_type, $slug, array &$results ) {
	$post = get_post( $post_id );
	if ( ! $post || $post_type !== $post->post_type || $slug !== $post->post_name ) {
		WP_CLI::warning( sprintf( 'Did not remove expected %s customization #%d (%s).', $post_type, $post_id, $slug ) );
		return;
	}

	wp_delete_post( $post_id, true );
	$results[] = sprintf( 'reset:%s#%d', $slug, $post_id );
}

$results = array();

if ( $tp_migration_repair_only ) {
	if ( $tp_migration_cf7_repair_only ) {
		foreach ( array( 'home', 'contact', 'schedule-a-tour' ) as $slug ) {
			$page = get_page_by_path( $slug, OBJECT, 'page' );
			if ( ! $page ) {
				WP_CLI::error( 'Missing page during Contact Form 7 block repair: ' . $slug );
			}
			tp_migration_update_page(
				$slug,
				serialize_blocks( tp_migration_transform_top_level_blocks( parse_blocks( $page->post_content ), $slug ) ),
				$results
			);
		}
	}

	foreach ( array( 'get-involved', 'meet-the-director', 'meet-the-teachers' ) as $slug ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		if ( ! $page ) {
			WP_CLI::error( 'Missing page during the remaining editable-page repair: ' . $slug );
		}
		tp_migration_update_page(
			$slug,
			tp_migration_editor_page_content( $page->post_content, $slug ),
			$results
		);
	}
	tp_migration_update_director_media_alt( $results );

	update_option( 'trinity_editable_content_migration', TP_EDITABLE_MIGRATION_VERSION, false );
	WP_CLI::success( 'Editable-content migration upgraded: ' . implode( ', ', $results ) );
	return;
}

$front_template = get_block_template( get_stylesheet() . '//front-page', 'wp_template' );
if ( ! $front_template ) {
	WP_CLI::error( 'The active Front Page template could not be loaded.' );
}
$home_blocks = tp_migration_extract_main_blocks( $front_template->content );
tp_migration_update_page( 'home', serialize_blocks( tp_migration_transform_top_level_blocks( $home_blocks, 'home' ) ), $results );

tp_migration_update_page( 'parent-corner', tp_migration_content_from_template( 'page-parent-corner.html', 'parent-corner' ), $results );
tp_migration_update_page( 'events', tp_migration_content_from_template( 'page-events.html', 'events' ), $results );
tp_migration_update_page( 'schedule-a-tour', tp_migration_schedule_content(), $results );
tp_migration_update_page( 'drop-off-pick-up', tp_migration_content_from_template( 'page-drop-off-pick-up.html', 'drop-off-pick-up' ), $results );
tp_migration_update_page( 'lunch-bunch', tp_migration_content_from_template( 'page-lunch-bunch.html', 'lunch-bunch' ), $results );
tp_migration_update_page( 'registration-forms', tp_migration_content_from_template( 'page-registration-forms.html', 'registration-forms' ), $results );

$extended = get_page_by_path( 'extended-days-program', OBJECT, 'page' );
tp_migration_update_page( 'extended-days-program', serialize_blocks( tp_migration_transform_top_level_blocks( parse_blocks( $extended->post_content ), 'extended-days-program' ) ), $results );

foreach ( array( 'contact', 'tuition-plans-pricing', 'get-involved', 'meet-the-teachers', 'meet-the-director' ) as $slug ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	$content = serialize_blocks( tp_migration_transform_top_level_blocks( parse_blocks( $page->post_content ), $slug ) );
	if ( in_array( $slug, array( 'get-involved', 'meet-the-director', 'meet-the-teachers' ), true ) ) {
		$content = tp_migration_editor_page_content( $content, $slug );
	}
	tp_migration_update_page( $slug, $content, $results );
}

tp_migration_update_director_media_alt( $results );

tp_migration_update_page(
	'parent-portal',
	tp_migration_standard_page(
		'Parent Portal',
		array(
			array( 'heading' => 'Family access', 'paragraphs' => array( 'Portal access details are shared directly with enrolled families so account links and credentials stay private.' ) ),
			array( 'heading' => 'Need help signing in?', 'paragraphs' => array( 'Contact the preschool office at <a href="tel:+18562351840">856-235-1840</a> and we will help you reach the correct family service.' ) ),
		),
		array( array( 'label' => 'Contact Trinity', 'url' => '/contact/' ) )
	),
	$results
);

tp_migration_update_page(
	'pay-tuition',
	tp_migration_standard_page(
		'Pay Tuition',
		array(
			array( 'heading' => 'Secure payment access', 'paragraphs' => array( 'Trinity sends the approved tuition-payment link directly to enrolled families. This page does not collect or store payment-card information.' ) ),
			array( 'heading' => 'Need the payment link?', 'paragraphs' => array( 'Call the preschool office at <a href="tel:+18562351840">856-235-1840</a> or use the contact page and we will resend it.' ) ),
		),
		array( array( 'label' => 'Contact Trinity', 'url' => '/contact/' ) )
	),
	$results
);

tp_migration_update_page(
	'privacy-policy',
	tp_migration_standard_page(
		'Privacy Policy',
		array(
			array( 'heading' => 'Information families provide', 'paragraphs' => array( 'When you contact Trinity or request a tour, you may provide names, contact details, a child’s age or anticipated school year, and the message you choose to send.' ) ),
			array( 'heading' => 'How information is used', 'paragraphs' => array( 'We use submitted information to answer questions, arrange requested visits, and provide preschool services. We do not use this site to process payment-card information.' ) ),
			array( 'heading' => 'Forms and retention', 'paragraphs' => array( 'Website form submissions are delivered to designated school staff by email. They are not stored in the WordPress dashboard unless the school later enables a separately governed submission-storage service.' ) ),
			array( 'heading' => 'Service providers and logs', 'paragraphs' => array( 'The website host and security or spam-prevention services may process limited technical information needed to operate and protect the site. Access is limited to people and providers who need it for those purposes.' ) ),
			array( 'heading' => 'Questions or requests', 'paragraphs' => array( 'To ask about, correct, or request deletion of information you submitted, use the contact page or call <a href="tel:+18562351840">856-235-1840</a>. This policy was last reviewed in August 2026.' ) ),
		),
		array( array( 'label' => 'Contact Trinity', 'url' => '/contact/' ) )
	),
	$results
);

tp_migration_update_page(
	'accessibility-statement',
	tp_migration_standard_page(
		'Accessibility Statement',
		array(
			array( 'heading' => 'Our commitment', 'paragraphs' => array( 'Trinity Episcopal Preschool wants families and community members of all abilities to be able to use this website and access school information.' ) ),
			array( 'heading' => 'Our accessibility goal', 'paragraphs' => array( 'We aim to follow WCAG 2.2 Level AA practices, including meaningful heading structure, keyboard access, readable contrast, text alternatives for informative images, and content that reflows when enlarged.' ) ),
			array( 'heading' => 'Need information in another format?', 'paragraphs' => array( 'If you encounter a barrier or need a document or page in another format, call <a href="tel:+18562351840">856-235-1840</a> or use the contact page. Please tell us the page or document and the format that would work best for you.' ) ),
			array( 'heading' => 'Ongoing improvement', 'paragraphs' => array( 'We review new content and important family workflows as the site changes. This statement was last reviewed in August 2026.' ) ),
		),
		array( array( 'label' => 'Report an accessibility issue', 'url' => '/contact/' ) )
	),
	$results
);

tp_migration_update_tour_form( $results );
tp_migration_event_types( $results );

tp_migration_delete_template_post( 66, 'wp_template', 'front-page', $results );
tp_migration_delete_template_post( 79, 'wp_template_part', 'header', $results );
foreach ( array( 40, 41 ) as $orphan_id ) {
	$orphan = get_post( $orphan_id );
	if ( $orphan && 'wp_template' === $orphan->post_type && 'archive-events' === $orphan->post_name && ! get_the_terms( $orphan_id, 'wp_theme' ) ) {
		wp_delete_post( $orphan_id, true );
		$results[] = 'removed:orphan-template#' . $orphan_id;
	}
}

update_option( 'trinity_editable_content_migration', TP_EDITABLE_MIGRATION_VERSION, false );
flush_rewrite_rules( false );

WP_CLI::success( 'Editable-content migration completed: ' . implode( ', ', $results ) );
