<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Handles file uploads
 *
 **/

class ppFile extends Plugin
{
	var $mimetypes    = array('image/png', 'image/pjpeg', 'image/jpeg', 'image/jpg', 'image/gif');
	var $jpeg_quality = 100;

	function ppFile() {
		$this->Plugin();

		if(!defined('JPEG_QUALITY')) {
			define('JPEG_QUALITY', $this->jpeg_quality);
		}
	}

	/**
	 * Check if a file has been uploaded
	 */
	function is_uploaded($key)
	{
		return !empty($_FILES[$key]['name']);
	}

	/**
	 * Move an uploaded file to a new location.
	 *
	 * @param string $key      The name the file form element
	 * @param string $destpath The full path (and filename) where the file will be moved
	 */
	function move($key, $destpath)
	{
		return move_uploaded_file($_FILES[$key]['tmp_name'], $destpath);
	}

	/**
	 * Return the file's original name, as uploaded by the user.
	 *
	 * @param string $key The name the file form element
	 */
	function original_name($key)
	{
		return $_FILES[$key]['name'];
	}

	/**
	 * Process an image upload, resizing it if necessary
	 */
	function process_image($key, $destpath, $max_width=false, $max_height=false)
	{
		$this->depend('image');

		if(empty($_FILES[$key]['name'])) return true;
		if($_FILES[$key]['error'] > 0) {
			return __('An error occurred during file upload.  Please try again.');
		}

		// verify mime type
		$found = false;
		foreach($this->mimetypes as $mime) {
			if($_FILES[$key]['type'] == $mime) $found = true;
		}
		if(!$found) {
			return __('This file does not look like a valid image.  Please upload a GIF, PNG, or JPEG.');
		}

		return $this->depends->image->resize($_FILES[$key]['type'], $_FILES[$key]['tmp_name'], $destpath, $max_width, $max_height);
	}
}

?>
