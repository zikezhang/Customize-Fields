<?php
/* todo $options = $this->options;*/
if ( function_exists('current_user_can') && current_user_can('edit_plugins') ) {
    $message = __('Tags rebuilt.', $base);
}