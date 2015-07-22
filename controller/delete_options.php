<?php
delete_option('custom_field_template_data');
$options = $this->model->options;
$message = __('Options deleted.', 'custom-field-template');