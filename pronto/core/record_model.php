<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Base class for all data models.  The bottom seven methods
 *              in this class can (and will) typically be overridden by
 *              model subclasses.
 *
 **/

class RecordModel_Base
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
	function RecordModel_Base()
	{
		$this->db    =& Registry::get('pronto:db');
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
				$key = 'model_'.get_class($this)."_{$this->pk}_$i";
				$this->cache->delete($key);
			}
		}
	}

	/**
	 * Build a result set matching the sub-query provided.  This will
	 * return an object through which some basic data manipulation
	 * primitives can be used (get, set, load, delete)
	 *
	 * @param string "Where" sub-query (leave blank to select all)
	 * @param mixed  Additional query argument (optional)
	 * @param mixed  Additional query argument (optional) ...
	 * @return object RecordSelector object.
	 */
	function find()
	{
		$args = func_get_args();
		$q    = array_shift($args);
		return new RecordSelector($q, $args, $this);
	}

	/*********************************************************************
	 * PRIMITIVES
	 *********************************************************************/

	/**
	 * Load a bare record.  This is just the row from the model's table,
	 * nothing else.  The cache is never used for a raw fetch.
	 *
	 * @param int $id
	 * @return mixed
	 */
	function fetch($id)
	{
		$q = $this->db->query("SELECT * FROM {$this->table} WHERE \"{$this->pk}\"='%s' LIMIT 1", array($id));
		return $this->db->get_item($q);
	}

	/**
	 * Store a bare record in the DB.
	 * 
	 * @param array $data
	 */
	function store($data)
	{
		$data = $this->sanitize($data);
		if(isset($data[$this->pk])) {
			$this->db->update_row($this->table, $data, $this->pk."='%s'", array($data[$this->pk]));
			return $data[$this->pk];
		} else {
			return $this->db->insert_row($this->table, $data);
		}
	}

	/**
	 * Erase a bare record from the DB.
	 *
	 * @param int $id
	 */
	function erase($id)
	{
		$q = $this->db->query("DELETE FROM {$this->table} WHERE \"{$this->pk}\"='%s' LIMIT 1", array($id));
		return $this->db->execute($q);
	}

	/*********************************************************************
	 * HIGHER-ORDER, CACHE-AWARE
	 *********************************************************************/

	/**
	 * Load a full record.  This includes any additional data processing, and/or
	 * additional records from other models.  If enabled, the cache will be
	 * checked first.
	 *
	 * @param int $id
	 * @return mixed
	 */
	function load($id)
	{
		if($this->enable_cache && $this->cache) {
			$key = 'model_'.get_class($this)."_{$this->pk}_$id";
			$rec =& $this->cache->get($key);
			if(!$rec) {
				$rec = $this->load_record($id);
				$this->cache->set($key, $rec);
			}
			return $rec;
		}
		return $this->load_record($id);
	}

	/**
	 * Save a full record to the DB.
	 *
	 * @param array $data
	 */
	function save($data)
	{
		if($data[$this->pk]) $this->invalidate($data[$this->pk]);
		return $this->save_record($data);
	}

	/**
	 * Delete a full record from the DB.
	 *
	 * @param int $id
	 */
	function delete($id)
	{
		$this->invalidate($id);
		return $this->delete_record($id);
	}

	/*********************************************************************
	 * HIGHER-ORDER, CACHE-TRANSPARENT
	 *   (OVERRIDE THESE)
	 *********************************************************************/

	/**
	 * Return a new/fresh entity.  This method is responsible for returning
	 * a brand new, un-edited record that will be used to prepopulate form
	 * fields in a "Create" form for this data entity.
	 *
	 * This method can be overridden.
	 *
	 * @return array
	 */
	function create_record()
	{
		return array();
	}

	/**
	 * Load a full record.  Override this method to control what
	 * gets loaded into the final record.
	 */
	function load_record($id)
	{
		return $this->fetch($id);
	}

	/**
	 * Save a full record.  Override this method to control what
	 * gets saved into the final record.
	 */
	function save_record($data)
	{
		return $this->store($data);
	}

	/**
	 * Delete a full record.  Override this method to control what
	 * else needs to happen when a record is deleted.
	 */
	function delete_record($id)
	{
		return $this->erase($id);
	}

	/**
	 * Validate data before a save.  Override this method.
	 *
	 * @param array $data
	 * @return array Associative array of errors
	 */
	function validate($data)
	{
		return array();
	}

	/**
	 * Used to do any necessary sanitization on the data before putting it
	 * in the DB.  Escaping is not necessary.  You can override this method
	 * to control how input data should be sanitized.  By default it is
	 * scrubbed by the RecordModel::purify() method.
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
	 * Define the enumeration schema for this model.  This describes
	 * how Pronto can enumerate and/or search for records.
	 */
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
			'order'      => "{$this->pk} ASC",
			'limit'      => 50
		);
	}
}

?>
