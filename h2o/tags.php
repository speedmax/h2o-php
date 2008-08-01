<?php

class IfTag extends Tag {
    var $syntax = '//';
    function __construct($argstring, $parser, $position = 0) {
        $this->body = $parser->parse('endif', 'else');
        
        if ($parser->token->content === 'else')
            $this->else = $parser->parse('endif');
    }
    
    function render($context, $stream) {
        $this->body->render($context, $stream);
    }
}

class ForTag extends Tag {
    var $position;
    private  $iteratable, $key, $item, $body, $else;
    private $syntax = '/(\w+)\s+in\s+(\w+)\s*(reversed)?/';

    function __construct($argstring, $parser, $position) {
        $this->body = $parser->parse('endfor', 'else');
        
        if ($parser->token->content === 'else')
            $this->else = $parser->parse('endfor');

        if (preg_match($this->syntax, $argstring, $match)) {
            @list(,$this->item, $this->iteratable, $this->reversed) = $match;
            $this->reversed = (bool) $this->reversed;
            $this->key = 'key_';
        } else {
            throw TemplateSyntaxError("Invalid for loop syntax ");
        }
    }
    
    function render($context, $stream) {
        $iteratable = $context->resolve($this->iteratable);
        if ($this->reversed)
            $iteratable = array_reverse($iteratable);
        $length = count($iteratable);
        
        if ($length) {
            $parent = $context->resolve('loop');
            $context->push();
            $rev_count = $is_even = $idx = 0;
            foreach($iteratable as $key => $value) {
                $is_even =  $idx % 2;
                $rev_count = $length - $idx;
                
                $context->set($this->item, $value);
                $context->set('loop', array(
                    'parent' => $parent,
                    'first' => $idx === 0, 
                    'last'  => $rev_count === 1,
                    'odd'	=> ! $is_even,
                    'even'	=> $is_even,
                    'length' => '',
                    'counter' => $idx + 1,
                    'counter0' => $idx,
                    'revcounter' => $rev_count,
                    'revcounter0' => $rev_count - 1
                ));    
                $this->body->render($context, $stream);
                ++$idx;                
            }
            $context->pop();
        } else {
            $this->else->render($context, $stream);
        }
    }
}

class BlockTag extends Tag {
    var $name;
    var $position;
    private $stack;
    private $syntax = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';
    
    function __construct($argstring, $parser, $position) {
        if (!preg_match($this->syntax, $argstring))
            throw new TemplateSyntaxError('Block tag expects a name, example: block [content]');

        $this->name = $argstring;
        $this->stack = array($parser->parse('endblock', "endblock {$this->name}"));
        $blocks =& $parser->storage['blocks'];
        
        if (isset($blocks[$this->name]))
            throw new TemplateSyntaxError('Block name exists, Please select a different block name');
            
        $blocks[$this->name] =& $this;
        $this->position = $position;
    }

    function addLayer(&$nodelist){
        $this->stack[] = $nodelist;
    }

    function render($context, $stream, $index = 1) {
        $context->push(array(
			'block' => new BlockContext($this, $context, $stream, $index)
        ));
        $key = count($this->stack) - $index;
        
        if (isset($this->stack[$key])) {
            $this->stack[$key]->render($context, $stream);
        }
        $context->pop();
    }
}

class ExtendsTag extends Tag {
    var $filename;
    protected $nodelist;
    
    function __construct($argstring, $parser, $position = 0) {
		if (!$parser->first)
		    throw new TemplateSyntaxError('extends must be first in file');

        if (!preg_match('/^["\'](.*?)["\']$/', $argstring))
		    throw new TemplatesyntaxError('filename must be quoted');
        
		$this->filename = substr($argstring, 1, -1);
		$this->nodelist = H2o::load($this->filename, $parser->options);

		$parser->parse();

		if (!isset($this->nodelist->parser->storage['blocks']) || !isset($parser->storage['blocks'])) {
		    return ;
		}

		# Blocks of parent template
		$blocks = $this->nodelist->parser->storage['blocks'];
		
		foreach($parser->storage['blocks'] as $name => $block) {
		    if (isset($blocks[$name]))
		        $blocks[$name]->addLayer($block);
		}
    }
    
    function render($context, $stream) {
        $this->nodelist->render($context, $stream);
    }
}

H2o::addTag(array('Block', 'Extends', 'If', 'For'));
?>