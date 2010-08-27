<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: DB driver for MS SQL.
 *
 **/

class DB_MSSQL extends DB_Base
{
	function DB_MSSQL($conn, $persistent) {
		$this->safesql = new SafeSQL_ANSI;
		$this->type = 'mssql';
		$this->params = $conn;
		$this->params['persistent'] = $persistent;

		// Connections are now lazy -- a DB connection is not made until
		// we actually have to perform a query.
	}

	function _catch($msg="") {
		if(!$this->error = mssql_get_last_message()) return true;
		$this->error($msg."<br>{$this->query} \n {$this->error}");
	}

	function connect()
	{
		$this->conn = mssql_connect($this->params['host'], $this->params['user'], $this->params['pass']);
		if(!$this->conn) {
			$this->_catch("Unable to connect to the database!");
			return false;
		}
		$this->select($this->params['name']);
		return true;
	}

	function select($db) {
		mssql_select_db($db, $this->conn) or $this->_catch("Unable to connect to the database!");
	}

	function get_table_defn($table) {
		return false;
	}

	function run_query($sql) {
		if(!$this->conn) {
			$this->connect();
		}
		return mssql_query($sql, $this->conn);
	}

	function fetch_row(&$q) {
		return mssql_fetch_row($q);
	}

	function fetch_array(&$q) {
		return mssql_fetch_assoc($q);
	}

	function fetch_field(&$q, $num) {
		return mssql_field_name($q, $num);
	}

	function num_fields(&$q) {
		return mssql_num_fields($q);
	}

	function num_rows(&$q) {
		return mssql_num_rows($q);
	}

	function get_insert_id() {
		$r = mssql_fetch_row(mssql_query("select @@IDENTITY", $this->conn));
		return $r[0];
	}

	function free_result($q) {
		return @mssql_free_result($q);
	}
}

?>
