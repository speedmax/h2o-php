<?php
/*
		Nodes
*/

class Node {
    var $position;
	function __construct($parser, $argstring) {}
	
	function render($context, $stream) {
		$stream->write('');
	}
}

class NodeList extends Node {
	var $list;
	var $parser;
	
	function __construct($parser, $initial = null, $position = 0) {
	    $this->parser = $parser;
        if (is_null($initial))
            $initial = array();
        $this->list = $initial;
        $this->position = $position;
	}

	function render($context, $stream){
		foreach($this->list as $node){
			$node->render($context, $stream);
		}
	}
	
    function append($node) {
        array_push($this->list, $node);
    }

    function extend($nodes) {
        array_merge($this->list, $nodes);
    }

    function getLength() {
        return count($this->list);
    }
}

class VariableNode extends Node {
  private $filters = false;
  private $variables;
  
	function __construct($variables, $filters, $position = 0) {
	  if (!empty($filters))
        $this->filters = $filters;

		$this->variables = $variables;
	}
	
	function render($context, $stream) {
		$value = $context->resolve($this->variables[0]);
		if ($this->filters)
		  $value = $context->applyFilters($value, $this->filters);
		$stream->write($value);
	}
}

class CommentNode extends Node {}

class TextNode extends Node {
	function __construct($content, $position = 0) {
		$this->content = $content;
		$this->position = $position;
	}
	
	function render($context, $stream) {
		$stream->write($this->content);
	}
}

class Tag extends Node {}

?>