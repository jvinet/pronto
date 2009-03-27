<?php
error_reporting(E_ALL & ~E_NOTICE);
define('DS', DIRECTORY_SEPARATOR);

/*
 * Locale Settings
 */
define('CHARSET', 'UTF-8');

/*
 * Mail Settings
 */
define('SMTP_HOST', 'smtp.eastlink.ca');
define('SMTP_USER', '');
define('SMTP_PASS', '');

/*
 * Session Settings
 */
define('SESSION_USEDB',    true);    // enable DB session storage
define('SESSION_COOKIE',  'PRONTO'); // name of session cookie
define('SESSION_IDLETIME', 86400);   // time session can be idle w/o dying
define('SESSION_LIFETIME', 86400);   // set to '0' for browser-close

/*
 * Debug Settings
 */
define('DEBUG', true);

/*
 * If using a web server that doesn't honor .htaccess files (eg, LigHTTPd),
 * set an explicit dispatch URL.
 */
//define('DISPATCH_URL', '/index.php');

/*
 * Site Settings
 */
define('SITE_NAME',   'Pronto');
define('ADMIN_EMAIL', 'admin@example.com');
// You can leave this blank for auto-detection, though it's better to
// specify it if you can. Otherwise commandline scripts will not know what
// your base site URL is, since the regular $_SERVER values are not
// available.
//
// Do not include any trailing path, as that part is set in DIR_WS_BASE.
// Only specify the "http://www.example.com" portion.
define('SITE_URL_BASE', '');

/*
 * Filesystem/URL Path Settings
 *   - DIR_WS_BASE   :: Location of the web app relative to the server's web root
 *   - DIR_FS_BASE   :: Full path to the web app (where index.php lives)
 *   - DIR_FS_PRONTO :: Full path to the Pronto code (the pronto/ directory)
 *   - DIR_FS_APP    :: Full path to the Application code (the app/ directory
 */
define('DIR_WS_BASE',  '/pronto');
define('DIR_FS_BASE',  '/home/httpd/html/pronto');
define('DIR_FS_PRONTO', DIR_FS_BASE.DS.'pronto');
define('DIR_FS_APP',    DIR_FS_BASE.DS.'app');

/*
 * Enable Plugins/Helpers/Modules (space-delimited)
 */
define('PLUGINS', 'mailer image file os');
define('HELPERS', 'html form table pager navigation ajax');
define('MODULES', 'user');

/*
 * Cache Settings
 */
require_once(DIR_FS_APP.DS.'config'.DS.'cache.php');

?>
