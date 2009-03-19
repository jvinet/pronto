<?php

class p{{MODULE_HUMAN_NAME}}_Example extends Page
{
	function __init__()
	{
		// required for modules
		$this->set_module('{{MODULE_NAME}}');
	}

	function GET()
	{
		// if importing plugins from this module, you have to do
		// so explicitly, like so.
		$this->import_plugin('{{MODULE_NAME}}');
		$this->template->set('stuff', $this->plugins->{{MODULE_NAME}}->do_stuff());

		$this->render('{{MODULE_NAME}}.php');
	}
}

?>
