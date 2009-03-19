<?php

class m_UENTITY_ extends Model
{
	var $table        = '_DB_TABLE_';
	var $default_sort = '_DEFAULT_SORT_ ASC';
	var $per_page     = 50;
	var $enable_cache = false;

	function validate_for_insert($data)
	{
		$errors = array();
		$this->validator->required($errors, array(), $data);
		return $errors;
	}

	function validate_for_update($data)
	{
		$errors = array();
		$this->validator->required($errors, array(), $data);
		return $errors;
	}

	function create()
	{
		return parent::create();
	}

	function insert($data)
	{
		return parent::insert($data);
	}

	function update($data)
	{
		return parent::update($data);
	}

	function delete($id)
	{
		parent::delete($id);
	}

	function list_params()
	{
		return array(
			'from'       => $this->table,
			'exprs'      => array(),
			'select'     => '*',
			'where'      => array(),
			'where_args' => array(),
			'order'      => $this->default_sort,
			'limit'      => $this->per_page,
			'group_by'   => ''
		);
	}

	function get_record($id)
	{
		return parent::get_record($id);
	}
}

?>
