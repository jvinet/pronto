<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: DB driver for PostgreSQL.
 *
 **/

class DB_PostgreSQL extends DB_Base
{
	function DB_PostgreSQL($conn, $persistent) {
		$this->safesql =& new SafeSQL_ANSI;
		$this->type = 'postgresql';
		
		$connstr = "dbname={$conn['name']}";
		if($conn['user']) $connstr .= " user={$conn['user']}";
		if($conn['pass']) $connstr .= " password={$conn['pass']}";
		if($conn['host']) $connstr .= " host={$conn['host']}";
		if($conn['port']) $connstr .= " port={$conn['port']}";

		if($persistent) {
			$this->conn = pg_pconnect($connstr);
		} else {
			$this->conn = pg_connect($connstr);
		}

		if(!$this->conn) {
			$this->_catch("Unable to connect to the database!");
			return false;
		};

		return true;
	}

	function _catch($msg="") {
		if(!$this->error = pg_last_error()) return true;
		$this->error($msg."<br>{$this->query} \n {$this->error}");
	}

	function get_table_defn($table) {
		return false;
	}

	function run_query($sql) {
		return pg_query($this->conn, $sql);
	}

	function fetch_row(&$q) {
		return pg_fetch_row($q);
	}

	function fetch_array(&$q) {
		return pg_fetch_assoc($q);
	}

	function fetch_field(&$q, $num) {
		return pg_fetch_row($q, $num);
	}

	function num_fields(&$q) {
		return pg_num_fields($q);
	}

	function num_rows(&$q) {
		return pg_num_rows($q);
	}

	function get_insert_id() {
		// requires PostgreSQL 8.1 or higher
		return $this->get_value("SELECT LASTVAL()");
	}
}

?>
