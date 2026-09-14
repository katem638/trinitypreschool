<?php
/**
 * Title: Document Row
 * Slug: trinity-preschool/document-row
 * Categories: trinity-preschool
 * Inserter: true
 */
?>

<!-- wp:group {"className":"tp-form-row tp-row-blue","metadata":{"name":"Document row"},"templateLock":"contentOnly","layout":{"type":"default"}} -->
<div class="wp-block-group tp-form-row tp-row-blue">
	<!-- wp:paragraph {"className":"tp-form-pages"} -->
	<p class="tp-form-pages">1pg</p>
	<!-- /wp:paragraph -->
	<!-- wp:group {"className":"tp-form-row-copy","metadata":{"name":"Document Description"},"layout":{"type":"default"}} -->
	<div class="wp-block-group tp-form-row-copy">
		<!-- wp:heading {"level":3} -->
		<h3 class="wp-block-heading">Document title</h3>
		<!-- /wp:heading -->
		<!-- wp:paragraph -->
		<p>Explain who needs this document and when.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
	<!-- wp:paragraph {"className":"tp-form-tag"} -->
	<p class="tp-form-tag">Required</p>
	<!-- /wp:paragraph -->
	<!-- wp:file {"href":"#","showDownloadButton":true,"displayPreview":false,"className":"tp-form-download tp-download-blue","metadata":{"name":"Document File"}} -->
	<div class="wp-block-file tp-form-download tp-download-blue"><a href="#">Choose document</a><a href="#" class="wp-block-file__button wp-element-button" download>Download</a></div>
	<!-- /wp:file -->
</div>
<!-- /wp:group -->
