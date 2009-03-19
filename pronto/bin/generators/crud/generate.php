<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: A generator for a CRUD model/controller/template files.
 *
 *              WARNING: Currently only MySQL is supported.
 *
 **/

$genpath = dirname(__FILE__);
if(!file_exists('profiles/cmdline.php')) {
	die("Run this script from your top-level app directory (eg, /var/www/html/app)\n");
}
require_once('profiles/cmdline.php');

$db = Registry::get('pronto:db');

if($_SERVER['argc'] < 3) {
	echo "usage: php generate.php <entity> <db_table> [basename] [module]\n\n";
	echo "If set, [basename] will be the basename for all files generated.\n";
	echo "For example, if basename is 'foo', then the generator will create:\n";
	echo "   * pages/foo.php\n";
	echo "   * models/foo.php\n";
	echo "   * templates/foo/create.php\n";
	echo "   * templates/foo/list.php\n\n";
	echo "If not specified, [basename] will default to <entity>\n\n";
	echo "If [module] is set, then the generated files will be placed in\n";
	echo "modules/<module>/ instead of your top-level app directory.\n\n";
	exit(0);
}

$entity_name = $_SERVER['argv'][1];
$human_name  = str_replace(' ','_',ucwords(str_replace('_',' ',$entity_name)));
$db_table    = $_SERVER['argv'][2];
$fnbase      = isset($_SERVER['argv'][3]) ? $_SERVER['argv'][3] : $entity_name;
$module      = isset($_SERVER['argv'][4]) ? $_SERVER['argv'][4] : '';
$db_columns  = array();
$basepath    = $module ? DIR_FS_APP.DS.'modules'.DS.$module : DIR_FS_APP;

echo "Entity:   $entity_name\n";
echo "DB Table: $db_table\n\n";

echo "Examining DB table for data introspection...\n";
$has_id = false;
foreach($db->get_all("EXPLAIN $db_table") as $r) {
	if($r['Field'] == 'id') {
		$has_id = true;
		continue;
	}
	if(ereg('_id$', $r['Field'])) {
		echo "Warning: Ignoring {$r['Field']} - handle foreign keys manually\n";
		continue;
	}
	$col = array('name' => $r['Field']);

	$type = array_shift(explode('(', $r['Type']));
	switch($type) {
		case 'date':
		case 'datetime':
			$col['type'] = 'date';
			break;
		case 'enum':
			preg_match('/\((.*)\)$/', $r['Type'], $m);
			$col['options'] = explode(',', $m[1]);
			$col['type'] = 'select';
			break;
		default:
			$col['type'] = 'text';
	}
	$db_columns[] = $col;
}
if(!$has_id) {
	echo "Warning: Table does not contain an 'id' field -- this is usually needed\n";
}
echo "\n";

/************************************************************************
 * INTERFACE: LIST
 ***********************************************************************/
$longest = 0;
foreach($db_columns as $col) {
	if(strlen($col['name']) > $longest) $longest = strlen($col['name']);
}

$list_items = '';
foreach($db_columns as $col) {
	$n = ucwords(str_replace('_',' ',$col['name']));
	$list_items .= "\t\t'{$col['name']}' ";
	for($i = 0; $i < $longest-strlen($col['name']); $i++) $list_items .= ' ';
	$list_items.= "=> array('label'=>__('$n'), 'type'=>'{$col['type']}'";

	if(isset($col['options'])) {
		$list_items .= ", 'options'=>array(";
		foreach($col['options'] as $k) {
			$list_items .= "$k=>$k,";
		}
		$list_items = substr($list_items, 0, strlen($list_items)-1).')';
	}
	$list_items .= "),\n";
}

/************************************************************************
 * INTERFACE: CREATE/EDIT
 ***********************************************************************/
$create_items = '';
foreach($db_columns as $col) {
	$n = ucwords(str_replace('_',' ',$col['name']));
	$create_items .= "\t\t\t'{$col['name']}' ";
	for($i = 0; $i < $longest-strlen($col['name']); $i++) $create_items .= ' ';

	$create_items .= "=> array(";
	$create_items .= "'prompt'=>__('$n').':', ";
	$create_items .= "'type'=>'{$col['type']}'";
	if(isset($col['options'])) {
		$create_items .= ", 'options'=>array(";
		foreach($col['options'] as $k) {
			$create_items .= "$k=>$k,";
		}
		$create_items = substr($create_items, 0, strlen($create_items)-1).")";
	}
	$create_items .= "),\n";
}

$keys = array('_ENTITY_',  '_UENTITY_',   '_DEFAULT_SORT_',
              '_DB_TABLE_','_LIST_ITEMS_','_CREATE_ITEMS_',
              '_MODULE_DESIGNATION_');
$vals = array($entity_name, $human_name, $db_columns[0]['name'],
              $db_table,    $list_items, $create_items,
              $module ? "\n\t\t".'$this->set_module('."'$module');\n" : '');


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

// Generate model
if(generate($genpath.'/templates/model.tpl.php', "$basepath/models/$fnbase.php", $keys, $vals)) {
	echo "New Model:           $basepath/models/$fnbase.php\n";
}
// Generate page controller
if(generate($genpath.'/templates/page.tpl.php', "$basepath/pages/$fnbase.php", $keys, $vals)) {
	echo "New Page Controller: $basepath/pages/$fnbase.php\n";
}

@mkdir("$basepath/templates/$fnbase");
// Generate "list" template
if(generate($genpath.'/templates/t_list.tpl.php', "$basepath/templates/$fnbase/list.php", $keys, $vals)) {
	echo "New Template:        $basepath/templates/$fnbase/list.php\n";
}
// Generate the "create/edit" template
if(generate($genpath.'/templates/t_create.tpl.php', "$basepath/templates/$fnbase/create.php", $keys, $vals)) {
	echo "New Template:        $basepath/templates/$fnbase/create.php\n";
}

echo "\n";
exit(0);

?>
