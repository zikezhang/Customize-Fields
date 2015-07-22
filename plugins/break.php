<?php
if ( !empty($data['class']) ) $class = ' class="' . $data['class'] . '"';
if ( !empty($data['style']) ) $style = ' style="' . $data['style'] . '"';
$tmpout .= '</div><div' . $class . $style . '>';