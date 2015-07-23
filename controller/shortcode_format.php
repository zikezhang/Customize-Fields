<?
if ( function_exists('current_user_can') && current_user_can('edit_plugins') ) {
    unset($options['shortcode_format'], $options['shortcode_format_use_php']);
    $j = 0;
    for ($i = 0; $i < count($_POST["custom_field_template_shortcode_format"]); $i++) {
        if (!empty($_POST["custom_field_template_shortcode_format"][$i])) :
            $options['shortcode_format'][$j] = $_POST["custom_field_template_shortcode_format"][$i];
            $options['shortcode_format_use_php'][$j] = isset($_POST["custom_field_template_shortcode_format_use_php"][$i]) ? $_POST["custom_field_template_shortcode_format_use_php"][$i] : '';
            $j++;
        endif;
    }
    update_option('custom_field_template_data', $options);
    $message = __('Options updated.', $base);
}