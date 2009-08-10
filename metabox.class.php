<?php
/* Metabox Class
 * version 1.1
 * -add outside_form() hook . to be use outside the form.
 * Version 1.0
 * -initial
 */
if (!function_exists ('add_action')) {
		header('Status: 403 Forbidden');
		header('HTTP/1.1 403 Forbidden');
		exit();
}

//class that reperesent the complete plugin
class metabox {
    var $page_name;
    var $title;
    var $pagehook;
    var $info_title  = 'Theme Info';
    var $info_type   = 'Theme';//or Plugin

    var $info_data_title;
    var $info_data_version;
    var $info_data_author;
    var $info_data_description;

	//constructor of class, PHP4 compatible construction for backward compatibility
	function metabox($page_name) {
        $this->page_name = $page_name;
		//add filter for WordPress 2.8 changed backend box system !
		add_filter('screen_layout_columns', array(&$this, 'on_screen_layout_columns'), 10, 2);
		//register callback for admin menu  setup
		add_action('admin_menu', array(&$this, 'on_admin_menu')); 
		//register the callback been used if options of page been submitted and needs to be processed
		add_action('admin_post_save_' . $this->page_name , array(&$this, 'on_save_changes'));
        //howto_metaboxes_general
	}
	
	//for WordPress 2.8 we have to tell, that we support 2 columns !
	function on_screen_layout_columns($columns, $screen) {
		if ($screen == $this->pagehook) {
			$columns[$this->pagehook] = 2;
		}
		return $columns;
	}
	
	//extend the admin menu
	function on_admin_menu() {
		//add our own option page, you can also add it to different sections or use your own one
		//$this->pagehook = add_options_page('Howto Metabox Page Title', "HowTo Metaboxes", 'manage_options', 'hahaha', array(&$this, 'on_show_page'));
		//register  callback gets call prior your own page gets rendered
        $this->on_admin_menu_hook();
		add_action('load-'.$this->pagehook, array(&$this, 'on_load_page'));     
	}


	//will be executed if wordpress core detects this page has to be rendered
	function on_load_page() {
		//ensure, that the needed javascripts been loaded to allow drag/drop, expand/collapse and hide/show of boxes
		wp_enqueue_script('common');
		wp_enqueue_script('wp-lists');
		wp_enqueue_script('postbox');
        $this->on_load_page_hook();
	}
	
	//executed to show the plugins complete admin page
	function on_show_page() {
		//we need the global screen column value to beable to have a sidebar in WordPress 2.8
		global $screen_layout_columns;
		//add a 3rd content box now for demonstration purpose, boxes added at start of page rendering can't be switched on/off, 
		//may be needed to ensure that a special box is always available
		add_meta_box($this->page_name . '_info', $this->info_title , array(&$this, 'metaboxInfo'), $this->pagehook, 'side', 'core');
		//define some data can be given to each metabox during rendering
	
		?>
		<div id="<?php echo $this->page_name; ?>" class="wrap">
		<?php screen_icon('options-general'); ?>
		<h2><?php echo $this->title; ?></h2>
        <div class="bordertitle"></div>
        <br/>
        <?php if (isset($_GET['msg']) && ($_GET['msg']!='')){ echo "<div class='updated'><p><strong>" . $_GET['msg'] . "</strong></p></div>" ;} ?>
		<form action="admin-post.php" method="post">
			<?php wp_nonce_field( $this->page_name ); ?>
			<?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
			<?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false ); ?>
			<input type="hidden" name="action" value="save_<?php echo $this->page_name; ?>" />
		
			<div id="poststuff" class="metabox-holder<?php echo 2 == $screen_layout_columns ? ' has-right-sidebar' : ''; ?>">
				<div id="side-info-column" class="inner-sidebar">
					<?php do_meta_boxes($this->pagehook, 'side', $data); ?>
				</div>
				<div id="post-body" class="has-sidebar">
					<div id="post-body-content" class="has-sidebar-content">
						<?php do_meta_boxes($this->pagehook, 'normal', $data); ?>						
						<?php do_meta_boxes($this->pagehook, 'additional', $data); ?>						
					</div>
				</div>
				<br class="clear"/>								
			</div>	
		</form>
        <?php $this->outside_form(); ?>
		</div>
	<script type="text/javascript">
		//<![CDATA[
		jQuery(document).ready( function($) {
			// close postboxes that should be closed
			$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
			// postboxes setup
			postboxes.add_postbox_toggles('<?php echo $this->pagehook; ?>');
		});
		//]]>
	</script>
		
		<?php
	}

	//executed if the post arrives initiated by pressing the submit button of form
	function on_save_changes() {
        global $msg;
		//user permission check
		if ( !current_user_can('manage_options') )
			wp_die( __('Cheatin&#8217; uh?') );			
		//cross check the given referer
		check_admin_referer($this->page_name);
		
		$this->on_save_changes_hook();
		
		//lets redirect the post request into get request (you may add additional params at the url, if you need to show save results
		//wp_redirect($_POST['_wp_http_referer']);
        $msg = urlencode($msg);
        $params = array( 'msg' => $msg );
        wp_redirect(add_query_arg( $params, $_POST['_wp_http_referer'] ));
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
        <input type="submit" value="Save Setting" class="button-primary" name="<?php echo $this->page_name;?>_submit" />

    <?php
    }

    function on_admin_menu_hook(){}
	function on_load_page_hook(){}
    function on_save_changes_hook(){}
    function outside_form(){}
	
}

?>