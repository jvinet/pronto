/**
 * The ubiquitous Pronto class.  Can be referenced from templates
 * as "pronto".
 */
Pronto = function(web_root) {
	this.CSS_LOADED  = new Array();
	this.JS_LOADED   = new Array();

	this.DIR_WS_BASE = web_root;

	this.guid = 1;

	/**
	 * Build a URL relative to the base web root
	 */
	this.url = function(relurl) {
		return this.DIR_WS_BASE + relurl;
	}

	/**
	 * Add one or more var=val query-string pair to an URL.
	 */
	this.qs_add = function(url, params) {
		var qs = this.qs_explode(url);
		var url = url.indexOf('?') > -1 ? url.substr(0, url.indexOf('?')) : url;
		for(var x in params) qs[x] = params[x];
		return url + '?' + this.qs_implode(qs);
	}

	/**
	 * Remove one or more var=val query-string pair from an URL.
	 */
	this.qs_remove = function(url, param_names) {
		var qs = this.qs_explode(url);
		var url = url.indexOf('?') > -1 ? url.substr(0, url.indexOf('?')) : url;
		for(var i = 0; i < param_names.length; i++) {
			for(var x in qs) if(x == param_names[i]) delete qs[x];
		}
		return url + '?' + this.qs_implode(qs);
	}

	/**
	 * Extract all var=val sets from an URL's query string, returning
	 * them as a map.
	 */
	this.qs_explode = function(url) {
		var parts = {};
		var p = url.split('?');
		var q = p[1].split('&');
		for(var x in q) {
			var a = q[x].split('=');
			parts[a[0]] = a[1];
		}
		return parts;
	}

	/**
	 * Assemble a proper URL query string using the map provided.
	 */
	this.qs_implode = function(parts) {
		var ret = '';
		for(var x in parts) ret += x + '=' + parts[x] + '&';
		return ret.length ? ret.substr(0, ret.length-1) : '';
	}

	/**
	 * Load a CSS file.
	 * @param string filename Filename, relative to the app's /css directory.
	 *                        Do not include the .css extension.
	 */
	this.load_css = function(filename) {
		if(jQuery.inArray(filename, this.CSS_LOADED) == -1) {
			var f = document.createElement("link");
			f.setAttribute("rel", "stylesheet");
			f.setAttribute("type", "text/css");
			f.setAttribute("href", this.url("/css/"+filename+".css"));
			document.getElementsByTagName("head").item(0).appendChild(f);

			this.CSS_LOADED.push(filename);
		}
	}

	/**
	 * Load a JavaScript file.
	 * @param string filename Filename, relative to the app's /js directory.
	 *                        Do not include the .js extension.
	 * @param function func Optional function to execute once script is loaded.
	 *                      If the JavaScript file has already been loaded,
	 *                      this function will be executed immediately.
	 */
	this.load_js = function(filename, func) {
		var func = func || function(){};
		if(jQuery.inArray(filename, this.JS_LOADED) == -1) {
			jQuery.getScript(this.url("/js/"+filename+".js"), func);
			this.JS_LOADED.push(filename);
		} else {
			func();
		}
	}

	/**
	 * Retrieve the browser's timezone offset (in seconds) and store it in
	 * a cookie.
	 */
	this.tz_offset = function(cookie_name) {
		var cn = cookie_name || "tz_offset";
		var d = new Date();
		var tzoff = d.getTimezoneOffset() * 60 * -1;
		d.setDate(d.getDate() + 1); // expires in 1 day
		this.set_cookie(cn, tzoff, d);
	}

	/**
	 * Set a cookie.
	 *
	 * @param name    string Cookie name
	 * @param value   string Cookie data
	 * @param expires mixed  Can be a Date object, a string (eg, "04/23/1999") or
	 *                      a number (absolute time in seconds). If no expiry
	 *                      time is passed, defaults to browser close.
	 */
	this.set_cookie = function(name, value, expires) {
		var d = false;
		if(expires) {
			switch(typeof expires) {
				case 'string': d = new Date(Date.parse(expires)); break;
				case 'number': d = new Date(expires*1000); break;
				case 'object': break;
			}
		}
		var ck = name + "="  + escape(value);
		if(d) ck += ";expires=" + d.toGMTString();
		document.cookie = ck;
	}
};
