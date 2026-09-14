<?php
/**
 * Title: Teacher Card
 * Slug: trinity-preschool/teacher-card
 * Categories: trinity-preschool
 * Inserter: true
 */
?>

<!-- wp:group {"tagName":"article","metadata":{"name":"Teacher: Teacher Name"},"className":"tp-teacher-card","layout":{"type":"default"}} -->
<article class="wp-block-group tp-teacher-card">
	<!-- wp:group {"className":"tp-teacher-profile","metadata":{"name":"Portrait and introduction"},"layout":{"type":"default"}} -->
	<div class="wp-block-group tp-teacher-profile">
		<!-- wp:image {"sizeSlug":"full","linkDestination":"none","metadata":{"name":"Teacher portrait - replace placeholder"}} -->
		<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/teacher-photo-placeholder.png' ) ); ?>" alt="Teacher photo coming soon"/></figure>
		<!-- /wp:image -->
		<!-- wp:group {"className":"tp-teacher-card-body","metadata":{"name":"Profile content"},"layout":{"type":"default"}} -->
		<div class="wp-block-group tp-teacher-card-body">
		<!-- wp:paragraph {"className":"tp-teacher-role","metadata":{"name":"Role"}} -->
		<p class="tp-teacher-role">Role</p>
		<!-- /wp:paragraph -->
		<!-- wp:heading {"level":3,"metadata":{"name":"Teacher name"}} -->
		<h3 class="wp-block-heading">Teacher Name</h3>
		<!-- /wp:heading -->
		<!-- wp:paragraph {"className":"tp-teacher-meta","metadata":{"name":"Classroom / age group"}} -->
		<p class="tp-teacher-meta">Room / age group</p>
		<!-- /wp:paragraph -->
		<!-- wp:paragraph {"className":"tp-teacher-summary","metadata":{"name":"Short introduction"}} -->
		<p class="tp-teacher-summary">Short intro sentence for the closed card.</p>
		<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
	<!-- wp:details {"className":"tp-teacher-bio","metadata":{"name":"Biography / Q&A"}} -->
	<details class="wp-block-details tp-teacher-bio">
		<summary>Read biography and Q&amp;A</summary>
		<!-- wp:group {"className":"tp-teacher-qa","metadata":{"name":"Biography answers"},"layout":{"type":"default"}} -->
		<div class="wp-block-group tp-teacher-qa">
			<!-- wp:heading {"level":4} -->
			<h4 class="wp-block-heading">What age group and/or room color do you teach?</h4>
			<!-- /wp:heading -->
			<!-- wp:paragraph -->
			<p>Answer goes here.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</details>
	<!-- /wp:details -->
</article>
<!-- /wp:group -->
