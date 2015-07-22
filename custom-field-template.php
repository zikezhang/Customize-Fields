<?php
/*
Plugin Name: Customize Fields
Plugin URI: https://zhangzike.wordpress.com/2015/07/22/wordpress-customize-custom-fields-plugin/
Description: This plugin adds the customize wordpress custom fields on the customize post type and Post/Page.
Author: Jeremy Zhang
Author URI: https://zhangzike.wordpress.com/
Version: 1.0.0
Text Domain: customize-fields
Domain Path: /
*/

/*
This program is based on the custom-field-template plugin written by Hiroaki Miyashita.
I appreciate your efforts, Hiroaki Miyashita.

I will add some new feature and change the UI.
*/

/*  Copyright 2015 Jeremy

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
if ( ! defined( 'ABSPATH' ) ) exit;

// Load plugin class files
require_once('custom_field_template_init.php');
require_once('bootstrap.php');
require_once('custom_field_template_model.php');


function customFieldTemplate ()
{
    //$customFieldTemplateModle = customFieldTemplateModel::instance();

    $instance = customFieldTemplateBootStrap::instance( __FILE__, '1.0.0' );

    if ( is_null( $instance->init ) ) {
        $instance->init = customFieldTemplateInit::instance( $instance );
    }

     return $instance;
}


customFieldTemplate();