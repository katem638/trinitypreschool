<?php

if ( ! function_exists( 'piecal_classic_metabox' ) ) {
    function piecal_classic_metabox( $post_type ) {

        include_once( PIECAL_DIR . '/includes/utils/General.php' );

        // Translators: Label for Pie Calendar classic metabox.
        $classicMetaboxLabel = __( 'Calendar', 'piecal' );

        if( Piecal\Utils\General::shouldAddMetabox( $post_type ) ) {
            add_meta_box(
                'piecalendar-metabox',
                $classicMetaboxLabel,
                'piecal_classic_metabox_callback',
                $post_type
            );
        }
    }
}

add_action( 'add_meta_boxes', 'piecal_classic_metabox' );

if ( ! function_exists( 'piecal_classic_metabox_callback' ) ) {
    function piecal_classic_metabox_callback() {

        global $post;
    
        $isEvent = boolval( get_post_meta($post->ID, '_piecal_is_event', true) );
        $isEventChecked = $isEvent ? "checked='true'" : null;
    
        $isAllday = boolval( get_post_meta($post->ID, '_piecal_is_allday', true) );
        $isAlldayChecked = $isAllday ? "checked='true'" : null;
    
        $startDate = get_post_meta($post->ID, '_piecal_start_date', true);
        $endDate = get_post_meta($post->ID, '_piecal_end_date', true);
    
    
        wp_nonce_field( 'piecal_classic_metabox_nonce', 'piecal_classic_metabox_nonce' );
    
        ?>
        <style>
            .piecal-metabox-wrapper {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }
    
            .piecal-metabox-wrapper > label {
                display: grid;
                grid-template-columns: 20% 80%;
                align-items: center;
            }
    
            .piecal-metabox-wrapper > label > input:not([type="checkbox"]) {
                max-width: 250px;
                width: 100%;
            }
        </style>
        <div class="piecal-metabox-wrapper">
            <label>
                <?php
                /* Translators: Label for post type toggle in Pie Calendar classic metabox. */
                echo esc_html__('Post Is Event', 'piecal');
                ?>
                <input type="checkbox" name="piecal_is_event" <?php echo esc_attr( $isEventChecked ); ?> value="1">
            </label>
            <label>
                <?php
                /* Translators: Label for all day event toggle in Pie Calendar classic metabox. */
                echo esc_html__('All Day Event', 'piecal');
                ?>
                <input type="checkbox" name="piecal_is_allday" <?php echo esc_attr( $isAlldayChecked ); ?> value="1">
            </label>
            <label>
                <?php
                /* Translators: Label for start date in Pie Calendar classic metabox. */
                echo esc_html__('Start Date', 'piecal');
                ?>
                <input type="datetime-local" name="piecal_start_date" value="<?php echo esc_attr( $startDate ); ?>">
            </label>
            <label>
                <?php
                /* Translators: Label for end date in Pie Calendar classic metabox. */
                echo esc_html__('End Date', 'piecal');
                ?>
                <input type="datetime-local" name="piecal_end_date" value="<?php echo esc_attr( $endDate ); ?>">
            </label>
        </div>
    
        <?php
    }
}

if ( ! function_exists( 'piecal_save_classic_metabox_data' ) ) {
    function piecal_save_classic_metabox_data( $post_id ) {

        //var_dump($_POST);
    
        // Check for nonce
         if ( ! isset( $_POST['piecal_classic_metabox_nonce'] ) ) {
             return;
         }
     
         // Verify nonce
         $nonce = isset( $_POST['piecal_classic_metabox_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['piecal_classic_metabox_nonce'] ) ) : '';
         if ( ! wp_verify_nonce( $nonce, 'piecal_classic_metabox_nonce' ) ) {
             return;
         }
     
         // Don't save on autosave
         if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
             return;
         }
     
         if ( ! isset( $_POST['piecal_is_event'] ) ) {
             update_post_meta( $post_id, '_piecal_is_event', '0' );
             return;
         }
     
         // Sanitize user input.
         $piecal_is_event_clean   = isset( $_POST['piecal_is_event'] ) ? rest_sanitize_boolean( $_POST['piecal_is_event'] ) : 0;
         $piecal_is_allday_clean  = isset( $_POST['piecal_is_allday'] ) ? rest_sanitize_boolean( $_POST['piecal_is_allday'] ) : 0;
         $piecal_start_date_clean = isset( $_POST['piecal_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['piecal_start_date'] ) ) : '';
         $piecal_end_date_clean   = isset( $_POST['piecal_end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['piecal_end_date'] ) ) : '';
        
    
        // Update the meta field in the database.
        update_post_meta( $post_id, '_piecal_is_event', $piecal_is_event_clean );
        update_post_meta( $post_id, '_piecal_is_allday', $piecal_is_allday_clean );
        update_post_meta( $post_id, '_piecal_start_date', $piecal_start_date_clean );
        update_post_meta( $post_id, '_piecal_end_date', $piecal_end_date_clean );
    }
}


add_action( 'save_post', 'piecal_save_classic_metabox_data' );