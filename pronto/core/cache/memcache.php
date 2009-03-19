<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Memcache access routines
 *
 **/
class Cache_MemCache extends Cache
{
	var $memcache = null;

	// only active if connected to at least one memcache instance
	var $active   = false;

	/**
	 * Constructor
	 */
	function Cache_MemCache()
	{
		$this->Cache();
		$this->memcache = new Memcache();
		$this->active   = false;

		if(defined('CACHE_MEMCACHE_SERVERS')) {
			foreach(split(' ', CACHE_MEMCACHE_SERVERS) as $tuple) {
				list($h,$p) = split(':', $tuple);
				$this->add_server($h, $p);
			}
		}
	}

	function add_server($host, $port)
	{
		if($this->memcache->addServer($host, $port)) $this->active = true;
		return $this->active;
	}

	function set($key, $var, $expire=0)
	{
		$key = SITE_NAME."|$key";
		return $this->active ? $this->memcache->set($key, $var, 0, $expire) : false;
	}

	function &get($key)
	{
		$key = SITE_NAME."|$key";
		if(!$this->active) return false;
		$val =& $this->memcache->get($key);
		return $val;
	}

	function delete($key)
	{
		if(!$this->active) return false;
		$key = SITE_NAME."|$key";
		return $this->memcache->delete($key);
	}

	function flush()
	{
		if(!$this->active) return false;
		return $this->memcache->flush();
	}

	function gc()
	{
		// memcache will do this automatically...
	}

	function stats()
	{
		if(!$this->active) return false;
		return $this->memcache->getExtendedStats();
	}
}

?>
