<?php

class pHome extends Page_Static
{
	function __init__()
	{
		$this->set_dir('home');

		l("debug", "Testing logger");
		l("warning", "Warning!");
	}
}


?>
