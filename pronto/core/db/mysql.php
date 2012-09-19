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
	// Store the time of the last executed query. If the last query was sent
	// over a minute ago, use mysql_ping() to check if the connection is still
	// alive. MySQL's wait_timeout parameter may be low enough that our DB
	// connection times out.
	var $last_query_at;

	function DB_MySQL($conn, $persistent) {
		$this->safesql = new SafeSQL_MySQL;
		$this->type = 'mysql';
		$this->params = $conn;
		$this->params['persistent'] = $persistent;

		// Connections are now lazy -- a DB connection is not made until
		// we actually have to perform a query.
	}

	function _catch($msg="") {
		if(!$this->error = mysql_error($this->conn)) return true;
		$this->error($msg."<br>{$this->query}\n {$this->error}");
	}

	function connect() {
		$this->close();

		$p = $this->params;
		if($p['persistent']) {
			$this->conn = mysql_pconnect($p['host'], $p['user'], $p['pass']);
		} else {
			$this->conn = mysql_connect($p['host'], $p['user'], $p['pass']);
		}

		if(!$this->conn) {
			$this->_catch("Unable to connect to the database: " . mysql_error());
			return false;
		}

		$this->last_query_at = time();

		if(DB_MYSQL_ANSI_MODE !== false) {
			// Put MySQL into ANSI mode (or at least closer to it)
			//   (this requires MySQL 4.1 or later)
			$this->run_query("SET SESSION sql_mode='ANSI'");
		}

		$this->select($p['name']);
		return true;
	}

	function close() {
		if($this->conn) return @mysql_close($this->conn);
		return false;
	}

	function ping() {
		if(!$this->conn) return $this->connect();

		$alive = @mysql_ping($this->conn);
		if(!$alive) {
			// attempt a reconnect
			return $this->connect();
		}
		return $alive;
	}

	function select($db) {
		mysql_select_db($db, $this->conn) or $this->_catch("Unable to connect to the database: " . mysql_error($this->conn));
	}		

	function get_table_defn($table) {
		return $this->get_all_pair("EXPLAIN \"$table\"");
	}

	function run_query($sql) {
		if(!$this->conn) {
			$this->connect();
		} else if(time() - $this->last_query_at > 60) {
			if(!$this->ping()) {
				$this->connect();
			}
		}
		$this->last_query_at = time();
		return mysql_query($sql, $this->conn);
	}

	function fetch_row(&$q, $free_result=false) {
		$a = mysql_fetch_array($q, MYSQL_NUM);
		if($free_result) mysql_free_result($q);
		return $a;
	}

	function fetch_array(&$q, $free_result=false) {
		$a = mysql_fetch_array($q, MYSQL_ASSOC);
		if($free_result) mysql_free_result($q);
		return $a;
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
		return mysql_insert_id($this->conn);
	}

	function free_result($q) {
		return @mysql_free_result($q);
	}
}

?>
