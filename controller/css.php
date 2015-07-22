<?php
$options['css'] = $_POST['custom_field_template_css'];
update_option('custom_field_template_data', $options);
$message = __('Options updated.', 'custom-field-template');