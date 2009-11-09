<?php
	switch(true) {
		case !empty($user['first_name']): $n = "Hello {$user['first_name']},"; break;
		case !empty($user['name']):       $n = "Hello {$user['name']},"; break;
		default:                          $n = "Hello,"; break;
	}
	echo "$n\n";
?>

<?php _e("We've received a request to reset your password on %s.", SITE_NAME) ?> 
<?php _e("Your temporary password is:") ?> 

<?php echo $password ?> 


<?php _e("You can login here") ?>:
<?php echo absolute_url('User_Auth','login') ?> 
 
<?php _e("After you login, we recommend you change your password by editing your profile.") ?> 
<?php _e("If you run into any difficulty with the new login details please contact us.") ?> 

<?php _e('Thanks') ?>,

--
<?php echo SITE_NAME ?>

