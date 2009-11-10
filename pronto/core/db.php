<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: DB interface, used by models and page controllers.
 *
 **/
require_once(DIR_FS_PRONTO.DS.'extlib'.DS.'safesql.php');

class DB_Base
{
	var $conn;
	var $query;
	var $result;
	var $error;
	var $insert_id;
	var $echo = false;
	var $debug = false;
	var $profile = false;
	var $profile_data = array();

	/**
	 * Exception/error catcher
	 *
	 * @param string $msg
	 */
	function _catch($msg="") {
		$this->error($msg);
	}

	/**
	 * Exit with an error message
	 *
	 * @param string $msg
	 */
	function error($msg) {
		throw new Exception($msg);
	}

	/**
	 * Generate a SQL string by stitching together SQL chunks
	 *
	 * @param string $select
	 * @param string $from
	 * @param string $where
	 * @param string $group
	 * @param string $having
	 * @param string $order
	 * @param string $limit
	 * @return string
	 */
	function build_sql($select, $from, $where='', $group='', $having='', $order='', $limit='')
	{
		$sql = "SELECT $select FROM $from";
		if($where)  $sql .= " WHERE $where";
		if($group)  $sql .= " GROUP BY $group";
		if($having) $sql .= " HAVING $having";
		if($order)  $sql .= " ORDER BY $order";
		if($limit)  $sql .= " LIMIT $limit";
		return $sql;
	}

	/**
	 * Generate a query string by substituting placeholders (eg, %i) with their
	 * real values
	 *
	 * @param string $query_str Query string
	 * @param array $query_arg Associative array of variables to substitute in
	 * @param bool $bypass If set, bypass variable substitution
	 * @return string The resulting SQL string
	 */
	function &query($query_str, $query_arg="", $bypass=false) {
		if($bypass == true) return($query_str);
		return $this->safesql->query($query_str, $query_arg);
	}

	/**
	 * Execute the query, first calling Query() to finalize the query string
	 *
	 * @param string $query_str Query string
	 * @param array $query_arg Associative array of variables to substitute in
	 * @param bool $bypass If set, bypass variable substitution
	 * @return mixed The result identifier
	 */
	function &execute($query_str, $query_arg="", $bypass=false) {
		$this->query = $bypass ? $query_str : $this->query($query_str, $query_arg);

		if($this->profile !== false) {
			$bt = debug_backtrace();
			$loc = '';
			if(isset($bt[1])) {
				$loc = $bt[1]['file'].':'.$bt[1]['line'].' ';
			}
			$start = array_sum(explode(" ",microtime()));
			if($this->profile !== true) {
				$fp = fopen($this->profile, "a+");
				fwrite($fp, $loc.$this->query."\n");
				fclose($fp);
			}
		}

		$this->result = $this->run_query($this->query) or $this->_catch();

		if($this->profile !== false) {
			$delta = round((array_sum(explode(" ",microtime()))-$start)*1000, 4);
			if($this->profile === true) {
				$this->profile_data[] = array(
					'query' => $this->query,
					'time'  => $delta
				);
			} else {
				$fp = fopen($this->profile, "a+");
				fwrite($fp, "\t\tD=$delta ms\n\n");
				fclose($fp);
			}
		}

		if($this->echo == true) echo $this->query."\n";
		$this->insert_id = $this->get_insert_id();
		return $this->result;
	}
	
	/**
	 * Return a single row from a query string, false if not found
	 *
	 * @param string $query_str Query string
	 * @param array $query_arg Query arguments
	 * @return mixed
	 */
	function &get_item($query_str, $query_arg="") {
		$q = $this->execute($query_str, $query_arg);
		if(($item = $this->fetch_array($q, true))) {
			return $item;
		} else {
			$ret = false;
			return $ret;
		}
	}

	/**
	 * Return a single row from a query string using PK for lookup, false if not found
	 *
	 * @param string $table
	 * @param string $pk Value of ID/PK field
	 * @param string $pk_col Name of PK column in this table
	 * @return mixed
	 */
	function get_item_by_pk($table, $pk, $pk_col='id') {
		return $this->get_item("SELECT * FROM $table WHERE \"$pk_col\"=%i LIMIT 1", array($pk));
	}

	/**
	 * Return all rows from a query
	 *
	 * @param string $query_str Query string
	 * @param array $query_arg Query arguments
	 * @param string $key If set, return only this column instead of the entire row
	 * @return mixed
	 */
	function &get_all($query_str, $query_arg="", $key="") {
		$result = $this->execute($query_str, $query_arg);
		$list = array();
		while($row = @$this->fetch_array($result)) {
			if(empty($key)) {
				$list[] = $row;
			} else {
				$list[$row[$key]] = $row;
			}
		}
		$this->free_result($result);
		return $list;
	}

	/**
	 * Return a single value from a query string, false if not found
	 *
	 * @param string $query_str Query string
	 * @param array $query_arg Query arguments
	 * @param string $value If non-empty, use this value from the resulting row,
	 *                      otherwise use the first one
	 * @return mixed
	 */
	function &get_value($query_str, $query_arg="", $value="") {
		if(empty($value)) {
			if($item = $this->fetch_row($this->execute($query_str, $query_arg), true)) { 
				return $item[0];
			} else {
				$ret = false;
				return $ret;
			}
		} else {
			if($item = $this->fetch_array($this->execute($query_str, $query_arg), true)) { 
				return $item[$value];
			} else {
				$ret = false;
				return $ret;
			}
		}
	}

	/**
	 * Return an array of value from a query string
	 *
	 * @param string $query_str Query string
	 * @param array $query_arg Query arguments
	 * @param string $value If non-empty, use this value from the resulting row,
	 *                      otherwise use the first one
	 * @return array
	 */
	function &get_values($query_str, $query_arg="", $value="") {
		$result = $this->execute($query_str, $query_arg);
		$values = array();
		if(empty($value)) {
			while($item = $this->fetch_row($result)) {
				$values[] = $item[0];
			}
		} else {
			while($item = $this->fetch_array($result)) {
				$values[] = $item[$value];
			}
		}
		$this->free_result($result);
		return $values;
	}

	/**
	 * Return an associative array of key=>value pairs.  The first
	 * item from the SELECT clause will be the key, and the second
	 * will be the value.  If more than two items are in the SELECT,
	 * then everything after the first will be returned as a sub-array.
	 *
	 * @param string $query_str Query string
	 * @param array $query_arg Query arguments
	 * @return mixed
	 */
	function &get_item_pair($query_str, $query_arg="") {
		if($item = $this->fetch_array($this->execute($query_str, $query_arg), true)) {
			if(count($item) > 2) {
				$key = array_shift($item);
				$sub = array();
				foreach($item as $k=>$v) {
					$sub[$k] = $v;
				}
				return array($key => $sub);
			} else {
				$key = array_shift($item);
				$val = array_shift($item);
				return array($key => $val);
			}
		} else {
			$ret = false;
			return $ret;
		}
	}

	/**
	 * Return an associative array of key=>value pairs.  The first
	 * item from the SELECT clause will be the key, and the second
	 * will be the value.  If more than two items are in the SELECT,
	 * then everything after the first will be returned as a sub-array.
	 *
	 * @param string $query_str Query string
	 * @param array $query_arg Query arguments
	 * @return mixed
	 */
	function &get_all_pair($query_str, $query_arg="") {
		$result = $this->execute($query_str, $query_arg);
		$list = array();
		
		while($item = @$this->fetch_array($result)) {
			if(count($item) > 2) {
				$key = array_shift($item);
				$sub = array();
				foreach($item as $k=>$v) {
					$sub[$k] = $v;
				}
				$list[$key] = $sub;
			} else {
				$key = array_shift($item);
				$val = array_shift($item);
				$list[$key] = $val;
			}
		}
		$this->free_result($result);
		return $list;
	}

	/**
	 * Insert multiple records.
	 *
	 * @param string $table
	 * @param array $data
	 * @param mixed $aFields
	 */
	function insert_all($table, $data, $aFields="", $mode='insert') {
		if(!$data) return;
		$fields = array();
		$values = array();
		$arg = array();
		foreach($data as $k=>$row) {
			$val = array();
			if(is_array($aFields)) {
				$new_row = array();
				foreach($aFields as $f=>$v) {
					$new_row[$f] = isset($row[$f]) ? $row[$f] : $v['value'];
				}
				$row = $new_row;
			}
			foreach($row as $f=>$v) {
				if(!$k) $fields[] = "\"$f\"";
				if(is_array($v)) {
					$val[] = $v[0];
				} else {
					$val[] = "'%s'";
					$arg[] = $v;
				}
			}
			$values[] = "(".join(",", $val).")";
		}
		$this->execute("$mode INTO ".$table." (".join(",",$fields).") VALUES ".join(",",$values), $arg);
		return $this->insert_id;
	}

	/**
	 * Replace multiple records.
	 *
	 * @param string $table
	 * @param array $data
	 * @param mixed $aFields
	 */
	function replace_all($table, $data, $aFields="") {
		return $this->insert_all($table, $data, $aFields, 'replace');
	}

	/**
	 * Update multiple records.
	 *
	 * @param string $table
	 * @param array $data
	 * @param string $where
	 * @param string $where_arg
	 * @param mixed $aUpdateFields
	 */
	function update_all($table, $data, $where="", $where_arg="", $aUpdateFields="") {
		if(!$data) return;
		if(!$where_arg) $where_arg = array();
		if($where) $aWhere[] = $where;
		if($aUpdateFields) {
			foreach($aUpdateFields as $f) $aWhere[] = $f."='%s'";
		}
		if($aWhere) $sWhere = " WHERE (".join(") AND (", $aWhere).")";
		foreach($data as $k=>$row) {
			$aFields = array();
			$aArg = array();
			foreach($row as $f=>$v) {
				if(!is_numeric($f)) {
					if(is_array($v)) {
						$val = $v[0];
					} else {
						$val = "'%s'";
						$aArg[] = $v;
					}
				}
				$aFields[] = "\"$f\"=$val";
			}
			$aArg = array_merge($aArg, $where_arg);
			if($aUpdateFields) {
				foreach($aUpdateFields as $f) {
					$aArg[] = $row[$f];
				}
			}
			$this->execute("UPDATE ".$table." SET ".join(",",$aFields).$sWhere, $aArg);
		}
	}

	/**
	 * Insert a record
	 *
	 * @param string $sTable
	 * @param array $aRow
	 * @param string $mode Set to "replace" to use REPLACE INTO instead of INSERT INTO
	 * @return int Insert ID of new row
	 */
	function insert_row($sTable, $aRow, $mode='insert') {
		$mode = $mode == 'replace' ? 'REPLACE' : 'INSERT';
		$fs = $this->get_table_defn($sTable);

		$aFields = array();
		$aValues = array();
		$aArgs = array();
		foreach($aRow as $k=>$v) {
			// if this RDBMS does not support self introspection (ie, examining the
			// table schema) then we can't know which fields in this array map to
			// actual column names.
			if(!$fs || isset($fs[$k])) {
				$aFields[] = "\"$k\"";
				$aValues[] = "'%s'";
				$aArgs[] = $v;
			}
		}
		$this->execute("$mode INTO ".$sTable." (".join(",",$aFields).") VALUES (".join(",",$aValues).")", $aArgs);

		return $this->insert_id;
	}

	/**
	 * Replace a record (REPLACE INTO)
	 *
	 * @param string $sTable
	 * @param array $aRow
	 * @return int Insert ID of new row
	 */
	function replace_row($sTable, $aRow) {
		return $this->insert_row($sTable, $aRow, 'replace');
	}

	/**
	 * Update a record
	 *
	 * @param string $sTable
	 * @param array $aRow
	 * @param string $sWhere WHERE clause to use for update
	 * @param array $aWhereArgs Arguments to substitute into WHERE clause
	 */
	function update_row($sTable, $aRow, $sWhere, $aWhereArgs=array()) {
		$fs = $this->get_table_defn($sTable);

		$aFields = array();
		$aArgs = array();
		foreach($aRow as $k=>$v) {
			// if this RDBMS does not support self introspection (ie, examining the
			// table schema) then we can't know which fields in this array map to
			// actual column names.
			if(!$fs || isset($fs[$k])) {
				$aFields[] = "\"$k\"='%s'";
				$aArgs[] = $v;
			}
		}
		if(is_array($aWhereArgs) && !empty($aWhereArgs)) {
			foreach ($aWhereArgs as $v) $aArgs[] = $v;
		}
		$this->execute("UPDATE \"".$sTable."\" SET ".join(",", $aFields)." WHERE ".$sWhere, $aArgs);
	}

	/**
	 * Insert or Update a record
	 *
	 * @param string $sTable
	 * @param array $aRow
	 * @param array $aKey Array of key/value pairs.  If matching record is found,
	 *                    then an update_row() is called, otherwise insert_row()
	 *                    is called.
	 */
	function insert_update_row($sTable, $aRow, $aKey) {
		$aKeyWhere = array();
		$aKeyArgs = array();
		foreach($aKey as $k=>$v) {
			$aKeyWhere[] = "\"$k\"='%s'";
			$aKeyArgs[] = $v;
		}
		$sKeyWhere = join(' AND ', $aKeyWhere);

		if($this->get_item('SELECT * FROM '.$sTable.' WHERE ('.$sKeyWhere.')', $aKeyArgs)) {
			$this->update_row($sTable, $aRow, $sKeyWhere, $aKeyArgs);
		} else {
			$this->insert_row($sTable, array_merge($aKey, $aRow));
		}
	}

	/**
	 * Increment a column within a row specified by the key column(s).  If
	 * the row does not exist, create it, setting the counter to 1.  Useful
	 * for updating statistical counters.
	 *
	 * @param string $sTable
	 * @param string $sField Name of the column that will be incremented or inserted.
	 * @param array $aKey Array of key/value pairs.  If matching record is found,
	 *                    then $sField will be incremented by one.  Otherwise, a row
	 *                    will be inserted and $sField will be set to one.
	 */
	function increment_row($sTable, $sField, $aKey) {
		$aKeyWhere = array();
		$aKeyArgs = array();
		foreach($aKey as $k=>$v) {
			$aKeyWhere[] = "\"$k\"='%s'";
			$aKeyArgs[] = $v;
		}
		$sKeyWhere = join(' AND ', $aKeyWhere);

		if($this->get_item('SELECT * FROM '.$sTable.' WHERE ('.$sKeyWhere.')', $aKeyArgs)) {
			$this->execute("UPDATE $sTable SET $sField=$sField+1 WHERE ($sKeyWhere)", $aKeyArgs);
		} else {
			$this->insert_row($sTable, array_merge($aKey, array($sField=>'1')));
		}
	}

}

?>
