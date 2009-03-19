<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Template plugin for general pagination
 *
 **/
class tpPager extends Plugin
{
	/**
	 * Constructor
	 */
	function tpPager() {
		$this->Plugin();
		$this->depend('html');
	}

	function generate($url, $perpage, $curpage, $total, $var_prefix='p_')
	{
		$numpages = ceil($total / $perpage);
		if($numpages <= 1) return '';

		$out = '<ul class="pagination">';
		$cp = $curpage;
		$pp = $perpage;

		$page = $this->_pagelink($cp, $cp, $pp, $url, $var_prefix);
		// left side
		if($cp > 1) {
			$page = $this->_pagelink($cp-1, $cp, $pp, $url, $var_prefix).$page;
			$left = $cp - 2;
			if($left > 0) {
				if($left > 2) $page = '<li class="ellipsis"><span>...</span></li> '.$page;
				if($left > 1) $page = $this->_pagelink(2, $cp, $pp, $url, $var_prefix).$page;
				$page = $this->_pagelink(1, $cp, $pp, $url, $var_prefix).$page;
			}
		}
		// right side
		if($cp < $numpages) {
			$page = $page.$this->_pagelink($cp+1, $cp, $pp, $url, $var_prefix);
			$left = $numpages - $cp - 1;
			if($left > 0) {
				if($left > 2) $page = $page.'<li class="ellipsis"><span>...</span></li> ';
				if($left > 1) $page = $page.$this->_pagelink($numpages-1, $cp, $pp, $url, $var_prefix);
				$page = $page.$this->_pagelink($numpages, $cp, $pp, $url, $var_prefix);
			}
		}
		$out .= rtrim($page);

		$out .= '</ul>';
		return $out;
	}

	function _pagelink($pagenum, $curpage, $perpage, $url, $prefix)
	{
		if($pagenum == $curpage) {
			$out = "<li class=\"current\"><span>$pagenum</span></li> ";
		} else {
			// build a new query with pagination parameters
			$GET = array_merge($_GET, array($prefix.'p'=>$pagenum, $prefix.'pp'=>$perpage));
			$qs = array();
			foreach($GET as $k=>$v) {
				$qs[] = "$k=$v";
			}
			$qs = implode('&', $qs);
			$out = '<li><span>'.$this->depends->html->link($pagenum, url($url.'?'.$qs)).'</span></li> ';
		}
		return $out;
	}

	function _attribs($attribs)
	{
		$ret = '';
		foreach($attribs as $k=>$v) {
			$ret .= " $k=\"$v\"";
		}
		return $ret;
	}

}

?>
