<?php
class customFieldTemplateModel
{

    public $option;

    private static $_instance = null;

    public function __construct( )
    {
        $this->option = get_option('custom_field_template_data');

        add_action( 'delete_post', array(&$this, 'custom_field_template_delete_post'), 100 );

        add_filter( 'get_the_excerpt', array(&$this, 'custom_field_template_get_the_excerpt'), 1 );
        add_filter( 'the_content', array(&$this, 'custom_field_template_the_content') );
        add_filter( 'the_content_rss', array(&$this, 'custom_field_template_the_content') );

        if ( isset($_REQUEST['cftsearch_submit']) ) {
            if ( !empty($_REQUEST['limit']) ) {
                add_action( 'post_limits', array(&$this, 'custom_field_template_post_limits'), 100);
            }

            add_filter( 'posts_join', array(&$this, 'custom_field_template_posts_join'), 100 );
            add_filter( 'posts_where', array(&$this, 'custom_field_template_posts_where'), 100 );
            add_filter( 'posts_orderby',  array(&$this, 'custom_field_template_posts_orderby'), 100 );
        };

        if ( function_exists('add_shortcode') ) {
            add_shortcode( 'cft', array(&$this, 'output_custom_field_values') );
            add_shortcode( 'cftsearch', array(&$this, 'search_custom_field_values') );
        };

        add_filter( 'get_post_metadata', array(&$this, 'get_preview_postmeta'), 10, 4 );
    }

    public function get_custom_field_template_data()
    {
        $options = $this->option;
        if ( !empty($options) && !is_array($options) ) {
            $options = array();
        };
        return $options;
    }

    public function custom_field_template_post_limits($sql_limit)
    {
        global $wp_query;

        if ( !$sql_limit ) return;
        list($offset, $old_limit) = explode(',', $sql_limit);
        $limit = (int)$_REQUEST['limit'];
        if ( !$limit )
            $limit = trim($old_limit);
        $wp_query->query_vars['posts_per_page'] = $limit;
        $offset = ($wp_query->query_vars['paged'] - 1) * $limit;
        if ( $offset < 0 ) $offset = 0;

        return ( $limit ? "LIMIT $offset, $limit" : '' );
    }

    public function get_preview_id( $post_id )
    {
        global $post;
        $preview_id = 0;
        if ( isset($post) && $post->ID == $post_id && is_preview() && $preview = wp_get_post_autosave( $post->ID ) ) :
            $preview_id = $preview->ID;
        endif;
        return $preview_id;
    }

    public function get_preview_postmeta( $return, $post_id, $meta_key, $single )
    {
        if ( $preview_id = $this->get_preview_id( $post_id ) ) :
            if ( $post_id != $preview_id ) :
                $return = $this->get_post_meta( $preview_id, $meta_key, $single );
                /*if ( empty($return) && !empty($post_id) ) :
                          $return = $this->get_post_meta( $post_id, $meta_key, $single );
                    endif;*/
            endif;
        endif;
        return $return;
    }

    public function set_value_count($key, $value, $id)
    {
        global $wpdb;

        if ( $id ) $where = " AND `". $wpdb->postmeta."`.post_id<>".$id;
        $query = $wpdb->prepare("SELECT COUNT(meta_id) FROM `". $wpdb->postmeta."` WHERE `". $wpdb->postmeta."`.meta_key = %s AND `". $wpdb->postmeta."`.meta_value = %s $where;", $key, $value);
        $count = $wpdb->get_var($query);
        return (int)$count;
    }

    public function get_value_count($key = '', $value = '')
    {
        $options = $this->get_custom_field_template_data();

        if ( $key && $value ) :
            return $options['value_count'][$key][$value];
        else:
            return $options['value_count'];
        endif;
    }

    public function custom_field_template_delete_post($post_id)
    {
        global $wpdb;
        $options = $this->get_custom_field_template_data();

        if ( is_numeric($post_id) )
            $id = !empty($options['posts'][$post_id]) ? $options['posts'][$post_id] : '';

        if ( is_numeric($id) ) :
            $fields = $this->get_custom_fields($id);

            if ( $fields == null )
                return;

            foreach( $fields as $field_key => $field_val) :
                foreach( $field_val as $title	=> $data) :
                    //$name = $this->sanitize_name( $title );
                    $title = esc_sql(stripcslashes(trim($title)));
                    $value = $this->get_post_meta($post_id, $title);
                    if ( is_array($value) ) :
                        foreach ( $value as $val ) :
                            if ( $data['valueCount'] == true ) :
                                $count = $this->set_value_count($title, $val, '')-1;
                                if ( $count<=0 )
                                    unset($options['value_count'][$title][$val]);
                                else
                                    $options['value_count'][$title][$val] = $count;
                            endif;
                        endforeach;
                    else :
                        if ( $data['valueCount'] == true ) :
                            $count = $this->set_value_count($title, $value, '')-1;
                            if ( $count<=0 )
                                unset($options['value_count'][$title][$value]);
                            else
                                $options['value_count'][$title][$value] = $count;
                        endif;
                    endif;
                endforeach;
            endforeach;
        endif;
        update_option('custom_field_template_data', $options);
    }

    public function get_custom_fields( $id )
    {
        $options = $this->get_custom_field_template_data();

        if ( empty($options['custom_fields'][$id]) )
            return null;

        $custom_fields = $this->parse_ini_str( $options['custom_fields'][$id]['content'], true );
        return $custom_fields;
    }

    public function parse_ini_str($Str,$ProcessSections = TRUE)
    {
        $options = $this->get_custom_field_template_data();

        $Section = NULL;
        $Data = array();
        $Sections = array();
        if ($Temp = strtok($Str,"\r\n")) {
            $sn = -1;
            do {
                switch ($Temp{0}) {
                    case ';':
                    case '#':
                        break;
                    case '[':
                        if (!$ProcessSections) {
                            break;
                        }
                        $Pos = strpos($Temp,'[');
                        $Section = substr($Temp,$Pos+1,strpos($Temp,']',$Pos)-1);
                        $sn++;
                        $Data[$sn][$Section] = array();
                        if ( isset($cftnum[$Section]) ) $cftnum[$Section]++;
                        else $cftnum[$Section] = 0;
                        $Data[$sn][$Section]['cftnum'] = $cftnum[$Section];
                        if($Data[$sn][$Section])
                            break;
                    default:
                        $Pos = strpos($Temp,'=');
                        if ($Pos === FALSE) {
                            break;
                        }
                        $Value = array();
                        $Value["NAME"] = trim(substr($Temp,0,$Pos));
                        $Value["VALUE"] = trim(substr($Temp,$Pos+1));

                        if ($ProcessSections) {
                            $Data[$sn][$Section][$Value["NAME"]] = $Value["VALUE"];
                        }
                        else {
                            $Data[$Value["NAME"]] = $Value["VALUE"];
                        }
                        break;
                }
            } while ($Temp = strtok("\r\n"));

            $gap = $key = 0;
            $returndata = array();
            foreach( $Data as $Data_key => $Data_val ) :
                foreach( $Data_val as $title => $data) :
                    if ( isset($cftisexist[$title]) ) $tmp_parentSN = $cftisexist[$title];
                    else $tmp_parentSN = count($returndata);
                    switch ( $data["type"]) :
                        case 'checkbox' :
                            if ( isset($data["code"]) && is_numeric($data["code"]) ) :
                                eval(stripcslashes($options['php'][$data["code"]]));
                            else :
                                if ( isset($data["value"]) ) $values = explode( '#', $data["value"] );
                                if ( isset($data["valueLabel"]) ) $valueLabel = explode( '#', $data["valueLabel"] );
                                if ( isset($data["default"]) ) $defaults = explode( '#', $data["default"] );
                            endif;

                            if ( !empty($valueLabel) ) $valueLabels = $valueLabel;

                            if ( isset($defaults) && is_array($defaults) )
                                foreach($defaults as $dkey => $dval)
                                    $defaults[$dkey] = trim($dval);

                            $tmp = $key;
                            $i = 0;
                            if ( isset($values) && is_array($values) ) :
                                foreach($values as $value) {
                                    $count_key = count($returndata);
                                    $Data[$Data_key][$title]["value"] = trim($value);
                                    $Data[$Data_key][$title]["originalValue"] = $data["value"];
                                    $Data[$Data_key][$title]['cftnum'] = $i;
                                    if ( isset($valueLabels[$i]) )
                                        $Data[$Data_key][$title]["valueLabel"] = trim($valueLabels[$i]);
                                    if ( $tmp!=$key )
                                        $Data[$Data_key][$title]["hideKey"] = true;
                                    if ( isset($defaults) && is_array($defaults) ) :
                                        if ( in_array(trim($value), $defaults) )
                                            $Data[$Data_key][$title]["checked"] = true;
                                        else
                                            unset($Data[$Data_key][$title]["checked"]);
                                    endif;
                                    $Data[$Data_key][$title]['parentSN'] = $tmp_parentSN+$gap;
                                    $returndata[$count_key] = $Data[$Data_key];
                                    $key++;
                                    $i++;
                                }
                            endif;
                            break;
                        default :
                            if ( $data['type'] == 'fieldset_open' ) :
                                $fieldset = array();
                                if ( isset($_REQUEST[$this->sanitize_name($title)]) ) $fieldsetcounter = count($_REQUEST[$this->sanitize_name($title)])-1;
                                else if ( isset($_REQUEST['post']) ) $fieldsetcounter = $this->get_post_meta( $_REQUEST['post'], $title, true )-1;
                                else $fieldsetcounter = 0;
                                if ( !empty($data['multiple']) ) : $fieldset_multiple = 1; endif;
                            endif;
                            if ( isset($fieldset) && is_array($fieldset) ) :
                                if ( empty($tmp_parentSN2[$title]) ) $tmp_parentSN2[$title] = $tmp_parentSN;
                            endif;
                            if ( isset($data['multiple']) && $data['multiple'] == true && $data['type'] != 'checkbox' && $data['type'] != 'fieldset_open' && !isset($fieldset) ) :
                                $counter = isset($_REQUEST[$this->sanitize_name($title)][$tmp_parentSN+$gap]) ? count($_REQUEST[$this->sanitize_name($title)][$tmp_parentSN+$gap]) : 0;
                                if ( $data['type'] == 'file' && !empty($_FILES[$this->sanitize_name($title)]) ) $counter = (int)count($_FILES[$this->sanitize_name($title)]['name'][$tmp_parentSN+$gap])+1;
                                if ( isset($_REQUEST['post_ID']) )	$org_counter = count($this->get_post_meta( $_REQUEST['post_ID'], $title ));
                                else if ( isset($_REQUEST['post']) ) $org_counter = count($this->get_post_meta( $_REQUEST['post'], $title ));
                                else $org_counter = 1;
                                if ( !$counter ) :
                                    $counter = $org_counter;
                                    $counter++;
                                else :
                                    if ( empty($_REQUEST[$this->sanitize_name($title)][$tmp_parentSN+$gap][$counter-1]) ) $counter--;
                                endif;
                                if ( !$org_counter ) $org_counter = 2;
                                if ( isset($data['startNum']) && is_numeric($data['startNum']) && $data['startNum']>$counter ) $counter = $data['startNum'];
                                if ( isset($data['endNum']) && is_numeric($data['endNum']) && $data['endNum']<$counter ) $counter = $data['endNum'];
                                if ( $counter ) :
                                    for($i=0;$i<$counter; $i++) :
                                        $count_key = count($returndata);
                                        if ( $i!=0 ) $Data[$Data_key][$title]["hideKey"] = true;
                                        if ( $i!=0 ) unset($Data[$Data_key][$title]["label"]);
                                        $Data[$Data_key][$title]['cftnum'] = $i;
                                        $Data[$Data_key][$title]['parentSN'] = $tmp_parentSN+$gap;
                                        $returndata[$count_key] = $Data[$Data_key];
                                        if ( isset($fieldset) && is_array($fieldset) ) :
                                            $fieldset[] = $Data[$Data_key];
                                        endif;
                                    endfor;
                                endif;
                                if ( $counter != $org_counter ) :
                                    $gap += ($org_counter - $counter);
                                endif;
                            else :
                                if ( !isset($cftisexist[$title]) && !isset($fieldset) ) $Data[$Data_key][$title]['parentSN'] = $tmp_parentSN+$gap;
                                else $Data[$Data_key][$title]['parentSN'] = $tmp_parentSN;
                                $returndata[] = $Data[$Data_key];
                                if ( isset($fieldset) && is_array($fieldset) ) :
                                    $Data[$Data_key][$title]['parentSN'] = $tmp_parentSN2[$title];
                                    $fieldset[] = $Data[$Data_key];
                                endif;
                            endif;
                            if ( $data['type'] == 'fieldset_close' && is_array($fieldset) ) :
                                for($i=0;$i<$fieldsetcounter;$i++) :
                                    $returndata = array_merge($returndata, $fieldset);
                                endfor;
                                if ( isset($_REQUEST['post_ID']) ) $groupcounter = (int)$this->get_post_meta( $_REQUEST['post_ID'], $title, true );
                                if ( !isset($groupcounter) || $groupcounter == 0 ) $groupcounter = $fieldsetcounter;
                                if ( isset($_REQUEST[$this->sanitize_name($title)]) && $fieldset_multiple ) :
                                    $gap += ($groupcounter - count($_REQUEST[$this->sanitize_name($title)]))*count($fieldset);
                                    unset($fieldset_multiple);
                                endif;
                                unset($fieldset, $tmp_parentSN2);
                            endif;
                            unset($counter);
                    endswitch;
                    if ( !isset($cftisexist[$title]) ) $cftisexist[$title] = $Data[$Data_key][$title]['parentSN'];
                endforeach;
            endforeach;

            $cftnum = array();
            if ( is_array($returndata) ) :
                foreach( $returndata as $Data_key => $Data_val ) :
                    foreach( $Data_val as $title => $data ) :
                        if ( isset($cftnum[$title]) && is_numeric($cftnum[$title]) ) $cftnum[$title]++;
                        else $cftnum[$title] = 0;
                        $returndata[$Data_key][$title]['cftnum'] = $cftnum[$title];
                    endforeach;
                endforeach;
            endif;
        }

        return $returndata;
    }

    public function install_custom_field_template_data() {
        $options['custom_field_template_before_list']  = '<ul>';
        $options['custom_field_template_after_list']   = '</ul>';
        $options['custom_field_template_before_value'] = '<li>';
        $options['custom_field_template_after_value']  = '</li>';
        $options['custom_fields'][0]['title']   = __('Default Template', 'custom-field-template');
        $options['custom_fields'][0]['content'] = '[Plan]
        type = text
        size = 35
        label = Where are you going to go?

        [Plan]
        type = textfield
        size = 35
        hideKey = true

        [Favorite Fruits]
        type = checkbox
        value = apple # orange # banana # grape
        default = orange # grape

        [Miles Walked]
        type = radio
        value = 0-9 # 10-19 # 20+
        default = 10-19
        clearButton = true

        [Temper Level]
        type = select
        value = High # Medium # Low
        default = Low

        [Hidden Thought]
        type = textarea
        rows = 4
        cols = 40
        tinyMCE = true
        htmlEditor = true
        mediaButton = true

        [File Upload]
        type = file';
        $options['shortcode_format'][0] =  '<table class="cft">
                                                <tbody>
                                                    <tr>
                                                        <th>Plan</th><td colspan="3">[Plan]</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Favorite Fruits</th><td>[Favorite Fruits]</td>
                                                        <th>Miles Walked</th><td>[Miles Walked]</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Temper Level</th><td colspan="3">[Temper Level]</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Hidden Thought</th><td colspan="3">[Hidden Thought]</td>
                                                    </tr>
                                                </tbody>
                                            </table>';
        update_option('custom_field_template_data', $options);
    }

    public function install_custom_field_template_css() {
        $options = get_option('custom_field_template_data');
        $options['css'] = '.cft { overflow:hidden; }
        .cft:after { content:" "; clear:both; height:0; display:block; visibility:hidden; }
        .cft dl { margin:10px 0; }
        .cft dl:after { content:" "; clear:both; height:0; display:block; visibility:hidden; }
        .cft dt { width:20%; clear:both; float:left; display:inline; font-weight:bold; text-align:center; }
        .cft dt .hideKey { visibility:hidden; }
        .cft dd { margin:0 0 0 21%; }
        .cft dd p.label { font-weight:bold; margin:0; }
        .cft_instruction { margin:10px; }
        .cft fieldset { border:1px solid #CCC; margin:5px; padding:5px; }
        .cft .dl_checkbox { margin:0; }
        ';
        update_option('custom_field_template_data', $options);
    }

    public function load_custom_field( $id = 0 ) {
        global $current_user, $post, $wp_version;
        $level = $current_user->user_level;

        $options = $this->get_custom_field_template_data();

        $post_id = isset($_REQUEST['post']) ? $_REQUEST['post'] : '';

        if ( isset($post_id) ) $post = get_post($post_id);

        if ( isset($_REQUEST['revision']) ) $post_id = $_REQUEST['revision'];

        if ( !empty($options['custom_fields'][$id]['disable']) )
            return;

        $fields = $this->get_custom_fields( $id );

        if ( $fields == null )
            return;

        if ( (isset($_REQUEST['post_type']) && $_REQUEST['post_type'] == 'page') || $post->post_type=='page' ) :
            $post->page_template = get_post_meta( $post->ID, '_wp_page_template', true );
            if ( !$post->page_template ) $post->page_template = 'default';
        endif;

        if ( !empty($options['custom_fields'][$id]['post_type']) ) :
            if ( substr($wp_version, 0, 3) < '3.0' ) :
                if ( $options['custom_fields'][$id]['post_type'] == 'post' && (strstr($_SERVER['REQUEST_URI'], 'wp-admin/page-new.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/page.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/edit-pages.php')) ) :
                    return;
                endif;
                if ( $options['custom_fields'][$id]['post_type'] == 'page' && (strstr($_SERVER['REQUEST_URI'], 'wp-admin/post-new.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/post.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/edit.php')) ) :
                    return;
                endif;
            else :
                if ( (isset($_REQUEST['post_type']) && $_REQUEST['post_type']!=$options['custom_fields'][$id]['post_type']) && $post->post_type!=$options['custom_fields'][$id]['post_type'] ) :
                    return;
                endif;
            endif;
        endif;

        if ( !empty($options['custom_fields'][$id]['custom_post_type']) ) :
            $custom_post_type = explode(',', $options['custom_fields'][$id]['custom_post_type']);
            $custom_post_type = array_filter( $custom_post_type );
            $custom_post_type = array_unique(array_filter(array_map('trim', $custom_post_type)));
            if ( !in_array($post->post_type, $custom_post_type) )
                return;
        endif;

        if ( substr($wp_version, 0, 3) < '3.0' ) :
            if ( !empty($options['custom_fields'][$id]['category']) && (strstr($_SERVER['REQUEST_URI'], 'wp-admin/page-new.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/page.php')) && empty($options['custom_fields'][$id]['template_files']) ) :
                return;
            endif;
            if ( !empty($options['custom_fields'][$id]['template_files']) && (strstr($_SERVER['REQUEST_URI'], 'wp-admin/post-new.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/post.php')) && empty($options['custom_fields'][$id]['category']) ) :
                return;
            endif;
        else :
            if ( !empty($options['custom_fields'][$id]['category']) && ($_REQUEST['post_type']=='page' || $post->post_type=='page') && empty($options['custom_fields'][$id]['template_files']) ) :
                return;
            endif;
            if ( !empty($options['custom_fields'][$id]['template_files']) && ($_REQUEST['post_type']!='page' && $post->post_type!='page') && empty($options['custom_fields'][$id]['category']) ) :
                return;
            endif;
        endif;

        if ( (!isset($post_id) || $post_id<0) && !empty($options['custom_fields'][$id]['category']) && $_REQUEST['cft_mode'] != 'ajaxload' )
            return;

        if ( isset($post_id) && !empty($options['custom_fields'][$id]['category']) && !isset($options['posts'][$post_id]) && $options['posts'][$post_id] !== $id && $_REQUEST['cft_mode'] != 'ajaxload' )
            return;

        if ( !isset($_REQUEST['id']) && !empty($options['custom_fields'][$id]['category']) && $_REQUEST['cft_mode'] == 'ajaxload' ) :
            $category = explode(',', $options['custom_fields'][$id]['category']);
            $category = array_filter( $category );
            $category = array_unique(array_filter(array_map('trim', $category)));

            if ( !empty($_REQUEST['tax_input']) && is_array($_REQUEST['tax_input']) ) :
                foreach($_REQUEST['tax_input'] as $key => $val) :
                    foreach($val as $key2 => $val2 ) :
                        if ( in_array($val2, $category) ) : $notreturn = 1; break; endif;;
                    endforeach;
                endforeach;
            else :
                if ( !empty($_REQUEST['post_category']) && is_array($_REQUEST['post_category']) ) :
                    foreach($_REQUEST['post_category'] as $val) :
                        if ( in_array($val, $category) ) : $notreturn = 1; break; endif;;
                    endforeach;
                endif;
            endif;
            if ( empty($notreturn) ) return;
        endif;

        if ( !empty($options['custom_fields'][$id]['post']) ) :
            $post_ids = explode(',', $options['custom_fields'][$id]['post']);
            $post_ids = array_filter( $post_ids );
            $post_ids = array_unique(array_filter(array_map('trim', $post_ids)));
            if ( !in_array($post_id, $post_ids) )
                return;
        endif;

        if ( !empty($options['custom_fields'][$id]['template_files']) && (isset($post->page_template) || (isset($_REQUEST['page_template']) && $_REQUEST['page_template'])) ) :
            $template_files = explode(',', $options['custom_fields'][$id]['template_files']);
            $template_files = array_filter( $template_files );
            $template_files = array_unique(array_filter(array_map('trim', $template_files)));
            if ( isset($_REQUEST['page_template']) ) {
                if ( !in_array($_REQUEST['page_template'], $template_files) ) {
                    return;
                };
            } else {
                if ( !in_array($post->page_template, $template_files) ){
                    return;
                };
            };
        endif;

        if ( substr($wp_version, 0, 3) >= '3.3' && !post_type_supports($post->post_type, 'editor') && $post->post_type!='post' && $post->post_type!='page' ) {
            wp_editor('', 'content', array('dfw' => true, 'tabindex' => 1) );
            $out = '<style type="text/css">#wp-content-wrap { display:none; }</style>';
        } else {
            $out = '';
        };

        if ( !empty($options['custom_fields'][$id]['instruction']) ) {
            $instruction = $this->EvalBuffer(stripcslashes($options['custom_fields'][$id]['instruction']));
            $out .= '<div id="cft_instruction'.$id.'" class="cft_instruction">' . $instruction . '</div>';
        };

        $out .= '<div id="cft_'.$id.'">';
        $out .= '<div>';
        $out .= '<input type="hidden" name="custom-field-template-id[]" id="custom-field-template-id" value="' . $id . '" />';

        if ( isset($options['custom_fields'][$id]['format']) && is_numeric($options['custom_fields'][$id]['format']) )
            $format = stripslashes($options['shortcode_format'][$options['custom_fields'][$id]['format']]);

        $last_title = '';
        $fieldset_open = 0;
        foreach( $fields as $field_key => $field_val ) {
            foreach( $field_val as $title => $data ) {
                $class = $style = $addfield = $tmpout = $out_all = $out_key = $out_value = $duplicator = '';
                if ( isset($data['parentSN']) && is_numeric($data['parentSN']) ) {
                    $parentSN = $data['parentSN'];
                } else {
                    $parentSN = $field_key;
                };
                if ( $fieldset_open ) {
                    $data['inside_fieldset'] = 1;
                };
                if ( isset($data['level']) && is_numeric($data['level']) ) {
                    if ( $data['level'] > $level ) continue;
                };

                require ('plugins/'.$data['type'].'.php');



                if ( isset($options['custom_fields'][$id]['format']) && is_numeric($options['custom_fields'][$id]['format']) ) {
                    $duplicator = '['.$title.']';
                    $preg_key = preg_quote($title, '/');
                    $out_key = str_replace('\\', '\\\\', $out_key);
                    $out_key = str_replace('$', '\$', $out_key);
                    $out_value = str_replace('\\', '\\\\', $out_value);
                    $out_value = str_replace('$', '\$', $out_value);
                    $format = preg_replace('/\[\['.$preg_key.'\]\]/', $out_key, $format);
                    $format = preg_replace('/\['.$preg_key.'\]/', $out_value.$duplicator, $format);
                    if ( !empty($last_title) && $last_title != $title ) $format = preg_replace('/\['.preg_quote($last_title,'/').'\]/', '', $format);
                    $last_title = $title;
                } else {
                    $out .= $tmpout.$out_all;
                }
            }
        };
        if ( !empty($last_title) ) {
            $format = preg_replace('/\['.preg_quote($last_title,'/').'\]/', '', $format);
        };

        if ( isset($options['custom_fields'][$id]['format']) && is_numeric($options['custom_fields'][$id]['format']) ) {
            $out .= $format;
        };

        $out .= '<script type="text/javascript">' . "\n" .
            '// <![CDATA[' . "\n";
        $out .= '	jQuery(document).ready(function() {' . "\n" .
            '		jQuery("#custom_field_template_select").val("' . $id . '");' . "\n" .
            '	});' . "\n";
        $out .= '// ]]>' . "\n" .
            '</script>';
        $out .= '</div>';
        $out .= '</div>';

        return array($out, $id);
    }

    public function custom_field_template_filter(){
        global $post, $wp_version;

        $options = $this->get_custom_field_template_data();
        $filtered_cfts = array();

        $post_id = isset($_REQUEST['post']) ? $_REQUEST['post'] : '';
        if ( empty($post) ) $post = get_post($post_id);

        $categories = get_the_category($post_id);
        $cats = array();
        if ( is_array($categories) ) foreach($categories as $category) $cats[] = $category->cat_ID;

        if ( !empty($_REQUEST['tax_input']) && is_array($_REQUEST['tax_input']) ) :
            foreach($_REQUEST['tax_input'] as $key => $val) :
                $cats = array_merge($cats, $val);
            endforeach;
        elseif ( !empty($_REQUEST['post_category']) ) :
            $cats = array_merge($cats, $_REQUEST['post_category']);
        endif;

        for ( $i=0; $i < count($options['custom_fields']); $i++ ) :
            unset($cat_ids, $template_files, $post_ids);
            if ( !empty($options['custom_fields'][$i]['post_type']) ) :
                if ( substr($wp_version, 0, 3) < '3.0' ) :
                    if ( $options['custom_fields'][$i]['post_type'] == 'post' && (strstr($_SERVER['REQUEST_URI'], 'wp-admin/page-new.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/page.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/edit-pages.php')) ) :
                        continue;
                    endif;
                    if ( $options['custom_fields'][$i]['post_type'] == 'page' && (strstr($_SERVER['REQUEST_URI'], 'wp-admin/post-new.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/post.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/edit.php')) ) :
                        continue;
                    endif;
                else :
                    if ( $post->post_type!=$options['custom_fields'][$i]['post_type'] ) :
                        continue;
                    endif;
                endif;
            endif;

            if ( !empty($options['custom_fields'][$i]['custom_post_type']) ) :
                $custom_post_type = explode(',', $options['custom_fields'][$i]['custom_post_type']);
                $custom_post_type = array_filter( $custom_post_type );
                $custom_post_type = array_unique(array_filter(array_map('trim', $custom_post_type)));
                if ( !in_array($post->post_type, $custom_post_type) )
                    continue;
            endif;

            $cat_ids = isset($options['custom_fields'][$i]['category']) ? explode(',', $options['custom_fields'][$i]['category']) : array();
            $template_files = isset($options['custom_fields'][$i]['template_files']) ? explode(',', $options['custom_fields'][$i]['template_files']) : array();
            $post_ids = isset($options['custom_fields'][$i]['post']) ? explode(',', $options['custom_fields'][$i]['post']) : array();
            $cat_ids = array_filter( $cat_ids );
            $template_files = array_filter( $template_files );
            $post_ids = array_filter( $post_ids );
            $cat_ids = array_unique(array_filter(array_map('trim', $cat_ids)));
            $template_files = array_unique(array_filter(array_map('trim', $template_files)));
            $post_ids = array_unique(array_filter(array_map('trim', $post_ids)));

            if ( !empty($template_files) ) :
                if ( (strstr($_SERVER['REQUEST_URI'], 'wp-admin/page-new.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/page.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/edit-pages.php') || strstr($_SERVER['REQUEST_URI'], 'post_type=page') || $post->post_type=='page') ) :
                    if ( count($template_files) && (isset($post->page_template) || isset($_REQUEST['page_template'])) ) :
                        if( !in_array($post->page_template, $template_files) && (!isset($_REQUEST['page_template']) || (isset($_REQUEST['page_template']) && !in_array($_REQUEST['page_template'], $template_files))) ) :
                            continue;
                        endif;
                    else :
                        continue;
                    endif;
                else :
                    continue;
                endif;
            endif;

            if ( count($post_ids) && (!isset($_REQUEST['post']) || (isset($_REQUEST['post']) &&!in_array($_REQUEST['post'], $post_ids))) ) :
                continue;
            endif;

            if ( !empty($cat_ids) ) :
                if ( (strstr($_SERVER['REQUEST_URI'], 'wp-admin/post-new.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/post.php') || strstr($_SERVER['REQUEST_URI'], 'wp-admin/edit.php')) ) :
                    if ( is_array($cat_ids) && count($cat_ids) && count($cats)>0 ) :
                        $cat_match = 0;
                        foreach ( $cat_ids as $cat_id ) :
                            if (in_array($cat_id, $cats) ) :
                                $cat_match = 1;
                            endif;
                        endforeach;
                        if($cat_match == 0) :
                            continue;
                        endif;
                    else :
                        continue;
                    endif;
                else :
                    continue;
                endif;
            endif;

            $options['custom_fields'][$i]['id'] = $i;
            $filtered_cfts[] = $options['custom_fields'][$i];
        endfor;
        return $filtered_cfts;
    }

    public function get_post_meta($post_id, $key = '', $single = false) {
        if ( !$post_id ) return '';

        if ( $preview_id = $this->get_preview_id( $post_id ) ) $post_id = $preview_id;

        $post_id = (int) $post_id;

        $meta_cache = wp_cache_get($post_id, 'cft_post_meta');

        if ( !$meta_cache ) {
            if ( $meta_list = $this->has_meta( $post_id ) ) {
                foreach ( (array) $meta_list as $metarow) {
                    $mpid = (int) $metarow['post_id'];
                    $mkey = $metarow['meta_key'];
                    $mval = $metarow['meta_value'];

                    if ( !isset($cache[$mpid]) || !is_array($cache[$mpid]) )
                        $cache[$mpid] = array();
                    if ( !isset($cache[$mpid][$mkey]) || !is_array($cache[$mpid][$mkey]) )
                        $cache[$mpid][$mkey] = array();

                    $cache[$mpid][$mkey][] = $mval;
                }
            }

            /*foreach ( (array) $ids as $id ) {
            if ( ! isset($cache[$id]) )
            $cache[$id] = array();
            }*/

            if ( !empty($cache) && is_array($cache) ) :
                foreach ( (array) array_keys($cache) as $post)
                    wp_cache_set($post, $cache[$post], 'cft_post_meta');

                $meta_cache = wp_cache_get($post_id, 'cft_post_meta');
            endif;
        }

        if ( $key ) :
            if ( $single && isset($meta_cache[$key][0]) ) :
                return maybe_unserialize( $meta_cache[$key][0] );
            else :
                if ( isset($meta_cache[$key]) ) :
                    if ( is_array($meta_cache[$key]) ) :
                        return array_map('maybe_unserialize', $meta_cache[$key]);
                    else :
                        return $meta_cache[$key];
                    endif;
                endif;
            endif;
        else :
            if ( is_array($meta_cache) ) :
                return array_map('maybe_unserialize', $meta_cache);
            endif;
        endif;

        return '';
    }

    public function has_meta( $postid ) {
        global $wpdb;

        return $wpdb->get_results( $wpdb->prepare("SELECT meta_key, meta_value, meta_id, post_id FROM $wpdb->postmeta WHERE post_id = %d ORDER BY meta_key,meta_id", $postid), ARRAY_A );
    }

    function custom_field_template_posts_join($sql) {
        if ( !in_array($_REQUEST['orderby'], array('post_author', 'post_date', 'post_title', 'post_modified', 'menu_order', 'post_parent', 'ID')) ){
            if ( (strtoupper($_REQUEST['order']) == 'ASC' || strtoupper($_REQUEST['order']) == 'DESC') && !empty($_REQUEST['orderby']) ) {
                global $wpdb;

                $sql = $wpdb->prepare(" LEFT JOIN `" . $wpdb->postmeta . "` AS meta ON (`" . $wpdb->posts . "`.ID = meta.post_id AND meta.meta_key = %s)", $_REQUEST['orderby']);
                return $sql;
            };
        };
    }

    function custom_field_template_posts_where($where) {
        global $wp_query, $wp_version, $wpdb;
        $options = $this->get_custom_field_template_data();

        if ( isset($_REQUEST['no_is_search']) ) :
            $wp_query->is_search = '';
        else:
            $wp_query->is_search = 1;
        endif;
        $wp_query->is_page = '';
        $wp_query->is_singular = '';

        $original_where = $where;

        $where = '';

        $count = count($options['custom_fields']);
        if ( $count ) :
            for ($i=0;$i<$count;$i++) :
                $fields = $this->get_custom_fields( $i );
                foreach ( $fields as $field_key => $field_val ) :
                    foreach ( $field_val as $key => $val ) :
                        $replace[$key] = $val;
                        $search = array();
                        if( isset($val['searchType']) ) eval('$search["type"] =' . stripslashes($val['searchType']));
                        if( isset($val['searchValue']) ) eval('$search["value"] =' . stripslashes($val['searchValue']));
                        if( isset($val['searchOperator']) ) eval('$search["operator"] =' . stripslashes($val['searchOperator']));

                        foreach ( $search as $skey => $sval ) :
                            $j = 1;
                            foreach ( $sval as $sval2 ) :
                                $replace[$key][$j][$skey] = $sval2;
                                $j++;
                            endforeach;
                        endforeach;
                    endforeach;
                endforeach;
            endfor;
        endif;

        if ( is_array($_REQUEST['cftsearch']) ) :
            foreach ( $_REQUEST['cftsearch'] as $key => $val ) :
                $key = rawurldecode($key);
                if ( is_array($val) ) :
                    $ch = 0;
                    foreach( $val as $key2 => $val2 ) :
                        if ( is_array($val2) ) :
                            foreach( $val2 as $val3 ) :
                                if ( $val3 ) :
                                    if ( $ch == 0 ) : $where .= ' AND (';
                                    else :
                                        if ( $replace[$key][$key2]['type'] == 'checkbox' || !$replace[$key][$key2]['type'] ) $where .= ' OR ';
                                        else $where .= ' AND ';
                                    endif;
                                    if ( !isset($replace[$key][$key2]['operator']) ) $replace[$key][$key2]['operator'] = '';
                                    switch( $replace[$key][$key2]['operator'] ) :
                                        case '<=' :
                                        case '>=' :
                                        case '<' :
                                        case '>' :
                                        case '=' :
                                        case '<>' :
                                        case '<=>':
                                            if ( is_numeric($val3) ) :
                                                $where .=  $wpdb->prepare(" ID IN (SELECT `" . $wpdb->postmeta . "`.post_id FROM `" . $wpdb->postmeta . "` WHERE (`" . $wpdb->postmeta . "`.meta_key = %s AND `" . $wpdb->postmeta . "`.meta_value " . $replace[$key][$key2]['operator'] . " %d) ) ", $key, trim($val3));
                                            else :
                                                $where .= $wpdb->prepare(" ID IN (SELECT `" . $wpdb->postmeta . "`.post_id FROM `" . $wpdb->postmeta . "` WHERE (`" . $wpdb->postmeta . "`.meta_key = %s AND `" . $wpdb->postmeta . "`.meta_value " . $replace[$key][$key2]['operator'] . " %s) ) ", $key, trim($val3));
                                            endif;
                                            break;
                                        default :
                                            $where .= $wpdb->prepare(" ID IN (SELECT `" . $wpdb->postmeta . "`.post_id FROM `" . $wpdb->postmeta . "` WHERE (`" . $wpdb->postmeta . "`.meta_key = %s AND `" . $wpdb->postmeta . "`.meta_value LIKE %s) ) ", $key, '%'.trim($val3).'%');
                                            break;
                                    endswitch;
                                    $ch++;
                                endif;
                            endforeach;
                        endif;
                    endforeach;
                    if ( $ch>0 ) $where .= ') ';
                endif;
            endforeach;
        endif;

        if ( $_REQUEST['s'] ) :
            $where .= ' AND (';
            if ( function_exists('mb_split') ) :
                $s = mb_split('\s', $_REQUEST['s']);
            else:
                $s = split('\s', $_REQUEST['s']);
            endif;
            $i=0;
            foreach ( $s as $v ) :
                if ( !empty($v) ) :
                    if ( $i>0 ) $where .= ' AND ';
                    $where .= $wpdb->prepare(" ID IN (SELECT `" . $wpdb->postmeta . "`.post_id FROM `" . $wpdb->postmeta . "` WHERE (`" . $wpdb->postmeta . "`.meta_value LIKE %s) ) ", '%'.trim($v).'%');
                    $i++;
                endif;
            endforeach;
            $where .= $wpdb->prepare(" OR ((`" . $wpdb->posts . "`.post_title LIKE %s) OR (`" . $wpdb->posts . "`.post_content LIKE %s))", '%'.trim($_REQUEST['s']).'%', '%'.trim($_REQUEST['s']).'%');
            $where .= ') ';
        endif;

        if ( is_array($_REQUEST['cftcategory_in']) ) :
            $ids = get_objects_in_term($_REQUEST['cftcategory_in'], 'category');
            if ( is_array($ids) && count($ids) > 0 ) :
                $in_posts = "'" . implode("', '", $ids) . "'";
                $where .= " AND ID IN (" . $in_posts . ")";
            endif;
            $where .= " AND `" . $wpdb->posts . "`.post_type = 'post'";
        endif;
        if ( isset($_REQUEST['cftcategory_not_in']) && is_array($_REQUEST['cftcategory_not_in']) ) :
            $ids = get_objects_in_term($_REQUEST['cftcategory_not_in'], 'category');
            if ( is_array($ids) && count($ids) > 0 ) :
                $in_posts = "'" . implode("', '", $ids) . "'";
                $where .= " AND ID NOT IN (" . $in_posts . ")";
            endif;
        endif;

        if ( !empty($_REQUEST['post_type']) ) :
            $where .= $wpdb->prepare(" AND `" . $wpdb->posts . "`.post_type = %s", trim($_REQUEST['post_type']));
        endif;

        if ( !empty($_REQUEST['no_is_search']) ) :
            $where .= " AND `".$wpdb->posts."`.post_status = 'publish'";
        else :
            $where .= " AND `".$wpdb->posts."`.post_status = 'publish' GROUP BY `".$wpdb->posts."`.ID";
        endif;

        return $where;
    }

    function custom_field_template_posts_orderby($sql) {
        global $wpdb;

        if ( empty($_REQUEST['order']) || ((strtoupper($_REQUEST['order']) != 'ASC') && (strtoupper($_REQUEST['order']) != 'DESC')) ) {
            $_REQUEST['order'] = 'DESC';
        }


        if ( !empty($_REQUEST['orderby']) ) {
            if ( in_array($_REQUEST['orderby'], array('post_author', 'post_date', 'post_title', 'post_modified', 'menu_order', 'post_parent', 'ID')) ) {
                $sql = "`" . $wpdb->posts . "`." . $_REQUEST['orderby'] . " " . $_REQUEST['order'];
            } elseif ( $_REQUEST['orderby'] == 'rand' ) {
                $sql = "RAND()";
            } else {
                if ( !empty($_REQUEST['cast']) && in_array($_REQUEST['cast'], array('binary', 'char', 'date', 'datetime', 'signed', 'time', 'unsigned')) ) {
                    $sql = " CAST(meta.meta_value AS " . $_REQUEST['cast'] . ") " . $_REQUEST['order'];
                } else {
                    $sql = " meta.meta_value " . $_REQUEST['order'];
                };
            };

            return $sql;
        };

        $sql = "`" . $wpdb->posts . "`.post_date " . $_REQUEST['order'];
        return $sql;
    }

    function output_custom_field_values($attr) {
        global $post;
        $options = $this->options;
        $post_id = empty($post->ID) ?  get_the_ID() : $post->ID;

        if ( !isset($options['custom_field_template_before_list']) ) {
            $options['custom_field_template_before_list'] = '<ul>';
        };
        if ( !isset($options['custom_field_template_after_list']) ) {
            $options['custom_field_template_after_list'] = '</ul>';
        };
        if ( !isset($options['custom_field_template_before_value']) ) {
            $options['custom_field_template_before_value'] = '<li>';
        };
        if ( !isset($options['custom_field_template_after_value']) ) {
            $options['custom_field_template_after_value'] = '</li>';
        };

        extract(shortcode_atts(array(
            'post_id'      => $post_id,
            'template'     => 0,
            'format'       => '',
            'key'          => '',
            'single'       => false,
            'before_list'  => $options['custom_field_template_before_list'],
            'after_list'   => $options['custom_field_template_after_list'],
            'before_value' => $options['custom_field_template_before_value'],
            'after_value'  => $options['custom_field_template_after_value'],
            'image_size'   => '',
            'image_src'    => false,
            'image_width'  => false,
            'image_height' => false,
            'value_count'  => false,
            'value' => ''
        ), $attr));

        $metakey = $key;
        $output = '';
        if ( $metakey ) {
            if ( $value_count && $value ) {
                return number_format($options['value_count'][$metakey][$value]);
            };
            $metavalue = $this->get_post_meta($post_id, $key, $single);
            if ( !is_array($metavalue) ) {
                $metavalue = array($metavalue);
            };
            if ( $before_list ) {
                $output = $before_list . "\n";
            };
            foreach ( $metavalue as $val ) {
                if ( !empty($image_size) ) {
                    if ( $image_src || $image_width || $image_height ) {
                        list($src, $width, $height) = wp_get_attachment_image_src($val, $image_size);
                        if ( $image_src ) : $val = $src; endif;
                        if ( $image_width ) : $val = $width; endif;
                        if ( $image_height ) : $val = $height; endif;
                    } else {
                        $val = wp_get_attachment_image($val, $image_size);
                    };
                };
                $output .= (isset($before_value) ? $before_value : '') . $val . (isset($after_value) ? $after_value : '') . "\n";
            };
            if ( $after_list ) {
                $output .= $after_list . "\n";
            };
            return do_shortcode($output);
        };

        if ( is_numeric($format) && $output = $options['shortcode_format'][$format] ) :
            $data = $this->get_post_meta($post_id);
            $output = stripcslashes($output);

            if( $data == null)
                return;

            $count = count($options['custom_fields']);
            if ( $count ) :
                for ($i=0;$i<$count;$i++) :
                    $fields = $this->get_custom_fields( $i );
                    foreach ( $fields as $field_key => $field_val ) :
                        foreach ( $field_val as $key => $val ) :
                            $replace_val = '';
                            if ( isset($data[$key]) && count($data[$key]) > 1 ) :
                                if ( isset($val['sort']) && $val['sort'] == 'asc' ) :
                                    sort($data[$key]);
                                elseif ( isset($val['sort']) && $val['sort'] == 'desc' ) :
                                    rsort($data[$key]);
                                endif;
                                if ( $before_list ) : $replace_val = $before_list . "\n"; endif;
                                foreach ( $data[$key] as $val2 ) :
                                    $value = $val2;
                                    if ( isset($val['outputCode']) && is_numeric($val['outputCode']) ) :
                                        eval(stripcslashes($options['php'][$val['outputCode']]));
                                    endif;
                                    if ( isset($val['shortCode']) && $val['shortCode'] == true ) $value = do_shortcode($value);
                                    $replace_val .= $before_value . $value . $after_value . "\n";
                                endforeach;
                                if ( $after_list ) : $replace_val .= $after_list . "\n"; endif;
                            elseif ( isset($data[$key]) && count($data[$key]) == 1 ) :
                                $value = $data[$key][0];
                                if ( isset($val['outputCode']) && is_numeric($val['outputCode']) ) :
                                    eval(stripcslashes($options['php'][$val['outputCode']]));
                                endif;
                                if ( isset($val['shortCode']) && $val['shortCode'] == true ) $value = do_shortcode($value);
                                $replace_val = $value;
                                if ( isset($val['singleList']) && $val['singleList'] == true ) :
                                    if ( $before_list ) : $replace_val = $before_list . "\n"; endif;
                                    $replace_val .= $before_value . $value . $after_value . "\n";
                                    if ( $after_list ) : $replace_val .= $after_list . "\n"; endif;
                                endif;
                            else :
                                if ( isset($val['outputNone']) ) $replace_val = $val['outputNone'];
                                else $replace_val = '';
                            endif;
                            if ( isset($options['shortcode_format_use_php'][$format]) )
                                $output = $this->EvalBuffer($output);

                            $key = preg_quote($key, '/');
                            $replace_val = str_replace('\\', '\\\\', $replace_val);
                            $replace_val = str_replace('$', '\$', $replace_val);
                            $output = preg_replace('/\['.$key.'\]/', $replace_val, $output);
                        endforeach;
                    endforeach;
                endfor;
            endif;
        else :
            $fields = $this->get_custom_fields( $template );

            if( $fields == null) {
                return;
            }


            $output = '<dl class="cft cft'.$template.'">' . "\n";
            foreach ( $fields as $field_key => $field_val ) :
                foreach ( $field_val as $key => $val ) :
                    if ( isset($keylist[$key]) && $keylist[$key] == true ) break;
                    $values = $this->get_post_meta( $post_id, $key );
                    if ( $values ):
                        if ( isset($val['sort']) && $val['sort'] == 'asc' ) :
                            sort($values);
                        elseif ( isset($val['sort']) && $val['sort'] == 'desc' ) :
                            rsort($values);
                        endif;
                        if ( isset($val['output']) && $val['output'] == true ) :
                            foreach ( $values as $num => $value ) :
                                $value = str_replace('\\', '\\\\', $value);
                                if ( isset($val['outputCode']) && is_numeric($val['outputCode']) ) :
                                    eval(stripcslashes($options['php'][$val['outputCode']]));
                                endif;
                                if ( empty($value) && $val['outputNone'] ) $value = $val['outputNone'];
                                if ( isset($val['shortCode']) && $val['shortCode'] == true ) $value = do_shortcode($value);
                                if ( !empty($val['label']) && !empty($options['custom_field_template_replace_keys_by_labels']) )
                                    $key_val = stripcslashes($val['label']);
                                else $key_val = $key;
                                if ( isset($val['hideKey']) && $val['hideKey'] != true && $num == 0 )
                                    $output .= '<dt>' . $key_val . '</dt>' . "\n";
                                $output .= '<dd>' . $value . '</dd>' . "\n";
                            endforeach;
                        endif;
                    endif;
                    $keylist[$key] = true;
                endforeach;
            endforeach;
            $output .= '</dl>' . "\n";
        endif;

        return do_shortcode(stripcslashes($output));
    }

    function search_custom_field_values($attr) {
        global $post;
        $options = $this->options;

        extract(shortcode_atts(array(
            'template'     => 0,
            'format'       => '',
            'search_label' => __('Search &raquo;', 'custom-field-template'),
            'button'       => true
        ), $attr));

        if ( is_numeric($format) && $output = $options['shortcode_format'][$format] ) :
            $output = stripcslashes($output);
            $output = '<form method="get" action="'.get_option('home').'/" id="cftsearch'.(int)$format.'">' . "\n" . $output;

            $count = count($options['custom_fields']);
            if ( $count ) :
                for ($t=0;$t<$count;$t++) :
                    $fields = $this->get_custom_fields( $t );
                    foreach ( $fields as $field_key => $field_val ) :
                        foreach ( $field_val as $key => $val ) :
                            unset($replace);
                            $replace[0] = $val;

                            $search = array();
                            if( isset($val['searchType']) ) eval('$search["type"] =' . stripslashes($val['searchType']));
                            if( isset($val['searchValue']) ) eval('$search["value"] =' . stripslashes($val['searchValue']));
                            if( isset($val['searchOperator']) ) eval('$search["operator"] =' . stripslashes($val['searchOperator']));
                            if( isset($val['searchValueLabel']) ) eval('$search["valueLabel"] =' . stripslashes($val['searchValueLabel']));
                            if( isset($val['searchDefault']) ) eval('$search["default"] =' . stripslashes($val['searchDefault']));
                            if( isset($val['searchClass']) ) eval('$search["class"] =' . stripslashes($val['searchClass']));
                            if( isset($val['searchSelectLabel']) ) eval('$search["selectLabel"] =' . stripslashes($val['searchSelectLabel']));

                            foreach ( $search as $skey => $sval ) :
                                $j = 1;
                                foreach ( $sval as $sval2 ) :
                                    $replace[$j][$skey] = $sval2;
                                    $j++;
                                endforeach;
                            endforeach;

                            foreach( $replace as $rkey => $rval ) :
                                $replace_val[$rkey] = "";
                                $class = "";
                                $default = array();
                                switch ( $rval['type'] ) :
                                    case 'text':
                                    case 'textfield':
                                    case 'textarea':
                                        if ( !empty($rval['class']) ) $class = ' class="' . $rval['class'] . '"';
                                        $replace_val[$rkey] .= '<input type="text" name="cftsearch[' . rawurlencode($key) . '][' . $rkey . '][]" value="' . esc_attr($_REQUEST['cftsearch'][rawurlencode($key)][$rkey][0]) . '"' . $class . ' />';
                                        break;
                                    case 'checkbox':
                                        if ( !empty($rval['class']) ) $class = ' class="' . $rval['class'] . '"';
                                        $values = $valueLabel = array();
                                        if ( $rkey != 0 )
                                            $values = explode( '#', $rval['value'] );
                                        else
                                            $values = explode( '#', $rval['originalValue'] );
                                        $valueLabel = explode( '#', $rval['valueLabel'] );
                                        $default = explode( '#', $rval['default'] );
                                        if ( is_numeric($rval['searchCode']) ) :
                                            eval(stripcslashes($options['php'][$rval['searchCode']]));
                                        endif;
                                        if ( count($values) > 1 ) :
                                            $replace_val[$rkey] .= '<ul' . $class . '>';
                                            $j=0;
                                            foreach( $values as $metavalue ) :
                                                $checked = '';
                                                $metavalue = trim($metavalue);
                                                if ( is_array($_REQUEST['cftsearch'][rawurlencode($key)][$rkey]) ) :
                                                    if ( in_array($metavalue, $_REQUEST['cftsearch'][rawurlencode($key)][$rkey]) )
                                                        $checked = ' checked="checked"';
                                                    else
                                                        $checked = '';
                                                endif;
                                                if ( in_array($metavalue, $default) && !$_REQUEST['cftsearch'][rawurlencode($key)][$rkey] )
                                                    $checked = ' checked="checked"';

                                                $replace_val[$rkey] .= '<li><label><input type="checkbox" name="cftsearch[' . rawurlencode($key) . '][' . $rkey . '][]" value="' . esc_attr($metavalue) . '"' . $class . $checked . '  /> ';
                                                if ( $valueLabel[$j] ) $replace_val[$rkey] .= stripcslashes($valueLabel[$j]);
                                                else $replace_val[$rkey] .= stripcslashes($metavalue);
                                                $replace_val[$rkey] .= '</label></li>';
                                                $j++;
                                            endforeach;
                                            $replace_val[$rkey] .= '</ul>';
                                        else :
                                            if ( $_REQUEST['cftsearch'][rawurlencode($key)][$rkey][0] == esc_attr(trim($values[0])) )
                                                $checked = ' checked="checked"';
                                            $replace_val[$rkey] .= '<label><input type="checkbox" name="cftsearch[' . rawurlencode($key) . '][' . $rkey . '][]" value="' . esc_attr(trim($values[0])) . '"' . $class . $checked . ' /> ';
                                            if ( $valueLabel[0] ) $replace_val[$rkey] .= stripcslashes(trim($valueLabel[0]));
                                            else $replace_val[$rkey] .= stripcslashes(trim($values[0]));
                                            $replace_val[$rkey] .= '</label>';
                                        endif;
                                        break;
                                    case 'radio':
                                        if ( !empty($rval['class']) ) $class = ' class="' . $rval['class'] . '"';
                                        $values = explode( '#', $rval['value'] );
                                        $valueLabel = explode( '#', $rval['valueLabel'] );
                                        $default = explode( '#', $rval['default'] );
                                        if ( is_numeric($rval['searchCode']) ) :
                                            eval(stripcslashes($options['php'][$rval['searchCode']]));
                                        endif;
                                        if ( count($values) > 1 ) :
                                            $replace_val[$rkey] .= '<ul' . $class . '>';
                                            $j=0;
                                            foreach ( $values as $metavalue ) :
                                                $checked = '';
                                                $metavalue = trim($metavalue);
                                                if ( is_array($_REQUEST['cftsearch'][rawurlencode($key)][$rkey]) ) :
                                                    if ( in_array($metavalue, $_REQUEST['cftsearch'][rawurlencode($key)][$rkey]) )
                                                        $checked = ' checked="checked"';
                                                    else
                                                        $checked = '';
                                                endif;
                                                if ( in_array($metavalue, $default) && !$_REQUEST['cftsearch'][rawurlencode($key)][$rkey] )
                                                    $checked = ' checked="checked"';
                                                $replace_val[$rkey] .= '<li><label><input type="radio" name="cftsearch[' . rawurlencode($key) . '][' . $rkey . '][]" value="' . esc_attr($metavalue) . '"' . $class . $checked . ' /> ';
                                                if ( $valueLabel[$j] ) $replace_val[$rkey] .= stripcslashes(trim($valueLabel[$j]));
                                                else $replace_val[$rkey] .= stripcslashes($metavalue);
                                                $replace_val[$rkey] .= '</label></li>';
                                                $j++;
                                            endforeach;
                                            $replace_val[$rkey] .= '</ul>';
                                        else :
                                            if ( $_REQUEST['cftsearch'][rawurlencode($key)][$rkey][0] == esc_attr(trim($values[0])) )
                                                $checked = ' checked="checked"';
                                            $replace_val[$rkey] .= '<label><input type="radio" name="cftsearch[' . rawurlencode($key) . '][]" value="' . esc_attr(trim($values[0])) . '"' . $class . $checked . ' /> ';
                                            if ( $valueLabel[0] ) $replace_val[$rkey] .= stripcslashes(trim($valueLabel[0]));
                                            else $replace_val[$rkey] .= stripcslashes(trim($values[0]));
                                            $replace_val[$rkey] .= '</label>';
                                        endif;
                                        break;
                                    case 'select':
                                        if ( !empty($rval['class']) ) $class = ' class="' . $rval['class'] . '"';
                                        $values = explode( '#', $rval['value'] );
                                        $valueLabel = isset($rval['valueLabel']) ? explode( '#', $rval['valueLabel'] ) : array();
                                        $default = isset($rval['default']) ? explode( '#', $rval['default'] ) : array();
                                        $selectLabel= isset($rval['selectLabel']) ? $rval['selectLabel'] : '';

                                        if ( isset($rval['searchCode']) && is_numeric($rval['searchCode']) ) :
                                            eval(stripcslashes($options['php'][$rval['searchCode']]));
                                        endif;
                                        $replace_val[$rkey] .= '<select name="cftsearch[' . rawurlencode($key) . '][' . $rkey . '][]"' . $class . '>';
                                        $replace_val[$rkey] .= '<option value="">'.$selectLabel.'</option>';
                                        $j=0;
                                        foreach ( $values as $metaval ) :
                                            $metaval = trim($metaval);
                                            if ( in_array($metavalue, $default) && !$_REQUEST['cftsearch'][rawurlencode($key)][$rkey] )
                                                $checked = ' checked="checked"';

                                            if ( $_REQUEST['cftsearch'][rawurlencode($key)][$rkey][0] == $metaval ) $selected = ' selected="selected"';
                                            else $selected = "";
                                            $replace_val[$rkey] .= '<option value="' . esc_attr($metaval) . '"' . $selected . '>';
                                            if ( $valueLabel[$j] )
                                                $replace_val[$rkey] .= stripcslashes(trim($valueLabel[$j]));
                                            else
                                                $replace_val[$rkey] .= stripcslashes($metaval);
                                            $replace_val[$rkey] .= '</option>' . "\n";
                                            $j++;
                                        endforeach;
                                        $replace_val[$rkey] .= '</select>' . "\n";
                                        break;
                                endswitch;
                            endforeach;

                            if ( isset($options['shortcode_format_use_php'][$format]) )
                                $output = $this->EvalBuffer($output);
                            $key = preg_quote($key, '/');
                            $output = preg_replace('/\['.$key.'\](?!\[[0-9]+\])/', $replace_val[0], $output);
                            $output = preg_replace('/\['.$key.'\]\[([0-9]+)\](?!\[\])/e', '$replace_val[${1}]', $output);
                        endforeach;
                    endforeach;
                endfor;
            endif;

            if ( $button === true )
                $output .= '<p><input type="submit" value="' . $search_label . '" class="cftsearch_submit" /></p>' . "\n";
            $output .= '<input type="hidden" name="cftsearch_submit" value="1" />' . "\n";
            $output .= '</form>' . "\n";
        else :
            $fields = $this->get_custom_fields( $template );

            if ( $fields == null )
                return;

            $output = '<form method="get" action="'.get_option('home').'/" id="cftsearch'.(int)$format.'">' . "\n";
            foreach( $fields as $field_key => $field_val) :
                foreach( $field_val as $key => $val) :
                    if ( $val['search'] == true ) :
                        if ( !empty($val['label']) && !empty($options['custom_field_template_replace_keys_by_labels']) )
                            $label = stripcslashes($val['label']);
                        else $label = $key;
                        $output .= '<dl>' ."\n";
                        if ( $val['hideKey'] != true) :
                            $output .= '<dt><label>' . $label . '</label></dt>' ."\n";
                        endif;

                        $class = "";
                        switch ( $val['type'] ) :
                            case 'text':
                            case 'textfield':
                            case 'textarea':
                                if ( $val['class'] ) $class = ' class="' . $val['class'] . '"';
                                $output .= '<dd><input type="text" name="cftsearch[' . rawurlencode($key) . '][' . $rkey . '][]" value="' . esc_attr($_REQUEST['cftsearch'][rawurlencode($key)][0][0]) . '"' . $class . ' /></dd>';
                                break;
                            case 'checkbox':
                                unset($checked);
                                if ( $val['class'] ) $class = ' class="' . $val['class'] . '"';
                                if ( is_array($_REQUEST['cftsearch'][rawurlencode($key)]) )
                                    foreach ( $_REQUEST['cftsearch'][rawurlencode($key)] as $values )
                                        if ( $val['value'] == $values[0] ) $checked = ' checked="checked"';
                                $output .= '<dd><label><input type="checkbox" name="cftsearch[' . rawurlencode($key) . '][' . $rkey . '][]" value="' . esc_attr($val['value']) . '"' . $class . $checked . ' /> ';
                                if ( $val['valueLabel'] )
                                    $output .= stripcslashes($val['valueLabel']);
                                else
                                    $output .= stripcslashes($val['value']);
                                $output .= '</label></dd>' . "\n";
                                break;
                            case 'radio':
                                if ( $val['class'] ) $class = ' class="' . $val['class'] . '"';
                                $values = explode( '#', $val['value'] );
                                $valueLabel = explode( '#', $val['valueLabel'] );
                                $i=0;
                                foreach ( $values as $metaval ) :
                                    unset($checked);
                                    $metaval = trim($metaval);
                                    if ( $_REQUEST['cftsearch'][rawurlencode($key)][0][0] == $metaval ) $checked = 'checked="checked"';
                                    $output .= '<dd><label>' . '<input type="radio" name="cftsearch[' . rawurlencode($key) . '][' . $rkey . '][]" value="' . esc_attr($metaval) . '"' . $class . $checked . ' /> ';
                                    if ( $val['valueLabel'] )
                                        $output .= stripcslashes(trim($valueLabel[$i]));
                                    else
                                        $output .= stripcslashes($metaval);
                                    $i++;
                                    $output .= '</label></dd>' . "\n";
                                endforeach;
                                break;
                            case 'select':
                                if ( $val['class'] ) $class = ' class="' . $val['class'] . '"';
                                $values = explode( '#', $val['value'] );
                                $valueLabel = explode( '#', $val['valueLabel'] );
                                $output .= '<dd><select name="cftsearch[' . rawurlencode($key) . '][' . $rkey . '][]"' . $class . '>';
                                $output .= '<option value=""></option>';
                                $i=0;
                                foreach ( $values as $metaval ) :
                                    unset($selected);
                                    $metaval = trim($metaval);
                                    if ( $_REQUEST['cftsearch'][rawurlencode($key)][0][0] == $metaval ) $selected = 'selected="selected"';
                                    else $selected = "";
                                    $output .= '<option value="' . esc_attr($metaval) . '"' . $selected . '>';
                                    if ( $val['valueLabel'] )
                                        $output .= stripcslashes(trim($valueLabel[$i]));
                                    else
                                        $output .= stripcslashes($metaval);
                                    $output .= '</option>' . "\n";
                                    $i++;
                                endforeach;
                                $output .= '</select></dd>' . "\n";
                                break;
                        endswitch;
                        $output .= '</dl>' ."\n";
                    endif;
                endforeach;
            endforeach;
            if ( $button == true )
                $output .= '<p><input type="submit" value="' . $search_label . '" class="cftsearch_submit" /></p>' . "\n";
            $output .= '<input type="hidden" name="cftsearch_submit" value="1" /></p>' . "\n";
            $output .= '</form>' . "\n";
        endif;

        return do_shortcode(stripcslashes($output));
    }

    function custom_field_template_the_content($content) {
        global $wp_query, $post, $shortcode_tags, $wp_version;
        $options = $this->get_custom_field_template_data();

        if ( isset($options['hook']) && count($options['hook']) > 0 ) {
            $categories = get_the_category();
            $cats = array();
            foreach( $categories as $val ) {
                $cats[] = $val->cat_ID;
            };

            for ( $i=0; $i<count($options['hook']); $i++ ) {

                if ( $this->is_excerpt && empty($options['hook'][$i]['excerpt']) ) {
                    $this->is_excerpt = false;
                    $content = $post->post_excerpt ? $post->post_excerpt : strip_shortcodes($content);
                    $strip_shortcode = 1;
                    continue;
                };

                $options['hook'][$i]['content'] = stripslashes($options['hook'][$i]['content']);
                if ( is_feed() && empty($options['hook'][$i]['feed']) ) {
                    break;
                };

                if ( !empty($options['hook'][$i]['category']) ) {
                    if ( is_category() || is_single() || is_feed() ) {
                        if ( !empty($options['hook'][$i]['use_php']) ) {
                            $options['hook'][$i]['content'] = $this->EvalBuffer(stripcslashes($options['hook'][$i]['content']));
                        };
                        $needle = explode(',', $options['hook'][$i]['category']);
                        $needle = array_filter($needle);
                        $needle = array_unique(array_filter(array_map('trim', $needle)));
                        foreach ( $needle as $val ) {
                            if ( in_array($val, $cats ) ) {
                                if ( $options['hook'][$i]['position'] == 0 ) {
                                    $content .= $options['hook'][$i]['content'];
                                } elseif ( $options['hook'][$i]['position'] == 2 ) {
                                    $content = preg_replace('/\[cfthook hook='.$i.'\]/', $options['hook'][$i]['content'], $content);
                                } else {
                                    $content = $options['hook'][$i]['content'] . $content;
                                }
                                break;
                            };
                        };
                    };
                } elseif ( $options['hook'][$i]['post_type']=='post' ) {
                    if ( is_single() ) {
                        if ( !empty($options['hook'][$i]['use_php']) ) {
                            $options['hook'][$i]['content'] = $this->EvalBuffer(stripcslashes($options['hook'][$i]['content']));
                        };
                        if ( $options['hook'][$i]['position'] == 0 ) {
                            $content .= $options['hook'][$i]['content'];
                        } elseif ( $options['hook'][$i]['position'] == 2 ) {
                            $content = preg_replace('/\[cfthook hook='.$i.'\]/', $options['hook'][$i]['content'], $content);
                        } else {
                            $content = $options['hook'][$i]['content'] . $content;
                        }
                    };
                } elseif ( $options['hook'][$i]['post_type']=='page' ) {
                    if ( is_page() ) {
                        if ( !empty($options['hook'][$i]['use_php']) ) {
                            $options['hook'][$i]['content'] = $this->EvalBuffer(stripcslashes($options['hook'][$i]['content']));
                        };
                        if ( $options['hook'][$i]['position'] == 0 ) {
                            $content .= $options['hook'][$i]['content'];
                        } elseif ( $options['hook'][$i]['position'] == 2 ) {
                            $content = preg_replace('/\[cfthook hook='.$i.'\]/', $options['hook'][$i]['content'], $content);
                        } else {
                            $content = $options['hook'][$i]['content'] . $content;
                        }
                    };
                } elseif ( $options['hook'][$i]['custom_post_type'] ) {
                    $custom_post_type = explode(',', $options['hook'][$i]['custom_post_type']);
                    $custom_post_type = array_filter( $custom_post_type );
                    array_walk( $custom_post_type, create_function('&$v', '$v = trim($v);') );
                    if ( in_array($post->post_type, $custom_post_type) ) {
                        if ( !empty($options['hook'][$i]['use_php']) ) {
                            $options['hook'][$i]['content'] = $this->EvalBuffer(stripcslashes($options['hook'][$i]['content']));
                        };
                        if ( $options['hook'][$i]['position'] == 0 ) {
                            $content .= $options['hook'][$i]['content'];
                        } elseif ( $options['hook'][$i]['position'] == 2 ) {
                            $content = preg_replace('/\[cfthook hook='.$i.'\]/', $options['hook'][$i]['content'], $content);
                        } else {
                            $content = $options['hook'][$i]['content'] . $content;
                        }
                    };
                } else {
                    if ( !empty($options['hook'][$i]['use_php']) ) {
                        $options['hook'][$i]['content'] = $this->EvalBuffer(stripcslashes($options['hook'][$i]['content']));
                    }
                    if ( $options['hook'][$i]['position'] == 0 ) {
                        $content .= $options['hook'][$i]['content'];
                    } elseif ( $options['hook'][$i]['position'] == 2 ) {
                        $content = preg_replace('/\[cfthook hook='.$i.'\]/', $options['hook'][$i]['content'], $content);
                    } else {
                        $content = $options['hook'][$i]['content'] . $content;
                    }
                };
            };
        };

        return !empty($strip_shortcode)? $content : do_shortcode($content);
    }

    function custom_field_template_get_the_excerpt($excerpt) {
        $options = $this->get_custom_field_template_data();
        if ( empty($excerpt) ) {
            $this->is_excerpt = true;
        };
        if ( !empty($options['custom_field_template_excerpt_shortcode']) ) {
            return do_shortcode($excerpt);
        } else {
            return $excerpt;
        }

    }

    function sanitize_name( $name ) {
        $name = sanitize_title( $name );
        $name = str_replace( '-', '_', $name );

        return $name;
    }


    function EvalBuffer($string) {
        ob_start();
        eval('?>'.$string);
        $ret = ob_get_contents();
        ob_end_clean();
        return $ret;
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
    public static function instance (  ) {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self(  );
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