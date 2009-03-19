/**
 *
 * Functions for flash messages
 *
 */

function flash_set(message) {
	$('#flash').empty().append('&rsaquo;&rsaquo; '+message);
	$('#flash').show();
	//$("#flash").fadeOut(10000);
}
