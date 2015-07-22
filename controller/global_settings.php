<?php


        $options['custom_field_template_replace_keys_by_labels'] = isset($_POST['custom_field_template_replace_keys_by_labels']) ? 1 : '';
        $options['custom_field_template_use_multiple_insert'] = isset($_POST['custom_field_template_use_multiple_insert']) ? 1 : '';
        $options['custom_field_template_use_wpautop'] = isset($_POST['custom_field_template_use_wpautop']) ? 1 : '';
        $options['custom_field_template_use_autosave'] = isset($_POST['custom_field_template_use_autosave']) ? 1 : '';
        $options['custom_field_template_use_disable_button'] = isset($_POST['custom_field_template_use_disable_button']) ? 1 : '';
        $options['custom_field_template_disable_initialize_button'] = isset($_POST['custom_field_template_disable_initialize_button']) ? 1 : '';
        $options['custom_field_template_disable_save_button'] = isset($_POST['custom_field_template_disable_save_button']) ? 1 : '';
        $options['custom_field_template_disable_default_custom_fields'] = isset($_POST['custom_field_template_disable_default_custom_fields']) ? 1 : '';
        $options['custom_field_template_disable_quick_edit'] = isset($_POST['custom_field_template_disable_quick_edit']) ? 1 : '';
        $options['custom_field_template_disable_custom_field_column'] = isset($_POST['custom_field_template_disable_custom_field_column']) ? 1 : '';
        $options['custom_field_template_replace_the_title'] = isset($_POST['custom_field_template_replace_the_title']) ? 1 : '';
        $options['custom_field_template_deploy_box'] = isset($_POST['custom_field_template_deploy_box']) ? 1 : '';
        if ( !empty($options['custom_field_template_deploy_box']) ) :
            $options['css'] = preg_replace('/#cft /', '.cft ', $options['css']);
            $options['css'] = preg_replace('/#cft_/', '.cft_', $options['css']);
        endif;
        $options['custom_field_template_widget_shortcode'] = isset($_POST['custom_field_template_widget_shortcode']) ? 1 : '';
        $options['custom_field_template_excerpt_shortcode'] = isset($_POST['custom_field_template_excerpt_shortcode']) ? 1 : '';
        $options['custom_field_template_use_validation'] = isset($_POST['custom_field_template_use_validation']) ? 1 : '';
        $options['custom_field_template_before_list'] = isset($_POST['custom_field_template_before_list']) ? $_POST['custom_field_template_before_list'] : '';
        $options['custom_field_template_after_list'] = isset($_POST['custom_field_template_after_list']) ? $_POST['custom_field_template_after_list'] : '';
        $options['custom_field_template_before_value'] = isset($_POST['custom_field_template_before_value']) ? $_POST['custom_field_template_before_value'] : '';
        $options['custom_field_template_after_value'] = isset($_POST['custom_field_template_after_value']) ? $_POST['custom_field_template_after_value'] : '';
        $options['custom_field_template_replace_keys_by_labels'] = isset($_POST['custom_field_template_replace_keys_by_labels']) ? 1 : '';
        $options['custom_field_template_replace_keys_by_labels'] = isset($_POST['custom_field_template_replace_keys_by_labels']) ? 1 : '';
        $options['custom_field_template_replace_keys_by_labels'] = isset($_POST['custom_field_template_replace_keys_by_labels']) ? 1 : '';
        $options['custom_field_template_disable_ad'] = isset($_POST['custom_field_template_disable_ad']) ? 1 : '';
        update_option('custom_field_template_data', $options);
        $message = __('Options updated.', 'custom-field-template');


