<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class customFieldTemplateInit
{


    private static $_instance = null;

    public $parent = null;

    public $base = '';

    public $settings = array();

    public $model;

    public function __construct ( $parent )
    {
        $this->parent = $parent;

        $this->base = 'custom-field-template';

        $this->model =  customFieldTemplateModel::instance();

        add_action( 'init', array($this, 'custom_field_template_init'), 100 );
        add_action( 'admin_menu', array(&$this, 'custom_field_template_admin_menu') );

        add_action( 'save_post', array(&$this, 'edit_meta_value'), 100, 2 );


        register_activation_hook(__FILE__, array(&$this, 'activate'));
        register_deactivation_hook(__FILE__, array(&$this, 'deactivate'));

        //add_filter( 'plugin_action_links', array(&$this, 'cp_filter_plugin_actions'), 100, 2 );
        add_filter( 'plugin_action_links_' . plugin_basename( $this->parent->file ) , array( $this, 'add_settings_link' ) );

    }

    /**
     * Activate the plugin
     */
    public static function activate()
    {
        // Do nothing
    } // END public static function activate
    /**
     * Deactivate the plugin
     */
    public static function deactivate()
    {
        // Do nothing
    } // END public static function deactivate

    public function add_settings_link ( $links ) {
        $settings_link = '<a href="options-general.php?page=' . $this->parent->_token . '">' . __( 'Settings', $this->parent->_token ) . '</a>';
        array_push( $links, $settings_link );
        return $links;
    }

    function custom_field_template_init()
    {

        global $wp_version;

        $options = $this->model->get_custom_field_template_data();

        if ( function_exists('load_plugin_textdomain') ) {
            if ( defined('WP_PLUGIN_DIR') ) {
                load_plugin_textdomain('custom-field-template', false, dirname( plugin_basename(__FILE__) ) );
            }
        }

        if( strstr($_SERVER['REQUEST_URI'], 'wp-admin/plugins.php') && ((isset($_GET['activate']) && $_GET['activate'] == 'true') || (isset($_GET['activate-multi']) && $_GET['activate-multi'] == 'true') ) ) {
            //$options = $this->get_custom_field_template_data();
            if( !$options ) {
                $this->model->install_custom_field_template_data();
                $this->model->install_custom_field_template_css();
            }
        }

        if (is_user_logged_in()) {
            if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'custom-field-template/custom-field-template.php') {
                if ( isset($_REQUEST['post']) ) {
                    if ($_REQUEST['cft_mode'] == 'selectbox') {
                        echo $this->custom_field_template_selectbox();
                        exit();
                    } elseif ($_REQUEST['cft_mode'] == 'ajaxsave') {
                        if ( $_REQUEST['post'] > 0 ) {
                            $this->edit_meta_value( $_REQUEST['post'], '' );
                            exit();
                        }
                    }
                } else {
                    if ($_REQUEST['cft_mode'] == 'ajaxload') {
                        if (isset($_REQUEST['id'])) {
                            $id = $_REQUEST['id'];
                        } elseif (isset($options['posts'][$_REQUEST['post']])) {
                            $id = $options['posts'][$_REQUEST['post']];
                        } else {
                            $filtered_cfts = $this->model->custom_field_template_filter();
                            $id = (count($filtered_cfts)>0)?$filtered_cfts[0]['id']:0;
                        }

                        list($body, $init_id) = $this->model->load_custom_field( $id );
                        echo $body;
                        exit();

                    }
                }
            }
        }

        if ( !empty($options['custom_field_template_widget_shortcode']) ) {
            add_filter('widget_text', 'do_shortcode');
        }

        if (substr($wp_version, 0, 3) >= '2.7') {
            if (empty($options['custom_field_template_disable_custom_field_column']) ) {
                add_action( 'manage_posts_custom_column', array(&$this, 'add_manage_posts_custom_column'), 10, 2 );
                add_filter( 'manage_posts_columns', array(&$this, 'add_manage_posts_columns') );
                add_action( 'manage_pages_custom_column', array(&$this, 'add_manage_posts_custom_column'), 10, 2 );
                add_filter( 'manage_pages_columns', array(&$this, 'add_manage_pages_columns') );
            }

            if (empty($options['custom_field_template_disable_quick_edit'])) {
                add_action( 'quick_edit_custom_box', array(&$this, 'add_quick_edit_custom_box'), 10, 2 );
            }
        }

        if (substr($wp_version, 0, 3) < '2.5' ) {
            add_action( 'simple_edit_form', array(&$this, 'insert_custom_field'), 1 );
            add_action( 'edit_form_advanced', array(&$this, 'insert_custom_field'), 1 );
            add_action( 'edit_page_form', array(&$this, 'insert_custom_field'), 1 );
        } else {
            if ( substr($wp_version, 0, 3) >= '3.3' && file_exists(ABSPATH . 'wp-admin/includes/screen.php') ) {
                require_once(ABSPATH . 'wp-admin/includes/screen.php');
            };

            require_once(ABSPATH . 'wp-admin/includes/template.php');

            if ( function_exists('remove_meta_box') && !empty($options['custom_field_template_disable_default_custom_fields']) ) {
                remove_meta_box('postcustom', 'post', 'normal');
                remove_meta_box('postcustom', 'page', 'normal');
                remove_meta_box('pagecustomdiv', 'page', 'normal');
            };

            if ( !empty($options['custom_field_template_deploy_box']) ) :
                if ( !empty($options['custom_fields']) ) :
                    $i = 0;
                    foreach ( $options['custom_fields'] as $key => $val ) :
                        if ( empty($options['custom_field_template_replace_the_title']) ) $title = __('Custom Field Template', 'custom-field-template');
                        else $title = $options['custom_fields'][$key]['title'];
                        if ( empty($options['custom_fields'][$key]['custom_post_type']) ) :
                            if ( empty($options['custom_fields'][$key]['post_type']) ) :
                                add_meta_box('cftdiv'.$i, $title, array(&$this, 'insert_custom_field'), 'post', 'normal', 'core', $key);
                                add_meta_box('cftdiv'.$i, $title, array(&$this, 'insert_custom_field'), 'page', 'normal', 'core', $key);
                            elseif ( $options['custom_fields'][$key]['post_type']=='post' ) :
                                add_meta_box('cftdiv'.$i, $title, array(&$this, 'insert_custom_field'), 'post', 'normal', 'core', $key);
                            elseif ( $options['custom_fields'][$key]['post_type']=='page' ) :
                                add_meta_box('cftdiv'.$i, $title, array(&$this, 'insert_custom_field'), 'page', 'normal', 'core', $key);
                            endif;
                        else :
                            $tmp_custom_post_type = explode(',', $options['custom_fields'][$key]['custom_post_type']);
                            $tmp_custom_post_type = array_filter( $tmp_custom_post_type );
                            $tmp_custom_post_type = array_unique(array_filter(array_map('trim', $tmp_custom_post_type)));
                            foreach ( $tmp_custom_post_type as $type ) :
                                add_meta_box('cftdiv'.$i, $title, array(&$this, 'insert_custom_field'), $type, 'normal', 'core', $key);
                            endforeach;
                        endif;
                        $i++;
                    endforeach;
                endif;
            else :
                add_meta_box('cftdiv', __('Custom Field Template', 'custom-field-template'), array(&$this, 'insert_custom_field'), 'post', 'normal', 'core');
                add_meta_box('cftdiv', __('Custom Field Template', 'custom-field-template'), array(&$this, 'insert_custom_field'), 'page', 'normal', 'core');
            endif;

            if ( empty($options['custom_field_template_deploy_box']) && is_array($options['custom_fields']) ) :
                $custom_post_type = array();
                foreach($options['custom_fields'] as $key => $val ) :
                    if ( isset($options['custom_fields'][$key]['custom_post_type']) ) :
                        $tmp_custom_post_type = explode(',', $options['custom_fields'][$key]['custom_post_type']);
                        $tmp_custom_post_type = array_filter( $tmp_custom_post_type );
                        $tmp_custom_post_type = array_unique(array_filter(array_map('trim', $tmp_custom_post_type)));
                        $custom_post_type = array_merge($custom_post_type, $tmp_custom_post_type);
                    endif;
                endforeach;
                if ( isset($custom_post_type) && is_array($custom_post_type) ) :
                    foreach( $custom_post_type as $val ) :
                        if ( function_exists('remove_meta_box') && !empty($options['custom_field_template_disable_default_custom_fields']) ) :
                            remove_meta_box('postcustom', $val, 'normal');
                        endif;
                        add_meta_box('cftdiv', __('Custom Field Template', 'custom-field-template'), array(&$this, 'insert_custom_field'), $val, 'normal', 'core');
                        if ( empty($options['custom_field_template_disable_custom_field_column']) ) :
                            add_filter( 'manage_'.$val.'_posts_columns', array(&$this, 'add_manage_pages_columns') );
                        endif;
                    endforeach;
                endif;
            endif;
        }



        if( strstr($_SERVER['REQUEST_URI'], 'wp-admin/post-new.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/post.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/page-new.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/page.php') ) {
            add_action('admin_head', array(&$this, 'custom_field_template_admin_head_buffer') );
            add_action('admin_footer', array(&$this, 'custom_field_template_admin_footer_buffer') );
        }

    }

    function custom_field_template_admin_menu() {
        add_options_page(__('Custom Field Template', $this->base), __('Custom Field Template', $this->parent->_token), 'manage_options', $this->parent->_token, array(&$this, 'custom_field_template_admin'));
    }

    function custom_field_template_selectbox() {
        $options = $options = $this->model->get_custom_field_template_data();

        if( count($options['custom_fields']) < 2 ) :
            return '&nbsp;';
        endif;

        $filtered_cfts = $this->model->custom_field_template_filter();

        if( count($filtered_cfts) < 1 ) :
            return '&nbsp;';
        endif;

        $out = '<select id="custom_field_template_select">';
        foreach ( $filtered_cfts as $filtered_cft ) :
            if ( isset($options['custom_fields'][$filtered_cft['id']]['disable']) ) :

            elseif ( isset($_REQUEST['post']) && isset($options['posts'][$_REQUEST['post']]) && $filtered_cft['id'] == $options['posts'][$_REQUEST['post']] ) :
                $out .= '<option value="' . $filtered_cft['id'] . '" selected="selected">' . stripcslashes($filtered_cft['title']) . '</option>';
            else :
                $out .= '<option value="' . $filtered_cft['id'] . '">' . stripcslashes($filtered_cft['title']) . '</option>';
            endif;
        endforeach;
        $out .= '</select> ';

        $out .= '<input type="button" class="button" value="' . __('Load', 'custom-field-template') . '" onclick="if(tinyMCEID.length) { for(i=0;i<tinyMCEID.length;i++) {tinyMCE.execCommand(\'mceRemoveControl\', false, tinyMCEID[i]);} tinyMCEID.length=0;};';
        $out .= ' var cftloading_select = function() {jQuery.ajax({type: \'GET\', url: \'?page=custom-field-template/custom-field-template.php&cft_mode=ajaxload&id=\'+jQuery(\'#custom_field_template_select\').val()+\'&post=\'+jQuery(\'#post_ID\').val(), success: function(html) {';
        if ( !empty($options['custom_field_template_replace_the_title']) ) :
            $out .= 'jQuery(\'#cftdiv h3 span\').text(jQuery(\'#custom_field_template_select :selected\').text());';
        endif;
        $out .= 'jQuery(\'#cft\').html(html);}});};';
        if ( !empty($options['custom_field_template_use_autosave']) ) :
            $out .= 'var fields = jQuery(\'#cft :input\').fieldSerialize();';
            $out .= 'jQuery.ajax({type: \'POST\', url: \'?page=custom-field-template/custom-field-template.php&cft_mode=ajaxsave&post=\'+jQuery(\'#post_ID\').val()+\'&custom-field-template-verify-key=\'+jQuery(\'#custom-field-template-verify-key\').val()+\'&\'+fields, success: cftloading_select});';
        else :
            $out .= 'cftloading_select();';
        endif;
        $out .= '" />';

        return $out;
    }

    function edit_meta_value( $id, $post ) {
        global $wpdb, $wp_version, $current_user;
        $options = $this->model->get_custom_field_template_data();
        $id      = (!isset( $id ) || isset($_REQUEST['post_ID']) )?$_REQUEST['post_ID']:'';
        if( !current_user_can('edit_post', $id) ){
            return $id;
        }

        if( isset($_REQUEST['custom-field-template-verify-key']) && !wp_verify_nonce($_REQUEST['custom-field-template-verify-key'], 'custom-field-template') )
        {
            return $id;
        }


        if ( !empty($_POST['wp-preview']) && $id != $post->ID ) {
            $id = $post->ID;
        }


        if ( !isset($_REQUEST['custom-field-template-id']) ) {
            if ( isset($options['posts'][$id]) ) {
                unset($options['posts'][$id]);
            }
            update_option('custom_field_template_data', $options);
            return $id;
        }


        if ( !empty($_REQUEST['custom-field-template-id']) && is_array($_REQUEST['custom-field-template-id']) ) :
            foreach ( $_REQUEST['custom-field-template-id'] as $cft_id ) :
                $fields = $this->model->get_custom_fields($cft_id);

                if ( $fields == null ){
                    continue;
                }

                if ( substr($wp_version, 0, 3) >= '2.8' ) {
                    if ( !class_exists('SimpleTags') && !empty($_POST['tax_input']['post_tag']) && is_string($_POST['tax_input']['post_tag']) ) {
                        $tags_input = explode(",", $_POST['tax_input']['post_tag']);
                    }
                } else {
                    if ( !class_exists('SimpleTags') && !empty($_POST['tags_input']) ) {
                        $tags_input = explode(",", $_POST['tags_input']);
                    }
                }

                $save_value = array();

                if ( !empty($_FILES) && is_array($_FILES) ) :
                    foreach($_FILES as $key => $val ) :
                        foreach( $val as $key2 => $val2 ) :
                            if ( is_array($val2) ) :
                                foreach( $val2 as $key3 => $val3 ) :
                                    foreach( $val3 as $key4 => $val4 ) :
                                        if ( !empty($val['name'][$key3][$key4]) ) :
                                            $tmpfiles[$key][$key3][$key4]['name']     = $val['name'][$key3][$key4];
                                            $tmpfiles[$key][$key3][$key4]['type']     = $val['type'][$key3][$key4];
                                            $tmpfiles[$key][$key3][$key4]['tmp_name'] = $val['tmp_name'][$key3][$key4];
                                            $tmpfiles[$key][$key3][$key4]['error']    = $val['error'][$key3][$key4];
                                            $tmpfiles[$key][$key3][$key4]['size']     = $val['size'][$key3][$key4];
                                        endif;
                                    endforeach;
                                endforeach;
                                break;
                            endif;
                        endforeach;
                    endforeach;
                endif;
                unset($_FILES);

                foreach( $fields as $field_key => $field_val) :
                    foreach( $field_val as $title => $data) :
                        //if ( is_numeric($data['parentSN']) ) $field_key = $data['parentSN'];
                        $name = $this->model->sanitize_name( $title );
                        $title = esc_sql(stripcslashes(trim($title)));

                        if ( isset($data['level']) && is_numeric($data['level']) && $current_user->user_level < $data['level'] ) :
                            $save_value[$title] = $this->model->get_post_meta($id, $title, false);
                            continue;
                        endif;

                        $field_key = 0;
                        if ( isset($_REQUEST[$name]) && is_array($_REQUEST[$name]) ) :
                            foreach( $_REQUEST[$name] as $tmp_key => $tmp_val ) :
                                $field_key = $tmp_key;
                                if ( is_array($tmp_val) ) $_REQUEST[$name][$tmp_key] = array_values($tmp_val);
                            endforeach;
                        endif;

                        switch ( $data['type'] ) :
                            case 'fieldset_open' :
                                $save_value[$title][0] = count($_REQUEST[$name]);
                                break;
                            default :

                                $value = isset($_REQUEST[$name][$field_key][$data['cftnum']]) ? trim($_REQUEST[$name][$field_key][$data['cftnum']]) : '';

                                if ( !empty($options['custom_field_template_use_wpautop']) && $data['type'] == 'textarea' && !empty($value) )
                                    $value = wpautop($value);
                                if ( isset($data['editCode']) && is_numeric($data['editCode']) ) :
                                    eval(stripcslashes($options['php'][$data['editCode']]));
                                endif;
                                if ( $data['type'] != 'file' ) :
                                    if( isset( $value ) && strlen( $value ) ) :
                                        if ( isset($data['insertTag']) && $data['insertTag'] == true ) :
                                            if ( !empty($data['tagName']) ) :
                                                $tags_input[trim($data['tagName'])][] = $value;
                                            else :
                                                $tags_input['post_tag'][] = $value;
                                            endif;
                                        endif;
                                        if ( isset($data['valueCount']) && $data['valueCount'] == true ) :
                                            $options['value_count'][$title][$value] = $this->model->set_value_count($title, $value, $id)+1;
                                        endif;
                                        if ( $data['type'] == 'textarea' && isset($_REQUEST['TinyMCE_' . $name . trim($_REQUEST[ $name."_rand" ][$field_key]) . '_size']) ) {
                                            preg_match('/cw=[0-9]+&ch=([0-9]+)/', $_REQUEST['TinyMCE_' . $name . trim($_REQUEST[ $name."_rand" ][$field_key]) . '_size'], $matched);
                                            $options['tinyMCE'][$id][$name][$field_key] = (int)($matched[1]/20);
                                        }
                                        $save_value[$title][] = $value;
                                    elseif ( isset($data['blank']) && $data['blank'] == true ) :
                                        $save_value[$title][] = '';
                                    else :
                                        $tmp_value = $this->model->get_post_meta( $id, $title, false );
                                        if ( $data['type'] == 'checkbox' ) :
                                            delete_post_meta($id, $title, $data['value']);
                                        else :
                                            if ( isset($tmp_value[$data['cftnum']]) ) delete_post_meta($id, $title, $tmp_value[$data['cftnum']]);
                                        endif;
                                    endif;
                                endif;

                                if ( $data['type'] == 'file' ) :
                                    if ( isset($_REQUEST[$name.'_delete'][$field_key][$data['cftnum']]) ) :
                                        if ( empty($data['mediaRemove']) ) wp_delete_attachment($value);
                                        delete_post_meta($id, $title, $value);
                                        unset($value);
                                    endif;
                                    if( isset($tmpfiles[$name][$field_key][$data['cftnum']]) ) :
                                        $_FILES[$title] = $tmpfiles[$name][$field_key][$data['cftnum']];
                                        if ( isset($value) ) :
                                            if ( empty($data['mediaRemove']) ) wp_delete_attachment($value);
                                        endif;

                                        if ( isset($data['relation']) && $data['relation'] == true ) :
                                            $upload_id = media_handle_upload($title, $id);
                                        else :
                                            $upload_id = media_handle_upload($title, '');
                                        endif;
                                        $save_value[$title][] = $upload_id;
                                        unset($_FILES);
                                    else :
                                        if ( !get_post($value) && $value ) :
                                            if ( isset($data['blank']) && $data['blank'] == true ) :
                                                $save_value[$title][] = '';
                                            endif;
                                        elseif ( $value ) :
                                            $save_value[$title][] = $value;
                                        else :
                                            if ( isset($data['blank']) && $data['blank'] == true ) :
                                                $save_value[$title][] = '';
                                            endif;
                                        endif;
                                    endif;
                                endif;
                        endswitch;
                    endforeach;
                endforeach;

                foreach( $save_value as $title => $values ) :
                    unset($delete);
                    if ( count($values) == 1 ) :
                        if ( !add_metadata( 'post', $id, $title, apply_filters('cft_'.rawurlencode($title), $values[0]), true ) ) :
                            if ( count($this->model->get_post_meta($id, $title, false))>1 ) :
                                delete_metadata( 'post', $id, $title );
                                add_metadata( 'post', $id, $title, apply_filters('cft_'.rawurlencode($title), $values[0]) );
                            else :
                                update_metadata( 'post', $id, $title, apply_filters('cft_'.rawurlencode($title), $values[0]) );
                            endif;
                        endif;
                    elseif ( count($values) > 1 ) :
                        $tmp = $this->model->get_post_meta( $id, $title, false );
                        if ( $tmp ) delete_metadata( 'post', $id, $title );
                        foreach($values as $val)
                            add_metadata( 'post', $id, $title, apply_filters('cft_'.rawurlencode($title), $val) );
                    endif;
                endforeach;

                if ( !empty($tags_input) && is_array($tags_input) ) {
                    foreach ( $tags_input as $tags_key => $tags_value ) {
                        if ( class_exists('SimpleTags') && $tags_key == 'post_tag' ) {
                            wp_cache_flush();
                            $taxonomy = wp_get_object_terms($id, 'post_tag', array());
                            if ( $taxonomy ) foreach($taxonomy as $val) {
                                $tags[] = $val->name;
                            };
                            if ( is_array($tags) ) {
                                $tags_value = array_merge($tags, $tags_value);
                            };
                        }

                        if ( is_array($tags_value) ) {
                            $tags_input = array_unique($tags_value);
                        } else {
                            $tags_input = $tags_value;
                        }

                        if ( substr($wp_version, 0, 3) >= '2.8' ) {
                            wp_set_post_terms( $id, $tags_value, $tags_key, true );
                        } elseif (substr($wp_version, 0, 3) >= '2.3') {
                            wp_set_post_tags( $id, $tags_value );
                        }
                    }
                }

                if ( empty($options['custom_field_template_deploy_box']) ) {
                    $options['posts'][$id] = $cft_id;
                };

            endforeach;
        endif;

        update_option('custom_field_template_data', $options);
        wp_cache_flush();

        do_action('cft_save_post', $id, $post);
    }

    function insert_custom_field($post, $args) {
        global $wp_version, $post, $wpdb;
        $options = $this->model->get_custom_field_template_data();
        $out = '';

        if( $options == null)
            return;

        if ( !$options['css'] ) {
            $this->model->install_custom_field_template_css();
            $options = $options = $this->model->get_custom_field_template_data();
        }

        if ( substr($wp_version, 0, 3) < '2.5' ) {
            $out .= '
<div class="dbx-b-ox-wrapper">
<fieldset id="seodiv" class="dbx-box">
<div class="dbx-h-andle-wrapper">
<h3 class="dbx-handle">' . __('Custom Field Template', 'custom-field-template') . '</h3>
</div>
<div class="dbx-c-ontent-wrapper">
<div class="dbx-content">';
        }

        if ( isset($args['args']) ) :
            $init_id = $args['args'];
            $suffix = $args['args'];
            $suffix2 = '_'.$args['args'];
            $suffix3 = $args['args'];
        else :
            if ( isset($_REQUEST['post']) ) $request_post = $_REQUEST['post'];
            else $request_post = '';
            if( isset($options['posts'][$request_post]) && count($options['custom_fields'])>$options['posts'][$request_post] ) :
                $init_id = $options['posts'][$request_post];
            else :
                $filtered_cfts = $this->model->custom_field_template_filter();
                if ( count($filtered_cfts)>0 ) :
                    $init_id = $filtered_cfts[0]['id'];
                else :
                    $init_id = 0;
                endif;
            endif;
            $suffix = '';
            $suffix2 = '';
            $suffix3 = '\'+jQuery(\'#custom-field-template-id\').val()+\'';
        endif;

        $out .= '<script type="text/javascript">' . "\n" .
            '// <![CDATA[' . "\n";
        $out .=		'jQuery(document).ready(function() {' . "\n";

        $fields = $this->model->get_custom_fields( $init_id );
        if ( user_can_richedit() ) :
            if ( is_array($fields) ) :
                foreach( $fields as $field_key => $field_val ) :
                    foreach( $field_val as $title => $data ) :
                        if( $data[ 'type' ] == 'textarea' && !empty($data['tinyMCE']) ) :
                            if ( substr($wp_version, 0, 3) >= '2.7' ) :
                                /*$out .=		'	if ( getUserSetting( "editor" ) == "html" ) {
    jQuery("#edButtonPreview").trigger("click"); }' . "\n";*/
                            else :
                                $out .=		'	if(wpTinyMCEConfig) if(wpTinyMCEConfig.defaultEditor == "html") { jQuery("#edButtonPreview").trigger("click"); }' . "\n";
                            endif;
                            break;
                        endif;
                    endforeach;
                endforeach;
            endif;
        endif;

        if ( empty($options['custom_field_template_deploy_box']) && !empty($options['custom_fields']) ) :
            if ( substr($wp_version, 0, 3) < '3.0' ) $taxonomy = 'categories';
            else $taxonomy = 'category';

            foreach ( $options['custom_fields'] as $key => $val ) :
                if ( !empty($val['category']) ) :
                    $val['category'] = preg_replace('/\s/', '', $val['category']);
                    $categories = explode(',', $val['category']);
                    $categories = array_filter($categories);
                    array_walk( $categories, create_function('&$v', '$v = trim($v);') );
                    $query = "SELECT * FROM `".$wpdb->prefix."term_taxonomy` WHERE term_id IN (".addslashes($val['category']).")";
                    $result = $wpdb->get_results($query, ARRAY_A);
                    $category_taxonomy = array();
                    if ( !empty($result) && is_array($result) ) :
                        for($i=0;$i<count($result);$i++) :
                            $category_taxonomy[$result[$i]['term_id']] = $result[$i]['taxonomy'];
                        endfor;
                    endif;
                    foreach($categories as $cat_id) :
                        if ( is_numeric($cat_id) ) :
                            if ( $taxonomy == 'category' ) $taxonomy = $category_taxonomy[$cat_id];
                            $out .=		'jQuery(\'#in-'.$category_taxonomy[$cat_id].'-' . $cat_id . '\').click(function(){if(jQuery(\'#in-'.$category_taxonomy[$cat_id].'-' . $cat_id . '\').attr(\'checked\') == true) { if(tinyMCEID.length) { for(i=0;i<tinyMCEID.length;i++) {tinyMCE.execCommand(\'mceRemoveControl\', false, tinyMCEID[i]);} tinyMCEID.length=0;}; jQuery.get(\'?page=custom-field-template/custom-field-template.php&cft_mode=selectbox&post=\'+jQuery(\'#post_ID\').val()+\'&\'+jQuery(\'#'.$taxonomy.'-all :input\').fieldSerialize(), function(html) { jQuery(\'#cft_selectbox\').html(html);';
                            if ( !empty($options['custom_field_template_use_autosave']) ) :
                                $out .= ' var fields = jQuery(\'#cft'.$suffix.' :input\').fieldSerialize();';
                                $out .= 'jQuery.ajax({type: \'POST\', url: \'?page=custom-field-template/custom-field-template.php&cft_mode=ajaxsave&post=\'+jQuery(\'#post_ID\').val()+\'&custom-field-template-verify-key=\'+jQuery(\'#custom-field-template-verify-key\').val()+\'&\'+fields, success: function(){jQuery(\'#custom_field_template_select\').val(\'' . $key . '\');jQuery.ajax({type: \'GET\', url: \'?page=custom-field-template/custom-field-template.php&cft_mode=ajaxload&id=' . $key . '&post=\'+jQuery(\'#post_ID\').val(), success: function(html) {';
                                if ( !empty($options['custom_field_template_replace_the_title']) ) :
                                    $out .= 'jQuery(\'#cftdiv'.$suffix.' h3 span\').text(\'' . $options['custom_fields'][$key]['title'] . '\');';
                                endif;
                                $out .= 'jQuery(\'#cft\').html(html);}});}});';
                            else :
                                $out .=		'	jQuery(\'#custom_field_template_select\').val(\'' . $key . '\');jQuery.ajax({type: \'GET\', url: \'?page=custom-field-template/custom-field-template.php&cft_mode=ajaxload&id=' . $key . '&post=\'+jQuery(\'#post_ID\').val()+\'&\'+jQuery(\'#'.$taxonomy.'-all :input\').fieldSerialize(), success: function(html) {';
                                if ( !empty($options['custom_field_template_replace_the_title']) ) :
                                    $out .= 'jQuery(\'#cftdiv'.$suffix.' h3 span\').text(\'' . $options['custom_fields'][$key]['title'] . '\');';
                                endif;
                                $out .= 'jQuery(\'#cft\').html(html);}});';
                            endif;
                            $out .= ' });';

                            $out .=		'	}else{ jQuery(\'#cft\').html(\'\');jQuery.get(\'?page=custom-field-template/custom-field-template.php&cft_mode=selectbox&post=\'+jQuery(\'#post_ID\').val()+\'&\'+jQuery(\'#'.$taxonomy.'-all :input\').fieldSerialize(), function(html) { jQuery(\'#cft_selectbox\').html(html); jQuery.ajax({type: \'GET\', url: \'?page=custom-field-template/custom-field-template.php&cft_mode=ajaxload&post=\'+jQuery(\'#post_ID\').val()+\'&\'+jQuery(\'#'.$taxonomy.'-all :input\').fieldSerialize(), success: function(html) { jQuery(\'#cft\').html(html);}}); });';
                            if ( !empty($options['custom_field_template_replace_the_title']) ) :
                                $out .= 'jQuery(\'#cftdiv'.$suffix.' h3 span\').text(\'' . __('Custom Field Template', 'custom-field-template') . '\');';
                            endif;
                            $out .= '}});' . "\n";
                        endif;
                    endforeach;
                endif;
            endforeach;
        endif;

        if ( empty($options['custom_field_template_deploy_box']) && 0 != count( get_page_templates() ) ):
            if ( empty($_REQUEST['post_type']) ) $_REQUEST['post_type'] = 'post';
            $out .=	'jQuery(\'#page_template\').change(function(){ if(tinyMCEID.length) { for(i=0;i<tinyMCEID.length;i++) {tinyMCE.execCommand(\'mceRemoveControl\', false, tinyMCEID[i]);} tinyMCEID.length=0;}; jQuery.get(\'?post_type='.$_REQUEST['post_type'].'&page=custom-field-template/custom-field-template.php&cft_mode=selectbox&post=\'+jQuery(\'#post_ID\').val()+\'&page_template=\'+jQuery(\'#page_template\').val(), function(html) { jQuery(\'#cft_selectbox\').html(html); jQuery.ajax({type: \'GET\', url: \'?post_type='.$_REQUEST['post_type'].'&page=custom-field-template/custom-field-template.php&cft_mode=ajaxload&page_template=\'+jQuery(\'#page_template\').val()+\'&post=\'+jQuery(\'#post_ID\').val(), success: function(html) { jQuery(\'#cft\').html(html);';
            if ( !empty($options['custom_field_template_replace_the_title']) ) :
                $out .= 'if(html) { jQuery(\'#cftdiv'.$suffix.' h3 span\').text(jQuery(\'#custom_field_template_select :selected\').text());}';
            endif;
            $out .= '}});});';
            $out .= '});' . "\n";
        endif;

        $out .= 	'	jQuery(\'#cftloading_img'.$suffix.'\').ajaxStart(function() { jQuery(this).show();});';
        $out .= 	'	jQuery(\'#cftloading_img'.$suffix.'\').ajaxStop(function() { jQuery(this).hide();});';
        $out .=		'});' . "\n";

        $out .=		'var tinyMCEID = new Array();' . "\n" .
            '// ]]>' . "\n" .
            '</script>';
        list($body, $init_id) = $this->model->load_custom_field($init_id);

        if ( empty($options['custom_field_template_deploy_box']) ) :
            $out .= '<div id="cft_selectbox">';
            $out .= $this->custom_field_template_selectbox();
            $out .= '</div>';
        else :
            $out .= '<div>&nbsp;</div>';
        endif;

        $out .= '<div id="cft'.$suffix.'" class="cft">';
        $out .= $body;
        $out .= '</div>';

        if ( substr($wp_version, 0, 3) < '3.3' ) :
            $top_margin = 30;
        else :
            $top_margin = 0;
        endif;

        $out .= '<div style="position:absolute; top:'.$top_margin.'px; right:5px;">';
        $out .= '<img class="waiting" style="display:none; vertical-align:middle;" src="images/loading.gif" alt="" id="cftloading_img'.$suffix.'" /> ';
        if ( !empty($options['custom_field_template_use_disable_button']) ) :
            $out .= '<input type="hidden" id="disable_value" value="0" />';
            $out .= '<input type="button" value="' . __('Disable', 'custom-field-template') . '" onclick="';
            $out .= 'if(jQuery(\'#disable_value\').val()==0) { jQuery(\'#disable_value\').val(1);jQuery(this).val(\''.__('Enable', 'custom-field-template').'\');jQuery(\'#cft'.$suffix2.' input, #cft'.$suffix2.' select, #cft'.$suffix2.' textarea\').attr(\'disabled\',true);}else{  jQuery(\'#disable_value\').val(0);jQuery(this).val(\''.__('Disable', 'custom-field-template').'\');jQuery(\'#cft'.$suffix2.' input, #cft_'.$init_id.' select, #cft'.$suffix2.' textarea\').attr(\'disabled\',false);}';
            $out .= '" class="button" style="vertical-align:middle;" />';
        endif;
        if ( empty($options['custom_field_template_disable_initialize_button']) ) :
            $out .= '<input type="button" value="' . __('Initialize', 'custom-field-template') . '" onclick="';
            $out .= 'if(confirm(\''.__('Are you sure to reset current values? Default values will be loaded.', 'custom-field-template').'\')){if(tinyMCEID.length) { for(i=0;i<tinyMCEID.length;i++) {tinyMCE.execCommand(\'mceRemoveControl\', false, tinyMCEID[i]);} tinyMCEID.length=0;};jQuery.ajax({type: \'GET\', url: \'?page=custom-field-template/custom-field-template.php&cft_mode=ajaxload&default=true&id='.$suffix3.'&post=\'+jQuery(\'#post_ID\').val(), success: function(html) {';
            $out .= 'jQuery(\'#cft'.$suffix2.'\').html(html);}});}';
            $out .= '" class="button" style="vertical-align:middle;" />';
        endif;
        if ( empty($options['custom_field_template_disable_save_button']) ) :
            $out .= '<input type="button" id="cft_save_button'.$suffix.'" value="' . __('Save', 'custom-field-template') . '" onclick="';
            if ( !empty($options['custom_field_template_use_validation']) ) :
                $out .= 'if(!jQuery(\'#post\').valid()) return false;';
            endif;
            $out .= 'tinyMCE.triggerSave(); var fields = jQuery(\'#cft'.$suffix2.' :input\').fieldSerialize();';
            $out .= 'jQuery.ajax({type: \'POST\', url: \'?page=custom-field-template/custom-field-template.php&cft_mode=ajaxsave&post=\'+jQuery(\'#post_ID\').val()+\'&custom-field-template-verify-key=\'+jQuery(\'#custom-field-template-verify-key\').val(), data: fields, success: function() {jQuery(\'.delete_file_checkbox:checked\').each(function() {jQuery(this).parent().parent().remove();});}});';
            $out .= '" class="button" style="vertical-align:middle;" />';
        endif;
        $out .= '</div>';

        if ( substr($wp_version, 0, 3) < '2.5' ) {
            $out .= '</div></fieldset></div>';
        } else {
            if ( $body && !empty($options['custom_field_template_replace_the_title']) && empty($options['custom_field_template_deploy_box']) ) :
                $out .= '<script type="text/javascript">' . "\n" . '// <![CDATA[' . "\n";
                $out .=	'jQuery(document).ready(function() {jQuery(\'#cftdiv h3 span\').text(\'' . $options['custom_fields'][$init_id]['title'] . '\');});' . "\n";
                $out .= '// ]]>' . "\n" . '</script>';
            endif;
        }

        $out .= '<div style="clear:both;"></div>';
        echo $out;
    }

    function add_manage_posts_custom_column($column_name, $post_id) {
        $data = $this->model->get_post_meta($post_id);

        if( is_array($data) && $column_name == 'custom-fields' ) :
            $flag = 0;
            $content = $output = '';
            foreach($data as $key => $val) :
                if ( substr($key, 0, 1) == '_' || !$val[0] ) continue;
                $content .= '<p class="key">' . $key . '</p>' . "\n";
                foreach($val as $val2) :
                    $val2 = htmlspecialchars($val2, ENT_QUOTES);
                    if ( $flag ) :
                        $content .= '<p class="value">' . $val2 . '</p>' . "\n";
                    else :
                        if ( function_exists('mb_strlen') ) :
                            if ( mb_strlen($val2) > 50 ) :
                                $before_content = mb_substr($val2, 0, 50);
                                $after_content  = mb_substr($val2, 50);
                                $content .= '<p class="value">' . $before_content . '[[[break]]]' . '<p class="value">' . $after_content . '</p>' . "\n";
                                $flag = 1;
                            else :
                                $content .= '<p class="value">' . $val2 . '</p>' . "\n";
                            endif;
                        else :
                            if ( strlen($val2) > 50 ) :
                                $before_content = substr($val2, 0, 50);
                                $after_content  = substr($val2, 50);
                                $content .= '<p class="value">' . $before_content . '[[[break]]]' . '<p class="value">' . $after_content . '</p>' . "\n";
                                $flag = 1;
                            else :
                                $content .= '<p class="value">' . $val2 . '</p>' . "\n";
                            endif;
                        endif;
                    endif;
                endforeach;
            endforeach;
            if ( $content ) :
                $content = preg_replace('/([^\n]+)\n([^\n]+)\n([^\n]+)\n([^\n]+)\n([^$]+)/', '\1\2\3\4[[[break]]]\5', $content);
                @list($before, $after) = explode('[[[break]]]', $content, 2);
                $after = preg_replace('/\[\[\[break\]\]\]/', '', $after);
                $output .= '<div class="cft_list">';
                $output .= balanceTags($before, true);
                if ( $after ) :
                    $output .= '<span class="hide-if-no-js-cft"><a href="javascript:void(0);" onclick="jQuery(this).parent().next().show(); jQuery(this).parent().next().next().show(); jQuery(this).parent().hide();">... ' . __('read more', 'custom-field-template') . '</a></span>';
                    $output .= '<span class="hide-if-js-cft">' . balanceTags($after, true) . '</span>';
                    $output .= '<span style="display:none;"><a href="javascript:void(0);" onclick="jQuery(this).parent().prev().hide(); jQuery(this).parent().prev().prev().show(); jQuery(this).parent().hide();">[^]</a></span>';
                endif;
                $output .= '</div>';
            else :
                $output .= '&nbsp;';
            endif;
        endif;

        if ( isset($output) ) echo $output;
    }

    function add_manage_posts_columns($columns) {
        $new_columns = array();
        foreach($columns as $key => $val) {
            $new_columns[$key] = $val;
            if ( $key == 'tags' ) {
                $new_columns['custom-fields'] = __('Custom Fields', 'custom-field-template');
            };
        };
        return $new_columns;
    }

    function add_manage_pages_columns($columns) {
        $new_columns = array();
        foreach($columns as $key => $val) {
            $new_columns[$key] = $val;
            if ( $key == 'author' ) {
                $new_columns['custom-fields'] = __('Custom Fields', 'custom-field-template');
            };
        };
        return $new_columns;
    }

    function add_quick_edit_custom_box($column_name, $type) {
        if( $column_name == 'custom-fields' ) {
            global $wp_version;
            $options = $this->model->get_custom_field_template_data();

            if( $options == null)
                return;

            if ( !$options['css'] ) {
                $this->model->install_custom_field_template_css();
                $options = $this->model->get_custom_field_template_data();
            }

            $out = '';
            $out .= '<fieldset style="clear:both;">' . "\n";
            $out .= '<div class="inline-edit-group">';
            $out .=	'<style type="text/css">' . "\n" .
                '<!--' . "\n";
            $out .=	$options['css'] . "\n";
            $out .=	'-->' . "\n" .
                '</style>';

            if ( count($options['custom_fields'])>1 ) {
                $out .= '<select id="custom_field_template_select">';
                for ( $i=0; $i < count($options['custom_fields']); $i++ ) {
                    if ( isset($_REQUEST['post']) && isset($options['posts'][$_REQUEST['post']]) && $i == $options['posts'][$_REQUEST['post']] ) {
                        $out .= '<option value="' . $i . '" selected="selected">' . stripcslashes($options['custom_fields'][$i]['title']) . '</option>';
                    } else
                        $out .= '<option value="' . $i . '">' . stripcslashes($options['custom_fields'][$i]['title']) . '</option>';
                }
                $out .= '</select>';
                $out .= '<input type="button" class="button" value="' . __('Load', 'custom-field-template') . '" onclick="var post = jQuery(this).parent().parent().parent().parent().attr(\'id\').replace(\'edit-\',\'\'); var cftloading_select = function() {jQuery.ajax({type: \'GET\', url: \'?page=custom-field-template/custom-field-template.php&cft_mode=ajaxload&id=\'+jQuery(\'#custom_field_template_select\').val()+\'&post=\'+post, success: function(html) {jQuery(\'#cft\').html(html);}});};cftloading_select(post);" />';
            }

            $out .= '<input type="hidden" name="custom-field-template-verify-key" id="custom-field-template-verify-key" value="' . wp_create_nonce('custom-field-template') . '" />';
            $out .= '<div id="cft" class="cft">';
            $out .= '</div>';

            $out .= '</div>' . "\n";
            $out .= '</fieldset>' . "\n";

            echo $out;
        };
    }

    function custom_field_template_admin_head_buffer() {
        ob_start(array(&$this, 'custom_field_template_add_enctype'));
    }

    function custom_field_template_add_enctype($buffer) {
        $buffer = preg_replace('/<form name="post"/', '<form enctype="multipart/form-data" name="post"', $buffer);
        return $buffer;
    }

    function custom_field_template_admin_footer_buffer() {
        ob_end_flush();
    }

    function custom_field_template_admin() {
        global $wp_version;
        $locale = get_locale();
        $base = $this->base;
        $options = $this->model->get_custom_field_template_data();
        //$controllerUrl =   esc_url( trailingslashit( plugins_url( '/controller/', $this->parent->file ) ) );
        require_once( 'custom_field_template_router.php' );

        foreach ($submitButtonArray as $setableOption) {
            if (!empty($_POST['custom_field_template_'.$setableOption.'_submit'])) {
                require_once('controller/'.$setableOption.'.php');
            }
        }

        require( 'view/layout.phtml' );
        //echo $viewTemplate;
    }

    function custom_field_template_rebuild_value_counts() {
        global $wpdb;
        $options = $this->options;
        unset($options['value_count']);
        set_time_limit(0);

        if ( is_array($options['custom_fields']) ) :
            for($j=0;$j<count($options['custom_fields']);$j++) :

                $fields = $this->model->get_custom_fields($j);

                if ( $fields == null )
                    return;

                foreach( $fields as $field_key => $field_val) :
                    foreach( $field_val as $title	=> $data) :
                        $name = $this->model->sanitize_name( $title );
                        $title = esc_sql(stripcslashes(trim($title)));
                        if ( $data['valueCount'] == true ) :
                            $query = $wpdb->prepare("SELECT COUNT(meta_id) as meta_count, `". $wpdb->postmeta."`.meta_value FROM `". $wpdb->postmeta."` WHERE `". $wpdb->postmeta."`.meta_key = %s GROUP BY `". $wpdb->postmeta."`.meta_value;", $title);
                            $result = $wpdb->get_results($query, ARRAY_A);
                            if ( $result ) :
                                foreach($result as $val) :
                                    $options['value_count'][$title][$val['meta_value']] = $val['meta_count'];
                                endforeach;
                            endif;
                        endif;
                    endforeach;
                endforeach;
            endfor;
        endif;
        update_option('custom_field_template_data', $options);
    }

    /**
     * Main WordPress_Plugin_Template_Settings Instance
     *
     * Ensures only one instance of WordPress_Plugin_Template_Settings is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @see WordPress_Plugin_Template()
     * @return Main WordPress_Plugin_Template_Settings instance
     */
    public static function instance ( $parent ) {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self( $parent );
        }
        return self::$_instance;
    } // End instance()

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone () {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
    } // End __clone()

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup () {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
    } // End __wakeup()

}