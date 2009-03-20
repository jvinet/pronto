<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: CSS proxy script.  Returns the requested CSS page after
 *              massaging relative URLs to point to the proper location.
 *
 **/
define('DS', DIRECTORY_SEPARATOR);

$urlbase = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/'));

if(!isset($_GET['c'])) die;
$fn = urldecode($_GET['c']).'.css';

// sanity/safety checks
if(ereg('\.\.', $fn)) die;
$base = dirname(__FILE__).DS.'css';
$path = $base.DS.$fn;
if(substr(dirname($path), 0, strlen($base)) != $base) die;
if(!file_exists($path)) die;

// only send back the CSS if it's been modified since the last fetch
$lastmod = filemtime($path);
if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
	$t = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
	if($lastmod <= $t) {
		header("HTTP/1.0 304 Not Modified");
		exit;
	}
}

// fix all url() strings to point to correct locations
$css = implode('', file($path));
$css = str_replace('url(/', 'url('.$urlbase.'/', $css);

header('Last-Modified: '.gmdate('D, d M Y H:i:s \G\M\T', $lastmod));
header('Content-Type: text/css');
echo $css;
die;

?>
