<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Utility functions that are available to all Pronto components.
 *
 **/

/********************************************************************
 *
 * URL ASSEMBLY FUNCTIONS
 *
 ********************************************************************/

/**
 * Build a relative URL.  This function can be called in two different
 * ways.
 *
 * (1) If called with (controller,action) parameters, it will scan
 * through the URL routing table and find the first match (multiple
 * routes can point to the same controller/action), using that for
 * the resulting URL.
 *
 * (2) If called with an URL fragment (eg, /blog/post), it will build
 * a relative URL using the DIR_WS_BASE constant, set in your
 * app/config/config.php.
 *
 * The context of the function parameters varies based on the context
 * with which this function is called.
 *
 * @param string $controller Either the name of the controller, or the
 *                           URL fragment.
 * @param mixed $action Either the name of the action, or blank/unused.
 *                      If called via method (2), then this may be set
 *                      to FALSE, which will ignore DISPATCH_URL even
 *                      if it's set.  This is needed for building URLs
 *                      to static resources, such as images, JS, CSS,
 *                      things that shouldn't be routed through the
 *                      DISPATCH_URL.
 *
 * @return string The resulting relative URL
 *
 */
function url($controller, $action='')
{
	// Make sure we haven't been passed a full URL already...
	if(strstr($controller, '://') !== false) return $controller;

	// We were passed an URL fragment, so create a full (relative)
	// URL with the fragment
	if(strstr($controller, '/') !== false) {
		$url = $controller;
		if(defined('DISPATCH_URL') && $action !== true) {
			$url = DISPATCH_URL.$url;
		}
		return rtrim(DIR_WS_BASE, '/').$url;
	}

	$controller = strtolower($controller);
	$action     = strtolower($action);

	// We were passed a (controller,action) tuple, so build a
	// valid URL fragment with it, then call ourself again
	// to get the full (relative) URL
	$urls = Registry::get('pronto:urls');
	foreach($urls as $regex=>$c) {
		// We can't handle URLs with anything more than the standard
		// (.*) subpattern in them, as they usually have additional
		// arguments in them (eg, /user/123/edit).  So we just remove
		// them.
		$regex = preg_replace('|\([^\.][^\*].*\)|', '', $regex);
		$regex = str_replace('//', '/', $regex);

		$a = '';
		if(is_array($c)) list($c,$a) = $c;
		$c = strtolower($c);
		$a = strtolower($a);

		if($a) {
			if($c == $controller && $a == $action) {
				return url($regex);
			}
		} else if($c == $controller) {
			return url(str_replace(
				array('(.*)', '__'),
				array($action, '/'), $regex));
		}
	}
	// no match, have to return something...
	return url('/');
}

/**
 * Build an absolute URL.  See the prototype for the url() function
 * for a description of the two contexts for which this function
 * can be called.
 *
 * @param string $controller Either the name of the controller, or the
 *                           URL fragment.
 * @param mixed $action Either the name of the action, or blank/unused.
 *                      If called via method (2), then this may be set
 *                      to FALSE, which will ignore DISPATCH_URL even
 *                      if it's set.  This is needed for building URLs
 *                      to static resources, such as images, JS, CSS,
 *                      things that shouldn't be routed through the
 *                      DISPATCH_URL.
 *
 * @return string The resulting absolute URL
 */
function absolute_url($controller, $action='')
{
	// Make sure we haven't been passed a full URL already...
	if(strstr($controller, '://') !== false) return $controller;

	if(defined('SITE_URL_BASE') && SITE_URL_BASE != '') {
		$url = rtrim(SITE_URL_BASE, '/');
	} else {
		$proto = $_SERVER['HTTPS'] ? 'https' : 'http';
		$host  = $_SERVER['SERVER_NAME'];
		$port  = $_SERVER['SERVER_PORT'];

		$url = $proto.'://'.$host;
		if($proto == 'http'  && $port != 80)  $url .= ":$port";
		if($proto == 'https' && $port != 443) $url .= ":$port";
	}

	return $url.url($controller, $action);
}

/********************************************************************
 *
 * DEBUG AND ERROR HANDLING
 *
 ********************************************************************/

/**
 * Output a string or variable for debugging purposes.
 *
 * @param boolean $queue If false, output data directly to browser.  Otherwise,
 *                       queue it and output it in the designated debug panel.
 */
function debug($var, $queue=true)
{
	$web =& Registry::get('pronto:web');
	if($queue && is_object($web)) {
		$bt = debug_backtrace();
		$loc = "{$bt[0]['file']}:{$bt[0]['line']}";
		$web->debug_messages[] = array('loc'=>$loc, 'msg'=>print_r($var, true));
	} else {
		if(is_object($web)) {
			echo '<pre>';
			print_r($var);
			echo '</pre>';
		} else {
			print_r($var);
		}
	}
}

/**
 * Trigger an error through PHP's E_USER_ERROR level.  Errors will
 * only be triggered if Debug mode is enabled.
 *
 * @param string $message
 */
function error($message)
{
	if(DEBUG !== true) return;
	trigger_error($message, E_USER_ERROR);
}

/**
 * Default error handler for Production mode.
 */
function pronto_error($errno, $message, $file, $line)
{
	// Don't report any errors if error_reporting == 0
	if(error_reporting() == 0) return;

	// Ignore E_STRICT or E_NOTICE
	if(in_array($errno, array(2048,8))) {
		return;
	}

	// Special handling for AJAX mode
	$web =& Registry::get('pronto:web');
	if(DEBUG === true && is_object($web) && $web->ajax) {
		// Send back an error through the AJAX channel
		$msg = "$file:$line\\n\\n" . strip_tags($message);
		$web->ajax_exec("alert('".str_replace("'", "\\'", $msg)."');");
		die;
	}

	if($errno == E_USER_ERROR) {
		$display_source = 'none';
	} else {
		$display_source = 'block';
	}

	$constants  = @get_defined_constants(true);
	$constants  = $constants['user'];
	$data       = array('$_POST'=>$_POST, '$_GET'=>$_GET, '$_COOKIE'=>$_COOKIE, '$_SESSION'=>$_SESSION, '$_SERVER'=>$_SERVER, '$_ENV'=>$_ENV, 'CONSTANTS'=>$constants);

	$tvars = array(
		'errno'   => $errno,
		'file'    => $file,
		'line'    => $line,
		'message' => $message,
		'uri'     => $_SERVER['REQUEST_URI'],
		'method'  => $_SERVER['REQUEST_METHOD']
	);

	if(is_file($file)) {
		$fh  = file($file);
		$len = sizeof($fh);
		$tvars['source_start'] = max(0, $line-5);
		$tvars['source_lines'] = array_slice($fh, $tvars['source_start'], 10);
	}

	// Function Backtrace
	$tvars['backtrace'] = array();
	$bt = debug_backtrace();
	array_shift($bt);
	foreach($bt as $tp) {
		$fn = $caller = '';
		if($tp['object']) {
			$fn .= get_class($tp['object']).'::';
		} else if($tp['class']) {
			$fn .= "{$tp['class']}::";
		}
		if($tp['function']) $fn .= "{$tp['function']}()";
		if($tp['file']) $caller .= "{$tp['file']}:{$tp['line']}";
		$tvars['backtrace'][] = array('function'=>$fn, 'caller'=>$caller);
	}

	// Global Data (GET/POST/COOKIE/SERVER etc.)
	$tvars['data'] = array();
	foreach($data as $name=>$global) {
		$tvars['data'][$name] = array();
		if(!is_array($global)) continue;
		foreach($global as $k=>$v) $tvars['data'][$name][$k] = var_export($v, true);
	}

	$tvars['output'] = ob_get_contents();
	ob_end_clean();

	// Rudimentary templating.  Output destination varies depending on
	// which profile we're in and whether we're in Debug mode or not.
	extract($tvars, EXTR_OVERWRITE);
	ob_start();
	if(DEBUG === true) {
		// Display to screen/browser
		if(is_object($web)) {
			require(DIR_FS_PRONTO.DS.'core'.DS.'error'.DS.'html.php');
		} else {
			require(DIR_FS_PRONTO.DS.'core'.DS.'error'.DS.'text.php');
		}
		$content = ob_get_contents();
		ob_end_clean();
		echo "\n$content\n";
	} else {
		// Use text format and email results
		require(DIR_FS_PRONTO.DS.'core'.DS.'error'.DS.'text.php');
		$content = ob_get_contents();
		ob_end_clean();
		@mail(ADMIN_EMAIL, SITE_NAME.": Error", $content);

		if(is_object($web)) {
			// call Web's error handler, which usually renders an error page to
			// the end user.
			$web->internalerror();
		}
	}
	die;
}

/********************************************************************
 *
 * ARRAY UTILITIES AND OTHER SHORTCUTS
 *
 ********************************************************************/

/**
 * Convert a numeric array into an associative one.
 */
function array_hash($arr)
{
	$hash = array();
	foreach($arr as $el) {
		$hash[$el] = $el;
	}
	return $hash;
}

/**
 * Return a simple Yes/No array that uses 1/0 as keys.  This is useful
 * for dropdown/radio widgets that map 1/0 to Yes/No values.
 */
function array_yesno()
{
	return array('1'=>__('Yes'), '0'=>__('No'));
}

/**
 * Similar to array_yesno(), but returns the actual string value
 * ("Yes" or "No") based on the integer provided.
 */
function yesno($int)
{
	return $int > 0 ? __('Yes') : __('No');
}

/**
 * Return an array consisting of a specific element of each
 * sub-array.
 *
 * @param array $arr A 2-d array.
 * @param string $key The key of the element to fetch.
 * @param array A 1-d array.
 */
function array_extract($arr, $key)
{
	$ret = array();
	foreach($arr as $v) $ret[] = $v[$key];
	return $ret;
}

/**
 * Alias for array_sub()
 */
function array_sub($arr, $key) {
	return array_extract($arr, $key);
}

/**
 * Insert a new element in an associative array.  The element will be
 * inserted directly after the $after element.
 *
 * @param array $arr Array to insert the new element in.
 * @param string $key The key of the new element.
 * @param mixed $val The value of the new element.
 * @param string $after The key of the element that the new element will
 *                      be inserted after.
 */
function array_insert(&$arr, $key, $val, $after)
{
	$new = array();
	foreach($arr as $k=>$v) {
		$new[$k] = $v;
		if($k == $after) $new[$key] = $val;
	}
	$arr = $new;
}

/**
 * Check and enforce a variable's data type.
 *
 * @param mixed $var The variable
 * @param mixed $type One of: array, string, int, float
 */
function assert_type(&$var, $type)
{
	switch($type) {
		case 'array':
			// only put $var in the new array if it's not zero/false/null
			if(!is_array($var)) $var = $var ? array($var) : array();
			break;
		case 'string': 
			if(!is_string($var)) $var = is_array($var) ? "".current($var) : "$var";
			break;
		case 'int':
			if(!is_int($var)) $var = is_array($var) ? (int)current($var) : (int)$var;
			break;
		case 'float':
			if(!is_float($var)) $var = is_array($var) ? (float)current($var) : (float)$var;
			break;
		case 'date':
			if(!ereg('^[12][0-9]{3}-[01][0-9]-[0123][0-9]$', $var)) $var = date('Y-m-d');
			break;
	}
	return $var;
}

/**
 * Shortcut function for Web::has_access()
 */
function a($key)
{
	$access =& Registry::get('pronto:access');
	// If the Access object isn't present, then we're probably being
	// called by a commandline script.  The a() access-check shortcut is
	// sometimes used in models, so we have to return something.
	return is_object($access) ? $access->has_access($key) : true;
}

/***********************************************************************
 * MULTIBYTE STRING FUNCTIONS
 *   Pronto uses the mb_* string functions internally, but we want
 *   to continue working even if the mbstring extension is not loaded,
 *   even though the string functions will not operate on UTF8
 *   correctly.
 ***********************************************************************/
if(!function_exists('mb_strlen')) {
	function mb_send_mail($to, $subj, $message, $hdrs='', $param='') {
		return mail($to, $subj, $message, $hdrs, $param);
	}
	function mb_strlen($s) {
		return strlen($s);
	}
	function mb_strpos($haystack, $needle, $offset=0) {
		return strpos($haystack, $needle, $offset);
	}
	function mb_strrpos($haystack, $needle, $offset=0) {
		return strrpos($haystack, $needle, $offset);
	}
	function mb_substr($str, $start, $length=0) {
		return substr($str, $start, $length);
	}
	function mb_strtolower($str) {
		return strtolower($str);
	}
	function mb_strtoupper($str) {
		return strtoupper($str);
	}
	function mb_substr_count($haystack, $needle) {
		return substr_count($haystack, $needle);
	}
	function mb_ereg($pattern, $string, $regs=array()) {
		return ereg($pattern, $string, $regs);
	}
	function mb_eregi($pattern, $string, $regs=array()) {
		return eregi($pattern, $string, $regs);
	}
	function mb_ereg_replace($pattern, $replacement, $string, $option='') {
		return ereg_replace($pattern, $replacement, $string, $option);
	}
	function mb_eregi_replace($pattern, $replacement, $string, $option='') {
		return eregi_replace($pattern, $replacement, $string, $option);
	}
	function mb_split($pattern, $string, $limit=-1) {
		return split($pattern, $string, $limit);
	}
}

?>
