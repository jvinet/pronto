<?php

/**
 * Administration functions for User managemnt.
 */

class pUser_Admin extends Page_CRUD
{
	function __init__()
	{
		$this->import_model('user');
		$this->set_entity('user', __('User'), 'admin/');
		$this->set_module('user');
	}

	/**********************************************************************
	 * OVERRIDES FOR CRUD OPERATIONS
	 **********************************************************************/
	function authenticate()
	{
		$this->web->check_access('ADMIN');
	}
	function hook__pre_edit(&$data)
	{
		$i18n =& Registry::get('pronto:i18n');
		$access =& Registry::get('pronto:access');

		$this->tset('languages', $i18n->get_languages());
		$this->tset('access_keys', $access->get_keys(true));
		$data['access_keys'] = $access->unserialize_keys($data['access_keys']);
		unset($data['password']);
	}
	function hook__pre_save(&$data)
	{
		$access =& Registry::get('pronto:access');
		$data['access_keys'] = $access->serialize_keys($data['access_keys']);
	}
	function hook_list__post_select(&$data)
	{
		$access =& Registry::get('pronto:access');
		$this->tset('access_keys', $access->get_keys());
	}

}

?>
