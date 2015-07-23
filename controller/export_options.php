<?php
if ( function_exists('current_user_can') && current_user_can('edit_plugins') ) {
    if ( isset($_POST['custom_field_template_export_options_submit']) ) {
        $filename = "cft".date('Ymd');
        header("Accept-Ranges: none");
        header("Content-Disposition: attachment; filename=$filename");
        header('Content-Type: application/octet-stream');
        echo maybe_serialize($options);
        exit();
    };
};