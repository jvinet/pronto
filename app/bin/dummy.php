#!/usr/bin/env php
<?php
/**
 * An example of a commandline script in Pronto.
 *
 */

@chdir(dirname($argv[0]).'/..');
require_once('profiles/cmdline.php');

$m_user =& Factory::model('user');

$users = $m_user->find("status='active'");
while($u = $users->load_one()) {
	echo "{$u['email']}\n";
}

exit(0);

?>
