<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: A generator for new Pronto modules.
 *
 **/

$genpath = dirname(__FILE__);
if(!file_exists('profiles/cmdline.php')) {
	die("Run this script from your top-level app directory (eg, /var/www/html/app)\n");
}
require_once('profiles/cmdline.php');

if($_SERVER['argc'] < 2) {
	echo "usage: php generate.php <module>\n\n";
	exit(0);
}

$module_name = $_SERVER['argv'][1];
$module_human_name = str_replace(' ','_',ucwords(str_replace('_',' ',$module_name)));
$module_dir = DIR_FS_APP.DS.'modules'.DS.$module_name;

echo "Module:  $module_name\n";
echo "Path:    $module_dir\n\n";

@mkdir($module_dir);
foreach(array('config','models','pages','plugins','templates') as $dir) {
	@mkdir($module_dir.DS.$dir);
}
@mkdir($module_dir.DS.'plugins'.DS.'page');
@mkdir($module_dir.DS.'plugins'.DS.'template');
@mkdir($module_dir.DS.'config'.DS.'sql');

$keys = array('{{MODULE_NAME}}','{{MODULE_HUMAN_NAME}}');
$vals = array($module_name, $module_human_name);

function generate($template, $dest, $keys, $vals)
{
	if(file_exists($dest)) {
		echo "$dest already exists.  Overwrite?  [Y/n]  ";
		$fp = fopen('php://stdin', 'r');
		$answer = trim(fgets($fp));
		fclose($fp);
		if(strcasecmp($answer,'Y') && $answer) return false;
	}
	$tmpl = implode('', file($template));
	$tmpl = str_replace($keys, $vals, $tmpl);
	$fp = fopen($dest, "w") or exit(1);
	fputs($fp, $tmpl);
	fclose($fp);
	return true;
}

if(generate($genpath.'/templates/config.tpl.php', $module_dir.DS.'config'.DS.'config.php', $keys, $vals)) {
	echo "New Config:     $module_dir".DS.'config'.DS."config.php\n";
}
if(generate($genpath.'/templates/urls.tpl.php', $module_dir.DS.'config'.DS.'urls.php', $keys, $vals)) {
	echo "New Config:     $module_dir".DS.'config'.DS."urls.php\n";
}
if(generate($genpath.'/templates/page.tpl.php', $module_dir.DS.'pages'.DS.'page.php', $keys, $vals)) {
	echo "New Controller: $module_dir".DS.'pages'.DS."page.php\n";
}
echo "\n";

echo "To enable this module, add it to the MODULES constant in config/config.php\n\n";
exit(0);

?>
