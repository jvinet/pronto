<?php
/*
 * Configuration for the User module.
 *
 */

// Enable user registrations
define('USER_ENABLE_REGISTRATION', false);

// Enable use of Open ID for logins
define('USER_USE_OPENID', true);

/*
 * A unique string that will be used to encrypt passwords. It is recommended
 * that you make use a salt that supports the BCrypt/Blowfish algorithm, as
 * it is more secure than MD5/SHA1.
 *
 * See http://php.net/manual/en/function.crypt.php for more info.
 */
define('USER_HASH_SALT', '$2a$10MO23zxU5SG/j8ED8igmxgL');

?>
