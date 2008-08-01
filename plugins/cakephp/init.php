<?php

class Cake_Tag extends TagNode {
	function execSrc($name, $args){
		return "call_user_func_array($name, (array)".$this->passVars($args).");";
	}
	function execMethodSrc($class, $method, $args){
		return $this->execSrc("array($$class, '$method')", $args);
	}
	function prepareArguments($args){
	    $arguments = array();
        $current_buffer = &$arguments;
        $named_buffer = array();
        $prev;
		  foreach ($args as $index => $argument) {
		  		if (H2o_Utils::is_string($argument)) {
		  			$argument = substr($argument, 1, -1);
		  		}
	            if (is_string($index)) {
	            	if (is_numeric($prev)) $named_buffer = array();
	            	
	                $current_buffer = &$named_buffer;
	            	$current_buffer[$index] = $argument;
	            }
		  		if (end(array_keys($args)) == $index || is_numeric($index) ){
		  			if (count($named_buffer)){
	                    array_push($arguments, $named_buffer);
	                }
		  		}	  	           	
	            if (is_numeric($index)) {
	            	$current_buffer = &$arguments;
	            	array_push($current_buffer, $argument);
	            }
	            $prev = $index;
		 }
		 return $arguments; 
	}	
}

h2o::add_filters(array(
	'pluralize'=>'Inflector::pluralize',
	'singularize'=>'Inflector::singularize',
));


h2o::add_filter('_', 'h2o_gettext');
function h2o_gettext ($string){
	return __($string, true);
}


h2o::add_filters(array('currency'=>'NumberHelper::curency'));

h2o::add_tag('cake', 'CakeHelper_Tag');
class CakeHelper_Tag extends Cake_Tag {
	var $arguments;
	var $type;
	var $name;
	var $auto_load = true;
	var $helper;
	var $loaded = false;
	function __construct($argstring, &$parser, $position) {
		$args = H2o_Utils::parseArguments($argstring, $position);
		if (count($args)<1) {
			$parser->env->error('Require more than 1 parameter');
		}
		list($name, $type) = explode('.', array_shift($args));
		$this->type = $type;
		$this->name = $name;
		$this->arguments = $this->prepareArguments($args);
		if (isset($parser->storage['helpers'][$this->name])){
			$this->helper = $parser->storage['helpers'][$this->name];
			$this->loaded = true;
		}
	}
	
	function compile(&$context, &$stream) {
		$class = Inflector::classify($this->name);
		
		if ($this->auto_load && !$this->loaded) {
			loadHelper($class);
			$class= $class.'Helper';
			$helper = new $class;
			
			if ($helper) {
				$stream->writeSource('if (!isset($'.$this->name.')) {
					loadHelper("'.$this->name.'");
					$'.$this->name.' = new '.$class.';
				}
				');
			}
		} else {
			$helper = $this->helper;	
		}
		
		if (is_null($helper)) {
			throw new Exception('helper is not loaded');
		} else if (!method_exists($helper, $this->type)) {
			throw new Exception('Cannot found helper method');
		}

		if ($this->name == 'session' && $this->type == "check") {
			if ($this->arguments[0] == 'Message.flash') {
				$tmp = "
				if (\$session->check('Message.flash')):
						\$session->flash();
				endif;
				";
				if (!in_array($tmp, $stream->buffer))
				$output = $tmp;
			}
		} else {
			$output = 'echo '.$this->execMethodSrc($this->name, $this->type, $this->arguments);
		}
	
		$stream->writeSource($output);
	}
}

?>