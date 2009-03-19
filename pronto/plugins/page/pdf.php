<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Convert HTML to PDF using DOMPDF or PrinceXML.
 *              You'll need the DOMPDF or Prince libraries in
 *              app/extlib/{prince,dompdf}
 *
 *              You should also put this in app/config/config.php:
 *
 *              // set to 'prince' or 'dompdf'
 *              define('PDF_BACKEND', 'prince');
 *
 **/

class ppPDF extends Plugin
{
	var $fn_pdf;
	var $tempdir;

	// Only used for DOMPDF
	var $paper_size        = 'letter';
	var $paper_orientation = 'portrait';

	function ppPDF()
	{
		$this->Plugin();

		if(!defined('PDF_BACKEND')) {
			define('PDF_BACKEND', 'prince');
		}

		$this->tempdir = defined('DIR_FS_TEMP') ? DIR_FS_TEMP : DS.'tmp';
	}

	/**
	 * Convert HTML into a PDF file.
	 *
	 * @param string $html The HTML to convert to PDF
	 * @param string $filename If set, resulting PDF file will be stored here.
	 *                         If empty, then a temporary file will be used
	 *                         until ::output() is called.
	 */
	function convert($html, $filename='')
	{
		if(!$filename) {
			$filename = $this->fn_pdf = tempnam($this->tempdir, 'pdf');
		}

		if(PDF_BACKEND == 'prince') {
			return $this->_convert_prince($html, $filename);
		}

		if(PDF_BACKEND == 'dompdf') {
			return $this->_convert_dompdf($html, $filename);
		}

		trigger_error('PDF_BACKEND is undefined or invalid');
		return false;
	}

	/**
	 * Send PDF output to browser.
	 * 
	 * @param string $filename Filename to use.  If empty, then send directly
	 *                         to browser instead of sending as an
	 *                         attachment.
	 */
	function output($filename='')
	{
		header('Content-Type: application/pdf');
		header('Content-Length: '.filesize($this->fn_pdf));
		if($filename) {
			header('Content-Disposition: inline; filename="'.$filename.'"');
		}
		readfile($this->fn_pdf);

		// cleanup
		@unlink($this->fn_pdf);
		$this->fn_pdf = '';
	}

	/**
	 * Set the paper size and orientation.
	 * This method is only relevant for the DOMPDF backend.
	 * For PrinceXML, use the "@page" CSS selector to set page size/margins/etc.
	 * See the PrinceXML site for documentation.
	 */
	function set_paper($size, $orientation='portrait')
	{
		$this->paper_size = $size;
		$this->paper_orientation = $orientation;
	}

	function _convert_dompdf($html, $filename)
	{
		require_once(DIR_FS_APP.DS.'extlib'.DS.'dompdf'.DS.'dompdf_config.inc.php');
		$pdf = new DOMPDF();
		$pdf->set_paper($this->paper_size, $this->paper_orientation);
		$pdf->load_html($html);
		$pdf->render();

		file_put_contents($filename, $pdf->output());
		return true;
	}

	function _convert_prince($html, $filename)
	{
		$f_html = tempnam($this->tempdir, 'html');
		file_put_contents($f_html, $html);

		$cmd  = realpath(DIR_FS_APP.DS.'extlib'.DS.'prince'.DS.'bin'.DS.'prince');
		$cmd .= " --silent -i html -o \"$filename\" $f_html";
		system($cmd);
		@unlink($f_html);
		return true;
	}

}

?>
