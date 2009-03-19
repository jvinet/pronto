<?php if(isset($data['id'])): ?>
	<h1><?php _e('Edit User') ?>: <?php echo $data['first_name'].' '.$data['last_name'] ?></h1>
<?php else: ?>
	<h1><?php _e('Create a New User') ?></h1>
<?php endif ?>

<?php
$f = array(
	'action' => url(CURRENT_URL),
	'submit' => array(__('Create User'), __('Update User')),
	'data_id' => $data['id'],
	'options' => array('numcols'=>2,'toplabel'=>false),
	'layout' => array(
		'col1' => array('colspan'=>1, 'label_width'=>'40%'),
		'col2' => array('colspan'=>1, 'label_width'=>'40%'),
	),
	'elements' => array(
		'col1' => array(
			'access_keys' => array('prompt'=>__('Access Level:'), 'type'=>'select', 'options'=>array_hash($access_keys), 'help'=>__('Access levels determine the amount of control a user will have.')),
			'first_name'  => array('prompt'=>__('First Name:'), 'type'=>'text'),
			'last_name'   => array('prompt'=>__('Last Name:'), 'type'=>'text'),
			'email'       => array('prompt'=>__('Email Address:'), 'type'=>'text', 'help'=>__('Email address must be unique, as it will be used for login purposes.')),
		),
		'col2' => array(
			'language'  => array('prompt'=>__('Language:'), 'type'=>'select', 'options'=>$languages),
			'status'    => array('prompt'=>__('Status:'),'type'=>'select','options'=>array_hash(array('active','pending','deleted'))),
			'password'  => array('prompt'=>__('Login Password:'), 'type'=>'password'),
			'password2' => array('prompt'=>__('Confirm Password:'), 'type'=>'password'),
		)
	)
);
if($data['openid']) {
	unset($f['elements']['col2']['password'], $f['elements']['col2']['password2']);
	$f['elements']['col2']['openid'] = array('prompt'=>__('Open ID:'), 'type'=>'text', attribs=>array('readonly'=>'readonly'));
}

// Statistics table
$html->css_load('grid');  // load the 'grid' CSS
$stats = $table->build_table(array(
	'class' => 'grid',
	'rows'  => array(
		array(__('Created On:'), $data['created_on']),
		array(__('Last Login:'), $data['last_login']),
		array(__('Status:'),     ucfirst($data['status'])),
	)
));

// Put them together in a tabbed form
echo $form->build_tabbed_form(array(
		'action'  => url(CURRENT_URL),
		'data_id' => $data['id'],
		'submit'  => array(__('Create User'),__('Update User')),
		'spinner' => 'spinner.gif',
	), array(
		'tab1' => array('label'=>__('User Info'), 'form'=>$f),
		'tab2' => array('label'=>__('Statistics'), 'content'=>$stats),
	), $data, $errors);
?>

