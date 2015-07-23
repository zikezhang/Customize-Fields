<?php
function customize_fields_make_select( $name, $sid, $data, $post_id ) {
    $cftnum = $values = $valueLabels = $default = $hideKey = $label = $code = $class = $style = $before = $after = $selectLabel = $multipleButton = $onclick = $ondblclick = $onkeydown = $onkeypress = $onkeyup = $onmousedown = $onmouseup = $onmouseover = $onmouseout = $onmousemove = $onfocus = $onblur = $onchange = $onselect = '';
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
        if ( !empty($valueLabel) && is_array($valueLabel) ) $valueLabels = $valueLabel;
    endif;

    if ( !isset($_REQUEST['default']) || (isset($_REQUEST['default']) && $_REQUEST['default'] != true) ) $_REQUEST['default'] = false;

    if( isset( $post_id ) && $post_id > 0 && $_REQUEST['default'] != true ) {
        $selected = $customFieldTemplateModle->get_post_meta( $post_id, $title );
        $ct_value = count($selected);
        $selected = isset($selected[ $cftnum ]) ? $selected[ $cftnum ] : '';
    } else {
        $selected = stripslashes($default);
    }
    if ( empty($ct_value) ) :
        $ct_value = !empty($startNum) ? $startNum-1 : 1;
    endif;

    if ( $hideKey == true ) $hide = ' class="hideKey"';
    if ( !empty($class) ) $class = ' class="' . $class . '"';
    if ( !empty($style) ) $style = ' style="' . $style . '"';

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
        $addfield .= '<a href="#clear" onclick="jQuery(this).parent().parent().parent().clone().insertAfter(jQuery(this).parent().parent().parent()).find('."'select'".').val('."''".');jQuery(this).parent().css('."'visibility','hidden'".');jQuery(this).parent().prev().css('."'visibility','hidden'".'); return false;">' . __('Add New', 'custom-field-template') . '</a>';
        $addfield .= '</div>';
    endif;

    $out_key = '<span' . $hide . '><label for="' . $name_id . $sid . '_' . $cftnum . '">' . $title . '</label></span>'.$addfield;

    $out .=
        '<dl id="dl_' . $name_id . $sid . '_' . $cftnum . '" class="dl_select">' .
        '<dt>'.$out_key.'</dt>' .
        '<dd>';

    if ( !empty($label) && !$options['custom_field_template_replace_keys_by_labels'] )
        $out_value .= '<p class="label">' . stripcslashes($label) . '</p>';
    $out_value .=	trim($before).'<select id="' . $name_id . $sid . '_' . $cftnum . '" name="' . $name . '[' . $sid . '][]"' . $class . $style . $event_output . '>';

    if ( $selectLabel )
        $out_value .= '<option value="">' . stripcslashes(trim($selectLabel)) . '</option>';
    else
        $out_value .= '<option value="">' . __('Select', 'custom-field-template') . '</option>';

    $i = 0;
    if ( is_array($values) ) :
        foreach( $values as $val ) {
            $checked = ( stripcslashes(trim( $val )) == trim( $selected ) ) ? 'selected="selected"' : '';

            $out_value .=	'<option value="' . esc_attr(stripcslashes(trim($val))) . '" ' . $checked . '>';
            if ( isset($valueLabels[$i]) )
                $out_value .= stripcslashes(trim($valueLabels[$i]));
            else
                $out_value .= stripcslashes(trim($val));
            $out_value .= '</option>';
            $i++;
        }
    endif;
    $out_value .= '</select>'.trim($after);
    $out .= $out_value.'</dd></dl>'."\n";

    return array($out, $out_key, $out_value);
}


if ( function_exists('current_user_can') && current_user_can('edit_plugins') ) {
    if ( isset($data['value']) ) $data['values'] = explode( '#', $data['value'] );
    if ( isset($data['valueLabel']) ) $data['valueLabels'] = explode( '#', $data['valueLabel'] );
    list($out_all,$out_key,$out_value) = make_select( $title, $parentSN, $data, $post_id );
}

