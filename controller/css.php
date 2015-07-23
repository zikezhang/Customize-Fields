<?php
if ( function_exists('current_user_can') && current_user_can('edit_plugins') ) {
    if ($_POST['custom_field_template_css']) {
        $parser = new CssParser();
        $parser->load_string(stripslashes($_POST['custom_field_template_css']));
        $parser->parse();
        if (!empty($parser->css)) {
            $options['css'] = $parser->css;
            update_option('custom_field_template_data', $options);
            $message = __('Options updated.', $base);
        }

    }
}
