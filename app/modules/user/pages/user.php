<?php

/**
 * Common user functions.
 */

class pUser extends Page
{
	function __init__()
	{
		$this->import_model('user');
		$this->set_module('user');
	}

	/**********************************************************************
	 * CREATE NEW ACCOUNT
	 **********************************************************************/
	function GET_captcha()
	{
		$this->import_plugin('captcha');
		$this->plugins->captcha->init();
		$_SESSION['captcha'] = $this->plugins->captcha->get_key();
		$this->plugins->captcha->display();
	}
	function GET_signup()
	{
		if(isset($_SESSION['openid_user_data'])) {
			$s = $_SESSION['openid_user_data'];
			$fn = '';
			$ln = $s['fullname'];
			$names = explode(' ', $s['fullname']);
			if(count($names) > 1) {
				$fn = array_shift($names);
				$ln = implode(' ', $names);
			}
			$data = array(
				'is_openid'  => true,
				'email'      => $s['email'],
				'first_name' => $fn,
				'last_name'  => $ln
			);
			$this->tset('new_user', true);
			$this->tset('data', $data);
		}
		$this->render('signup.php');
	}
	function POST_signup()
	{
		$data = $this->load_input();
		$is_update = false;
		if(ACCESS_ID) {
			$is_update = true;
			$data['id'] = ACCESS_ID;
		}

		$errors = $this->models->user->validate($data);
		if(!$is_update) {
			if(!isset($_SESSION['captcha']) || strcasecmp($_SESSION['captcha'],$data['captcha'])) {
				$errors['captcha'] = __("String did not match image -- please try again.");
			}
		}
		if(!empty($errors)) {
			$this->return_to_form('', $data, $errors);
			return;
		}

		$id = $this->models->user->save($data);
		if($is_update) {
			$_SESSION['USER'] = $this->models->user->load($data['id']);

			$this->flash(__('Your profile has been updated.'));
			$this->redirect(url('User','profile'));
		} else {
			// send confirmation email
			$user = $this->models->user->fetch($id);
			$this->tset('user', $user);
			$tmpl = $this->fetch('email/confirm.php');
			$this->plugins->mailer->send($user['email'], __('%s: Confirm Your Account', SITE_NAME), $tmpl);

			unset($_SESSION['openid_identity'], $_SESSION['openid_user_data']);

			$this->flash(__('Your account has been created.'));
			$this->render('signup_success.php');
		}
	}

	/**********************************************************************
	 * CONFIRM NEW ACCOUNT
	 **********************************************************************/
	function GET_confirm()
	{
		$token = $this->param('t');
		if(empty($token)) {
			$this->redirect(url('/'));
			return;
		}

		if(!$this->models->user->confirm_by_token($token)) {
			$this->render('confirm_error.php');
			return;
		}
		$this->flash(__("Your account has been activated!"));
		$this->redirect(url('/'));
	}

	/**********************************************************************
	 * EDIT PROFILE
	 **********************************************************************/
	function GET_profile()
	{
		$this->web->check_access('USER');
		$data = $this->models->user->load(ACCESS_ID);
		unset($data['password']);
		$this->tset('data', $data);

		$this->GET_signup();
	}
	function POST_profile()
	{
		$this->web->check_access('USER');
		$this->POST_signup();
	}

	/**********************************************************************
	 * CHANGE LANGUAGE
	 **********************************************************************/
	function GET_set_lang()
	{
		$_SESSION['LANGUAGE'] = $this->param('lang', 'en');
		$this->redirect_to_referrer(url('/'));
	}

	/**********************************************************************
	 * RESET PASSWORD
	 **********************************************************************/
	function GET_resetpass()
	{
		$this->render('resetpass.php');
	}
	function POST_resetpass()
	{
		$r =& $this->models->user->find("email='%s'", $this->param('email'));
		$user = $r->load();
		if(!$user) {
			$this->tset('error', __('No user account found'));
			$this->render('login.php');
			return;
		}
		if($user['openid']) {
			$this->tset('error', __("This account is authenticated with OpenID. To reset your OpenID password, visit your OpenID provider's website."));
			$this->render('login.php');
			return;
		}

		$password = $this->models->user->generate_password();
		$this->models->user->set_password($user['id'], $password);
		$this->tset('user', $user);
		$this->tset('password', $password);

		$body = $this->fetch('email/resetpass.php');
		$this->plugins->mailer->send($user['email'], __('%s: Reset Password', SITE_NAME), $body);

		$this->flash(__('Your new password has been emailed to you.'));
		$this->redirect(url('User_Auth','login'));
	}

}

?>
