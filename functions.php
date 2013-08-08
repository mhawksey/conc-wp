<?php
$root_cat = "Reader";
require ( get_stylesheet_directory() . '/includes/XProfile_FWP.php' );
// record if post has been read in the reader view
require (get_stylesheet_directory() . '/includes/readerlite_mark_post_as_read.php');

if (get_option('readerlite_mark_as_read') != 'true'){
	include(get_stylesheet_directory() . '/includes/readerlite_installation.php');
	readerlite_mar_install();
	add_option('readerlite_mark_as_read','true');
}

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

function register_my_menus() {
  register_nav_menus(
    array(
      'top-nav' => __( 'Main Site Menu' )
    )
  );
}
add_action( 'init', 'register_my_menus' );

function session_content_func( $atts=array() ) {
	extract($atts );
	$p = get_post($id);
	$post_content = $p->post_content;
	$content = '<div class="excerpt">'.substr(strip_tags($post_content), 0 , 250).'... <a href="" class="read">Read More</a>  </div>
				<div class="content">'.$post_content.' <a href="" class="read-less">Read Less</a></div>';
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
  $mailpress_user_key = $wpdb->get_var($wpdb->prepare("SELECT confkey FROM wp_mailpress_users WHERE email = '$user_email';"));
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
		groups_update_groupmeta( $group_id, 'ass_default_subscription', 'sub');
		groups_accept_invite(1, $group_id );
		groups_promote_member(1, $group_id, 'admin');
		
		
		add_post_meta($post_id, 'con_group', $group_id, true);
	}
	return true;
}
if (class_exists('Conferencer')){
	include(get_stylesheet_directory() . '/includes/Conferencer_Shortcode_Agenda_Custom.php');
}

// patch for conferencer to load jQuery 1.7.2 in reordering page (.curCSS depreciated in jQuery 1.8)
// http://wordpress.stackexchange.com/a/7282
function my_admin_enqueue($hook_suffix) {
    if($hook_suffix == 'conferencer_page_conferencer_reordering') {
		// http://wordpress.org/support/topic/error-has-no-method-curcss#post-3964638
    	wp_deregister_script('jquery');
    	wp_register_script('jquery', ("http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"), false, '1.7.2', false);
    	wp_enqueue_script('jquery');
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


// https://gist.github.com/sbrajesh/2142009
function bpdev_exclude_users($qs=false,$object=false){
    //list of users to exclude
    
    $excluded_user=join(',',bpdev_get_subscriber_user_ids());//comma separated ids of users whom you want to exclude
   
    if($object!='members')//hide for members only
        return $qs;
    
    $args=wp_parse_args($qs);
    
    //check if we are searching for friends list etc?, do not exclude in this case
    if(!empty($args['user_id']))
        return $qs;
    
    if(!empty($args['exclude']))
        $args['exclude']=$args['exclude'].','.$excluded_user;
    else 
        $args['exclude']=$excluded_user;
      
    $qs=build_query($args);
   
   
   return $qs;
    
}
add_action('bp_ajax_querystring','bpdev_exclude_users',20,2);
 
function bpdev_get_subscriber_user_ids(){
    $users=array();
    $subscribers= get_users( array( 'role' => 'contributor' ) );
   if(!empty($subscribers)){
       foreach((array)$subscribers as $subscriber)
           $users[]=$subscriber->ID;
       
   }
   return $users;
}



/**
 * Responsive header markup for frontend
 *
 * the markup use <noscript> responsive image techique as Antti Peisa describes it
 * @link http://www.monoliitti.com/images/
 *
 * with a slight alternation required by jQuery Picture plugin
 * @link http://jquerypicture.com/ 
 **/
function frl_header_image_markup(){
	$baseurl = get_stylesheet_directory_uri().'/images/header-';
?>
<picture alt="Logo" id="header-image">
    <source src="<?php echo $baseurl.'min.jpg';?>">
    <source src="<?php echo $baseurl.'med.jpg';?>" media="(min-width:440px)">
    <source src="<?php echo $baseurl.'max.jpg';?>" media="(min-width:600px)">
    <noscript>
        <img src="<?php echo $baseurl.'max.jpg';?>" alt="Logo">
    </noscript>
</picture>

<?php
}


/**
 * CSS for responsive header in frontend
 **/
function frl_header_image_style() {
	
	$src = get_stylesheet_directory_uri().'/js/jquery-picture-min.js';
    wp_enqueue_script('jquery-picture', $src, array('jquery'), 0.9, true);
?>
	<style type="text/css">
        #header-image {
            padding: 0.5em 0; }
            
        #header-image img {
			vertical-align: bottom;
            width: 100%;
            height: auto; }
    </style>
	
	<script>
        jQuery(document).ready(function($){
            $('#header-image').picture();
        });
    </script>
<?php
}
add_action('wp_head', 'frl_header_image_style');
?>