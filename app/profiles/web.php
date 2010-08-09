<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Execution profile for a web-based process.
 *
 **/

// Config
require_once(dirname(__FILE__).'/../config/config.php');
require_once(dirname(__FILE__).'/../config/access.php');

// By default, use the regular Pronto web profile...
require(DIR_FS_PRONTO.DS.'profiles'.DS.'web.php');

// Dispatch the request
$urls = Registry::get('pronto:urls');
$web->run($urls);

?>
