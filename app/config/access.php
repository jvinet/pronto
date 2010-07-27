<?php

/**
 * Defines the access model that will be used in this web application.
 *
 * "roles":     Within $ACCESS_KEYS, each array key is a role or group
 *              name, and the array value is an array of access keys that
 *              will be assigned to that role.  A user can be assigned to
 *              zero or more roles.  The role names themselves will be
 *              stored in the DB.  At login time, they will be resolved
 *              to the individual access keys and assigned.
 * "discrete":  The structure of $ACCESS_KEYS is the same as in "roles"
 *              mode.  However, in this model, the actual keys themselves
 *              are assigned to the user within the DB, so you can pick
 *              and choose which keys from which modules get assigned.
 *
 * If you're not sure which one to use, start with "roles".
 * Conceptually, it is simpler.
 *
 */
define('ACCESS_MODEL', 'roles');

/* This is the default access list.  There are two roles in the system,
 * USER and ADMIN.  Each user is assigned to one role only, so the
 * "Administrator" group includes access keys for both USER and ADMIN-level
 * functions.
 *
 * TIP: It is possible to assign a user to more than role if your
 * application requires this.  Simply use a "multiselect" widget instead of
 * a regular select, so the interface allows multiple selections.
 *
 * TIP: One role can inherit access keys from another role.  Just name the
 *      access key the same name as another role and prefix it with an '@'.
 *      Example:
 *        $ACCESS_KEYS = array(
 *          'Free'    => array('USER'),
 *          'Premium' => array('@Free', 'FEATURE_1', 'FEATURE_2')
 *        );
 */
$ACCESS_KEYS = array(
	'User'          => array('USER'),
	'Administrator' => array('USER','ADMIN')
);


/* An example of a "discrete" access list, organized by module.  You could
 * assign entire key sets by module, or allow finer-grained control
 * by assigning specific keys.  Remember that access keys must be unique
 * across all modules.
$ACCESS_KEYS = array(
	'USER' => array('user_insert','user_edit','user_view','user_delete'),
	'BLOG' => array('blog_create','post_create','edit_other_posts')
);
*/

/* An example of a "roles" access list.  Using a setup like this, you
 * will probably only assign one group per user, since access keys might
 * be duplicated across different groups.
$ACCESS_KEYS = array(
	'USER'  => array('news_view','stats_view','profile_edit'),
	'ADMIN' => array('news_view','news_edit','stats_view','stats_edit','profile_edit')
);
*/

?>
