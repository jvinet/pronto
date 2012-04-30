<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: A lazy-loading class for PHP5.  Objects of this class can
 *              "proxy" calls to the real object, avoiding any load time unless
 *              the object is actually used at some point.
 *
 **/
class LazyLoad
{
	var $classname;
	var $filepath;

	var $loaded;
	var $object;

	/**
	 * Constructor
	 *
	 * @param string $classname
	 * @param string $filepath
	 */
	function LazyLoad($classname, $filepath)
	{
		$this->classname = $classname;
		$this->filepath = $filepath;

		$this->loaded = false;
		$this->object = null;
	}

	function _load()
	{
		if(!$this->loaded) {
			require_once($this->filepath);
			$this->object = new $this->classname();
			$this->loaded = true;
		}
	}

	function __call($name, $args)
	{
		$this->_load();

		// proxy the call to the real object
		return call_user_func_array(array(&$this->object, $name), $args);
	}

	function __get($key)
	{
		$this->_load();
		return $this->object->$key;
	}

	function __set($key, $val)
	{
		$this->_load();
		$this->object->$key = $val;
	}
}

?>
