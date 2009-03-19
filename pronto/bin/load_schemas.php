<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Create new tables in the database from the schema files.
 *
 **/

if(!file_exists('profiles/cmdline.php')) {
	die("Run this script from your top-level app directory (eg, /var/www/html/app)\n");
}
require_once('profiles/cmdline.php');
$GLOBALS['db'] = Registry::get('pronto:db');

$args = $_SERVER['argv'];
array_shift($args);

$opts = getopt("t:m:ca");
for($i = 0; $i < count($opts); $i++) array_shift($args);

if(empty($opts)) {
	echo "usage: php load_schemas.php [-t <table>] [-m <module>] [-c] [-a]\n\n";
	echo "This script will scan for .sql schema files and execute them within your RDBMS.\n\n";
	echo "Options (use one):\n";
	echo "  -t <table>   Scan all enabled modules (as set in config/config.php) and\n";
	echo "               config/sql for schema file named <table>.sql\n";
	echo "  -m <module>  Scan <module>/config/sql for schema files\n";
	echo "  -c           Scan config/sql for schema files\n";
	echo "  -a           Scan all enabled modules (as set in config/config.php) and\n";
	echo "               config/sql for schema files\n\n";
	exit(0);
}

$files = array();
if(isset($opts['t'])) {
	// Scan all directories for the specified table file
	$dirs = array(DIR_FS_APP.DS.'config'.DS.'sql');
	foreach(explode(' ', MODULES) as $mod) {
		$dirs[] = DIR_FS_APP.DS.'modules'.DS.$mod.DS.'config'.DS.'sql';
	}
	foreach($dirs as $dir) {
		$fn = $dir.DS.$opts['t'].'.sql';
		if(file_exists($fn)) {
			$files = array($fn);
			break;
		}
	}
} else if(isset($opts['c'])) {
	$files = glob(DIR_FS_APP.DS.'config'.DS.'sql'.DS.'*.sql');
} else if(isset($opts['m'])) {
	$files = glob(DIR_FS_APP.DS.'modules'.DS.$opts['m'].DS.'config'.DS.'sql'.DS.'*.sql');
} else if(isset($opts['a'])) {
	$files = glob(DIR_FS_APP.DS.'config'.DS.'sql'.DS.'*.sql');
	foreach(explode(' ', MODULES) as $mod) {
		$files = array_merge($files, glob(DIR_FS_APP.DS.'modules'.DS.$mod.DS.'config'.DS.'sql'.DS.'*.sql'));
	}
}

if(empty($files)) {
	echo "No files found.\n";
	exit(1);
}

foreach($files as $f) {
	unset($GLOBALS['insql_done'], $GLOBALS['LFILE']);
	echo "Loading $f...\n";
	do_multi_sql('', $f);
}

function do_sql($sql) {
	$sql = trim($sql);
	if(empty($sql)) return true;

	$db =& $GLOBALS['db'];
	return !!$db->execute($sql);
}


/**
 * The following functions were hijacked from PHPMiniAdmin,
 * found here: http://phpminiadmin.sourceforge.net
 */
function do_multi_sql($insql, $fname) {
	$sql = '';
	$ochar = '';
	$is_cmt = '';
	$GLOBALS['insql_done'] = 0;
	while($str = get_next_chunk($insql, $fname)) {
		$opos =- strlen($ochar);
		$cur_pos = 0;
		$i = strlen($str);
		while($i--) {
			if($ochar) {
				list($clchar, $clpos) = get_close_char($str, $opos+strlen($ochar), $ochar);
				if($clchar) {
					if($ochar == '--' || $ochar == '#' || $is_cmt) {
						$sql .= substr($str, $cur_pos, $opos-$cur_pos);
					} else {
						$sql .= substr($str, $cur_pos, $clpos+strlen($clchar)-$cur_pos);
					}
					$cur_pos = $clpos + strlen($clchar);
					$ochar = '';
					$opos = 0;
				} else {
					$sql .= substr($str, $cur_pos);
					break;
				}
			} else {
				list($ochar, $opos) = get_open_char($str, $cur_pos);
				if($ochar == ';') {
					$sql .= substr($str, $cur_pos, $opos-$cur_pos+1);
					if(!do_sql($sql)) return 0;
					$sql='';
					$cur_pos = $opos + strlen($ochar);
					$ochar = '';
					$opos = 0;
				} else if (!$ochar) {
					$sql .= substr($str, $cur_pos);
					break;
				} else {
					$is_cmt = 0;
					if($ochar == '/*' && substr($str, $opos, 3) != '/*!') $is_cmt=1;
				}
			}
		}
	}

	if($sql) {
		if(!do_sql($sql)) return 0;
		$sql='';
	}

	return 1;
}

function get_next_chunk($insql, $fname) {
	global $LFILE, $insql_done;
	if($insql) {
		if($insql_done) {
			return '';
		} else {
			$insql_done = 1;
			return $insql;
		}
	}
	if(!$fname) return '';
	if(!$LFILE) {
		$LFILE = fopen($fname,"r+b") or die("Error: Can't open [$fname] file!\n");
	}
	return fread($LFILE, 64*1024);
}

function get_open_char($str, $pos) {
	if(preg_match("/(\/\*|^--|(?<=\s)--|#|'|\"|;)/", $str, $m, PREG_OFFSET_CAPTURE, $pos)) {
		$ochar = $m[1][0];
		$opos = $m[1][1];
	}
	return array($ochar, $opos);
}

function get_close_char($str, $pos, $ochar) {
	$aCLOSE = array(
		'\'' => '(?<!\\\\)\'|(\\\\+)\'',
		'"'  => '(?<!\\\\)"',
		'/*' => '\*\/',
		'#'  => '[\r\n]+',
		'--' => '[\r\n]+',
	);
	if($aCLOSE[$ochar] && preg_match("/(".$aCLOSE[$ochar].")/", $str, $m, PREG_OFFSET_CAPTURE, $pos)) {
		$clchar = $m[1][0];
		$clpos = $m[1][1];
		$sl = strlen($m[2][0]);
		if($ochar=="'" && $sl) {
			if($sl % 2) { // don't count as CLOSE char if number of slashes before ' ODD
				list($clchar, $clpos) = get_close_char($str, $clpos+strlen($clchar), $ochar);
			} else {
				$clpos += strlen($clchar)-1;
				$clchar = "'"; //correction
			}
		}
	}
	return array($clchar, $clpos);
}

?>
