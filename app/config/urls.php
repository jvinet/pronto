<?php
/*
 * Configure URLs with regular expressions. See the PHP reference
 * for the preg_match() function to see more examples of regular
 * expressions.
 *
 * Regexps are evaluated in order, with the first match being the one
 * used.
 *
 * FORMAT:
 *   array(
 *     'URL REGEX' => 'PAGE CLASS NAME',
 *     'URL'       => array('PAGE CLASS NAME', 'ACTION NAME')
 *   ) 
 *
 * EXAMPLES:
 *   array(
 *   // This exact URL goes to the "User" controller and the "login" action
 *     '/login/'               => array('User','login'),
 *
 *   // We used a named subpattern, which will get passed into the "view" action
 *   // as a request variable.  URLs like "/user/12/" will go to this
 *   // controller/action.
 *     '/user/(?<uid>[0-9]+)/' => array('User','view'),
 *
 *   // Anything starting with "/user/" will go to the controller.  The latter
 *   // part (ie, anything after /user/) will become the action.
 *   // eg, "/user/create" will go to the "User" controller and the "create"
 *   // action.
 *     '/user/(.*)'            => 'User',
 *
 *   // This uses two shortcuts, '>' and '//'.  The '>' tells the dispatcher
 *   // to issue a redirect to the URL that follows.  The '//' (double-slash)
 *   // shortcut tells the dispatcher to route the URL fragment through the
 *   // url() function before using it, which ensures that the resulting URL
 *   // points to the correct location relative to the web root.
 *     '/admin/'               => '>//user/list',
 *   )
 *
 * NOTES:
 *
 * - the first matching regex is used
 * - all URLs should end with a '/' or '(.*)'
 *   - if ending in '/', then that specific URL will point to the
 *     controller/action specified
 *   - if ending in '(.*)', then that anything starting with that URL
 *     will point to the page class specified
 * - to bind to a specific action, pass an array(CLASS, ACTION)
 *   - to pass an array of parameters to the action, pass
 *     array(CLASS, ACTION, array(PARAMS))
 *
 */

$URLS = array(
	//'/(.*)' => 'Home',                    // everything goes to the page server
	'/(.*)' => array('CMS_Page','view'),  // or send everything to the CMS
);

?>
