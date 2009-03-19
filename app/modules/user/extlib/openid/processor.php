<?PHP

/* Poidsy 0.4 - http://chris.smith.name/projects/poidsy
 * Copyright (c) 2008 Chris Smith
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

 require_once(dirname(__FILE__) . '/discoverer.inc.php');
 require_once(dirname(__FILE__) . '/poster.inc.php');
 require_once(dirname(__FILE__) . '/sreg.inc.php');
 require_once(dirname(__FILE__) . '/urlbuilder.inc.php');
 require_once(dirname(__FILE__) . '/keymanager.inc.php');

/* PRONTO
 if (session_id() == '') {
  // No session - testing maybe?
  session_start();
 }
*/

 // Process any openid_url form fields (compatability with 0.1)
 if (!defined('OPENID_URL') && isset($_POST['openid_url'])) {
  define('OPENID_URL', $_POST['openid_url']);
 } else if (!defined('OPENID_URL') && isset($_POST['openid_identifier'])) {
  define('OPENID_URL', $_POST['openid_identifier']);
 }

 // Maximum number of requests to allow without a OPENID_THROTTLE_GAP second
 // gap between two of them
 if (!defined('OPENID_THROTTLE_NUM')) {
  define('OPENID_THROTTLE_NUM', 3);
 }

 // Time to require between requests before the request counter is reset
 if (!defined('OPENID_THROTTLE_GAP')) {
  define('OPENID_THROTTLE_GAP', 30);
 }

 // Whether or not to use the key manager
 define('KEYMANAGER', !defined('OPENID_NOKEYMANAGER') && KeyManager::isSupported());

 /**
  * Processes the current request.
  */
 function openid_process() {
  if (defined('OPENID_URL')) {
   // Initial authentication attempt (they just entered their identifier)

   $reqs = checkRequests();
   $disc = tryDiscovery(OPENID_URL);

   $_SESSION['openid'] = array(
 	'identity' => $disc->getIdentity(),
	'delegate' => $disc->getDelegate(),
	'validated' => false,
	'server' => $disc->getServer(),
	'nonce' => uniqid(microtime(true), true),
	'requests' => $reqs,
   );

   $handle = getHandle($disc->getServer());

   $url = URLBuilder::buildRequest(defined('OPENID_IMMEDIATE') ? 'immediate' : 'setup',
              $disc->getServer(), $disc->getDelegate(),
              $disc->getIdentity(), URLBuilder::getCurrentURL(), $handle);

   URLBuilder::doRedirect($url);
  } else if (isset($_REQUEST['openid_mode'])) {
   checkNonce();

   $func = 'process' . str_replace(' ', '', ucwords(str_replace('_', ' ',
			strtolower($_REQUEST['openid_mode']))));
   if (function_exists($func)) {
  	 call_user_func($func, checkHandleRevocation());
   }
  }
 }

 /**
  * Checks that the user isn't making requests too frequently, and redirects
  * them with an appropriate error if they are.
  *
  * @return An array containing details about the requests that have been made
  */
 function checkRequests() {
  if (isset($_SESSION['openid']['requests'])) {
   $requests = $_SESSION['openid']['requests'];
  } else {
   $requests = array('lasttime' => 0, 'count' => 0);
  }

  if ($requests['lasttime'] < time() - OPENID_THROTTLE_GAP) {

   // Last request was a while ago, reset the timer
   $requests['count'] = 0;

  } else if ($requests['count'] > OPENID_THROTTLE_NUM) {

   // More than the legal number of requests
   openid_error('throttled', 'You are trying to authenticate too often');

  }

  $requests['count']++;
  $requests['lasttime'] = time();

  return $requests;
 }

 /**
  * Attempts to perform discovery on the specified URL, redirecting the user
  * with an appropriate error if discovery fails.
  *
  * @param String $url The URL to perform discovery on
  * @return An appropriate Discoverer object
  */
 function tryDiscovery($url) {
  try {
   $disc = new Discoverer(OPENID_URL);

   if ($disc->getServer() == null) {
   	openid_error('notvalid', 'Claimed identity is not a valid identifier');
   }

   return $disc;
  } catch (Exception $e) {
   openid_error('discovery', $e->getMessage());
  }
 }

 /**
  * Retrieves an association handle for the specified server. If we don't
  * currently have one, attempts to associate with the server.
  *
  * @param String $server The server whose handle we're retrieving
  * @return The association handle of the server or null on failure
  */
 function getHandle($server) {
  if (KEYMANAGER) {
   if (!KeyManager::hasKey($server)) {
    KeyManager::associate($server);
   }

   return KeyManager::getHandle($server);
  } else {
   return null;
  }
 }

 /**
  * Checks that the nonce specified in the current request equals the one
  * stored in the user's session, and redirects them if it doesn't.
  */
 function checkNonce() {
  if ($_REQUEST['openid_nonce'] != $_SESSION['openid']['nonce']) {
   openid_error('nonce', 'Nonce doesn\'t match - possible replay attack');
  } else {
   $_SESSION['openid']['nonce'] = uniqid(microtime(true), true);
  }
 }

 /**
  * Checks to see if the request contains an instruction to invalidate the
  * handle we used. If it does, the request is authenticated and the handle
  * removed (or the user is redirected with an error if the IdP doesn't
  * authenticate the message).
  *
  * @return True if the message has been authenticated, false otherwise
  */
 function checkHandleRevocation() {
  $valid = false;

  if (KEYMANAGER && isset($_REQUEST['openid_invalidate_handle'])) {
   $valid = KeyManager::dumbAuth();

   if ($valid) {
    KeyManager::removeKey($_SESSION['openid']['server'], $_REQUEST['openid_invalidate_handle']);
   } else {
   	openid_error('noauth', 'Provider didn\'t authenticate message');
   }
  }

  return $valid;
 }

 /**
  * Processes id_res requests.
  *
  * @param Boolean $valid True if the request has already been authenticated
  */
 function processIdRes($valid) {
  if (isset($_REQUEST['openid_identity'])) {
   if ($_REQUEST['openid_identity'] != $_SESSION['openid']['delegate']) {
   	openid_error('diffid', 'Identity provider validated wrong identity. Expected it to '
   	              . 'validate ' . $_SESSION['openid']['delegate'] . ' but it '
   	              . 'validated ' . $_REQUEST['openid_identity']);
   }

   if (!$valid) {
    $dumbauth = true;

    if (KEYMANAGER) {
     try {
      $valid = KeyManager::authenticate($_SESSION['openid']['server'], $_REQUEST);
      $dumbauth = false;
     } catch (Exception $ex) {
      // Ignore it - try dumb auth
     }
    }

    if ($dumbauth) {
     $valid = KeyManager::dumbAuthenticate();
    }
   }

   $_SESSION['openid']['validated'] = $valid;

   if (!$valid) {
   	openid_error('noauth', 'Provider didn\'t authenticate response');
   }

   parseSRegResponse();
   URLBuilder::redirect();

  } else if (isset($_REQUEST['openid_user_setup_url'])) {
   if (defined('OPENID_IMMEDIATE') && OPENID_IMMEDIATE) {
   	openid_error('noimmediate', 'Couldn\'t perform immediate auth');
   }

   $handle = getHandle($_SESSION['openid']['server']);

   $url = URLBuilder::buildRequest('setup', $_REQUEST['openid_user_setup_url'],
                                 $_SESSION['openid']['delegate'],
                                 $_SESSION['openid']['identity'],
                                 URLBuilder::getCurrentURL(), $handle);

   URLBuilder::doRedirect($url);
  }
 }

 /**
  * Processes cancel modes.
  *
  * @param Boolean $valid True if the request has already been authenticated
  */
 function processCancel($valid) {
  openid_error('cancelled', 'Provider cancelled the authentication attempt');
 }

 /**
  * Processes error modes.
  *
  * @param Boolean $valid True if the request has already been authenticated
  */
 function processError($valid) {
  openid_error('perror', 'Provider error: ' . $_REQUEST['openid_error']);
 }

 /**
  * Populates the session array with the details of the specified error and
  * redirects the user appropriately.
  *
  * @param String $code The error code that occured
  * @param String $message A description of the error
  */
 function openid_error($code, $message) {
  $_SESSION['openid']['error'] = $message;
  $_SESSION['openid']['errorcode'] = $code;
  URLBuilder::redirect();
 }

 // Here we go!
 openid_process();
?>
