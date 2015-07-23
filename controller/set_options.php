<?php
if ( function_exists('current_user_can') && current_user_can('edit_plugins') ) {
    unset($options['custom_fields']);
    $j = 0;
    for ($i = 0; $i < count($_POST["custom_field_template_content"]); $i++) {
        if ($_POST["custom_field_template_content"][$i]) {
            if (preg_match('/\[content\]|\[post_title\]|\[excerpt\]|\[action\]/i', $_POST["custom_field_template_content"][$i])) {
                $errormessage = __('You can not use the following words as the field key: `content`, `post_title`, and `excerpt`, and `action`.', $base);
            } else {
                if (isset($_POST["custom_field_template_title"][$i])) {
                    $options['custom_fields'][$j]['title'] = sanitize_text_field($_POST["custom_field_template_title"][$i]);
                };

                if (isset($_POST["custom_field_template_content"][$i])) {
                    $options['custom_fields'][$j]['content'] = ($_POST["custom_field_template_content"][$i]);
                };
                if (isset($_POST["custom_field_template_instruction"][$i])) {
                    $options['custom_fields'][$j]['instruction'] = sanitize_text_field($_POST["custom_field_template_instruction"][$i]);
                };
                if (isset($_POST["custom_field_template_category"][$i])) {
                    $cat_IDs = explode(',', $_POST["custom_field_template_category"][$i]);
                    $cateIDs = [];
                    foreach ($cat_IDs as $cat_ID) {
                        if (get_the_category_by_ID($cat_ID) && $cat_ID!='') {
                            $cateIDs[] = $cat_ID;
                            //$cateIDs .= ',';
                        } else {

                        }
                    }
                    $cateIDs = implode(',',$cateIDs);
                    $options['custom_fields'][$j]['category'] = $cateIDs;
                };
                if (isset($_POST["custom_field_template_post"][$i])) {
                    $post_IDs = explode(',', $_POST["custom_field_template_post"][$i]);
                    $postIDs = [];
                    foreach ($post_IDs as $post_ID) {
                        if (FALSE === get_post_status($post_ID)) {
                            // The post does not exist
                        } else {
                            $postIDs[] = $post_ID;
                            //$postIDs .= ',';
                        }
                    }
                    $postIDs = implode(',',$postIDs);
                    $options['custom_fields'][$j]['post'] = $postIDs;
                };
                if (isset($_POST["custom_field_template_post_type"][$i])) {
                    $post_type = $_POST["custom_field_template_post_type"][$i];
                    if (in_array($post_type, ['post', 'page'])) {
                        $options['custom_fields'][$j]['post_type'] = $post_type;
                    }
                };
                if (isset($_POST["custom_field_template_custom_post_type"][$i])) {
                    $post_types = explode(',', $_POST["custom_field_template_custom_post_type"][$i]);
                    $postypes = [];
                    foreach ($post_types as $post_type) {
                        if ($post_type != '' && post_type_exists($post_type)) {
                            $postypes[] = $post_type;
                            //$postypes .= ',';
                        } else {

                        }
                    }
                    $postypes = implode(',',$postypes);
                    $options['custom_fields'][$j]['custom_post_type'] = $postypes;
                };
                if (isset($_POST["custom_field_template_template_files"][$i])) {
                    $template_files = explode(',', $_POST["custom_field_template_template_files"][$i]);
                    $templates = [];
                    foreach ($template_files as $template_file) {
                        if (locate_template($template_file) != '') {
                            $templates[] = $template_file;
                            //$templates .= ',';
                        }
                    }
                    $templates = implode(',',$templates);
                    $options['custom_fields'][$j]['template_files'] = $templates;
                };
                if (isset($_POST["custom_field_template_disable"][$i])) {
                    if ($_POST["custom_field_template_disable"][$i] = 1) {
                        $options['custom_fields'][$j]['disable'] = $_POST["custom_field_template_disable"][$i];
                    }

                };
                $options['custom_fields'][$j]['format'] = isset($_POST["custom_field_template_format"][$i]) ? $_POST["custom_field_template_format"][$i] : '';
            }
            $j++;
        }
    }
    update_option('custom_field_template_data', $options);
    $message = __('Options updated.', $base);
}

