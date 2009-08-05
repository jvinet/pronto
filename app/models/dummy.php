<?php
/**
 * This is the basic skeleton of a model class.  It is here
 * for reference and is not part of the application.
 *
 * You can delete it if you want.
 */

class mDummy extends RecordModel
{
	var $table        = 'dummy';
	var $enable_cache = false;

	function validate($data)
	{
		$errors = array();
		$this->validator->required($errors, array('name','city'), $data);
		return $errors;
	}

	function create_record()
	{
		return parent::create_record();
	}

	function save_record($data)
	{
		return parent::save_record($data);
	}

	function load_record($id)
	{
		return parent::load_record($id);
	}

	function delete_record($id)
	{
		parent::delete_record($id);
	}

	function enum_schema()
	{
		return array(
			'from'       => $this->table,
			'exprs'      => array(),
			'gexprs'     => array(),
			'select'     => '*',
			'where'      => '',
			'group_by'   => '',
			'having'     => '',
			'order'      => 'name ASC',
			'limit'      => 50
		);
	}
}

?>
