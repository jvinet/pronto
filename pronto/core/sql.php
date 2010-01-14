<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet at zeroflux dot org>
 *
 * Description: SQL Generator Methods.  Intended to be used by page controllers only.
 *
 **/


class SQL_Generator
{
	var $db;
	var $page;

	/**
	 * Constructor
	 *
	 * @param object $page The controller object that owns this instance of SQL_Generator
	 */
	function SQL_Generator(&$page)
	{
		$this->db   =& Registry::get('pronto:db:main');
		$this->page =& $page;
	}

	/**
	 * Generate SQL to enumerate the records of an entity, returning the resulting
	 * rows and related pagination data.
	 *
	 * @param array $params Parameters that will be used to generate the resulting SQL.
	 *                      These parameters are typically provided by the entity's model,
	 *                      usually in a method called list_params().
	 * @param boolean $set_vars Automatically set template variables: (data, totalrows, curpage, perpage)
	 * @return array An array of: (data, total rows, current page, records per page)
	 */
	function enumerate($params, $set_vars=false)
	{
		// move the list parameters into the local namespace
		//   (from,exprs,gexprs,select,where,group_by,having,order,limit)
		extract($params, EXTR_OVERWRITE);

		assert_type($exprs,  'array');
		assert_type($gexprs, 'array');
		foreach($exprs as $k=>$v)  $select .= ",$v \"$k\"";
		foreach($gexprs as $k=>$v) $select .= ",$v \"$k\"";
		// if $params[select] was empty, then we have a leading comma...
		if(substr($select, 0, 1) == ',') $select = substr($select, 1);

		// Build WHERE/HAVING clauses from list params and
		// criteria sent from the browser
		list($w_sql,$w_args,$h_sql,$h_args) = $this->filter($exprs, $gexprs);

		// WHERE
		if(is_array($where)) {
			foreach($where as $v) $w_sql .= " AND ($v)";
		} else if(!empty($where)) {
			$w_sql .= " AND ($where)";
		}
		// backwards compatibility
		if(!empty($where_args)) $w_args = array_merge($w_args, $where_args);

		// HAVING
		if(is_array($having)) {
			foreach($having as $v) $h_sql .= " AND ($v)";
		} else if(!empty($having)) {
			$h_sql .= " AND ($having)";
		}

		// Merge all SQL args if necessary ($h_args could be empty)
		$args = empty($h_args) ? $w_args : array_merge($w_args, $h_args);

		// ORDER/LIMIT
		$sort_sql = $this->sort($order, $exprs);
		$page_sql = $this->paginate($limit);

		// Get data rows
		if($this->db->type == 'mysql') $select = "SQL_CALC_FOUND_ROWS $select";
		$sql  = $this->db->build_sql($select, $from, $w_sql, $group_by, $h_sql, $sort_sql, $page_sql);
		$data = $this->db->get_all($sql, $args);

		// Count all matching rows
		if($this->db->type == 'mysql') {
			$ttlrows = $this->db->get_value("SELECT FOUND_ROWS()");
		} else {
			$ttlrows = $this->db->get_value($this->db->build_sql("COUNT(*)", $from, $w_sql, $group_by, $h_sql), $args);
		}

		if($set_vars) {
			$this->page->template->set('data',      $data);
			$this->page->template->set('totalrows', $ttlrows);
			$this->page->template->set('curpage',   $this->page->param('p_p', 1));
			$this->page->template->set('perpage',   $this->page->param('p_pp', $limit));
		}

		// data, total rows, current page, per page
		return array($data, $ttlrows, $this->page->param('p_p', 1), $this->page->param('p_pp', $limit));
	}

	/**
	 * Generate SQL to filter a result set based on filter variables in _REQUEST.
	 * This can generate SQL that can be used in a WHERE or HAVING clause.
	 *
	 * @param array $exprs An array of expressions that do not correspond to real
	 *                     table columns, but to more complex statements that
	 *                     involve functions (eg, LENGTH(colname) or COUNT(c.id)).
	 *                     Each array key should be the "column" name used in the
	 *                     filter variable in _REQUEST.
	 * @return array The resulting SQL fragment and the corresponding SQL arguments
	 *               eg, array(where_sql, where_args, having_sql, having_args)
	 */
	function filter($exprs=array(), $gexprs=array())
	{
		$where  = array('sql'=>array(), 'args'=>array());
		$having = array('sql'=>array(), 'args'=>array());
		foreach($_REQUEST as $k=>$v) {
			$args = array();
			$sql  = array();

			if($v === '') continue;
			if(!preg_match('|^f_[dts]_|', $k)) continue;
			$t = substr($k, 2, 1);
			$k = substr($k, 4);
			// only alphanumerics allowed
			if(!preg_match('|^[A-z0-9_-]+$|', $k)) continue;
			switch($t) {
				case 'd': $coltype = 'date'; break;
				case 's': $coltype = 'select'; break;
				case 't':
				default: $coltype = 'text';
			}

			// look for an expression passed to the function first -- this is
			// used for trickier SQL expressions, eg, functions
			if(isset($exprs[$k])) {
				$s = $exprs[$k];
				$t = 'where';
			} else if(isset($gexprs[$k])) {
				$s = $gexprs[$k];
				$t = 'having';
			} else {
				// use the literal column name
				$s = "\"$k\"";
				$t = 'where';
			}

			$range = explode('<>', $v);
			if(count($range) == 2) {
				// double-bounded range
				$sql[]  = "($s>='%s' AND $s<='%s')";
				$args[] = $range[0];
				$args[] = $range[1];
			} else if(strlen($v) == 1) {
				// no range (explicit)
				// FYI: this check is needed, as the "else" block assumes
				// a string length of at least 2
				$sql[]  = is_numeric($v) ? "$s='%s'" : "$s LIKE '%%s%'";
				$args[] = $v;
			} else if(substr($v, 0, 2) == '!=') {
				// the "not-equals" operator
				$sql[]  = "$s!='%s'";
				$args[] = substr($v, 2);
			} else {
				// single-bounded range
				$chop = 0;
				switch(substr($v, 0, 1)) {
					case '=':
						$s .= '='; $chop = 1;
						break;
					case '>':
						switch($v{1}) {
							case '=': $s .= '>='; $chop = 2; break;
							default:  $s .= '>';  $chop = 1; break;
						}
						break;
					case '<':
						switch($v{1}) {
							case '=': $s .= '<='; $chop = 2; break;
							default:  $s .= '<';  $chop = 1; break;
						}
						break;
					default:
						$s .= ' LIKE ';
				}
				$v = substr($v, $chop);
				if($chop || is_numeric($v)) {
					$s .= "'%s'";
				} else {
					$s .= "'%%s%'";
				}
				$sql[]  = $s;
				$args[] = $v;

				// special handling for various filter types
				if($coltype == 'date' && $chop) {
					// don't include the default '0000-00-00' fields in ranged selections
					$s  = isset($exprs[$k]) ? $exprs[$k] : "\"$k\"";
					$s .= "!='0000-00-00'";
					$sql[] = $s;
				}
			}
			switch($t) {
				case 'where':
					$where['sql']  = array_merge($where['sql'], $sql);
					$where['args'] = array_merge($where['args'], $args);
					break;
				case 'having':
					$having['sql']  = array_merge($having['sql'], $sql);
					$having['args'] = array_merge($having['args'], $args);
			}
		}

		// ensure the WHERE clause always has something in it
		$where['sql'][] = '1=1';

		$final = array(implode(' AND ', $where['sql']), $where['args']);
		if(!empty($having['sql'])) {
			$final[] = implode(' AND ', $having['sql']);
			$final[] = $having['args'];
		}

		return $final;
	}

	/**
	 * Generate SQL to sort a result set based on sort variables in _REQUEST
	 *
	 * @param string $default Default column(s) to sort by
	 * @param array $exprs An array of expressions that do not correspond to real
	 *                     table columns, but to more complex statements that
	 *                     involve functions (eg, LENGTH(colname) or COUNT(c.id)).
	 *                     Each array key should be the "column" name used in the
	 *                     filter variable in _REQUEST.
	 * @return string The resulting SQL fragment
	 */
	function sort($default, $exprs=array())
	{
		$cols = $sortsql = array();
		$field = $this->page->param('s_f', '');
		if($field) {
			$dir  = $this->page->param('s_d', 'ASC');
			$cols = array(array('field'=>$field, 'dir'=>$dir));
		} else {
			// use the default
			foreach(explode(',', $default) as $pair) {
				$pair = trim($pair);
				if(empty($pair)) continue;
				$p = explode(' ', $pair);
				$cols[] = array('field'=>$p[0], 'dir'=>isset($p[1]) ? $p[1] : 'ASC');
			}
		}

		foreach($cols as $c) {
			// look for an expression passed to the function first -- this is
			// used for trickier SQL expressions, like functions
			if(isset($exprs[$c['field']])) {
				$s = $exprs[$c['field']];
			} else {
				// use the literal column name
				$p = explode('.', $c['field']);
				if(isset($p[1])) {
					$s = $p[0].'."'.$p[1].'"';
				} else {
					$s = "\"{$c['field']}\"";
				}
			}
			$sortsql[] = "$s {$c['dir']}";
		}

		return join(',', $sortsql);
	}

	/**
	 * Generate SQL to paginate a result set.  Also sets the "perpage" and
	 * "curpage" variables in the template.
	 *
	 * @param int $perpage Number of records to show per page
	 * @return string The resulting SQL fragment
	 */
	function paginate($perpage=50)
	{
		if($perpage == 0) return ''; // zero means unlimited

		$page    = $this->page->param('p_p', 1);
		$perpage = $this->page->param('p_pp', $perpage);
		$offset  = max(0,($page-1) * $perpage);
 
		return "$perpage OFFSET $offset";
	}

}

?>
