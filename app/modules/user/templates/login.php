<?php $html->css_load('login') ?>
<?php $html->css_load('modal') ?>

<div class="login">
<h2><?php _e('Login') ?></h2>
	<?php echo $form->open_form('login-normal', url('User_Auth','login')) ?>
		<?php if($return_url) echo $form->hidden('return_url', $return_url) ?>
		<?php echo $form->text('email', 'email address', '', '', array('class'=>'text','onFocus'=>"this.value=''")) ?>
		<?php echo $form->password('password', 'password', '', '', array('class'=>'text','onFocus'=>"this.value=''")) ?>
		<?php if(USER_USE_OPENID === true) echo $html->link($html->image('icons/openid_black.gif', 'Login with OpenID'), '#', '', false, array('id'=>'btn-openid','style'=>'float:left')) ?>
		<input type="submit" value="" class="login" />
		<br class="spacer" />
	<?php echo $form->close_form() ?>

	<?php echo $form->open_form('login-openid', url('User_Auth','login__openid'), 'post', array(), array('style'=>'display:none')) ?>
		<?php if($return_url) echo $form->hidden('return_url', $return_url) ?>
		<?php echo $form->text('openid_url', 'openid url', '', '', array('class'=>'text','onFocus'=>"this.value=''")) ?>
		<?php echo $html->link($html->image('icons/user.gif', 'Login with email/password'), '#', '', false, array('id'=>'btn-normal','style'=>'float:left')) ?>
		<input type="submit" value="" class="login" />
		<br class="spacer" />
	<?php echo $form->close_form() ?>
	<p class="bottom"></p>
</div>

<?php if(USER_USE_OPENID === true): ?>
<script type="text/javascript">
$(function(){
	$('#btn-openid').click(function(){
		$('#login-normal').slideUp();
		$('#login-openid').slideDown();
	});
	$('#btn-normal').click(function(){
		$('#login-openid').slideUp();
		$('#login-normal').slideDown();
	});
});
</script>
<?php endif ?>

<?php
if(isset($error)) {
	echo '<div class="error login-error">'.$error.'</div>';
}
?>

<div class="login-opts">
	<?php echo $html->link(__('Forgot Password?'), url('User','resetpass'), '', false, array('id'=>'resetpass-btn')) ?>
	<?php if(USER_ENABLE_REGISTRATION) echo ' | '.$html->link(__('New Account'), url('User','signup')) ?>
</div>

<div id="resetpass-dlg" title="<?php _e('Reset Password') ?>" style="width:420px;height:200px">
	<?php $nosubmit = true; include('resetpass.php'); ?>
</div>
<?php
$btns = array(
	__('OK')     => array('action'=>'submit_form', 'form_id'=>'resetpass-frm'),
	__('Cancel') => array('action'=>'close')
);
$ajax->dialog('resetpass-dlg', '#resetpass-btn', $btns, true, false);
?>

