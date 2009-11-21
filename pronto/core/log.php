<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: A logging facility.
 *
 * By default, there are two facilities, but you can add more:
 *   - Pronto
 *   - App
 *
 * These are the default priorities, in order:
 *   - debug
 *   - info
 *   - warning
 *   - error
 *
 **/

if(!defined('DIR_FS_LOG')) define('DIR_FS_LOG', DIR_FS_APP.DS.'log');

class Logger
{
	var $routes;
	var $files;

	/**
	 * Constructor
	 */
	function Logger($routes=null)
	{
		// A 2-D array.
		// First key is a regexp to match facility.
		// Second key is a regexp to match priority.
		// Value is a filename.
		if($routes) {
			$this->routes = $routes;
			return;
		}

		// Default routes - used if no routes were passed in.
		if(DEBUG !== true) {
			// If in Production mode, ignore priority levels "debug" and "info"
			//
			// NB: The ! prefix is not valid in regular expressions, but we parse
			//     it correctly here.  Don't use it elsewhere - it won't work!
			$this->routes = array(
				'pronto(:.*)*' => array('!(debug|info)' => 'pronto.log'),
				'app(:.*)*'    => array('!(debug|info)' => 'app.log')
			);
		} else {
			// Debug mode logging -- capture all priorities
			$this->routes = array(
				'pronto(:.*)*' => array('.*' => 'pronto.log'),
				'app(:.*)*'    => array('.*' => 'app.log')
			);
		}
	}

	/**
	 * Add multiple log routes.
	 */
	function add_routes($routes)
	{
		foreach($routes as $k=>$v) $this->routes[$k] = $v;
	}

	/**
	 * Empty routing table.
	 */
	function clear_routes()
	{
		$this->routes = array();
	}


	/**
	 * Log a message to the specific facility and priority.
	 * A log message can match more than one route.
	 */
	function msg($facility, $priority, $message)
	{
		foreach($this->routes as $fac=>$v) {
			$m = array();
			if(!$this->_preg($fac, $facility, $m)) continue;
			for($i = 1; $i < count($m); $i++) $v = str_replace("\\$i", $m[$i], $v);
			if(is_array($v)) {
				foreach($v as $pri=>$fn) {
					$m = array();
					if(!$this->_preg($pri, $priority, $m)) continue;
					for($i = 1; $i < count($m); $i++) $fn = str_replace("\\$i", $m[$i], $fn);
					$this->_log_msg($fn, $facility, $priority, $message);
				}
			} else {
				$this->_log_msg($v, $facility, $priority, $message);
			}
		}
	}

	/**
	 * A simple frontend to preg_match.  Allows a prefix of '!' which negates
	 * the regular expression.
	 *
	 * Don't include the RE delimiters (eg, "/"), they will be added.
	 */
	function _preg($re, $str, &$matches)
	{
		if(substr($re, 0, 1) == '!') {
			$re = substr($re, 1);
			return !preg_match("/^$re$/", $str);
		}
		return preg_match("/^$re$/", $str, $matches);
	}

	function _log_msg($filename, $facility, $priority, $message)
	{
		// silently fail if the log directory doesn't exist
		if(!is_dir(DIR_FS_LOG)) return;

		if(!is_resource($this->files[$filename])) {
			// prepend the log directory path if the filepath is not absolute
			$path = substr($filename, 0, 1) == DS ? $filename : DIR_FS_LOG.DS.$filename;
			$this->files[$filename] = @fopen($path, 'a');
			if(!is_resource($this->files[$filename])) {
				trigger_error("Cannot open log file: $path");
				return;
			}
		}
		$dt = date('Y-m-d H:i:s');
		$msg = "[$dt] [$facility.$priority] $message\n";
		fputs($this->files[$filename], $msg);
	}
}

/**
 * Shortcut function.
 */
function l() {
	$logger =& Registry::get('pronto:logger');
	if(!$logger) return;

	$args = func_get_args();
	if(count($args) == 1) {
		$facility = 'app';
		$priority = 'info';
		$message = $args[0];
	} else if(count($args) == 2) {
		$facility = 'app';
		$priority = $args[0];
		$message = $args[1];
	} else {
		$facility = $args[0];
		$priority = $args[1];
		$message  = vsprintf($args[2], array_slice($args, 3));
	}

	$logger->msg($facility, $priority, $message);
}

?>
