<h1><?php _e('Users') ?></h1>
<?php echo $html->link_button(__('Create a User'), url('User_Admin','create'), 'icons/add.gif', '', false, array('style'=>'float:right')) ?>
<br /><br />

<?php $html->css_load('grid') ?>
<?php $html->js_load('pronto.grid') ?>

<?php
	/**
	 * This is a stub for testing the new Javascript version of the rich Grid.
	 * It is still in development, and probably not ready for general use.
	 * 
	 * But you can play with it if you want.  :)
<table id="grid1" class="grid"></table>
<script type="text/javascript">
$(function(){
	var grid = new Pronto.UI.Grid($('#grid1'), '', {
		options: {},
		columns: {
			first_name:  {label:'First Name'},
			last_name:   {label:'Last Name'},
			email:       {label:'Email'},
			status:      {label:'Status', type:'select', options:['active','pending','delete']},
			access_keys: {label:'Access Keys', type:'select', options:['Admin','User']},
			last_login:  {label:'Last Login', type:'date'}
		}
	});
	grid.build();
});
</script>
<?php */ ?>

<?php
echo $table->build_grid(array(
	'options'  => array('ajax'=>true),
	'columns'  => array(
		'_OPTIONS_'   => array(
			'edit'   => $html->link($html->image('icons/edit.gif', array('title'=>__('Edit Item'),'class'=>'ajax_action')), url('User_Admin','edit').'?id=<id>'),
			'delete' => $html->link($html->image('icons/delete.gif', array('title'=>__('Delete Item'))), url('User_Admin','delete').'?id=<id>', __('Are you sure?'))),
		'first_name'  => array('label'=>__('First Name')),
		'last_name'   => array('label'=>__('Last Name')),
		'email'       => array('label'=>__('Email')),
		'status'      => array('label'=>__('Status'),'type'=>'select','options'=>array_hash(array('active','pending','deleted'))),
		'access_keys' => array('label'=>__('Access Keys'),'type'=>'select','options'=>array_hash($access_keys)),
		'last_login'  => array('label'=>__('Last Login'),'type'=>'date','date_format'=>'Y-m-d'),
		'_MULTI_' => array(
			'delete' => $html->button(__('Delete'), url('User','delete'), __('Are you sure?'), false, 'multi')
		)
	),
	'data'    => $data,
	'perpage' => $perpage,
	'curpage' => $curpage,
	'rows'    => $totalrows
));

?>
