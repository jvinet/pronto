<?php
/**
 * CAPTCHA class -- frontend to the Turing-Test external class
 */

require_once(dirname(__FILE__).'/../../extlib/turing.php');

class ppCAPTCHA extends Plugin
{
	var $turing = null;

	function ppCAPTCHA() {
		$this->Plugin();
	}

	function init() {
		$this->turing = new Turing();
		$this->turing->setLength(6);
		$this->turing->setFontFile(dirname(__FILE__).'/../../extlib/bboron.ttf');
		$this->turing->generateKey();
	}

	function display($key='')
	{
		if($key) {
			$this->turing->setKey($key);
		}
		$this->turing->displayImage();
	}

	function get_key()
	{
		return $this->turing->getKey();
	}
}

?>
