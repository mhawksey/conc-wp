// JavaScript Document
jQuery(document).ready(function($) {
	// $("html, body").animate({ scrollTop: $('#item-nav').offset().top }, 1000);
	if ($('#item-meta .excerpt').length>0){
		$('.content').hide();
	}
     $('a.read').click(function () {
         $(this).parent('.excerpt').hide();
         $(this).closest('#item-meta').find('.content').slideDown('fast');
         return false;
     });
     $('a.read-less').click(function () {
         $(this).parent('.content').slideUp('fast');
         $(this).closest('#item-meta').find('.excerpt').show();
         return false;
     });
});