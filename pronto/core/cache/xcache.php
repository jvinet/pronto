<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: XCache access routines
 *
 **/
class Cache_XCache extends Cache
{
	/**
	 * Constructor
	 */
	function Cache_XCache()
	{
		$this->Cache();
	}

	function set($key, $var, $expire=0)
	{
		parent::set($key, $var, $expire);
		$key = SITE_NAME."|$key";
		return xcache_set($key, $var, $expire);
	}

	function &get($key)
	{
		$key = SITE_NAME."|$key";
		$val =& xcache_get($key);
		return $val;
	}

	function delete($key)
	{
		parent::delete($key);
		$key = SITE_NAME."|$key";
		return xcache_unset($key);
	}

	function flush()
	{
		parent::flush();
		// TODO
	}

	function gc()
	{
		// xcache does this automatically...
	}

	function stats()
	{
		return array();
	}
}

?>
