<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: DB driver for SQLite3.  This driver uses the SQLite3 extension,
 *              available from PECL:
 *                $ pecl install channel://pecl.php.net/sqlite3-0.6
 *
 **/

class DB_SQLite3 extends DB_Base
{
	function DB_SQLite3($conn, $persistent) {
		$this->safesql =& new SafeSQL_ANSI;
		$this->type = 'sqlite3';
		
		$this->conn = new SQLite3($conn['file']);

		if(!$this->conn) {
			$this->_catch("Unable to connect to the database!");
			return false;
		}

		return true;
	}

	function _catch($msg="") {
		if(!$this->error = $this->conn->lastErrorMsg()) return true;
		$this->error($msg."<br>{$this->query}\n {$this->error}");
	}

	function get_table_defn($table) {
		return false;
	}

	function run_query($sql) {
		return $this->conn->query($sql);
	}

	function fetch_row(&$q) {
		return $q->fetchArray(SQLITE3_NUM);
	}

	function fetch_array(&$q) {
		return $q->fetchArray(SQLITE3_ASSOC);
	}

	function fetch_field(&$q, $num) {
		$a = $this->fetch_row($q);
		return $a[$num];
	}

	function num_fields(&$q) {
		return $q->numColumns();
	}

	function num_rows(&$q) {
		// For some reason, there's no SQLite3_result::numRows(), which
		// seems like a pretty big oversight.  This is awfully inefficient,
		// so try not to use this method if you can avoid it.
		$ct = 0;
		while($row = $q->fetchArray(SQLITE3_NUM)) $ct++;
		$q->reset();
		return $ct;
	}

	function get_insert_id() {
		return $this->conn->lastInsertRowID();
	}
}

?>
