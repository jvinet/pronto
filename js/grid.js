/**
 *
 * Grid-specific Functions
 * 
 */

/**
 * Dispatch.  Called when an AJAX-enabled link is clicked.
 */
function grid_dispatch(evt) {
	var targ = evt_get_target(evt);
	window.evt_target = targ;
	var url = $(targ).parent().attr('href');
	// call the URL in the event target and act on the JSON results
	$.ajaxSetup({dataType: 'json'});
	$.get(url, {_ajax: 1}, function(data){
		if(data.html) grid_loadhtml(targ, data.html);
		if(data.js) eval(data.js);
	});
	return false;
}

/**
 * Load some HTML (usually a form) into a content space below the
 * selected row.  If a form is present in the HTML, it will be
 * AJAX-enabled.
 */
function grid_loadhtml(targ, html) {
	var targ_id = $(targ).parent().parent().parent().attr('id');
	var td_id = "#"+targ_id+"_form_td";
	var close_str = "<br /><br />"+
		"<a href=\"#\" id=\""+td_id.substring(1)+"_close\" style=\"text-decoration:none;color:#00f\">"+
		"&#8593; Close &#8593;</a>";

	$(targ).parent().parent().parent().addClass('selected');
	$(td_id).empty();
	$(td_id).append(html + close_str);
	$(td_id).show();
	$(td_id+'_close').click(function(){ grid_closehtml(td_id); return false; });
	$(td_id+" form").ajaxForm({
		success: function(data, form) { grid_closehtml(targ); }
	}, { spinner: true });
	return;
}

/**
 * Remove the content/HTML region previously created by grid_loadhtml().
 */
function grid_closehtml(targ) {
	if(typeof targ == 'string') {
		var td_id = targ;
	} else {
		var targ_id = $(targ).parent().parent().parent().attr('id');
		var td_id = "#"+targ_id+"_form_td";
	}
	$(td_id).hide();
	$(td_id).empty();
	$(td_id).parent().prev().removeClass('selected');
}
