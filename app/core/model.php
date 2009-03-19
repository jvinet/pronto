<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Base class for all data models. Modify this class to make
 *              application-wide changes to data models.
 *
 **/

class Model extends Model_Base
{
	/**
	 * Constructor for all models
	 */
	function Model()
	{
		// parent constructor
		$this->Model_Base();
	}
}

?>
