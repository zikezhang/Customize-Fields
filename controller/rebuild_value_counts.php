<?php
if ( function_exists('current_user_can') && current_user_can('edit_plugins') ) {
    $this->custom_field_template_rebuild_value_counts();
    $options = $this->options;
    $message = __('Value Counts rebuilt.', $base);
}