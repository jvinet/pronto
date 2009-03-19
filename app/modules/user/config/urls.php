<?php
/*
 * URL routing for the User module.
 *
 */

$URLS = array(
	'/admin/user/(.*)' => 'User_Admin',
	'/user/auth/(.*)'  => 'User_Auth',
	'/user/(.*)'       => 'User',

	// shortcuts to common user functions
	'/login/'          => array('User_Auth','login'),
	'/logout/'         => array('User_Auth','logout'),
	'/set_lang/'       => array('User','set_lang'),
);


?>
