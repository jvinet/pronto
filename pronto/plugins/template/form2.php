<?php
/**
 * A different approach to building web forms.  See the bottom of this
 * file for a usage example.
 *
 * This design is intended to be more flexible while still aiming for
 * brevity. Instead of wrapping the entire form definition into one
 * array, we register "field generators" that can build each field,
 * then define a "form generator" function that will draw the form
 * using the field generators and any additional PHP/HTML they need.
 **/

class tpForm2 extends Plugin
{
	/**
	 * Constructor
	 */
	function tpForm2()
	{
		$this->Plugin();
	}

	/**
	 * Instantiate a real Form2 object and return it.
	 */
	function construct($name, $attribs=array())
	{
		return new Form2_Real($name, $attribs);
	}
}

class Form2_Real {
	var $form_name = '';
	var $form_data = array();
	var $attribs   = array();

	var $field_generators = null;
	var $form_generator   = null;

	function Form2_Real($name, $attribs=array()) {
		$this->form_name = $name;
		$this->attribs   = $attribs;

		$this->field_generators = new Form2_Generator_Set();
		$this->form_generator   = function(){};

		$form =& Factory::helper('form');

		// default generators
		$this->register('label',    function($o) { echo '<label for="'.$o['for'].'>'.$o['val'].'</label>'; });
		$this->register('text',     function($o) use($form){ echo $form->text($o['name'], $o['val']); });
		$this->register('password', function($o) use($form){ echo $form->password($o['name'], $o['val']); });
		$this->register('select',   function($o) use($form){ echo $form->select($o['name'], $o['val'], $o['options']); });
	}

	/**
	 * Set default form data (values).
	 */
	function set_data($data)
	{
		$this->form_data = $data;
		$this->field_generators->form_data = $data;
	}

	/**
	 * Register a new field generator.
	 * @param string
	 * @param function
	 */
	function register($name, $generator_fn)
	{
		$this->field_generators->generators[$name] = $generator_fn;
	}

	/**
	 * Define the form generator.
	 * @param function
	 */
	function define($generator_fn)
	{
		$this->form_generator = $generator_fn;
	}

	/**
	 * Render the form output.
	 */
	function render()
	{
		ob_start();

		echo '<form name="'.$this->form_name.'" id="'.$this->form_name.'">';
		call_user_func($this->form_generator, $this->field_generators);
		echo '</form>';

		ob_end_flush();
	}
}

class Form2_Generator_Set {
	var $form_data  = array();
	var $generators = array();

	function __call($name, $args)
	{
		if(empty($args)) $args = array(array());

		// If only a single string was passed, use that as the 'name' parameter
		if(count($args) == 1 && is_string($args[0])) {
			$args = array(array('name'=>$args[0], 'val'=>$this->form_data[$args[0]]));
		}
		if(!isset($args[0]['val']) && isset($args[0]['name'])) {
			$args[0]['val'] = $this->form_data[$args[0]['name']];
		}

		if(isset($this->generators[$name])) {
			call_user_func_array($this->generators[$name], $args);
		}
	}
}


/**
 * Example usage:
 *
<?php $f = new Form('signup-form', array('action'=>url(CURRENT_URL))); ?>
<?php $f->set_data(array('username'=>'Judd', 'email'=>'jvinet@gmail.com')) ?>
<?php $f->register('captcha', function($o){ echo '<img src="'.url('User_Signup','captcha').'" />'; }) ?>
<?php $f->define(function($g) use($form, $timezones) { ?>

	<label for="username">Username:</label>
	<?php $g->text('username') ?>
	<?php echo $form->tooltip("Do stuff") ?><br />

	<label for="email">Email:</label>
	<?php $g->text('email') ?><br />

	<label for="password">Password:</label>
	<?php $g->password('password') ?><br />

	<label for="password2">Confirm Password:</label>
	<?php $g->password('password2') ?><br />

	<label for="timezone">Timezone:</label>
	<?php $g->select(array('name'=>'timezone', 'options'=>$timezones)) ?><br />

	<div class="divider"></div>

	<label>Picture:</label>
	<?php $g->captcha() ?><br />

	<label for="captcha">Characters:</label>
	<?php $g->text('captcha') ?>

<?php }); ?>
<?php $f->render(); ?>

<style type="text/css">
	.helpicon {
		position: relative;
		top: 2px;
	}
	form label {
		width: 150px;
		padding-left: 24px;
		display: block;
		float: left;
		height: 16px;
		text-align: right;
		margin-right: 10px;
	}
	form input,
	form select {
		width: 200px;
		margin-bottom: 15px;
	}
</style>
*/

?>
