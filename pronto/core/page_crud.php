<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Page controller extension for basic CRUD operations
 *              around a data entity.  Also supports basic handling
 *              of image uploads/resizing.
 *
 **/

define('VALID_IMAGE_TYPES', 'image/png image/pjpeg image/jpeg image/jpg image/gif');

class Page_CRUD extends Page
{
	var $model_name;
	var $human_name;
	var $model;

	var $create_template;
	var $edit_template;
	var $list_template;
	var $edit_url;       // edit interface
	var $list_url;       // list interface
	var $after_url;      // URL to redirect to after a create/edit
	var $after_template; // Template to render after a create/edit, used for AJAX
	                     // creates/edits, or when after_url isn't set (no redirect).

	// Used for image uploads
	var $file_dir;

	// Which actions are enabled for this entity?
	// Default is all.
	var $enabled_actions = array('create','edit','delete','list','file');

	// when using AJAX, do we reload the main/parent window
	// after the form is submitted?
	var $ajax_reload_parent = false;

	/**
	 * Constructor for all Page_CRUD elements
	 *
	 */
	function Page_CRUD()
	{
		$this->Page();
	}

	/**
	 * Set the data about the entity we're CRUDing
	 *
	 * @param string $model_name Name of the model object
	 * @param string $human_name Name to use for flash messages, etc.
	 * @param string $template_prefix Prefix to use for templates
	 */
	function set_entity($model_name, $human_name, $template_prefix=false)
	{
		$this->model_name =  $model_name;
		$this->human_name =  $human_name;
		$this->model      =& $this->models->{$this->model_name};

		if($template_prefix === false) $template_prefix = $model_name.'/';

		// defaults -- these can be overridden manually by the subclass
		$this->create_template = "{$template_prefix}create.php";
		$this->edit_template   = "{$template_prefix}create.php";
		$this->list_template   = "{$template_prefix}list.php";

		// need to drop the 'p' from the front of the class names
		$cn = substr(get_class($this), 1);
		$this->edit_url  = url($cn, 'edit').'?id=<id>';
		$this->list_url  = url($cn, 'list');
		// after_url is the URL that the browser is sent to after an
		// entity is created or updated, and after_template is the template
		// that will be rendered after an entity is created/updated.  The
		// two are mutually exclusive.  Use one.
		$this->after_url = $this->list_url;
		$this->after_template = "";
	}

	/**
	 * Take an URL template and turn it into a "real" one by
	 * substituting in the proper ID value(s).
	 */
	function make_url($url, $id='')
	{
		return str_replace(array('_ID_','<id>'), $id, $url);
	}


	/**********************************************************************
	 * CREATE
	 **********************************************************************/
	function GET_create()
	{
		$this->auth_create();
		if(!in_array('create', $this->enabled_actions)) $this->web->forbidden();

		$json = array();
		// if the template already has 'data' set, it means we came
		// from POST_create() because there were validation errors
		if($this->template->is_set('data')) {
			$data = $this->template->get('data');
			$this->template->un_set('data');
			$json['errors'] = $this->template->get('errors');
			// POST_create() has already called the pre-edit hook, before it
			// passed control back to GET_create() for failed validation.
		} else {
			$data = $this->model->create_record();
			if($this->hook_create__pre_edit($data) === false) return;
			if($this->hook__pre_edit($data) === false) return;
		}
		$this->template->set('data', $data);

		if($this->ajax) {
			$this->ajax_render($this->create_template, $json);
		} else {
			$this->render($this->create_template);
		}
	}

	/**
	 * This is used for both Create and Edit operations.
	 */
	function POST_create()
	{
		$this->auth_create();

		$data = $this->load_input();
		$is_update = isset($data['id']) && !empty($data['id']);

		if(!$is_update && !in_array('create', $this->enabled_actions)) $this->web->forbidden();

		if(preg_match('|^Model|', get_parent_class($this->model))) {
			// Backwards Compatibility
			$errors = $this->model->validate($data, $is_update);
		} else {
			$errors = $this->model->validate($data);
		}

		// validate file uploads, if any
		if(is_array($this->model->files)) {
			foreach($this->model->files as $k=>$v) {
				if(!isset($_FILES[$k])) continue;
				if($_FILES[$k]['error'] != 0) continue;
				$f =& $_FILES[$k];
				if($f['error'] != 0) $errors[$k] = __('An error occurred while uploading - please try again');
				if(isset($v['type'])) {
					// validate mime type
					$match = false;
					if($v['type'] == 'image') {
						$v['type'] = explode(' ', VALID_IMAGE_TYPES);
					}
					if(is_array($v['type'])) {
						foreach($v['type'] as $m) {
							if($f['type'] == $m) $match = true;
						}
					} else {
						if($f['type'] == $v['type']) $match = true;
					}
					if(!$match) $errors[$k] = __('%s: Invalid file type uploaded', ucwords(str_replace('_',' ',$k)));
				}
			}
		}
		
		if(!empty($errors)) {
			$this->template->set('errors', $errors);
			if($is_update) {
				if($this->hook_edit__failed_validation($data) === false) return;
				if($this->hook__failed_validation($data) === false) return;
				if($this->hook_edit__pre_edit($data) === false) return;
				if($this->hook__pre_edit($data) === false) return;
				$this->template->set('data', $data);
				$this->GET_edit();
			} else {
				if($this->hook_create__failed_validation($data) === false) return;
				if($this->hook__failed_validation($data) === false) return;
				if($this->hook_create__pre_edit($data) === false) return;
				if($this->hook__pre_edit($data) === false) return;
				$this->template->set('data', $data);
				$this->GET_create();
			}
			return;
		}

		if($is_update) {
			$id = $data['id'];
			if($this->hook_update__pre_save($data) === false) return;
			if($this->hook__pre_save($data) === false) return;
			$this->model->save($data);
			$flash = __('%s has been updated.', $this->human_name);
		} else {
			if($this->hook_insert__pre_save($data) === false) return;
			if($this->hook__pre_save($data) === false) return;
			$id = $this->model->save($data);
			$data['id'] = $id;
			$flash = __('%s has been created.', $this->human_name);
		}

		// handle file uploads
		if(is_array($this->model->files)) {
			foreach($this->model->files as $k=>$v) {
				if(!isset($_FILES[$k])) continue;
				if($_FILES[$k]['error'] != 0) continue;
				$f =& $_FILES[$k];
				foreach($data as $dk=>$dv) {
					if(is_array($dv)) continue;
					$v['filename'] = str_replace("<$dk>", $dv, $v['filename']);
				}
				$path = $v['fileroot'].DS.$v['filename'];
				if($v['type'] == 'image') {
					if(!isset($this->plugins->image)) $this->import_plugin('image');
					if(isset($v['max_width'])) {
						$this->plugins->image->resize($f['type'], $f['tmp_name'], $path, $v['max_width'], $v['max_height'], true);
					} else {
						$this->plugins->image->convert($f['type'], $f['tmp_name'], $path);
					}
				} else {
					move_uploaded_file($f['tmp_name'], $path);
				}
			}
		}

		// run the "post" hooks
		if($is_update) {
			if($this->hook_update__post_save($data) === false) return;
		} else {
			if($this->hook_insert__post_save($data) === false) return;
		}
		if($this->hook__post_save($data) === false) return;

		if($this->ajax) {
			if($this->ajax_reload_parent) {
				if(!$this->flash_isset()) $this->flash($flash);
				$this->ajax_render($this->after_template, array('success'=>true,'reload'=>true));
			} else {
				$flash = $this->flash_isset() ? $this->flash_get() : $flash;
				$this->ajax_render($this->after_template, array('success'=>true,'flash'=>$flash));
			}
		} else {
			if(!$this->flash_isset()) $this->flash($flash);
			if($this->after_template) {
				$this->render($this->after_template);
			} else {
				$this->redirect($this->make_url($this->after_url, $id));
			}
		}
	}

	/**********************************************************************
	 * EDIT
	 **********************************************************************/
	function GET_edit()
	{
		$this->auth_create();
		if(!in_array('edit', $this->enabled_actions)) $this->web->forbidden();

		$json = array();
		// if the template already has 'data' set, it means we came
		// from POST_edit() because there were validation errors
		if($this->template->is_set('data')) {
			$data = $this->template->get('data');
			$this->template->un_set('data');
			$json['errors'] = $this->template->get('errors');
			// POST_create() has already called the pre-edit hook, before it
			// passed control back to GET_create() for failed validation.
		} else {
			$id = $this->param('id');
			$data = $this->model->load($id) or $this->web->notfound();
		}
		if($this->hook_edit__pre_edit($data) === false) return;
		if($this->hook__pre_edit($data) === false) return;
		$this->template->set('data', $data);

		// populate file fields, if any
		if(is_array($this->model->files)) {
			foreach($this->model->files as $k=>$v) {
				foreach($data as $dk=>$dv) {
					if(!is_array($dv)) $v['filename'] = str_replace("<$dk>", $dv, $v['filename']);
				}
				if(file_exists($v['fileroot'].DS.$v['filename'])) {
					$data[$k] = $v['webroot'].'/'.$v['filename'];
				}
			}
		}

		if($this->ajax) {
			$this->ajax_render($this->edit_template, $json);
		} else {
			$this->render($this->edit_template);
		}
	}
	function POST_edit()
	{
		$this->auth_create();
		if(!in_array('edit', $this->enabled_actions)) $this->web->forbidden();
		$this->POST_create();
	}

	/**********************************************************************
	 * DELETE
	 **********************************************************************/
	function GET_delete()
	{
		$this->auth_delete();
		if(!in_array('delete', $this->enabled_actions)) $this->web->forbidden();

		$ids = $this->param('delete_ids', $this->param('ids', array($this->param('id'))));
		foreach($ids as $id) {
			$data = $this->model->load($id) or $this->web->notfound();
			if($this->hook_delete__pre_delete($data) === false) return;
			$this->model->delete($data['id']);

			// delete any associated files
			if(is_array($this->model->files)) {
				foreach($this->model->files as $k=>$v) @unlink($this->model->files[$k]['path'].DS.$id);
			}

			if($this->hook_delete__post_delete($data) === false) return;
		}
		$this->flash(__('%s has been deleted.', $this->human_name));
		if($this->ajax) {
			$this->ajax_render('', array('success'=>true));
		} else {
			$this->redirect($this->make_url($this->list_url));
		}
	}

	/**********************************************************************
	 * LIST
	 **********************************************************************/
	function GET_list()
	{
		$this->auth_list();
		if(!in_array('list', $this->enabled_actions)) $this->web->forbidden();

		// Gather list parameters from the model
		$params = $this->model->enum_schema();
		if(empty($params['order'])) $params['order'] = "{$this->model->pk} ASC"; 
		if(empty($params['limit'])) $params['limit'] = 50;
		if($this->hook_list__params($params) === false) return;

		list($data,$ttlrows,$curpage,$perpage) = $this->sql->enumerate($params);
		if($this->hook_list__post_select($data) === false) return;

		if($this->ajax) {
			$ret = array(
				'total'   => ceil($ttlrows / $perpage),
				'page'    => $curpage,
				'records' => $ttlrows,
				'rows'    => array(),
			);

			// use the 'fields' query var to return only the columns requested
			$cols = $this->param('cols');
			if($cols) {
				// Note: this method is currently in testing for a new AJAX-based grid component
				$cols = explode('&', urldecode($this->param('cols')));
				foreach($data as $k=>$v) {
					$row = array('cell' => array());
					if(isset($v['id'])) $row['id'] = $v['id'];
					foreach($cols as $c) $row['cell'][] = $v[$c];
					$ret['rows'][] = $row;
				}
			} else {
				foreach($data as $k=>$v) {
					$row = array();
					foreach($v as $key=>$val) $row[$key] = $val;
					$ret['rows'][] = $row;
				}
			}

			echo json_encode($ret);
		} else {
			$this->template->set('data',      $data);
			$this->template->set('totalrows', $ttlrows);
			$this->template->set('curpage',   $curpage);
			$this->template->set('perpage',   $perpage);
			$this->render($this->list_template);
		}
	}

	/**********************************************************************
	 * FILE PREVIEW / REMOVE
	 **********************************************************************/
	function GET_file__preview()
	{
		$this->auth_list();
		if(!in_array('file', $this->enabled_actions)) $this->web->forbidden();

		$id = $this->param('id');
		if(!is_numeric($id)) $this->web->notfound();
		$key = $this->param('key');
		if(!isset($this->model->files[$key])) $this->web->notfound();

		// build the filename
		$data = $this->model->load($id) or $this->web->notfound();
		$filename = $this->model->files[$key]['filename'];
		foreach($data as $k=>$v) {
			if(is_array($v)) continue;
			$filename = str_replace("<$k>", $v, $filename);
		}

		if($this->model->files[$key]['type'] == 'image') {
			header('Content-Type: image/jpeg');
		} else if(isset($this->model->files[$key]['type'])) {
			header('Content-Type: '.$this->model->files[$key]['type']);
		}
		readfile($this->model->files[$key]['fileroot'].DS.$filename);
		die;
	}
	function GET_file__remove()
	{
		$this->auth_delete();
		if(!in_array('file', $this->enabled_actions)) $this->web->forbidden();

		$id = $this->param('id');
		if(!is_numeric($id)) $this->web->notfound();
		$key = $this->param('key');
		if(!isset($this->model->files[$key])) $this->web->notfound();

		// build the filename
		$data = $this->model->get_or_404($id);
		$filename = $this->model->files[$key]['filename'];
		foreach($data as $k=>$v) {
			if(is_array($v)) continue;
			$filename = str_replace("<$k>", $v, $filename);
		}

		@unlink($this->model->files[$key]['fileroot'].DS.$filename);
		$this->redirect($this->make_url($this->edit_url, $id));
	}


	/**********************************************************************
	 * AUTH -- Override with $web->check_access() calls to protect actions
	 **********************************************************************/
	function authenticate() {}
	function auth_create()  { $this->authenticate(); }
	function auth_delete()  { $this->authenticate(); }
	function auth_list()    { $this->authenticate(); }


	/**********************************************************************
	 * DATA HOOKS -- Override these to tweak data at some process points
	 *               To stop any further execution in the calling function,
	 *               just have your hook function return false.
	 **********************************************************************/
 
	/* These are run for both create AND edit operations */
	function hook__pre_edit(&$data)          { return true; }
	function hook__failed_validation(&$data) { return true; }
	function hook__pre_save(&$data)          { return true; }
	function hook__post_save(&$data)         { return true; }

	/* The rest are split into their specific operations */
	function hook_create__pre_edit(&$data)          { return true; }
	function hook_create__failed_validation(&$data) { return true; }
	function hook_edit__pre_edit(&$data)            { return true; }
	function hook_edit__failed_validation(&$data)   { return true; }

	function hook_insert__pre_save(&$data)  { return true; }
	function hook_insert__post_save(&$data) { return true; }
	function hook_update__pre_save(&$data)  { return true; }
	function hook_update__post_save(&$data) { return true; }

	function hook_delete__pre_delete(&$data)  { return true; }
	function hook_delete__post_delete(&$data) { return true; }

	function hook_list__params(&$params)          { return true; }
	function hook_list__post_select(&$data)       { return true; }
}

?>
