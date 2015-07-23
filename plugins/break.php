<?php
if ( function_exists('current_user_can') && current_user_can('edit_plugins') ) {
    if (!empty($data['class'])) {
        $class = ' class="' . $data['class'] . '"';
    }
    if (!empty($data['style'])) {
        $style = ' style="' . $data['style'] . '"';
    }
    $tmpout .= '</div><div' . $class . $style . '>';
}