<?php
/*
Plugin Name: VideoNab
Plugin URI: http://www.videonab.com
Description: Need more features like topic-based video streaming and video ranking?  <a href="http://www.videonab.com" target="_blank">Get the Pro Version</a>
Version: 1.4.1
Author: Josh Kremer & Josh Sears
*/

// define plugin variables
define('VHub_Prefix', 'crbh_' );
define('VHub_LangPrefix', 'videonab' );

// Init the plugin class
add_action('init',  array('VHub_Main', 'init'));

// include Google API v3
require_once( VHub_Main::get_plugin_path('lib/plugin-classes/google-api.php') );

// include Video Functions
require_once( VHub_Main::get_plugin_path('lib/plugin-classes/video.php') );

// include Video Functions
require_once( VHub_Main::get_plugin_path('lib/plugin-classes/video.php') );

// include Date Range
require_once( VHub_Main::get_plugin_path('lib/plugin-classes/date_range.php') );

// include custom prev next links
require_once( VHub_Main::get_plugin_path('lib/plugin-classes/prev_next_links.php') );

//Flushes Rewrite(permalink) Rules uppon Activation
function vhub_pages_rewrite_flush() {
	VHub_Main::register_post_types();

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'vhub_pages_rewrite_flush' );

/**
*	Define main plugin class
*/
class VHub_Main {

	/**
	*	Define class variables
	*/
	protected 	$prefix = VHub_Prefix,
				$plugin_name = 'VideoNab',
				$cache_buster = '0.4',
				$post_type,
				$tax_category,
				$plugin_url,
				$plugin_path,
				$filter_date_range = array(
						'order_type' => '',
						'date_filter' => '',
						'date_filter_fixed' => '',
						'date_filter_before' => '',
						'date_filter_after' => '',
						'date_filter_last_days' => 5,
						'youtube_time_start' => 'T00:00:01.000Z',
						'youtube_time_end' => 'T23:59:59.000Z'
					);

	function __construct(){
		$this->set_plugin_path();
		$this->set_plugin_url();

		// Block Video
		if ( isset($_GET['block_video']) && is_numeric($_GET['block_video']) && current_user_can('administrator') && ($post_exists = get_post($_GET['block_video'])) ) {
			update_post_meta( $post_exists->ID, '_block_video', 'Yes' );
		}

		// Set translations folder
		load_theme_textdomain(VHub_LangPrefix, $this->get_plugin_path('languages'));

		$this->post_type 		= $this->prefix . 'videos';
		$this->tax_category 	= $this->prefix . 'video_cats';
		$this->tax_tag 			= $this->prefix . 'video_tags';

		$this->register_post_types(); 												// initialize plugin post types

		$this->register_taxonomies();	 											// initialize plugin taxonomies

		$this->enqueue_scripts();													// include plugin javascripts

		$this->enqueue_styles();													// include plugin styles

		// carbon framework
		$this->carbom_framework_init(); 											// initialize carbon framework
		$this->carbon_framework_meta_fields(); 										// set carbon framework meta fields
		$this->carbon_framework_options_page(); 									// set carbon options page

		$this->carbon_setup_editor_buttons();										// add additional shortcode button to the editor

		// admin hooks
		if (is_admin()) {
			add_action( 'admin_head', array($this, 'admin_head') );						// hook to admin_head
			add_action( 'admin_url', array($this, 'admin_url'), 1, 3 );					// modify the "Add New" link
			add_action(	'admin_menu', array($this, 'admin_menu'));						// modify admin menu

			add_action( 'admin_init' , array($this, 'admin_columns') ); 				// Admin columns
		}else{
			// crb_wp_head hooks to wp_head
			add_action('wp_head', array($this, 'wp_head_before_jquery'), 8);
			add_action('wp_head', array($this, 'wp_head_after_jquery'), 15);

			add_filter('the_content', array($this, 'the_content'), 99);

			add_action('template_redirect', array($this, 'template_redirect'));
		}

		// [videonab]
		add_shortcode( 'videonab', array($this, 'shortcode') );						// add shortcode support

		add_action('admin_notices', array($this, 'plugin_admin_notices'));

		add_action('pre_get_posts', array($this, 'pre_get_posts'));

		$this->set_fitler_date_range();

		return $this;
	}

	public static function init(){
		return new self();
	}

	/**
	*	Set/Get plugin path
	*/
	protected function set_plugin_path(){
		$this->plugin_path = plugin_dir_path(__FILE__);
		return $this;
	}

	/**
	*	Returns plugin file path to specific file. Might be used with require/include functions.
	*	@param string $src
	*	@return string
	*/
	public static function get_plugin_path( $src = '' ){
		$path = (isset($this) ? $this->plugin_path : plugin_dir_path(__FILE__)) . $src;
		return preg_replace('~[\\/]~', DIRECTORY_SEPARATOR, $path);
	}

	/**
	*	Set/Get plugin URL
	*/
	protected function set_plugin_url(){
		$this->plugin_url = plugin_dir_url( __FILE__ );
		return $this;
	}

	/**
	*	Returns current plugin url. http://....
	*	@param string $ext , ex: "css/style.css"
	*	@return string
	*/
	public function get_plugin_url($ext = ''){
		return (isset($this) ? $this->plugin_url : plugin_dir_url( __FILE__ )) . $ext;
	}

	/**
	*	Returns simple array with Yes and No options
	*	@param boolean $reverse
	*	@return array
	*/
	public function yes_no($reverse = false){
		$array = array('Yes'=>'Yes','No'=>'No');
		return $reverse ? array_reverse($array) : $array;
	}

	/**
	*	Checks if the current page is using SSL and if so then returns true otherwise returns false
	*	@return boolean
	*/
	public function is_ssl(){
		return isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on";
	}

	/**
	*	Returns videos per page value
	*	@return string , numeric
	*/
	public function videos_per_page(){
		return get_option( $this->prefix . 'video_per_page' );
	}

	/**
	*	Returns video page ID or page link
	*	@param boolean $id
	*	@return string
	*/
	public function get_video_page($id = false){
		$page_id = get_option($this->prefix . 'video_page');
		return $id ? $page_id : get_permalink($page_id);
	}

	/**
	*	Returns an array with all entries from a specific post type
	*	@param string post_type
	*	@param array $new_args
	*	@return array
	*/
	protected function get_posts_array($post_type = null, $new_args = array() ){
		$return = array();
		$return[] = 'Please Select';

		if ($post_type) {
			$args = array(
				'posts_per_page'	=>	-1,
				'post_type'			=>	$post_type
			);

			$posts = get_posts( array_merge($args, $new_args) );
			foreach ((array) $posts as $p) {
				$return[$p->ID] = $p->post_title;
			}
		}
		return $return;
	}

	/**
	*	Add custom javascript
	*/
	protected function enqueue_scripts(){
		global $pagenow;
		if (!is_admin() && $pagenow != 'wp-login.php') {
			// required for youtube videos
			wp_enqueue_script('swfobject');

			// add sharethisp js plugin
			// //w.sharethis.com/button/buttons.js
			wp_enqueue_script($this->prefix . 'sharethis', $this->get_plugin_url('js/share-this-buttons.js') );

			// --
			wp_enqueue_script($this->prefix . 'functions', $this->get_plugin_url('js/functions.js'), array(
					'jquery'
				), $this->cache_buster );
		} else if ( is_admin() ) {
			wp_enqueue_script($this->prefix . 'functions', $this->get_plugin_url('js/backend.js') );
		}
	}

	/**
	*	Add custom css
	*/
	protected function enqueue_styles(){
		if (is_admin()) {
			wp_enqueue_style($this->prefix . 'backend-css', 	$this->get_plugin_url('css/backend-style.css'), 	array(), $this->cache_buster );
		}else{
			wp_enqueue_style($this->prefix . 'frontend-css', 	$this->get_plugin_url('css/frontend-style.css'), 	array(), $this->cache_buster );
		}
	}

	public function wp_head_before_jquery(){ // priority 8
		$return = '';

		?>
		<script type="text/javascript">
			window.vhub_website_url = "<?php echo home_url('/') ?>";
		</script>
		<?php

		echo $return;
	}

	public function wp_head_after_jquery(){  // priority 15
		?>
		<script type="text/javascript">stLight.options({publisher: "e7bad47c-3995-41d2-a8e2-736790e780af", doNotHash: false, doNotCopy: false, hashAddressBar: false});</script>

		<script>(function(d, s, id) {
		  var js, fjs = d.getElementsByTagName(s)[0];
		  if (d.getElementById(id)) return;
		  js = d.createElement(s); js.id = id;
		  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1<?php echo ( $facebook_app_id = get_option($this->prefix . 'facebook_app_id') ) ? '&appId=' . $facebook_app_id . '&version=v2.0;' : ''; ?>";
		  fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));</script>
		<?php
	}

	/**
	*	Modify the video details page content
	*
	*	@return string
	*/
	public function the_content( $content ){

		remove_action('the_content', array($this, 'the_content'));
		if (is_singular( $this->post_type )) {
			ob_start();
				include( $this->get_plugin_path('includes/details-view.php') );
			$content = ob_get_clean();
		}

		return $content;
	}

	/**
	*	Modify the "Add New" link
	*   Hook
	*/
	public function admin_url($url, $path, $blog_id){
		if ($url == home_url('/wp-admin/post-new.php?post_type=' . $this->post_type)) {
			$url = home_url('/wp-admin/edit.php?post_type=' . $this->post_type . '&page=add-new-video-replay');
		}

		return $url;
	}

	/**
	*	Modify the "Add New" link
	*   Hook
	*/
	public function admin_menu(){
		// remove default "add new video page"
		remove_submenu_page('edit.php?post_type=' . $this->post_type, 'post-new.php?post_type=' . $this->post_type);


		// custom options page
		// for add new videos functionality
		$this->options_pages(); 					// set options page
	}

	/**
	*	Admin Head
	*   Hook
	*/
	public function admin_head(){
		global $menu, $submenu;
		// in progress
	}


	/**
	*	Register plugin post types
	*/
	public function register_post_types(){
		register_post_type( VHub_Prefix . 'videos' , array(
				'labels' => array(
					'name'	 				=> __('VideoNab', VHub_LangPrefix),
					'singular_name' 		=> __('Video', VHub_LangPrefix),
					'add_new' 				=> __( 'Add New Video', VHub_LangPrefix ),
					'add_new_item' 			=> __( 'Add New Video', VHub_LangPrefix ),
					'view_item' 			=> __('View Video'. VHub_LangPrefix),
					'edit_item' 			=> __('Edit Video', VHub_LangPrefix),
					'new_item' 				=> __('New Video', VHub_LangPrefix),
					'view_item' 			=> __('View Video', VHub_LangPrefix),
					'search_items' 			=> __('Search Videos', VHub_LangPrefix),
					'not_found' 			=> __('No Videos found', VHub_LangPrefix),
					'not_found_in_trash' 	=> __('No Videos found in Trash', VHub_LangPrefix),
				),
				'menu_icon'				=> self::get_plugin_url('css/images/menu-icon-small.png'),
				'public' 				=> true,
				'exclude_from_search' 	=> true,
				'show_ui' 				=> true,
				'capability_type' 		=> 'post',
				'hierarchical' 			=> false,
				'_edit_link' 			=> 'post.php?post=%d',
				'rewrite' => array(
					"slug" => "videonab",
					"with_front" => false,
				),
				'query_var' 			=> true,
				'supports' 				=> array('title', 'editor'),
			));
	}

	/**
	*	Register plugin Taxonomies
	*/
	protected function register_taxonomies(){
		$taxonomies = array(
			array(
				'post_type' 	=> $this->post_type,
				'name' 			=> $this->tax_category,
				'menu_label' 	=> __('Video Categories', VHub_LangPrefix),
				'singular' 		=> __('Category', VHub_LangPrefix),
				'plural' 		=> __('Categories', VHub_LangPrefix),
			),
			/*
			array(
				'post_type' 	=> $this->post_type,
				'name' 			=> $this->tax_tag,
				'menu_label' 	=> 'Video Tags',
				'singular' 		=> 'Tag',
				'plural' 		=> 'Tags',
			)
			*/
		);

		foreach ($taxonomies as $tax) {
			$args = array(
				'hierarchical'        	=> true,
				'labels'              	=> $this->taxonomy_labels( $tax['menu_label'], $tax['singular'], $tax['plural'] ),
				'show_ui'             	=> true,
				'show_admin_column'   	=> true,
				'query_var'           	=> true,
				'rewrite'             	=> false
			);
			register_taxonomy( $tax['name'], array( $tax['post_type'] ), $args );
		}
	}

	protected function taxonomy_labels( $menu_label = 'Categories', $singular = 'Category', $plural = 'Categories' ){
		return array(
			'name'                => _x( $plural, 'taxonomy general name', VHub_LangPrefix ),
			'singular_name'       => _x( $singular, 'taxonomy singular name', VHub_LangPrefix ),
			'search_items'        => __( 'Search '.$plural, VHub_LangPrefix ),
			'all_items'           => __( 'All '.$plural, VHub_LangPrefix ),
			'parent_item'         => __( 'Parent '.$singular, VHub_LangPrefix ),
			'parent_item_colon'   => __( 'Parent '.$singular.':', VHub_LangPrefix ),
			'edit_item'           => __( 'Edit '.$singular, VHub_LangPrefix ),
			'update_item'         => __( 'Update '.$singular, VHub_LangPrefix ),
			'add_new_item'        => __( 'Add New '.$singular, VHub_LangPrefix ),
			'new_item_name'       => __( 'New '.$singular.' Name', VHub_LangPrefix ),
			'menu_name'           => __( $menu_label, VHub_LangPrefix )
		);
	}

	/**
	*	Add options pages
	*/
	protected function options_pages(){

		// Options page for adding new videos
		add_submenu_page(
			'edit.php?post_type=' . $this->post_type,		// parent slug
			'Add New Video',								// page title
			'Add New Video',								// menu title
			'publish_posts', 								// capability
			'add-new-video-replay',							// page slug
			array($this, 'page_add_new_videos')				// callback function
		);

		// Options page for wiping all videos
		add_submenu_page(
			'edit.php?post_type=' . $this->post_type,		// parent slug
			'Video Removal',								// page title
			'Video Removal',								// menu title
			'publish_posts', 								// capability
			'video-removal',								// page slug
			array($this, 'page_video_removal')				// callback function
		);

		// Options page for updating all videos
		add_submenu_page(
			'edit.php?post_type=' . $this->post_type,		// parent slug
			'Video Updater',								// page title
			'Video Updater',								// menu title
			'publish_posts', 								// capability
			'video-updater',								// page slug
			array($this, 'page_video_updater')				// callback function
		);
	}

	// add new videos page content
	public function page_add_new_videos(){
		include( $this->get_plugin_path('includes/admin-add-new-video.php') );
	}

	// video removal
	public function page_video_removal(){
		include( $this->get_plugin_path('includes/admin-video-removal.php') );
	}

	// video updater
	public function page_video_updater(){
		include( $this->get_plugin_path('includes/admin-video-updater.php') );
	}


	/**
	*	Check if Carbon Framework is available
	*
	*	@return boolean
	*/
	protected function carbon_framework_exist(){
		return class_exists('Carbon_Container');
	}

	/**
	*	Load Carbon Framework
	*/
	protected function carbom_framework_init(){
		if ( !$this->carbon_framework_exist() ) {
			define('CRB_THEME_DIR', $this->get_plugin_path() );
			if (!defined('CARBON_PLUGIN_URL')) {
				define('CARBON_PLUGIN_URL', $this->get_plugin_url('lib/carbon-fields') );
			}else{
				${CARBON_PLUGIN_URL} = $this->get_plugin_url('lib/carbon-fields');
			}
			include_once( $this->get_plugin_path('lib/carbon-fields/carbon-fields.php') );
		}

		if ( $this->carbon_framework_exist() ) {
			include_once( $this->get_plugin_path('lib/carbon-fields-extended/Carbon_Field.php') );
		}
	}

	/**
	* 	Carbon Meta Fields
	*	Set post type meta fields
	*/
	protected function carbon_framework_meta_fields(){
		if ( $this->carbon_framework_exist() ){
			Carbon_Container::factory('custom_fields', __('VideoNab Options Panel', VHub_LangPrefix))
				->show_on_post_type( $this->post_type )
				->add_fields(array(
					Carbon_Field::factory('select', 		'block_video', 	__('Block Video', VHub_LangPrefix) )
						->add_options( $this->yes_no(true) ),
					Carbon_Field::factory('html', 			'video_summary')
						->set_html( $this->build_video_summary() ),
				));
		}
	}

	/**
	* 	Carbon Options Page
	*	Set post type meta fields
	*/
	protected function carbon_framework_options_page(){
		if ( $this->carbon_framework_exist() ){
			$available_templates = array_merge( array(
				'default' => 'Default Post Template',
				'page.php' => 'Default Page Template'
			), (array) wp_get_theme()->get_page_templates() );

			Carbon_Container::factory('theme_options', 'Settings')
				->set_page_parent('edit.php?post_type=crbh_videos')
				->add_fields(array(
					Carbon_Field::factory('html', $this->prefix . 'scodeinfo')
						->set_html('
							<div class="crbh-info-text-one" >
								<p><strong>' . __( 'Shortcode: [videonab]', VHub_LangPrefix) . '</strong></p>
								<p><em>' .  __( 'To display our video feed, simply enter dtails below, then paste the above shortcode into your page or post.', VHub_LangPrefix ) . '</em></p>
							</div>
						'),

					Carbon_Field::factory('separator', $this->prefix . 'sep0', __('Display Options', VHub_LangPrefix)),
					Carbon_Field::factory('Select_Extended_VH', $this->prefix . 'newest', __('View Results', VHub_LangPrefix))
						->tooltip(
								__( '<p><strong>YouTube Publish Date: When “New” toggle is selected (default view)</strong> Displays videos by the date added to YouTube: Fore example, even if you manually add a video today, but it was posted on YouTube in 2008, it will be placed in that order with other videos. Aggregated videos will also display in chronological order based on when they were added to YouTube. Great for presenting time-senstive videos, like tech announcements, movie trailers, moments in history, etc.</p>', VHub_LangPrefix ) .
								__( '<p><strong>By Date Added to Site:</strong> Displays videos  as they have been added to the site: For example, if you add a video today, it will show up with today’s time stamp regardless of when it was originally posted to YouTube. Aggregated videos will display by the date they were pulled from YouTube and placed on your site. This is ideal for sites preseting tutorials, or more random topics that don’t rely on specific dates, like cat videos. Additionally it allows you to force videos to the top.</p>', VHub_LangPrefix )
							)
						->add_options( array(
								'added_to_video_service' => 'Youtube Publish Date',
								'added_to_website' => 'By Date Added to Site',
							) ),
					Carbon_Field::factory('text', $this->prefix . 'video_per_page', __('Number of videos per page', VHub_LangPrefix))
						->set_default_value('10'),
					Carbon_Field::factory('select', $this->prefix . 'include_pagination', __('Include Pagination', VHub_LangPrefix))
						->add_options( $this->yes_no() ),
					Carbon_Field::factory('select', $this->prefix . 'enable_comments', __('Enable Comments', VHub_LangPrefix))
						->add_options( $this->yes_no() ),
					Carbon_Field::factory('select', $this->prefix . 'video_page', __('Choose Main Video Page', VHub_LangPrefix))
						->add_options( $this->get_posts_array('page') ),
					Carbon_Field::factory('select', $this->prefix . 'video_details_page', __('Choose Video Details Page Template', VHub_LangPrefix))
						->add_options( $available_templates ),


					/*
					Video Filter options goes here
					*/
					Carbon_Field::factory('html', $this->prefix . 'sepline_one')
						->set_html('<span class="crbh-sepline"></span>'),
					Carbon_Field::factory('separator', $this->prefix . 'sep3', __('Video Date Filters', VHub_LangPrefix)),
					Carbon_Field::factory('Select_Extended_VH', $this->prefix . 'enable_listing_filter', __('Filter Video Library by Date', VHub_LangPrefix))
						->tooltip(
								__( '<p>When set to yes, this option will filter videos contained in your library (both on the video feed and backend library) independently of the aggregation process. This comes in handy when you have a large library of videos and want to showcase only those in a specific date range. For example, many topics won’t require the Event Scheduler, and only need videos to be pulled once. This will allow you to assign a date range to this type of collection.</p>', VHub_LangPrefix )
							)
						->add_options( $this->yes_no() ),
					Carbon_Field::factory('select', $this->prefix . 'date_filter', __('Videos Date Filter', VHub_LangPrefix))
						->add_options( array(
								'any_date' => 'Videos from any date',
								'within_last_days' => 'Videos within the last x # of days',
								'specific_date' => 'Videos on specific date',
								'after_specific_date' => 'Videos after specific date',
								'before_specific_date' => 'Videos before specific date',
								'between_specific_date' => 'Videos in specific date range'
							) ),
					Carbon_Field::factory('text', $this->prefix . 'date_filter_last_days', __('Number of days including today', VHub_LangPrefix))
						->set_default_value( 5 )
						->help_text('Must be numeric.'),
					Carbon_Field::factory('date', $this->prefix . 'date_filter_fixed', __('Date', VHub_LangPrefix)),
					Carbon_Field::factory('date', $this->prefix . 'date_filter_before', __('Date Before', VHub_LangPrefix)),
					Carbon_Field::factory('date', $this->prefix . 'date_filter_after', __('Date After', VHub_LangPrefix)),

					/*
					Cron Jobs options goes here
					*/
					Carbon_Field::factory('html', $this->prefix . 'cron_grab_now', '&nbsp;')
						->set_html('
							<div class="crbh-cron_grab_now carbon-container" >
								<p class="submit">
									<input type="submit" name="submit" id="submit" class="button-primary" value="Save Changes">&nbsp;&nbsp;&nbsp;
								</p>
							</div>
						'),

					Carbon_Field::factory('separator', $this->prefix . 'sep5', __('Google Settings', VHub_LangPrefix)),
					Carbon_Field::factory('html', $this->prefix . 'ginfo')
						->set_html('
							<div class="crbh-info-text-two" >
								<p><strong>Google API requires a Google Developer Key for retrieving the video information.</strong></p>
								<p><strong>To retrieve the Google Developer Key, please follow the instuctions below:</strong></p>
								<ol>
									<li>Go to <a href="https://console.developers.google.com/project" target="_blank">Google API Console</a> Select you project OR Create your project.</li>
									<li>Click on the "Create Project" and enter a projects name and create it.</li>
									<li>If the project data doesn\'t automatically load, click on the project name.</li>
									<li>To enable the YouTube Data Api
										<ol>
											<li>Click on the "API\'s & Auth" left menu link.</li>
											<li>Click on the "YouTube Data API" link and "Enable" it.</li>
										</ol>
									</li>
									<li>To create a "Google Developer Key"
										<ol>
											<li>Click on the "API\'s & Auth" left menu link.</li>
											<li>Click on the "Credentials" link.</li>
											<li>Click on the "Create New Key" button in the "Public API access" section.</li>
											<li>Click on the "Browser Key" button and then "Create" leaving the textarea field empty.</li>
											<li>Copy the "API key" value and paste it to the input field below.</li>
										</ol>
									</li>
								</ol>
							</div>
						'),
					Carbon_Field::factory('text', $this->prefix . 'google_developer_key', __('Google Developer Key', VHub_LangPrefix))
						->help_text('Required. ex: xxxxxxxxxxxxxxxxxxxxxx2VF8Wwn3WSaKqE2vk'),
				));
		}
	}

	public function template_redirect() {
		if ( !is_singular( $this->post_type ) ) {
			return;
		}

		$template_to_load = get_option( $this->prefix . 'video_details_page' );

		if ( $template_to_load==='default' ) {
			return;
		}

		if ( $overridden_template = locate_template( $template_to_load ) ) {
			load_template( $overridden_template ); exit;
		}
	}

	public function admin_columns(){
		add_filter( 'manage_posts_columns' , array($this, 'manage_posts_columns') );
		add_filter('manage_' . $this->post_type . '_posts_columns', 		array($this, 'wpadmin_columns')			, 5);
		add_action('manage_' . $this->post_type . '_posts_custom_column', 	array($this, 'wpadmin_custom_columns')	, 5, 2);
	}

	public function manage_posts_columns($columns){
		if (isset($_GET['post_type']) && $_GET['post_type']==$this->post_type ) {
			unset($columns['date']);
			unset($columns['author']);
			unset($columns['comments']);
		}
		return $columns;
	}

	public function wpadmin_columns($defaults){
		if (isset($_GET['post_type']) && $_GET['post_type']==$this->post_type) {
			$defaults['sorted_by'] = __('Video Order', VHub_LangPrefix);
			$defaults['video_service'] = __('Video Service', VHub_LangPrefix);
			$defaults['_block_video'] = __('Blocked', VHub_LangPrefix);
			$defaults['thumbnail'] = __('Thumbnail', VHub_LangPrefix);
		}

		return $defaults;
	}

	public function wpadmin_custom_columns($column_name, $id){
		global $post;

		switch ($column_name) {
			case 'thumbnail':
				$img 		= get_post_meta($id,'thumbnail',true);
				$video_id 	= get_post_meta($id,'video_id',true);
				if ($img) {
					echo '<a target="_blank" href="https://www.youtube.com/watch?v=' . $video_id . '"><img width="120" src="' . $img . '" alt="" /></a>';
				}
				break;
			case '_block_video':
				$val = get_post_meta($id,$column_name,true);
				if ( !$val ) {
					$val = 'No';
				}
				echo ucfirst($val);
				break;
			case 'sorted_by':
				echo '<span>';
				if ( $this->filter_date_range['order_type']==='added_to_video_service' ) {
					echo '<em>' . __('Published on Youtube', VHub_LangPrefix) . '</em><br/>' . date('Y-m-d H:i:s', get_post_meta( $id, 'published_time', true ) );
				} else {
					echo '<em>' . __('Published on Site', VHub_LangPrefix) . '</em><br/>' . get_the_time( 'Y-m-d H:i:s', $post );
				}
				echo '</span>';
				break;
			case 'video_service':
				echo '<img style="width:70px; height: auto" src="' . $this->get_plugin_url('css/images/' . get_post_meta($id,$column_name,true) . '.png') . '" alt="" />';
				break;
		}
	}

	protected function get_videos( $reset_query = false ){
		$per_page = $this->videos_per_page();
		$curr_page = isset($_GET['video_page']) ? $_GET['video_page'] : 1;
		$current_order = isset($_GET['crbh_orderby']) ? $_GET['crbh_orderby'] : false;

		$newest_order = get_option( $this->prefix . 'newest' ); # added_to_video_service, added_to_website
		if ( !$newest_order ) {
			$newest_order = 'added_to_website';
		}

		$popular_order = get_option( $this->prefix . 'popular' ); # youtube_popularity, facebook_votes
		if ( !$popular_order ) {
			$popular_order = 'youtube_popularity';
		}

		$query_args = array(
				'post_type' => $this->post_type,
				'posts_per_page' => (($per_page && is_numeric($per_page)) ? $per_page : 10),
				'paged' => $curr_page,
				'post_status' => 'publish',
				'meta_query' => array(
						'relation' => 'AND',
						array(
							'key' => '_block_video',
							'compare' => '!=',
							'value' => 'Yes'
						)
					)
			);

		if ( $current_order && $current_order==='popular' ) {
			$query_args['order'] = 'DESC';
			$query_args['orderby'] = 'meta_value_num';

			if( $popular_order==='facebook_votes' ) {
				$query_args['meta_key'] = 'fb_likes_comments_count'; // fb_comments_total
			} else {
				$query_args['meta_key'] = 'youtube_view_count';
			}
		} else {
			if( $newest_order==='added_to_website' ) { // added_to_website
				$query_args['orderby'] = 'post_date';
				$query_args['order'] = 'DESC';
			} else { // added_to_video_service
				$query_args['order'] = 'DESC';
				$query_args['meta_key'] = 'published_time';
				$query_args['orderby'] = 'meta_value_num';
			}
		}

		$entries = new WP_Query( $query_args );

		if ($reset_query) {
			wp_reset_query();
		}

		return $entries;
	}

	/**
	*	Plugin Shortcode [videohub]
	*/
	public function shortcode( $atts ) {
		if (!class_exists('VHub_Video')) {
			return;
		}

		ob_start();
			$entries = $this->get_videos();
				include( $this->get_plugin_path('includes/shortcode.php') );
			wp_reset_query();
		$html = ob_get_clean();

		return $html;
	}

	/**
	*	Returns an array with video symmary information
	*	@return array
	*/
	protected function build_video_summary(){
		$html = '';

		if (isset($_GET['post']) && is_numeric($_GET['post']) && class_exists('VHub_Video')) {
			$video_data = VHub_Video::get_video_data($_GET['post']);

			$html .= self::table_row_html(__('Video Title', VHub_Prefix), $video_data['title']);
			$html .= self::table_row_html(__('Video Link', VHub_Prefix), 'https://www.youtube.com/watch?v=' . $video_data['id']);
			$html .= self::table_row_html(__('Video Image', VHub_Prefix), '<img src="' . $video_data['thumbnail'] . '" alt="" />');
			$html .= self::table_row_html(__('Video Description', VHub_Prefix), wpautop($video_data['description']) );

			foreach ($video_data['metas'] as $meta_key => $meta_value ) {
				if (in_array($meta_key, array('rating', 'fb_likes', 'fb_comments', 'fb_comments_total'))) {
					continue;
				}
				$html .= self::table_row_html( __( ucwords(str_replace('_', ' ', $meta_key)), VHub_Prefix ) , $meta_value);
			}
		}

		return self::table_html( $html );
	}

	/**
	*	@return string
	*/
	public function table_html( $content ){
		return '
			<table width="99%" cellpadding="1" border="0" bgcolor="#EAEAEA">
				<tbody>
					<tr>
						<td>
							<table width="100%" cellpadding="5" border="0" bgcolor="#FFFFFF">
								<tbody>
									' . $content . '
								</tbody>
							</table>
						</td>
					</tr>
				</tbody>
			</table>
		';
	}

	/**
	*	@return string
	*/
	public function table_row_html($field_name, $field_value){
		return '
			<tr bgcolor="#EAF2FA">
				<td colspan="2">
					<font style="font-family:sans-serif;font-size:12px">
						<strong>' . esc_attr($field_name) . '</strong>
					</font>
				</td>
			</tr>
			<tr bgcolor="#FFFFFF">
				<td width="20">&nbsp;</td>
				<td>
					<font style="font-family:sans-serif;font-size:12px">' . $field_value . '</font>
				</td>
			</tr>
		';
	}

	/**
	*	@return string
	*/
	public function table_row_html_two($field_name, $field_value){
		return '
			<tr bgcolor="#EAF2FA">
				<td>
					<font style="font-family:sans-serif;font-size:12px">
						<strong>' . esc_attr($field_name) . '</strong>
					</font>
				</td>
				<td bgcolor="#FFFFFF">
					<font style="font-family:sans-serif;font-size:12px">' . $field_value . '</font>
				</td>
			</tr>
		';
	}

	/**
	*	Add additional editor button
	*/
	public function carbon_setup_editor_buttons() {
		// Only add hooks when the current user has permissions AND is in Rich Text editor mode
		if ( ( current_user_can('edit_posts') || current_user_can('edit_pages') ) && get_user_option('rich_editing') ) {
			add_filter("mce_external_plugins", 		array($this, "crb_register_tinymce_javascript") );
			add_filter('mce_buttons', 				array($this, 'crb_register_buttons') );
		}
	}

	public function crb_register_buttons($buttons) {
	   array_push($buttons, "separator", "vhub_plugin");
	   return $buttons;
	}

	// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
	public function crb_register_tinymce_javascript($plugin_array) {
	   $plugin_array["vhub_plugin"] = $this->get_plugin_url('js/backend-tinymce.js');
	   return $plugin_array;
	}

	public function plugin_admin_notices() {
		global $pagenow;

		$option_key = $this->prefix . 'plugin_notification';

		if ( !empty($_GET['videonab-notification']) && $_GET['videonab-notification']==='dismiss' ) {
			update_option( $option_key, 'disabled' );
			return;
		}

		if ( 'index.php'===$pagenow && get_option( $option_key )!=='disabled' ) {
			include( $this->get_plugin_path('includes/admin-notification.php') );
		}
	}

	protected function set_fitler_date_range() {
		$date_ranges = new VHub_Video_DateRange();
		$this->filter_date_range = $date_ranges->get_date_range();

		return $this;
	}

	public function pre_get_posts( $query ) {

		if (
			empty($query->query['post_type'])
			|| $query->query['post_type']!==$this->post_type
		) {
			return $query;
		}

		if (
			is_admin()
			&& !$query->is_main_query()
		) {
			return $query;
		}

		add_filter( 'posts_orderby', array($this, 'posts_orderby'));

		if (
			get_option( $this->prefix . 'enable_listing_filter' )!=='Yes'
			&& (
				empty( $_GET['crbh_orderby'] )
				|| $_GET['crbh_orderby']==='default'
			)
		) {

			$newest_order = get_option( $this->prefix . 'newest' ); # added_to_video_service, added_to_website
			if ( !$newest_order ) {
				$newest_order = 'added_to_website';
			}

			if( $newest_order==='added_to_website' ) { // added_to_website
				$query->set('orderby', 'post_date');
				$query->set('order', 'DESC');
			} else { // added_to_video_service
				$query->set('orderby', 'meta_value_num');
				$query->set('order', 'DESC');
				$query->set('meta_key', 'published_time');
			}

			return $query;
		}

		if ( $this->filter_date_range['order_type']!=='added_to_video_service' ) {

			add_filter( 'posts_where', array($this, 'posts_where') );

			return $query;
		}

		add_filter( 'posts_join_paged', array($this, 'posts_join_paged'));

		//Get original meta query
		$meta_query = $query->get('meta_query');

		$youtube_start_time = $this->filter_date_range['youtube_time_start'];
		$youtube_end_time = $this->filter_date_range['youtube_time_end'];

		$date_filter = $this->filter_date_range['date_filter'];
		$date_filter_fixed = strtotime( $this->filter_date_range['date_filter_fixed'] . $youtube_start_time );
		$date_filter_before = strtotime( $this->filter_date_range['date_filter_before'] . $youtube_end_time );
		$date_filter_after = strtotime( $this->filter_date_range['date_filter_after'] . $youtube_start_time );

		$date_filter_last_days = $this->filter_date_range['date_filter_last_days'];
		if ( !is_numeric($date_filter_last_days) ) {
			$date_filter_last_days = 5;
		}
		$date_filter_last_days_time = strtotime( "-{$date_filter_last_days} day" . date('Y-m-d') . $youtube_start_time );

		if (
			(
				$date_filter==='after_specific_date'
				|| $date_filter==='between_specific_date'
			)
			&& $date_filter_after
		) {
			$meta_query[] = array(
					'key' => 'published_time',
					'compare' => '>=',
					'value' => $date_filter_after,
					'type' => 'NUMERIC'
				);
		}

		if (
			(
				$date_filter==='before_specific_date'
				|| $date_filter==='between_specific_date'
			)
			&& $date_filter_before
		) {
			$meta_query[] = array(
					'key' => 'published_time',
					'compare' => '<=',
					'value' => $date_filter_before,
					'type' => 'NUMERIC'
				);
		}

		if (
			$date_filter==='specific_date'
			&& $date_filter_fixed
		) {
			$meta_query[] = array(
					'key' => 'published_time',
					'compare' => 'BETWEEN',
					'value' => array($date_filter_fixed,  strtotime( date('Y-m-d',$date_filter_fixed). $youtube_end_time ) ),
					'type' => 'NUMERIC'
				);
		}

		if (
			$date_filter==='within_last_days'
		) {
			$meta_query[] = array(
					'key' => 'published_time',
					'compare' => '>=',
					'value' => $date_filter_last_days_time,
					'type' => 'NUMERIC'
				);
		}

		$meta_query = $query->set('meta_query', $meta_query);

		return $query;
	}

	public function posts_orderby( $orderby ) {
		remove_filter( 'posts_orderby', array($this, 'posts_orderby'));

		global $wpdb;

		if ( $this->filter_date_range['order_type']==='added_to_video_service' ) {
			$orderby = str_replace( "{$wpdb->posts}.post_date" , "`crbh_meta`.`meta_value`", $orderby);
		} else {
			$orderby .= " , {$wpdb->posts}.post_title";
		}

		return $orderby;
	}

	public function posts_join_paged( $join ) {

		global $wpdb;

		$join .= "INNER JOIN `$wpdb->postmeta` AS `crbh_meta` ON (
					`crbh_meta`.`post_id` = `{$wpdb->posts}`.`ID`
					AND `crbh_meta`.`meta_key` = 'published_time'
				) ";

		return $join;
	}

	public function posts_where( $where ) {
		global $wpdb;

		# clear the posts_where filter
		remove_filter( 'posts_where', array($this, 'posts_where') );

		$date_filter = $this->filter_date_range['date_filter'];
		$date_filter_before = strtotime( $this->filter_date_range['date_filter_before'] );
		$date_filter_after = strtotime( $this->filter_date_range['date_filter_after'] );

		$date_filter_fixed_start = strtotime( $this->filter_date_range['date_filter_fixed'] . ' 00:00:00' );
		$date_filter_fixed_end = strtotime( $this->filter_date_range['date_filter_fixed'] . ' 23:59:59' );

		$date_filter_last_days = $this->filter_date_range['date_filter_last_days'];
		if ( !is_numeric($date_filter_last_days) ) {
			$date_filter_last_days = 5;
		}
		$date_filter_last_days_time = strtotime( "-{$date_filter_last_days} day" . date('Y-m-d') );

		$date = " CAST( unix_timestamp(`{$wpdb->posts}`.`post_date`) AS SIGNED) ";

		if (
			(
				$date_filter==='after_specific_date'
				|| $date_filter==='between_specific_date'
			)
			&& $date_filter_after
		) {
			$where .= " AND {$date} >='{$date_filter_after}' ";
		}

		if (
			(
				$date_filter==='before_specific_date'
				|| $date_filter==='between_specific_date'
			)
			&& $date_filter_before
		) {
			$where .= " AND {$date} <= '{$date_filter_before}' ";
		}

		if (
			$date_filter==='specific_date'
			&& $date_filter_fixed
		) {
			$where .= " AND {$date} >= '{$date_filter_fixed_start}' ";
			$where .= "  AND {$date} <= '{$date_filter_fixed_end}' ";
		}

		if (
			$date_filter==='within_last_days'
		) {
			$where .= " AND {$date} >='{$date_filter_last_days_time}' ";
		}

		return $where;
	}
}
