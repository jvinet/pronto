<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Internationalization functions.
 *
 **/

class I18N
{
	var $config_path;
	var $language_code;
	var $language_name;
	var $messages;

	function I18N($lang='en')
	{
		$this->config_path = DIR_FS_APP.DS.'config'.DS.'i18n';
		$this->set_language($lang);
	}

	function get_languages()
	{
		$cache =& Registry::get('pronto:cache');
		$langs =& $cache->get('pronto:i18n_languages');
		if(is_array($langs)) return $langs;

		$langs = array();
		foreach(glob($this->config_path.DS.'*') as $d) {
			if(is_dir($d) && file_exists($d.DS.'messages.php')) {
				include($d.DS.'messages.php');
				$langs[$LANGUAGE_CODE] = $LANGUAGE_NAME;
			}
		}
		$cache->set('pronto:i18n_languages', $langs);
		return $langs;
	}

	/**
	 * @return string The language that was set prior to this call.
	 */
	function set_language($lang)
	{
		$old = $this->get_language();
		$lang = strtolower($lang);
		$this->language_code = $lang;
		if(!$this->has_language($lang)) {
			$this->messages = array();
			return;
		}

		// Try the registry first, then the cache.
		$cache =& Registry::get('pronto:cache');
		$msgs =& Registry::get('pronto:i18n_language_'.$lang);
		if(!is_array($msgs)) {
			$msgs =& $cache->get('pronto:i18n_language_'.$lang);
		}
		if(!is_array($msgs)) {
			include($this->config_path.DS.$lang.DS.'messages.php');
			if(strtolower($LANGUAGE_CODE) != $lang) {
				trigger_error("i18n: Language requested does not match i18n messages file.");
			}
			$msgs = compact('MESSAGES', 'LANGUAGE_NAME', 'LANGUAGE_CODE');
			unset($MESSAGES, $LANGUAGE_NAME, $LANGUAGE_CODE);

			// scan module directories as well
			foreach(explode(' ', MODULES) as $modname) {
				$modpath = DIR_FS_APP.DS.'modules'.DS.$modname.DS.'config'.DS.'i18n';
				if(file_exists($modpath.DS.$lang.DS.'messages.php')) {
					include($modpath.DS.$lang.DS.'messages.php');
					if(strtolower($LANGUAGE_CODE) != $lang) {
						trigger_error("i18n: Language requested does not match i18n messages file.");
					}
					$msgs['MESSAGES'] = array_merge($msgs['MESSAGES'], $MESSAGES);
					unset($MESSAGES, $LANGUAGE_NAME, $LANGUAGE_CODE);
				}
			}

			Registry::set('pronto:i18n_language_'.$lang, $msgs);
			$cache->set('pronto:i18n_language_'.$lang, $msgs);
		}

		$this->language_name = $msgs['LANGUAGE_NAME'];
		$this->messages      = $msgs['MESSAGES'];
		return $old;
	}

	function get_language()
	{
		return $this->language_code;
	}

	function get_language_name()
	{
		return $this->language_name;
	}

	function has_language($lang)
	{
		if(empty($lang)) return false;
		$lang = strtolower($lang);
		return file_exists($this->config_path.DS.$lang.DS.'messages.php');
	}

	// Automatically set language based on browser headers or session data
	function autoset_language($default='en')
	{
		$lang = $default;
		if(isset($_SESSION['LANGUAGE'])) {
			$lang = $_SESSION['LANGUAGE'];
		} else if(isset($_SESSION['USER']['language'])) {
			$lang = $_SESSION['USER']['language'];
		} else if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			$langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
			foreach($langs as $l) {
				$code = str_replace('-', '_', array_shift(explode(';', $l)));
				$basecode = array_shift(explode('_', $code));
				if($this->has_language($code)) {
					$lang = $code;
					break;
				} else if($code != $basecode && $this->has_language($basecode)) {
					$lang = $basecode;
					break;
				}
			}
		}
		$this->set_language($lang);
	}

	function msg($msg)
	{
		$args = func_get_args();
		if(isset($this->messages[$msg])) {
			$args[0] = $this->messages[$msg];
		} else {
			$args[0] = $msg;
		}
		$str = call_user_func_array('sprintf', $args);
		return $str;
	}
}

/**
 * Shortcut function for the I18N::msg() method.
 *
 * @param string $msg String to translate
 * @return string The translated string
 */
function __($msg)
{
	$i18n =& Registry::get('pronto:i18n');
	$args = func_get_args();
	if(is_object($i18n)) {
		return call_user_func_array(array(&$i18n,'msg'), $args);
	}
	return call_user_func_array('sprintf', $args);
}

/**
 * Convenience function - same as __(), but echo the result instead of
 * merely returning it.
 *
 * @param string $msg String to translate
 */
function _e($msg)
{
	$args = func_get_args();
	echo call_user_func_array('__', $args);
}

?>
