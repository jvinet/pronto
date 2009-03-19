<?php

class p_UENTITY_ extends Page_CRUD
{
	function __init__()
	{_MODULE_DESIGNATION_
		$this->import_model('_ENTITY_');
		$this->set_entity('_ENTITY_', '_UENTITY_');
	}

	/**********************************************************************
	 * OVERRIDES FOR CRUD OPERATIONS
	 **********************************************************************/
	function authenticate()
	{
		$this->web->check_access('ADMIN');
	}
}

?>
