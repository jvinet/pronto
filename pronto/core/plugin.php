<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Base class for all page/template plugins.
 *
 **/

class Plugin_Base
{
	var $web;
	var $depends;

	/**
	 * Constructor for all Plugins
	 */
	function Plugin_Base()
	{
		// $web may actually be null if this is a page plugin loaded from
		// the commandline execution profile.
		$this->web =& Registry::get('pronto:web');
		$this->depends = new stdClass;

		if(method_exists($this, '__init__')) {
			$this->__init__();
		}
	}

	/**
	 * Load another plugin as a dependency of this one.  Plugins can
	 * only load dependencies of their own kind.  Templates plugins
	 * load template plugins, page plugins load page plugins.
	 *
	 * @param string $plugin The name of the plugin to import.  Will be
	 *                       saved as $this->depends->$plugin
	 */
	function depend($plugin)
	{
		switch(substr(get_class($this), 0, 2)) {
			case 'tp': $type = 'template'; break;
			case 'pp': $type = 'page'; break;
		}
		foreach(func_get_args() as $arg) {
			$this->depends->$arg =& Factory::plugin($arg, $type);
		}
	}
}

?>
