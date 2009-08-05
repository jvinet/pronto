/**
 *
 * Functions for flash messages using the Gritter plugin
 *
 */

function flash_set(message) {
	pronto.load_css('gritter');
	pronto.load_js('jq/jquery.gritter', function(){
		$.gritter.add({
			title: ' ',
			text: message,
			sticky: false,
			image: pronto.url('/img/gritter/ico_check.png'),
			time: 3000
		});
	});
}
