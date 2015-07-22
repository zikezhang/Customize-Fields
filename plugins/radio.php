<?php
function make_radio( $name, $sid, $data, $post_id ) {
    $cftnum = $values = $valueLabels = $clearButton = $default = $hideKey = $label = $code = $class = $style = $before = $after = $multipleButton = $onclick = $ondblclick = $onkeydown = $onkeypress = $onkeyup = $onmousedown = $onmouseup = $onmouseover = $onmouseout = $onmousemove = $onfocus = $onblur = $onchange = $onselect = '';
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
    $class .= ' '.$name_id . $sid;
    if ( !empty($class) ) $class = ' class="' . trim($class) . '"';
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
        $addfield .= '<a href="#clear" onclick="var tmp = jQuery(this).parent().parent().parent().clone().insertAfter(jQuery(this).parent().parent().parent());tmp.find('."'input'".').attr('."'checked',false".');if(tmp.find('."'input'".').attr('."'name'".').match(/\[([0-9]+)\]$/)) { matchval = RegExp.$1; matchval++;tmp.find('."'input'".').attr('."'name',".'tmp.find('."'input'".').attr('."'name'".').replace(/\[([0-9]+)\]$/, \'[\'+matchval+\']\'));}jQuery(this).parent().css('."'visibility','hidden'".');jQuery(this).parent().prev().css('."'visibility','hidden'".'); return false;">' . __('Add New', 'custom-field-template') . '</a>';
        $addfield .= '</div>';
    endif;

    $out_key = '<span' . $hide . '>' . $title . '</span>'.$addfield;

    if( $clearButton == true ) {
        $out_key .= '<div>';
        $out_key .= '<a href="#clear" onclick="jQuery(\'.'.$name_id . $sid.'\').attr(\'checked\', false); return false;">' . __('Clear', 'custom-field-template') . '</a>';
        $out_key .= '</div>';
    }

    $out .=
        '<dl id="dl_' . $name_id . $sid . '_' . $cftnum . '" class="dl_radio">' .
        '<dt>'.$out_key.'</dt>' .
        '<dd>';

    if ( !empty($label) && empty($options['custom_field_template_replace_keys_by_labels']) )
        $out_value .= '<p class="label">' . stripcslashes($label) . '</p>';
    $i = 0;

    $out_value .= trim($before).'<input name="' . $name . '[' . $sid . '][' . $cftnum . ']" value="" type="hidden" />';

    if ( is_array($values) ) :
        foreach( $values as $val ) {
            $value_id = preg_replace( '/%/', '', $customFieldTemplateModle->sanitize_name( $val ) );
            $id = $name_id . '_' . $value_id . '_' . $sid . '_' . $cftnum;

            $checked = ( stripcslashes(trim( $val )) == trim( $selected ) ) ? 'checked="checked"' : '';

            $out_value .=
                '<label for="' . $id . '" class="selectit"><input id="' . $id . '" name="' . $name . '[' . $sid . '][' . $cftnum . ']" value="' . esc_attr(trim(stripcslashes($val))) . '" ' . $checked . ' type="radio"' . $class . $style . $event_output . ' /> ';
            if ( isset($valueLabels[$i]) )
                $out_value .= stripcslashes(trim($valueLabels[$i]));
            else
                $out_value .= stripcslashes(trim($val));
            $out_value .= '</label> ';
            $i++;
        }
    endif;
    $out_value .= trim($after);
    $out .= $out_value.'</dd></dl>'."\n";

    return array($out, $out_key, $out_value);
}

$data['values'] = explode( '#', $data['value'] );
if ( isset($data['valueLabel']) ) $data['valueLabels'] = explode( '#', $data['valueLabel'] );
list($out_all,$out_key,$out_value) = make_radio( $title, $parentSN, $data, $post_id );