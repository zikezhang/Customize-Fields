<?php
function make_file( $name, $sid, $data, $post_id ) {
    $cftnum = $size = $hideKey = $label = $class = $style = $before = $after = $multipleButton = $relation = $mediaLibrary = $mediaPicker = '';
    $hide = $addfield = $out = $out_key = $out_value = $picker = $inside_fieldset = '';
    extract($data);
    $customFieldTemplateModle = customFieldTemplateModel::instance();
    $options = $customFieldTemplateModle->get_custom_field_template_data();

    $name = stripslashes($name);

    $title = $name;
    $name = $customFieldTemplateModle->sanitize_name( $name );
    $name_id = preg_replace( '/%/', '', $name );

    if ( !isset($_REQUEST['default']) || (isset($_REQUEST['default']) && $_REQUEST['default'] != true) ) $_REQUEST['default'] = false;

    if( isset( $post_id ) && $post_id > 0 && $_REQUEST['default'] != true ) {
        $value = $customFieldTemplateModle->get_post_meta( $post_id, $title );
        $ct_value = count($value);
        $value = isset($value[ $cftnum ]) ? $value[ $cftnum ] : '';
    }

    if ( empty($ct_value) ) :
        $ct_value = !empty($startNum) ? $startNum-1 : 1;
    endif;

    if ( $hideKey == true ) $hide = ' class="hideKey"';
    if ( !empty($class) ) $class = ' class="' . $class . '"';
    if ( !empty($style) ) $style = ' style="' . $style . '"';

    if ( !empty($label) && !empty($options['custom_field_template_replace_keys_by_labels']) )
        $title = stripcslashes($label);

    if ( $multipleButton == true && $ct_value == $cftnum ) :
        $addfield .= '<div style="margin-top:-1em;">';
        $addfield .= '<a href="#clear" onclick="var tmp = jQuery(this).parent().parent().parent().clone().insertAfter(jQuery(this).parent().parent().parent());if(tmp.find('."'input[type=file]'".').attr('."'id'".').match(/([0-9]+)$/)) { matchval = RegExp.$1; matchval++;tmp.find('."'input[type=file]'".').attr('."'id',".'tmp.find('."'input[type=file]'".').attr('."'id'".').replace(/([0-9]+)$/, matchval));}if(tmp.find('."'input[type=hidden]'".').attr('."'id'".').match(/([0-9]+)_hide$/)) { matchval = RegExp.$1; matchval++;tmp.find('."'input[type=hidden]'".').attr('."'id',".'tmp.find('."'input[type=hidden]'".').attr('."'id'".').replace(/([0-9]+)_hide$/, matchval+'."'_hide'".'));}if(tmp.find('."'input[type=hidden]'".').attr('."'name'".').match(/\[([0-9]+)\]$/)) { matchval = RegExp.$1; matchval++;tmp.find('."'input[type=hidden]'".').attr('."'name',".'tmp.find('."'input[type=hidden]'".').attr('."'name'".').replace(/\[([0-9]+)\]$/, \'[\'+matchval+\']\'));}jQuery(this).parent().css('."'visibility','hidden'".');jQuery(this).parent().prev().css('."'visibility','hidden'".'); return false;">' . __('Add New', 'custom-field-template') . '</a>';
        $addfield .= '</div>';
    endif;

    if ( $relation == true ) $tab = 'gallery';
    else $tab = 'library';
    $media_upload_iframe_src = "media-upload.php";
    $image_upload_iframe_src = apply_filters('image_upload_iframe_src', "$media_upload_iframe_src?type=image&tab=library");

    if ( $mediaPicker == true ) :
        $picker = __(' OR ', 'custom-field-template');
        $picker .= '<a href="'.$image_upload_iframe_src.'&post_id='.$post_id.'&TB_iframe=1&tab='.$tab.'" class="thickbox" onclick="jQuery('."'#cft_current_template'".').val(jQuery(this).parent().parent().parent().';
        if ( $inside_fieldset ) $picker .= 'parent().';
        $picker .= 'parent().attr(\'id\').replace(\'cft_\',\'\'));jQuery('."'#cft_clicked_id'".').val(jQuery(this).parent().find(\'input\').attr(\'id\'));">'.__('Select by Media Picker', 'custom-field-template').'</a>';
    endif;

    $out_key = '<span' . $hide . '><label for="' . $name_id . $sid . '_' . $cftnum . '">' . $title . '</label></span>'.$addfield;

    $out .=
        '<dl id="dl_' . $name_id . $sid . '_' . $cftnum . '" class="dl_file">' .
        '<dt>'.$out_key.'</dt>' .
        '<dd>';

    if ( !empty($label) && !$options['custom_field_template_replace_keys_by_labels'] )
        $out_value .= '<p class="label">' . stripcslashes($label) . '</p>';
    $out_value .= trim($before).'<input id="' . $name_id . $sid . '_' . $cftnum . '" name="' . $name . '['.$sid.'][]" type="file" size="' . $size . '"' . $class . $style . ' onchange="if (jQuery(this).val()) { jQuery(\'#cft_save_button\'+jQuery(this).parent().parent().parent().parent().attr(\'id\').replace(\'cft_\',\'\')).attr(\'disabled\', true); jQuery(\'#post-preview\').hide(); } else { jQuery(\'#cft_save_button\').attr(\'disabled\', false); jQuery(\'#post-preview\').show(); }" />'.trim($after).$picker;

    if ( isset($value) && ( $value = intval($value) ) && $thumb_url = wp_get_attachment_image_src( $value, 'thumbnail', true ) ) :
        $thumb_url = $thumb_url[0];

        $post = get_post($value);
        $filename = basename($post->guid);
        $title = esc_attr(trim($post->post_title));

        if ( !empty($mediaLibrary) ) :
            $title = '<a href="'.$image_upload_iframe_src.'&post_id='.$post_id.'&TB_iframe=1&tab='.$tab.'" class="thickbox">'.$title.'</a>';
        endif;

        $out_value .= '<p><label for="'.$name . $sid . '_' . $cftnum . '_delete"><input type="checkbox" name="'.$name . '_delete[' . $sid . '][' . $cftnum . ']" id="'.$name_id . $sid . '_' . $cftnum . '_delete" value="1" class="delete_file_checkbox" /> ' . __('Delete', 'custom-field-template') . '</label> <img src="'.$thumb_url.'" width="32" height="32" style="vertical-align:middle;" /> ' . $title . ' </p>';
        $out_value .= '<input type="hidden" id="' . $name_id . $sid . '_' . $cftnum . '_hide" name="'.$name . '[' . $sid . '][' . $cftnum . ']" value="' . $value . '" />';
    else :
        $out_value .= '<input type="hidden" id="' . $name_id . $sid . '_' . $cftnum . '_hide" name="'.$name . '[' . $sid . '][' . $cftnum . ']" value="" />';
    endif;

    $out .= $out_value.'</dd></dl>'."\n";

    return array($out, $out_key, $out_value);
}
list($out_all,$out_key,$out_value) = make_file( $title, $parentSN, $data, $post_id );