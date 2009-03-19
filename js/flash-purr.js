/**
 *
 * Functions for flash messages using the Purr plugin
 *
 */

function flash_set(message) {
	pronto.load_css('purr');
	pronto.load_js('jq/jquery.purr', function(){
		var html = '<div class="notice"><div class="notice-body">';
		html += '<img src="'+pronto.url('/img/purr/info-trans.png')+'" />';
		html += '<p>'+message+'</p></div>';
		html += '<div class="notice-bottom"></div></div>';
		$(html).purr({usingTransparentPNG:true});
	});
}
