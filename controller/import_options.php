<?php
if ( function_exists('current_user_can') && current_user_can('edit_plugins') ) {
    if (is_uploaded_file($_FILES['cftfile']['tmp_name'])) {
        ob_start();
        readfile($_FILES['cftfile']['tmp_name']);
        $import = ob_get_contents();
        ob_end_clean();
        $import = maybe_unserialize($import);
        update_option('custom_field_template_data', $import);
        $message = __('Options imported.', 'custom-field-template');
        $options = $this->model->options;
    }
}