<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: A frontend plugin for SwiftMailer
 *
 **/

class ppMailer extends Plugin
{
	var $loaded;

	function ppMailer() {
		$this->Plugin();
		$this->loaded = false;
	}

	function _load()
	{
		if($this->loaded) return;
		require_once(DIR_FS_PRONTO.DS.'extlib'.DS.'swift'.DS.'Swift.php');
		$this->loaded = true;
	}

	/**
	 * Create an email message.  The ppMailer plugin class is 
	 * basically a factory class that returns a ppMailer_Message object
	 * that can be manipulated before being sent.
	 *
	 * @return object
	 */
	function create($to='', $subject='', $body='', $fromemail='', $fromname='')
	{
		$this->_load();
		$swift = new ppMailer_Message();

		if(defined('SMTP_HOST') && SMTP_HOST != '') {
			require_once(DIR_FS_PRONTO.DS.'extlib'.DS.'swift'.DS.'Swift'.DS.'Connection'.DS.'SMTP.php');
			$conn = new Swift_Connection_SMTP(SMTP_HOST);
		} else {
			require_once(DIR_FS_PRONTO.DS.'extlib'.DS.'swift'.DS.'Swift'.DS.'Connection'.DS.'NativeMail.php');
			$conn = new Swift_Connection_NativeMail();
		}
		$swift->swift = new Swift($conn);

		if(defined('SMTP_USER') && SMTP_USER != '') {
			$conn->setUsername(SMTP_USER);
			$conn->setPassword(SMTP_PASS);
		}

		$swift->message = new Swift_Message($subject);
		$swift->message->setCharset(defined('CHARSET') ? CHARSET : 'UTF-8');
		if($body) $swift->add_text_part($body);

		$swift->recipients = new Swift_RecipientList();

		// we need a non-empty default
		$swift->set_from(defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'donotreply@example.com');
		if($fromemail) {
			$swift->set_from($fromemail, $fromname);
		}

		if(is_array($to)) {
			foreach($to as $email=>$name) {
				if(is_numeric($email)) {
					$swift->add_recipient($name);
				} else {
					$swift->add_recipient($email, $name);
				}
			}
		} else if($to) {
			$swift->add_recipient($to);
		}

		return $swift;
	}

	/**
	 * Create and send an email message.
	 *
	 * @return boolean
	 */
	function send($to='', $subject='', $body='', $fromemail='', $fromname='')
	{
		$this->_load();
		$mail = $this->create($to, $subject, $body, $fromemail, $fromname);
		return $mail->send();
	}
}

/**
 * An instance of a mail message.  This object will be returned from
 * a ppMailer::create() call.  You can then operate on the object
 * before calling ::send().
 */
class ppMailer_Message
{
	var $swift;
	var $message;
	var $recipients;

	var $callback;

	var $from;
	var $charset;

	/**
	 * Add message parts (text, HTML, etc)
	 */
	function add_part($content, $mime='text/plain')
	{
		$this->message->attach(new Swift_Message_Part($content, $mime));
	}
	function add_html_part($content)
	{
		$this->add_part($content, 'text/html');
	}
	function add_text_part($content)
	{
		$this->add_part($content, 'text/plain');
	}

	/**
	 * Add recipients (To, CC, BCC)
	 */
	function add_recipient($email, $name='')
	{
		if($name) {
			$this->recipients->addTo($email, $name);
		} else {
			$this->recipients->addTo($email);
		}
	}
	function add_to($email, $name='')
	{
		$this->add_recipient($email, $name);
	}
	function add_cc($email, $name='')
	{
		if($name) {
			$this->recipients->addCc($email, $name);
		} else {
			$this->recipients->addCc($email);
		}
	}
	function add_bcc($email, $name='')
	{
		if($name) {
			$this->recipients->addBcc($email, $name);
		} else {
			$this->recipients->addBcc($email);
		}
	}

	/**
	 * Set "From:" address
	 */
	function set_from($email, $name='')
	{
		if($name) {
			$this->from = new Swift_Address($email, $name);
		} else if($email) {
			$this->from = new Swift_Address($email);
		}
	}

	/**
	 * Add attachments
	 */
	function add_attachment($filepath, $filename='', $mime='application/octet-stream')
	{
		if(empty($filename)) $filename = basename($filepath);
		$this->message->attach(new Swift_Message_Attachment(new Swift_File($filepath), $filename, $mime));
	}

	/**
	 * Add an embedded image.
	 *
	 * @return string The content ID (CID), which should be placed in the img
	 *                tag like so: <img src="$cid" />
	 */
	function add_embedded_image($filepath, $filename='', $cid=null, $mime='application/octet-stream')
	{
		if(empty($filename)) $filename = basename($filepath);
		$img =& new Swift_Message_Image(new Swift_File($filepath), $filename, $mime, $cid);
		return $this->message->attach($img);
	}

	/**
	 * Manipulate headers
	 */
	function add_header($name, $value)
	{
		$this->message->headers->set($name, $value);
	}
	function get_header($name)
	{
		return $this->message->get($name);
	}

	/**
	 * Set a callback function that will be called for each recipient in
	 * the delivery list.
	 *
	 * Callback prototype: function(string $address, bool $result);
	 *
	 * You can also pass in the function as an object/method array, just as
	 * you would when calling call_user_func().
	 */
	function set_display_callback($fn)
	{
		$this->callback = $fn;
	}

	/**
	 * Send the email message
	 */
	function send()
	{
		if(defined('MAILER_DISABLE') && MAILER_DISABLE === true) return true;
		if($this->callback) {
			require_once(DIR_FS_PRONTO.DS.'extlib'.DS.'swift'.DS.'Swift'.DS.'Plugin'.DS.'VerboseSending.php');
			require_once('_mailer_callback.php');
			$view = new ppMailer_Display_Callback();
			$view->cb_func = $this->callback;
			$this->swift->attachPlugin(new Swift_Plugin_VerboseSending($view), 'verbose');
		}
		return !!$this->swift->send($this->message, $this->recipients, $this->from);
	}

	/**
	 * Batch-send the email message.  This is used to send out
	 * mass mails (newsletter, mailing lists, etc to multiple recipients)
	 * but obscure the recipient list so recipients can't see each others'
	 * email addresses.
	 */
	function batch_send()
	{
		if(defined('MAILER_DISABLE') && MAILER_DISABLE === true) return true;
		if($this->callback) {
			require_once(DIR_FS_PRONTO.DS.'extlib'.DS.'swift'.DS.'Swift'.DS.'Plugin'.DS.'VerboseSending.php');
			require_once('_mailer_callback.php');
			$view = new ppMailer_Display_Callback();
			$view->cb_func = $this->callback;
			$this->swift->attachPlugin(new Swift_Plugin_VerboseSending($view), 'verbose');
		}

		// use the AntiFlood plugin to send in smaller-sized
		// chunks of recipients
		require_once(DIR_FS_PRONTO.DS.'extlib'.DS.'swift'.DS.'Swift'.DS.'Plugin'.DS.'AntiFlood.php');
		$this->swift->attachPlugin(new Swift_Plugin_AntiFlood(200, 10), 'anti-flood');

		return !!$this->swift->batchSend($this->message, $this->recipients, $this->from);
	}

}

?>
