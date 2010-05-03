<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright &copy; 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Template plugin for common HTML elements.
 *
 **/
class tpForm extends Plugin
{
	var $guid    = 0;
	var $data_id = 0;

	var $errors;
	var $form_name;
	var $element_layouts;

	/**
	 * Constructor
	 */
	function tpForm() {
		$this->Plugin();
		$this->depend('html');

		$this->errors    = array();
		$this->form_name = '';

		$this->element_layouts = array(
			'leftlabel' => '<div class="form_element_container clearfix">'.
			               '<div class="form_help form_leftlabel">{{HELP}}</div>'.
			               '<div class="form_label form_leftlabel" style="width:{{LBLWIDTH}}; min-width:{{LBLWIDTH}};">{{LABEL}}</div>'.
										 '<div class="form_element form_leftlabel">{{ELEMENT}}{{ERROR}}</div><!--[if IE]><br /><![endif]-->'.
				             '</div>',
			'toplabel'  => '<div class="form_element_container clearfix">'.
			               '<div class="form_element form_toplabel">{{HELP}}{{LABEL}}<br />{{ELEMENT}}{{ERROR}}</div>'.
			               '</div>',
			'labelonly' => '<div class="form_element_container clearfix">'.
			               '<div class="form_label form_leftlabel" style="max-width:100%; width:100%">{{LABEL}}</div>'.
			               '</div>',
			'elemonly'  => '<div class="form_element_container clearfix">'.
			               '<div class="form_element form_leftlabel" style="max-width:100%; width:100%">{{ELEMENT}}{{ERROR}}</div>'.
			               '</div>'
		 );
	}

	/**
	 * Propagate all GET/POST fields into hidden form elements
	 *
	 * @param mixed $data If blank, propagate all GET/POST vars.  If set to "get" or "post", propagate
	 *                    only those variables.  If set to an array, propagate all values in the array.
	 * @return string
	 */
	function propagate($data='', $prefix='', $suffix='')
	{
		$out = '';
		if(!is_array($data)) {
			switch(strtolower($data)) {
				case 'post': $data = $_POST; break;
				case 'get':  $data = $_GET; break;
				default:     $data = array_merge($_GET, $_POST);
			}
		}
		foreach($data as $k=>$v) {
			if(is_array($v)) {
				if($prefix) {
					$out .= $this->propagate($v, $prefix.$k.'][', ']');
				} else {
					$out .= $this->propagate($v, $prefix.$k.'[', ']');
				}
				continue;
			}
			$out .= $this->hidden($prefix.$k.$suffix, $v)."\n";
		}
		return $out;
	}

	/**
	 * Display a list of errors in a div element
	 *
	 * @param array $errors Associative array of errors
	 * @return string
	 */
	function error_box($errors)
	{
		$out = '<div class="error"><ul>';
		foreach($errors as $k=>$v) {
			$out .= "<li>$v</li>";
		}
		$out .= '</ul></div>';
		return $out;
	}

	/**
	 * Generate a tooltip (contextual help) icon.  When the mouse hovers
	 * over it, a little popup will appear, displaying the help text.
	 *
	 * @param string $text The text to display in the popup.
	 * @param string $icon URL of the icon to use, relative to the /img
	 *                     directory.  Default is icons/info.gif.
	 * @return string The resulting HTML.
	 */
	function tooltip($text, $icon='')
	{
		if(!$icon) $icon = 'icons/info.gif';
		$this->depends->html->js_load('jq_tooltip', 'jq/jquery.tooltip');
		$this->depends->html->js_run('jq_tooltip', '$(\'img.helpicon\').Tooltip({showURL:false,delay:0});');
		$this->depends->html->css_load('tooltip');

		// escape double-quotes
		$text = preg_replace('|"|', '\\"', $text);
		return $this->depends->html->image($icon, array('class'=>'helpicon','title'=>$text));
	}

	/**
	 * Bind tooltip functionality to one or more elements.  The value of the
	 * element's "title" attribute will be used for the tooltip content.
	 *
	 * @param string $dom_id The jQuery selector of the elements.
	 */
	function tooltip_bind($elements)
	{
		$this->depends->html->js_load('jq_tooltip', 'jq/jquery.tooltip');
		$this->depends->html->js_run('', '$(\''.$elements.'\').Tooltip({showURL:false,delay:0});');
		$this->depends->html->css_load('tooltip', 'tooltip');
	}

	/**
	 * Open a new form.  Returns the form tag, and can optionally
	 * load in an array of errors that can be linked to form elements
	 * within this form.
	 *
	 * @param string $name Name and ID of the form element
	 * @param string $method Submission method ('get' or 'post')
	 * @param array $errors Associative array of errors
	 * @param array $attribs Additional HTML attributes
	 * @return string
	 */
	function open_form($name, $action, $method='post', $errors=array(), $attribs=array())
	{
		$this->form_name = '';
		list($name) = $this->_escape($name);
		$id = $this->dom_id(isset($attribs['id']) ? $attribs['id'] : $name);
		unset($attribs['id']);
		$out  = '<form name="'.$name.'" id="'.$id.'" method="'.$method.'" action="'.$action.'"';
		$out .= $this->depends->html->_attribs($attribs) . '>';;
		$this->errors    = $errors;
		$this->form_name = $name;
		return $out;
	}

	/**
	 * Close a form.  Returns the /form tag and clears any errors that
	 * were loaded for the form, returning the plugin to a clean state so
	 * a new form can be used.
	 *
	 * @return string
	 */
	function close_form()
	{
		$this->errors = array();
		return '</form>';
	}

	/**
	 * Generate an image input tag
	 *
	 * @param string $name Name attribute
	 * @param string $src URL of image
	 * @param array $attribs Additional HTML attributes
	 * @return string
	 */
	function imagebutton($name, $src, $attribs=array())
	{
		list($name,$src) = $this->_escape($name,$src);
		$id   = $this->dom_id($name);
		$out  = '<input type="image" name="'.$name.'" src="'.$src.'" align="absmiddle"';
		$attribs['class'] .= (empty($attribs['class']) ? 'image' : ' image');
		$out .= $this->depends->html->_attribs(array_merge(array('id'=>$id), $attribs));
		$out .= ' />';
		return $out;
	}

	/**
	 * Generate a submit button
	 *
	 * @param string $name Name attribute
	 * @param string $value Button text
	 * @param array $attribs Additional HTML attributes
	 * @return string
	 */
	function submit($name, $value='Submit', $attribs=array())
	{
		list($name,$value) = $this->_escape($name,$value);
		$id   = $this->dom_id($name);
		$out  = '<input type="submit" name="'.$name.'" value="'.$value.'" class="submit" align="absmiddle"';
		$out .= $this->depends->html->_attribs(array_merge(array('id'=>$id), $attribs));
		$out .= ' />';
		return $out;
	}

	/**
	 * Generate a hidden form element
	 *
	 * @param string $name
	 * @param string $value
	 * @param array $attribs Additional HTML attributes
	 * @return string
	 */
	function hidden($name, $value, $attribs=array())
	{
		list($name,$value) = $this->_escape($name,$value);
		$id   = $this->dom_id($name);
		$out  = '<input type="hidden" name="'.$name.'" value="'.$value.'"';
		$out .= $this->depends->html->_attribs(array_merge(array('id'=>$id), $attribs));
		$out .= ' />';
		return $out;
	}

	/**
	 * Generate a text form element
	 *
	 * @param string $name
	 * @param string $value
	 * @param string $size
	 * @param string $maxlength
	 * @param array $attribs Additional HTML attributes
	 * @return string
	 */
	function text($name, $value='', $size='', $maxlength='', $attribs=array())
	{
		list($name,$value) = $this->_escape($name,$value);
		$id  = $this->dom_id($name);
		$err = $this->_error($name, $attribs);
		$out = '<input type="text" name="'.$name.'" value="'.$value.'"';
		if($size)      $out .= ' size="'.$size.'"';
		if($maxlength) $out .= ' maxlength="'.$maxlength.'"';
		$out .= $this->depends->html->_attribs(array_merge(array('id'=>$id), $attribs));
		$out .= ' />';
		return $out.$err;
	}

	/**
	 * Generate a password form element
	 *
	 * @param string $name
	 * @param string $value
	 * @param string $size
	 * @param string $maxlength
	 * @param array $attribs Additional HTML attributes
	 * @return string
	 */
	function password($name, $value='', $size='', $maxlength='', $attribs=array())
	{
		list($name,$value) = $this->_escape($name,$value);
		$id  = $this->dom_id($name);
		$err = $this->_error($name, $attribs);
		$out = '<input type="password" name="'.$name.'" value="'.$value.'"';
		if($size)      $out .= ' size="'.$size.'"';
		if($maxlength) $out .= ' maxlength="'.$maxlength.'"';
		$out .= $this->depends->html->_attribs(array_merge(array('id'=>$id), $attribs));
		$out .= ' />';
		return $out.$err;
	}

	/**
	 * Generate a file-upload form element
	 *
	 * @param string $name
	 * @param string $value
	 * @param string $size
	 * @param boolean $preview Show a "preview" link so the user can view
	 *                the file currently in use.
	 * @param boolean $remove Show a "remove" link so the user can delete
	 *                the file currently in use without having to upload a
	 *                new one.
	 * @param array $attribs Additional HTML attributes
	 * @return string
	 */
	function file($name, $value='', $size='', $preview_url='', $remove_url='', $attribs=array())
	{
		list($name,$value) = $this->_escape($name,$value);
		$id  = $this->dom_id($name);
		$err = $this->_error($name, $attribs);

		$out = '<input type="file" name="'.$name.'" value="'.$value.'"';
		if($size) $out .= ' size="'.$size.'"';
		$out .= $this->depends->html->_attribs(array_merge(array('id'=>$id), $attribs));
		$out .= ' />';

		$preview_url = str_replace(array('_ID_','<id>'), $this->data_id, $preview_url);
		$remove_url  = str_replace(array('_ID_','<id>'), $this->data_id, $remove_url);

		if($preview_url) {
			$out .= ' &nbsp; '.$this->depends->html->link(__('Preview'), url($preview_url), '', false, array('target'=>'_blank'));
		} else if($value && $preview_url !== false) {
			// Use a sensible default URL (direct link to the file).
			// Don't url()-ize it, it's already absolute
			$out .= ' &nbsp; '.$this->depends->html->link(__('Preview'), $value, '', false, array('target'=>'_blank'));
		}
		if($remove_url) {
			$sep = $preview_url !== false ? ' | ' : ' &nbsp; ';
			$out .= $sep.$this->depends->html->link(__('Remove'), url($remove_url), __('Are you sure you want to remove this file?'));
		}
		return $out.$err;
	}

	/**
	 * Generate a file-upload form element intended for images, with a preview
	 *
	 * @param string $name
	 * @param string $value
	 * @param string $size
	 * @param boolean $preview Show a "preview" link so the user can view
	 *                a larger version of the image currently in use.
	 * @param boolean $remove Show a "remove" link so the user can delete
	 *                the image currently in use without having to upload a
	 *                new one.
	 * @param array $attribs Additional HTML attributes
	 * @return string
	 */
	function image($name, $value='', $size='', $preview_url='', $remove_url='', $attribs=array())
	{
		list($name,$value) = $this->_escape($name,$value);
		$id  = $this->dom_id($name);
		$err = $this->_error($name, $attribs);
		$out = '';
		$display = 'block';
		$preview_url = str_replace(array('_ID_','<id>'), $this->data_id, $preview_url);
		$remove_url  = str_replace(array('_ID_','<id>'), $this->data_id, $remove_url);
		if($value) {
			$display = 'none';
			$out .= '<div id="'.$name.'_img">';
			if($preview_url) {
				$out .= $this->depends->html->link('<img src="'.$value.'" border="0" />', url($preview_url), '', true);
			} else {
				$out .= '<img src="'.$value.'" border="0" />';
			}
			$out .= '<br /><a href="#" onClick="$(\'#'.$name.'_img\').hide(\'normal\',function(){ $(\'#'.$name.'_file\').show(\'normal\'); });return false;">'.__('Change').'</a>';
			if($remove_url) {
				$out .= ' | '.$this->depends->html->link('Delete', url($remove_url), __('Are you sure?'));
			}
			$out .= '<br /></div>';
		}
		$out .= '<div id="'.$name.'_file" style="display:'.$display.'">';
		$out .= '<input type="file" name="'.$name.'" value="'.$value.'"';
		if($size) $out .= ' size="'.$size.'"';
		$out .= $this->depends->html->_attribs(array_merge(array('id'=>$id), $attribs));
		$out .= ' /></div>';
		return $out.$err;
	}

	/**
	 * Generate a date-selection form element
	 *
	 * @param string $name
	 * @param string $value
	 * @param string $format Date format
	 * @param array $attribs Additional HTML attributes
	 * @return string
	 */
	function date($name, $value='', $format='%Y-%m-%d', $attribs=array())
	{
		$err = $this->_error($name, $attribs);
		$id  = $this->dom_id($name);
		$this->depends->html->css_load('calendar', 'calendar');
		$this->depends->html->js_load('calendar', 'calendar/calendar');
		$this->depends->html->js_load('calendar_lang', 'calendar/calendar-en');
		$this->depends->html->js_load('calendar_setup', 'calendar/calendar-setup');
		$js = <<<EOT
	Calendar.setup({
		inputField:  "$id",
		ifFormat:    "$format",
		showsTime:   false,
		button:      "btn_$name",
		singleClick: true,
		showOthers:  true
	});
EOT;
		$this->depends->html->js_run('calendar_setup', $js, false);
		$out  = $this->text($name, $value, 10, 22, array_merge($attribs,array('no_error'=>true)));
		$out .= $this->depends->html->image('icons/calendar.gif', array('id'=>"btn_$name",'align'=>'top'));
		return $out.$err;
	}

	/**
	 * Generate a date/time selection form element
	 *
	 * @param string $name
	 * @param string $value
	 * @param string $format Date/time format
	 * @param array $attribs Additional HTML attributes
	 * @return string
	 */
	function datetime($name, $value='', $format='%Y-%m-%d %k:%M', $attribs=array())
	{
		$err = $this->_error($name, $attribs);
		$id  = $this->dom_id($name);
		$this->depends->html->css_load('calendar', 'calendar');
		$this->depends->html->js_load('calendar', 'calendar/calendar');
		$this->depends->html->js_load('calendar_lang', 'calendar/calendar-en');
		$this->depends->html->js_load('calendar_setup', 'calendar/calendar-setup');
		$js = <<<EOT
	// fix the width if in a table cell
	var inp = $('#$id');
	var dad = inp.parent()[0].nodeName.toLowerCase();
	if(dad == 'th' || dad == 'td') {
		inp.css('width', inp.width()+'px');
		inp.parent().css('width', (inp.width()+20+'px'));
	}
	inp.parent().find('img').css('cursor','pointer');

	Calendar.setup({
		inputField:  "$id",
		ifFormat:    "$format",
		showsTime:   true,
		button:      "btn_$name",
		singleClick: true,
		showOthers:  true
	});
EOT;
		$this->depends->html->js_run('calendar_setup', $js, false);
		$out  = $this->text($name, $value, 16, 34, array_merge($attribs,array('no_error'=>true)));
		$out .= $this->depends->html->image('icons/calendar.gif', array('id'=>"btn_$name",'align'=>'top'));
		return $out.$err;
	}

	/**
	 * Generate a color-selection form element
	 *
	 * @param string $name
	 * @param string $value
	 * @param array $attribs Additional HTML attributes
	 * @return string
	 */
	function color($name, $value, $attribs=array())
	{
		$err = $this->_error($name, $attribs);
		$div = '<div id="color_picker"></div>';
		$js = <<<EOT
var farb = $.farbtastic('#color_picker');
$('.colorwell').each(function(){
	farb.linkTo(this);
});
EOT;
		$value = $value ? $value : '#ffffff';
		$this->depends->html->css_load('colorsel', 'colorsel');
		$this->depends->html->js_load('jq_colorsel', 'jq/jquery.colorsel');
		$this->depends->html->js_run('jq_colorsel', $js);
		$out  = $div;
		$out .= $this->text($name, $value, 8, 7, array_merge($attribs,array('class'=>'colorwell')));
		$out .= $this->imagebutton("btn_$name", $this->depends->html->url('/img/icons/color_wheel.gif',true),array('onClick'=>'colorsel(this,\''.$name.'\');return false;'));
		return $out.$err;
	}

	/**
	 * Generate a textarea form element
	 *
	 * @param string $name
	 * @param string $value
	 * @param int $rows
	 * @param int $cols
	 * @param array $attribs Additional HTML attributes
	 * @return string
	 */
	function textarea($name, $value='', $rows=10, $cols=50, $attribs=array())
	{
		list($name,$value) = $this->_escape($name,$value);
		$err  = $this->_error($name, $attribs);
		$id   = $this->dom_id($name);
		$out  = '<textarea name="'.$name.'" rows="'.$rows.'" cols="'.$cols.'"';
		$out .= $this->depends->html->_attribs(array_merge(array('id'=>$id), $attribs));
		$out .= '>';
		$out .= $value . '</textarea>';
		return $out.$err;
	}

	/**
	 * Generate a rich WYSIWYG textarea form element
	 *
	 * @param string $name
	 * @param string $value
	 * @param int $rows Number of rows for the textarea element
	 * @param int $cols Number of columns for the textarea element
	 * @param string $mceinit Initialization JS code for the MCE control; if blank, the
	 *                        default will be used.  If an array, then the settings
	 *                        in the array will override those in the default.  If it
	 *                        is a non-empty string, then that string will be used
	 *                        instead of the default.  In this case, make sure the
	 *                        string is a valid Javascript object.
	 * @param array $attribs Additional HTML attributes
	 * @return string
	 */
	function htmlarea($name, $value='', $rows=15, $cols=100, $mceinit='', $attribs=array())
	{
		$js_gz = <<<EOT
tinyMCE_GZ.init({
	plugins : "safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",
	themes : "simple,advanced",
	languages : "en",
	disk_cache : true,
	debug : false
});
EOT;

		$default_cfg = array(
			'mode'            => "textareas",
			'theme'           => "advanced",
			'editor_selector' => "mceEditor",
			'plugins'         => "safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",

			// Theme options
			'theme_advanced_buttons1' => "save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect",
			'theme_advanced_buttons2' => "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
			'theme_advanced_buttons3' => "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
			//'theme_advanced_buttons4' => "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak",
			'theme_advanced_toolbar_location'   => "top",
			'theme_advanced_toolbar_align'      => "left",
			'theme_advanced_statusbar_location' => "bottom",
			'theme_advanced_resizing'           => true,

			// Options
			'file_browser_callback'   => "tinyBrowser",
			'convert_urls'            => false,
			'relative_urls'           => false,
			'paste_use_dialog'        => false,
			'theme_advanced_resizing' => true,
			'theme_advanced_resize_horizontal' => true,
			'apply_source_formatting' => true,
			'force_br_newlines'       => false,
			'force_p_newlines'        => false,	

			'content_css' => url('/css.php?c=htmlarea')
		);

		// if no $mceinit specified, fall back to a default one set by htmlarea_preload()
		if($mceinit === '' && isset($this->_mce_init)) $mceinit = $this->_mce_init;

		// if $mceinit['content_css'] is an array of files, then convert the
		// local ones to go through css.php
		if(is_array($mceinit) && is_array($mceinit['content_css'])) {
			$css_arr = array();
			foreach($mceinit['content_css'] as $c) {
				$css_arr[] = (!preg_match('|^http[s]?://|', $c) && !preg_match('|^/|', $c)) ? url("/css.php?c=$c") : $c;
			}
			$mceinit['content_css'] = implode(',', $css_arr);
		}

		if(is_array($mceinit)) {
			// add to default settings
			$init_cfg = json_encode(array_merge($default_cfg, $mceinit));
		} else if(!empty($mceinit)) {
			// overwrite default settings
			$init_cfg = $mceinit;
		} else {
			// use default
			$init_cfg = json_encode($default_cfg);
		}

		$mceinit = <<<EOT
tinyMCE.init($init_cfg);

function mce_toggle(id) {
	if(!tinyMCE.get(id)) {
		tinyMCE.execCommand('mceAddControl', false, id);
	} else {
		tinyMCE.execCommand('mceRemoveControl', false, id);
	}
}
EOT;

		if($this->web->ajax) {
			$js_ajax = "tinyMCE.execCommand(\"mceAddControl\", false, \"$name\");";
			$this->depends->html->js_run('+tinymce_'.$name, $js_ajax);
		} else {
			$this->depends->html->head('tinymce_gz_js', $this->depends->html->js('tinymce/tiny_mce_gzip'));
			$this->depends->html->head('tinymce_browser_js', '<script type="text/javascript" src="'.url('/js/tinymce/plugins/tinybrowser/tb_tinymce.js.php').'"></script>');
			$this->depends->html->head('tinymce_gzinit_js', '<script type="text/javascript">'.$js_gz.'</script>');
			//$this->depends->html->js_load('tinymce', 'tinymce/tiny_mce');
			$this->depends->html->js_run('+tinymce', $mceinit);
		}

		// this session key must be set in order to use the file/image
		// manager plugin.
		$_SESSION['TINYMCE'] = true;

		if($name == '_htmlarea_preload') return '';
		return $this->textarea($name, $value, 15, 100, array('class'=>'mceEditor')+$attribs);
	}

	/**
	 * Preload libraries for a htmlarea widget.
	 * This function is necessary if you're going to be loading an htmlarea widget via
	 * AJAX, as TinyMCE has some pecularities when loading dynamically (eg, through a
	 * .getScript() call).  To work around this, use htmlarea_preload() in the page that
	 * will be loading an htmlarea widget via AJAX.
	 *
	 * @param array $css An array of CSS files that should be loaded into the editor
	 *                   widget.
	 * @param string $mceinit Initialization JS code for the MCE control; if blank, the
	 *                        default will be used.  If an array, then the settings
	 *                        in the array will override those in the default.  If it
	 *                        is a non-empty string, then that string will be used
	 *                        instead of the default.  In this case, make sure the
	 *                        string is a valid Javascript object.
	 */
	function htmlarea_preload($mceinit='')
	{
		$this->_mce_init = $mceinit;
		return $this->htmlarea('_htmlarea_preload', '', 15, 100, $mceinit, array('style'=>'display:none'));
	}

	/**
	 * Generate a checkbox form element
	 *
	 * @param string $name
	 * @param string $value
	 * @param string $label
	 * @param bool $checked
	 * @param array $attribs Additional HTML attributes
	 * @return string
	 */
	function checkbox($name, $value='', $label='', $checked=false, $attribs=array())
	{
		list($name,$value) = $this->_escape($name,$value);
		$err = $this->_error($name, $attribs);
		$id  = $this->dom_id($name);
		$out = '<input type="checkbox" name="'.$name.'" value="'.$value.'"';
		if($checked) $out .= ' checked="checked"';
		$out .= $this->depends->html->_attribs(array_merge(array('id'=>$id, 'style'=>'border:none'),$attribs));
		$out .= ' /> '.$label;
		return $out.$err;
	}

	/**
	 * Generate a radio form element
	 *
	 * @param string $name
	 * @param string $value
	 * @param array $options
	 * @param string $sep Separator to use between options (default is a BR tag)
	 * @param array $attribs Additional HTML attributes
	 * @return string
	 */
	function radio($name, $value='', $options=array(), $sep='<br />', $attribs=array())
	{
		// backwards compatibility
		if(is_array($sep)) {
			$attribs = $sep;
			$sep = '<br />';
		}

		list($name) = $this->_escape($name);
		$err = $this->_error($name, $attribs);
		$id  = $this->dom_id($name);
		$out = '';
		foreach($options as $val=>$label) {
			$out .= '<input type="radio" name="'.$name.'" value="'.$val.'"';
			if($val == $value) $out .= ' checked="checked"';
			$out .= $this->depends->html->_attribs(array_merge(array('id'=>$id, 'style'=>'border:none'),$attribs));
			$out .= ' /> '.$label.$sep;
		}
		return $out.$err;
	}

	/**
	 * Generate a select/dropdown form element
	 *
	 * @param string $name
	 * @param string $value
	 * @param array $options
	 * @param int $size
	 * @param bool $multiple
	 * @param array $attribs Additional HTML attributes
	 * @return string
	 */
	function select($name, $value='', $options=array(), $size='', $multiple=false, $attribs=array())
	{
		list($name) = $this->_escape($name);
		$err = $this->_error($name, $attribs);
		$id  = $this->dom_id($name);
		$fname = $name;
		if($multiple && substr($fname, -2) != '[]') $fname .= '[]';
		$out = '<select name="'.$fname.'"';
		if($size)     $out .= ' size="'.$size.'"';
		if($multiple) $out .= ' multiple="multiple"';
		$out .= $this->depends->html->_attribs(array_merge(array('id'=>$id), $attribs));
		$out .= '>';

		if(!$multiple && is_array($value)) $value = array_shift($value);

		$selected = false;
		foreach($options as $val=>$label) {
			if(is_array($label)) {
				$out .= '<optgroup label="'.$val.'">';
				foreach($label as $subval=>$sublbl) {
					$out .= $this->_select_option($subval, $sublbl, $value, $multiple, $selected);
				}
				$out .= '</optgroup>';
			} else {
				$out .= $this->_select_option($val, $label, $value, $multiple, $selected);
			}
		}
		$out .= '</select>';
		return $out.$err;
	}
	function _select_option($optval, $optlbl, $value, $mult, &$sel) {
		$ret = '<option value="'.$optval.'"';
		if(is_array($value) && $mult) {
			// multiple selections
			if(in_array($optval, $value)) $ret .= ' selected';
		} else {
			// single selection
			if($optval == $value && !$mult && !$sel) {
				$ret .= ' selected';
				$sel = true;
			}
		}
		$ret .= '>'.$optlbl.'</option>';
		return $ret;
	}

	/**
	 * Generate a multi-select form element that is more intuitive than
	 * the standard select with CTRL-click.  Can also be used for single
	 * selects.
	 *
	 * @param string $name
	 * @param mixed $value The current selection(s)
	 * @param array $options A hash of option=>value tuples
	 * @param boolean $show_selects Show select-all and select-none icons
	 * @param boolean $multiple Allow multiple selections
	 * @param array $attribs Additional HTML attributes
	 * @return string
	 */
	function multiselect($name, $value, $options, $show_selects=false, $multiple=true, $attribs=array())
	{
		$err = $this->_error($name, $attribs);
		$id  = $this->dom_id($name);
		if(!is_array($value)) $value = array($value=>$value);
		list($name) = $this->_escape($name);
		if(substr($name, -2) != '[]') $name .= '[]';

		$js = <<<EOT
$('div.multiselect p.option').click(function(){
	var chk = $(this).find('input[type=checkbox]:first');
	if(chk.attr('checked')) {
		chk.attr('checked',false);
		$(this).removeClass("selected");
	} else {
		var div = chk.parent().parent();
		if(div.attr('rel') != 'multiple') {
			div.find('input[type=checkbox]').attr('checked',false).parent().removeClass("selected");
		}
		chk.attr('checked',true);
		$(this).addClass("selected");
	}
});
$('div.multiselect p.option input[type=checkbox]').click(function() {
	$(this).attr('checked', !$(this).attr('checked'));
});
EOT;
		$out  = '<div class="multiselect_container"';
		$out .= $this->depends->html->_attribs($attribs);
		$out .= '>';
		$out .= '<div class="multiselect" id="'.$id.'"';
		if($multiple) $out .= ' rel="multiple"';
		$out .= '>';
		foreach($options as $val=>$label) {
			if(in_array($val, $value)) {
				$out .= '<p class="option selected"><input type="checkbox" name="'.$name.'" value="'.$val.'" checked="checked" /> '.$label.'</p>';
			} else {
				$out .= '<p class="option"><input type="checkbox" class="checkbox" name="'.$name.'" value="'.$val.'" /> '.$label.'</p>';
			}
		}
		$out .= '</div>';
		if($show_selects) {
			$out .= '<div class="multiselect_control" id="'.$id.'_control">';
			$out .= $this->depends->html->image('icons/tick.gif', 'selall', array('title'=>__('Select All')));
			$out .= $this->depends->html->image('icons/cross.gif', 'selnone', array('title'=>__('Select None')));
			$out .= '</div>';
			$this->depends->html->css_load('', 'tooltip');
			$this->depends->html->js_load('jq_tooltip', 'jq/jquery.tooltip');
			$this->depends->html->js_run('jq_tooltip_multiselect', '$(\'div.multiselect_control img\').Tooltip({showURL:false,extraClass:\'action\'});');
			$js .= <<<EOT
$('div.multiselect_control img[alt=selall]').click(function() {
	$(this).parent().prev('div').find('input[type=checkbox]').each(function(){
		$(this).attr('checked',true);
		$(this).parent().addClass("selected");
	});
});
$('div.multiselect_control img[alt=selnone]').click(function() {
	$(this).parent().prev('div').find('input[type=checkbox]').each(function(){
		$(this).attr('checked',false);
		$(this).parent().removeClass("selected");
	});
});
EOT;
		}
		$out .= '</div>';
		$this->depends->html->js_run('multiselect', $js);
		return $out.$err;
	}

	/**
	 * Generate a multi-column form.
	 *
	 * @param array $params General form parameters
	 * @param array $data Values to prefill form elements with
	 * @param array $errors Errors to attach to elements containing invalid data
	 *
	 * Form Parameters:
	 *   - form_name ()   :: form name
	 *   - form_id ()     :: form id
	 *   - class (form)   :: CSS class to use for outer <div>
	 *   - action ()
	 *   - method (POST)
	 *   - enctype ()
	 *   - data_id ()     :: PK of data element being edited
	 *   - submit         :: label on submit button; can be a 2-element array: ('create','update')
	 *   - submit_msg     :: change button message to this when clicked 
	 *   - submit_pos     :: "left" or "right" (default is "right")
	 *   - submit_html    :: if set, use this for the submit button code instead of generating it
	 *   - options array
	 *     - noopen           :: don't include opening div/form tags
	 *     - noclose          :: don't include closing div/form tags or a Submit button
	 *     - nosubmit         :: don't include a Submit button
	 *     - nopk             :: don't include the name="id" hidden element
	 *     - toplabel         :: put labels above elements instead of beside them
	 *     - numcols          :: number of columns to use for form elements (1)
	 *     - hide_misc_errors :: don't show errors that aren't bound to an element in the form definition
	 *     - spinner          :: image to show beside submit button when clicked
	 *   - layout array
	 *     - <column name> :: array(colspan<int>, label_width<int|"auto">, width<int>)
	 *   - elements array
	 *     - <name> => array
	 *       - prompt :: label to display beside form element
	 *       - type   :: element type (see the _show_element() method for types)
	 *       - value  :: value of element
	 *       - error  :: error message associated with element
	 *       - help   :: context-specific help message
	 *       - extra  :: extra content (HTML) to be added beside the element
	 *       - type-specific parameters (size, maxlength, rows, cols, etc)
	 *       - attribs array :: additional html attributes
	 */
	function build_form($params, $data=array(), $errors=array())
	{
		$guid = ++$this->guid;

		$class     = $this->_getparam($params, 'class', 'form');
		$method    = $this->_getparam($params, 'method', 'post');
		$submit    = $this->_getparam($params, 'submit', __('Save Changes'));
		$options   = $this->_getparam($params, 'options', array());
		$data_id   = $this->_getparam($params, 'data_id', 0);
		$layout    = $this->_getparam($params, 'layout', array());
		$elements  = $this->_getparam($params, 'elements', array());
		$form_id   = $this->_getparam($params, 'form_id', 'form'.$guid);
		$form_name = $this->_getparam($params, 'form_name', 'form'.$guid);
		$action    = $this->_getparam($params, 'action', '');

		// this is used to pass the data ID back to all form element generators; it
		// will be unset once build_form() is done so it's not accidentally used
		// for form element calls that originate outside of build_form()
		$this->data_id = $data_id;

		if(!$options['noopen']) {
			// <form>
			$form_attribs = array('id'=>$form_id);
			if(isset($params['enctype'])) $form_attribs['enctype'] = $params['enctype'];
			$out .= $this->open_form($form_name, $action, $method, $errors, $form_attribs);

			// <div>
			$out .= '<div';
			if($class) $out .= ' class="'.$class.'"';
			$out .= ">\n";
		}

		$numcols  = $this->_getparam($options, 'numcols', 1);
		// if numcols is one, then we might have to massage the elements array into
		// the proper multi-column-friendly format
		$first_key = key($elements);
		$first_val = current($elements);
		if(isset($first_val['type']) && !is_array($first_val['type'])) {
			$elements = array('0' => $elements);
		}
		// if we're editing a record (as opposed to creating one), then load in
		// the PK of the data element
		if($data_id && !$options['nopk']) {
			$elements[$first_key]['id'] = array('type'=>'hidden','value'=>$data_id);
		}
		// check for a happy layout definition
		assert_type($layout, 'array');
		foreach($elements as $col=>$subform) {
			assert_type($layout[$col], 'array');
			if(!isset($layout[$col]['colspan'])) $layout[$col]['colspan'] = 1;
			if(!isset($layout[$col]['label_width'])) $layout[$col]['label_width'] = 'auto';
		}
		// set element layout
		$elem_layout = $options['toplabel'] ? 'toplabel' : 'leftlabel';

		// Prefill form values and errors
		foreach($elements as $column=>$elems) {
			foreach($elems as $name=>$elem) {
				if(isset($elem['value']) && $elem['type'] == 'checkbox') {
					if($data[$name] == $elem['value']) {
						$elements[$column][$name]['checked'] = true;
					}
				} else if(!isset($elem['value']) && isset($data[$name])) {
					// don't prefill a date field if the value is '0000-00-00'
					if(!($elements[$column][$name]['type'] == 'date' && $data[$name] == '0000-00-00')) {
						$elements[$column][$name]['value'] = $data[$name];
					}
				}
				unset($errors[$name]);
			}
		}

		// If there are any errors not bound to a form element, display them at
		// the top
		if(count($errors) && !$options['hide_misc_errors']) {
			$out .= $this->error_box($errors);
		}

		// form elements (hidden)
		foreach($elements as $column=>$subform) {
			foreach($subform as $name=>$elem) {
				if($elem['type'] != 'hidden') continue;
				$out .= $this->hidden($name, $elem['value']);
			}
		}

		$c_loc = 0;
		$clearnext = true;
		foreach($elements as $col_id=>$subform) {
			// the <br> fixes some float issues in IE
			if($clearnext && $c_loc > 0) $out .= '<br class="clearfix" />';
			$c_loc += $layout[$col_id]['colspan'];
			if(isset($layout[$col_id]['width'])) {
				$width = $layout[$col_id]['width'];
				if(is_numeric($width)) $width .= 'px';
			} else {
				$width = floor(($layout[$col_id]['colspan'] / $numcols) * 100).'%';
			}
			$cls = $c_loc % $numcols == 0 ? ' dynsize' : '';
			if($layout[$col_id]['colspan']) {
				$cls .= " {$layout[$col_id]['class']}";
			}
			$out .= '<div class="subform'.$cls.'" style="width:'.$width.';';
			if($clearnext) {
				$out .= 'clear:left;';
				$clearnext = false;
			} else if($c_loc % $numcols == 0) {
				// IE6 workaround: use clear:both if $numcols==1
				$out .= $numcols == 1 ? 'clear:both' : 'clear:right;';
				$clearnext = true;
			}
			$out .= '">';

			// calculate optimal width of the label divs
			if(!isset($layout[$col_id]['label_width'])) $layout[$col_id]['label_width'] = 'auto';
			$lblwidth = $layout[$col_id]['label_width'];
			if($elem_layout == 'leftlabel' && $lblwidth == 'auto') {
				if($layout[$col_id]['label_width'] == 'auto') {
					// find the longest label to determine width of the label divs
					$llength = 0;
					foreach($subform as $name=>$elem) {
						if($elem['type'] == 'label') continue;
						if(!isset($elem['prompt'])) continue;
						$s = mb_strlen(strip_tags($elem['prompt']));
						if($s > $llength) $llength = $s;
					}
					$lblwidth = $llength.'em';
				}
			}
			if(is_numeric(substr($lblwidth, -1, 1))) $lblwidth .= 'px';

			foreach($subform as $name=>$elem) {
				if($elem['type'] == 'hidden') continue;

				$subvars = array('help'=>'', 'label'=>'', 'element'=>'', 'error'=>'', 'lblwidth'=>$lblwidth);
				$attribs = $this->_getparam($elem, 'attribs', array());
				// set the "error" class if validation failed
				if(isset($elem['error'])) $attribs['class'] = 'error';
				// set the tabindex relative to the column we're in
				$attribs['tabindex'] = $c_loc;

				// tooltip
				if(isset($elem['help'])) {
					$subvars['help'] = $this->tooltip($elem['help']);
					if($elem_layout == 'toplabel') $subvars['help'] .= ' ';
				} else if($elem_layout == 'leftlabel') {
					$subvars['help'] = '&nbsp;';
				}

				// label, element, error
				if(empty($elem['prompt'])) $elem['prompt'] = '&nbsp;';
				$subvars['label'] = '<label for="'.$name.'">'.$elem['prompt'].'</label>';
				$subvars['element'] = $this->_show_element($name, $elem, $attribs);
				if(isset($elem['error'])) {
					$subvars['error'] = '<br /><p class="error">'.$elem['error'].'</p>';
				}

				if($elem['type'] == 'label') {
					$el = $this->element_layouts['labelonly'];
				} else if($elem['nolabel'] == true) {
					$el = $this->element_layouts['elemonly'];
				} else {
					$el = $this->element_layouts[$elem_layout];
				}

				// sub in vars and output this element
				$out .= str_replace(
					array('{{HELP}}','{{LABEL}}','{{ELEMENT}}','{{ERROR}}','{{LBLWIDTH}}'),
					$subvars, $el);
			}
			$out .= "</div>\n";
		}

		if(!$options['noclose'] && !$options['nosubmit']) {
			$c = array_shift(explode(' ', $class));

			if(empty($params['submit_pos']) || $params['submit_pos'] == 'right') {
				// TODO: This is ugly, fix it.
				$js = <<<EOT
var elem_width = 0;
try { var parent = $('#$form_id div.$c').position(); } catch(err) { return; }
$('#{$form_id} div.dynsize').find('div.form_element').each(function(){
	var pos = $(this).position().left + $(this).width() - parent.left;
	if(pos > elem_width) elem_width = pos;
});
$('#{$form_id} div.form_submit').css('width', elem_width+'px');
EOT;
				$this->depends->html->js_run('', $js);
			}

			$out .= '<div class="form_submit">';

			// If submit_html was used, output the contents directly.  Otherwise
			// build a submit button ourselves.
			if(isset($params['submit_html'])) {
				$out .= $params['submit_html'];
			} else {
				$opts = array();
				if(isset($options['spinner'])) {
					$opts['onClick'] .= "$(this).prev('img.spinner').css('display','inline');";
					$out .= $this->depends->html->image($options['spinner'], array('class'=>'spinner','align'=>'top','style'=>'display:none'));
				}
				if(isset($params['submit_msg'])) {
					$opts['onClick'] .= "this.value='{$params['submit_msg']}';this.disabled=true;this.form.submit();return false;";
				}
				if(is_array($submit)) {
					if($data_id) {
						$out .= $this->submit('submit_btn'.$guid, $submit[1], $opts);
					} else {
						$out .= $this->submit('submit_btn'.$guid, $submit[0], $opts);
					}
				} else {
					$out .= $this->submit('submit_btn'.$guid, $submit, $opts);
				}
			}

			$out .= "</div>\n";
		}

		if(!$options['noclose']) {
			$out .= "</div>\n";
			$out .= $this->close_form();
		}

		$this->data_id = 0;
		return $out;
	}

	/**
	 * Generate a tabbed form.
	 *
	 * @param array $params General parameters
	 * @param array $tabs Array of arrays containing tab labels and form definitions
	 * @param array $data Values to prefill form elements with
	 * @param array $errors Errors to attach to elements containing invalid data
	 *
	 * General Parameters:
	 *   - form_name ()   :: form name
	 *   - form_id ()     :: form id
	 *   - class (form)   :: CSS class to use for outer <div>
	 *   - action ()
	 *   - method (POST)
	 *   - enctype ()
	 *   - data_id ()     :: PK of data element being edited
	 *   - submit         :: label on submit button; can be a 2-element array: ('create','update')
	 *   - spinner        :: image to show beside submit button when clicked
	 *
	 * Form Definitions:  See build_form() $params array.
	 *
	 * Example:
	 @code
	   echo $form->build_tabbed_form(
	     array('data_id'=>$data['id'], 'submit'=>'Save'),
	     array(
	       'tab1' => array('label'=>'First Tab', 'form'=>$f1),
	       'tab2' => array('label'=>'Second Tab', 'form'=>$f2, 'active'=>true),
	       'tab3' => array('label'=>'Third Tab', 'content'=>'No form, just some content')
	     ), $data, $errors
	   );
	 @endcode
	 *
	 */
	function build_tabbed_form($params, $tabs, $data=array(), $errors=array())
	{
		$guid = ++$this->guid;

		$class      = $this->_getparam($params, 'class', 'form');
		$method     = $this->_getparam($params, 'method', 'post');
		$submit     = $this->_getparam($params, 'submit', __('Save Changes'));
		$submit_msg = $this->_getparam($params, 'submit_msg', '');
		$form_id    = $this->_getparam($params, 'form_id', 'form'.$guid);
		$form_name  = $this->_getparam($params, 'form_name', 'form'.$guid);
		$action     = $this->_getparam($params, 'action', '');
		$data_id    = $this->_getparam($params, 'data_id', 0);
		$spinner    = $this->_getparam($params, 'spinner', '');

		$this->depends->html->css_load('tabs');
		$this->depends->html->js_load('jq/jquery.tabs');

		$out = '';

		// <form>
		$form_attribs = array('id'=>$form_id);
		if(isset($params['enctype'])) $form_attribs['enctype'] = $params['enctype'];
		$out .= $this->open_form($form_name, $action, $method, $errors, $form_attribs);
		if($data_id) {
			$out .= $this->hidden('id', $data_id);
		}

		$out .= '<div id="form_container'.$guid.'">';
		$out .= '<ul>';
		foreach($tabs as $tabid=>$tab) {
			$active = $tab['active'] ? ' class="tabs-selected"' : '';
			$out .= '<li'.$active.'><a href="#'.$tabid.'">'.$tab['label'].'</a></li>';
		}
		$out .= '</ul>';
		foreach($tabs as $tabid=>$tab) {
			$content = '';
			if(isset($tab['content'])) {
				$content = $tab['content'];
			} else if(isset($tab['form'])) {
				$f = $tab['form'];
				if(!is_array($f['options'])) $f['options'] = array();
				$f['options']['noopen']  = true;
				$f['options']['noclose'] = true;
				$f['options']['nopk']    = true;
				$f['data_id'] = $data_id;
				$content = $this->build_form($f, $data, $errors);
			}

			$out .= '<div id="'.$tabid.'" style="background:#fff;">';
			$out .= '<div class="'.$class.' clearfix">';
			$out .= $content;
			$out .= '</div></div>';
		}
		$out .= '</div>';

		// Submit Button
		$opts = array('style'=>'float:right');
		if($spinner)    $opts['onClick'] .= "$(this).next('img.spinner').css('display','inline');";
		if($submit_msg) $opts['onClick'] .= "this.value='$submit_msg';this.disabled=true;this.form.submit();return false;";
		if(is_array($submit)) {
			if($data_id) {
				$out .= $this->submit('submit_btn'.$guid, $submit[1], $opts);
			} else {
				$out .= $this->submit('submit_btn'.$guid, $submit[0], $opts);
			}
		} else {
			$out .= $this->submit('submit_btn'.$guid, $submit, $opts);
		}
		if($spinner) $out .= $this->depends->html->image($spinner, array('class'=>'spinner','align'=>'top','style'=>'float:right;display:none'));
		$out .= $this->close_form();

		$this->depends->html->js_run('', "$('#form_container{$guid}').tabs();");

		return $out;
	}

	function _show_element($name, $elem, $attribs)
	{
		$out = '';
		switch($elem['type']) {
		/* SPECIAL/CUSTOM FORM ELEMENTS
			Separator: No output, just a blank line for some vertical spacing.
			Label:     Output the prompt only, no widget or text values.
			Custom:    Output the content in the 'data' field exactly as-is,
			           useful for custom widgets.
			Value:     Output the prompt and value normally, the value can be
			           pulled directly from the data array as normal, but it
								 won't be used as a widget value, just a simple text element.
		 */
			case 'separator':
			case 'label':
				$out .= '<br />';
				break;
			case 'custom':
				$out .= $this->_getparam($elem, 'data', '');
				break;
			case 'value':
				$v = $this->_getparam($elem, 'value', '');
				if(empty($v)) $v = '&nbsp;';
				$out .= "<strong>$v</strong>";
				break;
		// STANDARD FORM ELEMENTS
			case 'text':
				$out .= $this->text($name,
					$this->_getparam($elem, 'value', ''),
					$this->_getparam($elem, 'size', 30),
					$this->_getparam($elem, 'maxlength', 255),
					$attribs);
				break;
			case 'password':
				$out .= $this->password($name,
					$this->_getparam($elem, 'value', ''),
					$this->_getparam($elem, 'size', 30),
					$this->_getparam($elem, 'maxlength', 255),
					$attribs);
				break;
			case 'file':
				$out .= $this->file($name,
					$this->_getparam($elem, 'value', ''),
					$this->_getparam($elem, 'size', '30'),
					$this->_getparam($elem, 'preview_url', ''),
					$this->_getparam($elem, 'remove_url', ''),
					$attribs);
				break;
			case 'image':
				$out .= $this->image($name,
					$this->_getparam($elem, 'value', ''),
					$this->_getparam($elem, 'size', '30'),
					$this->_getparam($elem, 'preview_url', ''),
					$this->_getparam($elem, 'remove_url', ''),
					$attribs);
				break;
			case 'date':
				$out .= $this->date($name,
					$this->_getparam($elem, 'value', ''),
					$this->_getparam($elem, 'format', '%Y-%m-%d'),
					$attribs);
				break;
			case 'datetime':
				$out .= $this->datetime($name,
					$this->_getparam($elem, 'value', ''),
					$this->_getparam($elem, 'format', '%Y-%m-%d %k:%M'),
					$attribs);
				break;
			case 'color':
				$out .= $this->color($name,
					$this->_getparam($elem, 'value', ''),
					$attribs);
				break;
			case 'textarea':
				$out .= $this->textarea($name,
					$this->_getparam($elem, 'value', ''),
					$this->_getparam($elem, 'rows', 10),
					$this->_getparam($elem, 'cols', 50),
					$attribs);
				break;
			case 'htmlarea':
				$out .= $this->htmlarea($name,
					$this->_getparam($elem, 'value', ''),
					$this->_getparam($elem, 'rows', 15),
					$this->_getparam($elem, 'cols', 100),
					$this->_getparam($elem, 'mceinit', ''),
					$attribs);
				break;
			case 'checkbox':
				$out .= $this->checkbox($name,
					$this->_getparam($elem, 'value', ''),
					$this->_getparam($elem, 'label', ''),
					$this->_getparam($elem, 'checked', false),
					$attribs);
				break;
			case 'radio':
				$out .= $this->radio($name,
					$this->_getparam($elem, 'value', ''),
					$this->_getparam($elem, 'options', array()),
					$attribs);
				break;
			case 'select':
				$out .= $this->select($name,
					$this->_getparam($elem, 'value', ''),
					$this->_getparam($elem, 'options', array()),
					$this->_getparam($elem, 'size', ''),
					$this->_getparam($elem, 'multiple', false),
					$attribs);
				break;
			case 'multiselect':
				$out .= $this->multiselect($name,
					$this->_getparam($elem, 'value', ''),
					$this->_getparam($elem, 'options', array()),
					$this->_getparam($elem, 'show_selects', false),
					$this->_getparam($elem, 'multiple', true),
					$attribs);
				break;
		}
		if($elem['extra']) {
			$out .= " {$elem['extra']}";
		}
		return $out;
	}

	/**
	 * Build a DOM-friendly ID.  Some characters (like underscores) are
	 * not valid and must be replaced/removed.
	 *
	 * @param string $name The name of the element.
	 * @return string The DOM ID
	 */
	function dom_id($name)
	{
		// XXX: this breaks backwards-compatibility for a lot of JavaScript
		// stuff, so it's disabled for now.
		$id  = str_replace(array('[',']',' '), array('','','-'), $name);
		if($this->form_name) $id = $this->form_name."_$id";
		return $id;

		$id  = str_replace(array('_','[',']',' '), array('-','','','-'), $name);
		if($this->form_name) $id = $this->form_name."-$id";
		return $id;
	}

	function _getparam($params, $name, $default)
	{
		if(isset($params[$name])) {
			return $params[$name];
		}
		return $default;
	}

	function _escape($var)
	{
		$ret = array();
		foreach(func_get_args() as $var) {
			$ret[] = htmlspecialchars($var);
		}
		return $ret;
	}

	function _error($name, &$attribs)
	{
		$err = '';
		if($attribs['no_error']) {
			unset($attribs['no_error']);
			return $err;
		}
		if(isset($this->errors[$name])) {
			$err = '<br /><p class="error">'.$this->errors[$name].'</p>';
			$attribs['class'] .= (empty($attribs['class']) ? 'error' : ' error');
		}
		return $err;
	}
}

?>
