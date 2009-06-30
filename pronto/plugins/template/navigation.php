<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Template plugin for navigation items such as menus.
 *
 * Example Config:
 @code
	$NAV_MENU = array(
		'Home'  => array('access'=>'', 'url'=>url('/')),
		'Manage' => array('access'=>'ADMIN', 'menu'=>array(
			'Users'   => array('menu'=>array(
				'Create' => array('url'=>url('/admin/user/create')),
				'List'   => array('url'=>url('/admin/user/list')),
				'_sep1'  => '',
				'Moderators' => array('menu'=>array(
					'Create' => array('url'=>url('/')),
					'List'   => array('url'=>url('/')),
				)),
			)),
			'Posts'  => array('menu'=>array(
				'Create' => array('url'=>url('/')),
				'List'   => array('url'=>url('/')),
				'_sep1' => '',
				'Comments' => array('menu'=>array(
					'List' => array('url'=>url('/')),
				)),
			)),
		)),
	);
 @endcode
 *
 **/


class tpNavigation extends Plugin
{
	/**
	 * Constructor
	 */
	function tpNavigation() {
		$this->Plugin();
		$this->depend('html');
	}

	function menu($config=false, $config_file='navigation.php')
	{
		if(!$config) {
			require_once(DIR_FS_APP.DS.'config'.DS.$config_file);
			$config = $NAV_MENU;
			unset($NAV_MENU);

			if(defined('MODULES')) {
				foreach(explode(' ', MODULES) as $modname) {
					$modpath = DIR_FS_APP.DS.'modules'.DS.$modname.DS.'config'.DS;
					if(file_exists($modpath.$config_file)) {
						require_once($modpath.$config_file);
						$config += $NAV_MENU;
						unset($NAV_MENU);
					}
				}
			}
		}

		$out = '<ul class="nav">';
		foreach($config as $label=>$item) {
			$out .= $this->_submenu($label, $item, true);
		}
		$out .= '</ul>';
		return $out;
	}

	function _is_active($menu)
	{
		foreach($menu as $label=>$item) {
			if(isset($menu['base']) && preg_match("|^{$menu['base']}|", url(CURRENT_URL))) return true;
			if(isset($item['menu']) && $this->_is_active($item['menu'])) return true;
			if(isset($item['url']) && $item['url'] == url(CURRENT_URL)) return true;
		}
		return false;
	}

	function _submenu($label, $menu, $toplevel)
	{
		$ret = '';
		if(!empty($menu['access'])) {
			if(substr($menu['access'], 0, 1) == '!' && a(substr($menu['access'], 1))) return '';
			if(substr($menu['access'], 0, 1) != '!' && !a($menu['access'])) return '';
		}
		if(isset($menu['menu'])) {
			// see if any items in our submenu match the current URL; if
			// so, then give the first-tier tab the "current" class
			$a = $this->_is_active($menu['menu']) ? 'current' : '';

			// submenu
			$ret .= '<li><a href="#" class="'.$a.'">'.$label.'</a>';
			$ret .= '<ul>';
			foreach($menu['menu'] as $sublabel=>$subitem) {
				$ret .= $this->_submenu($sublabel, $subitem, false);
			}
			$ret .= '</ul></li>';
		} else if(isset($menu['url'])) {
			$a = array();
			if($toplevel) {
				if(url(CURRENT_URL) == $menu['url']) {
					$a['class'] = 'current';
				} else if(isset($menu['base']) && preg_match("|^{$menu['base']}|", url(CURRENT_URL))) {
					$a['class'] = 'current';
				}
			}
			$ret .= '<li>'.$this->depends->html->link($label, $menu['url'], '', false, $a).'</li>';
		} else {
			// separator
			$ret .= '<li></li>';
		}
		return $ret;
	}

}

?>
