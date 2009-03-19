<?php
/**
 * Configuration for a multi-tier Navigation Menu.
 */

$NAV_MENU = array(
	__('Home')  => array('access'=>'', 'url'=>url('/')),

	/* if you use the 'base' element, then this tab will be "active" for
	 * all URLs that start with the value of 'base'.
	 */
	//__('Books') => array('access'=>'ADMIN', 'url'=>url('/book/list'), 'base'=>url('/book/'))
);

?>
