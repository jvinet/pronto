<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: APC access routines
 *
 **/
class Cache_APC extends Cache
{
	/**
	 * Constructor
	 */
	function Cache_APC()
	{
		$this->Cache();
	}

	function set($key, $var, $expire=0)
	{
		parent::set($key, $var, $expire);
		$key = SITE_NAME."|$key";
		return apc_add($key, $var, $expire);
	}

	function &get($key)
	{
		$key = SITE_NAME."|$key";
		$val =& apc_fetch($key);
		return $val;
	}

	function delete($key)
	{
		parent::delete($key);
		$key = SITE_NAME."|$key";
		return apc_delete($key);
	}

	function flush()
	{
		parent::flush();
		apc_clear_cache('user');
	}

	function gc()
	{
		// APC should do this automatically
	}

	function stats()
	{
		return apc_cache_info('user');
	}
}

?>
