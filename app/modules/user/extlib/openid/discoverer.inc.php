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

class Discoverer {

 private $server = null;
 private $delegate = '';
 private $identity = '';
 private $version = 1;

 public function __construct($uri) {
  if ($uri !== null) {
   $this->discover($this->identity = $this->normalise($uri));
  }
 }

 public function getServer() {
  return $this->server;
 }

 public function getDelegate() {
  return $this->delegate;
 }

 public function getIdentity() {
  return $this->identity;
 }

 public function getVersion() {
  return $this->version;
 }

 public static function normalise($uri) {
  // Strip xri:// prefix
  if (substr($uri, 0, 6) == 'xri://') {
   $uri = substr($uri, 6);
  }

  // If the first char is a global context symbol, treat it as XRI
  if (in_array($uri[0], array('=', '@', '+', '$', '!'))) {
   // TODO: Implement
   throw new Exception('This implementation does not currently support XRI');
  }

  // Add http:// if needed
  if (strpos($uri, '://') === false) {
   $uri = 'http://' . $uri;
  }

  $bits = @parse_url($uri);

  $result = $bits['scheme'] . '://';
  if (defined('OPENID_ALLOWUSER') && isset($bits['user'])) {
   $result .= $bits['user'];
   if (isset($bits['pass'])) {
    $result .= ':' . $bits['pass'];
   }
   $result .= '@';
  }
  $result .= preg_replace('/\.$/', '', $bits['host']);

  if (isset($bits['port']) && !empty($bits['port']) &&
     (($bits['scheme'] == 'http' && $bits['port'] != '80') ||
      ($bits['scheme'] == 'https' && $bits['port'] != '443') ||
      ($bits['scheme'] != 'http' && $bits['scheme'] != 'https'))) {
   $result .= ':' . $bits['port'];
  }

  if (isset($bits['path'])) {
   do {
    $bits['path'] = preg_replace('#/([^/]*)/\.\./#', '/', str_replace('/./', '/', $old = $bits['path'])); 
   } while ($old != $bits['path']);
   $result .= $bits['path'];
  } else {
   $result .= '/'; 
  }

  if (defined('OPENID_ALLOWQUERY') && isset($bits['query'])) {
   $result .= '?' . $bits['query'];
  }

  return $result;
 }

 private function discover($uri) {
  // TODO: Yaris discovery

  $this->delegate = $uri;
  $this->server = null;

  $this->htmlDiscover($uri);
 }

 private function htmlDiscover($uri) {
  $fh = fopen($uri, 'r');

  if (!$fh) {
   return;
  }

  $details = stream_get_meta_data($fh);

  foreach ($details['wrapper_data'] as $line) {
   if (preg_match('/^Location: (.*?)$/i', $line, $m)) {
    if (strpos($m[1], '://') !== false) {
     // Fully qualified URL
     $this->identity = $m[1];
    } else if ($m[1][0] == '/') {
     // Absolute URL
     $this->identity = preg_replace('#^(.*?://.*?)/.*$#', '\1', $this->identity) . $m[1];
    } else {
     // Relative URL
     $this->identity = preg_replace('#^(.*?://.*/).*?$#', '\1', $this->identity) . $m[1];
    }
   }
   $this->identity = self::normalise($this->identity);
  }

  $data = '';
  while (!feof($fh) && strpos($data, '</head>') === false) {
   $data .= fgets($fh);
  }

  fclose($fh);

  $this->parseHtml($data);
 }
  
 public function parseHtml($data) {
  preg_match_all('#<link\s*(.*?)\s*/?>#is', $data, $matches);

  $links = array();

  foreach ($matches[1] as $link) {
   $rel = $href = null;

   if (preg_match('#rel\s*=\s*(?:([^"\'>\s]*)|"([^">]*)"|\'([^\'>]*)\')(?:\s|$)#is', $link, $m)) {
    $rel = $m[1] . $m[2] . $m[3]; 
   }

   if (preg_match('#href\s*=\s*(?:([^"\'>\s]*)|"([^">]*)"|\'([^\'>]*)\')(?:\s|$)#is', $link, $m)) {
    $href = $m[1] . $m[2] . $m[3];
   }

   $links[$rel] = html_entity_decode($href);
  }

  if (isset($links['openid2.provider'])) {
   $this->version = 2;
   $this->server = $links['openid2.provider'];

   if (isset($links['openid2.local_id'])) {
    $this->delegate = $links['openid2.local_id'];
   }
  } else if (isset($links['openid.server'])) {
   $this->version = 1;
   $this->server = $links['openid.server'];

   if (isset($links['openid.delegate'])) {
    $this->delegate = $links['openid.delegate'];
   }
  }
 }

}

?>
