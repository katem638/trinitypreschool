<?php
/**
 * Builds the first editable WordPress pass of the Trinity Preschool site.
 *
 * Run with: ddev wp eval-file scripts/setup-trinity-site.php
 */

if ( ! class_exists( 'WP_CLI' ) ) {
	exit( "This script is intended to run through WP-CLI.\n" );
}

function tp_attachment( $slug, $alt, $title = null ) {
	$attachments = get_posts(
		array(
			'name'           => $slug,
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'orderby'        => 'ID',
			'order'          => 'DESC',
		)
	);

	if ( empty( $attachments ) ) {
		WP_CLI::error( sprintf( 'Missing media attachment with slug "%s". Import media before running this script.', $slug ) );
	}

	$attachment = $attachments[0];
	update_post_meta( $attachment->ID, '_wp_attachment_image_alt', $alt );

	if ( $title ) {
		wp_update_post(
			array(
				'ID'         => $attachment->ID,
				'post_title' => $title,
			)
		);
	}

	return array(
		'id'  => (int) $attachment->ID,
		'url' => wp_get_attachment_url( $attachment->ID ),
		'alt' => $alt,
	);
}

function tp_image_block( $image, $class_name = '' ) {
	$id      = (int) $image['id'];
	$url     = esc_url( $image['url'] );
	$alt     = esc_attr( $image['alt'] );
	$json    = $class_name ? sprintf( ',"className":"%s"', esc_js( $class_name ) ) : '';
	$classes = trim( 'wp-block-image size-full ' . $class_name );

	return sprintf(
		'<!-- wp:image {"id":%1$d,"sizeSlug":"full","linkDestination":"none"%2$s} -->' . "\n" .
		'<figure class="%3$s"><img src="%4$s" alt="%5$s" class="wp-image-%1$d"/></figure>' . "\n" .
		'<!-- /wp:image -->',
		$id,
		$json,
		esc_attr( $classes ),
		$url,
		$alt
	);
}

function tp_upsert_cf7_form( $title, $form_markup, $mail_subject, $mail_body ) {
	if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
		WP_CLI::error( 'Contact Form 7 is not active.' );
	}

	$existing = null;
	$forms    = get_posts(
		array(
			'post_type'      => WPCF7_ContactForm::post_type,
			'post_status'    => 'any',
			'posts_per_page' => -1,
		)
	);

	foreach ( $forms as $form_post ) {
		if ( $form_post->post_title === $title ) {
			$existing = $form_post;
			break;
		}
	}

	$form = $existing ? wpcf7_contact_form( $existing ) : WPCF7_ContactForm::get_template( array( 'title' => $title ) );
	$form->set_title( $title );

	$properties = $form->get_properties();

	$properties['form'] = $form_markup;
	$properties['mail'] = array_merge(
		isset( $properties['mail'] ) ? $properties['mail'] : array(),
		array(
			'subject'            => $mail_subject,
			'sender'             => 'Trinity Episcopal Preschool <wordpress@trinityepiscopalpreschool.org>',
			'recipient'          => '[_site_admin_email]',
			'body'               => $mail_body,
			'additional_headers' => 'Reply-To: [your-email]',
			'attachments'        => '',
			'use_html'           => 0,
			'exclude_blank'      => 0,
		)
	);
	$properties['mail_2'] = array_merge(
		isset( $properties['mail_2'] ) ? $properties['mail_2'] : array(),
		array(
			'active' => false,
		)
	);

	$form->set_properties( $properties );
	return (int) $form->save();
}

function tp_upsert_page( $title, $slug, $content, $parent_id = 0 ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );

	$args = array(
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_content' => $content,
		'post_parent'  => $parent_id,
	);

	if ( $page ) {
		$args['ID'] = $page->ID;
		return (int) wp_update_post( $args );
	}

	return (int) wp_insert_post( $args );
}

update_option( 'blogname', 'Trinity Episcopal Preschool' );
update_option( 'blogdescription', 'Joyful learning every day in Moorestown, NJ.' );

$media = array(
	'logo'          => tp_attachment( 'logo', 'Trinity Episcopal Preschool logo', 'Trinity Episcopal Preschool logo' ),
	'homeimage1'    => tp_attachment( 'homeimage1', 'Colorful preschool toys arranged on a table', 'Colorful preschool toys' ),
	'homeimage2'    => tp_attachment( 'homeimage2', 'Children working together on a classroom craft', 'Children creating a classroom craft' ),
	'contactimage'  => tp_attachment( 'contactimage', 'Paints and paint brushes on a classroom table', 'Classroom paint table' ),
	'director'      => tp_attachment( 'director-photo', 'Mrs. Christine Andonie smiling for a portrait', 'Mrs. Christine Andonie' ),
	'teacher1'      => tp_attachment( 'teacher1', 'Emily Thompson, Director', 'Emily Thompson portrait' ),
	'teacher2'      => tp_attachment( 'teacher2', 'Olivia Baker, Lead Teacher', 'Olivia Baker portrait' ),
	'teacher3'      => tp_attachment( 'teacher3', 'Michael Davis, Early Childhood Specialist', 'Michael Davis portrait' ),
	'teacher4'      => tp_attachment( 'teacher4', 'Sophia Clark, Art and Music Instructor', 'Sophia Clark portrait' ),
	'extended1'     => tp_attachment( 'extended1', 'Music class drum activity', 'Music class' ),
	'extended2'     => tp_attachment( 'extended2', 'Children playing sack race outdoors', 'Exercise with Coach Pat' ),
	'extended3'     => tp_attachment( 'extended3', 'Child in karate class uniform', 'Action Karate Class' ),
	'extended4'     => tp_attachment( 'extended4', 'Child baking dough in class', 'Little Bakers' ),
	'extended5'     => tp_attachment( 'extended5', 'Child playing with building toys', 'Building Club' ),
	'extended6'     => tp_attachment( 'extended6', 'Children drawing with colorful markers', 'Art and Science' ),
);

set_theme_mod( 'custom_logo', $media['logo']['id'] );

$contact_form = tp_upsert_cf7_form(
	'Contact Trinity Preschool',
	'<label class="tp-half">First name * [text* first-name autocomplete:given-name]</label><label class="tp-half">Last name [text last-name autocomplete:family-name]</label><label>Email * [email* your-email autocomplete:email]</label><label>Write a message [textarea your-message]</label>[submit "Submit"]',
	'New Trinity Preschool inquiry',
	"From: [first-name] [last-name] <[your-email]>\n\nMessage:\n[your-message]\n\n--\nThis message was sent from [_site_title] ([_site_url])."
);

$tour_form_markup = <<<'HTML'
<div class="tp-tour-form-fields">
	<div class="tp-tour-field tp-tour-field-full">
		<label for="tour-parent-name">Your name <span aria-hidden="true">*</span></label>
		[text* parent-name id:tour-parent-name autocomplete:name placeholder "Jane Doe"]
	</div>

	<div class="tp-tour-field-grid">
		<div class="tp-tour-field">
			<label for="tour-email">Email <span aria-hidden="true">*</span></label>
			[email* your-email id:tour-email autocomplete:email placeholder "you@email.com"]
		</div>
		<div class="tp-tour-field">
			<label for="tour-phone">Phone</label>
			[tel your-phone id:tour-phone autocomplete:tel placeholder "(856) 000-0000"]
		</div>
	</div>

	<fieldset class="tp-tour-choice-field">
		<legend>Best way to reach you</legend>
		[radio contact-method use_label_element default:1 "Text" "Email" "Call"]
	</fieldset>

	<fieldset class="tp-tour-child-card">
		<legend><span>Child 1</span><small>required</small></legend>
		<div class="tp-tour-child-grid">
			<div class="tp-tour-field">
				<label for="tour-child-name">Name</label>
				[text* child-name id:tour-child-name placeholder "Nora"]
			</div>
			<div class="tp-tour-field">
				<label for="tour-child-age">Age at start</label>
				[number* child-age id:tour-child-age min:1 max:6 placeholder "3"]
			</div>
			<div class="tp-tour-field tp-tour-field-full">
				<label for="tour-school-year">For school year</label>
				[text* school-year id:tour-school-year placeholder "Fall 2026"]
			</div>
		</div>
	</fieldset>

	<button type="button" class="tp-tour-add-sibling" aria-expanded="false" aria-controls="tp-tour-sibling-fields"><span aria-hidden="true">+</span> Add a sibling</button>

	<fieldset class="tp-tour-child-card tp-tour-child-card-sibling" id="tp-tour-sibling-fields" hidden>
		<legend><span>Sibling</span><small>optional</small></legend>
		<div class="tp-tour-child-grid">
			<div class="tp-tour-field">
				<label for="tour-sibling-name">Name</label>
				[text sibling-name id:tour-sibling-name placeholder "Name"]
			</div>
			<div class="tp-tour-field">
				<label for="tour-sibling-age">Age at start</label>
				[number sibling-age id:tour-sibling-age min:1 max:6 placeholder "Age"]
			</div>
			<div class="tp-tour-field tp-tour-field-full">
				<label for="tour-sibling-school-year">For school year</label>
				[text sibling-school-year id:tour-sibling-school-year placeholder "Fall 2026"]
			</div>
		</div>
	</fieldset>

	<fieldset class="tp-tour-choice-field tp-tour-day-card">
		<legend>I'd love a tour on a...</legend>
		[radio tour-day use_label_element default:3 "Tue" "Wed" "Thu" "Fri"]
	</fieldset>

	<div class="tp-tour-submit-row">[submit class:tp-tour-submit "Book my tour"]</div>
</div>
HTML;

$tour_form_markup = trim( preg_replace( '/\s+/', ' ', $tour_form_markup ) );

$tour_form = tp_upsert_cf7_form(
	'Schedule a Tour Request',
	$tour_form_markup,
	'New Trinity Preschool tour request',
	"Parent: [parent-name]\nEmail: [your-email]\nPhone: [your-phone]\nBest way to reach: [contact-method]\n\nChild 1:\nName: [child-name]\nAge at start: [child-age]\nSchool year: [school-year]\n\nSibling:\nName: [sibling-name]\nAge at start: [sibling-age]\nSchool year: [sibling-school-year]\n\nPreferred tour day: [tour-day]\n\n--\nThis message was sent from [_site_title] ([_site_url])."
);

$contact_shortcode = sprintf( '[contact-form-7 id="%d" title="Contact Trinity Preschool"]', $contact_form );
$tour_shortcode    = sprintf( '[contact-form-7 id="%d" title="Schedule a Tour Request"]', $tour_form );

$teacher_cards_home = '';
$teachers           = array(
	array( 'image' => 'teacher1', 'name' => 'Emily Thompson', 'role' => 'Director' ),
	array( 'image' => 'teacher2', 'name' => 'Olivia Baker', 'role' => 'Lead Teacher' ),
	array( 'image' => 'teacher3', 'name' => 'Michael Davis', 'role' => 'Early Childhood Specialist' ),
	array( 'image' => 'teacher4', 'name' => 'Sophia Clark', 'role' => 'Art and Music Instructor' ),
);

foreach ( $teachers as $teacher ) {
	$teacher_cards_home .= '<!-- wp:group {"className":"tp-home-teacher-card","layout":{"type":"default"}} -->' . "\n";
	$teacher_cards_home .= '<div class="wp-block-group tp-home-teacher-card">' . "\n";
	$teacher_cards_home .= tp_image_block( $media[ $teacher['image'] ] ) . "\n";
	$teacher_cards_home .= sprintf(
		'<!-- wp:paragraph --><p><strong>%s</strong><br>%s</p><!-- /wp:paragraph -->',
		esc_html( $teacher['name'] ),
		esc_html( $teacher['role'] )
	);
	$teacher_cards_home .= "\n</div>\n<!-- /wp:group -->\n";
}

$home_hero_image    = tp_image_block( $media['homeimage1'], 'tp-hero-image' );
$home_contact_image = tp_image_block( $media['homeimage2'], 'tp-contact-image' );

$home_content = <<<HTML
<!-- wp:group {"align":"full","className":"tp-home-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull tp-home-hero">
	<!-- wp:group {"align":"wide","className":"tp-home-hero-panel","layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide tp-home-hero-panel">
		{$home_hero_image}
		<!-- wp:group {"className":"tp-hero-card","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tp-hero-card">
			<!-- wp:heading {"level":1} -->
			<h1 class="wp-block-heading">Joyful Learning. Every Day.</h1>
			<!-- /wp:heading -->
			<!-- wp:heading {"level":2} -->
			<h2 class="wp-block-heading">Welcome to Trinity Preschool!</h2>
			<!-- /wp:heading -->
			<!-- wp:paragraph -->
			<p>Established in 2000 in the heart of Moorestown, Trinity Episcopal Preschool is a place where children are encouraged to explore, create, and grow. Your child will be nurtured in a safe, loving environment where curiosity is celebrated, and kindness comes first.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"tp-testimonial","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull tp-testimonial">
	<!-- wp:paragraph {"className":"tp-quote-mark"} -->
	<p class="tp-quote-mark">"</p>
	<!-- /wp:paragraph -->
	<!-- wp:quote -->
	<blockquote class="wp-block-quote"><p>"Testimonials are a great way to share positive feedback you have received and encourage others to work with you. Add your own here."</p><cite>Claire Brooks, MI</cite></blockquote>
	<!-- /wp:quote -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"tp-mission","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull tp-mission">
	<!-- wp:heading -->
	<h2 class="wp-block-heading">Our Mission</h2>
	<!-- /wp:heading -->
	<!-- wp:heading {"level":3} -->
	<h3 class="wp-block-heading">Nurturing Young Hearts &amp; Minds. Fostering Lifelong Curiosity</h3>
	<!-- /wp:heading -->
	<!-- wp:paragraph -->
	<p>At Trinity Episcopal Preschool, our mission is to nurture young hearts and minds by providing engaging activities that enhance early academic skills and encourage creative expression. We believe that lifelong curiosity comes from the ability to explore and learn through play-based activities in a safe environment. Our programs are designed to cultivate a well-rounded early education and promote social, emotional, and cognitive growth.</p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"tp-teacher-preview","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull tp-teacher-preview">
	<!-- wp:group {"align":"wide","className":"tp-teacher-preview-grid","layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide tp-teacher-preview-grid">
		<!-- wp:group {"layout":{"type":"default"}} -->
		<div class="wp-block-group">
			<!-- wp:heading -->
			<h2 class="wp-block-heading">Meet Our Teachers</h2>
			<!-- /wp:heading -->
			<!-- wp:paragraph -->
			<p>Our team of dedicated educators and staff are committed to creating a nurturing learning environment where young children can thrive, learn, and develop to their full potential.</p>
			<!-- /wp:paragraph -->
			<!-- wp:buttons -->
			<div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/meet-the-teachers/">Meet Our Teachers</a></div><!-- /wp:button --></div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->
		<!-- wp:group {"className":"tp-home-teachers","layout":{"type":"default"}} -->
		<div class="wp-block-group tp-home-teachers">
			{$teacher_cards_home}
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"tp-home-contact","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull tp-home-contact">
	<!-- wp:group {"align":"wide","className":"tp-contact-grid","layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide tp-contact-grid">
		{$home_contact_image}
		<!-- wp:group {"className":"tp-contact-copy","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tp-contact-copy" id="contact-form">
			<!-- wp:heading -->
			<h2 class="wp-block-heading">Get in<br>Touch</h2>
			<!-- /wp:heading -->
			<!-- wp:paragraph -->
			<p>Reach out to us for any inquiries, admissions, or to learn more about our engaging programs that inspire young hearts and minds.</p>
			<!-- /wp:paragraph -->
			<!-- wp:shortcode -->
			{$contact_shortcode}
			<!-- /wp:shortcode -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
HTML;

$programs = array(
	array( 'image' => 'extended1', 'title' => 'Music Class', 'text' => 'This 30-minute program is taught by Nanci Wright. Each week, the children concentrate on basic music fundamentals such as tempo, beat, rhythm, pitch, dynamics, orchestral instruments while featuring a variety of themes that will enable children to utilize their imaginations and creativity.' ),
	array( 'image' => 'extended2', 'title' => 'Exercise with Coach Pat', 'text' => 'This 45-minute class is held every other Friday and taught by Coach Pat. He teaches children to have fun with exercise through games, music and stories. The program is designed to promote exercise and wellness for young children.' ),
	array( 'image' => 'extended3', 'title' => 'Action Karate Class', 'text' => 'This 30-minute class is taught by an instructor from Action Karate. It will teach your child various skills such as focus, respect, confidence, self-discipline, courtesy, safety and the benefits of healthy eating and exercise.' ),
	array( 'image' => 'extended4', 'title' => 'Little Bakers', 'text' => 'This program is for 4- and 5-year old children and is held on Mondays from after school to 1:30pm. The children will bake through the alphabet while learning to cook with healthy ingredients. They will also learn to make healthier food choices, learn kitchen and food safety, as well as measuring and using kitchen utensils.' ),
	array( 'image' => 'extended5', 'title' => 'Building Club', 'text' => 'This class is for 4- and 5-year old children and is held on Tuesday or Wednesday from after school to 1:30pm. The children will be working in small groups and building with Lego pieces. Each week there will be different objectives along with different structures to make. We will also allow time for the children to create their own structures.' ),
	array( 'image' => 'extended6', 'title' => 'Art and Science', 'text' => 'This class is for 4- and 5-year old children and is held every other Friday from after school to 1:30. The children will engage in different science activities with an art activity to go along with the topic.' ),
);

$program_cards = '';
foreach ( $programs as $program ) {
	$program_cards .= '<!-- wp:group {"className":"tp-program-card","layout":{"type":"default"}} -->' . "\n";
	$program_cards .= '<div class="wp-block-group tp-program-card">' . "\n";
	$program_cards .= tp_image_block( $media[ $program['image'] ] ) . "\n";
	$program_cards .= sprintf(
		'<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">%s</h2><!-- /wp:heading -->' . "\n" .
		'<!-- wp:paragraph --><p>%s</p><!-- /wp:paragraph -->',
		esc_html( $program['title'] ),
		esc_html( $program['text'] )
	);
	$program_cards .= "\n</div>\n<!-- /wp:group -->\n";
}

$extended_content = <<<HTML
<!-- wp:group {"align":"full","className":"tp-programs-page","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull tp-programs-page">
	<!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":1} -->
		<h1 class="wp-block-heading">Extended Day Classes</h1>
		<!-- /wp:heading -->
		<!-- wp:paragraph {"className":"tp-program-intro"} -->
		<p class="tp-program-intro">Explore our extended-day classes which provide enriching activities and a safe environment for your child to learn and grow.</p>
		<!-- /wp:paragraph -->
		<!-- wp:group {"className":"tp-program-grid","layout":{"type":"default"}} -->
		<div class="wp-block-group tp-program-grid">
			{$program_cards}
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
HTML;

$schedule_content = <<<HTML
<!-- wp:group {"align":"full","className":"tp-standard-page","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull tp-standard-page">
	<!-- wp:group {"className":"tp-standard-inner","layout":{"type":"default"}} -->
	<div class="wp-block-group tp-standard-inner">
		<!-- wp:paragraph {"className":"tp-breadcrumbs"} -->
		<p class="tp-breadcrumbs"><a href="/">Home</a> &gt; <a href="/schedule-a-tour/">Service list</a> &gt; Schedule a Tour of Trinity Preschool</p>
		<!-- /wp:paragraph -->
		<!-- wp:heading {"level":1} -->
		<h1 class="wp-block-heading">Schedule a Tour of Trinity Preschool</h1>
		<!-- /wp:heading -->
		<!-- wp:html -->
		<div class="tp-service-meta"><span>1 hr</span><span>207 West Main Street</span></div>
		<!-- /wp:html -->
		<!-- wp:buttons -->
		<div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#tour-request">Book Now</a></div><!-- /wp:button --></div>
		<!-- /wp:buttons -->
		<!-- wp:separator -->
		<hr class="wp-block-separator has-alpha-channel-opacity"/>
		<!-- /wp:separator -->
		<!-- wp:heading -->
		<h2 class="wp-block-heading">Want to get to know us better?</h2>
		<!-- /wp:heading -->
		<!-- wp:paragraph -->
		<p>Schedule a tour with our Preschool Director!</p>
		<!-- /wp:paragraph -->
		<!-- wp:separator -->
		<hr class="wp-block-separator has-alpha-channel-opacity"/>
		<!-- /wp:separator -->
		<!-- wp:heading -->
		<h2 class="wp-block-heading">Contact Details</h2>
		<!-- /wp:heading -->
		<!-- wp:paragraph -->
		<p>207 West Main Street, Moorestown, NJ, USA</p>
		<!-- /wp:paragraph -->
		<!-- wp:separator -->
		<hr class="wp-block-separator has-alpha-channel-opacity"/>
		<!-- /wp:separator -->
		<!-- wp:group {"className":"tp-tour-form","layout":{"type":"default"}} -->
		<div class="wp-block-group tp-tour-form" id="tour-request">
			<!-- wp:heading -->
			<h2 class="wp-block-heading">Request a Tour</h2>
			<!-- /wp:heading -->
			<!-- wp:shortcode -->
			{$tour_shortcode}
			<!-- /wp:shortcode -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
HTML;

$director_image = tp_image_block( $media['director'], 'tp-director-photo' );

$director_content = <<<HTML
<!-- wp:group {"align":"full","className":"tp-director-band","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull tp-director-band">
	<!-- wp:group {"align":"wide","className":"tp-director-card","layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide tp-director-card">
		<!-- wp:group {"className":"tp-director-copy","layout":{"type":"default"}} -->
		<div class="wp-block-group tp-director-copy">
			<!-- wp:heading {"level":1} -->
			<h1 class="wp-block-heading">Meet Our Director</h1>
			<!-- /wp:heading -->
			<!-- wp:heading -->
			<h2 class="wp-block-heading">Mrs. Christine Andonie</h2>
			<!-- /wp:heading -->
			<!-- wp:paragraph -->
			<p>Having served multiple roles within the preschool for the last ten years, Christine brings a passion for early education that focuses on learning through play. Her goal is to provide a nurturing school setting that bolsters self-confidence and promotes Christian values such as kindness and leading with love.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
		{$director_image}
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
HTML;

$teacher_bios = array(
	array( 'image' => 'teacher1', 'name' => 'Emily Thompson', 'role' => 'Director', 'bio' => 'Emily leads the preschool with warmth, organization, and a deep belief in helping children feel known, safe, and ready to learn.' ),
	array( 'image' => 'teacher2', 'name' => 'Olivia Baker', 'role' => 'Lead Teacher', 'bio' => 'Olivia creates joyful classroom routines that balance discovery, structure, movement, and the everyday wonder of early childhood.' ),
	array( 'image' => 'teacher3', 'name' => 'Michael Davis', 'role' => 'Early Childhood Specialist', 'bio' => 'Michael supports social-emotional growth and encourages curiosity through books, conversation, creative play, and hands-on projects.' ),
	array( 'image' => 'teacher4', 'name' => 'Sophia Clark', 'role' => 'Art and Music Instructor', 'bio' => 'Sophia brings color, music, rhythm, and imagination into the school day so children can express themselves with confidence.' ),
);

$teacher_cards = '';
foreach ( $teacher_bios as $teacher ) {
	$teacher_cards .= '<!-- wp:group {"className":"tp-teacher-card","layout":{"type":"default"}} -->' . "\n";
	$teacher_cards .= '<div class="wp-block-group tp-teacher-card">' . "\n";
	$teacher_cards .= tp_image_block( $media[ $teacher['image'] ] ) . "\n";
	$teacher_cards .= '<!-- wp:group {"className":"tp-teacher-card-body","layout":{"type":"default"}} -->' . "\n";
	$teacher_cards .= '<div class="wp-block-group tp-teacher-card-body">' . "\n";
	$teacher_cards .= sprintf( '<!-- wp:paragraph {"className":"tp-teacher-role"} --><p class="tp-teacher-role">%s</p><!-- /wp:paragraph -->', esc_html( $teacher['role'] ) ) . "\n";
	$teacher_cards .= sprintf( '<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">%s</h2><!-- /wp:heading -->', esc_html( $teacher['name'] ) ) . "\n";
	$teacher_cards .= sprintf( '<!-- wp:paragraph --><p>%s</p><!-- /wp:paragraph -->', esc_html( $teacher['bio'] ) ) . "\n";
	$teacher_cards .= '<!-- wp:paragraph {"className":"tp-teacher-social"} --><p class="tp-teacher-social">f  x  in</p><!-- /wp:paragraph -->' . "\n";
	$teacher_cards .= "</div>\n<!-- /wp:group -->\n</div>\n<!-- /wp:group -->\n";
}

$teachers_content = <<<HTML
<!-- wp:group {"align":"full","className":"tp-teachers-page","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull tp-teachers-page">
	<!-- wp:heading {"level":1} -->
	<h1 class="wp-block-heading">Meet The Teachers</h1>
	<!-- /wp:heading -->
	<!-- wp:html -->
	<div class="tp-title-rule"></div>
	<!-- /wp:html -->
	<!-- wp:group {"className":"tp-teacher-grid","layout":{"type":"default"}} -->
	<div class="wp-block-group tp-teacher-grid">
		{$teacher_cards}
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
HTML;

$contact_page_image = tp_image_block( $media['contactimage'], 'tp-contact-hero-image' );

$contact_content = <<<HTML
<!-- wp:group {"align":"full","className":"tp-contact-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull tp-contact-hero">
	<!-- wp:group {"align":"wide","className":"tp-contact-hero-grid","layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide tp-contact-hero-grid">
		{$contact_page_image}
		<!-- wp:group {"className":"tp-contact-hero-copy","layout":{"type":"constrained"}} -->
		<div class="wp-block-group tp-contact-hero-copy">
			<!-- wp:heading {"level":1} -->
			<h1 class="wp-block-heading">Contact Us</h1>
			<!-- /wp:heading -->
			<!-- wp:paragraph -->
			<p>We welcome any questions you may have about our preschool programs or our curriculum. Please feel free to reach out to us using the contact information provided below. We are excited to hear from you!</p>
			<!-- /wp:paragraph -->
			<!-- wp:shortcode -->
			{$contact_shortcode}
			<!-- /wp:shortcode -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
HTML;

$events_content = <<<HTML
<!-- wp:group {"align":"full","className":"tp-standard-page","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull tp-standard-page">
	<!-- wp:group {"className":"tp-standard-inner","layout":{"type":"default"}} -->
	<div class="wp-block-group tp-standard-inner">
		<!-- wp:heading {"level":1} -->
		<h1 class="wp-block-heading">Events</h1>
		<!-- /wp:heading -->
		<!-- wp:paragraph -->
		<p>School events and family updates will be shared here as the calendar is finalized.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
HTML;

$privacy_content = '<!-- wp:group {"align":"full","className":"tp-standard-page","layout":{"type":"constrained"}} --><div class="wp-block-group alignfull tp-standard-page"><!-- wp:group {"className":"tp-standard-inner","layout":{"type":"default"}} --><div class="wp-block-group tp-standard-inner"><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Privacy Policy</h1><!-- /wp:heading --><!-- wp:paragraph --><p>Add the preschool privacy policy here.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:group -->';
$accessibility_content = '<!-- wp:group {"align":"full","className":"tp-standard-page","layout":{"type":"constrained"}} --><div class="wp-block-group alignfull tp-standard-page"><!-- wp:group {"className":"tp-standard-inner","layout":{"type":"default"}} --><div class="wp-block-group tp-standard-inner"><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Accessibility Statement</h1><!-- /wp:heading --><!-- wp:paragraph --><p>Add the preschool accessibility statement here.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:group -->';

$home_id          = tp_upsert_page( 'Home', 'home', $home_content );
$extended_id      = tp_upsert_page( 'Extended Days Program', 'extended-days-program', $extended_content );
$schedule_id      = tp_upsert_page( 'Schedule a Tour', 'schedule-a-tour', $schedule_content );
$director_id      = tp_upsert_page( 'Meet Our Director', 'meet-the-director', $director_content );
$teachers_id      = tp_upsert_page( 'Meet The Teachers', 'meet-the-teachers', $teachers_content );
$events_id        = tp_upsert_page( 'Events', 'events', $events_content );
$contact_id       = tp_upsert_page( 'Contact', 'contact', $contact_content );
$privacy_id       = tp_upsert_page( 'Privacy Policy', 'privacy-policy', $privacy_content );
$accessibility_id = tp_upsert_page( 'Accessibility Statement', 'accessibility-statement', $accessibility_content );

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home_id );
update_option( 'page_for_posts', 0 );

WP_CLI::success(
	sprintf(
		'Created/updated pages: Home %d, Extended %d, Schedule %d, Director %d, Teachers %d, Events %d, Contact %d. Contact Form 7 forms: %d, %d.',
		$home_id,
		$extended_id,
		$schedule_id,
		$director_id,
		$teachers_id,
		$events_id,
		$contact_id,
		$contact_form,
		$tour_form
	)
);
