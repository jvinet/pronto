<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Some common image-handling routines.  Currently this plugin only
 *              outputs to JPEG format.  Quality is adjustable by setting the
 *              JPEG_QUALITY constant (1-100).
 *
 **/

define('IMAGE_FLIP_HORIZONTAL', 1);
define('IMAGE_FLIP_VERTICAL',   2);
define('IMAGE_FLIP_BOTH',       3);

class ppImage extends Plugin
{
	function ppImage()
	{
		$this->Plugin();
	}

	/**
	 * Convert an image from GIF/PNG/JPEG to JPEG, preserving dimensions
	 */
	function convert($srcType, $srcFile, $dstFile)
	{
		$size = getimagesize($srcFile);

		return $this->resize($srcType, $srcFile, $dstFile, $size[0], $size[1], true);
	}

	/**
	 * Rotate an image
	 *
	 * @param string $srcType MIME type of the source file
	 * @param string $srcFile Source filename
	 * @param string $dstFile Destination filename, can be the same as $srcFile
	 * @param float  $angle Angle (degrees) to rotate, can be negative
	 */
	function rotate($srcType, $srcFile, $dstFile, $angle)
	{
		$src = $this->_load_image($srcFile, $srcType);
		if(!$src) return false;

		$dst = imagerotate($src, $angle, 0);
		if(!$dst) return false;

		if(!$this->_create_image($dst, $dstFile, $srcType)) return false;

		imagedestroy($src);
		imagedestroy($dst);

		return true;
	}

	/**
	 * Automatically rotate an image based on EXIF data.  If the
	 * IFD0[Orientation] header is available, this function will use
	 * it to rotate the image to the correct orientation.
	 *
	 * @param string $srcType MIME type of the source file
	 * @param string $srcFile Source filename
	 * @param string $dstFile Destination filename, can be the same as $srcFile
	 */
	function auto_rotate($srcType, $srcFile, $dstFile, $angle)
	{
		if(!extension_loaded('exif')) {
			trigger_error("ppImage::auto_rotate() requires the exif PHP extension.");
			return false;
		}

		$exif = exif_read_data($srcFile);
		if($exif == false) return false;

		if(!isset($exif['IFD0']['Orientation'])) return false;

		$rotate = 0;
		$flip = false;
		switch($o) {
			case 1:
				$rotate = 0;
				$flip = false;
				break;
			case 2:
				$rotate = 0;
				$flip = true;
				break;
			case 3:
				$rotate = 180;
				$flip = false;
				break;
			case 4:
				$rotate = 180;
				$flip = true;
				break;
			case 5:
				$rotate = 90;
				$flip = true;
				break;
			case 6:
				$rotate = 90;
				$flip = false;
				break;
			case 7:
				$rotate = 270;
				$flip = true;
				break;
			case 8:
				$rotate = 270;
				$flip = false;
				break;
		}
		if($flip) {
			$this->flip($srcType, $srcFile, $srcFile, IMAGE_FLIP_HORIZONTAL);
		}
		if($rotate) {
			$this->rotate($srcType, $srcFile, $srcFile, $rotate);
		}
		if($srcFile != $dstFile) {
			copy($srcFile, $dstFile);
		}

		return true;
	}

	/**
	 * Resize an image, optionally maintaining aspect ratio.  Also converts
	 * to JPEG format.
	 *
	 * @param string $srcType MIME type of the source file
	 * @param string $srcFile Source filename
	 * @param string $dstFile Destination filename, can be the same as $srcFile
	 * @param int $max_width
	 * @param int $max_height
	 * @param bool $maintain_aspect
	 */
	function resize($srcType, $srcFile, $dstFile, $max_width=0, $max_height=0, $maintain_aspect=true)
	{
		$size = getimagesize($srcFile);

		$width = $size[0];
		$height = $size[1];

		if(!$max_width)  $max_width  = $width;
		if(!$max_height) $max_height = $height;

		if($maintain_aspect) {
			// Proportionally resize the image to the max sizes specified above
			$x_ratio = $max_width / $width;
			$y_ratio = $max_height / $height;

			if(($width <= $max_width) && ($height <= $max_height)) {
				$tn_width = $width;
				$tn_height = $height;
			} else if(($x_ratio * $height) < $max_height) {
				$tn_height = ceil($x_ratio * $height);
				$tn_width = $max_width;
			} else {
				$tn_width = ceil($y_ratio * $width);
				$tn_height = $max_height;
			}
		} else {
			$tn_width = $max_width;
			$tn_height = $max_height;
		}

		// Create the new image
		$src = $this->_load_image($srcFile, $srcType);
		if(!$src) return false;

		$dst = imagecreatetruecolor($tn_width, $tn_height);
		imagecopyresampled($dst, $src, 0, 0, 0, 0, $tn_width, $tn_height, $width, $height);

		// output to JPEG format
		$this->_create_image($dst, $dstFile, 'image/jpeg');

		imagedestroy($src);
		imagedestroy($dst);

		return true;
	}

	/**
	 * Flip an image horizontally, vertically, or both.
	 *
	 * @param string $srcType MIME type of the source file
	 * @param string $srcFile Source filename
	 * @param string $dstFile Destination filename, can be the same as $srcFile
	 * @param int $mode Either IMAGE_FLIP_HORIZONTAL, IMAGE_FLIP_VERTICAL,
	 *                  or IMAGE_FLIP_BOTH
	 */
	function flip($srcType, $srcType, $dstFile, $mode)
	{
		$size = getimagesize($srcFile);

		$width = $size[0];
		$height = $size[1];

		$src_x      = 0;
		$src_y      = 0;
		$src_width  = $width;
		$src_height = $height;

		switch((int)$mode) {
			case IMAGE_FLIP_HORIZONTAL:
				$src_y      = $height;
				$src_height = -$height;
				break;
			case IMAGE_FLIP_VERTICAL:
				$src_x      = $width;
				$src_width  = -$width;
				break;
			case IMAGE_FLIP_BOTH:
				$src_x      = $width;
				$src_y      = $height;
				$src_width  = -$width;
				$src_height = -$height;
				break;
			default:
				return false;
		}

		$src = $this->_load_image($srcFile, $srcType);
		if(!$src) return false;

		$dest = imagecreatetruecolor($width, $height);

		if(imagecopyresampled($dest, $src, 0, 0, $src_x, $src_y, $width, $height, $src_width, $src_height)) {
			// output to JPEG format
			$this->_create_image($dest, $dstFile, 'image/jpeg');
			return true;
		}

		return false;
	}

	function _load_image($fn, $mime)
	{
		switch($mime) {
			case 'image/gif':   return ImageCreateFromGif($fn);
			case 'image/jpeg':
			case 'image/pjpeg': return ImageCreateFromJpeg($fn);
			case 'image/png':   return ImageCreateFromPng($fn);
		}
		return false;
	}

	function _create_image($img, $fn, $mime)
	{
		$q = defined('JPEG_QUALITY') ? JPEG_QUALITY : 75;

		switch($mime) {
			case 'image/gif':   return ImageGif($img, $fn);
			case 'image/jpeg':
			case 'image/pjpeg': return ImageJpeg($img, $fn, $q);
			case 'image/png':   return ImagePng($img, $fn);
		}
		return false;
	}

}

?>
