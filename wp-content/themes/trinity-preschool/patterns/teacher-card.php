<?php
/**
 * Title: Teacher Card
 * Slug: trinity-preschool/teacher-card
 * Categories: trinity-preschool
 * Inserter: true
 */
?>

<!-- wp:group {"tagName":"article","metadata":{"name":"Teacher Card"},"className":"tp-teacher-card","layout":{"type":"default"}} -->
<article class="wp-block-group tp-teacher-card" id="teacher-name">
	<!-- wp:paragraph {"className":"tp-teacher-placeholder"} -->
	<p class="tp-teacher-placeholder">TN</p>
	<!-- /wp:paragraph -->
	<!-- wp:group {"className":"tp-teacher-card-body","layout":{"type":"default"}} -->
	<div class="wp-block-group tp-teacher-card-body">
		<!-- wp:paragraph {"className":"tp-teacher-role"} -->
		<p class="tp-teacher-role">Role</p>
		<!-- /wp:paragraph -->
		<!-- wp:heading {"level":3} -->
		<h3 class="wp-block-heading">Teacher Name</h3>
		<!-- /wp:heading -->
		<!-- wp:paragraph {"className":"tp-teacher-meta"} -->
		<p class="tp-teacher-meta">Room / age group</p>
		<!-- /wp:paragraph -->
		<!-- wp:paragraph {"className":"tp-teacher-summary"} -->
		<p class="tp-teacher-summary">Short intro sentence for the closed card.</p>
		<!-- /wp:paragraph -->
		<!-- wp:details {"className":"tp-teacher-bio"} -->
		<details class="wp-block-details tp-teacher-bio">
			<summary>Read Q&amp;A with Teacher Name</summary>
			<!-- wp:group {"className":"tp-teacher-qa","layout":{"type":"default"}} -->
			<div class="wp-block-group tp-teacher-qa">
				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading">What age group and/or room color do you teach?</h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph -->
				<p>Answer goes here.</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</details>
		<!-- /wp:details -->
	</div>
	<!-- /wp:group -->
</article>
<!-- /wp:group -->
