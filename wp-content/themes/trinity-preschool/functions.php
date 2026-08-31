<?php
/**
 * Trinity Preschool theme setup.
 */

if ( ! function_exists( 'trinity_preschool_setup' ) ) {
	function trinity_preschool_setup() {
		add_editor_style( 'style.css' );
	}
}
add_action( 'after_setup_theme', 'trinity_preschool_setup' );

if ( ! function_exists( 'trinity_preschool_register_block_pattern_category' ) ) {
	function trinity_preschool_register_block_pattern_category() {
		if ( function_exists( 'register_block_pattern_category' ) ) {
			register_block_pattern_category(
				'trinity-preschool',
				array( 'label' => __( 'Trinity Preschool', 'trinity-preschool' ) )
			);
		}
	}
}
add_action( 'init', 'trinity_preschool_register_block_pattern_category' );

if ( ! function_exists( 'trinity_preschool_enqueue_styles' ) ) {
	function trinity_preschool_enqueue_styles() {
		$stylesheet_path = get_stylesheet_directory() . '/style.css';

		wp_enqueue_style(
			'trinity-preschool-style',
			get_stylesheet_uri(),
			array(),
			file_exists( $stylesheet_path ) ? filemtime( $stylesheet_path ) : wp_get_theme()->get( 'Version' )
		);

		$queried_post = get_queried_object();
		$has_tour_form = $queried_post instanceof WP_Post
			&& has_block( 'contact-form-7/contact-form-selector', $queried_post )
			&& false !== strpos( $queried_post->post_content, '"id":22' );

		if ( $has_tour_form ) {
			wp_register_script(
				'trinity-preschool-tour-form',
				false,
				array(),
				file_exists( $stylesheet_path ) ? filemtime( $stylesheet_path ) : wp_get_theme()->get( 'Version' ),
				true
			);
			wp_enqueue_script( 'trinity-preschool-tour-form' );
			wp_add_inline_script(
				'trinity-preschool-tour-form',
				<<<'JS'
document.addEventListener('DOMContentLoaded', function () {
	document.querySelectorAll('form.tp-tour-cf7, .tp-tour-cf7 form').forEach(function (form, formIndex) {
		var button = form.querySelector('.tp-tour-add-sibling');
		var siblingFields = form.querySelector('.tp-tour-sibling-fields');

		if (!button || !siblingFields) {
			return;
		}

		var siblingId = 'tp-tour-sibling-fields-' + (formIndex + 1);
		siblingFields.id = siblingId;
		button.setAttribute('aria-controls', siblingId);

		var controls = siblingFields.querySelectorAll('input, select, textarea');
		var setExpanded = function (expanded) {
			siblingFields.hidden = !expanded;
			button.classList.toggle('is-expanded', expanded);
			button.setAttribute('aria-expanded', expanded ? 'true' : 'false');

			controls.forEach(function (control) {
				control.disabled = !expanded;
			});
		};

		setExpanded(false);

		button.addEventListener('click', function () {
			setExpanded(siblingFields.hidden);

			if (!siblingFields.hidden) {
				var firstField = siblingFields.querySelector('input, select, textarea');

				if (firstField) {
					firstField.focus();
				}
			}
		});
	});
});
JS
			);
		}

	}
}
add_action( 'wp_enqueue_scripts', 'trinity_preschool_enqueue_styles' );

if ( ! function_exists( 'trinity_preschool_disable_cf7_form_autop' ) ) {
	function trinity_preschool_disable_cf7_form_autop( $autop, $options ) {
		if ( isset( $options['for'] ) && 'form' === $options['for'] ) {
			return false;
		}

		return $autop;
	}
}
add_filter( 'wpcf7_autop_or_not', 'trinity_preschool_disable_cf7_form_autop', 10, 2 );
