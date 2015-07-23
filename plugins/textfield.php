<?php
function customize_fields_make_textfield( $name, $sid, $data, $post_id ) {
    $cftnum = $size = $default = $hideKey = $label = $code = $class = $style = $before = $after = $maxlength = $multipleButton = $date = $dateFirstDayOfWeek = $dateFormat = $startDate = $endDate = $readOnly = $onclick = $ondblclick = $onkeydown = $onkeypress = $onkeyup = $onmousedown = $onmouseup = $onmouseover = $onmouseout = $onmousemove = $onfocus = $onblur = $onchange = $onselect = '';
    $hide = $addfield = $out = $out_key = $out_value = '';
    extract($data);
    $customFieldTemplateModle = customFieldTemplateModel::instance();
    $options = $customFieldTemplateModle->get_custom_field_template_data();

    $name = stripslashes($name);

    $title = $name;
    $name = $customFieldTemplateModle->sanitize_name( $name );
    $name_id = preg_replace( '/%/', '', $name );

    if ( isset($code) && is_numeric($code) ) :
        eval(stripcslashes($options['php'][$code]));
    endif;

    if ( !isset($_REQUEST['default']) || (isset($_REQUEST['default']) && $_REQUEST['default'] != true) ) $_REQUEST['default'] = false;

    if( isset( $post_id ) && $post_id > 0 && $_REQUEST['default'] != true ) {
        $value = $customFieldTemplateModle->get_post_meta( $post_id, $title, false );
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

    if ( isset($enforced_value) ) :
        $value = $enforced_value;
    endif;

    if ( isset($hideKey) && $hideKey == true ) $hide = ' class="hideKey"';
    if ( !empty($class) && $date == true ) $class = ' class="' . $class . ' datePicker"';
    elseif ( empty($class) && isset($date) && $date == true ) $class = ' class="datePicker"';
    elseif ( !empty($class) ) $class = ' class="' . $class . '"';
    if ( !empty($style) ) $style = ' style="' . $style . '"';
    if ( !empty($maxlength) ) $maxlength = ' maxlength="' . $maxlength . '"';
    if ( !empty($readOnly) ) $readOnly = ' readonly="readonly"';

    if ( !empty($label) && !empty($options['custom_field_template_replace_keys_by_labels']) )
        $title = stripcslashes($label);

    $event = array('onclick' => $onclick, 'ondblclick' => $ondblclick, 'onkeydown' => $onkeydown, 'onkeypress' => $onkeypress, 'onkeyup' => $onkeyup, 'onmousedown' => $onmousedown, 'onmouseup' => $onmouseup, 'onmouseover' => $onmouseover, 'onmouseout' => $onmouseout, 'onmousemove' => $onmousemove, 'onfocus' => $onfocus, 'onblur' => $onblur, 'onchange' => $onchange, 'onselect' => $onselect);
    $event_output = "";
    foreach($event as $key => $val) :
        if ( $val )
            $event_output .= " " . $key . '="' . stripcslashes(trim($val)) . '"';
    endforeach;

    if ( isset($multipleButton) && $multipleButton == true && $date != true && $ct_value == $cftnum ) :
        $addfield .= '<div style="margin-top:-1em;">';
        $addfield .= '<a href="#clear" onclick="jQuery(this).parent().parent().parent().clone().insertAfter(jQuery(this).parent().parent().parent()).find('."'input'".').val('."''".');jQuery(this).parent().css('."'visibility','hidden'".');jQuery(this).parent().prev().css('."'visibility','hidden'".'); return false;">' . __('Add New', 'custom-field-template') . '</a>';
        $addfield .= '</div>';
    endif;

    $out_key = '<span' . $hide . '><label for="' . $name_id . $sid . '_' . $cftnum . '">' . $title . '</label></span>'.$addfield;

    $out =
        '<dl id="dl_' . $name_id . $sid . '_' . $cftnum . '" class="dl_text">' .
        '<dt>'.$out_key.'</dt>' .
        '<dd>';

    if ( !empty($label) && empty($options['custom_field_template_replace_keys_by_labels']) )
        $out_value .= '<p class="label">' . stripcslashes($label) . '</p>';
    $out_value .= trim($before).'<input id="' . $name_id . $sid . '_' . $cftnum . '" name="' . $name . '['. $sid . '][]" value="' . esc_attr(trim($value)) . '" type="text" size="' . $size . '"' . $class . $style . $maxlength . $event_output . $readOnly . ' />'.trim($after);

    if ( $date == true ) :
        $out_value .= '<script type="text/javascript">' . "\n" .
            '// <![CDATA[' . "\n";
        if ( is_numeric($dateFirstDayOfWeek) ) $out_value .= 'Date.firstDayOfWeek = ' . stripcslashes(trim($dateFirstDayOfWeek)) . ";\n";
        if ( $dateFormat ) $out_value .= 'Date.format = "' . stripcslashes(trim($dateFormat)) . '"' . ";\n";
        $out_value .=	'jQuery(document).ready(function() { jQuery(".datePicker").css("float", "left"); jQuery(".datePicker").datePicker({';
        if ( $startDate ) $out_value .= "startDate: " . stripcslashes(trim($startDate));
        if ( $startDate && $endDate ) $out_value .= ",";
        if ( $endDate ) $out_value .= "endDate: " . stripcslashes(trim($endDate)) . "";
        $out_value .= '}); });' . "\n" .
            '// ]]>' . "\n" .
            '</script>';
    endif;

    $out .= $out_value.'</dd></dl>'."\n";

    return array($out, $out_key, $out_value);
}

if ( function_exists('current_user_can') && current_user_can('edit_plugins') ) {
    list($out_all,$out_key,$out_value) = customize_fields_make_textfield( $title, $parentSN, $data, $post_id );
}

