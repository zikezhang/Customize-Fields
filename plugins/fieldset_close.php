<?php
if ( function_exists('current_user_can') && current_user_can('edit_plugins') ) {
    $fieldset_open = 0;
    $tmpout .= '</fieldset>';
}