<?php

$DATABASES = array(
	'main' => array(
		'type' => 'mysql',
		'host' => 'localhost',
		'user' => 'root',
		'pass' => '',
		'name' => 'pronto',
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
