<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: 
 *
 **/

$bindir = dirname(__FILE__);

$msgs_file = 'config/i18n/en/messages.php';
if(!file_exists($msgs_file)) {
	echo "Error: Cannot find $msgs_file\n\n";
	echo "Before running this script, you should generate an English messages file\n";
	echo "using the pronto/bin/i18n_scan.php script.\n\n";
	echo "Run this script from the top-level app-directory or from the top-level\n";
	echo "directory of a module.\n\n";
	exit(1);
}

$LANGS = array(
	'ar'    => 'Arabic',
	'zh_cn' => 'Chinese Simplified',
	'zh_tw' => 'Chinese Traditional',
	'nl'    => 'Dutch',
	'fr'    => 'French',
	'de'    => 'German',
	'el'    => 'Greek',
	'it'    => 'Italian',
	'ja'    => 'Japanese',
	'ko'    => 'Korean',
	'pt'    => 'Portuguese',
	'ru'    => 'Russian',
	'es'    => 'Spanish'
);

$orig = file_get_contents($msgs_file);
chdir('config/i18n');
foreach($LANGS as $code=>$name) {
	echo "Translating English to $name...\n";
	$new = str_replace('$LANGUAGE_CODE = \'en\';', '$LANGUAGE_CODE = \''.$code.'\';', $orig);
	$new = str_replace('$LANGUAGE_NAME = \'English\';', '$LANGUAGE_NAME = \''.$name.'\';', $new);
	@mkdir($code);
	file_put_contents("$code/messages.php", $new);

	system("php $bindir/google_translate.php $code/messages.php");
}


?>
