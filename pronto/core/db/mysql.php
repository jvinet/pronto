<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: DB driver for MySQL.
 *
 **/

class DB_MySQL extends DB_Base
{
	function DB_MySQL($conn, $persistent) {
		$this->safesql =& new SafeSQL_MySQL;
		$this->type = 'mysql';
		
		if($persistent) {
			$this->conn = mysql_pconnect($conn['host'], $conn['user'], $conn['pass']);
		} else {
			$this->conn = mysql_connect($conn['host'], $conn['user'], $conn['pass']);
		}

		if(!$this->conn) {
			$this->_catch("Unable to connect to the database!");
			return false;
		}

		if(DB_MYSQL_ANSI_MODE !== false) {
			// Put MySQL into ANSI mode (or at least closer to it)
			//   (this requires MySQL 4.1 or later)
			$this->run_query("SET SESSION sql_mode='ANSI'");
		}

		$this->select($conn['name']);
		return true;
	}

	function _catch($msg="") {
		if(!$this->error = mysql_error()) return true;
		$this->error($msg."<br>{$this->query}\n {$this->error}");
	}

	function select($db) {
		mysql_select_db($db, $this->conn) or $this->_catch("Unable to connect to the database!");
	}		

	function get_table_defn($table) {
		return $this->get_all_pair("EXPLAIN \"$table\"");
	}

	function run_query($sql) {
		return mysql_query($sql, $this->conn);
	}

	function fetch_row(&$q) {
		return mysql_fetch_array($q, MYSQL_NUM);
	}

	function fetch_array(&$q) {
		return mysql_fetch_array($q, MYSQL_ASSOC);
	}

	function fetch_field(&$q, $num) {
		return mysql_fetch_field($q, $num);
	}

	function num_fields(&$q) {
		return mysql_num_fields($q);
	}

	function num_rows(&$q) {
		return mysql_num_rows($q);
	}

	function get_insert_id() {
		return mysql_insert_id();
	}
}

?>
