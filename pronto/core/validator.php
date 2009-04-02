<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Utility class for validating input data from GET/POST, etc.
 *
 **/

/*
 * Regular Expressions used for Input Validation
 *
 */
define('VALID_NOT_EMPTY', '/.+/');
define('VALID_NUMBER',    '/^[0-9]+$/');
define('VALID_FLOAT',     '/^[0-9]*\.?[0-9]+$/');
define('VALID_EMAIL',     '/\\A(?:^([a-z0-9][a-z0-9_\\-\\.\\+]*)@([a-z0-9][a-z0-9\\.\\-]{0,63}\\.(com|org|net|biz|info|name|net|pro|aero|coop|museum|[a-z]{2,4}))$)\\z/i');
define('VALID_URL',       '@((ht|f)tps?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@');
define('VALID_DATE',      '/^[12][0-9]{3}-[01][0-9]-[0123][0-9]$/');
define('VALID_TIME',      '/^[0-2][0-9]:[0-5][0-9]:[0-5][0-9]$/');
define('VALID_YEAR',      '/^[12][0-9]{3}$/');

class Validator
{
	/**
	 * Basic input validation
	 *
	 * @param array $errors List of errors found during validation
	 * @param string $var Name of request variable to check
	 * @param string $regex Regular expression to use for validation (see top of page.php for defaults regexps)
	 * @param string $errorstr Error string to put in $errors if validation check fails
	 * @param array $data Array of arguments that contains the request variable to validate.
	 *                    If not set, then the default request argument array will be used.
	 */
	function validate(&$errors, $var, $regex, $errorstr, $data=false)
	{
		$req = is_array($data) ? $data : Registry::get('pronto:request_args');
		$val = $this->prepare_input($req[$var]);
		if(!$this->is_valid($val, $regex)) {
			if(is_array($errors)) $errors[$var] = $errorstr;
			return false;
		}
		return true;
	}

	/**
	 * Shortcut for basic validation - useful for times when you
	 * don't need to populate an array of error messages.
	 *
	 * @param string $value Value to check for validity
	 * @param string $regex Regular expression to use for validation (see top of page.php for default regexps)
	 * @return boolean
	 */
	function is_valid($value, $regex)
	{
		return !!preg_match($regex, $value);
	}

	/**
	 * Validation shortcut to check for required variables
	 *
	 * @param array $errors List of errors found during validation
	 * @param array $vars Names of request variables to check
	 * @param array $data Array of arguments that contains the request variable to validate.
	 *                    If not set, then the default request argument array will be used.
	 */
	function required(&$errors, $vars, $data=false)
	{
		foreach($vars as $v) {
			$errstr = __('%s is required', ucwords(str_replace('_', ' ', $v)));
			$this->validate($errors, $v, VALID_NOT_EMPTY, $errstr, $data);
		}
	}

	/**
	 * Strip slashes if necessary, depending on magic_quotes_gpc
	 *
	 * @param mixed $val
	 * @return mixed
	 */
	function prepare_input($val)
	{
		if(is_array($val)) {
			foreach($val as $k=>$v) {
				$val[$k] = $this->prepare_input($v);
			}
			return $val;
		}
		return get_magic_quotes_gpc() ? stripslashes($val) : $val;
	}
}

?>
