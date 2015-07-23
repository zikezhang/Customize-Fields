<?php
if ( function_exists('current_user_can') && current_user_can('edit_plugins') ) {
    $this->model->install_custom_field_template_data();
    $this->model->install_custom_field_template_css();
    $options = $this->options;
    $message = __('Options resetted.', $base);
}