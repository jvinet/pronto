<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: A logging facility.
 *
 **/

if(!defined('DIR_FS_LOG')) define('DIR_FS_LOG', DIR_FS_BASE.DS.'log');

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

		$this->routes = array(
			'pronto(:.*)*' => array(
				'.*' => 'pronto.log'
			),
			'app(:.*)*' => array(
				'.*' => 'app.log'
			)
		);
	}

	function add_routes($routes)
	{
		foreach($routes as $k=>$v) $this->routes[$k] = $v;
	}

	function msg($facility, $priority, $message)
	{
		foreach($this->routes as $fac=>$v) {
			if(!preg_match("/$fac/", $facility)) continue;
			echo "matched $fac<br>\n";
			if(is_array($v)) {
				foreach($v as $pri=>$fn) {
					if(!preg_match("/$pri/", $priority)) continue;
					$this->_log_msg($fn, $facility, $priority, $message);
				}
			} else {
				$this->_log_msg($fn, $facility, $priority, $message);
			}
		}
	}

	function _log_msg($filename, $facility, $priority, $message)
	{
		if(!is_resource($this->files[$filename])) {
			$path = DIR_FS_LOG.DS.$filename;
			$this->files[$filename] = @fopen($path, 'a');
			if(!is_resource($this->files[$filename])) {
				trigger_error("Cannot open log file: $path");
				return;
			}
		}
		$dt = date('Y-m-d H:i:s');
		$msg = "[$dt] [$facility:$priority] $message\n";
		fputs($this->files[$filename], $msg);
	}
}

/**
 * Shortcut function.
 */
function l() {
	$logger =& Registry::get('pronto:logger');

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

	echo "$facility:$priority $message<br>\n";

	$logger->msg($facility, $priority, $message);
}

// Start up an instance immediately -- it will be the only one.
$l = new Logger();
Registry::set('pronto:logger', $l);


?>
