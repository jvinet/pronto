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

 require_once(dirname(__FILE__) . '/bigmath.inc.php');
 require_once(dirname(__FILE__) . '/poster.inc.php');
 require_once(dirname(__FILE__) . '/urlbuilder.inc.php');

 class KeyManager {

  /** Diffie-Hellman P value, defined by OpenID specification. */
  const DH_P = '155172898181473697471232257763715539915724801966915404479707795314057629378541917580651227423698188993727816152646631438561595825688188889951272158842675419950341258706556549803580104870537681476726513255747040765857479291291572334510643245094715007229621094194349783925984760375594985848253359305585439638443';
  /** Diffie-Hellman G value. */
  const DH_G = '2';

  private static $header = null;
  private static $data = null;
  private static $bigmath = null;

  private static function loadData() {
   if (self::$data == null) {
    $data = file(dirname(__FILE__) . '/keycache.php');
    self::$header = array_shift($data);
    self::$data = unserialize(implode("\n", $data));
   }
  }

  private static function saveData() {
   file_put_contents(dirname(__FILE__) . '/keycache.php', self::$header . serialize(self::$data));
  }

  public static function associate($server) {
   $data = URLBuilder::buildAssociate($server);

   try {
    $res = Poster::post($server, $data);
   } catch (Exception $ex) {
    return;
   }

   $data = array();

   foreach (explode("\n", $res) as $line) {
    if (preg_match('/^(.*?):(.*)$/', $line, $m)) {
     $data[$m[1]] = $m[2];
    }
   }

   $data['expires_at'] = time() + $data['expires_in'];

   self::$data[$server][$data['assoc_handle']] = $data;
   self::saveData();
  }

  public static function getHandle($server) {
   self::loadData();

   if (!isset(self::$data[$server])) {
    return null;
   }

   foreach (self::$data[$server] as $handle => $data) {
    if ($handle == '__private') { continue; }

    if ($data['expires_at'] < time()) {
     unset(self::$data[$server][$handle]);
    } else {
     return $handle;
    }
   }

   return null;
  }

  public static function hasKey($server) {
   return self::getHandle($server) !== null;
  }

  public static function getData($server, $handle) {
   self::loadData();

   if (isset(self::$data[$server][$handle])) {
    if (self::$data[$server][$handle]['expires_at'] < time()) {
     self::removeKey($server, $handle);
     return null;
    } else {
     return self::$data[$server][$handle];
    }
   } else {
    return null;
   }
  }

  public static function authenticate($server, $args) {
   $data = self::getData($server, $args['openid_assoc_handle']);

   if ($data === null) {
    throw new Exception('No key available for that server/handle');
   }

   $contents = '';
   foreach (explode(',', $args['openid_signed']) as $arg) {
    $argn = str_replace('.', '_', $arg);
    $contents .= $arg . ':' . $args['openid_' . $argn] . "\n";
   }

   switch (strtolower($data['session_type'])) {
    case 'dh-sha1':
     $algo = 'sha1';
     break;
    case 'dh-sha256':
     $algo = 'sha256';
     break;
    case 'no-encryption':
    case 'blank':
    case '':
     $algo = false;
     break;
    default:
     throw new Exception('Unable to handle session type ' . $data['session_type']);
   }

   if ($algo !== false) {
    // The key is DH'd
    $mac = base64_decode($data['enc_mac_key']);
    $x = self::getDhPrivateKey($server);
    $temp = self::$bigmath->btwoc_undo(base64_decode($data['dh_server_public'])); 
    $temp = self::$bigmath->powmod($temp, $x, self::DH_P);
    $temp = self::$bigmath->btwoc($temp);
    $temp = hash($algo, $temp, true);
    $mac = $mac ^ $temp;
   } else {
    $mac = base64_decode($data['mac_key']);
   }

   switch (strtolower($data['assoc_type'])) {
    case 'hmac-sha1':
     $algo = 'sha1';
     break;
    case 'hmac-sha256':
     $algo = 'sha256';
     break; 
    default:
     throw new Exception('Unable to handle association type ' . $data['assoc_type']);
   }

   $sig = base64_encode(hash_hmac($algo, $contents, $mac, true));

   if ($sig == $args['openid_sig']) {
    return true;
   } else {
    return false;
   }
  }

  public static function dumbAuthenticate() {
   $url = URLBuilder::buildAuth($_REQUEST);

   try {
    $data = Poster::post($_SESSION['openid']['server'], $url);
   } catch (Exception $ex) {
    return false;
   }

   $valid = false;
   foreach (explode("\n", $data) as $line) {
    if (substr($line, 0, 9) == 'is_valid:') {
     $valid = (boolean) substr($line, 9);
    }
   }

   return $valid;
  }

  public static function removeKey($server, $handle) {
   self::loadData();
   unset(self::$data[$server][$handle]);
   self::saveData();
  }

  public static function isSupported() {
   return @is_writable(dirname(__FILE__) . '/keycache.php')
	&& function_exists('hash_hmac');
  }

  public static function getDhModulus() {
   return base64_encode(self::$bigmath->btwoc(self::DH_P));
  }

  public static function getDhGen() {
   return base64_encode(self::$bigmath->btwoc(self::DH_G));
  }

  public static function getDhPrivateKey($server) {
   self::loadData();
   return self::$data[$server]['__private'];
  }

  public static function getDhPublicKey($server) {
   self::loadData();
   $key = self::createDhKey($server);
   self::saveData();

   return base64_encode(self::$bigmath->btwoc(self::$bigmath->powmod(self::DH_G, $key, self::DH_P)));
  }

  private static function createDhKey($server) {
   return self::$data[$server]['__private'] = self::$bigmath->rand(self::DH_P); 
  }

  public static function supportsDH() {
   return self::$bigmath != null;
  }

  public static function init() {
   self::$bigmath = BigMath::getBigMath();
  }

 }

 KeyManager::init();

?>
