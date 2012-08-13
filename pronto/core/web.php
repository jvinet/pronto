<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * This file modified from WebPHP, which was in turn based on WEBPY
 * Original WebPHP Author: Nando Vieira <fnando dot vieira at gmail dot com>
 *
 * Description: Main dispatch controller.  Redirects all requests to proper
 *              page controller and provides common routines for
 *              code queuing and browser control.
 *
 */

if(!defined('CHARSET')) define('CHARSET', 'UTF-8');

if(!defined('SLASHES')) define('SLASHES', '\\/');
if(!defined('WDS'))     define('WDS', '/');
if(!defined('DS'))      define('DS', DIRECTORY_SEPARATOR);


class Web {
	var $context;
	var $controller;

	// profiling
	var $start_point;
	var $time_start;

	// debugging
	var $debug_messages;
	var $no_debug = false; // if true, debug output is suppressed, even when
	                       // the system-wide DEBUG constant it set.

	// output queues
	var $q_html_head;
	var $q_css_load;
	var $q_js_load;
	var $q_js_run;

	/*
	 * Constructor -- call as web(__FILE__)
	 *
	 * @param string $start_point Full path to main entry script (typically index.php)
	 */
	function Web($start_point)
	{
		$this->time_start  = array_sum(explode(" ",microtime()));
		$this->start_point = $start_point;
		$this->ajax        = false;
		$this->q_html_head = array();
		$this->q_css_load  = array();
		$this->q_js_load   = array();
		$this->q_js_run    = array();

		$this->debug_messages = array();

		$this->context = new StdClass();
		$this->context->protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
		$this->context->host     = $_SERVER['SERVER_NAME'];
		$this->context->port     = $_SERVER['SERVER_PORT'];
		$this->context->basedir  = dirname($start_point);
		$this->context->home     = $this->context->basedir;
		$this->context->method   = $_SERVER['REQUEST_METHOD'];
		$this->context->fullpath = $_SERVER['REQUEST_URI'];
		$this->context->ip       = $_SERVER['REMOTE_ADDR'];

		if(defined('DIR_WS_BASE')) {
			$path = WDS.DIR_WS_BASE.WDS;
		} else {
			// try to figure out our base path automagically...
			$len = strlen($_SERVER['DOCUMENT_ROOT']);
			$path = str_replace('\\', WDS, $this->context->basedir);
			$path = WDS.trim(substr($path, $len), WDS).WDS;        
		}
		$this->context->basepath = preg_replace("/(\/+)/", "/", $path);
		if($this->context->basepath == '') {
			$this->context->basepath = '/';
		}

		if(!defined('BASE_URL')) define('BASE_URL', $this->context->basepath);
		if(!defined('BASE_DIR')) define('BASE_DIR', $this->context->basedir);

		$basepath  = $this->context->basepath == WDS ? '' : $this->context->basepath;
		$full_url  = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $this->context->fullpath;
		$url_parts = @parse_url($full_url);
		$this->context->path = ltrim(substr($url_parts['path'], strlen($basepath)), WDS);
		$this->context->querystring = $url_parts['query'];
	}

	/********************************************************************
	 *
	 * DEBUGGING/PROFILING
	 *
	 ********************************************************************/

	/**
	 * Enable debugging information in a little dropdown panel.
	 * Also buffer any browser output so it can be displayed through the
	 * error handler if necessary.
	 */
	function enable_debug()
	{
		$this->queue_css_load('debug', url('/css/debug.css'));
		$this->queue_js_load('debug', url('/js/debug.js'));
		ob_start();
	}

	/********************************************************************
	 *
	 * CONTROLLER DISPATCH
	 *
	 ********************************************************************/

	/**
	 * Start dispatcher.  This begins the dispatch process of the framework.
	 * The requested URL is parsed and the respective page controller is called.
	 *
	 * @param array $urls Array of URL patterns to map Pages to URLs
	 * @param mixed $path If not FALSE, use this as the relative base path instead of autodetecting it
	 */
	function run($urls, $path=false)
	{
		if($path !== false) {
			$this->context->path = ltrim($path, WDS);
		}

		$uri_parts   = explode(WDS, trim($this->context->path, WDS));
		$strip_array = create_function('&$array', 'foreach ($array as $key => $item) if ($item === "") unset($array[$key]);');

		$possible_classes = array();
		foreach($urls as $class_name) {
			if(is_array($class_name)) {
				$possible_classes[] = $class_name[0];
			} else {
				$possible_classes[] = $class_name;
			}
		}

		if(defined('DISPATCH_URL')) {
			// shave off the dispatch URL before matching to a url regexp
			array_shift($uri_parts);
		}
		$path = str_replace('//', '/', WDS.join(WDS, $uri_parts).WDS);
		if ($path == '') {
			$path = '/';
		}

		foreach($urls as $re=>$class_name) {
			$action = array();
			$params = array();
			$re = sprintf('|^%s$|', $re);

			if(is_array($class_name)) {
				if(@is_array($class_name[2])) {
					foreach($class_name[2] as $k=>$v) {
						$params[$k] = $v;
					}
				}
				$action = array($class_name[1]);
				$class_name = $class_name[0];
			}

			if(preg_match($re, $path, $matches)) {
				array_shift($matches);
				$url_args = array();
				foreach($matches as $k=>$v) {
					if(!is_numeric($k)) {
						$url_args[$k] = $v;
						unset($matches[$k]);
					}
				}
				$actionarg = '';
				if(count($matches) > count($url_args)) {
					$actionarg = array_pop($matches);
				}

				$action_params = array_filter(explode('/', $actionarg), create_function('$a','return !empty($a);'));
				if(empty($action)) {
					$action = $action_params;
				} else {
					$action = array_merge($action, $action_params);
				}
				break;
			} else {
				$class_name = '';
				$action = array();
			}
		}
		if(empty($class_name)) {
			error("No matching page controller for path: $path");
			$this->notfound();
		}

		// Look for a special redirect URL (eg, ">/admin/user/list")
		if(substr($class_name, 0, 1) == '>') {
			$redir_url = trim(substr($class_name, 1));
			$this->redirect($redir_url);
			return;
		}

		define('CURRENT_URL', $this->current_url(true));

		// merge together request variables from various places
		$request_args = array_merge($url_args, $_GET, $_POST, $params);
		Registry::set('pronto:request_args', $request_args);

		$class =& Factory::page($class_name);
		if($class === false) {
			error("Class ($class_name) for REQUEST_URI not found. Check if you declared this class.");
			return $this->notfound();
		}

		$strip_array($uri_parts);

		// create a list of possible "wildcard" actions
		$wildcard_actions = array();
		$last = $this->context->method.'_';
		$args = array();
		for($i = 0; $i < count($action); $i++) {
			$last = $last . $action[$i] . '__';
			$args = array_slice($action, $i+1);
			$wildcard_actions[] = array($last, $args);
		}
		$wildcard_actions = array_reverse($wildcard_actions);

		// convert action array into a string, slashes to double underscores
		//   eg, /blog/view converts to blog__view()
		$oldaction = $action;
		$action = implode('__', $action);

		// Now build a list of all possible page actions, in order of priority.
		// Each element is a tuple of (method_name, url_arguments), but only
		// wildcard actions actually have url arguments.
		$methods = array_merge(
			array(array($this->context->method.'_'.$action, array())),
			$wildcard_actions,
			array(array($this->context->method, $oldaction))
		);
		if(empty($action)) {
			// lose the first method (GET_), it's not valid
			array_shift($methods);
		}

		// if request method is HEAD, then fall back to a GET if we can't
		// find a HEAD-specific method
		if($this->context->method == 'HEAD') {
			$methods_get = $methods;
			array_walk($methods_get, create_function('&$v,$k','$v[0] = preg_replace("|^HEAD|", "GET", $v[0]);'));
			$methods = array_merge($methods, $methods_get);
		}

		// scan through possible methods and take the first one
		$method_name = '';
		$method_args = array();
		foreach($methods as $m) {
			if(method_exists($class, $m[0])) {
				$method_name = $m[0];
				$method_args = $m[1];
				break;
			}
		}
		if(!$method_name) {
			error("Method <code>{$methods[0]}</code> not found. Check if you declared this method in the class <code>{$class_name}</code>.");
			return $this->notfound();
		}

		define('CONTROLLER', $class_name);
		define('ACTION',     $method_name);

		$this->controller =& $class;
		$this->ajax = $this->controller->ajax;

		// start buffering output
		if(!defined('OUTPUT_BUFFERING') || OUTPUT_BUFFERING !== false) ob_start();

		// Finally!  Call the page/action pair.
		call_user_func_array(array($class, $method_name), $method_args);

		// Cleanup
		$this->finish();
	}

	/**
	 * Run cleanup code necessary to finalize a web request.
	 * This code is automatically called from Web::run().  However,
	 * if your action needs to exit immediately instead of leaving
	 * your controller via the normal "return" command, then you can
	 * call this.
	 *
	 * Rule of thumb:  If you're tempted to use die() or exit() to
	 * stop script execution, then call this instead.
	 */
	function finish()
	{
		if(method_exists($this->controller, '__end__')) {
			call_user_func(array(&$this->controller, '__end__'));
		}
		if(function_exists('pronto_end')) {
			call_user_func('pronto_end');
		}

		// if there's data in the session and we haven't initialized
		// the session yet, do so now.
		if(!empty($_SESSION) && !session_id()) {
			$s = $_SESSION;
			start_session();
			$_SESSION = $s;
			// don't allow shared caching now that we have session data
			$this->expires(-3600);
		}

		// flush the output buffer
		if(ob_get_level()) ob_end_flush();

		/*
		 * Debug/Profiling Output
		 */
		if(DEBUG === true && !$this->ajax && !$this->no_debug) {
			echo '<div id="pronto_debug">';
			if(count($this->debug_messages)) {
				echo '<h2>Debug Messages</h2>';
				foreach($this->debug_messages as $v) {
					if($v['msg']) echo "<h4>{$v['loc']}</h4><pre>{$v['msg']}</pre>";
				}
			}
			if(defined('DB_NAME')) {
				$dbs = array('main');
			} else {
				$dbs = array();
				include(DIR_FS_APP.DS.'config'.DS.'databases.php');
				foreach($DATABASES as $key=>$dbcfg) $dbs[] = $key;
			}
			foreach($dbs as $dbname) {
				$db =& Registry::get('pronto:db:'.$dbname);
				if($db && $db->profile === true) {
					echo "<h2>Database Queries for $dbname</h2>";
					echo '<table class="query_profile" cellspacing="0">';
					foreach($db->profile_data as $i=>$a) {
						$t = $a['time'].' ms';
						echo '<tr><th>'.htmlspecialchars($a['query'])."</th><td>$t</td></tr>";
					}
					echo '<tr><th><b>Total Queries</b></th><td><b>'.count($db->profile_data).'</b></td></tr>';
					echo "</table>";
				}
			}
			echo '<h2>Summary</h2>';
			echo '<table class="query_profile" cellspacing="0">';
			$total = round((array_sum(explode(" ",microtime()))-$this->time_start)*1000, 3).' ms';
			echo "<tr><th><b>Total Execution Time (PHP+SQL)</b></th><td><b>$total</b></td></tr>";
			if(function_exists('memory_get_usage')) {
				$mem = sprintf("%.2f", memory_get_usage()/1024);
				echo "<tr><th><b>Memory Usage</b></th><td><b>$mem KB</b></td></tr>";
			}
			echo "</table>";
			echo '</div>';
			echo '<div id="pronto_debug_bar">DEBUG</div>';
		}

		// This is the official end of a web request.
		die;
	}

	/**
	 * Get the current URL, relative to our base web root
	 *
	 * @param bool $trim_querystring If set, the query string portion of the URL
	 *                               will not be included.
	 * @return string
	 */
	function current_url($trim_querystring=false)
	{
		$path = $this->context->path;
		if(defined('DISPATCH_URL')) {
			$path = substr($path, strlen(DISPATCH_URL));
		}
		$url = '/'.$path;
		if($this->context->querystring && !$trim_querystring) {
			$url .= '?'.$this->context->querystring;
		}
		return $url;
	}

	/**
	 * Redirect to the specified URL
	 *
	 * @param string $url
	 */
	function redirect($url)
	{
		// XXX: Technically, HTTP/1.1 requires an absolute URL in a
		// Location header, but the major web browsers don't seem to care.

		// Two slashes (//) is a shortcut for the url() function, which builds
		// us an internal URL relative to our DIR_WS_BASE.
		if(substr($url, 0, 2) == '//') $url = url(substr($url, 1));
		$this->header("Location", $url);
	}

	/********************************************************************
	 *
	 * ACCESS CONTROL
	 *
	 ********************************************************************/

	/**
	 * Check access levels to determine if the user is allowed to
	 * proceed.  If not logged in, send to login form; if logged in
	 * with insufficient privileges, send a 403, unless $always_login is true.
	 *
	 * @param string $key The access key required for this action
	 * @param string $login_url The relative URL to redirect to if user isn't logged in
	 * @param string $return_url Return URL to pass through the login form
	 * @param boolean $always_login If true, a logged-in user with insufficient
	 *                              privileges will be sent to the login form,
	 *                              instead of receiving a 403.
	 */
	function check_access($key, $login_url='', $return_url='', $always_login=false)
	{
		$access =& Registry::get('pronto:access');
		if(!$login_url) $login_url = url('/login');
		if(!$access->has_access($key)) {
			if($access->logged_in && !$always_login) {
				$this->forbidden();
			} else {
				if($return_url !== false) {
					if($return_url === '') {
						$return_url = $this->current_url();
					}
					$login_url .= '?return_url='.urlencode($return_url);
				}
				if($this->ajax) {
					$login_url .= '&_ajax=1';
				}
				$this->redirect($login_url);
			}
			exit;
		}
	}

	/**
	 * Check if the user has the access key required for this operation
	 *
	 * @return boolean
	 */
	function has_access($key)
	{
		$access =& Registry::get('pronto:access');
		return $access->has_access($key);
	}

	/**
	 * Collect username/password via HTTP Basic Authentication.
	 * Username can be found in $_SERVER['PHP_AUTH_USER']
	 * Password can be found in $_SERVER['PHP_AUTH_PW']
	 *
	 * @param string $realm 
	 */
	function http_auth($realm)
	{
		header('WWW-Authenticate: Basic realm="'.$realm.'"');
		header('HTTP/1.0 401 Unauthorized');
		echo "You are not authorized to view this page.";
		die;
	}

	/**
	 * Ensure that the current page is being accessed over HTTPS.
	 * If not, redirect to https://CURRENT_URL.
	 */
	function require_https()
	{
		if(isset($_SERVER['HTTPS'])) return;
		$url = absolute_url($this->current_url());
		$this->redirect(str_replace('http://', 'https://', $url));
		die;
	}

	/********************************************************************
	 *
	 * HTML/JS/CSS QUEUEING
	 *
	 ********************************************************************/

	/**
	 * Add text to the <head> area of the template
	 *
	 * @param string $key A unique key for this chunk of HTML
	 * @param string $val The HTML itself
	 */
	function queue_html_head($key, $val)
	{
		$this->q_html_head[$key] = $val;
	}

	function queue_css_load($key, $url)
	{
		if(!isset($this->q_css_load[$key])) {
			$this->q_css_load[$key] = $url;
		}
	}

	/**
	 * Schedule some JavaScript code to be run.  If the page is being
	 * delivered normally, then it will be executed in the <head> section
	 * of the template.  If the page is loaded via AJAX, then it will be
	 * executed immediately.
	 *
	 * @param string $key A unique key for this JavaScript.  If the key begins
	 *                    with a '+', then it will be run as-is.  Otherwise it will
	 *                    be run through a jQuery $(document).ready() call.
	 *                    If the key is blank, then we generate a hash string
	 *                    to use as the key.
	 * @param string $code The JS code
	 * @param boolean $overwrite If true, overwrite code using this key,
	 *                           otherwise append to it
	 */
	function queue_js_run($key, $code, $overwrite=true)
	{
		// avoid slashes or periods in the key
		$key = str_replace(array('/','.'), '__', $key);
		if(empty($key)) $key = 'a'.md5($code); // JS vars should start with a letter
		if($overwrite) {
			$this->q_js_run[$key] = $code;
		} else {
			$this->q_js_run[$key] .= "\n".$code;
		}
	}

	/**
	 * Same as queue_js_run, except this one will mimic a a remote load,
	 * eg, <script type="text/javascript" src="/path/to/js">
	 *
	 * @param string $key A unique key for this JavaScript
	 * @param string $url The URL to the JS file
	 */
	function queue_js_load($key, $url)
	{
		// avoid slashes or periods in the key
		$key = str_replace(array('/','.'), '__', $key);
		if(!isset($this->q_js_load[$key])) {
			$this->q_js_load[$key] = $url;
		}
	}


	/********************************************************************
	 *
	 * TEMPLATE RENDERING
	 *
	 ********************************************************************/

	/**
	 * Process queued JavaScript code to be passed back as an AJAX response.
	 */
	function ajax_load_queues()
	{
		$js = '';
		foreach($this->q_css_load as $k=>$v) {
			$js .= 'var f = document.createElement("link");';
			$js .= 'f.setAttribute("rel", "stylesheet");';
			$js .= 'f.setAttribute("type", "text/css");';
			$js .= 'f.setAttribute("href", "'.$v.'");';
			$js .= 'document.getElementsByTagName("head").item(0).appendChild(f);'."\n";
		}
		// We first request every file from the load queue.  Once all external
		// files are loaded, we process every code snippet in the run queue.
		// This ensures that "run" code can safely depend on external JS files.
		$js .= "pronto_js_load_queue = new Object();\n";
		$js .= "pronto_js_run_queue = new Object();\n";
		$js .= "function pronto_run_queue() { var l=0; for(var x in pronto_js_load_queue) l++; if(l==0) { for(var y in pronto_js_run_queue) { eval('pronto_js_run_queue.'+y+'();'); } } }\n";
		foreach($this->q_js_run as $k=>$v) {
			if(substr($k, 0, 1) == '+') $k = substr($k, 1);
			// remove commonly-used symbols that are not legal javascript var names
			$k = str_replace(array(':','/','-','.'), '_', $k);
			$js .= "pronto_js_run_queue.$k = function(){ $v }\n";
		}
		foreach($this->q_js_load as $k=>$v) {
			$js .= "pronto_js_load_queue.$k = true;\n";
			$k = str_replace(array(':','/','-','.'), '_', $k);
			$js .= "$.getScript(\"$v\", function(){ delete pronto_js_load_queue.$k; pronto_run_queue(); });\n";
		}
		// if the load queue is empty, then we have to execute the run queue immediately
		if(empty($this->q_js_load)) {
			$js .= "pronto_run_queue();";
		}
		return $js;
	}

	/**
	 * Render and output a template to answer an AJAX request.
	 *
	 * @param object $template Template object
	 * @param string $filename Template file to render
	 * @param array $jsvars Additional JavaScript variables to pass back
	 */
	function ajax_render($template, $filename, $jsvars=array())
	{
		$html = $filename ? $template->fetch($filename) : '';
		$json = array(
			'js'   => $this->ajax_load_queues(),
			'html' => $html
		);
		if(isset($jsvars['redirect_url'])) {
			// we're heading to a new URL, so don't process flash messages yet
			$json['exec'] = 'window.location.href="'.$jsvars['redirect_url'].'";';
		} else if(isset($_SESSION['_FLASH_MESSAGE']) && !isset($jsvars['reload'])) {
			$json['flash'] = $_SESSION['_FLASH_MESSAGE'];
			unset($_SESSION['_FLASH_MESSAGE']);
		}

		foreach($jsvars as $k=>$v) $json[$k] = $v;
		echo json_encode($json);
	}

	/**
	 * Convenience function to pass back JavaScript as an AJAX response.
	 *
	 * @param string $js JavaScript to pass back via ajax_render()
	 */
	function ajax_exec($js='')
	{
		$this->ajax_render(new Template(), '', array('exec'=>$js));
	}

	/**
	 * Render and output a template.
	 *
	 * @param object $template Template object
	 * @param string $filename Template file to render
	 * @param array $vars Additional variables to include in template
	 * @param string $layout Layout template to use.  Leave empty/false for no layout.
	 */
	function render($template, $filename, $vars=array(), $layout='')
	{
		if($layout) {
			$vars['CONTENT_FOR_LAYOUT'] = $template->fetch($filename, $vars);
			// use the absolute path so the template doesn't look in a module
			$filename = DIR_FS_APP.DS.'templates'.DS.$layout;
		}
		if(isset($_SESSION['_FLASH_MESSAGE'])) {
			$vars['FLASH_MESSAGE'] = $_SESSION['_FLASH_MESSAGE'];
			unset($_SESSION['_FLASH_MESSAGE']);
		}

		// insert <head> data
		$head = '';
		foreach($this->q_html_head as $k=>$v) $head .= $v;
		if(!$this->ajax) {
			foreach($this->q_css_load as $k=>$v) {
				$head .= '<link rel="stylesheet" type="text/css" href="'.$v.'" />'."\n";
			}
			foreach($this->q_js_load as $k=>$v) {
				$head .= '<script type="text/javascript" src="'.$v.'"></script>';
			}
			if(!empty($this->q_js_run)) {
				$head .= '<script type="text/javascript">'."\n";
				foreach($this->q_js_run as $k=>$v) {
					if(substr($k, 0, 1) == '+') {
						$head .= "$v\n";
					} else {
						$head .= '$(document).ready(function(){'.$v.'});'."\n";
					}
				}
				$head .= "</script>\n";
			}
		}
		$vars['HTML_HEAD'] = $head;

		echo $template->fetch($filename, $vars);
	}

	/********************************************************************
	 *
	 * HTTP HEADER CONTROL
	 *
	 ********************************************************************/

	/**
	 * Do the same thing as header native function.
	 * The difference is that you don't need to concatenate header name and its value.
	 *
	 * @param string $name
	 * @param string $value
	 */

	function header($name, $value)
	{
		header($name.': '.$value);
	}

	/**
	 * Sets "Expires" and "Cache-Control" headers
	 * 
	 * @param int $secs Number of seconds until page content expires.
	 *                  If set to zero, then cache will be disabled.
	 */
	function expires($secs=0)
	{
		if($secs <= 0) {
			$this->header('Cache-Control', "no-cache");
			$this->header('Pragma', "no-cache");
		} else {
			$this->header('Cache-Control', "public,max-age=$secs");
			$this->header('Pragma', "");
		}
		$time = strtotime(gmdate('D, d M Y H:i:s')) + $secs;
		$this->header('Expires', gmdate('D, d M Y H:i:s', $time).' GMT');
	}

	/**
	 * Sets "Content-type" header
	 *
	 * @param string $type
	 */
	function content_type($type='')
	{
		if(empty($type)) $type = 'text/html; charset='.CHARSET;
		$this->header('Content-Type', $type);
	}

	/**
	 * Sets "Last-modified" header.
	 *
	 * @param 
	 */
	function lastmodified($datetime)
	{
		$this->header('Last-Modified', $datetime);
	}

	/**
	 * Sets "Status" header.
	 *
	 * @param string $status
	 */
	function status($status)
	{
		$this->context->status = $status;
		header("{$_SERVER['SERVER_PROTOCOL']} $status");
	}

	/**
	 * Sets "Not found" status.
	 * Executes notfound function, if exists.
	 */
	function notfound()
	{
		$this->status('404 Not Found');

		$p = DIR_FS_APP.DS.'pages'.DS.'404.php';
		if(file_exists($p)) require_once($p);

		if(function_exists('notfound')) {
			call_user_func('notfound', $this);
		} else {
			echo 'File Not Found';
		}
	}

	/**
	 * Sets "Forbidden" status.
	 * Executes forbidden function, if exists.
	 */
	function forbidden()
	{
		$this->status('403 Forbidden');

		$p = DIR_FS_APP.DS.'pages'.DS.'404.php';
		if(file_exists($p)) require_once($p);

		if(function_exists('forbidden')) {
			call_user_func('forbidden', $this);
		} else {
			echo 'Forbidden';
		}
	}

	/**
	 * Sets "Bad request" status.
	 * Executes badrequest function, if exists.
	 */
	function badrequest()
	{
		$this->status('400 Bad Request');

		$p = DIR_FS_APP.DS.'pages'.DS.'404.php';
		if(file_exists($p)) require_once($p);

		if(function_exists('badrequest')) {
			call_user_func('badrequest', $this);
		} else {
			echo 'Bad Request';
		}
	}

	/**
	 * Sets "Internal Server Error" status.
	 * Executes internalerror function, if exists.
	 */
	function internalerror()
	{
		$this->status('500 Internal Server Error');

		$p = DIR_FS_APP.DS.'pages'.DS.'404.php';
		if(file_exists($p)) require_once($p);

		if(function_exists('internalerror')) {
			call_user_func('internalerror', $this);
		} else {
			echo 'Internal Server Error';
		}
	}
}

?>
