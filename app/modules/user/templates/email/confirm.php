<?php echo $user['first_name'] ?>, 

<?php _e("We've received your signup request at %s.", SITE_NAME) ?> 
<?php _e("To confirm your new account, please click the following URL:") ?> 

<?php echo absolute_url('User','confirm').'?t='.$user['confirm_token'] ?> 


<?php _e("Once complete, you can login and set up your profile.") ?> 
 
<?php _e('Thanks for using %s!', SITE_NAME) ?> 

--
<?php echo SITE_NAME ?>

