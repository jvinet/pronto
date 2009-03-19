<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Translate a messages file into another language using Google's
 *              Translate service.
 *
 *              Currently, Google will translate to the following languages:
 *
 *              - Arabic (ar)
 *              - Chinese Simplified (zh_cn)
 *              - Chinese Traditional (zh_tw)
 *              - Dutch (nl)
 *              - French (fr)
 *              - German (de)
 *              - Greek (el)
 *              - Italian (it)
 *              - Japanese (ja)
 *              - Korean (ko)
 *              - Portuguese (pt)
 *              - Russian (ru)
 *              - Spanish (es)
 *
 **/

if(!extension_loaded('mbstring')) {
	die("Error: This script requires the mbstring PHP extension.\n");
}

ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

function post($url, $params)
{
	$urlparts = parse_url($url);
	$paramstr = '';
	foreach($params as $k=>$v) {
		$paramstr .= "&$k=".urlencode($v);
	}
	$paramstr = substr($paramstr, 1);

	$fp = fsockopen($urlparts['host'], 80);
	if($fp === false) die("Error: could not connect to {$urlparts['host']}\n");
	fwrite($fp, "POST {$urlparts['path']} HTTP/1.0\r\n");
	fwrite($fp, "Host: {$urlparts['host']}\r\n");
	fwrite($fp, "Content-Type: application/x-www-form-urlencoded\r\n");
	fwrite($fp, "Content-Length: ".strlen($paramstr)."\r\n\r\n");
	fwrite($fp, $paramstr);

	$result = '';
	while(!feof($fp)) {
		$result .= fread($fp, 4096);
	}
	fclose($fp);

	return $result;
}

/**
 * Taken from http://ca3.php.net/strrev
 * Reverses a UTF8 string, but doesn't reverse numbers
 */
function utf8_strrev($str, $reverse_numbers=false) {
	preg_match_all('/./us', $str, $ar);
	if ($reverse_numbers)
		return join('',array_reverse($ar[0]));
	else {
		$temp = array();
		foreach ($ar[0] as $value) {
			if (is_numeric($value) && !empty($temp[0]) && is_numeric($temp[0])) {
				foreach ($temp as $key => $value2) {
					if (is_numeric($value2))
						$pos = ($key + 1);
					else
						break;
				}
				$temp2 = array_splice($temp, $pos);
				$temp = array_merge($temp, array($value), $temp2);
			} else
				array_unshift($temp, $value);
		}
		return implode('', $temp);
	}
}

if($_SERVER['argc'] < 2) {
	echo "desc:  Translate a messages file into another language using Google's\n";
	echo "       Translate service.\n\n";
	echo "usage: google_translate.php <messages_file>\n";
	echo "ex:    google_translate.php config/i18n/fr/messages.php\n\n";
	exit;
}
$MESSAGES_FILE = $_SERVER['argv'][1];

if(!is_readable($MESSAGES_FILE)) {
	die("Error: cannot open file for read: $MESSAGES_FILE\n");
}

include($MESSAGES_FILE);
$LANGUAGE_CODE = str_replace('_','-',$LANGUAGE_CODE);

$params = array(
	'ie'       => 'UTF8',
	'hl'       => 'en',
	'langpair' => 'en|'.$LANGUAGE_CODE,
	'text'     => ""
);
foreach($MESSAGES as $k=>$v) {
	$params['text'] .= "$k\n";
}

$r = post('http://www.google.com/translate_t', $params);

// parse header for charset
$headers = array();
$hdrtext = substr($r, 0, strpos($r, "\r\n\r\n"));
foreach(split("\r\n", $hdrtext) as $hdr) {
	if(strpos($hdr, ':') === false) continue;
	$parts = split(':', $hdr);
	$k = array_shift($parts);
	$headers[$k] = trim(join(':', $parts));
}

// convert to UTF-8
$parts = split(';', $headers['Content-Type']);
$parts2 = split('=', $parts[1]);
$charset = trim($parts2[1]);
$r = iconv($charset, 'UTF-8//TRANSLIT', $r);

// scrape HTML for our string list
preg_match('#<div id=result_box dir="(ltr|rtl)">(.*)</div>#Uus', $r, $matches);
if(empty($matches[2])) {
	echo ("Error: did find translation results in output\n");
	//print_r($matches);
	//echo "\n$r\n";
	exit(1);
}

$outfile = file_get_contents($MESSAGES_FILE);
reset($MESSAGES);

$words = split("<br>", $matches[2]);
foreach($words as $word) {
	$word = trim($word);
	$word = html_entity_decode($word, ENT_COMPAT, 'UTF-8');
	//if($matches[1] == 'rtl') $word = utf8_strrev($word);
	
	// for some reason, Google will sometimes break up our printf
	// tokens, turning "%s" into "s%" or "% s"
	$word = str_replace(array('% s','% S','s%','S%'), '%s', $word);
	$word = str_replace(array('% d','% D','d%','D%'), '%d', $word);

	// escape quotes
	$word = str_replace('"', '\\"', $word);

	$orig = key($MESSAGES);
	$outfile = str_replace("=> \"$orig\"", "=> \"$word\"", $outfile);
	next($MESSAGES);
}

$fp = fopen($MESSAGES_FILE, 'w');
fputs($fp, $outfile);
fclose($fp);

?>
