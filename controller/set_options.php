<?php
unset($options['custom_fields']);
$j = 0;
for($i=0;$i<count($_POST["custom_field_template_content"]);$i++) {
    if( $_POST["custom_field_template_content"][$i] ) {
        if ( preg_match('/\[content\]|\[post_title\]|\[excerpt\]|\[action\]/i', $_POST["custom_field_template_content"][$i]) ) :
            $errormessage = __('You can not use the following words as the field key: `content`, `post_title`, and `excerpt`, and `action`.', 'custom-field-template');
        endif;
        if ( isset($_POST["custom_field_template_title"][$i]) ) $options['custom_fields'][$j]['title']   = $_POST["custom_field_template_title"][$i];
        if ( isset($_POST["custom_field_template_content"][$i]) ) $options['custom_fields'][$j]['content'] = $_POST["custom_field_template_content"][$i];
        if ( isset($_POST["custom_field_template_instruction"][$i]) ) $options['custom_fields'][$j]['instruction'] = $_POST["custom_field_template_instruction"][$i];
        if ( isset($_POST["custom_field_template_category"][$i]) ) $options['custom_fields'][$j]['category'] = $_POST["custom_field_template_category"][$i];
        if ( isset($_POST["custom_field_template_post"][$i]) ) $options['custom_fields'][$j]['post'] = $_POST["custom_field_template_post"][$i];
        if ( isset($_POST["custom_field_template_post_type"][$i]) ) $options['custom_fields'][$j]['post_type'] = $_POST["custom_field_template_post_type"][$i];
        if ( isset($_POST["custom_field_template_custom_post_type"][$i]) ) $options['custom_fields'][$j]['custom_post_type'] = $_POST["custom_field_template_custom_post_type"][$i];
        if ( isset($_POST["custom_field_template_template_files"][$i]) ) $options['custom_fields'][$j]['template_files'] = $_POST["custom_field_template_template_files"][$i];
        if ( isset($_POST["custom_field_template_disable"][$i]) ) $options['custom_fields'][$j]['disable'] = $_POST["custom_field_template_disable"][$i];
        $options['custom_fields'][$j]['format'] = isset($_POST["custom_field_template_format"][$i]) ? $_POST["custom_field_template_format"][$i] : '';
        $j++;
    }
}
update_option('custom_field_template_data', $options);
$message = __('Options updated.', 'custom-field-template');


