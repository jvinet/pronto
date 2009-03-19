<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: DB driver for SQLite2.
 *
 **/

class DB_SQLite extends DB_Base
{
	function DB_SQLite($conn, $persistent) {
		$this->safesql =& new SafeSQL_ANSI;
		
		if($persistent) {
			$this->conn = sqlite_popen($conn['file']);
		} else {
			$this->conn = sqlite_open($conn['file']);
		}

		if(!$this->conn) {
			$this->_catch("Unable to connect to the database!");
			return false;
		}

		return true;
	}

	function _catch($msg="") {
		if(!$this->error = sqlite_error_string(sqlite_last_error($this->conn))) return true;
		$this->error($msg."<br>{$this->query}\n {$this->error}");
	}

	function get_table_defn($table) {
		if(PHP_VERSION < 5) return false;

		// PHP5 only
		return sqlite_fetch_column_types($table, $this->conn, SQLITE_ASSOC);
	}

	function run_query($sql) {
		return sqlite_query($sql, $this->conn);
	}

	function fetch_row(&$q) {
		return sqlite_fetch_array($q, SQLITE_NUM);
	}

	function fetch_array(&$q) {
		return sqlite_fetch_array($q, SQLITE_ASSOC);
	}

	function fetch_field(&$q, $num) {
		$row = $this->fetch_row($q);
		return $row[$num];
	}

	function num_fields(&$q) {
		return sqlite_num_fields($q);
	}

	function num_rows(&$q) {
		return sqlite_num_rows($q);
	}

	function get_insert_id() {
		return sqlite_last_insert_rowid($this->conn);
	}
}

?>
