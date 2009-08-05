<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET ?>">
	<title><?php echo SITE_NAME ?></title>
	<?php echo $html->favicon('favicon') ?>
	<?php echo $html->css('layout') ?>
	<?php echo $html->css('ui') ?>
	<?php echo $html->css('form') ?>
	<?php echo $html->css('nav') ?>
	<?php echo $html->js('core') ?>
	<script type="text/javascript">pronto = new Pronto("<?php echo DIR_WS_BASE ?>");</script>
	<?php echo $html->js('jq/jquery') ?>
	<?php echo $html->js('jq/jquery.menu') ?>
	<?php echo $html->js('flash-gritter') ?>
	<!--[if lt IE 8]><script src="http://ie7-js.googlecode.com/svn/version/2.0(beta3)/IE8.js" type="text/javascript"></script><![endif]-->
	<script type="text/javascript">$(function(){ $('ul.nav').menu({minWidth: 120, arrowSrc: '<?php echo url('/img/icons/arrow_right.gif') ?>'});});</script>
	<?php echo $HTML_HEAD ?>
</head>

<body>
<div class="container">
	<div id="header">
		<div class="title"><?php echo SITE_NAME ?></div>
		<div class="status">
			<?php echo date('l F d, Y') ?> <span class="dark">|</span>
			<?php echo date('g:i a') ?> <span class="dark">|</span>
			<?php if(ACCESS_ID): ?>
				<?php _e('Logged in as %s', "<b>{$_SESSION['USER']['email']}</b>") ?></b> <span class="dark">|</span>
				<?php echo $html->link(__('Logout'), url('/logout')) ?>
			<?php else: ?>
				<?php _e('Not logged in') ?> <span class="dark">|</span>
				<?php echo $html->link(__('Login'), url('/login')) ?>
			<?php endif ?>
		</div>
		<div id="flash"></div>
	<?php if(isset($FLASH_MESSAGE)): ?>
		<script type="text/javascript">$(function(){ flash_set('<?php echo str_replace("'", "\\'", $FLASH_MESSAGE) ?>'); })</script>
	<?php elseif(is_array($languages) && count($languages)): ?>
		<div class="language">
			<div id="lang-form-div" style="display:none">
				<?php echo $form->open_form('lang-form', url('/set_lang'), 'get') ?>
				<?php _e('Language:') ?>
				<?php echo $form->select('lang', $curr_lang, $languages) ?>
				<?php echo $form->close_form() ?>
				<script type="text/javascript">$(function(){ $('#lang-form_lang').change(function(){ $('#lang-form').submit(); }); });</script>
			</div>
			<a id="lang-a" href="#"><?php _e('Change Language') ?></a>
			<script type="text/javascript">$(function(){ $('#lang-a').click(function(){ $(this).blur().fadeOut('normal', function(){ $('#lang-form-div').fadeIn('normal'); }); }); });</script>
		</div>
	<?php endif ?>
		<?php if(ACCESS_ID) echo $navigation->menu() ?>
	</div>

	<div class="content">
		<?php echo $CONTENT_FOR_LAYOUT ?>
	</div>

	<div class="footer">
		<div class="badge">
			<?php echo $html->link($html->image('badge.png', __('Powered by Pronto')), 'http://www.prontoproject.com') ?>
		</div>
		<div class="copyright">
			<?php _e('Copyright') ?> &copy; 2006-<?php echo date('Y') ?> Judd Vinet
		</div>
	</div>
</div>
</body>
</html>
