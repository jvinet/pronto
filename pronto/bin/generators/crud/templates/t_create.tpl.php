<?php if(isset($data['id'])): ?>
	<h1><?php _e('Edit _UENTITY_') ?></h1>
<?php else: ?>
	<h1><?php _e('Create a New _UENTITY_') ?></h1>
<?php endif ?>

<?php
$f = array(
	'action'   => $html->url(CURRENT_URL),
	'submit'   => array(__('Create _UENTITY_'),__('Update _UENTITY_')),
	'data_id'  => $data['id'],
	'options'  => array('numcols'=>1, 'toplabel'=>false),
	'layout'   => array(
		'col1' => array('colspan'=>1, 'label_width'=>'auto')
	),
	'elements' => array(
		'col1' => array(
_CREATE_ITEMS_
		)
	)
);
echo $form->build_form($f, $data, $errors);
?>

