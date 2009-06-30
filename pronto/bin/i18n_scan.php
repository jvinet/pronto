<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Scan all applicable files for strings contained in the
 *              __() or _e() functions.  Outputs a PHP file containing
 *              all strings in an array, ready to be used by the I18N class.
 *
 **/

if(!file_exists('profiles/cmdline.php')) {
	die("Run this script from your top-level app directory (eg, /var/www/html/app)\n");
}
require_once('profiles/cmdline.php');

function findfiles($path, $func)
{
	foreach(glob($path.'/*') as $fn) {
		if(is_dir($fn)) {
			findfiles($fn, $func);
		} else {
			$ext = array_pop(explode('.', $fn));
			if($ext != 'php') continue;
			$func($fn);
		}
	}
}

function process($fn)
{
	global $STRINGS;

	$contents = file_get_contents($fn);
	if($contents === false) {
		echo "Error: cannot open file: $fn\n";
		return;
	}

	$tokens = @token_get_all($contents);

	if(0) {
		// DEBUG
		foreach($tokens as $token) {
			if(is_string($token)) {
				echo "STRING: $token\n";
			} else {
				echo token_name($token[0]).": {$token[1]}\n";
			}
		}
	}

	$_php = false;
	$_stage = 0;
	foreach($tokens as $token) {
		if(is_string($token)) {
			if($token == '(' && $_stage == 1) {
				$_stage = 2;
			}
			if($token == ')') {
				$_stage = 0;
			}
		} else {
			list($id, $text, $linenum) = $token;
			switch($id) {
				case T_WHITESPACE:
					continue;
					break;
				case T_OPEN_TAG:
				case T_OPEN_TAG_WITH_ECHO:
					$_php = true;
					$_stage = 0;
					break;
				case T_CLOSE_TAG:
					$_php = false;
					$_stage = 0;
					break;
				case T_STRING:
					if($_php && ($text == '__' || $text == '_e')) {
						$_stage = 1;
					}
					break;
				case T_CONSTANT_ENCAPSED_STRING:
					if($_stage == 2) {
						$_stage = 0;
						$str = trim($text);
						// strip surrounding quotes
						$quote = substr($str, 0, 1);
						$str = substr($str, 1, strlen($str)-2);
						// remove escaped quotes
						if($quote == "'") {
							eval("\$str = '$str';");
						} else if($quote == '"') {
							eval("\$str = \"$str\";");
						}
						if(!isset($STRINGS[$str])) {
							$STRINGS[$str] = array(
								'locations'  => array(),
								'translated' => $str
							);
						}
						$STRINGS[$str]['locations'][] = "$fn:$linenum";
					}
					break;
			}
		}
	}
}

$args = $_SERVER['argv'];
array_shift($args);

/*
 * Unfortunately, this method won't work if you combine options with
 * a single hyphen (eg, -lm)
 */
$opts = getopt("lm:");
foreach($opts as $k=>$v) {
	switch($k) {
		case 'l': array_shift($args); break;
		case 'm': array_shift($args); array_shift($args); break;
	}
}

if(count($args) < 2) {
	echo "desc:  Scans PHP files for strings enclosed in i18n functions __() or _e()\n";
	echo "       and builds a PHP messages file from the results. If the messages.php file\n";
	echo "       does not exist, it will be created. If it already exists, it will be\n";
	echo "       merged/updated.\n\n";
	echo "usage: i18n_scan.php [-l] [-m] <language_code> <language_name>\n";
	echo "ex:    i18n_scan.php en English\n";
	echo "ex:    i18n_scan.php -m mymod de German\n\n";
	echo "options:\n";
	echo "  -l        Include the file and line number of each occurrence of each string.\n";
	echo "  -m <mod>  Scan within a module. Use this when building messages for a module.\n\n";
	exit(0);
}

$OPTIONS = array(
	'locations' => isset($opts['l']),
	'module'    => isset($opts['m']) ? $opts['m'] : false
);

$STRINGS       = array();
$LANG_CODE     = $args[0];
$LANG_NAME     = $args[1];
if($OPTIONS['module']) {
	$MESSAGES_FILE = DIR_FS_APP."/modules/{$OPTIONS['module']}/config/i18n/$LANG_CODE/messages.php";
} else {
	$MESSAGES_FILE = DIR_FS_APP."/config/i18n/$LANG_CODE/messages.php";
}

if(file_exists($MESSAGES_FILE)) {
	include($MESSAGES_FILE);
	if($LANGUAGE_CODE != $LANG_CODE) {
		die("Error: Language in $MESSAGE_FILES does not match the one specified: $LANG_CODE\n");
	}
	foreach($MESSAGES as $k=>$v) {
		$STRINGS[$k] = array(
			'locations'  => array(),
			'translated' => $v
		);
	}
}

if($OPTIONS['module']) {
	$mod_dirs    = array('config','models','pages','plugins','templates');
	foreach($mod_dirs as $dir) {
		$d = DIR_FS_APP."/modules/{$OPTIONS['module']}/$dir";
		echo "Scanning $d\n";
		findfiles($d, 'process');
	}
} else {
	$app_dirs    = array('bin','config','core','models','pages','plugins',
											 'profiles','templates');
	$pronto_dirs = array('core','plugins','profiles');
	foreach($app_dirs as $dir) {
		$d = DIR_FS_APP."/$dir";
		echo "Scanning $d\n";
		findfiles($d, 'process');
	}
	foreach($pronto_dirs as $dir) {
		$d = DIR_FS_PRONTO."/$dir";
		echo "Scanning $d\n";
		findfiles($d, 'process');
	}
}
ksort($STRINGS);

@mkdir(dirname($MESSAGES_FILE), 0755, true);
$mode = file_exists($MESSAGES_FILE) ? 'update' : 'create';
$fp = fopen($MESSAGES_FILE, 'w');
if($fp === false) die("Error: cannot open file for write: $MESSAGES_FILE\n");

$now = date('Y-m-d H:i:s');
$header = <<<EOT
<?php
/*
 * Generated by i18n_scan.php at $now
 */

\$LANGUAGE_CODE = '$LANG_CODE';
\$LANGUAGE_NAME = '$LANG_NAME';

\$MESSAGES = array(

EOT;
fputs($fp, $header);

foreach($STRINGS as $str=>$arr) {
	$s = str_replace('"', "\\\"", $str);
	$t = str_replace('"', "\\\"", $arr['translated']);
	if($OPTIONS['locations'] && count($arr['locations'])) {
		fputs($fp, "\t// ".join(', ',$arr['locations'])."\n");
	}
	fputs($fp, "\t\"$s\" => \"$t\",\n");
}
fputs($fp, ");\n\n?>");

fclose($fp);

echo "\nSuccessfully {$mode}d $MESSAGES_FILE\n";

?>
