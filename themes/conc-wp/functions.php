<?php
$root_cat = "Reader";
require ( get_stylesheet_directory() . '/includes/XProfile_FWP.php' );
// record if post has been read in the reader view
require (get_stylesheet_directory() . '/includes/readerlite_mark_post_as_read.php');
// dropdown nav
require (get_stylesheet_directory() . '/includes/Walker_Nav_Menu_Dropdown.php');

if (class_exists('Conferencer')){
	include(get_stylesheet_directory() . '/includes/Conferencer_Shortcode_Agenda_Custom.php');
}

add_filter('bp_search_form_type_select_options', 'include_session_in_seach_option');
add_filter('bp_core_search_site', 'search_session_url', 10, 2);

add_filter('pre_get_posts','mySearchFilter');


function activitysub_custom_textdomain() {

	load_textdomain( 'bp-ass', WP_LANG_DIR.'/bp-ass.mo' );
}
add_action( 'init', 'activitysub_custom_textdomain' );

//add_action('after_setup_theme', 'remove_admin_bar');
function remove_admin_bar() {
	if ( ! current_user_can( 'edit_posts' ) ){
	  //echo "<style>#wp-admin-bar-site-name{display:none;}</style>";
	}
}

// Filter wp_nav_menu() to add profile link
// http://buddypress.org/support/topic/adding-dynamic-profile-link-to-main-menu-item/
add_filter( 'wp_nav_menu_items', 'my_nav_menu_profile_link', 10 ,2 );
function my_nav_menu_profile_link($menu, $args) { 
	$profilelink = "";
	if ($args->menu_id == "nav"){
			if (!is_user_logged_in())
					$profilelink = '<li><a href="/login/">Login</a></li>';
			else
					$profilelink = '<li><a href="' . bp_loggedin_user_domain( '/' ) . '">' . __('My Profile') . '</a></li>';
	}
	
	return $menu . $profilelink;
}

/**
 * Redirect back to homepage and not allow access to 
 * WP admin for Subscribers.
 */
function themeblvd_redirect_admin(){
	if ( ! current_user_can( 'edit_posts' ) && is_admin() && $_SERVER['PHP_SELF'] != '/wp-admin/admin-ajax.php' ){
		wp_redirect( bp_core_get_user_domain(bp_loggedin_user_id()) );
		exit;		
	}
}
add_action( 'admin_init', 'themeblvd_redirect_admin' );

function lost_password_link(){
	
	echo ('<p align="right"><a href="'.wp_lostpassword_url().'" title="Lost Password">Lost Password?</a></p>');	
	echo ("<p>Online delegates can <a href=\"/register\" title=\"Create an account\">request an account</a> (if you've registered to attend the conference and have not received login details please use the <a href=\"javascript:void(0)\" onclick=\"usernoise.window.show()\">Feedback</a> form).</p>");
}
add_filter('bp_after_sidebar_login_form', 'lost_password_link');


if (!function_exists('reader_excerpt')) {
	function reader_excerpt($post_id = false) {
		if ($post_id) $post = is_numeric($post_id) ? get_post($post_id) : $post_id;
		else $post = $GLOBALS['post'];

		if (!$post) return '';
		//if (isset($post->post_excerpt) && !empty($post->post_excerpt)) return $post->post_excerpt;
		if (!isset($post->post_content)) return '';
	
		$content = $raw_content = $post->post_content;
	
		if (!empty($content)) {
			
			$content = strip_tags($content);
			$content = preg_replace( '/\s+/', ' ', $content );
			$excerpt = explode(' ', $content, 50);
			array_pop($excerpt);
			$excerpt = implode(" ",$excerpt).'...';
			return $excerpt;
		}
	
	}
}

add_action( 'init', 'infinite_scroll_init' );
function infinite_scroll_init() {
	add_theme_support( 'infinite-scroll', array(
		'container' => 'accordion',
		'render'    => 'infinite_scroll_render',	
		'wrapper'   => false,
		'footer'    => false
	) );
}

/**
 * Set the code to be rendered on for calling posts,
 * hooked to template parts when possible.
 *
 * Note: must define a loop.
 */
function infinite_scroll_render() {
	//if(!$ajaxedload){
		get_template_part( 'content' );
	//} else {
	//	get_template_part( 'content-ajaxed' );
	//}
}

function mySearchFilter($query) {
	$search_which = $_REQUEST['search-which'];
	if (!$search_which == 'session') {
		$post_type = 'any';
	} else {
		$post_type = $search_which;	
	}
    if ($query->is_search) {
		$query->set( 'post_type', array( 'session' ) );
    };

    return $query;
};

/* Inject Activity in the Search drop down */

function include_session_in_seach_option($options) {
	$newoption['session'] = $options['groups'];
	unset($options['groups']);
	$options = $newoption + $options;
	return $options;
}
//where to redirect on activity search, obviously activity directory
function search_session_url($url, $search_terms) {
	$search_which = $_POST['search-which']; //what is being searched?

	if ($search_which != 'session')//is it activity? if not, let us return
		return $url;

	$url = '/?s=' . urlencode($search_terms);

	return $url;
}

if (get_option('readerlite_mark_as_read') != 'true'){
	include(get_stylesheet_directory() . '/includes/readerlite_installation.php');
	readerlite_mar_install();
	add_option('readerlite_mark_as_read','true');
}

register_sidebar(array(
	'name' => __('Reader Sidebar', 'buddypress'),
	'id' => 'reader',
	'description'   => __( 'The sidebar widget area', 'buddypress' ),
	'before_widget' => '<div id="%1$s" class="widget %2$s">',
	'after_widget'  => '</div>',
	'before_title'  => '<h3 class="widgettitle">',
	'after_title'   => '</h3>'
));

function reader_ajax() { // loads post content into accordion
    $post_id = $_POST['post_id'];
	$post_type = $_POST['post_type'];
	query_posts(array('p' => $post_id, 'post_type' => array('post')));
	ob_start();
	while (have_posts()) : the_post(); 
	if ($post_type == "summary") {
		// used if post content from the reader is being dynamically written
	} 
	if(function_exists('readerlite_mark_post_as_read')){
    	readerlite_mark_post_as_read();
	}
	endwhile;
	$output = ob_get_contents();
	ob_end_clean();
	echo $output;
	die(1);
}

add_action('wp_ajax_ajaxify', 'reader_ajax');           // for logged in user  
add_action('wp_ajax_nopriv_ajaxify', 'reader_ajax');

// load reader template even if sub category 
// inducded because the way I configure FWP is to put all posts in a reader category with various child stubs
// http://stackoverflow.com/a/3120150/1027723
function load_cat_parent_template()
{
    global $wp_query;
    if (!$wp_query->is_category)
        return true; // saves a bit of nesting
    // get current category object
    $cat = $wp_query->get_queried_object();
    // trace back the parent hierarchy and locate a template
    while ($cat && !is_wp_error($cat)) {
        $template = get_stylesheet_directory() . "/category-{$cat->slug}.php";
        if (file_exists($template)) {
            load_template($template);
			
            exit;
        }
        $cat = $cat->parent ? get_category($cat->parent) : false;
    }
}
add_action('template_redirect', 'load_cat_parent_template');

function reader_enqueue_accordion(){
	global $post;
	if (cat_is_ancestor_of(get_cat_id($root_cat), get_query_var('cat')) || is_category($root_cat)){
		wp_enqueue_script('jquery-ui-accordion'); // required for the reader
		wp_enqueue_style('conc-wp-jqueryui',
                get_stylesheet_directory_uri().'/css/jquery-ui-1.10.3.custom.min.css',
                false,
                1,
                false);
	}
}
add_action('wp_enqueue_scripts', 'reader_enqueue_accordion');

function remove_assets() {
	if (!is_admin()){
		$agent = $_SERVER['HTTP_USER_AGENT'];
		if (strpos($agent,'MSIE 8.') !== false){
			wp_dequeue_script('dtheme-ajax-js');
			wp_enqueue_script('ie8shim', get_stylesheet_directory_uri().'/js/stinkingie8.js'); // required for the reader
		}
	}
}
add_action('wp_print_styles', 'remove_assets', 99999);

function register_my_menus() {
  register_nav_menus(
    array(
      'top-nav' => __( 'Main Site Menu' ),
	  'header-nav' => __( 'Header Navigation' )
    )
  );
}
add_action( 'init', 'register_my_menus' );

function session_content_func( $atts=array() ) {
	extract($atts );
	$p = get_post($id);
	$post_content = $p->post_content;
	$new_excerpt = generate_excerpt($id);
	if (strlen($new_excerpt)>140){
		$content = '<div class="excerpt">'.$new_excerpt.'... <a href="" class="read">Read More</a>  </div>
				<div class="content">'.wpautop($post_content).' <a href="" class="read-less">Read Less</a></div>';
	} else {
		$content = '<div class="content">'.wpautop($post_content).'</div>';
	}
	$prefix = do_shortcode('[session_meta post_id='.$id.']');
	
	if (!is_admin()){
		$content = render_calendar($id).$prefix.$content;
	} else {
		$content = $prefix;
	}
	
	// return apply_filters( 'session-content-shortcodes-content', apply_filters( 'the_content', $content ), $p );
	return $content;
}
add_shortcode( 'session-content', 'session_content_func' );
add_filter( 'bp_get_group_description', 'do_shortcode' );

// adding custom jQuery to group pages (except home) eg scroll jump
function conc_wp_session_script() {
	//if(!bp_is_group_home()){
		wp_enqueue_script(
			'session-script',
			get_stylesheet_directory_uri() . '/js/session-script.js',
			array( 'jquery' )
		);  
	//}
}
add_filter( 'bp_before_group_header_meta', 'conc_wp_session_script' );


function get_user_role( $user_id ){

  $user_data = get_userdata( $user_id );

  if(!empty( $user_data->roles ))
      return $user_data->roles[0];

  return false; 

}

function render_calendar($id){ 
	return '<div style="float:right">'.google_calendar_link($id).ics_calendar_link($id).'</div>';
}

function ics_calendar_link($id){ 
	$img_url = get_stylesheet_directory_uri() . '/icons/add-to-calendar.png';
	return '<div class="calendar_button"><a href="?ical=1&sessionid='.$id.'" title="Download .ics file for your calendar software" onclick="_gaq.push([\'_trackEvent\', \'Calendar\', \'iCal\', \''.get_the_slug($id).'\']);"><img src="'.esc_url($img_url).'" alt="0" border="0" title="Download event details calendar button"></a></div>';
}

function add_query_vars($aVars) {
	$aVars[] = 'ical';
	$aVars[] = 'sessionid'; 
	return $aVars;
}
 
// hook add_query_vars function into query_vars
add_filter('query_vars', 'add_query_vars');


/**
 * Generates an ics file for a single event 
 * Modified from http://wordpress.org/plugins/events-manager/
 */
function ical_event($wp_query){
	if(isset($wp_query->query_vars['ical']) && $wp_query->query_vars['ical']=="1") {
		//send headers
		header('Content-type: text/calendar; charset=utf-8');
		header('Content-Disposition: inline; filename="'.get_the_slug($wp_query->query_vars['sessionid']).'.ics"');
		load_template( get_stylesheet_directory() . '/includes/ical.php');
		exit();
	}
}
add_action ( 'parse_query', 'ical_event' );

function google_calendar_link($id){
	$post = get_post($id);
	Conferencer::add_meta($post);
	// Modified from http://wordpress.org/plugins/events-manager/ em-events.php
	//get dates
	$dateStart	= date('Ymd\THis\Z',get_post_meta($post->time_slot, '_conferencer_starts', true) - (60*60*get_option('gmt_offset')) ); 
	$dateEnd = date('Ymd\THis\Z',get_post_meta($post->time_slot, '_conferencer_ends', true) - (60*60*get_option('gmt_offset')));
	//build url
	$gcal_url = 'http://www.google.com/calendar/event?action=TEMPLATE&text=event_name&dates=start_date/end_date&details=post_content&location=location_name&trp=false&sprop=event_url&sprop=name:blog_name';
	$gcal_url = str_replace('event_name', urlencode($post->post_title), $gcal_url);
	$gcal_url = str_replace('start_date', urlencode($dateStart), $gcal_url);
	$gcal_url = str_replace('end_date', urlencode($dateEnd), $gcal_url);
	$gcal_url = str_replace('location_name', urlencode(get_the_title($post->room)), $gcal_url);
	$gcal_url = str_replace('blog_name', urlencode(get_bloginfo()), $gcal_url);
	$gcal_url = str_replace('event_url', urlencode(get_permalink($id)), $gcal_url);
	//calculate URL length so we know how much we can work with to make a description.
	if( !empty($post->post_excerpt) ){
		$gcal_url_description = $post->post_excerpt;
	}else{
		$matches = explode('<!--more', $post->post_content);
		$gcal_url_description = strip_tags(wp_kses_data($matches[0]));
		
	}
	$gcal_url_length = strlen($gcal_url) - 9;
	$gcal_url_description = strip_tags(do_shortcode("[session_meta
							post_id='$post->ID'
							show='room,track'
							room_prefix='Room: '
							room_suffix='\n'
							track_prefix='Track: '
							link_all=false]"))."\n\n".generate_excerpt($post);
	if( strlen($gcal_url_description) + $gcal_url_length > 1350 ){
		$gcal_url_description = substr($gcal_url_description, 0, 1380 - $gcal_url_length - 3 ).'...';
	}	
	$gcal_url_description .= "\n\n".get_permalink($id);

	$gcal_url = str_replace('post_content', urlencode($gcal_url_description), $gcal_url);
	//get the final url
	$replace = $gcal_url;
	//if( $result == '#_EVENTGCALLINK' ){
		$img_url = 'www.google.com/calendar/images/ext/gc_button2.gif';
		$img_url = is_ssl() ? 'https://'.$img_url:'http://'.$img_url;
		$replace = '<div class="calendar_button"><a href="'.$replace.'" target="_blank" title="Add to your Google Calendar"  onclick="_gaq.push([\'_trackEvent\', \'Calendar\', \'Google\', \''.get_the_slug($id).'\']);"><img src="'.esc_url($img_url).'" alt="0" border="0" title="Add event to your Google Calendar button"></a></div>';
	//}	
	return $replace;
}

function edit_profile_link($atts){
		extract( shortcode_atts( array(
	      'text' => 'edit your profile',
		  'link' => '/login/',
		  'link_post' => ''
     ), $atts ) );

	if ( is_user_logged_in() ) {
		$link = bp_core_get_user_domain(bp_loggedin_user_id()) . $link_post ;
	}
	$output = '<a href="'.$link.'">'.$text.'</a>';
	return $output;
}
add_shortcode('edit_profile_link', 'edit_profile_link');

function newsletter_subscription_notification_settings() {
	global $bp ;?>
	<table class="notification-settings zebra" id="groups-notification-settings">
	<thead>
		<tr>
			<th class="icon"></th>
			<th class="title">Newsletter Subscription</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td></td>
			<td><?php get_mailpress_mlink(bp_core_get_user_email( $bp->loggedin_user->userdata->ID )); ?></td>
		</tr>
	</tbody>
	</table>	
<?php
}
if (class_exists('MailPress') && function_exists('get_mailpress_mlink')){
	add_action( 'bp_notification_settings', 'newsletter_subscription_notification_settings' );
}

// http://wordpress.org/support/topic/some-very-useful-tips-for-mailpress
function get_mailpress_mlink($user_email) {
  global $wpdb;
  $mailpress_user_key = $wpdb->get_var($wpdb->prepare("SELECT confkey FROM wp_mailpress_users WHERE email = '%s';", $user_email));
  	echo 'To manage your conference newsletter subscription goto <a href="/newsletter-subscription/?action=mail_link&del=' . $mailpress_user_key . '">Manage Newsletter Subscriptions</a>';
}

add_action('new_to_publish', 'con_group_create');		
add_action('draft_to_publish', 'con_group_create');		
add_action('pending_to_publish', 'con_group_create');

function con_group_create($post){
	$post_id = $post->ID;
    if ($post->post_type == 'session') {
		$new_group = new BP_Groups_Group;
 
        $new_group->creator_id = 1;
        $new_group->name = $post->post_title;
        $new_group->slug = $post->post_name;
        $new_group->description = '[session-content id='.$post_id.']';
        $new_group->status = 'public';
        $new_group->is_invitation_only = 0;
        $new_group->enable_wire = 1;
        $new_group->enable_forum = 0;
        $new_group->enable_photos = 1;
        $new_group->photos_admin_only = 1;
        $new_group->date_created = current_time('mysql');
        $new_group->total_member_count = 0;
        $new_group->avatar_thumb = 'http://altc2013.alt.ac.uk/wp-content/uploads/group-avatars/1/14084c335eb6682b25276ad41636d13d-bpthumb.png';
        $new_group->avatar_full = 'http://altc2013.alt.ac.uk/wp-content/uploads/group-avatars/1/14084c335eb6682b25276ad41636d13d-bpfull.png';
 
		$new_group -> save();
	 
	 	$group_id = $new_group->id;
		
		groups_update_groupmeta( $group_id, 'total_member_count', 0 );
		groups_update_groupmeta( $group_id, 'last_activity', current_time('mysql') );
		groups_update_groupmeta( $group_id, 'ass_default_subscription', 'dig');
		groups_update_groupmeta( $group_id, 'invite_status', 'members' );
		groups_accept_invite(1, $group_id );
		groups_promote_member(1, $group_id, 'admin');
		
		
		add_post_meta($post_id, 'con_group', $group_id, true);
	}
	return true;
}


// patch for conferencer to load jQuery 1.7.2 in reordering page (.curCSS depreciated in jQuery 1.8)
// http://wordpress.stackexchange.com/a/7282
function my_admin_enqueue($hook_suffix) {
    if($hook_suffix == 'conferencer_page_conferencer_reordering') {
		// http://wordpress.org/support/topic/error-has-no-method-curcss#post-3964638
    	wp_deregister_script('jquery');
		wp_deregister_script( 'jquery-ui-core' );
		wp_deregister_script( 'jquery-ui-draggable' );
    	wp_register_script('jquery', ("http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"), false, '1.7.2', false);
    	wp_enqueue_script('jquery');
    }
	if($hook_suffix == 'toplevel_page_bp-groups'){
		echo "<style>#bp-groups-form span.delete {display:none}</style>";	
	}
}
add_action('admin_enqueue_scripts', 'my_admin_enqueue');

// http://www.tcbarrett.com/2013/05/wordpress-how-to-get-the-slug-of-your-post-or-page/#.UexMz41ORsk
function get_the_slug( $id=null ){
  if( empty($id) ):
    global $post;
    if( empty($post) )
      return ''; // No global $post var available.
    $id = $post->ID;
  endif;

  $slug = basename( get_permalink($id) );
  return $slug;
}

function my_bp_activity_is_favorite($activity_id) {
global $bp, $activities_template;
return apply_filters( 'bp_get_activity_is_favorite', in_array( $activity_id, (array)$activities_template->my_favs ) );
}

function my_bp_activity_favorite_link($activity_id) {
global $activities_template;
echo apply_filters( 'bp_get_activity_favorite_link', wp_nonce_url( site_url( BP_ACTIVITY_SLUG . '/favorite/' . $activity_id . '/' ), 'mark_favorite' ) );
}

function my_bp_activity_unfavorite_link($activity_id) {
global $activities_template;
echo apply_filters( 'bp_get_activity_unfavorite_link', wp_nonce_url( site_url( BP_ACTIVITY_SLUG . '/unfavorite/' . $activity_id . '/' ), 'unmark_favorite' ) );
}
function my_bp_fav_button($post_id){
if ( is_user_logged_in() ) :
		bp_has_activities();
		$activity_id = bp_activity_get_activity_id( array(
													'component' => 'blogs',
													'secondary_item_id' => $post_id) );
	if ( !my_bp_activity_is_favorite($activity_id) ) : 
	?>
		<div class="fav_widget" act-id="<?php echo $activity_id;?>"><a href="<?php my_bp_activity_favorite_link($activity_id) ?>" class="fav" title="<?php _e( 'Mark as Favorite', 'buddypress' ) ?>"></a></div>
<?php else : ?>
		<div class="fav_widget" act-id="<?php echo $activity_id;?>"><a href="<?php my_bp_activity_unfavorite_link($activity_id) ?>" class="unfav" title="<?php _e( 'Remove Favorite', 'buddypress' ) ?>"></a></div>
	<? endif; 
endif;
}

/**
 * bp_like_button()
 *
 * Outputs the 'Like/Unlike' and 'View likes/Hide likes' buttons.
 * Modified from http://wordpress.org/plugins/buddypress-like/
 */
function conc_wp_bp_like_button( $id = '', $type = '' ) {
	if (function_exists(bp_like_button)):
	
	$users_who_like = 0;
	$liked_count = 0;
	
	/* Set the type if not already set, and check whether we are outputting the button on a blogpost or not. */
	if ( !$type && !is_single() && !is_category() )
		$type = 'activity';
	elseif ( !$type && is_single() )
		$type = 'blogpost';
	elseif ( !$type && is_category() )
		$type = 'reader';
	
	if ( $type == 'activity' ) :
	
		$activity = bp_activity_get_specific( array( 'activity_ids' => bp_get_activity_id() ) );
		$activity_type = $activity['activities'][0]->type;
	
		if ( is_user_logged_in() && $activity_type !== 'activity_liked' ) :
			
			if ( bp_activity_get_meta( bp_get_activity_id(), 'liked_count', true )) {
				$users_who_like = array_keys( bp_activity_get_meta( bp_get_activity_id(), 'liked_count', true ) );
				$liked_count = count( $users_who_like );
			}
			
			if ( !bp_like_is_liked( bp_get_activity_id(), 'activity' ) ) : ?>
				<a href="#" class="like" id="like-activity-<?php bp_activity_id(); ?>" title="<?php echo bp_like_get_text( 'like_this_item' ); ?>"><?php echo bp_like_get_text( 'like' ); if ( $liked_count ) echo ' <span>' . $liked_count . '</span>'; ?></a>
			<?php else : ?>
				<a href="#" class="unlike" id="unlike-activity-<?php bp_activity_id(); ?>" title="<?php echo bp_like_get_text( 'unlike_this_item' ); ?>"><?php echo bp_like_get_text( 'unlike' ); if ( $liked_count ) echo ' <span>' . $liked_count . '</span>'; ?></a>
			<?php endif;
			
			if ( $users_who_like ): ?>
				<a href="#" class="view-likes" id="view-likes-<?php bp_activity_id(); ?>"><?php echo bp_like_get_text( 'view_likes' ); ?></a>
				<p class="users-who-like" id="users-who-like-<?php bp_activity_id(); ?>"></p>
			<?php
			endif;
		endif;
	
	elseif ( $type == 'blogpost' ) :
		global $post;
		
		if ( !$id && is_single() )
			$id = $post->ID;
		
		if ( is_user_logged_in() && get_post_meta( $id, 'liked_count', true ) ) {
			$liked_count = count( get_post_meta( $id, 'liked_count', true ) );
		}
		
		if ( !bp_like_is_liked( $id, 'blogpost' ) ) : ?>
		
		<div class="like-box"><a href="#" class="like_blogpost" id="like-blogpost-<?php echo $id; ?>" title="<?php echo bp_like_get_text( 'like_this_item' ); ?>"><?php echo bp_like_get_text( 'like' ); if ( $liked_count ) echo ' (' . $liked_count . ')'; ?></a></div>
		
		<?php else : ?>
		
		<div class="like-box"><a href="#" class="unlike_blogpost" id="unlike-blogpost-<?php echo $id; ?>" title="<?php echo bp_like_get_text( 'unlike_this_item' ); ?>"><?php echo bp_like_get_text( 'unlike' ); if ( $liked_count ) echo ' (' . $liked_count . ')'; ?></a></div>
		<?php endif;

	elseif ( $type == 'reader' ) :
			
		if ( is_user_logged_in() && get_post_meta( $id, 'liked_count', true ) ) {
			$liked_count = count( get_post_meta( $id, 'liked_count', true ) );
		}
		
		if ( !bp_like_is_liked( $id, 'blogpost' ) ) : ?>
		
		<div class="like_widget"><a href="#" class="like_blogpost" id="like-blogpost-<?php echo $id; ?>" title="<?php echo bp_like_get_text( 'like_this_item' ); ?>"></a></div>
		
		<?php else : ?>
		
		<div class="like_widget"><a href="#" class="unlike_blogpost" id="unlike-blogpost-<?php echo $id; ?>" title="<?php echo bp_like_get_text( 'unlike_this_item' ); ?>"></a></div>
		<?php endif;
	endif;
	endif;
}

remove_filter( 'bp_activity_entry_meta', 'bp_like_button' );
add_filter( 'bp_activity_entry_meta', 'conc_wp_bp_like_button' );
remove_action( 'bp_before_blog_single_post', 'bp_like_button' );
add_action( 'bp_before_blog_single_post', 'conc_wp_bp_like_button' );

?>