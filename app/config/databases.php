<?php

/**
 * DATABASE CONNECTIONS
 */

$DATABASES = array(
	'main' => array(
		'type' => 'pdo',
		'dsn'  => 'mysql:host=localhost;dbname=pronto',
		'user' => 'root',
		'pass' => '',
		'opts' => array('persistent' => true)
	),

	/*
	'another' => array(
		'type' => 'pdo',
		'dsn'  => 'sqlite:'.DIR_FS_BASE.DS.'db'.DS.'another.db'
	),
	*/

);

?>
