<?php

/**
 * DATABASE CONNECTIONS
 *
 * NOTE: If you're using MySQL and you want to connect to multiple
 * databases using the same host/user/pass connection parameters, then
 * you may run into some side effects, as PHP will implicitly use the same
 * connection internally.  This can result in queries being executed on the
 * wrong database.  To work around this, use slightly different connection
 * values (eg, "127.0.0.1" instead of "localhost") for each connection.
 */

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
