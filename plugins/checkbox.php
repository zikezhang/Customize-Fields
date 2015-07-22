<?php
function make_checkbox( $name, $sid, $data, $post_id ) {
    $cftnum = $value = $valueLabel = $checked = $hideKey = $label = $code = $class = $style = $before = $after = $onclick = $ondblclick = $onkeydown = $onkeypress = $onkeyup = $onmousedown = $onmouseup = $onmouseover = $onmouseout = $onmousemove = $onfocus = $onblur = $onchange = $onselect = '';
    $hide = $addfield = $out = $out_key = $out_value = '';
    extract($data);
    $customFieldTemplateModle = customFieldTemplateModel::instance();
    $options = $customFieldTemplateModle->get_custom_field_template_data();

    $name = stripslashes($name);

    $title = $name;
    $name = $customFieldTemplateModle->sanitize_name( $name );
    $name_id = preg_replace( '/%/', '', $name );

    if ( !$value ) $value = "true";

    if ( !isset($_REQUEST['default']) || (isset($_REQUEST['default']) && $_REQUEST['default'] != true) ) $_REQUEST['default'] = false;

    if( isset( $post_id ) && $post_id > 0 && $_REQUEST['default'] != true ) {
        $selected = $customFieldTemplateModle->get_post_meta( $post_id, $title );
        if ( $selected ) {
            if ( in_array(stripcslashes($value), $selected) ) $checked = 'checked="checked"';
        }
    } else {
        if( $checked == true )  $checked = ' checked="checked"';
    }

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

    $id = $name_id . '_' . $customFieldTemplateModle->sanitize_name( $value ) . '_' . $sid . '_' . $cftnum;

    $out_key = '<span' . $hide . '>' . $title . '</span>';

    $out .=
        '<dl id="dl_' . $id . '" class="dl_checkbox">' .
        '<dt>'.$out_key.'</dt>' .
        '<dd>';

    if ( !empty($label) && !$options['custom_field_template_replace_keys_by_labels'] && $cftnum == 0 )
        $out_value .= '<p class="label">' . stripcslashes($label) . '</p>';
    $out_value .=	'<label for="' . $id . '" class="selectit"><input id="' . $id . '" name="' . $name . '[' . $sid . '][' . $cftnum . ']" value="' . esc_attr(stripcslashes(trim($value))) . '"' . $checked . ' type="checkbox"' . $class . $style . $event_output . ' /> ';
    if ( $valueLabel )
        $out_value .= stripcslashes(trim($valueLabel));
    else
        $out_value .= stripcslashes(trim($value));
    $out_value .= '</label> ';

    $out .= $out_value.'</dd></dl>'."\n";

    return array($out, $out_key, $out_value);
}

list($out_all,$out_key,$out_value) = make_checkbox( $title, $parentSN, $data, $post_id );