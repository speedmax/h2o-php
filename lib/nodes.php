<?php
class Node {
	var $format;
	function __construct(){
	}
		
	function compile(&$context, &$stream){
	}
	

	function toString(){
	}

	function strFormat($tag, $data = ''){
		if (!defined('PHP_'.$tag)) return ;
		$result = sprintf(constant('PHP_'.$tag), $data);
		$result = sprintf(constant('PHP_TAG'), $result);
		return $result;
	}
}

class NodeList extends Node {
	var $list;
	function __construct(&$parser, $initial=null, $position=0) {
        if (is_null($initial))
            $initial = array();
        $this->list = $initial;
        $this->position = $position;
        $this->parser =& $parser;
	}

	function compile(&$context, &$stream){
		foreach($this->list as $node){
			$node->compile($context, $stream);
		}
	}
	
    function append(&$node) {
        array_push($this->list, $node);
    }

    function extend(&$nodes) {
        array_merge($this->list, $nodes);
    }

    function getLength() {
        return count($this->list);
    }
}

class CommentNode extends Node {
	var $content;
	function __construct($argstring, $position = 0){
		parent::__construct();
		
		$this->content = $argstring;
	}

	function compile(&$context, &$stream){
		$stream->write('');
	}
}

class TextNode extends Node {
	var $content;
	function __construct($argstring, $position = 0){
		parent::__construct();
		$this->content = $argstring;
	}
	function compile(&$context, &$stream){
		$stream->write($this->content);
	}
}

class VariableNode extends Node {
	var $filters = array();
	var $objects = array();
	
	function __construct(&$objects, &$filters, $position = 0){
		$this->filters = $filters;
		$this->objects = $objects;
	}
	function compile(&$context, &$stream){
		//resolve variable
		
		$variable = array();
		foreach($this->objects as $i => $data){
			$result = $context->resolve($data, true);
			if (!empty($result)) {
			 array_push($variable, $result);  
			}
		}
		$variable = join('.', $variable);
		
		if (empty($variable)) {
			
			return null;
		}
		
		if(!empty($this->filters) && !empty($variable)){
			$variable = H2o_Utils::applyFilters($variable ,$this->filters);
		}
		$stream->write($this->strFormat('PRINT', $variable));
	}
}

class ErrorNode extends Node {
	function compile(&$context, &$stream){
	}
}


?>