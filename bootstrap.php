<?php
class customFieldTemplateBootStrap
{

    private static $_instance = null;

    public $is_excerpt;

    public $options;

    public $locale;

    public $plugin_dir;

    public $init = null;

    //public $model = null;

    public $_version;

    public $_token;

    public $file;

    public $dir;

    public $assets_dir;

    public $assets_url;

    public $model;

    /**
     * Constructor function.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function __construct ( $file = '', $version = '1.0.0' )
    {
        $this->model = new customFieldTemplateModel();
        $this->options = $this->model->get_custom_field_template_data();
        $this->locale  = get_locale();

        if ( !defined('WP_PLUGIN_DIR') ) {
            $plugin_dir = str_replace( ABSPATH, '', dirname(__FILE__) );
        } else {
            $plugin_dir = dirname( plugin_basename(__FILE__) );
        }

        $this->plugin_dir = '/' . PLUGINDIR . '/' . $plugin_dir;

        $this->_version = $version;
        $this->_token = 'custom_field_template';


        $this->file = $file;
        $this->dir = dirname( $this->file );

        $this->assets_dir = trailingslashit( $this->dir ) . 'assets';
        $this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

        add_action( 'admin_print_scripts', array(&$this, 'custom_field_template_admin_scripts') );
        add_action( 'admin_head', array(&$this, 'custom_field_template_admin_head'), 100 );
        add_action( 'dbx_post_sidebar', array(&$this, 'custom_field_template_dbx_post_sidebar') );



        add_filter( 'media_send_to_editor', array(&$this, 'media_send_to_custom_field'), 15 );

        add_filter( 'attachment_fields_to_edit', array(&$this, 'custom_field_template_attachment_fields_to_edit'), 10, 2 );
        add_filter( '_wp_post_revision_fields', array(&$this, 'custom_field_template_wp_post_revision_fields'), 1 );
        add_filter( 'edit_form_after_title', array(&$this, 'custom_field_template_edit_form_after_title') );

    }

    public static function instance ( $file = '', $version = '1.0.0' )
    {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self( $file, $version );
        }
        return self::$_instance;
    }

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone () {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
    }

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup () {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
    } // End __wakeup ()

    function custom_field_template_admin_scripts()
    {
        global $post;
        $options = $this->options;

        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-form' );
        wp_enqueue_script( 'bgiframe', $this->assets_dir . '/js/jquery.bgiframe.js', array('jquery') ) ;
        if (strpos($_SERVER['REQUEST_URI'], 'custom-field-template') !== false ) {
            wp_enqueue_script( 'textarearesizer', $this->assets_dir . '/js/jquery.textarearesizer.js', array('jquery') );
            if( strstr($_SERVER['REQUEST_URI'], 'wp-admin/post-new.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/post.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/page-new.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/page.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/edit.php') || (is_object($post) && $post->post_type=='page') ) {
                wp_enqueue_script('date', $this->assets_dir . '/js/date.js', array('jquery') );
                wp_enqueue_script('datePicker',  $this->assets_dir . '/js/jquery.datePicker.js', array('jquery') );
                wp_enqueue_script('editor');
                wp_enqueue_script('quicktags');

                if ( !empty($options['custom_field_template_use_validation']) ) {
                    wp_enqueue_script( 'jquery-validate', $this->assets_dir . '/js/jquery.validate.js', array('jquery') );
                    wp_enqueue_script( 'additional-methods',  $this->assets_dir . '/js/additional-methods.js', array('jquery') );
                    wp_enqueue_script( 'messages_' . $this->locale, '/' . PLUGINDIR . '/' . $this->assets_dir . '/js/messages_' . $this->locale .'.js', array('jquery') );
                }
            }
        }
    }

    function custom_field_template_admin_head()
    {
        global $wp_version, $post;
        $options = $this->options;

        echo '<link rel="stylesheet" type="text/css" href="' . $this->assets_url . '/js/datePicker.css" />'."\n";

        if ( !empty($options['custom_field_template_use_validation']) ) {
            if( strstr($_SERVER['REQUEST_URI'], 'wp-admin/post-new.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/post.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/page-new.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/page.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/edit.php') || (is_object($post) && $post->post_type=='page') ) {
                echo '<script type="text/javascript">jQuery(document).ready(function() {jQuery("#post").validate();});</script>'."\n";
                echo '<link rel="stylesheet" type="text/css"  >label.error{ color:#FF0000; }</style>'."\n";
            }
        }

        if ( substr($wp_version, 0, 3) >= '2.7' && is_user_logged_in() && ( strstr($_SERVER['REQUEST_URI'], 'wp-admin/edit.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/edit-pages.php') ) && !strstr($_SERVER['REQUEST_URI'], 'page=') ) {
            echo '<script type="text/javascript" src="' . $this->assets_url . '/css/custom_field_template_admin_head.css"></script>'."\n";
            echo '<link rel="stylesheet" type="text/css" href="' . $this->assets_url . '/css/custom_field_template_admin_head.css" />'."\n";
        }

    }

    function custom_field_template_dbx_post_sidebar() {
        global $wp_version;
        $options = $this->options;
        $suffix = (empty($options['custom_field_template_deploy_box']))?'':'"+win.jQuery("#cft_current_template").val()+"';

        $out = '';
        $out .= 	'<script type="text/javascript">' . "\n" .
            '// <![CDATA[' . "\n";
        $out .=		'function cft_use_this(file_id) {
		var win = window.dialogArguments || opener || parent || top;
		win.jQuery("#"+win.jQuery("#cft_clicked_id").val()+"_hide").val(file_id);
		var fields = win.jQuery("#cft'.$suffix.' :input").fieldSerialize();
		win.jQuery.ajax({type: "POST", url: "?page=custom-field-template/custom-field-template.php&cft_mode=ajaxsave&post="+win.jQuery(\'#post_ID\').val()+"&custom-field-template-verify-key="+win.jQuery("#custom-field-template-verify-key").val(), data: fields, success: function() {win.jQuery.ajax({type: "GET", url: "?page=custom-field-template/custom-field-template.php&cft_mode=ajaxload&id="+win.jQuery("#cft_current_template").val()+"&post="+win.jQuery(\'#post_ID\').val(), success: function(html) {win.jQuery("#cft'.$suffix.'").html(html);win.tb_remove();}});}});
            }';

                $out .=		'function qt_set(new_id) { eval("qt_"+new_id+" = new QTags(\'qt_"+new_id+"\', \'"+new_id+"\', \'editorcontainer_"+new_id+"\', \'more\');");}';

                $out .=     'function _edInsertContent(myField, myValue) {
            var sel, startPos, endPos, scrollTop;

            //IE support
            if (document.selection) {
                myField.focus();
                sel = document.selection.createRange();
                sel.text = myValue;
                myField.focus();
            }
            //MOZILLA/NETSCAPE support
            else if (myField.selectionStart || myField.selectionStart == "0") {
                startPos = myField.selectionStart;
                endPos = myField.selectionEnd;
                scrollTop = myField.scrollTop;
                myField.value = myField.value.substring(0, startPos)
                              + myValue
                              + myField.value.substring(endPos, myField.value.length);
                myField.focus();
                myField.selectionStart = startPos + myValue.length;
                myField.selectionEnd = startPos + myValue.length;
                myField.scrollTop = scrollTop;
            } else {
                myField.value += myValue;
                myField.focus();
            }
        }';

        $out .= 	'function send_to_custom_field(h) {' . "\n" .
            '	if ( tmpFocus ) ed = tmpFocus;' . "\n" .
            '	else if ( typeof tinyMCE == "undefined" ) ed = document.getElementById("content");' . "\n" .
            '	else { ed = tinyMCE.get("content"); if(ed) {if(!ed.isHidden()) isTinyMCE = true;}}' . "\n" .
            '	if ( typeof tinyMCE != "undefined" && isTinyMCE && !ed.isHidden() ) {' . "\n" .
            '		ed.focus();' . "\n" .
            '		if ( tinymce.isIE && ed.windowManager.insertimagebookmark )' . "\n" .
            '			ed.selection.moveToBookmark(ed.windowManager.insertimagebookmark);' . "\n" .
            '		if ( h.indexOf("[caption") === 0 ) {' . "\n" .
            '			if ( ed.plugins.wpeditimage )' . "\n" .
            '				h = ed.plugins.wpeditimage._do_shcode(h);' . "\n" .
            '		} else if ( h.indexOf("[gallery") === 0 ) {' . "\n" .
            '			if ( ed.plugins.wpgallery )' . "\n" .
            '				h = ed.plugins.wpgallery._do_gallery(h);' . "\n" .
            '		} else if ( h.indexOf("[embed") === 0 ) {' . "\n" .
            '			if ( ed.plugins.wordpress )' . "\n" .
            '				h = ed.plugins.wordpress._setEmbed(h);' . "\n" .
            '		}' . "\n" .
            '		ed.execCommand("mceInsertContent", false, h);' . "\n" .
            '	} else {' . "\n" .
            '		if ( tmpFocus ) _edInsertContent(tmpFocus, h);' . "\n" .
            '		else edInsertContent(edCanvas, h);' . "\n" .
            '	}' . "\n";

        if ( empty($options['custom_field_template_use_multiple_insert']) ) {
            $out .= '	tb_remove();' . "\n" .
                '	tmpFocus = undefined;' . "\n" .
                '	isTinyMCE = false;' . "\n";
        }

        if (substr($wp_version, 0, 3) < '3.9' ) {
            $qt_position = 'jQuery(\'#qt_\'+id+\'_toolbar\')';
            $load_tinyMCE = 'var ed = new tinyMCE.Editor(id, tinyMCEPreInit.mceInit[\'content\']); ed.render();';
        } elseif(substr($wp_version, 0, 3) < '3.3') {
            $qt_position = 'jQuery(\'#editorcontainer_\'+id).prev()';
            $load_tinyMCE = 'tinyMCE.execCommand(' . "'mceAddControl'" . ',false, id);';
        } else {
            $qt_position = 'jQuery(\'#qt_\'+id+\'_toolbar\')';
            $load_tinyMCE = 'tinyMCE.execCommand(' . "'mceAddEditor'" . ', true, id);';
        }

        $out .=		'}' . "\n" .
            'jQuery(".thickbox").bind("click", function (e) {' . "\n" .
            '	tmpFocus = undefined;' . "\n" .
            '	isTinyMCE = false;' . "\n" .
            '});' . "\n" .
            'var isTinyMCE;' . "\n" .
            'var tmpFocus;' . "\n" .
            'function focusTextArea(id) {' . "\n" .
            '	jQuery(document).ready(function() {' . "\n" .
            '		if ( typeof tinyMCE != "undefined" ) {' . "\n" .
            '			var elm = tinyMCE.get(id);' . "\n" .
            '		}' . "\n" .
            '		if ( ! elm || elm.isHidden() ) {' . "\n" .
            '			elm = document.getElementById(id);' . "\n" .
            '			isTinyMCE = false;' . "\n" .
            '		}else isTinyMCE = true;' . "\n" .
            '		tmpFocus = elm' . "\n" .
            '		elm.focus();' . "\n" .
            '		if (elm.createTextRange) {' . "\n" .
            '			var range = elm.createTextRange();' . "\n" .
            '			range.move("character", elm.value.length);' . "\n" .
            '			range.select();' . "\n" .
            '		} else if (elm.setSelectionRange) {' . "\n" .
            '			elm.setSelectionRange(elm.value.length, elm.value.length);' . "\n" .
            '		}' . "\n" .
            '	});' . "\n" .
            '}' . "\n" .
            'function switchMode(id) {' . "\n" .
            '	var ed = tinyMCE.get(id);' . "\n" .
            '	if ( ! ed || ed.isHidden() ) {' . "\n" .
            '		document.getElementById(id).value = switchEditors.wpautop(document.getElementById(id).value);' . "\n" .
            '		if ( ed ) { '.$qt_position.'.hide(); ed.show(); }' . "\n" .
            '		else {'.$load_tinyMCE.'}' . "\n" .
            '	} else {' . "\n" .
            '		ed.hide(); '.$qt_position.'.show(); document.getElementById(id).style.color="#000000";' . "\n" .
            '	}' . "\n" .
            '}' . "\n";

        $out .=		'function thickbox(link) {' . "\n" .
            '	var t = link.title || link.name || null;' . "\n" .
            '	var a = link.href || link.alt;' . "\n" .
            '	var g = link.rel || false;' . "\n" .
            '	tb_show(t,a,g);' . "\n" .
            '	link.blur();' . "\n" .
            '	return false;' . "\n" .
            '}' . "\n";
        $out .=     '//--></script>';
        $out .= '<input type="hidden" id="cft_current_template" value="" />';
        $out .= '<input type="hidden" id="cft_clicked_id" value="" />';
        $out .= '<input type="hidden" name="custom-field-template-verify-key" id="custom-field-template-verify-key" value="' . wp_create_nonce('custom-field-template') . '" />';

        $out .=		'<style type="text/css">' . "\n" .
            '<!--' . "\n";
        $out .=		$options['css'] . "\n";
        $out .=		'.editorcontainer { overflow:hidden; background:#FFFFFF; }
                    .content { width:98%; }
                    .editorcontainer .content { padding: 6px; line-height: 150%; border: 0 none; outline: none;	-moz-box-sizing: border-box;	-webkit-box-sizing: border-box;	-khtml-box-sizing: border-box; box-sizing: border-box; }
                    .quicktags { border:1px solid #DFDFDF; border-collapse: separate; -moz-border-radius: 6px 6px 0 0; -webkit-border-top-right-radius: 6px; -webkit-border-top-left-radius: 6px; -khtml-border-top-right-radius: 6px; -khtml-border-top-left-radius: 6px; border-top-right-radius: 6px; border-top-left-radius: 6px; }
                    .quicktags { padding: 0; margin-bottom: -1px; border-bottom-width:1px;	background-image: url("images/ed-bg.gif"); background-position: left top; background-repeat: repeat; }
                    .quicktags div div { padding: 2px 4px 0; }
                    .quicktags div div input { margin: 3px 1px 4px; line-height: 18px; display: inline-block; border-width: 1px; border-style: solid; min-width: 26px; padding: 2px 4px; font-size: 12px; -moz-border-radius: 3px; -khtml-border-radius: 3px; -webkit-border-radius: 3px; border-radius: 3px; background:#FFFFFF url(images/fade-butt.png) repeat-x scroll 0 -2px; overflow: visible; }' . "\n";
        $out .=		'-->' . "\n" .
            '</style>';
        echo $out;
    }

    function media_send_to_custom_field($html) {
        if ( strstr($_SERVER['REQUEST_URI'], 'wp-admin/admin-ajax.php') ) {
            return $html;
        };
        $out =  '<script type="text/javascript">' . "\n" .
            '	/* <![CDATA[ */' . "\n" .
            '	var win = window.dialogArguments || opener || parent || top;' . "\n" .
            '   if ( typeof win.send_to_custom_field == "function" ) ' . "\n" .
            '	    win.send_to_custom_field("' . addslashes($html) . '");' . "\n" .
            '   else ' . "\n" .
            '       win.send_to_editor("' . addslashes($html) . '");' . "\n" .
            '/* ]]> */' . "\n" .
            '</script>' . "\n";

        echo $out;
        exit();
    }

    function custom_field_template_attachment_fields_to_edit($form_fields, $post) {
        $form_fields["custom_field_template"]["label"] = __('Media Picker', 'custom-field-template');
        $form_fields["custom_field_template"]["input"] = "html";
        $form_fields["custom_field_template"]["html"] = '<a href="javascript:void(0);" onclick="var win = window.dialogArguments || opener || parent || top;win.cft_use_this('.$post->ID.');return false;">'.__('Use this', 'custom-field-template').'</a>';

        return $form_fields;
    }

    function custom_field_template_wp_post_revision_fields($fields) {
        $fields['cft_debug_preview'] = 'cft_debug_preview';
        return $fields;
    }

    function custom_field_template_edit_form_after_title() {
        echo '<input type="hidden" name="cft_debug_preview" value="cft_debug_preview" />';
    }


}