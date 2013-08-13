// JavaScript Document
var _gaq = _gaq || [];
jQuery(document).ready(function($) {
    $( ".searchsubmit.bbpsw-search-submit").val("Go");
	$( ".widget-wrapper.widget_bbpress_search").children().eq(0).hide();
	$( "#accordion" ).accordion({active: false, collapsible: true, heightStyle: "content"});
	customAccordionHooks();	
	$( "#accordion" ).show();
	$( "#accordionLoader" ).hide();
	
	// shared count getter https://gist.github.com/yahelc/1413508#file-jquery-sharedcount-js
	
	$( document.body ).on( 'post-load', function(){
		$('.infinite-loader').remove();
		var opened = $("#accordion").accordion( "option", "active" );
		$("#accordion").accordion('destroy');
		$("#accordion").accordion({active: opened, collapsible: true, heightStyle: "content"});
		customAccordionHooks();	
	});

	$("#accordion").on("accordionactivate", function(event ,ui){
		event.preventDefault(); 
		var accor = $('.ajaxed', ui.newHeader);
		var loaded_post = $('.loaded-post', ui.newPanel);
		var post_id = accor.attr("id");
		var post_url  = accor.attr("url");
		var post_type = accor.attr("type");
		_gaq.push(['_trackEvent', 'Course Reader', 'read', post_url]);
		if (!loaded_post.hasClass('true') && loaded_post.length > 0){ 

			// clean post url removing GA utm_ for shared count
			post_url = post_url.replace(/\?([^#]*)/, function(_, search) {
							search = search.split('&').map(function(v) {
							  return !/^utm_/.test(v) && v;
							}).filter(Boolean).join('&'); // omg filter(Boolean) so dope.
							return search ? '?' + search : '';
							});
			$.ajax({
				type: 'POST',
				url: "/wp-admin/admin-ajax.php",
				data: ({
					action : 'ajaxify',
					post_id: post_id,
					post_type: post_type
					}),
				success:function(response){
					if (post_type == "summary")	$("#post-"+post_id).html(response);
					twttr.widgets.load();
					$("#accordion").accordion("refresh");
					$("#accordion h3[aria-controls='post-"+post_id+"']").addClass("read");
					$("#post-"+post_id+" .loaded-post").addClass('true');
					// added sharedcount.com data to accordion foot
					$.sharedCount(post_url, function(data){
							$("#post-"+post_id+" span#tw-count").text(data.Twitter);
							$("#post-"+post_id+" span#fb-count").text(data.Facebook.like_count);
							$("#post-"+post_id+" span#gp-count").text(data.GooglePlusOne);
							$("#post-"+post_id+" span#li-count").text(data.LinkedIn);
							$("#post-"+post_id+" span#del-count").text(data.Delicious);
					});
					
					
				}
			});
		}
	});
});

function customAccordionHooks(){
	jQuery('.jump_to_url').on("click", function(event){
		event.stopImmediatePropagation();
		event.stopPropagation();
	});	
	jQuery('.like, .unlike, .like_blogpost, .unlike_blogpost').on('click', function() {
		event.stopImmediatePropagation();
		event.stopPropagation();
		
		// Add the BuddyPress Like plugin code back in 
		// From http://wordpress.org/plugins/buddypress-like/
		// http://plugins.svn.wordpress.org/buddypress-like/tags/0.0.8/_inc/js/bp-like.dev.js
		var type = jQuery(this).attr('class');
		var id = jQuery(this).attr('id');
		
		jQuery(this).addClass('loading');
		
		jQuery.post( ajaxurl, {
			action: 'activity_like',
			'cookie': encodeURIComponent(document.cookie),
			'type': type,
			'id': id
		},
		function(data) {
			
			jQuery('#' + id).fadeOut( 100, function() {
				jQuery(this).html(data).removeClass('loading').fadeIn(100);
			});
			// Swap from like to unlike
			if (type == 'like') {
				var newID = id.replace("like", "unlike");
				jQuery('#' + id).removeClass('like').addClass('unlike').attr('title', bp_like_terms_unlike_message).attr('id', newID);
			} else if (type == 'like_blogpost') {
				var newID = id.replace("like", "unlike");
				jQuery('#' + id).removeClass('like_blogpost').addClass('unlike_blogpost').attr('title', bp_like_terms_unlike_message).attr('id', newID);
				var post_url = jQuery('#'+id.match(/\d+/)+'.ajaxed').attr('url')
				_gaq.push(['_trackEvent', 'Course Reader', 'fav', post_url]);
			} else if (type == 'unlike_blogpost') {
				var newID = id.replace("unlike", "like");
				jQuery('#' + id).removeClass('unlike_blogpost').addClass('like_blogpost').attr('title', bp_like_terms_unlike_message).attr('id', newID);
			} else {
				var newID = id.replace("unlike", "like");
				jQuery('#' + id).removeClass('unlike').addClass('like').attr('title', bp_like_terms_like_message).attr('id', newID);
			}
			
			// Nobody else liked this, so remove the 'View Likes'
			if (data == bp_like_terms_like) {
				var pureID = id.replace("unlike-activity-", "");
				jQuery('.view-likes#view-likes-'+ pureID).remove();
				jQuery('.users-who-like#users-who-like-'+ pureID).remove();
			}
			
			// Show the 'View Likes' if user is first to like
			if ( data == bp_like_terms_unlike_1 ) {
				var pureID = id.replace("like-activity-", "");
				jQuery('li#activity-'+ pureID + ' .activity-meta').append('<a href="" class="view-likes" id="view-likes-' + pureID + '">' + bp_like_terms_view_likes + '</a><p class="users-who-like" id="users-who-like-' + pureID + '"></p>');
			}
			
		});
		
		return false;
	});
}


function qs(url) {
    var params = {}, queries, temp, i, l;
    // Split into key/value pairs
    queries = url.substring( url.indexOf('?') + 1 ).split("&");
    // Convert the array of strings into an object
    for ( i = 0, l = queries.length; i < l; i++ ) {
        temp = queries[i].split('=');
        params[temp[0]] = temp[1];
    }
    return params;
};

jQuery.sharedCount = function(url, fn) {
	url = encodeURIComponent(url || location.href);
	var arg = {
		url: "//" + (location.protocol == "https:" ? "sharedcount.appspot" : "api.sharedcount") + ".com/?url=" + url,
		cache: true,
		dataType: "json"
	};
	if ('withCredentials' in new XMLHttpRequest) {
		arg.success = fn;
	}
	else {
		var cb = "sc_" + url.replace(/\W/g, '');
		window[cb] = fn;
		arg.jsonpCallback = cb;
		arg.dataType += "p";
	}
	return jQuery.ajax(arg);
};
function pop(title,url,optH,optW){ // script to handle social share popup
	h = optH || 500;
	w = optW || 680;
	sh = window.innerHeight || document.body.clientHeight;
	sw = window.innerWidth || document.body.clientWidth;
	wd = window.open(url, title,'scrollbars=no,menubar=no,height='+h+',width='+w+',resizable=yes,toolbar=no,location=no,status=no,top='+((sh/2)-(h/2))+',left='+((sw/2)-(w/2)));
}