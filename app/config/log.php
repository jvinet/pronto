<?php
/**
 * Configuration for logging routes.
 *
 * Log routes are controlled by facility and priority.  A log message
 * can match multiple routes.
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
	// Debug: Log everything
	'pronto(:.*)*' => array('.*' => 'pronto.log'),
	'app(:.*)*'    => array('.*' => 'app.log'),

	// Production: Don't log debug/info messages
	//'pronto(:.*)*' => array('!(debug|info)' => 'pronto.log'),
	//'app(:.*)*'    => array('!(debug|info)' => 'app.log'),
	
	/* Example: Messages from 'mymod' facililty that aren't debug or info priority */ 
	//'mymod' => array('!(debug|info)' => 'mymod.log'),

	/* Example: Log each subapp to a different file */
	//'app:(.*)' => 'app_\1.txt',
);

?>
