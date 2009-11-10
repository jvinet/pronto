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
	var $table_defns = array();

	function DB_PostgreSQL($conn, $persistent) {
		$this->safesql = new SafeSQL_ANSI;
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
		if(isset($this->table_defns[$table])) return $this->table_defns[$table];

		// XXX: this only works if there's already a row in the table
		$fs = array();
		$res = pg_query($this->conn, "SELECT * FROM $table LIMIT 1");
		$i = pg_num_fields($res);
		for($j = 0; $j < $i; $j++) $fs[pg_field_name($res, $j)] = '';

		$this->table_defns[$table] = $fs;
		return $fs;
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
		// don't use $this->get_value() or we'll infinitely recurse
		$q = @pg_query($this->conn, "SELECT lastval()");
		return $q ? array_shift($this->fetch_row($q)) : 0;
	}

	function free_result($q) {
		return pg_free_result($q);
	}
}

?>
