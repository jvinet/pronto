<?php if(isset($data['id'])): ?>
	<h1><?php _e('Edit Profile') ?></h1>
	<?php $submit = __('Save Changes') ?>
<?php else: ?>
	<h1><?php _e('Create a New Account') ?></h1>
	<?php $submit = __('Create Account') ?>
<?php endif ?>

<?php
$f = array(
	'action'   => url(CURRENT_URL),
	'submit'   => $submit,
	'elements' => array(
		'0' => array(
			'first_name'=> array('prompt'=>__('First Name').':','type'=>'text'),
			'last_name' => array('prompt'=>__('Last Name').':','type'=>'text'),
			'email'     => array('prompt'=>__('Email Address').':','type'=>'text'),
			'password'  => array('prompt'=>__('Login Password').':','type'=>'password'),
			'password2' => array('prompt'=>__('Confirm Password').':','type'=>'password'),
			'sep1'      => array('type'=>'separator'),
		)
	)
);
if($data['is_openid'] || $data['openid']) {
	unset($f['elements']['0']['password'], $f['elements']['0']['password2']);
}
if(!isset($data['id'])) {
	$f['elements'][0] += array(
		'lbl'           => array('prompt'=>__('Enter the letters/numbers you see in this image').':','type'=>'label'),
		'captcha_img'   => array('type'=>'custom','data'=>'<img src="'.url('User','captcha').'"><br />'),
		'captcha'       => array('prompt'=>'','type'=>'text'),
		'sep2'          => array('type'=>'separator'),
	);
}
echo $form->build_form($f, $data, $errors);
?>

