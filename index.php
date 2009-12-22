<?php
/*
Plugin Name: Comments Policy
Plugin URI: http://www.profitplugs.com/
Description: Help your readers to self-moderate their comments by displaying a clear Comments Policy.
Author: Gobala Krishnan
Author URI: http://www.profitplugs.com/
Version: 1.2

*/
if (!class_exists('metabox')){
    include('metabox.class.php');
}

//for php version < 5.2.1
if ( !function_exists('sys_get_temp_dir') )
{
    function sys_get_temp_dir()
    {
        if ( !empty($_ENV['TMP']) ){
            return realpath( $_ENV['TMP'] );
        }else if ( !empty($_ENV['TMPDIR']) ){
            return realpath( $_ENV['TMPDIR'] );
        }else if ( !empty($_ENV['TEMP']) ){
            return realpath( $_ENV['TEMP'] );
        }else{
            // Try to use system's temporary directory
            // as random name shouldn't exist
            $temp_file = tempnam( md5(uniqid(rand(), TRUE)), '' );
            if ( $temp_file ){
                $temp_dir = realpath( dirname($temp_file) );
                unlink( $temp_file );
                return $temp_dir;
            }else{
                return FALSE;
            }
        }
    }
}

class comments_policy extends metabox{

    function comments_policy(){
        //info box
        $this->info_title = 'Plugin Info';
        $this->info_type = 'Plugin';
        $this->info_data_title = 'Comments Policy';
        $this->info_data_version= '1.1';
        $this->info_data_author= '<a href="http://www.profitplugs.com/" target="_blank">Gobala Krishnan</a>';
        $this->info_data_description= 'Allow readers to self-moderate their comments based on your policy.';
        //---        
        $this->title = 'Comments Policy';
        $this->metabox('comments_policy_plugin');

        add_action('wp_ajax_get_max_value', array(&$this,'get_array_max_value'));
        add_action('wp_ajax_export_policy', array(&$this,'export_comments_policy'));
        add_action('wp_ajax_import_policy', array(&$this,'import_comments_policy'));
    }

    function on_admin_menu_hook(){
         //$this->pagehook = add_object_page('edit-comments.php', "Comments Policy", 'manage_options', $this->page_name, array(&$this, 'on_show_page') );
        $this->pagehook = add_submenu_page('edit-comments.php', "Comments Policy","Comments Policy", 'manage_options', $this->page_name, array(&$this, 'on_show_page') );
    }
    
    function on_load_page_hook() {
        global $policy_data;
        wp_enqueue_script('jquery');        

        add_action('admin_head-'.$this->pagehook, array(&$this,'add_javascript'));
        add_action('admin_head-'.$this->pagehook, array(&$this,'add_style'));
        

        if (!get_option($this->page_name)){
            $this->default_data();
            update_option($this->page_name,$policy_data);
        }
        $policy_data = get_option($this->page_name);
               
        add_meta_box($this->page_name . '_info', $this->info_title , array(&$this, 'metaboxInfo'), $this->pagehook, 'side', 'core');
        add_meta_box($this->page_name . '_1', 'Your Comments Policy', array(&$this, 'comment_policy'), $this->pagehook, 'normal', 'core');
        add_meta_box($this->page_name . '_2', 'Custom CSS', array(&$this, 'custom_css'), $this->pagehook, 'normal', 'core');
        add_meta_box($this->page_name . '_3', 'How to Use This Plugin', array(&$this, 'how_to'), $this->pagehook, 'normal', 'core');
        add_meta_box($this->page_name . '_4', 'Import & Export Policy', array(&$this, 'import_export'), $this->pagehook, 'side', 'core');
        
    }
    function on_save_changes_hook(){
        global $msg;
        global $policy_data;

        if (isset($_POST[$this->page_name.'_submit'])){
            $policy_data['policy-enable']              = (bool)$_POST['txtEnable'];
            $policy_data['policy-display-position']    = $_POST['rbDisplayPosition'];
            $policy_data['policy-recommend']           = (bool)$_POST['txtRecommend'];
            $policy_data['policy-display-title']       = $_POST['txtDisplayTitle'];
            $policy_data['policy-opening-statement']   = $_POST['txtOpeningStatement'];
            $policy_data['policy-closing-statement']   = $_POST['txtClosingStatement'];
            $policy_data['policy-css']                 = ($_POST['txtPolicyCss']);
            $policy_data['policy-sort']                = $_POST['ItemListSort'];
            $txtListCheck = $_POST['txtListCheck'];
            $txtList = $_POST['txtList'];

            foreach($txtList as $key => $value){
                $policy_data['policy-list'][$key]['name']     = $value;
                if ( $txtListCheck != null){
                    $policy_data['policy-list'][$key]['checked']  = (array_key_exists($key,$txtListCheck))?True:False;
                }else{
                     $policy_data['policy-list'][$key]['checked'] = False;
                }
            }           
            update_option($this->page_name,$policy_data);  
            $msg = 'Your data was updated';
        }

        if (isset($_POST[$this->page_name.'_reset'])){
            $this->default_data();
            update_option($this->page_name,$policy_data);
            $msg = 'Your data was reseted';
        }
    }
    
    function metaboxInfo(){        
    ?>
        <table width="100%" border="0" cellspacing="4">
            <tr>
                <td width="80px" valign="top"><?php echo $this->info_type . ' title'; ?></td>
                <td valign="top">: <?php echo $this->info_data_title; ?></td>
            </tr>
            <tr>
                <td valign="top">Version</td>
                <td valign="top">: <?php echo $this->info_data_version; ?></td>
            </tr>
            <tr>
                <td valign="top">Author</td>
                <td valign="top">: <?php echo $this->info_data_author; ?></td>
            </tr>
            <tr>
                <td valign="top">Description</td>
                <td valign="top">: <?php echo $this->info_data_description; ?></td>
            </tr>
        </table> 
        <input type="submit" value="Save Setting" class="button-primary" name="<?php echo $this->page_name;?>_submit" /><input type="submit" value="Reset Setting" class="button-primary" name="<?php echo $this->page_name;?>_reset" onclick="return reset_confirmation()" />
    <?php        
    }

//callback & function ----------------------------------------------------------
    


    function default_data(){
        global $policy_data;

        $policy_data['policy-enable']              = (bool)True;
        $policy_data['policy-display-title']       = 'Read This Before Leaving a Comment';
        $policy_data['policy-display-position']    = 'fix';//fix or custom
        $policy_data['policy-recommend']           = True;
        $policy_data['policy-opening-statement']   = 'Please make sure your comments follow our guidelines:';
        $policy_data['policy-list']                = array(
                                                        0 =>
                                                        array(
                                                        name => 'Use your real name, not keywords',
                                                        checked => true
                                                        ),
                                                         1 =>
                                                        array(
                                                        name => 'No signature links in your comments',
                                                        checked => true
                                                        ),
                                                         2 =>
                                                        array(
                                                        name => 'No foul language (please)',
                                                        checked => true
                                                        ),
                                                         3 =>
                                                        array(
                                                        name => 'You can type directly here to change the text',
                                                        checked => false
                                                        ),
                                                        4 =>
                                                        array(
                                                        name => 'You can drag and drop to change my position ',
                                                        checked => false
                                                        )
                                                    );
        $policy_data['policy-sort']                = 'listItem[]=0&listItem[]=1&listItem[]=2&listItem[]=3&listItem[]=4';
        $policy_data['policy-closing-statement']   = 'Comments that do not adhere will be deleted or marked as SPAM.';
        $policy_data['policy-css']                 = '
#comment_policy_display {
    color:#40454B;
	font-size: 10pt;
	background-color: #f2f2f2;
	border: thin dotted #CCCCCC;
	font-family: Verdana, Arial, Helvetica, sans-serif;
	line-height: 12pt;
	margin: 0px;
	padding: 8px;
}
#comment_policy_display p {
	margin: 3px;
    font-size: 10pt;
    font-family: Verdana, Arial, Helvetica, sans-serif;
    line-height: 12pt;
    background-color: #f2f2f2;
    color:#40454B;
    font-weight:normal;
    padding:0px;
}
#comment_policy_display h3 {
	font-family: Trebuchet MS;
	font-size: 13pt;
	line-height: 13pt;
	color: #333333;
	font-weight: bold;
	padding: 3px 0px 3px 0px;
	margin: 0px 3px 3px 3px;
	border-bottom-width: thin;
	border-bottom-style: dotted;
	border-bottom-color: #CCCCCC;
    background:none;
}
#comment_policy_display ul{
    list-style-type:disc;
    padding:0px;
    padding-left:50px;
    font-size: 10pt;
    font-family: Verdana, Arial, Helvetica, sans-serif;
    font-weight:normal;
    color:#40454B;
}
#comment_policy_display ul li{
    list-style-type:disc;
    font-size: 10pt;
    font-family: Verdana, Arial, Helvetica, sans-serif;
    font-weight:normal;
    color:#40454B;
}
#comment_policy_credits {
	font-size: 7pt;
	color: #999999;
	line-height: 8pt;
    width:100%;
    height:10px;

}
#comment_policy_credits span{
    font-family: Verdana, Arial, Helvetica, sans-serif;
    color: #999999;
    font-weight:normal;
    float:right;
}
#comment_policy_credits a,#comment_policy_credits a:visited {
    font-family: Verdana, Arial, Helvetica, sans-serif;
	color: #999999;
    font-weight:normal;
    text-decoration:none;
}
';

    }
    function add_javascript(){
        $plugin_location = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
        if (function_exists('json_encode')){
        _e('<script type=\'text/javascript\' src=\'' . $plugin_location . 'ajaxupload.3.5.js\'></script>');
        _e('<script type="text/javascript">
            /* <![CDATA[ */
            jQuery(document).ready(function() {
                jQuery("#btnExport").click(function(){
                    jQuery.ajax({
                        url:\'admin-ajax.php?action=export_policy\',
                        type: \'post\',
                        dataType:\'html\',
                        success:function(html){
                            jQuery("#exportData").val(html);
                            jQuery("#exportSubmit").submit();
                        }
                    });
                });
                var button = jQuery(\'#btnImport\'), interval;
                new AjaxUpload(button,{
                    action: \'admin-ajax.php?action=import_policy\',
                    name: \'myfile\',
                    onSubmit : function(file, ext){
                        // change button text, when user selects file
                        button.text(\'Uploading\');

                        // If you want to allow uploading only 1 file at time,
                        // you can disable upload button
                        this.disable();

                        // Uploding -> Uploading. -> Uploading...
                        interval = window.setInterval(function(){
                            var text = button.text();
                            if (text.length < 13){
                                button.text(text + \'.\');
                            } else {
                                button.text(\'Uploading\');
                            }
                        }, 200);
                    },
                    onComplete: function(file, response){
                        button.text(\'Import policy\');
                        window.clearInterval(interval);
                        // enable upload button
                        this.enable();
                        if ("Comment Policy successful imported"==response){
                            alert(response + ". This page will be reload after you close this message");
                            window.location.reload();
                        }else{
                            alert(response);
                        }
                    }
                });
            });
            /* ]]> */
            </script>');
        }
    _e('<script type="text/javascript">
        /* <![CDATA[ */
        function reset_confirmation(){
            var confirm_answer = confirm("Do you really want to reset your Comments Policy into default value?.It will remove all your previous setting");
            if (confirm_answer== true){
                return true;
            }else{
                return false;
            }
        }
        jQuery(document).ready(function() {
            function update_order(){
                var order = jQuery("#sortable-list").sortable("serialize");
                jQuery("#ItemListSort").val(order);
            }
            jQuery("#sortable-list").sortable({
                handle : ".handlesort",
                update : function () {
                    update_order();
                }
            });
            jQuery(".delete_icon").click(function(){
                var answer = confirm("Do you really want to delete this data?")
                if (answer){
                    jQuery("li#"+jQuery(this).attr("id")).remove();
                    update_order();
                }
            });
            jQuery(".btnAddNewList").click(function(){
                var input = jQuery("#txtAddNewList").val();
                if (input != ""){
                    var itemSort = jQuery("#ItemListSort").val();
                    jQuery.ajax({
                        url:\'admin-ajax.php?action=get_max_value&array_name=listItem&\'+itemSort,
                        type: \'post\',
                        dataType:\'html\',
                        success:function(html){
                            jQuery("<li id=\'listItem_" + ( parseInt(html) + 1 ) + "\'><div class=\'handlesort\'><table width=\'100%\'><tr><td width=\'10\'><input type=\'checkbox\' name=\'txtListCheck[" + ( parseInt(html) + 1 ) + "]\' /></td><td><input type=\'text\' name=\'txtList[" + ( parseInt(html) + 1 ) + "]\' style=\'width:100%;\' value=\'" + input + "\'/></td><td width=\'10\'><input type=\'submit\' name=\'btnDelete\' onclick=\'return false\' id=\'listItem_" + ( parseInt(html) + 1 ) + "\'  value=\'X\' /></td></table></div></li>").appendTo("ul#sortable-list");
                            update_order();
                            jQuery(":submit#listItem_" + ( parseInt(html) + 1 )).addClass("delete_icon").click(function(){
                                var answer = confirm("Do you really want to delete this data?")
                                if (answer){
                                    jQuery("li#"+jQuery(this).attr("id")).remove();
                                    update_order();
                                }
                            });
                            jQuery("#txtAddNewList").val("");
                       }
                    });
                }else{
                    alert("No data to add");
                }
            });
            
        });
        /* ]]> */
        </script>');

    }
    function outside_form(){
         $plugin_location = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
    _e('
        <div style="display:none;">
        <form name="exportSubmit" method="post" id="exportSubmit" action="'.$plugin_location . 'export_response.php" >
            <input name="data" type="hidden" id="exportData" value=""/>
        </form>
        </div>
        ');
    }
    
    function add_style(){
    _e('<style type="text/css">
            .delete_icon:hover{
                font-weight: bold;
                cursor:pointer;
                color: #FF0000;
            }
            .handlesort{
                cursor:move;
                padding-left:20px;
                border-left:4px solid #DADADA;
            }           
            .handlesort input{
                background:transparent;
            }

            #sortable-list input{
                border:0px;
            }
        
        #sortable-list li {
            border:1px solid #DADADA;
            background-color:#EFEFEF;
            padding:3px 5px;
            margin-bottom:3px;
            margin-top:3px;
            width:450px;
            list-style-type:none;
            font-family:Arial, Helvetica, sans-serif;
            color:#666666;
            font-size:0.8em;
        }

        #sortable-list  li:hover {
            background-color:#FFF;
            cursor:move;
        }

        #how_to{
             list-style-type:decimal;
             margin-left:30px;
             list-style-position:inside;
             text-indent:-20px;
        }
        #how_to_recommend{
            margin-left:30px;
        }
        </style>');
    }
     
    function get_array_max_value(){
        if(isset($_GET['array_name'])){
            $array_name = $_GET['array_name'];
            $array_data = ((isset($_GET[$array_name]))?$_GET[$array_name]:array(0));
        }   
        echo  max($array_data);
        exit;
    }
    
    function export_comments_policy(){
        $data = json_encode(get_option('comments_policy_plugin'));
        echo $data;
        exit;
    }

    function import_comments_policy(){
        $tempdir = sys_get_temp_dir();
        $uploadfile = $tempdir . '/' . basename($_FILES['myfile']['name']);
        $ext = preg_replace('/^.*\.([^.]+)$/D', '$1', $uploadfile);
        if ( strtoupper($ext) == 'JSON'){
            if (move_uploaded_file($_FILES['myfile']['tmp_name'], $uploadfile)) {

                $fh             = fopen($uploadfile, 'r');
                $Data           = fread($fh, filesize($uploadfile));
                $policy_data    = json_decode($Data,true);
                if ( array_key_exists('policy-enable',(array)$policy_data )){
                    update_option('comments_policy_plugin',$policy_data) ;
                    echo "Comment Policy successful imported";
                }else{
                    echo "invalid json format";
                }
                fclose($fh);
                
            } else {
              echo "error on imported";
            }
        }else{
            echo 'invalid file format';
        }
        exit;
    }

    function policy_list(){
        global $policy_data;
        $plugin_location = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
        _e('<ul id="sortable-list">');
            parse_str($policy_data['policy-sort']);
            if ( count($listItem) > 0 ){
                foreach ($listItem as $key => $value ){
                    _e('<li id="listItem_'.$value.'" ><div class="handlesort"><table width="100%"><tr><td width="10"><input type="checkbox" name="txtListCheck['.$value.']" '.(($policy_data['policy-list'][$value]['checked'])?'checked="checked"':'') .' /></td><td><input type="text" name="txtList['.$value.']" style="width:100%;" value="'.$policy_data['policy-list'][$value]['name'].'" /></td><td width="10"><input type="submit" name="btnDelete" onclick="return false" id="listItem_'.$value.'" class="delete_icon" value="X" /></td></table></div></li>');
                }
            }
        _e('</ul>');
        
    }
    
    function comment_policy() {
        global $policy_data;
    _e('<table style="width:98%">
        <tr>
            <td valign="top" style="width:150px">
                <strong>Enable</strong>
            </td>
            <td>
                <input type="checkbox" name="txtEnable" value="True" '.(($policy_data['policy-enable'])?'checked="checked"':'').' />
            </td>
        </tr>
        <tr>
            <td valign="top" style="width:150px"></td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td valign="top" style="width:150px">
                <strong>Display On</strong>
            </td>
            <td>
                <input name="rbDisplayPosition" type="radio"  value="fix" '.(($policy_data['policy-display-position']=='fix')?'checked="checked"':'').' /> Automatically display on the bottom of my comment entry form.
            </td>
        </tr>
        <tr>
            <td valign="top" style="width:150px">
                <strong></strong>
            </td>
            <td>
                <input name="rbDisplayPosition" type="radio"  value="custom" '.(($policy_data['policy-display-position']=='custom')?'checked="checked"':'').' /> I\'ll manually insert the code <b>&lt;?php comments_policy(); ?&gt;</b> to where I want it to appear.
            </td>
        </tr>
        <tr>
            <td valign="top" style="width:150px"></td>
            <td>&nbsp;</td>
        </tr>        
        <tr>
            <td valign="top" style="width:150px"></td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td valign="top" style="width:150px">
                <strong>Policy display title</strong>
            </td>
            <td>
                <input type="text" name="txtDisplayTitle" style="width:100%;" value="'.$policy_data['policy-display-title'].'"  />
            </td>
        </tr>
        <tr>
            <td valign="top">
                <strong>Opening statement</strong>
            </td>
            <td>
                <textarea name="txtOpeningStatement" style="width:100%;height:100px;" >'.$policy_data['policy-opening-statement'].'</textarea>
            </td>
       </tr>
       </table>');
    _e('<p>Create, rename and rearrange your individual policy items below. You can temporarily deactivate individual items, or delete them permanently</p>');
    _e('<br/>');
    _e('<br/>');
    _e('<table style="width:98%">
        <tr>
            <td valign="top" style="width:150px"></td>
            <td>
                <div id="policy_list">');
                    $this->policy_list();
                _e('<input type="hidden" name="ItemListSort" id="ItemListSort" value="'.$policy_data['policy-sort'].'"/>
                </div>
            </td>
        </tr>
        <tr>
            <td valign="top" style="width:150px"></td>
            <td><input type="text" name="txtAddNewList" id="txtAddNewList" value="" style="width:250px" /><input type="submit" class="button rbutton btnAddNewList" onclick="return false" value="Add More >>" /></td>
        </tr>
       </table>');
      _e('<table style="width:98%">
        <tr>
            <td valign="top" style="width:150px">
                <strong>Closing statement</strong>
            </td>
            <td>
                <textarea name="txtClosingStatement" style="width:100%;height:100px;" >'.$policy_data['policy-closing-statement'].'</textarea>
            </td>
        </tr>       
       </table>');
     
	}

    function import_export(){
        if (function_exists('json_encode')){
        _e('<table width="100%" border="0" cellspacing="4">
            <tr>
                <td valign="top" >
                Import your comment policy file.
                </td>
            <tr>
                <td valign="top" >
               <div id="btnImport" class="button rbutton" style="width:100px;">Import policy</div>
                <hr/>
                </td>
            </tr>
            <tr>
                <td valign="top" >
                You can export your current comment policy and save into your hard disk.
                </td>
            </tr>
            <tr>
                <td valign="top" ><input type="submit" class="button rbutton" id="btnExport" onclick="return false;" value="Export Existing Policy" />
                </td>
            </tr>
            </table>');
        }else{
            _e('Sorry, this function require at lease PHP 5.2.0 and higher, or PECL json 1.2.0 and higher<br/><br/>You are currently using  PHP ' .phpversion() );
        }

    }
   
	function custom_css() {
        global $policy_data;
	_e('<table style="width:98%">
        <tr>
            <td valign="top" style="width:150px"><strong>Custom CSS</strong></td>
            <td><textarea name="txtPolicyCss" style="width:100%;height:200px;" >'.$policy_data['policy-css'].'</textarea></td>
        </tr>       
       </table>');
	}

    function how_to(){
        global $policy_data;
        //$policy_data = get_option($this->page_name);
       // var_dump($policy_data);

    _e('<table style="width:98%">
        <tr>
            <td>
            <ol id="how_to">            
            <li>Create your own custom Comments Policy based on the default</li>
            <li>You can choose to display your policy automatically or manually insert the code <b>&lt;?php comments_policy(); ?&gt;</b> inside your theme.</li>
            <li>You can customize the appearance of the comments policy by editing the Custom CSS box.</li>
			<li>You can export your comments policy and use them on your other blogs too!</li>
            <li>For more information visit <a href="http://www.profitplugs.com/comments-policy-wordpress/" target="_blank">Comments Policy Plugin Homepage</a></li>
            </ol>
            </td>
        </tr>
        <tr>           
            <td>
                <div id="how_to_recommend">
                <input type="checkbox" name="txtRecommend"  onclick="if(this.checked){document.getElementById(\'donation\').style.display = \'none\';}else{document.getElementById(\'donation\').style.display = \'block\';}" value="True" '.(($policy_data['policy-recommend'])?'checked="checked"':'').' /> Recommend this plugin on your blog');
                if($policy_data['policy-recommend']){
                    $display_css = 'display:none;';
                }else{
                    $display_css = 'display:block;';
                }

              _e('<div id="donation" style="' . $display_css . '" ><em><p>Do consider <a href="http://www.profitplugs.com/donate/" target="_blank" >making a donation for this plugin</a>. As a donor we\'ll list your website on ours and send you some prime time traffic. You can also <a href="http://twitter.com/home/?status=RT@profitplugs.com Get a free WordPress comments policy plugin at http://www.profitplugs.com/" target="_blank" >Tweet this plugin on Twitter</a></em></p></div>
              </div>
            </td>
        </tr>
        </table>');
	}  

}

$comments_policy = new comments_policy();
//------------------------------------------------------------------------------


function comments_policy() {
    do_action('comments_policy');
}

$policy_data = get_option($comments_policy->page_name);
//var_dump($policy_data);
if ( $policy_data['policy-display-position']=='fix' ){
    add_action('comment_form','display_policy');
}else{
    add_action('comments_policy','display_policy');
}

add_action('wp_head','display_policy_css',100);
function display_policy(){

    $policy_data = get_option('comments_policy_plugin');
    if ($policy_data['policy-enable']){
        if ( is_single() || is_page()){
    
    _e('<div id="comment_policy_display">
        <h3>'.$policy_data['policy-display-title'].'</h3>
        <p>
        '.$policy_data['policy-opening-statement']. '</p>');
        parse_str($policy_data['policy-sort']);
        if ( count($listItem) > 0 ){
            _e('<ul>');
            foreach ($listItem as $key => $value ){
                if ( $policy_data['policy-list'][$value]['checked'] ){
                _e('<li>'.$policy_data['policy-list'][$value]['name'].'</li>');
                }
            }
            _e('</ul>');
        }
                    
    _e('<p>' . $policy_data['policy-closing-statement'].'</p>');
    if($policy_data['policy-recommend']){
    _e('
        <div id="comment_policy_credits">
        <span >by <a href="http://www.profitplugs.com/comments-policy-wordpress/" target="_blank">Comments Policy for WordPress</a></span>
        </div>');
    }
    _e('</div>&nbsp;');
            }
     }
}

function display_policy_css(){
    $policy_data = get_option('comments_policy_plugin');
    if ($policy_data['policy-enable']){
    _e('<style type="text/css">'.$policy_data['policy-css'].'</style>');
    }
}

?>
