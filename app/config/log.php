<?php
/**
 * Configuration for logging routes.
 *
 * Log routes are controlled by facility and priority.
 *
 * By default, there are two facilities, but you can add more:
 *   - Pronto
 *   - App
 *
 * These are the default priorities, in order:
 *   - debug
 *   - info
 *   - warning
 *   - error
 *
 * The $LOG_ROUTES array is a 2-dimensional array.  The first
 * key is a regular expression that will match the facility of the
 * log message.  The second key is a regular expression that will match
 * the priority of the message.
 *
 * You can optionally prefix a regexp with a ! to negate it.
 *
 * NB: The ! prefix is not valid in regular expressions, but we parse
 *     it correctly here.  Don't use it elsewhere - it won't work!
 */

$LOG_ROUTES = array(
	/* Example: Log all 'app' messages of 'warning' priority */
	//'app' => array('warning' => 'warnings.log'),
	
	/* Example: Log all 'mymod' messages that aren't debug or info level */ 
	//'mymod' => array('!(debug|info)' => 'mymod.log'),
);

?>
