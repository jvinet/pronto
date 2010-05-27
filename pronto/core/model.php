<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Base class for all data models.  Many of the methods here
 *              will be overridden by subclasses.
 *
 * NOTE: This class is now deprecated in favor of the newer RecordModel
 *       and RecordSelector classes.  Please use those instead.
 *
 **/

class Model_Base
{
	var $db      = null;
	var $web     = null;
	var $cache   = null;
	var $depends = null;
	var $plugins = null;

	/**
	 * This can be set by the page controller acting on the model.
	 * It defines the context with which a record is being modified.
	 * For example, a Page_CRUD controller may set the model's context
	 * to 'ADMIN', indicating that an authorized administrator is
	 * modifying a record from a backend admin console, and so regular
	 * validation should be bypassed.
	 *
	 * The model can then act on this setting in its overridden
	 * methods (such as validate, insert, update, etc.)
	 *
	 * This setting is optional.
	 */
	var $context = null;

	/*
	 * Variables below are to be overridden by subclasses
	 */

	/**
	 * Name of database table this model will use
	 */
	var $table        = null;
	/**
	 * Name of the PK column in this table (one column only)
	 */
	var $pk           = 'id';
	/**
	 * Default column to sort by
	 */
	var $default_sort = 'id';
	/**
	 * Default number of items to show per page
	 */
	var $per_page     = 50;
	/**
	 * Enable caching for this entity.  Only enable this if you plan to
	 * be diligent about _only_ inserting/updating/fetching/deleting this
	 * entity _through_ the model interface.  If you interact with the data
	 * yourself (eg, through $this->db in a controller) then you will
	 * undermine the caching layer.
	 */
	var $enable_cache = false;

	/**
	 * An array of files associated with this model, used by
	 * Page_CRUD to handle file uploads.  The 'type' element determines
	 * the acceptable MIME type(s).  Use an array for multiple types, or
	 * "image" to accept normal image types.  Omit it to accept any file.
	 *
	 * If type==image, then images will be converted to JPEG for consistency.
	 * If type==image, then use 'max_width' and 'max_height' to optionally
	 * resize images, maintaining aspect ratio.
	 *
	 * The filename parameter can include any values from the record
	 * returned from the model's get() method.  Each data key should be
	 * surrounded in angle brackets (eg, "<name>").
	 *
	 * Array keys in $files must match those of the create/edit form defined
	 * in the template.
	 *
	 * Example:
	 @code
	 var $files = array(
	   'image' => array(
	     'type'       => 'image',
	     'max_width'  => '640',      // used for type==image only
	     'max_height' => '800',      // used for type==image only
	     'webroot'    => DIR_WS_DATA_IMG,
	     'fileroot'   => DIR_FS_DATA_IMG,
	     'filename'   => "<id>.jpg"
	   )
	 );
	 @endcode
	 */
	var $files = null;

	/**
	 * Constructor for all models
	 *
	 */
	function Model_Base()
	{
		$this->db    =& Registry::get('pronto:db:main');
		$this->cache =& Registry::get('pronto:cache');
		$this->web   =& Registry::get('pronto:web');
		$this->validator =& Registry::get('pronto:validator');

		// this isn't a very smart inflector -- best to explicitly set the
		// table name in the model itself
		if(is_null($this->table) || empty($this->table)) {
			$this->table = strtolower(substr(get_class($this),1)).'s';
		}

		// load plugins
		$this->plugins =& Registry::get('pronto:plugins');

		if(method_exists($this, '__init__')) {
			$this->__init__();
		}
	}

	/**
	 * Load another model as a dependency of this one.
	 *
	 * @param string $model_name
	 */
	function depend($model)
	{
		foreach(func_get_args() as $name) {
			if(isset($this->depends->$name)) continue;
			$this->depends->$name =& Factory::model($name);
		}
	}

	/**
	 * Import a plugin (aka "page plugin").
	 * @param string $name Plugin name
	 */
	function &import_plugin($name)
	{
		foreach(func_get_args() as $name) {
			$class =& Factory::plugin($name, 'page');
			if($class === false) {
				trigger_error("Plugin $name does not exist");
				die;
			}
		}
		// reload $this->plugins
		$this->plugins =& Registry::get('pronto:plugins');
		if($class) return $class;
	}

	/**
	 * Validate data for an INSERT.  To be overridden by subclasses.
	 *
	 * @param array $data
	 * @return array Associative array of errors
	 */
	function validate_for_insert($data)
	{
		return false;
	}

	/**
	 * Validate data for an UPDATE.  To be overridden by subclasses.
	 *
	 * @param array $data
	 * @return array Associative array of errors
	 */
	function validate_for_update($data)
	{
		return false;
	}

	/**
	 * Perform the correct validation routine depending on insert/update mode.
	 *
	 * @param array $data
	 * @param bool $is_update Whether this data manipulation is an UPDATE or not
	 * @return array Associative array of errors
	 */
	function validate($data, $is_update=false)
	{
		if($is_update) {
			return $this->validate_for_update($data);
		}
		return $this->validate_for_insert($data);
	}

	/**
	 * Use the HTML checker to remove any possible XSS attacks (eg, <script> tags)
	 *
	 * @param array $data
	 * @return array
	 */
	function purify($data)
	{
		require_once(DIR_FS_PRONTO.DS.'extlib'.DS.'safehtml'.DS.'safehtml.php');
		foreach($data as $k=>$v) {
			if(is_array($v)) {
				// PHP4 doesn't like self::purify()
				$data[$k] = Model::purify($v);
			} else if(class_exists('safehtml')) {
				$purifier = new safehtml();
				$data[$k] = $purifier->parse($v);
			}
		}
		return $data;
	}

	/**
	 * Used to do any necessary sanitization on the data before putting it
	 * in the DB.  addslashes() stuff is not necessary.
	 *
	 * @param array $data
	 * @return array
	 */
	function sanitize($data)
	{
		$data = $this->purify($data);
		return $data;
	}

	/**
	 * Return a new/fresh entity.  This method is responsible for returning
	 * a brand new, un-edited record that will be used to prepopulate form
	 * fields in a "Create" form for this data entity.
	 *
	 * @return array
	 */
	function create()
	{
		return array();
	}

	/**
	 * Return a single record by PK.  Normally, you won't want to override this method.
	 * Instead, override the get_record() method, which does the actual fetching/assembling
	 * of a data record for use by other objects.
	 *
	 * @param int $id
	 * @return array
	 */
	function get($id)
	{
		if($this->enable_cache && $this->cache) {
			$key = 'model:'.get_class($this).":{$this->pk}:$id";
			$item =& $this->cache->get($key);
			if(!$item) {
				$item = $this->get_record($id);
				$this->cache->set($key, $item);
			}
			return $item;
		}
		return $this->get_record($id);
	}

	/**
	 * Remove an entry from the cache
	 *
	 * @param mixed $id PK of the record to be invalidated from the cache.
	 *                  Use an array if invalidating multiple records.
	 */
	function invalidate($id)
	{
		if(!is_array($id)) $id = array($id);
		foreach($id as $i) {
			if($this->enable_cache && $this->cache) {
				$key = 'model:'.get_class($this).":{$this->pk}:$i";
				$this->cache->delete($key);
			}
		}
	}

	/**
	 * Remove all cache entries for this data entity.
	 */
	function invalidate_all()
	{
		if($this->cache) {
			$re = '^model:'.get_class($this).':.*$';
			$this->delete_by_regex("/$re/");
		}
	}

	/**
	 * Return a full record for this entity, looked up by PK.  Normally
	 * you will want to override this method to do any additional work on
	 * the record before returning it to the caller.
	 *
	 * @param int $id
	 * @return array
	 */
	function get_record($id)
	{
		return $this->get_bare($id);
	}

	/**
	 * Return a single bare record by PK.  Returns a single row from the
	 * table, no other data manipulation is performed.  Normally the
	 * Model::get_record() method is overridden to do additional data work, so
	 * this method serves to retain a plain record-getter.
	 *
	 * @param int $id
	 * @return array
	 */
	function get_bare($id)
	{
		return $this->db->get_item("SELECT * FROM {$this->table} WHERE \"{$this->pk}\"=%i LIMIT 1", array($id));
	}

	/**
	 * Return multiple records by PK, optionally  ignoring empty/false records.
	 *
	 * @param array $ids
	 * @param boolean $ignore_empty Ignore empty records.
	 * @return array
	 */
	function get_multi($ids, $ignore_empty=true)
	{
		$data = array();
		foreach($ids as $id) {
			$rec = $this->get($id);
			if($rec || !$ignore_empty) $data[] = $rec;
		}
		return $data;
	}

	/**
	 * Return a single column from a single entity by PK
	 *
	 * @param int $id
	 * @param string $column
	 * @return array
	 */
	function get_value($id, $column)
	{
		return $this->db->get_value("SELECT \"$column\" FROM {$this->table} WHERE \"{$this->pk}\"=%i LIMIT 1", array($id));
	}

	/**
	 * Fetch a row by a specified column.  If $column and $value are
	 * arrays, then "AND" all the values in the WHERE clause.
	 *
	 * @param mixed $column
	 * @param mixed $value
	 * @return array
	 */
	function get_by($column, $value)
	{
		if(!is_array($column)) $column = array($column);
		if(!is_array($value))  $value  = array($value);

		$where = array();
		foreach($column as $c) $where[] = "\"{$c}\"='%s'";
		$where = implode(' AND ', $where);

		$id = $this->db->get_value("SELECT \"{$this->pk}\" FROM {$this->table} WHERE $where LIMIT 1", $value);
		return $this->get($id);
	}

	/**
	 * Fetch all rows by a specified column.  If $column and $value are
	 * arrays, then "AND" all the values in the WHERE clause.
	 *
	 * @param mixed $column
	 * @param mixed $value
	 * @return array
	 */
	function get_all_by($column, $value)
	{
		if(!is_array($column)) $column = array($column);
		if(!is_array($value))  $value  = array($value);

		$where = array();
		foreach($column as $c) $where[] = "\"{$c}\"='%s'";
		$where = implode(' AND ', $where);

		$ids = $this->db->get_values("SELECT \"{$this->pk}\" FROM {$this->table} WHERE $where ORDER BY {$this->default_sort}", $value);
		return $this->get_multi($ids);
	}

	/**
	 * Fetch all records.
	 *
	 * @return array
	 */
	function get_all()
	{
		$ids = $this->db->get_values("SELECT \"{$this->pk}\" FROM {$this->table} ORDER BY {$this->default_sort}");
		return $this->get_multi($ids);
	}

	/**
	 * Return an entity or throw a 404 page if not found
	 *
	 * @param int $id
	 * @return array
	 */
	function get_or_404($id)
	{
		$data = $this->get($id);
		if($data === false) {
			$this->web->notfound();
		}
		return $data;
	}

	/**
	 * Delete a single entity by PK
	 *
	 * @param int $id
	 */
	function delete($id)
	{
		$this->invalidate($id);
		return $this->db->execute("DELETE FROM {$this->table} WHERE \"{$this->pk}\"=%i", array($id));
	}

	/**
	 * Insert a new entity into the table
	 *
	 * @param array $data
	 * @return int The insert ID of the newly-inserted row
	 */
	function insert($data)
	{
		$data = $this->sanitize($data);
		return $this->db->insert_row($this->table, $data);
	}

	/**
	 * Update an entity
	 *
	 * @param array $data
	 */
	function update($data)
	{
		$this->invalidate($data['id']);
		$data = $this->sanitize($data);
		return $this->db->update_row($this->table, $data, $this->pk."='%s'", array($data[$this->pk]));
	}

	/**
	 * Return some data used for generating smart SQL for lists/grids.
	 *
	 * @return array
	 */
	function list_params()
	{
		return array(
			'from'       => $this->table,
			'exprs'      => array(),
			'gexprs'     => array(),
			'select'     => '*',
			'where'      => '',
			'group_by'   => '',
			'having'     => '',
			'order'      => $this->default_sort,
			'limit'      => $this->per_page,
		);
	}

	/**
	 * TODO: PHP5 only!
	 *
	 * Call like so: $this->set_mycolname($id, $value)
	 *          and: $this->get_mycolname($id)
	 */
	function __call($name, $args)
	{
		switch(true) {
			case substr($name, 0, 4) == 'get_':
				$field = substr($name, 4);
				return $this->db->get_value("SELECT \"$field\" FROM {$this->table} WHERE \"{$this->pk}\"='%s'", array($args[0]));
			case substr($name, 0, 4) == 'set_':
				$field = substr($name, 4);
				$this->db->execute("UPDATE {$this->table} SET \"$field\"='%s' WHERE \"{$this->pk}\"='%s'", array($args[1],$args[0]));
				$this->invalidate($args[0]);
				return true;
		}
		trigger_error("Method does not exist: $name");
	}

	/**
	 * BACKWARDS COMPATIBILITY
	 *
	 * This makes the basic model functions compatible with the newer RecordModel
	 * class.
	 */
	function save($data)
	{
		if($data['id']) {
			return $this->update($data);
		} else {
			return $this->insert($data);
		}
	}

	function load($id)
	{
		return $this->get($id);
	}

	function create_record()
	{
		return $this->create();
	}

	function enum_schema()
	{
		return $this->list_params();
	}
}

?>
