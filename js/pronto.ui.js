Pronto.UI = {};

/**
 * Popup a new window
 */
Pronto.UI.popup = function(url, w, h) {
	var w = w || 640;
	var h = h || 480;
	var a = window.open(url, 'win'+pronto.guid++, 'toolbar=no,location=no,directories=no,status=yes,menubar=no,resizable=yes,copyhistory=no,scrollbars=yes,width='+w+',height='+h);
	a.focus();
	return a;
}

/**
 * Builders for common form tags
 */
Pronto.UI.tag = {};

Pronto.UI.tag.tag = function(name, htmlopts, inner_html) {
	var inner = inner_html || '';
	var opts = '';
	for(var o in htmlopts) {
		opts += ' '+o+'="'+htmlopts[o]+'"';
	}
	if(inner.length) {
		return '<'+name+opts+'>'+inner+'</'+name+'>';
	}
	return '<'+name+opts+' />';
}

Pronto.UI.tag.input = function(type, name, value, htmlopts) {
	var htmlopts = htmlopts || {};
	htmlopts.type = type;
	htmlopts.name = name;
	htmlopts.value = value || '';
	return this.tag('input', htmlopts);
}

Pronto.UI.tag.text = function(name, value, htmlopts) {
	return this.input('text', name, value, htmlopts);
}

Pronto.UI.tag.checkbox = function(name, value, htmlopts) {
	return this.input('checkbox', name, value, htmlopts);
}

Pronto.UI.tag.textarea = function(name, value, htmlopts) {
	var htmlopts = htmlopts || {};
	htmlopts.name = name;
	return this.tag('textarea', htmlopts, value || '');
}

Pronto.UI.tag.select = function(name, value, choices, htmlopts) {
	var htmlopts = htmlopts || {};
	var inner = '';
	var value = value || '';
	for(var k in choices) {
		var c = choices[k];
		if(typeof c == 'object') {
			var val = c.value;
			var lbl = c.label;
		} else {
			var val = c;
			var lbl = c;
		}
		inner += '<option value="'+val+'"';
		if(value == val) inner += ' selected="selected"';
		inner += '>'+lbl+'</option>';
	}
	htmlopts.name = name;
	return this.tag('select', htmlopts, inner);
}

/**
 * Basic dialog with resize/drag functionality.
 * Requires the jqModal plugin.
 */
Pronto.UI.Dialog = function(id, title, overlay, modal) {
	this.loaded = true;

	if(id) {
		this.loaded = false;
		this.id = id;

		// create the dialog, insert into the DOM
		var template = '' +
			'<div class="dialog">' +
				'<div class="dialog-tb">' +
					'<img src="/.../img/icons/close_x.gif" class="dialog-close jqmClose">' +
					'<span>{{title}}</span>' +
				'</div>' +
				'<div class="dialog-content"></div>' +
				'<img src="/.../img/icons/resize.gif" class="dialog-resize />' +
			'</div>';

		this.$dlg = $(template
			.replace(/{{title}}/, title)
			.replace(/\/\.\.\.\//g, pronto.DIR_WS_BASE+'/')
		);
		this.$dlg.attr('id', this.id).appendTo($('body'));

		var opts = {toTop: true, modal:!!modal};
		if(!overlay) opts.overlay = 0;
		this.$dlg = $('#'+this.id);
		this.$dlg.jqm(opts).jqDrag('.dialog-tb').jqResize('img.dialog-resize');
	}

	this.content = function(c) {
		this.$dlg.find('.dialog-content').html(c);
		return this;
	}

	this.append = function(c) {
		this.$dlg.find('.dialog-content').append(c);
		return this;
	}

	this.size = function(w, h) {
		var h = Math.min($(window).height(), parseInt(h));
		var w = Math.min($(window).width(), parseInt(w));
		this.$dlg.css({height: h+'px', width: w+'px'});
	}

	this.show = function() {
		this.$dlg.jqmShowCenter();
		return this;
	}

	this.hide = function() {
		this.$dlg.jqmHide();
		return this;
	}

	this.destroy = function() {
		this.hide();
		this.$dlg.remove();
	}
}
/**
 * Find an existing dialog element
 */
Pronto.UI.Dialog.load = function(id) {
	var d = new Pronto.UI.Dialog();
	d.id = id;
	d.$dlg = $('#'+id);
	return d;
}
