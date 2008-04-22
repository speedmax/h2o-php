<?php
require '../../h2o.php';


/*	Here is all data 
 * 		I don't care how you get them, or where they from. h2o eats data like snack
 * 		it could come from
 * 			 File, database, manually assigned or data you invented. i don't caure
 * 
 **/
// A Page, typical data coming from text file/xml/sql/feed/remote
$page = array('title'=>'title of a test page',
			'description' => 'this is not funny',
			'content' => '
							Beautiful is better than ugly.
							Explicit is better than implicit.
							Simple is better than complex.
							Complex is better than complicated.
							Flat is better than nested.
							Sparse is better than dense.
							Readability counts.
							Special cases aren\'t special enough to break the rules.
							Although practicality beats purity.
							Errors should never pass silently.
							Unless explicitly silenced.
							In the face of ambiguity, refuse the temptation to guess.
							There should be one-- and preferably only one --obvious way to do it.
							Although that way may not be obvious at first unless you\'re Dutch.
							Now is better than never.
							Although never is often better than *right* now.
							If the implementation is hard to explain, it\'s a bad idea.
							If the implementation is easy to explain, it may be a good idea.
							Namespaces are one honking great idea -- let\'s do more of those!
							',
			'hits' => 200,
			'created' =>'2007-06-22 10:58:00',
			'keywords' => array('testing', 'template', 'PHP'),
			);
/*	Test for Object handling*/
$happy_people = array(
		new Person("Taylor Black"),
		
		new Person("Lamma Vksa"),
		
		new Person("Peter Wong", array('hobbies'	=>	array('Golf', 'Playing pool'))),
		
		new Person("Ming luke",	array('hobbies'		=>	array('Video Game', 'Washing hair'),
									  'age'			=> 29,
									  'password'	=> 'girls are lazy',))
		);
$axis = 'That would be a bad bad person';

$magic_number = 50;


/*
 * 
 * 
 * 	Template engine start
 * 
 * 
 */
/*	prepare data for template 
			Push variable to context, */
$context = compact('page', 'happy_people', 'axis', 'magic_number');

$h2o = new H2O('tests.tpl', array('cache'=> false));

/* Disable caching : 
		for the sake of example*/


/* Evaluate the context: 
		tokenize template, test agains context object, compile into php */
$h2o->evaluate($context);

debug(memory_get_peak_usage());
/*
 * Template engine end
 * 
 */



class Person {
	//If this is set true, h2o will execute those methods
	var $h2o_safe = array('password', 'show_hobbies');
	
	//Set::extract();

	var $name = 'Unknow/Average person';
	var $age = 'unknow';
	var $_password = 'i like apple';
	var $hobbies = array('Stay at home', 'Sleep');
	
	function __construct($name, $options = array()){
		$this->name= $name;
		extract($options);
		if (isset($password)) $this->_password = $password;
		if (isset($age)) $this->age = $age;
		if (isset($hobbies)) $this->hobbies = $hobbies;
	}
	
	function evil_method() {
		return '<font color="red">I am evil</font>';
	}
	
	function show_hobbies(){
		return implode(", ",$this->hobbies);
	}
	
	function password(){
		return md5($this->_password);
	}
}
function debug(){ $args = func_get_args(); foreach($args as $obj ){ echo"<pre>"; print_r($obj); echo "</pre>"; } }

?>
