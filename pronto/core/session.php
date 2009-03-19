<?php
/**
 * PRONTO WEB FRAMEWORK
 * @copyright Copyright (C) 2006, Judd Vinet
 * @author Judd Vinet <jvinet@zeroflux.org>
 *
 * Description: Session facilities.
 *
 **/

/**
 * This class replaces the default session handler with a
 * DB-based one.
 */
class Session_DB
{
	var $db = null;

	/**
	 * Constructor
	 *
	 * @param object $db DB access object
	 */
	function Session_DB(&$db)
	{
		$this->db =& $db;
		
		session_set_save_handler(
			array($this, 'open'),
			array($this, 'close'),
			array($this, 'read'),
			array($this, 'write'),
			array($this, 'destroy'),
			array($this, 'gc'));
	}

	/**
	 * Close a session
	 *
	 * @return bool
	 */
	function close()
	{
		return true;
	}

	/**
	 * Destroy a session
	 *
	 * @param string $id Session ID
	 * @return bool
	 */
	function destroy($id)
	{
		$this->db->execute("DELETE FROM sessions WHERE id='%s'", array($id));
		return true;
	}

	/**
	 * Run garbage collection
	 *
	 * @param int Timestamp threshold.  Remove entries older than this.
	 * @return bool
	 */
	function gc($lifetime)
	{
		global $DB;
		$t = time() - $lifetime;

		$this->db->execute("DELETE FROM sessions WHERE lastupdate<%i", array($t));
		return true;
	}

	/**
	 * Open a new session
	 *
	 * @param string $path
	 * @param string $name
	 * @return bool
	 */
	function open($path, $name)
	{
		return true;
	}

	/**
	 * Read a session from DB
	 *
	 * @param string $id Session ID
	 * @return string Serialized session data
	 */
	function read($id)
	{
		$data = $this->db->get_item("SELECT data FROM sessions WHERE id='%s'", array($id));
		if(!$data) {
			$this->db->execute("INSERT INTO sessions (id,lastupdate,data) VALUES ('%s',%i,'')", array($id,time()));
			return '';
		}
		return $data['data'];
	}

	/**
	 * Write a session to DB
	 *
	 * @param string $id Session ID
	 * @param string $data Serialized session data
	 * @return bool
	 */
	function write($id, $data)
	{
		$this->db->execute("UPDATE sessions SET data='%s',lastupdate=%i WHERE id='%s'", array($data,time(),$id));
		return true;
	}

}

/**
 * Start the session.  In a normal scenario, the session ID is automatically
 * fetched from the cookie name (as defined by SESSION_COOKIE).  However, if
 * you already know the session ID, you can bypass all the cookie stuff and
 * start the session manually.
 *
 * This is useful in situations where cookies aren't available.  For example,
 * Flash does not pass cookies with HTTP requests.
 *
 * @param string $sess_id If set, use this session ID instead of looking in
 *                        the session cookie.
 * @return none
 */
function start_session($sess_id='')
{
	if(session_id()) return;
	if($sess_id) session_id($sess_id);
	ini_set('session.use_trans_sid', 0);
	ini_set('session.use_cookies', 1);
	ini_set('session.use_only_cookies', 1);
	ini_set('session.cookie_lifetime', SESSION_LIFETIME);
	ini_set('session.gc_maxlifetime', SESSION_IDLETIME);
	ini_set('session.gc_probability', 10);
	ini_set('session.gc_divisor', 100);
	ini_set('session.name', SESSION_COOKIE);
	if(defined('SESSION_USEDB') && SESSION_USEDB === true) {
		$db =& Registry::get('pronto:db');
		$session = new Session_DB($db);
		Registry::set('pronto:session', $session);
	}
	if(isset($_COOKIE[SESSION_COOKIE]) && SESSION_LIFETIME > 0) {
		// reset the timeout
		setcookie(SESSION_COOKIE, $_COOKIE[SESSION_COOKIE], time()+SESSION_LIFETIME, "/");
	}
	session_name(SESSION_COOKIE);
	session_start();
}

?>
