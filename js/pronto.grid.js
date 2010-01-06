/**
 *
 * Grid-specific Functions
 * 
 */

function grid_sort(href) {
	location.href = $(href).find('a').attr('href');
	return false;
}

function grid_submit(form) {
	// form -> tbody -> table
	var guid = $(form).parent().parent().attr('id');
	var url = $(form).attr('action') + '?' + $(form).serialize();
	$.get(url, {_ajax: 1, gridcfg: window.grid[guid]}, function(d) {
		console.log(d);
	});
	return false;
}

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

/**
 * Issues:
 *   - how to do i18n?
 *
 * TODO:
 *   - rename/reorg params if necessary
 *   - use shortcut for common vars (eg, self.cfg.options)
 *   - normalize css class names (eg, "filter" to "grid-filter")
 *   - first-time rendering may happen twice in a row?
 */

Pronto.UI.Grid = function($table, url, cfg) {
	this.$table = $table;
	this.url = url || window.location.href;
	this.cfg = cfg;

	// merge supplied config with defaults
	var defaults = {
		'class':         'grid',
		options:       [],
		data_id:       'id',
		perpage_opts:  [50,200,500,1000],
		noresults_txt: "No Matches"
	}
	this.cfg = $.extend(defaults, cfg);

	// data from backend
	this.data = {};

	// state variables
	this.state = {
		sortdir:    'asc',
		sortby:     '',
		curpage:    1,
		perpage:    50,
		url_params: {},
		totals:     {}
	}

	this.build = function() {
		var self = this;

		// Create orgnizational elements
		self.$table.append('<thead></thead>')
			.append('<tbody></tbody')
			.append('<tfoot></tfoot>');

		self.head();
		self.fetch();  // calls .body() and .foot()
	}

	this.head = function() {
		var self = this;

		var $thead = self.$table.find('thead');

		// COLUMN HEADERS
		if(!self.cfg.options.noheaders) {
			var $tr = $('<tr class="label grid-hdr"></tr>');
			var i = 1;
			$.each(self.cfg.columns, function(colname, col) {
				var $th = $('<th></th>');
				if(i++ == self.cfg.columns.length) {
					$th.css('borderRight', '0');
				}

				if(!/^_OPTIONS_/.test(colname)) {
					// highlight the sorted column, otherwise add mouseover/out events
					// to highlight columns
					if(self.state.sortby == colname) {
						$th.addClass('hover');
					} else {
						$th.hover(
							function(){ $(this).addClass('hover'); },
							function(){ $(this).removeClass('hover') }
						);
					}
					var label = '';
					if(/^_MULTI_/.test(colname)) {
						$th.css('textAlign', 'center');
						label = colname.substr(7);
					} else {
						label = col.label ? col.label : '&nbsp;';
					}
					if(!self.cfg.options.nosorting && !col.nosort) {
						if(self.state.sortby == colname) {
							var arrow = self.state.sortdir == 'desc' ? 'arrow_black_down.gif' : 'arrow_black_up.gif';
							$th.append('<img class="grid-sort" src="'+pronto.url('/img/icons/'+arrow)+'" style="float:right" />');
						}
						label = $('<a href="#">'+label+'</a>');

						// bind clicks to sort methods
						$th.click(function(){
							var dir = 'asc';
							if(self.state.sortby == colname) {
								dir = self.state.sortdir == 'desc' ? 'asc' : 'desc';
							}
							self.state.sortby = colname;
							self.state.sortdir = dir;

							$thead.find('th').removeClass('selected');
							$(this).blur().parent().addClass('selected');

							var arrow = self.state.sortdir == 'desc' ? 'arrow_black_down.gif' : 'arrow_black_up.gif';
							$thead.find('img.grid-sort').remove();
							$(this).prepend('<img class="grid-sort" src="'+pronto.url('/img/icons/'+arrow)+'" style="float:right" />');

							self.fetch({s_f:colname, s_d:dir});
							return false;
						});
					}
					$th.append(label);
				} else {
					if(colname == '_OPTIONS_' && !self.cfg.options.nofilters && !self.cfg.options.nofilterbutton) {
						// Search Icon - click to show the search filters
						var $a = $('<a href="#"><img src="'+pronto.url('/img/icons/magnifier.gif')+'" /></a>');
						$a.click(function(){
							// show the next <tr>
							$(this).parent().parent().next().show();
							$(this).hide().next('a').show();
							return false;
						});
						$a.appendTo($th);

						var $a = $('<a style="display:none" href="'+pronto.url('/static/filters.en.html')+'">Filter Help</a>');
						$a.click(function(){
							var w = Pronto.UI.popup($(this).attr('href'));
							return false;
						});
						$a.appendTo($th);
					} else {
						$th.append(colname.substr(9));
					}
				}
				$th.appendTo($tr);
			});

			$tr.appendTo($thead);
		}

		// SEARCH FILTERS
		if(!self.cfg.options.nofilters) {
			var $tr = $('<tr class="filter grid-hdr" style="display:none"></tr>');
			var i = 1;

			// Callback to submit the filter vars to the AJAX backend
			function submit_search() {
				var qs = {};
				$thead.find('input.grid-filter').each(function(){
					qs[$(this).attr('name')] = $(this).val();
				});
				self.fetch(qs);
				return false;
			}

			$.each(self.cfg.columns, function(colname, col) {
				var $th = $('<th></th>');
				if(i++ == self.cfg.columns.length) {
					$th.css('borderRight', '0');
				}
				if(self.state.sortby == colname) {
					$th.addClass('hover');
				}

				// Multiselect column
				if(/^_MULTI_/.test(colname)) {
					$th.css('textAlign', 'center');
					var mname = colname.substr(7).toLowerCase();
					if(mname) mname += '_';
					var $chk = $('<input type="checkbox" name="_'+mname+'all" value="all" />');
					// TODO handle this check event
					$th.append($chk);

					// we're in a callback, not a loop, so no "continue"
					$th.appendTo($tr);
					return;
				}

				// Action/option column
				if(/^_OPTIONS_/.test(colname)) {
					if(colname == '_OPTIONS_' && !self.cfg.options.nofilters && !self.cfg.options.nofilterbutton) {
						var $sbmt = $('<input type="submit" class="submit" value="Filter" />');
						$sbmt.css('width', 'auto');
						$sbmt.click(submit_search);
						$th.append($sbmt);
					} else {
						$th.append('&nbsp;');
					}
					// we're in a callback, not a loop, so no "continue"
					$th.appendTo($tr);
					return;
				}

				// Regular search filter
				col.type = col.type || 'text';
				if(col.type == 'none') {
					$th.append('&nbsp;');
				} else {
					var t = col.type.substr(0, 1) || 't';
					var expr = col.expr ? 'f_'+t+'_'+col.expr : 'f_'+t+'_'+colname;
					var attrs = col.attribs || {};
					var select_opts = [''];
					if(col.options) $.each(col.options, function(k,v) {
						select_opts.push({label:v, value:k});
					});
					if(col.options_nokeys) $.each(col.options_nokeys, function(k,v) {
						select_opts.push(v);
					});
					var elem = '';
					// TODO: honour col.flength for text fields, or do we need it?
					switch(col.type) {
						case 'select': elem = Pronto.UI.tag.select(expr, '', select_opts, attrs); break;
						case 'date':   elem = Pronto.UI.tag.text(expr, '', attrs); break;
						case 'text':
						default:       elem = Pronto.UI.tag.text(expr, '', attrs);
					}
					var $elem = $(elem);
					$elem.addClass('grid-filter');
					// allow Enter keypress to submit the search form
					$elem.keypress(function(e){
						if(e.which == 13) return submit_search();
						return true;
					});

					$th.append($elem);
				}
				$th.appendTo($tr);
			});
			$tr.appendTo($thead);
		}

		self.fetch();
	}

	/**
	 * Fetch data from backend and pass it to a callback function.
	 */
	this.fetch = function(params, cb) {
		var self = this;
		var params = params || {};
		var cb = cb || function(d) { self.body(); self.foot(); };

		$.each(params, function(k,v){ self.state.url_params[k] = v });
		params = self.state.url_params;
		params['_ajax'] = 1;
		params['p_p']   = self.state.curpage;
		params['p_pp']  = self.state.perpage;
		$.getJSON(self.url, params, function(d){
			self.data = d;
			cb.call(self, d);
		});
	}

	/**
	 * Draw the grid body using the data provided.
	 */
	this.body = function() {
		var self = this;

		var $tbody = self.$table.find('tbody');
		var $tfoot = self.$table.find('tfoot');

		// erase old rows
		$tbody.empty();
		$tfoot.empty();

		// Private functions
		function _getrowdata(row, pk_col) {
			if(typeof pk_col == 'array') {
				if(pk_col.length == 1) return row[pk_col[0]];
				var singleidx = pk_col.shift();
				return _getrowdata(row[pk_col[0]], pk_col);
			}
			return row[pk_col];
		}

		// TODO: handle cb_vars

		self.state.totals = {};
		if(self.cfg.totals) {
			$.each(self.cfg.totals, function(k, v){ self.state.totals[k] = 0; });
		}

		if(self.data.records < 1) {
			var $tr = $('<tr><td colspan="100%"><p>'+self.cfg.noresults_txt+'</p></td></tr>');
			$tr.appendTo($tbody);
		}

		var multi_form = false;
		$.each(self.cfg.columns, function(colname,col){ if(/^_MULTI_/.test(colname)) multi_form = true; });
		if(multi_form) {
			// TODO: start a new form for multiselect boxes
		}

		var rowct = 0;
		$.each(self.data.rows, function(rowidx,row) {
			var tr_id = 'tr' + (pronto.guid++);
			var $tr = $('<tr id="'+tr_id+'" class="grid-data"></tr>');
			if(rowct++ % 2 != 1) $tr.addClass('altrow');
			// call the function defined in rowclassfn, if it exists
			if(self.cfg.rowclassfn) {
				$tr.addClass(self.cfg.rowclassfn.call(this));
			}

			if(self.cfg.rowclick) {
				// TODO
			}

			$.each(self.cfg.columns, function(colname,col){
				var $td = $('<td></td>');
				if(col.align) $td.attr('align', col.align);

				if(/^_OPTIONS_/.test(colname)) {
					$td.addClass('options');
					$.each(col, function(_,opt){
						// TODO: if(function_exists($opt)) ...
						var link = opt.replace(/_ID_/g, _getrowdata(row, self.cfg.data_id));

						// also sub in values for column names of the form <colname>
						var re = /<([A-z0-9\._-]+)>/g;
						var final_link = link;
						while(subs = re.exec(link)) {
							if(typeof subs == 'object') {
								final_link = final_link.replace(subs[0], _getrowdata(row, subs[1]));
							}
						}
						$td.append(final_link);
					});
				} else if(/^_MULTI_/.test(colname)) {
					// TODO
					var mname = colname.substr(7);
					if(mname) mname = mname + '_';

					var $chk = $('<input type="checkbox" style="border:none" name="'+mname+'ids[]" value="'+_getrowdata(row, self.cfg.data_id)+'" />');
					if(_getrowdata(row, '_m_'+mname)) $chk.attr('checked','checked');
					$td.addClass('multi');
					$td.append($chk);
				} else {
					var data = _getrowdata(row, colname);
					if(self.state.totals[colname]) {
						self.state.totals[colname] += data;
					}

					// TODO: mangle row data if necessary
					if(col.display_map && col.display_map[data]) {
						data = col.display_map[data];
					} else if(typeof col.display_func == 'function') {
						// TODO: use cb_vars like the old one?
						data = col.display_func.call(row);
					} else if(col.format) {
						if(typeof $.sprintf != 'function') {
							pronto.load_js('jq/jquery.sprintf', function(){
								data = $.sprintf(col.format, data);
								// have to issue the .html() call here since, the one below
								// will get run immediately (before we get $.sprintf() loaded)
								$td.html(data);
							});
						} else {
							data = $.sprintf(col.format, data);
						}
					} else if(col.date_format) {
						if(typeof dateFormat != 'function') {
							pronto.load_js('date.format', function(){
								data = dateFormat(col.date_format, data);
								// have to issue the .html() call here since, the one below
								// will get run immediately (before we get $.sprintf() loaded)
								$td.html(data);
							});
						} else {
							data = dateFormat(col.date_format, data);
						}
					}

					$td.html(data);
				}
				$td.appendTo($tr);
			});

			$tr.appendTo($tbody);

			// this <tr> is used by grids that load sub-content via AJAX
			$tr = $('<tr id="'+tr_id+'_form" class="ajaxcontent"></tr>');
			$tr.append('<td id="'+tr_id+'_form_td" style="display:none;padding-left:13px" colspan="100%"></td>');
			$tr.appendTo($tbody);
		});

		// hover/highlighting
		$tbody.find('tr').click(function(){ $(this).parent().find('tr').removeClass('selected'); $(this).addClass('selected'); });
		$tbody.find('tr').hover(
			function(){ $(this).addClass('highlight'); },
			function(){ $(this).removeClass('highlight'); }
		);
	}

	/**
	 * Draw the grid footer.
	 */
	this.foot = function() {
		var self = this;

		// TOTALS
		if(!self.cfg.options.nototals && self.state.totals.length > 0) {
			var $tr = $('<tr class="totals"></tr>');
			var i = 0;
			$.each(self.cfg.columns, function(colname,col){
				var $td = $('<td></td>');
				if(col.align) $td.attr('align', col.align);
				if(typeof self.state.totals[colname] == 'undefined') {
					$td.append('<strong>'+'Totals'+'</strong>');
				} else {
					if(self.cfg.totals[colname].format) {
						self.state.totals[colname] = $.sprintf(self.cfg.totals[colname].format, self.state.totals[colname]);
					}
					$td.append('<strong>'+totals[colname]+'</strong>');
				}
			});
			$td.appendTo($tr);
			$tr.appendTo($tfoot);
		}

		// MULTI-SELECT ACTIONS
		var multi_form = false;
		$.each(self.cfg.columns, function(colname,col){ if(/^_MULTI_/.test(colname)) multi_form = true; });
		if(self.data.records) {
			if(multi_form) {
				var $tr = $('<tr class="multi"></tr>');
				$.each(self.cfg.columns, function(colname,col){
					var $td = $('<td></td>');
					if(/^_MULTI_/.test(colname)) {
						$.each(col, function(action){ $td.append(action+"<br />"); });
					}
					$td.appendTo($tr);
				});
				$tr.appendTo($tfoot);
			}
		}
		if(multi_form) {
			// TODO: close multiselect form
		}

		// PAGINATION
		function _pagelink(self, pagenum, curpage, perpage) {
			if(pagenum == curpage) {
				return $('<span>'+pagenum+'</span>'); 
			} else {
				$a = $('<a href="#">'+pagenum+'</a>');
				$a.click(function(){
					self.state.curpage = pagenum;
					self.fetch();
					return false;
				});
				return $a;
			}
		}

		var numpages = 0;
		if(self.data.records) {
			numpages = Math.floor(self.data.records / self.state.perpage);
			if(self.data.records % self.state.perpage) numpages++;
		}
		if(!self.cfg.options.nopagination && numpages > 1) {
			var cp = self.state.curpage;
			var pp = self.state.perpage;
			var $tr = $('<tr class="pagination"></tr>');
			var $td = $('<td colspan="100%"></td>');

			var $div = $('<div style="float:right"></div>');
			$div.append(_pagelink(self, cp, cp, pp)).append(' ').prepend(' ');
			// left side
			if(cp > 1) {
				$div.prepend(_pagelink(self, cp-1, cp, pp)).prepend(' ');
				var left = cp - 2;
				if(left > 0) {
					if(left > 2) $div.prepend(' ... ');
					if(left > 1) $div.prepend(_pagelink(self, 2, cp, pp)).prepend(' ');
					$div.prepend(_pagelink(self, 1, cp, pp));
				}
			}
			// right side
			if(cp < numpages) { 
				$div.append(_pagelink(self, cp+1, cp, pp)).append(' ');
				var left = numpages - cp - 1;
				if(left > 0) {
					if(left > 2) $div.append(' ... ');
					if(left > 1) $div.append(_pagelink(self, numpages-1, cp, pp)).append(' ');
					$div.append(_pagelink(self, numpages, cp, pp)).append(' ');
				}
			}
			$td.append($div);

			var pp_opts = self.cfg.perpage_opts || [50,200,500,1000];
			var $sel = $(Pronto.UI.tag.select('p_pp', pp, pp_opts));
			$td.append('Showing ').append($sel).append(' per page (total records: '+self.data.records+')');

			$sel.change(function(){
				self.state.curpage = 1;
				self.state.perpage = $(this).val();
				self.fetch();
				return false;
			});

			$td.appendTo($tr);
			$tr.appendTo(self.$table);
		}
	}
}
