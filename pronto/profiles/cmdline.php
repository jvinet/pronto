<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Execution profile for a commandline-based process.
 *
 **/

set_time_limit(0);
if(!defined('PROFILE')) define('PROFILE', 'cmdline');

// Core Libraries
require_once(DIR_FS_PRONTO.DS.'core'.DS.'registry.php');
require_once(DIR_FS_PRONTO.DS.'core'.DS.'log.php');
require_once(DIR_FS_PRONTO.DS.'core'.DS.'factory.php');
if(phpversion() < 5) {
	require_once(DIR_FS_PRONTO.DS.'core'.DS.'lazyload_php4.php');
} else {
	require_once(DIR_FS_PRONTO.DS.'core'.DS.'lazyload_php5.php');
}
require_once(DIR_FS_PRONTO.DS.'core'.DS.'template.php');
require_once(DIR_FS_PRONTO.DS.'core'.DS.'validator.php');
require_once(DIR_FS_PRONTO.DS.'core'.DS.'i18n.php');
require_once(DIR_FS_PRONTO.DS.'core'.DS.'cache.php');
require_once(DIR_FS_PRONTO.DS.'core'.DS.'util.php');

// Error handler for Debug and Production mode, defined in core/util.php.
// To override it, define your own pronto_error() function in
// app/profiles/web.php or somewhere else that is included before this file.
set_error_handler('pronto_error');
if(phpversion() >= 5) {
	set_exception_handler('pronto_exception');
}

// URL route config
require_once(DIR_FS_APP.DS.'config'.DS.'urls.php');
Registry::set('pronto:urls', $URLS);
unset($URLS);

/************************************************************************
 * LOG INITIALIZATION
 ************************************************************************/
$l = new Logger();
if(file_exists(DIR_FS_APP.DS.'config'.DS.'log.php')) {
	require_once(DIR_FS_APP.DS.'config'.DS.'log.php');
	$l->clear_routes();
	$l->add_routes($LOG_ROUTES);
}
Registry::set('pronto:logger', $l);
unset($l);

/************************************************************************
 * CACHE INITIALIZATION
 ************************************************************************/
if(USE_CACHE === true && defined('CACHE_DRIVER')) {
	require_once(DIR_FS_PRONTO.DS.'core'.DS.'cache'.DS.CACHE_DRIVER.'.php');
	$cn = "Cache_".CACHE_DRIVER;
	$cache = new $cn();
	unset($cn);
} else {
	$cache = new Cache();
}
$cache->gc();
Registry::set('pronto:cache', $cache);

/************************************************************************
 * MODULE INITIALIZATION
 ************************************************************************/
if(defined('MODULES')) {
	foreach(explode(' ', MODULES) as $modname) {
		$modpath = DIR_FS_APP.DS.'modules'.DS.$modname.DS.'config'.DS;
		if(file_exists($modpath.'config.php')) require_once($modpath.'config.php');
		if(file_exists($modpath.'urls.php')) {
			$old = Registry::get('pronto:urls');
			require_once($modpath.'urls.php');
			$URLS += $old;
			Registry::set('pronto:urls', $URLS);
			unset($old, $URLS);
		}
		if(file_exists($modpath.'log.php')) {
			$l =& Registry::get('pronto:logger');
			require_once($modpath.'log.php');
			$l->add_routes($LOG_ROUTES);
			unset($l);
		}
	}
	unset($modname, $modpath);
}

/************************************************************************
 * CHARACTER SET
 ************************************************************************/
ini_set('default_charset', 'UTF-8');
if(extension_loaded('mbstring')) {
	mb_internal_encoding(CHARSET);
}

/************************************************************************
 * CONNECT TO DATABASE(S)
 ************************************************************************/
if(defined('DB_NAME')) {
	$db =& Factory::db(array(
		'dsn'  => DB_DSN,
		'file' => DB_FILE,
		'host' => DB_HOST,
		'user' => DB_USER,
		'pass' => DB_PASS,
		'name' => DB_NAME));
	Registry::set('pronto:db:main', $db);
} else {
	require_once(DIR_FS_APP.DS.'config'.DS.'databases.php');
	foreach($DATABASES as $key=>$dbcfg) {
		$db =& Factory::db($dbcfg);
		Registry::set('pronto:db:'.$key, $db);
	}
	// we leave $db set for scripts that expect it
	unset($key, $dbcfg);
}

/************************************************************************
 * INTERNATIONALIZATION
 ************************************************************************/
$i18n = new I18N();
$i18n->autoset_language('en');
define('LANG', $i18n->get_language());
Registry::set('pronto:i18n', $i18n);

/************************************************************************
 * PRELOAD PLUGINS
 ************************************************************************/
foreach(explode(' ', PLUGINS) as $p) {
	if($p) Factory::plugin($p, 'page');
}
unset($p);
$plugins =& Registry::get('pronto:plugins');

/************************************************************************
 * REMAINING UTILITY CLASSES
 ************************************************************************/
$p = new Validator();
Registry::set('pronto:validator', $p);
unset($p);

/************************************************************************
 * HANDLE ERRORS/DEBUGGING/PROFILING
 ************************************************************************/
error_reporting(E_ALL & ~E_NOTICE);
if(DEBUG === true) {
	$db =& Registry::get('pronto:db:main');
	if($db) $db->profile = true;
}

/************************************************************************
 * HELPER FUNCTIONS FOR COMMANDLINE SCRIPTS
 ************************************************************************/
function script_lock($name)
{
	if(file_exists("/tmp/$name.lck")) {
		echo "Script is already locked -- skipping execution\n";
		mail(ADMIN_EMAIL, SITE_NAME.': skipping script', "$name is already running.\n");
		exit;
	}
	touch("/tmp/$name.lck");
}

function script_unlock($name)
{
	@unlink("/tmp/$name.lck");
}

?>
