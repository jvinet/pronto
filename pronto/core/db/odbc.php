<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: DB driver for ODBC.
 *
 **/

class DB_ODBC extends DB_Base
{
	function DB_ODBC($conn, $persistent) {
		$this->safesql =& new SafeSQL_ANSI;

		$this->conn = odbc_connect("DRIVER=FreeTDS;SERVER=".$conn['host'].";DATABASE=".$conn['name'], $conn['user'], $conn['pass']);
		if(!$this->conn) {
			$this->_catch("Unable to connect to the database!");
			return false;
	 	}
		return true;
	}

	function _catch($msg="") {
		if(!$this->error = odbc_errormsg($this->conn)) return true;
		$this->error($msg."<br>{$this->query} \n {$this->error}");
	}

	function select($db) {
	}		

	function get_table_defn($table) {
		return false;
	}

	function run_query($sql) {
		return odbc_exec($sql, $this->conn);
	}

	function fetch_row(&$q) {
		return odbc_fetch_row($q);
	}

	function fetch_array(&$q) {
		return odbc_fetch_array($q);
	}

	function fetch_field(&$q, $num) {
		return odbc_field_name($q, $num);
	}

	function num_fields(&$q) {
		return odbc_num_fields($q);
	}

	function num_orws(&$q) {
		return odbc_num_rows($q);
	}

	function get_insert_id() {
		$r = odbc_fetch_row(odbc_exec("select @@IDENTITY"));
		return $r[0];
	}
}

?>
