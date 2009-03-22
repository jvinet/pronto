<p>
	<?php _e('Enter your email address below.  We will generate a new password and email it to you.') ?>
</p>

<?php
$f = array(
	'action'   => url('User','resetpass'),
	'submit'   => __('Reset Password'),
	'form_id'  => 'resetpass-frm',
	'layout'   => array(
		'0' => array('colspan'=>1, 'label_width'=>'100px')
	),
	'elements' => array(
		'0' => array(
			'email' => array('prompt'=>__('Email Address').':','type'=>'text'),
		)
	)
);
if($nosubmit) $f['options']['nosubmit'] = true;
echo $form->build_form($f, $data, $errors);
?>

