<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: General lower-level routines for files/directories and
 *              other OS functions.
 *
 **/

class ppOs extends Plugin
{
	function ppOs()
	{
		$this->Plugin();
	}

	/*
	 * Remove a file if it exists
	 */
	function delfile($path)
	{
		if(file_exists($path)) {
			return @unlink($path);
		}
		return false;
	}

	/*
	 * Recursively remove a directory
	 */
	function deltree($path)
	{
		foreach(glob($path.DS.'*') as $f) {
			if(is_dir($f)) {
				$this->deltree($f);
			} else {
				unlink($f);
			}
		}
		rmdir($path);
	}

	/*
	 * Delete all files matching a pattern
	 */
	function delpattern($pattern)
	{
		foreach(glob($pattern) as $f) {
			unlink($f);
		}
	}

}

?>
