<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Access control and User identification functions.
 *
 **/

class Access
{
	var $logged_in;     // is user logged in?
	var $access_id;     // unique identifier of logged-in user (0 for guest)
	var $access_keys;   // array of access keys this user has

	function Access()
	{
		$this->clear_authentication(false);
		$this->identify();
	}

	function identify()
	{
		if(isset($_SESSION['_ACCESS'])) {
			$this->logged_in   = !!($_SESSION['_ACCESS']['id'] > 0);
			$this->access_id   = $_SESSION['_ACCESS']['id'];
			$this->access_keys = $_SESSION['_ACCESS']['keys'];
		}
	}

	function clear_authentication($clear_sess=true)
	{
		$this->clear_id($clear_sess);
		if($clear_sess) unset($_SESSION['_ACCESS']);
	}

	function set_id($id)
	{
		$this->access_id = $id;
		$this->logged_in = !!$id;

		$_SESSION['_ACCESS']['id'] = $this->access_id;
	}

	function get_id()
	{
		return $this->access_id;
	}

	function clear_id($clear_sess=false)
	{
		$this->clear_keys($clear_sess);
		$this->logged_in = false;
		$this->access_id = 0;
		if($clear_sess) $_SESSION['_ACCESS']['id'] = $this->access_id;
	}

	function set_key($key)
	{
		if(empty($key) || !isset($GLOBALS['ACCESS_KEYS'][$key])) return;
		if(ACCESS_MODEL === "roles") {
			foreach($GLOBALS['ACCESS_KEYS'][$key] as $k) {
				$this->access_keys[] = $k;
			}
		} else if(ACCESS_MODEL === "discrete") {
			$this->access_keys[] = $key;
		}

		$_SESSION['_ACCESS']['keys'] = $this->access_keys;
	}

	function set_keys($keys)
	{
		if(!is_array($keys)) $keys = split(',', $keys);
		foreach($keys as $key) $this->set_key($key);
	}

	/**
	 * Return an array of all valid access keys.  If ACCESS_MODEL is in "discrete" mode,
	 * then a pseudo-tree format will be used:  group names are included, and keys are
	 * prefixed with "-- " to denote them as such.
	 *
	 * @param $tree boolean Only relevant for the "discrete" model.  If true, then a
	 *                      pseudo-tree format will be used:  group names are included,
	 *                      and keys are prefixed with "-- " to denote them as such.  This
	 *                      is useful for populating select/multiselect widgets to see
	 *                      which elements are groups/parents and which are keys/children.
	 * @return array
	 */
	function get_keys($tree=false)
	{
		if(ACCESS_MODEL == "roles") {
			return array_keys($GLOBALS['ACCESS_KEYS']);
		} else if(ACCESS_MODEL == "discrete") {
			$ret = array();
			foreach($GLOBALS['ACCESS_KEYS'] as $k=>$v) {
				if($tree) {
					$ret[] = $k;
					foreach($v as $vv) $ret[] = "-- $vv";
				} else {
					foreach($v as $vv) $ret[] = $vv;
				}
			}
			return $ret;
		}
	}

	/**
	 * Serialize an array of access keys into a CSV list suitable
	 * for DB storage
	 *
	 * @param array $keys
	 * @return string
	 */
	function serialize_keys($keys)
	{
		$ak = array();
		if(!is_array($keys)) $keys = array($keys);
		if(ACCESS_MODEL == "discrete") {
			foreach($keys as $key) {
				if(substr($key, 0, 3) == '-- ') {
					// regular key
					$ak[] = substr($key, 3);
				} else {
					// group name, include all keys under it
					foreach($GLOBALS['ACCESS_KEYS'][$key] as $v) $ak[] = $v;
				}
			}
		} else if(ACCESS_MODEL == "roles") {
			// no action necessary
			$ak = $keys;
		}
		return join(',', array_unique($ak));
	}

	/**
	 * Unserialize a CSV list of access keys into an array so it can be
	 * used in a select/multiselect widget.
	 *
	 * @param string $keys
	 * @return array
	 */
	function unserialize_keys($keys)
	{
		// ignore if $keys is already an array
		if(is_array($keys)) return $keys;

		if(ACCESS_MODEL == "discrete") {
			// since only keys (and not module/group names) are stored, we
			// know each key from the DB will be a sub-item
			$ak = array();
			foreach(explode(',', $keys) as $k) $ak[] = "-- $k";
		} else if(ACCESS_MODEL == "roles") {
			$ak = explode(',', $keys);
		}
		return $ak;
	}

	function clear_keys($clear_sess=false)
	{
		$this->access_keys = array();
		if($clear_sess) $_SESSION['_ACCESS']['keys'] = $this->access_keys;
	}

	function has_access($key)
	{
		if(!is_array($this->access_keys)) return false;
		return in_array($key, $this->access_keys);
	}
}

?>
