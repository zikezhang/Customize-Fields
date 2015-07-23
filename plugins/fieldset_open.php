<?php
if ( function_exists('current_user_can') && current_user_can('edit_plugins') ) {
    $customFieldTemplateModle = customFieldTemplateModel::instance();
    $fieldset_open = 1;
    if (!empty($data['class'])) $class = ' class="' . $data['class'] . '"';
    if (!empty($data['style'])) $style = ' style="' . $data['style'] . '"';
    $tmpout .= '<fieldset' . $class . $style . '>' . "\n";
    $tmpout .= '<input type="hidden" name="' . $customFieldTemplateModle->sanitize_name($title) . '[]" value="1" />' . "\n";

    if (isset($data['multipleButton']) && $data['multipleButton'] == true) :
        $addfield .= ' <span>';
        if (isset($post_id)) $addbutton = $customFieldTemplateModle->get_post_meta($post_id, $title, true) - 1;
        if (!isset($addbutton) || $addbutton <= 0) $addbutton = 0;
        if ($data['cftnum'] / 2 == $addbutton) :
            if (substr($wp_version, 0, 3) < '3.3') :
                $load_htmlEditor1 = 'if ( jQuery(\'#qt_\'+jQuery(this).attr(' . "'id'" . ')+\'_qtags\').html() ) {jQuery(\'#qt_\'+jQuery(this).attr(' . "'id'" . ')+\'_qtags\').remove();';
                $load_htmlEditor2 = 'qt_set(textarea_html_ids[i]);';
                $load_tinyMCE = 'tinyMCE.execCommand(' . "'mceAddControl'" . ',false, textarea_tmce_ids[i]); switchMode(textarea_tmce_ids[i]); switchMode(textarea_tmce_ids[i]);';
            elseif (substr($wp_version, 0, 3) < '3.9') :
                $load_htmlEditor1 = 'if ( jQuery(\'#qt_\'+jQuery(this).attr(' . "'id'" . ')+\'_toolbar\').html() ) {jQuery(\'#qt_\'+jQuery(this).attr(' . "'id'" . ')+\'_toolbar\').remove();';
                $load_htmlEditor2 = 'new QTags(textarea_html_ids[i]);QTags._buttonsInit();';
                $load_tinyMCE = 'var ed = new tinyMCE.Editor(textarea_tmce_ids[i], tinyMCEPreInit.mceInit[\'content\']); ed.render(); switchMode(textarea_tmce_ids[i]); switchMode(textarea_tmce_ids[i]);';
            else :
                $load_htmlEditor1 = 'if ( jQuery(\'#qt_\'+jQuery(this).attr(' . "'id'" . ')+\'_toolbar\').html() ) {jQuery(\'#qt_\'+jQuery(this).attr(' . "'id'" . ')+\'_toolbar\').remove();';
                $load_htmlEditor2 = 'new QTags(textarea_html_ids[i]);QTags._buttonsInit();';
                $load_tinyMCE = 'tinyMCE.execCommand(' . "'mceAddEditor'" . ', true, textarea_tmce_ids[i]); switchMode(textarea_tmce_ids[i]); switchMode(textarea_tmce_ids[i]);';
            endif;
            $addfield .= '<input type="hidden" id="' . $customFieldTemplateModle->sanitize_name($title) . '_count" value="0" /><script type="text/javascript">jQuery(document).ready(function() {jQuery(\'#' . $customFieldTemplateModle->sanitize_name($title) . '_count\').val(0); });</script>';
            $addfield .= ' <a href="#clear" onclick="var textarea_tmce_ids = new Array();var textarea_html_ids = new Array();var html_start = 0;jQuery(this).parent().parent().parent().find(' . "'textarea'" . ').each(function(){if ( jQuery(this).attr(' . "'id'" . ') ) {' . $load_htmlEditor1 . 'if ( jQuery(\'#' . $customFieldTemplateModle->sanitize_name($title) . '_count\').val() == 0 ) html_start++;textarea_html_ids.push(jQuery(this).attr(' . "'id'" . '));}}ed = tinyMCE.get(jQuery(this).attr(' . "'id'" . ')); if(ed) {textarea_tmce_ids.push(jQuery(this).attr(' . "'id'" . '));tinyMCE.execCommand(' . "'mceRemoveControl'" . ',false,jQuery(this).attr(' . "'id'" . '));}});var checked_ids = new Array();jQuery(this).parent().parent().parent().find(' . "'input[type=radio]:checked'" . ').each(function(){checked_ids.push(jQuery(this).attr(' . "'id'" . '));});var tmp = jQuery(this).parent().parent().parent().clone().insertAfter(jQuery(this).parent().parent().parent());tmp.find(' . "'input'" . ').attr(' . "'checked',false" . ');for( var i=0;i<checked_ids.length;i++) { jQuery(' . "'#'+checked_ids[i]" . ').attr(' . "'checked'" . ', true); }tmp.find(' . "'input[type=text],input[type=hidden],input[type=file]'" . ').val(' . "''" . ');tmp.find(' . "'select'" . ').val(' . "''" . ');tmp.find(' . "'textarea'" . ').text(' . "''" . ');tmp.find(' . "'p'" . ').remove();tmp.find(' . "'dl'" . ').each(function(){if(jQuery(this).attr(' . "'id'" . ')){if(jQuery(this).attr(' . "'id'" . ').match(/_([0-9]+)$/)) {matchval = RegExp.$1;matchval++;jQuery(this).attr(' . "'id'," . 'jQuery(this).attr(' . "'id'" . ').replace(/_([0-9]+)$/, \'_\'+matchval));jQuery(this).find(' . "'textarea'" . ').each(function(){if(jQuery(this).attr(' . "'id'" . ').match(/([0-9]+)$/)) {var tmce_check = false;var html_check = false;for( var i=0;i<textarea_tmce_ids.length;i++) { if ( jQuery(this).attr(' . "'id'" . ')==textarea_tmce_ids[i] ) { tmce_check = true; } }for( var i=0;i<textarea_html_ids.length;i++) { if ( jQuery(this).attr(' . "'id'" . ')==textarea_html_ids[i] ) { html_check = true; } }  if ( tmce_check || html_check ) {matchval2 = RegExp.$1;jQuery(this).attr(' . "'id'," . 'jQuery(this).attr(' . "'id'" . ').replace(/([0-9]+)$/, parseInt(matchval2)+1));re = new RegExp(matchval2, ' . "'ig'" . ');jQuery(this).parent().parent().parent().html(jQuery(this).parent().parent().parent().html().replace(re, parseInt(matchval2)+1));if ( tmce_check ) textarea_tmce_ids.push(jQuery(this).attr(' . "'id'" . '));if ( html_check ) textarea_html_ids.push(jQuery(this).attr(' . "'id'" . '));}}jQuery(this).attr(' . "'name'," . 'jQuery(this).attr(' . "'name'" . ').replace(/\[([0-9]+)\]$/, \'[\'+matchval+\']\'));});jQuery(this).find(' . "'input'" . ').each(function(){if(jQuery(this).attr(' . "'id'" . ')){jQuery(this).attr(' . "'id'," . 'jQuery(this).attr(' . "'id'" . ').replace(/_([0-9]+)_/, \'_\'+matchval+\'_\'));jQuery(this).attr(' . "'id'," . 'jQuery(this).attr(' . "'id'" . ').replace(/_([0-9]+)$/, \'_\'+matchval));}if(jQuery(this).attr(' . "'name'" . ')){jQuery(this).attr(' . "'name'," . 'jQuery(this).attr(' . "'name'" . ').replace(/\[([0-9]+)\]$/, \'[\'+matchval+\']\'));}});jQuery(this).find(' . "'label'" . ').each(function(){jQuery(this).attr(' . "'for'," . 'jQuery(this).attr(' . "'for'" . ').replace(/_([0-9]+)_/, \'_\'+matchval+\'_\'));jQuery(this).attr(' . "'for'," . 'jQuery(this).attr(' . "'for'" . ').replace(/_([0-9]+)$/, \'_\'+matchval));jQuery(this).attr(' . "'for'," . 'jQuery(this).attr(' . "'for'" . ').replace(/\[([0-9]+)\]$/, \'[\'+matchval+\']\'));});}}});for( var i=html_start;i<textarea_html_ids.length;i++) { ' . $load_htmlEditor2 . ' }for( var i=html_start;i<textarea_tmce_ids.length;i++) { ' . $load_tinyMCE . ' }jQuery(this).parent().css(' . "'visibility','hidden'" . ');jQuery(\'#' . $customFieldTemplateModle->sanitize_name($title) . '_count\').val(parseInt(jQuery(\'#' . $customFieldTemplateModle->sanitize_name($title) . '_count\').val())+1);return false;">' . __('Add New', 'custom-field-template') . '</a>';
        else :
            $addfield .= ' <a href="#clear" onclick="jQuery(this).parent().parent().parent().remove();return false;">' . __('Delete', 'custom-field-template') . '</a>';
        endif;
        $addfield .= '</span>';
    endif;

    if (isset($data['legend']) || isset($addfield)) {
        if (!isset($data['legend'])) $data['legend'] = '';
        if (!isset($addfield)) $addfield = '';
        $tmpout .= '<legend>' . stripcslashes(trim($data['legend'])) . $addfield . '</legend>';
    };
}