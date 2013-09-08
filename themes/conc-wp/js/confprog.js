var jq = jQuery;
jq(document).on('click', 'div.group-button a', 
		function() {
			var gid = jq(this).parent().attr('id');
			gid = gid.split('-');
			gid = gid[1];
			
			var nonce = jq(this).attr('href');
			nonce = nonce.split('?_wpnonce=');
			nonce = nonce[1].split('&');
			nonce = nonce[0];
			
			var thelink = jq(this);

			jq.post( ajaxurl, {
				action: 'joinleave_group',
				'cookie': encodeURIComponent(document.cookie),
				'gid': gid,
				'_wpnonce': nonce
			},
			function(response)
			{
				//console.log(response);
				response = response.substr(0, response.length-1);
				var parentdiv = thelink.parent();

				jq(parentdiv).fadeOut(200, 
					function() {
						parentdiv.html(response);
						
						var but = parentdiv.children("a:first");
						if (but.text() == "Follow Session"){
							but.text('+');
						} else if (but.text() == "Unfollow Session"){
							but.text('–');
						}
						parentdiv.fadeIn(200);
					}
				);
			});
			return false;
		}
	);
	
	
jq( document ).ready(function() {
	jq('.mysessions').toggle(function(){
		jq('.join-group').parent().parent().slideUp();
        jq(this).text('All Sessions'); 
		window.location.hash = "my"
		//return false;
    },function(){
		jq('.join-group').parent().parent().slideDown();
		jq('input:checkbox').change();
        jq(this).text('My Sessions'); 
		window.location.hash = "all"
		//return false;      
    });

	jq(".track input:checkbox").on('change', function() {
		var session_class = '.session.' +  this.id;   
		var sessionState = jq('.mysessions').text();
		if (sessionState == 'My Sessions' || sessionState == 'Not logged in'){
			jq(this).is(':checked') ? jq(session_class).slideDown() : jq(session_class).slideUp();
		} else {
			if(jq(this).is(':checked') ){
				if (jq(session_class).find('.leave-group').length){
					jq(session_class).find('.leave-group').parent().parent().slideDown();
				}
			} else {
				jq(session_class).slideUp();
			}
		}
		return false;
	});
	var is_touch_device = 'ontouchstart' in document.documentElement;
	if (!is_touch_device){
		jq(".session").hover(function() {
			var tip = jq(this).children(".session-tooltip:first");
			tip.stop(true).each(function(i) { 
			tip.delay(1000).fadeIn();  
		}); 
		}, function() {
			var tip = jq(this).children(".session-tooltip:first");
			tip.stop(true, true).fadeOut(0);
		});
	}
	jq('.check').toggle(function(){
        jq('input:checkbox').removeAttr('checked').change();
        jq(this).text('Check All'); 
    },function(){
		jq('input:checkbox').attr('checked','checked').change();
        jq(this).text('Uncheck All');       
    });

	
	// http://stackoverflow.com/a/5334231/1027723
	var gids = [];
	jq(".group-button.prog").each(function(){
	   gids.push(this.id.replace('groupbutton-','')); 
	});
	
	jq.post( ajaxurl, {
		action: 'get_joinleave_buttons_array',
		'cookie': encodeURIComponent(document.cookie),
		'gids': gids.toString(),
		},function(response){
			if (response=="false"){
				jq(".generic-button.prog").hide();
				jq(".mysessions").replaceWith("Not logged in");
				return;	
			};
			var ids = JSON.parse(response);
			jq.each(ids, function(key, val) {
				var sess = jq(".generic-button[id='groupbutton-"+key+"']");
				sess.replaceWith(val);
			});
			jq(".mysessions").text("My Sessions");	
			var hash = window.location.hash.slice(1);
			if (hash == "my"){
				jq('.mysessions').click();
			}
		})
		
});

function sessionToggle(){
	jq('.mysessions').toggle(function(){
		jq('.group-button.join-group').parent().parent().slideUp();
        jq(this).text('All Sessions'); 
		window.location.hash = "my"
		//return false;
    },function(){
		jq('.group-button.join-group').parent().parent().slideDown();
		jq('input:checkbox').change();
        jq(this).text('My Sessions'); 
		window.location.hash = "all"
		//return false;      
    });	
}