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

 require_once(dirname(__FILE__) . '/keymanager.inc.php');

 class URLBuilder {

  const NAMESPACE = 'http://openid.net/signon/1.1';

  public static function addArguments($base, $arguments) {
   $first = true;
   $res = $base === false ? '' : $base;

   if ($base !== false && strrpos($base, '?', -2) === false) {
    if ($base[strlen($base) - 1] != '?') {
     $res .= '?';
    }
   } else if ($base !== false) {
    $res .= '&';
   }

   foreach ($arguments as $key => $value) {
    if ($first) {
     $first = false;
    } else {
     $res .= '&';
    }

    $res .= urlencode($key) . '=' . urlencode($value);
   }

   return $res;
  }

  public static function buildRequest($type, $base, $delegate, $identity, $returnURL, $handle) {
   $args = array(
    'openid.ns' => self::NAMESPACE,
	'openid.mode' => 'checkid_' . $type,
	'openid.identity' => $delegate,
	'openid.claimed_id' => $identity,
	'openid.trust_root' => self::getTrustRoot(),
	'openid.return_to' => self::addArguments($returnURL,
		array('openid.nonce' => $_SESSION['openid']['nonce']))
   );

   if ($handle !== null) {
    $args['openid.assoc_handle'] = $handle;
   }

   self::addSRegArgs($args);

   return self::addArguments($base, $args);
  }

  private static function getTrustRoot() {
   if (defined('OPENID_TRUSTROOT')) {
    return OPENID_TRUSTROOT;
   } else {
    return self::getCurrentURL();
   }
  }

  private static function addSRegArgs(&$args) {
   if (defined('OPENID_SREG_REQUEST')) {
    $args['openid.sreg.required'] = OPENID_SREG_REQUEST;
   }

   if (defined('OPENID_SREG_OPTIONAL')) {
    $args['openid.sreg.optional'] = OPENID_SREG_OPTIONAL;
   }

   if (defined('OPENID_SREG_POLICY')) {
    $args['openid.sreg.policy_url'] = OPENID_SREG_POLICY;
   }
  }

  public static function buildAssociate($server) {
   $args = array(
        'openid.ns' => self::NAMESPACE,
	'openid.mode' => 'associate',
	'openid.assoc_type' => 'HMAC-SHA1',
   );

   if (KeyManager::supportsDH()) {
    $args['openid.session_type'] = 'DH-SHA1';
    $args['openid.dh_modulus'] = KeyManager::getDhModulus();
    $args['openid.dh_gen'] = KeyManager::getDhGen();
    $args['openid.dh_consumer_public'] = KeyManager::getDhPublicKey($server);
   } else {
    $args['openid.session_type'] = '';
   }

   return self::addArguments(false, $args);
  }

  public static function buildAuth($params) {
   $args = array(
        'openid.ns' => self::NAMESPACE,
	'openid.mode' => 'check_authentication'
   );

   $toadd = array('assoc_handle', 'sig', 'signed');
   $toadd = array_merge($toadd, explode(',', $params['openid_signed']));

   foreach ($toadd as $arg) {
    if (!isset($args['openid.' . $arg])) {
     $args['openid.' . $arg] = $params['openid_' . $arg];
    }
   }

   return self::addArguments(false, $args);
  }

  public static function getCurrentURL() {
   $res = 'http';

   if (isset($_SERVER['HTTPS'])) {
    $res = 'https';
   }

   $res .= '://' . $_SERVER['SERVER_NAME'];

   if ($_SERVER['SERVER_PORT'] != 80) {
    $res .= ':' . $_SERVER['SERVER_PORT'];
   }

   $url = $_SERVER['REQUEST_URI'];

   while (preg_match('/([\?&])openid[\._](.*?)=(.*?)(&|$)/', $url, $m)) {
    $url = str_replace($m[0], $m[1], $url);
   }

   $url = preg_replace('/\??&*$/', '', $url);

   return $res . $url;
  }

  /**
   * Redirects the user back to their original page.
   */
  public static function redirect() {
   if (defined('OPENID_REDIRECTURL')) {
    $url = OPENID_REDIRECTURL;
   } else if (isset($_SESSION['openid']['redirect'])) {
    $url = $_SESSION['openid']['redirect'];
   } else {
    $url = self::getCurrentURL();
   }

   self::doRedirect($url);
  }

  /**
   * Redirects the user to the specified URL.
   *
   * @param $url The URL to redirect the user to
   */
  public static function doRedirect($url) {
   header('Location: ' . $url);
   echo '<html><head><title>Redirecting</title></head><body>';
   echo '<p>Redirecting to <a href="', htmlentities($url), '">';
   echo htmlentities($url), '</a></p></body></html>';
   exit();
  }

 }

?>
