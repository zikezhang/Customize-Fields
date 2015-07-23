<?php
if ( function_exists('current_user_can') && current_user_can('edit_plugins') ) {
    delete_option('custom_field_template_data');
    $options = $this->model->options;
    $message = __('Options deleted.', 'custom-field-template');
}