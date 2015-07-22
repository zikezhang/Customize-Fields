<?php
unset($options['hook']);
$j = 0;
for($i=0;$i<count($_POST["custom_field_template_hook_content"]);$i++) {
    if( $_POST["custom_field_template_hook_content"][$i] ) {
        $options['hook'][$j]['position'] = !empty($_POST["custom_field_template_hook_position"][$i]) ? $_POST["custom_field_template_hook_position"][$i] : '';
        $options['hook'][$j]['content']  = $_POST["custom_field_template_hook_content"][$i];
        $options['hook'][$j]['custom_post_type'] = preg_replace('/\s/', '', $_POST["custom_field_template_hook_custom_post_type"][$i]);
        $options['hook'][$j]['category'] = preg_replace('/\s/', '', $_POST["custom_field_template_hook_category"][$i]);
        $options['hook'][$j]['use_php']  = !empty($_POST["custom_field_template_hook_use_php"][$i]) ? $_POST["custom_field_template_hook_use_php"][$i] : '';
        $options['hook'][$j]['feed']  = !empty($_POST["custom_field_template_hook_feed"][$i]) ? $_POST["custom_field_template_hook_feed"][$i] : '';
        $options['hook'][$j]['post_type']  = !empty($_POST["custom_field_template_hook_post_type"][$i]) ? $_POST["custom_field_template_hook_post_type"][$i] : '';
        $options['hook'][$j]['excerpt']  = !empty($_POST["custom_field_template_hook_excerpt"][$i]) ? $_POST["custom_field_template_hook_excerpt"][$i] : '';
        $j++;
    }
}
update_option('custom_field_template_data', $options);
$message = __('Options updated.', 'custom-field-template');