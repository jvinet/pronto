<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: SHM (Shared Memory) cache driver.  This driver is useful
 *              for times when you don't have a more robust caching facility,
 *              such as eAccelerator or MemCache.  It should be a last resort,
 *              however.
 *
 **/
class Cache_SHM extends Cache
{
	var $shmkey;
	var $shmid;
	var $memsize = CACHE_SHM_SIZE;

	/**
	 * Constructor
	 */
	function Cache_SHM()
	{
		$this->Cache();

		// use the primary entry script as our ftok filename
		$this->shmkey = ftok(DIR_FS_BASE.DS.'index.php', 'p');
		if($this->shmkey == -1) {
			trigger_error("Unable to create SHM Key");
			return;
		}

		$this->shmid = shm_attach($this->shmkey, $this->memsize, 0600);
	}

	function set($key, $var, $expire=0)
	{
		parent::set($key, $var, $expire);
		$key = SITE_NAME."|$key";
		// hash the string key into a unique integer
		$varkey = $this->_hash_key($key);

		$dict = $this->_get_dict();
		if(@shm_put_var($this->shmid, $varkey, $var)) {
			$dict['keys'][$key] = array(
				'varkey' => $varkey,
				'expire' => $expire ? time()+$expire : 0
			);
		} else {
			// failed to put it in the cache, make sure it's gone entirely
			$this->delete($key);
			unset($dict['keys'][$key]);
			// if in DEBUG mode, throw out a warning so the developer knows it might be
			// a good idea to bump up the SHM size
			if(DEBUG === true) {
				echo "<p><b>Warning:</b> SHM Cache is out of space, consider enlarging it.</p>";
			}
		}
		$this->_set_dict($dict);
	}

	function &get($key)
	{
		$key = SITE_NAME."|$key";
		$varkey = $this->_hash_key($key);
		$d = @shm_get_var($this->shmid, $varkey);
		return $d;
	}

	function delete($key)
	{
		parent::delete($key);
		$key = SITE_NAME."|$key";
		$varkey = $this->_hash_key($key);
		return @shm_remove_var($this->shmid, $varkey);
	}

	/**
	 * Flush entire cache.
	 */
	function flush()
	{
		parent::flush();
		// remove and reconnect to the SHM block
		shm_remove($this->shmid);
		$this->shmid = shm_attach($this->shmkey, $this->memsize, 0600);
	}

	/**
	 * Expire old entries.
	 */
	function gc()
	{
		$dict = $this->_get_dict();
		foreach($dict['keys'] as $k=>$v) {
			if($v['expire'] && $v['expire'] <= time()) {
				shm_remove_var($this->shmid, $v['varkey']);
				unset($dict['keys'][$k]);
			}
		}
		$this->_set_dict($dict);
	}

	/**
	 * Return some usage statistics.
	 */
	function stats()
	{
		// TODO: make this better and more uniform
		$dict = $this->_get_dict();
		return array(
			'shmkey' => dechex($this->shmkey),
			'shmid'  => $this->shmid,
			'dict'   => $dict
		);
	}

	/**
	 * Internal dictionary routines
	 */
	function _get_dict()
	{
		$dict = @shm_get_var($this->shmid, 0);
		if(!$dict) $dict = array('nextvar'=>1, 'keys' => array());
		return $dict;
	}

	function _set_dict($dict)
	{
		shm_put_var($this->shmid, 0, $dict);
	}

	function _hash_key($key)
	{
		$dict = $this->_get_dict();
		if(isset($dict['keys'][$key])) {
			$varkey = $dict['keys'][$key]['varkey'];
		} else {
			$varkey = $dict['nextvar']++;
			$this->_set_dict($dict);
		}
		return $varkey;
	}
}

?>
