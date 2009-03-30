/**
 *
 * General AJAX Functions
 * 
 */



/**
 * Return the DOM element that was the target of the most recent event
 */
function evt_get_target(e) {
	var targ;
	if(!e) var e = window.event;
	if(e.target) {
		targ = e.target;
	} else if(e.srcElement) {
		targ = e.srcElement;
	}
	if(targ.nodeType == 3) // defeat Safari bug
		targ = targ.parentNode;
	return targ;
}

/**
 * Execute a JSON packet coming back from an AJAX request, with
 * optional target element that can be acted upon by code in
 * the JSON packet.
 */
function ajax_exec(data, target) {
	if(data.flash && typeof flash_set == 'function') flash_set(data.flash);
	if(data.exec)   eval(data.exec);
	if(data.js)     eval(data.js);
	if(data.reload) window.location.reload();
}

/**
 * Position an element near the mouse click.
 */
jQuery.fn.position_near_click = function(e) {
	var e = e || window.event;
	var h = $(this).height();
	var w = $(this).width();
	var ex = e.pageX || (e.clientX + document.body.scrollLeft + document.documentElement.scrollLeft);
	var ey = e.pageY || (e.clientY + document.body.scrollTop  + document.documentElement.scrollTop);
	var vx = $(window).scrollLeft() + $(window).width();
	var vy = $(window).scrollTop() + $(window).height();
	var x = (ex + w > vx) ? ex - w : ex;
	var y = (ey + (h+20) > vy) ? ey - h-20 : ey+20;
	if(x < 0) x = 0;
	if(y < 0) y = 0;
	$(this).css({left:x, top:y});
	return this;
}

/**
 * Populate an element with the results of an AJAX call and
 * show it near the mouse click (calc'ed from event object).
 */
jQuery.fn.ajaxShow = function(e, url, args) {
	var el = this;
	$(el).position_near_click(e);
	$.ajaxSetup({dataType: 'text'});
	$.get(url, args, function(data){
		$(el).empty().append(data).show("normal");
	});
}

/**
 * Make an existing link submit through AJAX.
 *
 * @param map callbacks A map of callbacks that fire under various conditions:
 *                      'precall' : Called before the AJAX request is issued
 *                      'success' : Called when AJAX request is completed
 *                      'fail'    : Called when the AJAX request fails
 * @param map options A map of option fields:
 *                    'spinner' : If true, add the "spinner" icon after the
 *                                anchor.
 */
jQuery.fn.ajaxLink = function(callbacks, options) {
	var callbacks = callbacks || {};
	var options   = options || {};
	var cb = function(data) {
		if(options.spinner) this.anchor.next('div.spinner').remove();
		if(typeof callbacks.success == 'function') callbacks.success(data, this.anchor);
		ajax_exec(data);
	}

	this.each(function(){
		var anchor = $(this);
		anchor.click(function(){
			anchor.blur();
			if(typeof callbacks.precall == 'function') {
				if(!callbacks.precall()) {
					return false;
				}
			}
			if(options.spinner) anchor.after('<div class="spinner"></div>');

			var opts = {
				url: anchor.attr('href'),
				type: "GET",
				data: {_ajax: 1},
				dataType: "json",
				success: cb,
				anchor: anchor
			};
			opts.error = function(xmlhttp, textstatus) {
				if(options.spinner) anchor.next('div.spinner').remove();
				if(typeof callbacks.fail == 'function') callbacks.fail(xmlhttp, textstatus);
			};
			$.ajax(opts);
			return false;
		});
	});
};

/**
 * Make an existing form submit through AJAX.  This will also set the correct
 * error fields if validation fails.
 *
 * @param map callbacks A map of callbacks that fire under various conditions:
 *                      'precall' : Called before the AJAX request is issued
 *                      'success' : Called when a form passes validation
 *                      'error'   : Called when a form fails validation
 *                      'fail'    : Called when the AJAX request fails
 * @param map options A map of option fields:
 *                    'spinner' : If true, add the "spinner" class to submit
 *                                buttons before making AJAX request
 */
jQuery.fn.ajaxForm = function(callbacks, options) {
	var callbacks = callbacks || {};
	var options   = options || {};
	var cb = function(data) {
		var form = $('#'+this.form_id);
		var formname = form.attr('name');
		if(options.spinner) {
			var sbmt = $('input[type=submit]', form);
			sbmt.css('width', sbmt.width()+'px');
			sbmt.removeClass('spinner');
		}
		// clear out old errors
		form.find('div.error').remove();
		form.find('p.error').remove();
		form.find('.error').removeClass('error');
		if(data.errors) {
			// Error
			var outside = new Array();
			jQuery.each(data.errors, function(k,v){
				var el = form.find('#'+formname+'_'+k);
				if(el[0]) {
					el.addClass('error');
					el.parent().append('<p class="error">'+v+'</p>');
				} else {
					// the others belong in a <div class="error"> at the top of the form
					outside.push(v);
				}
			});
			if(outside.length) {
				var s = '<div class="error"><ul>';
				for(var i = 0; i < outside.length; i++) s += "<li>"+outside[i]+"</li>";
				form.prepend(s+"</ul></div>");
			}
			if(typeof callbacks.error == 'function') callbacks.error(data, form);
		} else {
			if(typeof callbacks.success == 'function') callbacks.success(data, form);
		}
		ajax_exec(data);
	}

	this.each(function(){
		$(this).submit(function(){
			$(this).find('input[type=submit]').blur();
			if(typeof callbacks.precall == 'function') {
				if(!callbacks.precall($(this))) {
					return false;
				}
			}
			if(options.spinner) {
				$('input[type=submit]', this).css('width', ($('input[type=submit]', this).width()+30)+'px');
				$('input[type=submit]', this).addClass('spinner');
			}

			var qs = $(this).serializeArray();
			qs.push({name:'_ajax', value:'1'});
			var opts = {
				url: $(this).attr('action') ? $(this).attr('action') : window.location.href,
				data: qs,
				dataType: "json",
				success: cb,
				form_id: $(this).attr('id')
			};
			opts.error = function(xmlhttp, textstatus) {
				if(options.spinner) {
					$('input[type=submit]').css('width', $('input[type=submit]').width()+'px');
					$('input[type=submit]').removeClass('spinner');
				}
				if(typeof callbacks.fail == 'function') callbacks.fail(xmlhttp, textstatus);
			};
			if(/[Pp][Oo][Ss][Tt]/.test($(this).attr('method'))) {
				opts.type = "POST";
			} else {
				opts.type = "GET";
			}
			$.ajax(opts);
			return false;
		});
	});
};

