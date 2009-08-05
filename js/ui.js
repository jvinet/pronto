Pronto.UI = {};

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
