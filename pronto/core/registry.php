<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: A registry.
 *
 **/
class Registry
{
	var $store;

	/**
	 * Constructor
	 */
	function Registry()
	{
		$this->store = array();
	}

	function _set($key, &$var)
	{
		$this->store[$key] =& $var;
	}

	function &_get($key)
	{
		$f = false;
		if(!isset($this->store[$key])) return $f;
		return $this->store[$key];
	}

	/**
	 * Store a variable/object.
	 *
	 * @param string $key Unique key name to store value under
	 * @param mixed $var Variable to store
	 */
	function set($key, &$var)
	{
		$reg =& $GLOBALS['__registry'];
		return $reg->_set($key, $var);
	}

	/**
	 * Fetch a variable/object.
	 *
	 * @param string $key Key name
	 * @param mied $default Value to return if the object does not exist in the registry.
	 */
	function &get($key, $default=false)
	{
		$reg =& $GLOBALS['__registry'];
		$data =& $reg->_get($key);
		if($data === false) $data = $default;
		return $data;
	}
}

// Start up an instance immediately -- it will be the only one.
$GLOBALS['__registry'] = new Registry();


?>
