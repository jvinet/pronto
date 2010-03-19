<p>
	<?php _e('Welcome to the') ?>
	<strong><?php _e('Pronto Framework') ?></strong>
</p>

<?php if(ACCESS_ID): ?>
	<p><?php _e('You are logged in.') ?></p>
<?php else: ?>
	<p><?php _e('You are not logged in.') ?></p>
	<p><?php _e('The default administration credentials are: admin@example.com / pronto') ?></p>
<?php endif ?>

<br />
<p>
	<?php _e("If this is your first time working with Pronto, you may want to consult the documentation:") ?>
	<ul>
		<li><?php echo $html->link(__("Manual"), $_SERVER['REQUEST_URI'].'doc/manual.html') ?></li>
		<li><?php echo $html->link(__("API Reference"), $_SERVER['REQUEST_URI'].'doc/api/index.html') ?></li>
	</ul>
</p>

