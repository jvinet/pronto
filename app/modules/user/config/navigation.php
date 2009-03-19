<?php
/**
 * Configuration for a multi-tier Navigation Menu.
 */

$NAV_MENU = array(
	__('Users') => array('access'=>'ADMIN', 'menu'=>array(
		__('New')   => array('url'=>url('User_Admin','create')),
		__('List')  => array('url'=>url('User_Admin','list')))),
);

?>
