<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Main entry script.  All web requests start here before
 *              going to the dispatch controller.
 *
 **/

// Call the 'web' execution profile - it will do the rest
require('app/profiles/web.php');

?>
