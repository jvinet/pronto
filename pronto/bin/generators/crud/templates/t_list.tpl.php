<h1><?php _e('_UENTITY_s') ?></h1>
<?php echo $html->link_button(__('Create a _UENTITY_'), url('_UENTITY_','create'), 'icons/add.gif', '', false, array('style'=>'float:right')) ?>
<br /><br />

<?php
echo $table->build_grid(array(
	'columns'  => array(
		'_OPTIONS_'   => array(
			'edit'   => $html->link($html->image('icons/edit.gif', array('title'=>__('Edit Item'))), url('_UENTITY_','edit').'?id=<id>'),
			'delete' => $html->link($html->image('icons/delete.gif', array('title'=>__('Delete Item'))), url('_UENTITY_','delete').'?id=<id>', __('Are you sure?'))),
_LIST_ITEMS_
	),
	'data'    => $data,
	'perpage' => $perpage,
	'curpage' => $curpage,
	'rows'    => $totalrows
));

?>
