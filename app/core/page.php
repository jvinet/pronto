<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Base class for all page controllers (action/view in MVC terms).
 *              Modify this class to make application-wide changes to page
 *              controllers.
 *
 **/

class Page extends Page_Base
{
	/**
	 * Constructor for all Page elements
	 */
	function Page()
	{
		// parent constructor
		$this->Page_Base();

		$i18n =& Registry::get('pronto:i18n');
		$this->tset('languages', $i18n->get_languages());
		$this->tset('curr_lang', $i18n->get_language());
	}
}

?>
