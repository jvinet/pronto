<?php

class pHome extends Page_Static
{
	function __init__()
	{
		$this->set_dir('home');
	}

	function GET()  {
		$this->plugins->mailer->send('jvinet@zeroflux.org', 'SendGrid Test',
			'This is a test', 'jvinet@zeroflux.org');
		echo 'Sent';
		$this->render('home/index.php');
	}
}


?>
