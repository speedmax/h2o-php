<?php
/*
		Nodes
*/

class H2o_Node {
    var $position;
	function __construct($argstring) {}
	
	function render($context, $stream) {}
}

class NodeList extends H2o_Node implements IteratorAggregate  {
	var $parser;
	var $list;
	
	function __construct(&$parser, $initial = null, $position = 0) {
	    $this->parser = $parser;
        if (is_null($initial))
            $initial = array();
        $this->list = $initial;
        $this->position = $position;
	}

	function render($context, $stream) {
		foreach($this->list as $node) {
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
    
    function getIterator() {
        return new ArrayIterator( $this->list );
    }
}

class VariableNode extends H2o_Node {
    private $filters = array();
    var $variable;
    
	function __construct($variable, $filters, $position = 0) {
        if (!empty($filters))
            $this->filters = $filters;
		$this->variable = $variable;
	}

	function render($context, $stream) {
        $value = $context->resolve($this->variable);
        $value = $context->applyFilters($value, $this->filters);
		$stream->write($value);
	}
}

class CommentNode extends H2o_Node {}

class TextNode extends H2o_Node {
    var $content;
	function __construct($content, $position = 0) {
		$this->content = $content;
		$this->position = $position;
	}
	
	function render($context, $stream) {
		$stream->write($this->content);
	}
	
	function is_blank() {
	    return strlen(trim($this->content));
	}
}


?>