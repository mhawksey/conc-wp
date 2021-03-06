<?php




// Handle what's linked in user profile https://gist.github.com/modemlooper/4574785
function bp_select_links_in_profile() {
  add_filter( 'bp_get_the_profile_field_value', 'bp_links_in_profile', 10, 3 );
}
add_action( 'bp_init', 'bp_select_links_in_profile', 0 );

// function to handle links in user profile (removing hyperlink search to bio 
function bp_links_in_profile( $val, $type, $key ) {
	$field = new BP_XProfile_Field( $key );
	$field_name = $field->name;
	if(  strtolower( $field_name ) == 'bio' ) {
		$val = strip_tags( $val );
	}
	return $val;
}

// adding custom jQuery to profile edit page
function conc_wp_profile_edit() {
	//if(!bp_is_group_home()){
		wp_enqueue_script(
			'profile-edit',
			get_stylesheet_directory_uri() . '/js/profile.js',
			array( 'jquery' )
		);  
	//}
}
add_filter( 'bp_before_profile_edit_content', 'conc_wp_profile_edit');

// some ajax for when we try and detect the blog feed
function ajaxFeedSearch() {
  	$url = $_POST['blog'];
	$id = $_POST['id'];
	$output = '<select name="'.$id.'" id="'.$id.'">';
	// stolen from Alan Levine (@cogdog)
    if($html = @DOMDocument::loadHTML(file_get_contents($url))) {
  
        $xpath = new DOMXPath($html);
        $options = false;
         
        // find RSS 2.0 feeds
        $feeds = $xpath->query("//head/link[@href][@type='application/rss+xml']/@href");
        foreach($feeds as $feed) {
            //$results[] = $feed->nodeValue;
			$urlStr = $feed->nodeValue;
			$parsed = parse_url($urlStr);
			if (empty($parsed['scheme'])) $urlStr = untrailingslashit($url).$urlStr;
			$options .= '<option value="'.$urlStr.'">'.$urlStr.'</option>';
        }
  
         // find Atom feeds
        $feeds = $xpath->query("//head/link[@href][@type='application/atom+xml']/@href");
        foreach($feeds as $feed) {
            //$results[] = $feed->nodeValue;
			$urlStr = $feed->nodeValue;
			$parsed = parse_url($urlStr);
			if (empty($parsed['scheme'])) $urlStr = untrailingslashit($url).$urlStr;
			$options .= '<option value="'.$urlStr.'">'.$urlStr.'</option>';
        }
		
        //$single_rss_url = $results[0];
        //return $single_rss_url;
    }
	
	$options .= '<option value="Other">Other</option>';
	$output .= $options.'</select>';
	$output .= '<input id="other_feed" name="other_feed" type="text" placeholder="Enter other feed" />';
	
    echo $output;
	die(1);;
}
add_action('wp_ajax_ajaxFeedSearch', 'ajaxFeedSearch');

function subscriber_type($userid){
	$user = new WP_User( $userid );
	$is_sub = false;
    if ( !empty( $user->roles ) && is_array( $user->roles ) ) {
        foreach ( $user->roles as $role ){
            if ($role=="subscriber" || $role=="subscriber-unlisted") 
				return $role;
		}
    }
	return $is_sub;
}

// handling registration of a blog feed with feedwordpress
function update_extra_profile_fields($user_id, $posted_field_ids, $errors) {
    // There are errors
    if ( empty( $errors ) ) {
        // Reset the errors var
        $errors = false;
        // Now we've checked for required fields, lets save the values.
        foreach ( (array) $posted_field_ids as $field_id ) {
			$field = new BP_XProfile_Field($field_id);
			//print_r($field);
			if ($field->name == 'Blog'){
				$blogurl = $_POST['field_'.$field_id];
			}
			if ($field->name == 'Blog RSS'){
				$blogrss = $_POST['field_'.$field_id];
			}
			if ($field->name == 'Searchable in members list'){
				$onlist = $_POST['field_'.$field_id];
			}
	
		}
	}
	if (!$onlist && subscriber_type($user_id) == "subscriber"){
		// http://wordpress.stackexchange.com/a/4727
		$u = new WP_User( $user_id );
		// Remove role
		$u->remove_role( "subscriber" );
		// Add role
		$u->add_role( 'subscriber-unlisted' );
	} elseif ($onlist && subscriber_type($user_id) == "subscriber-unlisted"){
		$u = new WP_User( $user_id );
		$u->remove_role( "subscriber-unlisted" );
		$u->add_role( 'subscriber' );
	}
	
	$linkid = get_user_meta($user_id, 'link_id', true);
	if ($blogrss != "" && $blogurl == "") $blogurl = $blogrss;
	if ($blogrss != "" && $blogurl != ""){ 
		$newid = make_link($user_id, $blogurl, $blogrss, $linkid);
		update_user_meta($user_id, 'link_id', $newid);
	}
}

// add to wp_link (used by FWP for list of syndicated sites
function make_link($user_id, $blogurl, $blogrss, $linkid = false) {
	// a lot of this was inspired by http://wrapping.marthaburtis.net/2012/08/22/perfecting-the-syndicated-blog-sign-up/
	remove_filter('pre_link_rss', 'wp_filter_kses');
	remove_filter('pre_link_url', 'wp_filter_kses');
	// Get contributors category 
	$mylinks_categories = get_terms('link_category', 'name__like=Contributors');
	$contrib_cat = intval($mylinks_categories[0]->term_id);
	
	$link_notes = 'map authors: name\n*\n'.$user_id;
	$new_link = array(
			'link_name' => $blogurl,
			'link_url' => $blogurl,
			'link_category' => $contrib_cat,
			'link_rss' => $blogrss
			);
	if( !function_exists( 'wp_insert_link' ) )
		include_once( ABSPATH . '/wp-admin/includes/bookmark.php' );	
			
	if (!($linkid)) { // if no link insert new link
		$linkid = wp_insert_link($new_link);
		// update new link with notes
		global $wpdb;
		$esc_link_notes = $wpdb->escape($link_notes);
		$result = $wpdb->query("
			UPDATE $wpdb->links
			SET link_notes = \"".$esc_link_notes."\" 
			WHERE link_id='$linkid'
		");
	} else {
		//update existing link
		$new_link['link_id'] = $linkid;
		$linkid = wp_insert_link($new_link);
	}
	return $linkid;
}
add_action( 'xprofile_updated_profile', 'update_extra_profile_fields',10, 3 );

// remove and add some wp_usermeta 
// TODO sync with social profiles with wp_user_meta aliases for FWP author detection
function add_hide_profile_fields( $contactmethods ) {
	unset($contactmethods['aim']);
	unset($contactmethods['jabber']);
	unset($contactmethods['yim']);
	$contactmethods['twitter'] = 'Twitter';
	$contactmethods['blog'] = 'Blog';
	$contactmethods['blogrss'] = 'Blog RSS Feed';
	$contactmethods['particpant_id'] = 'Particpant ID';
	$contactmethods['paper_id'] = 'Paper ID';
	$contactmethods['main_author'] = 'Main Author';
return $contactmethods;
}
add_filter('user_contactmethods','add_hide_profile_fields',10,1);

// https://gist.github.com/sbrajesh/2142009
function bpdev_exclude_users($qs=false,$object=false){
    $args=wp_parse_args($qs);
	

    if($object!='members' || $args['pagename']=='friends')//hide for members only
        return $qs;
	
    //list of users to exclude
	
    $excluded_user_array = array_merge(bpdev_get_subscriber_user_ids('contributor'),bpdev_get_subscriber_user_ids('subscriber-unlisted'));	
    $excluded_user=join(',',$excluded_user_array);//comma separated ids of users whom you want to exclude
    
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
 
function bpdev_get_subscriber_user_ids($role){
    $users=array();
    $subscribers= get_users( array( 'role' => $role ) );
   if(!empty($subscribers)){
       foreach((array)$subscribers as $subscriber)
           $users[]=$subscriber->ID;
       
   }
   return $users;
}

function conc_wp_tick_subscriber(){
	global $bp;
	if (subscriber_type($bp->displayed_user->id) == "subscriber"){
		?>
  <script type="text/javascript">
jQuery( document ).ready(function($) {
	$('.field_searchable-in-members-list input:checkbox').attr('checked','checked');
	});
</script>      
        <?php	
	}
}
add_action('bp_after_profile_field_content', 'conc_wp_tick_subscriber');

?>