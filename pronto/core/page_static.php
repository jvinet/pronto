<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Page controller extension that's basically just a dummy
 *              frontend for a directory of templates.  Whatever argument
 *              is passed to it will be used as the template name.
 *
 **/

class Page_Static extends Page
{
	var $template_dir = 'home';

	/**
	 * Set the template directory
	 *
	 * @param string $dir Template directory
	 */
	function set_dir($dir)
	{
		$this->template_dir = $dir;
	}

	function GET($path='',$s='')
	{
		if(empty($path)) $path = 'index';
		if(!ereg('^[A-z0-9+_-]+$', $path)) {
			$this->web->notfound();
		}
		if(!file_exists(DIR_FS_APP.DS.'templates'.DS.$this->template_dir.DS."$path.php")) {
			$this->web->notfound();
		}
		$this->render($this->template_dir.DS."$path.php");
	}

}

?>
