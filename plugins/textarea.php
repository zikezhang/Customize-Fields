<?php
function customize_fields_make_textarea( $name, $sid, $data, $post_id ) {
    $cftnum = $rows = $cols = $tinyMCE = $htmlEditor = $mediaButton = $default = $hideKey = $label = $code = $class = $style = $wrap = $before = $after = $multipleButton = $mediaOffMedia = $mediaOffImage = $mediaOffVideo = $mediaOffAudio = $onclick = $ondblclick = $onkeydown = $onkeypress = $onkeyup = $onmousedown = $onmouseup = $onmouseover = $onmouseout = $onmousemove = $onfocus = $onblur = $onchange = $onselect = '';
    $hide = $addfield = $out = $out_key = $out_value = $media = $editorcontainer_class = '';
    extract($data);
    $customFieldTemplateModle = customFieldTemplateModel::instance();
    $options = $customFieldTemplateModle->get_custom_field_template_data();

    global $wp_version;

    $name = stripslashes($name);

    $title = $name;
    $name = $customFieldTemplateModle->sanitize_name( $name );
    $name_id = preg_replace( '/%/', '', $name );

    if ( is_numeric($code) ) :
        eval(stripcslashes($options['php'][$code]));
    endif;

    if ( !isset($_REQUEST['default']) || (isset($_REQUEST['default']) && $_REQUEST['default'] != true) ) $_REQUEST['default'] = false;

    if( isset( $post_id ) && $post_id > 0 && $_REQUEST['default'] != true ) {
        $value = $customFieldTemplateModle->get_post_meta( $post_id, $title );
        if ( !empty($value) && is_array($value) ) {
            $ct_value = count($value);
            $value = isset($value[ $cftnum ]) ? $value[ $cftnum ] : '';
        }
    } else {
        $value = stripslashes($default);
    }

    if ( empty($ct_value) ) :
        $ct_value = !empty($startNum) ? $startNum-1 : 1;
    endif;

    $rand = rand();
    $switch = '';
    $textarea_id = sha1($name . $rand).rand(0,9);

    if( $tinyMCE == true ) {
        $out_value = '<script type="text/javascript">' . "\n" .
            '// <![CDATA[' . "\n" .
            'jQuery(document).ready(function() {if ( typeof tinyMCE != "undefined" ) {' . "\n";

        if ( substr($wp_version, 0, 3) < '3.3' ) :
            $load_tinyMCE = 'tinyMCE.execCommand('."'mceAddControl'".', false, "'. $textarea_id . '");';
            $editorcontainer_class = ' class="editorcontainer"';
        elseif ( substr($wp_version, 0, 3) < '3.9' ) :
            $load_tinyMCE = 'var ed = new tinyMCE.Editor("'. $textarea_id . '", tinyMCEPreInit.mceInit["content"]); ed.render();';
            $editorcontainer_class = ' class="wp-editor-container"';
        else :
            $load_tinyMCE = '';
            if ( wp_default_editor() == 'html' ) $load_tinyMCE .= 'tinyMCE.init({"convert_urls": false, "relative_urls": false, "remove_script_host": false});';
            $load_tinyMCE .= 'tinyMCE.execCommand('."'mceAddEditor'".', false, "'. $textarea_id . '");';
            $editorcontainer_class = ' class="wp-editor-container"';
        endif;
        if ( !empty($options['custom_field_template_use_wpautop']) ) :
            $out_value .=	'document.getElementById("'. $textarea_id . '").value = document.getElementById("'. $textarea_id . '").value; '.$load_tinyMCE.' tinyMCEID.push("'. $textarea_id . '");' . "\n";
        else:
            $out_value .=	'document.getElementById("'. $textarea_id . '").value = switchEditors.wpautop(document.getElementById("'. $textarea_id . '").value); '.$load_tinyMCE.' tinyMCEID.push("'. $textarea_id . '");' . "\n";
        endif;
        $out_value .= '}});' . "\n";
        $out_value .= '// ]]>' . "\n" . '</script>';
    }

    if ( substr($wp_version, 0, 3) >= '2.5' ) {

        if ( !strstr($_SERVER['REQUEST_URI'], 'wp-admin/edit.php') && !strstr($_SERVER['REQUEST_URI'], 'wp-admin/edit-pages.php')  ) {

            if ( $mediaButton == true ) :
                $media_upload_iframe_src = "media-upload.php";

                if ( substr($wp_version, 0, 3) < '3.3' ) :
                    if ( !$mediaOffImage ) :
                        $image_upload_iframe_src = apply_filters('image_upload_iframe_src', "$media_upload_iframe_src?type=image");
                        $image_title = __('Add an Image');
                        $media .= "<a href=\"{$image_upload_iframe_src}&TB_iframe=true\" id=\"add_image{$rand}\" title='$image_title' onclick=\"focusTextArea('".$textarea_id."'); jQuery(this).attr('href',jQuery(this).attr('href').replace('\?','?post_id='+jQuery('#post_ID').val())); return thickbox(this);\"><img src='images/media-button-image.gif' alt='$image_title' /></a> ";
                    endif;
                    if ( !$mediaOffVideo ) :
                        $video_upload_iframe_src = apply_filters('video_upload_iframe_src', "$media_upload_iframe_src?type=video");
                        $video_title = __('Add Video');
                        $media .= "<a href=\"{$video_upload_iframe_src}&amp;TB_iframe=true\" id=\"add_video{$rand}\" title='$video_title' onclick=\"focusTextArea('".$textarea_id."'); jQuery(this).attr('href',jQuery(this).attr('href').replace('\?','?post_id='+jQuery('#post_ID').val())); return thickbox(this);\"><img src='images/media-button-video.gif' alt='$video_title' /></a> ";
                    endif;
                    if ( !$mediaOffAudio ) :
                        $audio_upload_iframe_src = apply_filters('audio_upload_iframe_src', "$media_upload_iframe_src?type=audio");
                        $audio_title = __('Add Audio');
                        $media .= "<a href=\"{$audio_upload_iframe_src}&amp;TB_iframe=true\" id=\"add_audio{$rand}\" title='$audio_title' onclick=\"focusTextArea('".$textarea_id."'); jQuery(this).attr('href',jQuery(this).attr('href').replace('\?','?post_id='+jQuery('#post_ID').val())); return thickbox(this);\"><img src='images/media-button-music.gif' alt='$audio_title' /></a> ";
                    endif;
                    if ( !$mediaOffMedia ) :
                        $media_title = __('Add Media');
                        $media .= "<a href=\"{$media_upload_iframe_src}?TB_iframe=true\" id=\"add_media{$rand}\" title='$media_title' onclick=\"focusTextArea('".$textarea_id."'); jQuery(this).attr('href',jQuery(this).attr('href').replace('\?','?post_id='+jQuery('#post_ID').val())); return thickbox(this);\"><img src='images/media-button-other.gif' alt='$media_title' /></a>";
                    endif;
                else :
                    $media_title = __('Add Media');
                    $media .= "<a href=\"{$media_upload_iframe_src}?TB_iframe=true\" id=\"add_media{$rand}\" title='$media_title' onclick=\"focusTextArea('".$textarea_id."'); jQuery(this).attr('href',jQuery(this).attr('href').replace('\?','?post_id='+jQuery('#post_ID').val())); return thickbox(this);\"><img src='images/media-button.png' alt='$media_title' /></a>";
                endif;
            endif;

            $switch = '<div>';
            if( $tinyMCE == true && user_can_richedit() ) {
                $switch .= '<a href="#toggle" onclick="switchMode(jQuery(this).parent().parent().parent().find(\'textarea\').attr(\'id\')); return false;">' . __('Toggle', 'custom-field-template') . '</a>';
            }
            $switch .= '</div>';
        }

    }

    if ( $hideKey == true ) $hide = ' class="hideKey"';
    $content_class = ' class="';
    if ( $htmlEditor == true || $tinyMCE == true ) :
        if ( substr($wp_version, 0, 3) < '3.3' ) :
            $content_class .= 'content';
        else :
            $content_class .= 'wp-editor-area';
        endif;
    endif;
    if ( !empty($class) ) $content_class .= ' ' . $class;
    $content_class .= '"';
    if ( !empty($style) ) $style = ' style="' . $style . '"';
    if ( !empty($wrap) && ($wrap == 'soft' || $wrap == 'hard' || $wrap == 'off') ) $wrap = ' wrap="' . $wrap . '"';

    if ( !empty($label) && !empty($options['custom_field_template_replace_keys_by_labels']) )
        $title = stripcslashes($label);

    $event = array('onclick' => $onclick, 'ondblclick' => $ondblclick, 'onkeydown' => $onkeydown, 'onkeypress' => $onkeypress, 'onkeyup' => $onkeyup, 'onmousedown' => $onmousedown, 'onmouseup' => $onmouseup, 'onmouseover' => $onmouseover, 'onmouseout' => $onmouseout, 'onmousemove' => $onmousemove, 'onfocus' => $onfocus, 'onblur' => $onblur, 'onchange' => $onchange, 'onselect' => $onselect);
    $event_output = "";
    foreach($event as $key => $val) :
        if ( $val )
            $event_output .= " " . $key . '="' . stripcslashes(trim($val)) . '"';
    endforeach;

    if ( $multipleButton == true && $ct_value == $cftnum ) :
        $addfield .= '<div style="margin-top:-1em;">';
        if ( !empty($htmlEditor) ) :
            if ( substr($wp_version, 0, 3) < '3.3' ) :
                $load_htmlEditor1 = 'jQuery(\'#qt_\'+original_id+\'_qtags\').remove();';
                $load_htmlEditor2 = 'qt_set(original_id);qt_set(new_id);';
                if( $tinyMCE == true ) : $load_htmlEditor2 .= ' jQuery(\'#qt_\'+original_id+\'_qtags\').hide(); jQuery(\'#qt_\'+new_id+\'_qtags\').hide();'; endif;
            else  :
                $load_htmlEditor1 = 'jQuery(\'#qt_\'+original_id+\'_toolbar\').remove();';
                $load_htmlEditor2 = 'new QTags(new_id);QTags._buttonsInit();';
                if( $tinyMCE == true ) : $load_htmlEditor2 .= ' jQuery(\'#qt_\'+new_id+\'_toolbar\').hide();'; endif;
            endif;
        endif;
        if ( !empty($tinyMCE) ) :
            if ( substr($wp_version, 0, 3) < '3.3' ) :
                $load_tinyMCE = 'tinyMCE.execCommand(' . "'mceAddControl'" . ',false, original_id);tinyMCE.execCommand(' . "'mceAddControl'" . ',false, new_id);';
            elseif ( substr($wp_version, 0, 3) < '3.9' ) :
                $load_tinyMCE = 'var ed = new tinyMCE.Editor(original_id, tinyMCEPreInit.mceInit[\'content\']); ed.render(); var ed = new tinyMCE.Editor(new_id, tinyMCEPreInit.mceInit[\'content\']); ed.render();';
            else :
                $load_tinyMCE = 'tinyMCE.execCommand('."'mceAddEditor'".', false, original_id);tinyMCE.execCommand('."'mceAddEditor'".', false, new_id);';
            endif;

            $addfield .= '<a href="#clear" onclick="var original_id; var new_id; jQuery(this).parent().parent().parent().find('."'textarea'".').each(function(){original_id = jQuery(this).attr('."'id'".');'.$load_htmlEditor1.'tinyMCE.execCommand(' . "'mceRemoveControl'" . ',true,jQuery(this).attr('."'id'".'));});var clone = jQuery(this).parent().parent().parent().clone().insertAfter(jQuery(this).parent().parent().parent()); clone.find('."'textarea'".').val('."''".');if(original_id.match(/([0-9])$/)) {var matchval = RegExp.$1;re = new RegExp(matchval, '."'ig'".');clone.html(clone.html().replace(re, parseInt(matchval)+1)); new_id = original_id.replace(/([0-9])$/, parseInt(matchval)+1);}if ( tinyMCE.get(jQuery(this).attr('."original_id".')) ) {'.$load_tinyMCE.'}jQuery(this).parent().css('."'visibility','hidden'".');'.$load_htmlEditor2.'jQuery(this).parent().prev().css('."'visibility','hidden'".'); return false;">' . __('Add New', 'custom-field-template') . '</a>';
        else :
            $addfield .= '<a href="#clear" onclick="var original_id; var new_id; jQuery(this).parent().parent().parent().find('."'textarea'".').each(function(){original_id = jQuery(this).attr('."'id'".');});'.$load_htmlEditor1.'var clone = jQuery(this).parent().parent().parent().clone().insertAfter(jQuery(this).parent().parent().parent()); clone.find('."'textarea'".').val('."''".');if(original_id.match(/([0-9]+)$/)) {var matchval = RegExp.$1;re = new RegExp(matchval, '."'ig'".');clone.html(clone.html().replace(re, parseInt(matchval)+1)); new_id = original_id.replace(/([0-9]+)$/, parseInt(matchval)+1);}'.$load_htmlEditor2.'jQuery(this).parent().css('."'visibility','hidden'".');jQuery(this).parent().prev().css('."'visibility','hidden'".'); return false;">' . __('Add New', 'custom-field-template') . '</a>';
        endif;
        $addfield .= '</div>';
    endif;

    $out_key = '<span' . $hide . '><label for="' . $name_id . $sid . '_' . $cftnum . '">' . $title . '</label></span><br />' . $addfield . $media . $switch;

    $out .=
        '<dl id="dl_' . $name_id . $sid . '_' . $cftnum . '" class="dl_textarea">' .
        '<dt>'.$out_key.'</dt>' .
        '<dd>';

    if ( !empty($label) && empty($options['custom_field_template_replace_keys_by_labels']) )
        $out_value .= '<p class="label">' . stripcslashes($label) . '</p>';

    $out_value .= trim($before);

    if ( ($htmlEditor == true || $tinyMCE == true) && substr($wp_version, 0, 3) < '3.3' ) $out_value .= '<div class="quicktags">';

    if ( $htmlEditor == true ) :
        if ( substr($wp_version, 0, 3) < '3.3' ) :
            if( $tinyMCE == true ) $quicktags_hide = ' jQuery(\'#qt_' . $textarea_id . '_qtags\').hide();';
            $out_value .= '<script type="text/javascript">' . "\n" . '// <![CDATA[' . '
            jQuery(document).ready(function() { qt_' . $textarea_id . ' = new QTags(\'qt_' . $textarea_id . '\', \'' . $textarea_id . '\', \'editorcontainer_' . $textarea_id . '\', \'more\'); ' . $quicktags_hide . ' });' . "\n" . '// ]]>' . "\n" . '</script>';
            $editorcontainer_class = ' class="editorcontainer"';
        else :
            if( $tinyMCE == true ) $quicktags_hide = ' jQuery(\'#qt_' . $textarea_id . '_toolbar\').hide();';
            $out_value .= '<script type="text/javascript">' . "\n" . '// <![CDATA[' . '
            jQuery(document).ready(function() { new QTags(\'' . $textarea_id . '\'); QTags._buttonsInit(); ' . $quicktags_hide . ' }); ' . "\n";
            $out_value .=  '// ]]>' . "\n" . '</script>';
            $editorcontainer_class = ' class="wp-editor-container"';
        endif;
    endif;

    $out_value .= '<div' . $editorcontainer_class . ' id="editorcontainer_' . $textarea_id . '" style="clear:none;"><textarea id="' . $textarea_id . '" name="' . $name . '[' . $sid . '][]" rows="' .$rows. '" cols="' . $cols . '"' . $content_class . $style . $event_output . $wrap . '>' . htmlspecialchars(trim($value)) . '</textarea><input type="hidden" name="'.$name.'_rand['.$sid.']" value="'.$rand.'" /></div>';
    if ( ($htmlEditor == true || $tinyMCE == true) && substr($wp_version, 0, 3) < '3.3' ) $out_value .= '</div>';
    $out_value .= trim($after);
    $out .= $out_value.'</dd></dl>'."\n";

    return array($out, $out_key, $out_value);
}
if ( function_exists('current_user_can') && current_user_can('edit_plugins') ) {
    list($out_all,$out_key,$out_value) = make_textarea( $title, $parentSN, $data, $post_id );
}
