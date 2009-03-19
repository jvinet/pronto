<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Globally-accessible handlers for errors.
 *
 **/

function notfound($web)
{
	$web->render(new Template(), '404.php', array(), 'layout.php');
	exit;
}

function forbidden($web)
{
	$web->render(new Template(), '403.php', array(), 'layout.php');
	exit;
}

function internalerror($web)
{
	$web->render(new Template(), '500.php', array(), 'layout.php');
	exit;
}

?>
