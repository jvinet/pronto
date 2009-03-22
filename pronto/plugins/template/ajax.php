<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Template plugin for AJAX-y widgets/controls.
 *
 **/
class tpAJAX extends Plugin
{
	var $guid;

	/**
	 * Constructor
	 */
	function tpAJAX() {
		$this->Plugin();
		$this->depend('html');

		$this->guid = 1;
	}

	/**
	 * Create a modal dialog window and bind it to a trigger element/event.
	 *
	 * Example:
	 @code
	 <div id="mydlg" title="My Dialog">Hello World!</div>
	 <a href="#" id="clickme">Click Me</a>
	 <?php $ajax->dialog('mydlg', '#clickme') ?>
	 @endcode
	 *
	 * @param string $dlg_id   The DOM ID of the content that will be converted
	 *                         into a dialog window.
	 * @param mixed $target    The jQuery selector of the DOM element used to
	 *                         trigger the dialog window, or an array of the
	 *                         event and selector, eg, array('click','a.trig').
	 *                         This field can be blank if you want to handle
	 *                         the event yourself.
	 * @param boolean $overlay Grey out the background before showing the
	 *                         new window.
	 * @param boolean $modal   Make the window modal, so the user can't
	 *                         interface with other parts of the window until
	 *                         the dialog is closed.
	 * @param array $opts Additional options to pass to jqModal.  Options should
	 *                    be contained in the values only (don't use an
	 *                    associative array) and separated with colons, just as
	 *                    you would build them in Javascript.  Options can be
	 *                    found on the jqModal website:
	 *                    http://dev.iceburg.net/jquery/jqModal/
	 */
	function dialog($dlg_id, $target, $buttons=array(), $overlay=false, $modal=false, $opts=array())
	{
		// IE6 fix: if the dialog is inside a postion:relative element, then the
		// overlay will appear over the dialog box.
		$opts[] = 'toTop:true';
		if(!$overlay) $opts[] = 'overlay:0';
		if($modal)    $opts[] = 'modal:true';
		if(empty($opts)) {
			$opts = '';
		} else {
			$opts = '{' . implode(',', $opts) . '}';
		}

		list($btn_html,$btn_js) = $this->buttons($buttons, 'dialog', true);
		$btn_html = str_replace("'", "\\'", $btn_html);

		// build HTML for titlebar and resize icon
		$tb = '<div class="dialog-tb">'.$this->depends->html->image('icons/close_x.gif',array('class'=>'dialog-close jqmClose')).'</div>';
		$tb = str_replace("'", "\\'", $tb);
		$rz = $this->depends->html->image('icons/resize.gif', array('class'=>'dialog-resize'));
		$rz = str_replace("'", "\\'", $rz);

		// fiddle with the provided div to wrap it with the necessary dialog elements
		$js  = "$('#$dlg_id').wrap('<div></div>');";
		$js .= "$('#$dlg_id').before('$tb').after('$rz');";
		$js .= "$('#$dlg_id').parent().attr('style', $('#$dlg_id').attr('style'));"; // move styling to parent
		$js .= "$('#$dlg_id').parent().attr('class', $('#$dlg_id').attr('class'));"; // move class to parent
		$js .= "$('#$dlg_id').parent().addClass('dialog');";
		$js .= "$('#$dlg_id').removeAttr('style').removeAttr('class');";
		$js .= "$('#$dlg_id').addClass('dialog-content');";
		$js .= "$('#$dlg_id').attr('id', '').parent().attr('id', '$dlg_id');"; // move ID up to parent
		$js .= "$('#$dlg_id').find('.dialog-tb').append($('#$dlg_id').find('.dialog-content').attr('title'));";
		$js .= "$('#$dlg_id').find('.dialog-content').append('$btn_html');";
		$js .= $btn_js;

		$this->depends->html->js_run('jq_dlgsetup_'.$dlg_id, $js);

		$js  = "$('#{$dlg_id}').jqm({$opts}).jqDrag('.dialog-tb').jqResize('img.dialog-resize');";
		if($target) {
			$event = 'click';
			if(is_array($target)) {
				$event  = $target[0];
				$target = $target[1];
			}
			$js .= "$('{$target}').{$event}(function(){";
			$js .= "$(this).blur();";
			$js .= "$('#$dlg_id').jqmShowCenter(); return false; });";
		}

		$this->depends->html->css_load('dialog');
		$this->depends->html->js_load('jq/jquery.modal');
		$this->depends->html->js_run('jq_dlg_'.$dlg_id, $js);
	}

	/**
	 * Connect an existing div to one or more targets that, when clicked, will make
	 * the div visible.  Also include zero or more buttons along the bottom of the
	 * popup div.
	 *
	 * @param $div_id string The DOM ID of the div element serving as the popup
	 * @param $targets array jQuery selector(s) for target elements (eg, "a.new-row" or "#btn")
	 * @param $buttons array Button configuration (see tpAJAX::buttons() for example)
	 * @return none
	 */
	function popup_bind($div_id, $targets=array(), $buttons=array())
	{
		list($btn_html,$btn_js) = $this->buttons($buttons, 'popup', true);
		$js = "$('#$div_id').append('<div>".str_replace("'","\\'",$btn_html)."</div>');";
		$js .= $btn_js;

		if(!is_array($targets)) $targets = array($targets);
		foreach($targets as $targ) {
			$js .= "$('$targ').click(function(e){ ";
			// generate a random DOM ID if one isn't set
			$js .= "if($(this).attr('id') == '') $(this).attr('id', 'anon'+parseInt(Math.random()*100000));";
			// store the target's DOM ID in the div's "rel" attribute
			$js .= "$('#$div_id').attr('rel',$(this).attr('id')).position_near_click(e).show('normal');return false; });";
		}
		$this->depends->html->css_load('dialog', 'dialog');
		$this->depends->html->js_load('ajax');
		$this->depends->html->js_run('', $js);
	}

	/**
	 * Return the HTML/JS to create a series of buttons destined for a popup
	 * or a dialog.
	 *
	 * Action can be one of the following:
	 *   - "ajax_load": Fetch the "url" variable via GET and populate the
	 *                  popup div with the resulting HTML.  The AJAX action
	 *                  should return a JSON packet.
	 *   - "submit_form": Submit the form specified by the "form_id" variable.
	 *   - "close": Close/hide the popup div.
	 *   - "function": Call the JavaScript function specified by the
	 *                 "function" variable.  The function should take a
	 *                 single argument, which is the jQuery element for the
	 *                 content container of the dialog or popup.
	 *   - "custom": Run a custom snippet of JavaScript as specified in the
	 *               "js" variable.
	 *
	 * Example:
	 @code
	 $ajax->buttons(array(
		 __('OK')     => array('action'=>'submit_form', 'form_id'=>'myform'),
		 __('Thingy') => array('action'=>'ajax_load', 'url'=>url('/ajax/thingy')),
		 __('Cancel') => array('action'=>'close')
	 ));
	 @endcode
	 *
	 * @param array $buttons Button configuration
	 * @param string $context Either 'popup' or 'dialog', depending on what
	 *                        the buttons will be used in.
	 * @param boolean $retjs Return JavaScript code for event handlers as well.
	 *                       If false, the returned HTML will include
	 *                       JavaScript to install the event handlers.
	 * @return string
	 */
	function buttons($buttons, $context, $retjs=false)
	{
		switch($context) {
			case 'dialog':
				$hide    = "$(this).parents('.dialog:first').jqmHide()";
				$content = "$(this).parents('.dialog-content:first')";
				break;
			case 'popup':
				$hide    = "$(this).parents('.popup:first').hide('normal')";
				$content = "$(this).parents('.popup:first')";
				break;
		}
		$ret = '<div class="buttons">';
		$bjs = '';
		foreach($buttons as $label=>$btn) {
			switch($btn['action']) {
				case 'ajax_load':
					$this->depends->html->js_load('ajax');
					$js = "var c = {$content};".
						"$.ajax({".
							"url: '{$btn['url']}',".
							"type: 'GET',".
							"data: {_ajax:1},".
							"dataType: 'json',".
							"success: function(d){ if(d.html) {c.empty().append(d.html)} else {{$hide};} ajax_exec(d); }".
						"});"; break;

				case 'submit_form':
					$js = "$('#{$btn['form_id']}').submit();"; break;

				case 'custom':
					$js = $btn['js']; break;

				case 'function':
					$js = "{$btn['function']}({$content});"; break;

				case 'close':
				default:
					$js = "{$hide};";
			}
			$id = "prontobtn-".$this->guid++;
			$ret .= '<input id="'.$id.'" type="button" class="button" value="'.$label.'" />&nbsp;';
			// we have to bind events this way because IE is retarded beyond repair
			$bjs .= "$('#$id').click(function(){ $js return false; });";
		}
		$ret .= '</div>';

		if($retjs) {
			return array($ret, $bjs);
		} else {
			$this->depends->html->js_run('', $bjs);
			return $ret;
		}
	}

	/**
	 * Generate an autocomplete textbox widget for a single selection.
	 *
	 * This widget requires an AJAX controller to handle the search requests.  The
	 * controller should return the results in plain text, with each result on a
	 * new line.  Separate fields with a pipe (|).
	 *
	 * Here's an example of a simple AJAX search controller that does both
	 * searches and lookups:
	 *
	 @code
	 function GET_search() {
		 $this->set_ajax(true);
		 if($id = $this->param('id')) {
			 $user = $this->models->user->get($id);
			 if($user) echo $user['name']."\n";
		 } else {
		   $matches = $this->models->user->search($this->param('q'), $this->param('limit'));
		   foreach($matches as $id=>$name) echo "$id|$name\n";
		 }
	 }
	 @endcode
	 *
	 * @param string $name Name of the form field (usually type="hidden")
	 *                     that will contain the selections.  This field will
	 *                     be generated for you automatically.
	 * @param string $value_id The "ID" of the current selection.  This is the
	 *                         variable that will populate the value="" field
	 *                         of the hidden form variable.
	 * @param string $value_name The display vlue of the current selection.
	 *                           This is the variable that will be shown in
	 *                           the browser.  If this is set to TRUE, then
	 *                           the widget will request the value name through
	 *                           the $search_url by passing the "id" query var.
	 * @param string $search_url Full URL to the AJAX search URL
	 * @param string $form_id DOM ID of the form that contains the selections
	 *
	 * @return string
	 */
	function autocomplete($name, $value_id, $value_name, $search_url, $form_id) {
		$this->depend('form');
		$this->depends->html->js_load('jq/jquery.autocomplete');
		$this->depends->html->css_load('autocomplete');

		// autocomplete plugin docs:
		//   http://docs.jquery.com/Plugins/Autocomplete

		$guid = $this->guid++;
		$id   = $this->depends->form->dom_id($name);
		$icon = $this->depends->html->image('icons/pencil.gif', __('Change'), array('id'=>"ac{$guid}-chg",'style'=>'cursor:pointer'));

		$out = <<<EOT
<script type="text/javascript">
function ac{$guid}_cb(event, data, formatted) {
	$('#$id').val(data[0]);
	$('#ac{$guid}-text').hide();
	$('#ac{$guid}-val span.ac-val').html(data[1]);
	$('#ac{$guid}-val').show();
}
$(document).ready(function(){
	$('#ac{$guid}-text').autocomplete("{$search_url}", {
		minChars: 2,
		cacheLength: 20,
		formatItem: function(d){ return d[1]; },
		formatResult: function(d){ return d[1]; }
	});
	$('#ac{$guid}-text').result(ac{$guid}_cb);

	$('#ac{$guid}-chg').click(function(){
		$('#$id').val('');
		$('#ac{$guid}-val span.ac-val').html('');
		$('#ac{$guid}-val').hide();
		$('#ac{$guid}-text').val('').show();
	});
});
</script>
EOT;
		if($value_id && $value_name === true) {
			$out .= <<<EOT
<script type="text/javascript">
$(document).ready(function(){
	$.get("{$search_url}", {id: {$value_id}}, function(d){
		$('#ac{$guid}-val span.ac-val').html(d);
	});
});
</script>
EOT;
		}

		$out .= '<input type="hidden" id="'.$id.'" name="'.$name.'" value="'.$value_id.'" />';
		if($value_id) {
			$out .= '<span id="ac'.$guid.'-val" class="ac-val"><span class="ac-val">'.$value_name.'</span><span class="ac-chg">'.$icon.'</span></span>';
			$out .= $this->depends->form->text("ac{$guid}-text", '', '', '', array('id'=>"ac{$guid}-text",'class'=>'ac-txt','style'=>'display:none'));
		} else {
			$out .= '<span id="ac'.$guid.'-val" class="ac-val" style="display:none"><span class="ac-val">'.$value_name.'</span><span class="ac-chg">'.$icon.'</span></span>';
			$out .= $this->depends->form->text("ac{$guid}-text", '', '', '', array('id'=>"ac{$guid}-text",'class'=>'ac-txt'));
		}

		return $out;
	}

	/**
	 * Generate an autocomplete textbox widget and "container" div to hold
	 * the multiple selections.
	 *
	 * This widget requires an AJAX controller to handle the search requests.  The
	 * controller should return the results in plain text, with each result on a
	 * new line.  Separate fields with a pipe (|).
	 *
	 * Here's an example of a simple AJAX search controller:
	 *
	 @code
	 function GET_search() {
	   $this->set_ajax(true);
	   $matches = $this->models->user->search($this->param('q'), $this->param('limit'));
	   foreach($matches as $id=>$name) echo "$id|$name\n";
	 }
	 @endcode
	 *
	 * @param string $name Name of the form field (usually type="hidden")
	 *                     that will contain the selections.  Don't include the
	 *                     trailing '[]' as it will be appended automatically.
	 * @param array $values Currently selected values
	 * @param string $search_url Full URL to the AJAX search URL
	 * @param string $form_id DOM ID of the form that contains the selections
	 * @param int $max_sel The maximum number of selections allowed, or zero for unlimited
	 *
	 * @return string
	 */
	function autocomplete_multisel($name, $values, $search_url, $form_id, $max_sel=0) {
		$this->depend('form');
		$this->depends->html->js_load('jq/jquery.autocomplete');
		$this->depends->html->css_load('autocomplete');

		// autocomplete plugin docs:
		//   http://docs.jquery.com/Plugins/Autocomplete/

		$guid = $this->guid++;

		// i18n
		$msg_remove  = __('Remove');
		$msg_toomany = __("You can have a maximum of %d selections.", $max_sel);

		$out = <<<EOT
<script type="text/javascript">
function ac{$guid}_cb(event, data, formatted) {
	$('#ac{$guid}-text').val('');
	if({$max_sel} > 0 && $('li', $('#ac{$guid}-sel')).length >= {$max_sel}) {
		alert("{$msg_toomany}");
		return;
	}
	if($('li[rel='+data[0]+']', $('#ac{$guid}-sel')).length == 0) {
		$('#ac{$guid}-sel').append('<li rel="'+data[0]+'">[<a title="{$msg_remove}" href="#" onClick="return ac{$guid}_remove(this)">x</a>] &nbsp; '+data[1]+'</li>');
		$('#{$form_id}').append('<input type="hidden" name="{$name}[]" value="'+data[0]+'" />');
	}
}
function ac{$guid}_remove(el) {
	var id = $(el).parent().attr('rel');
	$('#$form_id').find('input[name="{$name}[]"][type="hidden"][value="'+id+'"]').remove();
	$('#ac{$guid}-sel').find('li[rel="'+id+'"]').remove();
	return false;
}
$(document).ready(function(){
	$('#ac{$guid}-text').autocomplete("{$search_url}", {
		minChars: 2,
		cacheLength: 20,
		formatItem: function(d){ return d[1]; },
		formatResult: function(d){ return d[1]; }
	});
	$('#ac{$guid}-text').result(ac{$guid}_cb);
});
</script>
EOT;

		$out .= $this->depends->form->text("ac{$guid}-text", '', '', '', array('id'=>"ac{$guid}-text",'class'=>'ac-txt'));
		foreach($values as $id=>$val) {
			$out .= '<input type="hidden" name="'.$name.'[]" value="'.$id.'" />';
		}
		$out .= '<ul id="ac'.$guid.'-sel" class="ac-sel">';
		foreach($values as $id=>$val) {
			$out .= '<li rel="'.$id.'">[<a title="Remove" href="#" onClick="return ac'.$guid.'_remove(this)">x</a>] &nbsp; '.$val.'</li>';
		}
		$out .= '</ul>';

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
