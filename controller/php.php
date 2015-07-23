<?php
if ( function_exists('current_user_can') && current_user_can('edit_plugins') ) {
    unset($options['php']);
    for ($i = 0; $i < count($_POST["custom_field_template_php"]); $i++) {
        if (!empty($_POST["custom_field_template_php"][$i]))
            $options['php'][] = $_POST["custom_field_template_php"][$i];
    }
    update_option('custom_field_template_data', $options);
    $message = __('Options updated.', $base);
}