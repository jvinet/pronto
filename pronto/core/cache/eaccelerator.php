<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: eAccelerator access routines
 *
 **/
class Cache_eAccelerator extends Cache
{
	/**
	 * Constructor
	 */
	function Cache_eAccelerator()
	{
		$this->Cache();
	}

	function set($key, $var, $expire=0)
	{
		$key = SITE_NAME."|$key";
		return eaccelerator_put($key, $var, $expire);
	}

	function &get($key)
	{
		$key = SITE_NAME."|$key";
		$val =& eaccelerator_get($key);
		return $val;
	}

	function delete($key)
	{
		$key = SITE_NAME."|$key";
		return eaccelerator_rm($key);
	}

	function flush()
	{
		foreach(eaccelerator_list_keys() as $k=>$v) {
			$this->delete($v['name']);
		}
	}

	function gc()
	{
		return eaccelerator_gc();
	}

	function stats()
	{
		return eaccelerator_list_keys();
	}
}

?>
