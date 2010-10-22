<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Start an interactive REPL (Read Eval Print Loop) for Pronto.
 *              Useful when you want to interact with Pronto objects without
 *              writing a stub script to do so.
 *
 *
 **/

if(!file_exists('profiles/cmdline.php')) {
	die("Run this script from your top-level app directory (eg, /var/www/html/app)\n");
}
require_once('profiles/cmdline.php');

require_once(DIR_FS_PRONTO . DS . 'extlib' . DS . 'repl.php');
$repl = new PHP_Repl(array('prompt'=>'pronto> '));
$repl->run();

exit(0);

?>
