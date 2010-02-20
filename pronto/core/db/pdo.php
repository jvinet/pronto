<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: DB driver for PDO.
 *
 * NOTE: Requires PHP5.
 *
 **/

class DB_PDO extends DB_Base
{
	function DB_PDO($conn, $persistent) {
		$this->safesql = new SafeSQL_ANSI;
		$this->type = 'pdo';
		
		$att = array(PDO::ATTR_PERSISTENT => $persistent);
		try {
			$this->conn = new PDO($conn['dsn'], $conn['user'], $conn['pass'], $att);
		} catch(PDOException $e) {
			$this->_catch("Unable to connect to database: ".$e->getMessage());
			return false;
		}

		if(!$this->conn) {
			$this->_catch("Unable to connect to database!");
			return false;
		}

		$this->driver = $this->conn->getAttribute(PDO::ATTR_DRIVER_NAME);
		if($this->driver == 'mysql' && DB_MYSQL_ANSI_MODE !== false) {
			// Put MySQL into ANSI mode (or at least closer to it)
			//   (this requires MySQL 4.1 or later)
			$this->run_query("SET SESSION sql_mode='ANSI'");
		}

		return true;
	}

	function _catch($msg="") {
		if($this->conn) {
			$this->error = $this->conn->errorInfo();
			if(!$this->error) return true;
			$this->error = $this->error[2];
			$this->error($msg."<br>{$this->query}\n {$this->error}");
		} else {
			$this->error($msg);
		}
	}

	function get_table_defn($table) {
		if($this->driver == 'mysql') {
			return $this->get_all_pair("EXPLAIN \"$table\"");
		}
		if($this->driver == 'sqlite') {
			$defn = array();
			$a = $this->get_all("PRAGMA table_info(\"$table\")");
			foreach($a as $v) $defn[$v['name']] = $v['type'];
			return $defn;
		}

		$defn = array();
		$q = $this->run_query("SELECT * FROM \"$table\" LIMIT 1");
		for($i = 0; $i < $this->num_fields($q); $i++) {
			$col = $q->getColumnMeta($i);
			$defn[$col['name']] = $col['native_type'];
		}
		return $defn;
	}

	function run_query($sql) {
		return $this->conn->query($sql);
	}

	function fetch_row(&$q) {
		return $q->fetch(PDO::FETCH_NUM);
	}

	function fetch_array(&$q) {
		return $q->fetch(PDO::FETCH_ASSOC);
	}

	function fetch_field(&$q, $num) {
		return $q->fetchColumn($num);
	}

	function num_fields(&$q) {
		return $q->columnCount();
	}

	function num_rows(&$q) {
		return $q->rowCount();
	}

	function get_insert_id() {
		return $this->conn->lastInsertId();
	}

	function free_result($q) {
		return true;
	}
}

?>
