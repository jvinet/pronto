<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Template facilities.
 *
 **/
class Template
{
	var $variables;
	var $module_name;

	/**
	 * Constructor.
	 * 
	 * @param string $module_name If the template object is working within a
	 *                            Pronto module, then set the name here.  This will
	 *                            affect the path that the template object uses
	 *                            to find template files.
	 */
	function Template($module_name='')
	{
		$this->variables   = array();
		$this->module_name = $module_name;

		// load helpers
		foreach(explode(' ', HELPERS) as $p) {
			$this->import_helper($p);
		}
	}

	/**
	 * Declare this template object to be part of a Pronto module.
	 *
	 * @param string $name Module name
	 */
	function set_module($name)
	{
		$this->module_name = $name;
	}

	/**
	 * Import a helper (aka "template plugin").
	 * @param string $name Helper name
	 *
	 */
	function &import_helper($name)
	{
		foreach(func_get_args() as $name) {
			$class =& Factory::plugin($name, 'template');
			if($class === false) {
				trigger_error("Helper $name does not exist");
				die;
			}
		}
		if($class) return $class;
	}

	/**
	 * Set a variable to be passed to the template
	 *
	 * @param mixed $name
	 * @param mixed $value
	 */
	function set($name, $value)
	{
		if(is_array($name)) {
			// $value must be an array too
			reset($value);
			foreach($name as $k) {
				$this->variables[$k] = current($value);
				next($value);
			}
		} else {
			$this->variables[$name] = $value;
		}
	}

	/**
	 * Retrieve a variable that will be passed to the template
	 *
	 * @param string $name
	 * @return mixed
	 */
	function &get($name)
	{
		return $this->variables[$name];
	}

	/**
	 * Check if a template variable has been set
	 *
	 * @param string $name
	 * @return bool
	 */
	function is_set($name)
	{
		return isset($this->variables[$name]);
	}

	/**
	 * Unset/Delete a template variable that has been set
	 *
	 * @param string $name
	 */
	function un_set($name)
	{
		unset($this->variables[$name]);
	}

	/**
	 * Return a rendered template.
	 *
	 * @param string $filename Template file to render
	 * @param array $vars Additional variables to include in template
	 * @param string $language I18N language to set before rendering template
	 * @return string Rendered template
	 */
	function fetch($filename, $vars=array(), $language=false)
	{
		if(!is_array($vars)) $vars = array();

		$helpers =& Registry::get('pronto:helpers', array());
		// convert the stdClass to an array
		$helpers_arr = array();
		foreach($helpers as $k=>$v) {
			$helpers_arr[$k] = $v;
		}

		$vars = array_merge(
			$this->variables,
			$vars,
			$helpers_arr
		);
		// this makes it easier for template functions to access plugins
		$GLOBALS['PLUGINS'] = $helpers_arr;
		$GLOBALS['HELPERS'] = $helpers_arr;

		if($filename{0} == DS) {
			// Use the absolute path provided to us
			$template_path = $filename;
		} else {
			// Build a relative path depending on whether we're in a module or not...
			if(!empty($this->module_name)) {
				$template_path = DIR_FS_APP.DS.'modules'.DS.$this->module_name.DS.'templates'.DS.$filename;
			} else {
				$template_path = DIR_FS_APP.DS.'templates'.DS.$filename;
			}
		}
		$vars['__template_path'] = dirname($template_path);

		$i18n = Registry::get('pronto:i18n');
		$old = ($language && $i18n) ? $i18n->set_language($language) : '';
		ob_start();
		extract($vars, EXTR_OVERWRITE);
		require $template_path;
		$content = ob_get_contents();
		ob_end_clean();

		// clean up
		if($old) $i18n->set_language($old);
		unset($GLOBALS['PLUGINS'], $GLOBALS['HELPERS']);

		return $content;
	}

}

?>
