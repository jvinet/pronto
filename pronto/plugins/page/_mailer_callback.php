<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: A utility class for display callbacks.  This class is
 *              part of the ppMailer plugin.
 *
 **/

class ppMailer_Display_Callback extends Swift_Plugin_VerboseSending_AbstractView
{
	var $cb_func;

	function paintResult($address, $result)
	{
		if($this->cb_func) call_user_func($this->cb_func, $address, $result);
	}
}

?>
