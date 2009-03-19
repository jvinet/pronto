<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Base class for all page/template plugins.
 *              Modify this class to make application-wide changes to plugins.
 *
 **/

class Plugin extends Plugin_Base
{
	/**
	 * Constructor for all Plugins
	 */
	function Plugin()
	{
		$this->Plugin_Base();
	}
}

?>
