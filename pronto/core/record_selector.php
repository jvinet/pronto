<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Auxiliary class for models, used to fluently assemble
 *              result sets that can be operated on with primitive
 *              data manipulation operations (get, set, fetch, load).
 *
 **/

class RecordSelector
{
	var $model    = null;

	var $sql_where = null;
	var $sql_order = null;
	var $sql_limit = null;

	var $it_ptr;
	var $id_cache;

	/**
	 * Build a new RecordSelector object that can be used to operate
	 * on a subset of records from the model's table.  This constructor
	 * should only be called from within a data model object.
	 *
	 * @param $where string Where clause
	 * @param $args array Query arguments to substitute
	 * @param $model object The model object creating this selector
	 */
	function RecordSelector($where, $args, &$model)
	{
		$this->_reset_state();
		$this->model = $model;
		if($where) {
			$this->sql_where = "WHERE (".$this->model->db->query($where, $args).")";
		}
	}

	/**
	 * Add an additional WHERE restriction to the result set
	 *
	 * @param string "Where" sub-query (leave blank to select all)
	 * @param mixed  Additional query argument (optional)
	 * @param mixed  Additional query argument (optional) ...
	 * @return object RecordSelector object.
	 */
	function where()
	{
		$this->_reset_state();
		$args = func_get_args();
		$q    = array_shift($args);
		if(empty($q)) return $this;

		if($this->sql_where) {
			$this->sql_where .= " AND (".$this->model->db->query($q, $args).")";
		} else {
			$this->sql_where = "WHERE (".$this->model->db->query($q, $args).")";
		}
		return $this;
	}

	/**
	 * A convenience method for RecordSelector::where().  Adds a WHERE clause
	 * that states "$col='$val'".
	 *
	 * @param string $col The column to match.
	 * @param string $val The value of the column.
	 */
	function eq($col, $val)
	{
		return $this->where("$col='%s'", $val);
	}

	/**
	 * A convenience method for RecordSelector::where().  Look for record(s)
	 * that match all column values in the array.
	 *
	 * @param array $record Associative array of column->value mappings that
	 *                      will be used as search criteria.
	 */
	function match($record)
	{
		$t = $this;
		foreach($record as $k=>$v) {
			$t = $this->eq($k, $v);
		}
		return $t;
	}

	/**
	 * Add an additional ORDER BY restriction to the result set
	 *
	 * @param string $orderby "Order by" sub-clause
	 * @return object          RecordSelector object
	 */
	function order($orderby)
	{
		$this->_reset_state();
		if($this->sql_order) {
			$this->sql_order .= ",$orderby";
		} else {
			$this->sql_order = "ORDER BY $orderby";
		}
		return $this;
	}

	/**
	 * Add an additional LIMIT restriction to the result set
	 *
	 * @param int $num    Number of records to limit the result set to
	 * @param int $offset Offset to start from (default 0, the beginning)
	 * @return object     RecordSelector object
	 */
	function limit($num, $offset=0)
	{
		$this->_reset_state();
		$this->sql_limit = "LIMIT $num OFFSET $offset";
		return $this;
	}

	/**
	 * Return one or more bare records.  Each record will be an associative array.
	 * If multiple records are round, it will return an array of associative
	 * arrays.
	 *
	 * @return array
	 */
	function fetch()
	{
		$q = "SELECT * FROM {$this->model->table}".$this->_build_clause();
		$ret = $this->model->db->get_all($q);
		switch(count($ret)) {
			case 0:  return false;
			case 1:  return $ret[0];
			default: return $ret;
		}
	}

	/**
	 * Return the number of records that match the criteria.
	 *
	 * @return int
	 */
	function count()
	{
		$q = "SELECT COUNT (*) FROM {$this->model->table}".$this->_build_clause();
		return $this->model->db->get_value($q);
	}

	/**
	 * Return one or more full records.  Each record will be an associative array.
	 * If multiple records are round, it will return an array of associative
	 * arrays.
	 *
	 * @param ret2d boolean Always return an array of associative arrays, even
	 *                      if only one record was found.  If zero recors are
	 *                      found, an empty array is returned.
	 *
	 * @return array
	 */
	function load($ret2d=false)
	{
		// let the model do the load
		$ids = $this->_get_ids();
		$ret = array();
		foreach($ids as $id) $ret[] = $this->model->load($id);
		switch(count($ret)) {
			case 0:  return $ret2d ? array() : false;
			case 1:  return $ret2d ? $ret : $ret[0];
			default: return $ret;
		}
	}

	/**
	 * Return one or more full records.  The difference with load() is that
	 * this method always returns an array of associative arrays, even if only
	 * one matching record is found.  Useful for callers that always expect
	 * a 2D array.
	 *
	 * @return array
	 */
	function load_all()
	{
		return $this->load(true);
	}

	/**
	 * Return one record, no matter how many are in the result set.  This
	 * method uses an internal pointer to keep track of which record will
	 * be returned next, so you can call this method in an iterative fashion
	 * to process large results sets.
	 *
	 * @return mixed Return a data record, or false if none are left.
	 */
	function load_one()
	{
		$ids = $this->_get_ids();
		if(!isset($ids[$this->it_ptr])) return false;
		return $this->model->load($ids[$this->it_ptr++]);
	}

	/**
	 * Alias for load_one()
	 */
	function one()
	{
		return $this->load_one();
	}

	/**
	 * For each record in the set, run a callback function.
	 * PHP 5.3.x or higher required.
	 */
	function each($fn)
	{
		while($row = $this->load_one()) {
			$fn($row);
		}
	}

	/**
	 * Delete one or more records.
	 */
	function delete()
	{
		// let the model remove the rows
		$ids = $this->_get_ids();
		foreach($ids as $id) $this->model->delete($id);
	}

	/**
	 * Set the value of one or more columns.
	 *
	 * @param mixed $key Column name.  To set multiple columns, pass an
	 *                   associative array of all column=>value mappings.
	 * @param mixed $val Column value.  Only used if setting a single column
	 *                   value.
	 */
	function set($key, $val=null)
	{
		if(is_array($key)) {
			$kp = $args = array();
			foreach($key as $k=>$v) {
				$kp[]   = "\"$k\"='%s'";
				$args[] = $v;
			}
			$q = $this->model->db->query("UPDATE {$this->model->table} SET ".implode(',',$kp).$this->_build_clause(), $args);
		} else {
			$q = $this->model->db->query("UPDATE {$this->model->table} SET \"$key\"='%s'".$this->_build_clause(), array($val));
		}
		// invalidate cache entries
		if($this->model->enable_cache && $this->model->cache) {
			$ids = $this->_get_ids();
			foreach($ids as $id) $this->model->invalidate($id);
		}
		return $this->model->db->execute($q);
	}

	/**
	 * Get one or more column values from the result set.  The return
	 * value varies:
	 *
	 * 1. If a single column is requested, then a scalar value will be returned.
	 * 2. If multiple columns are requested, then an associative array will be
	 *    returned.
	 *
	 * If more than one row matches, than an array of #1 or #2 will be returnd.
	 *
	 * @param string Column name, ...
	 * @return mixed
	 */
	function get()
	{
		$cols = array();
		foreach(func_get_args() as $a) $cols[] = "\"$a\"";
		$q = "SELECT ".implode(',',$cols)." FROM {$this->model->table}".$this->_build_clause();
		$rows = $this->model->db->get_all($q);

		if(empty($rows)) return false;

		$cols = func_get_args();
		$ret  = array();
		if(count($cols) > 1) {
			foreach($rows as $row) {
				$rec = array();
				foreach($cols as $col) $rec[$col] = $row[$col];
				$ret[] = $rec;
			}
		} else {
			foreach($rows as $row) $ret[] = $row[$cols[0]];
		}

		if(count($rows) == 1) {
			return $ret[0];
		}
		return $ret;
	}

	/**
	 * Return a name-value-pair for the requested column(s).
	 *
	 * @param string $key Column to use as the array key
	 * @param string $val Column to use as the array value.  If multiple
	 *                    values are passed, then the array value will be an
	 *                    associative array itself.
	 * @return array
	 */
	function pair()
	{
		if(func_num_args() < 2) return false;
		$args = func_get_args();
		$rows = call_user_func_array(array($this,'get'), $args);
		if(!isset($rows[0])) $rows = array($rows);

		$ret = array();
		foreach($rows as $row) {
			if(count($row) > 2) {
				$ret[$row[$args[0]]] = array();
				for($i = 1; $i < count($args); $i++) $ret[$row[$args[0]]][] = $row[$args[$i]];
			} else {
				$ret[$row[$args[0]]] = $row[$args[1]];
			}
		}
		return $ret;
	}

	function _get_ids()
	{
		if(!empty($this->id_cache)) {
			return $this->id_cache;
		}
	 	$ids = $this->get('id');
		assert_type($ids, 'array');
		$this->id_cache = $ids;
		return $ids;
	}

	function _reset_state()
	{
		$this->it_ptr   = 0;
		$this->id_cache = array();
	}

	function _build_clause()
	{
		$sql = '';
		if($this->sql_where) $sql .= ' '.$this->sql_where;
		if($this->sql_order) $sql .= ' '.$this->sql_order;
		if($this->sql_limit) $sql .= ' '.$this->sql_limit;
		return $sql;
	}
}

?>
