<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: File-based cache driver.  This driver is useful
 *              for times when you don't have a more robust caching facility,
 *              such as eAccelerator or MemCache.
 *
 * WARNING:     These is no file locking in place for this driver.
 *
 **/
class Cache_File extends Cache
{
	var $cache_dir;

	/**
	 * Constructor
	 */
	function Cache_File()
	{
		$this->Cache();
	}

	function set_options($cfg=array())
	{
		if(!empty($cfg['cache_dir'])) {
			$this->cache_dir = $cfg['cache_dir'];
		} else {
			if(!defined('CACHE_FILES_DIR')) {
				// Yikes, we need a cache dir.
				trigger_error("CACHE_FILES_DIR is not defined.  Set it in app/config/cache.php");
				return;
			}

			$this->cache_dir = CACHE_FILES_DIR;
		}
	}

	function set($key, $var, $expire=0)
	{
		parent::set($key, $var, $expire);

		$key = $this->_mangle_key($key);
		$fn  = $this->cache_dir.DS.$key;

		$fp = @fopen($fn, 'w');
		if($fp === false) {
			trigger_error("Unable to write to cache file: $fn");
			return;
		}

		fputs($fp, serialize($var));
		fclose($fp);

		// We use the file's modtime to store the expiry time (in seconds).
		// Files that never expire will have modtimes of December 31, 1969
		$mtime = $expire > 0 ? time()+$expire : 0;
		touch($fn, $mtime);
	}

	function &get($key)
	{
		$key = $this->_mangle_key($key);
		$fn  = $this->cache_dir.DS.$key;

		// used so we can return by reference
		$a = false;

		if(!file_exists($fn)) return $a;

		$ts = filemtime($fn);
		if($ts > 0 && $ts <= time()) {
			// record is expired
			$this->delete($key);
			return $a;
		}

		$var = @file_get_contents($fn);
		if(!$var) return $a;

		$data = unserialize($var);
		return $data;
	}

	function delete($key)
	{
		parent::delete($key);
		$key = $this->_mangle_key($key);
		$fn  = $this->cache_dir.DS.$key;

		return is_file($fn) ? @unlink($fn) : false;
	}

	/**
	 * Flush entire cache.
	 */
	function flush()
	{
		parent::flush();
		foreach(glob($this->cache_dir.DS.'*') as $fn) {
			if(is_file($fn)) @unlink($fn);
		}
		return true;
	}

	/**
	 * Expire old entries.
	 */
	function gc()
	{
		// to help performance, we don't actually remove expired entries,
		// we just delete them in ::get() if they've been expired.
		return true;
	}

	/**
	 * Return some usage statistics.
	 */
	function stats()
	{
		return array(
			'files' => glob($this->cache_dir.DS.'*')
		);
	}

	/**
	 * Mangle key names into filesystem-friendly format
	 */
	function _mangle_key($key)
	{
		return str_replace(array('/','\\'), '_', $key);
	}
}

?>
