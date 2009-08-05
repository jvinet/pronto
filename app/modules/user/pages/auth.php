<?php

/**
 * Authentication routines.
 */

class pUser_Auth extends Page
{
	function __init__()
	{
		$this->import_model('user');
		$this->set_module('user');
	}

	/**********************************************************************
	 * LOGIN - NORMAL METHOD (EMAIL/PASSWORD)
	 **********************************************************************/
	function GET_login()
	{
		unset($_SESSION['openid']);
		if($this->ajax) {
			$msg = __('This feature is only available to site members. Please create an account and login first.');
			$this->ajax_render('', array('exec'=>"alert('".str_replace("'","\\'",$msg)."');"));
		} else {
			$return_url = $this->param('return_url');
			if($return_url) {
				$this->tset('return_url', $return_url);
			}
			$this->render('login.php');
		}
	}
	function POST_login()
	{
		$email    = $this->param('email');
		$password = $this->param('password');

		$err = $this->models->user->authenticate_password($email, $password);
		if($err !== true) {
			$this->tset('error', $err);
			$this->GET_login();
		} else {
			$return_url = $this->param('return_url', '/');
			$this->redirect(url($return_url));
		}
	}

	/**********************************************************************
	 * LOGIN - OPENID METHOD
	 **********************************************************************/
	function GET_login__openid()
	{
		if(USER_USE_OPENID !== true) {
			$this->redirect(url('User','login'));
			return;
		}
		if($this->param('openid_mode')) {
			start_session();
			define('OPENID_NOKEYMANAGER', 1);
			define('OPENID_SREG_REQUEST', 'nickname,email,fullname,dob,gender,postcode,country,language,timezone');
			require_once(dirname(__FILE__).'/../extlib/openid/processor.php');
			return;
		} else if(isset($_SESSION['openid']['validated'])) {
			$this->POST_login__openid();
			return;
		} else if(isset($_SESSION['openid']['error'])) {
			$this->tset('error', $_SESSION['openid']['error']);
		}
		$this->GET_login();
	}
	function POST_login__openid()
	{
		if(USER_USE_OPENID !== true) {
			$this->redirect(url('User','login'));
			return;
		}
		if(isset($_SESSION['openid']['validated'])) {
			if($_SESSION['openid']['validated']) {
				$err = $this->models->user->authenticate_openid($_SESSION['openid']['identity']);
				if($err === false) {
					// no user exists yet, but we have a positive openid auth,
					// so let's create a new account...
					$_SESSION['newuser_data'] = array();
					if(is_array($_SESSION['openid']['sreg'])) {
						$_SESSION['openid_identity']  = $_SESSION['openid']['identity'];
						$_SESSION['openid_user_data'] = $_SESSION['openid']['sreg'];
						$this->redirect(url('User','signup'));
						return;
					}
				}
			} else {
				$err = $_SESSION['openid']['error'];
			}
			unset($_SESSION['openid']);

			if($err !== true) {
				$this->tset('error', $err);
				$this->GET_login();
			} else {
				$return_url = $this->param('return_url', '/');
				$this->redirect(url($return_url));
			}
		} else if($this->param('openid_url')) {
			define('OPENID_NOKEYMANAGER', 1);
			define('OPENID_SREG_REQUEST', 'nickname,email,fullname,dob,gender,postcode,country,language,timezone');
			start_session();
			require_once(dirname(__FILE__).'/../extlib/openid/processor.php');
		}
	}

	/**********************************************************************
	 * LOGOUT
	 **********************************************************************/
	function GET_logout()
	{
		$this->models->user->clear_authentication();
		$this->redirect(url('/'));
	}

}

?>
