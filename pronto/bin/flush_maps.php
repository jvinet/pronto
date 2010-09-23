<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Flush the Pronto class maps from the cache.
 *
 * NOTE: If using the SHM adapter, you may get "permission denied" errors
 *       when trying to connect to the cache.  This is likely due to the
 *       fact that the UID of the web server is different than the UID that
 *       is running this script.  By default, Pronto uses '0600' permissions
 *       on the cache, so only the UID that created the SHM segment can
 *       access it.
 *
 *       A workaround is to enable DEBUG mode and hit an URL in your
 *       web app with "?cache_flush" appended to it.
 *
 *       eg, http://localhost/myapp/?cache_flush
 *
 **/

if(!file_exists('profiles/cmdline.php')) {
	die("Run this script from your top-level app directory (eg, /var/www/html/app)\n");
}
require_once('profiles/cmdline.php');

if(USE_CACHE === true && defined('CACHE_DRIVER')) {

	echo "Flushing Pronto class maps from ".CACHE_DRIVER." cache...\n";
	$cache =& Registry::get('pronto:cache');

	$keys = array('map:pages','map:models','map:page_plugins','map:template_plugins');
	foreach($keys as $k) {
		echo "\tpronto:$k\n";
		$cache->delete("pronto:$k");
	}
}


?>
