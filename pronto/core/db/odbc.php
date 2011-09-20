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
		$this->safesql = new SafeSQL_ANSI;
		$this->type = 'odbc';
		$this->params = $conn;
		$this->params['persistent'] = $persistent;

		// Connections are now lazy -- a DB connection is not made until
		// we actually have to perform a query.
	}

	function _catch($msg="") {
		if(!$this->error = odbc_errormsg($this->conn)) return true;
		$this->error($msg."<br>{$this->query} \n {$this->error}");
	}

	function connect() {
		$this->close();

		$p = $this->params;
		$this->conn = odbc_connect("DRIVER=FreeTDS;SERVER=".$p['host'].";DATABASE=".$p['name'], $p['user'], $p['pass']);
		if(!$this->conn) {
			$this->_catch("Unable to connect to the database!");
			return false;
	 	}
		return true;
	}

	function close()
	{
		if($this->conn) {
			@odbc_close($this->conn);
			return true;
		}
		return false;
	}

	function select($db) {
	}		

	function get_table_defn($table) {
		return false;
	}

	function run_query($sql) {
		if(!$this->conn) {
			$this->connect();
		}
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
		$r = odbc_fetch_row(odbc_exec("select @@IDENTITY", $this->conn));
		return $r[0];
	}

	function free_result($q) {
		return @odbc_free_result($q);
	}
}

?>
