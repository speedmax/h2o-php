<?php
class TagNode {
	function __construct($argstring, &$parser, $position) {}

	function compile(&$context, &$stream) {}

	function strFormat($tag, $data = ''){
		if (!defined('PHP_'.$tag)) return ;
		$result = sprintf(constant('PHP_'.$tag), $data);
		$result = sprintf(constant('PHP_TAG'), $result);
		return $result;
	}

	function parseArguments($argstring, $position){
		return H2o_Utils::parseArguments($argstring, $position);
	}

	function passVars($args){
		return 'unserialize("'.str_replace('$','\$',addslashes(serialize($args))).'")';
	}
}

h2o::add_tag('if', 'IfCondition_Tag');
class IfCondition_Tag extends TagNode {

	var $body;
	var $elseBody;
	var $arguments;
	var $comparision;

	function __construct($argstring, &$parser, $position) {
		$args = $this->parseArguments($argstring, $position);
		$this->comparision = $this->_parseIsCompare($args);

		$this->arguments = $args;
		$this->body = $parser->parse('else', 'endif');

		$token = $parser->pop_token();

		if ($token->content == 'else'){
			$this->elseBody = $parser->parse('endif');
		}
	}

	function _parseIsCompare(& $args) {
		$rules = array('numeric'=>'number','float','integer','int', 'real','numeric',
		'string', 'bool'=>'boolean', 'array', 'object', 'true', 'false', 'null');
		$result =array();
		foreach($args as $index=>$arg) {
			if ($arg == 'is') {
				if (in_array($args[$index+1],  $rules)) {
					$value = array_search($args[$index+1], $rules);
					if (is_numeric($value)) $value = $rules[$value];
					$result[$args[$index-1]] = $value;
				}
				unset($args[$index], $args[$index+1]);
			}
		}
		return $result;
	}

	function compile(& $context, & $stream) {
		$result =array();
		foreach ($this->arguments as $arg) {
			if (is_array($arg) && isset($arg['operator'])) {
				$result[] = $arg['operator'];
			} else {
				$name = $arg;
				$arg = $context->resolve($arg, true);
				if (in_array($name, array_keys($this->comparision))) {
					if (!in_array($this->comparision[$name], array('true', 'false')))
					$arg = 'is_'.$this->comparision[$name].'('.$arg.')';
					else
					$arg = '(bool) '.$arg;
				}
				if ($arg == null) {
					$arg = 'null';
				}
				$result[] = $arg;
			}
		}
		$result = $this->strFormat('IF', join(' ', $result));
		$stream->write($result);
		$this->body->compile($context, $stream);

		if ($this->elseBody) {
			$stream->write( $this->strFormat('ELSE'));
			if ($this->elseBody)
			$this->elseBody->compile($context, $stream);
		}
		$stream->write($this->strFormat('ENDIF'));
	}
}

h2o::add_tag('for', 'ForLoop_Tag');
class ForLoop_Tag extends TagNode {
	var $body;
	var $elseBody;
	var $arguments;
	var $list;
	var $index;
	var $item;
	var $reverse =false;
    var $limit = false;
    
	function __construct($argstring, &$parser, $position) {
		$args = $this->arguments = $this->parseArguments($argstring, $position);
		if (end($args) == 'reversed' ) {
			$this->reverse = true;
			array_pop($args);
		}
		
		if (ctype_digit(end($args)) && prev($args) == 'limit' ) {
		      $this->limit = array_pop($args);
		      array_pop($args);
		}
		  
		$this->list = array_pop($args);
		if(end($args) == 'in') {
			$keyword_in = array_pop($args);
		} else {
			throw new H2o_TemplateSyntaxError('For loop syntax error, (example: for [key], [item] in [list] )',
			$parser->filename,
			$position,
			$argstring);
		}

		if (isset($args[1])) {
			$this->index = $args[0];
			$this->item = $args[1];
		} else {
			$this->item = $args[0];
		}

		$this->body = $parser->parse('endfor');
	}

	function compile(&$context, &$stream){
		$list = H2o_Utils::makeArray($context->resolve($this->list));
		$length = count($list);
		$isEmpty = $length == 0;
		$parent = $context->getVariable('loop');

		$context->push();

		if (isset($this->index))
		$context->setVariable($this->index, is_null($this->index) ? 0 : 1);
		$context->setVariable($this->item, $isEmpty ? 0  : $list[0]);

		$listName = $context->resolve($this->list, true);
		$itemName = $context->resolve($this->item, true);
		$indexName = $context->resolve($this->index, true);

		if ($listName == null){
			$listName = 'array()';
		}

		if ($this->reverse){
			$listName = "array_reverse($listName)";
		}

		$keyValueText = !is_null($this->index) ? "$indexName => $itemName" : $itemName;
		$sourceCode = 'if (!isset($_forstack)) $_forstack = array();'.
		'if (!empty($_forstack)) extract(array_pop($_forstack));'.
		'if (isset($loop) && !isset($parent)) $parent = $loop;'.
		'else $parent = null;'.
		'$idx=0;$loop = array();$length = count('.$listName.');';
		$stream->writeSource($sourceCode);


		$stream->write($this->strFormat('FORLOOP', $listName.' as '.$keyValueText));
        if ($this->limit)
        $stream->writeSource('if ($idx >= '.$this->limit.') break;');
        
		// Loop object
		$context->setVariable('loop', array('parent'=>$parent,'even'=>0,'odd'=>0,'first'=>0,'last'=>0,'length'=>0, 'counter'=>0, 'counter0'=>0,'revcounter'=>0,'revcounter0'=>0));
		$sourceCode = '$loop["parent"] = $parent;'.
		'$loop["even"] = $idx % 2 == 0;'.
		'$loop["odd"] = $idx %2 == 1;'.
		'$loop["revcounter"] = $length - $idx +1;'.
		'$loop["revcounter0"] =  $length - $idx;'.
		'$loop["counter"] = $idx +1;'.
		'$loop["counter0"] = $idx;'.
		'$loop["first"] = $idx == 0;'.
		'$loop["length"] = $length;'.
		'$loop["last"] = $idx == ($length - 1)';

		$stream->writeSource($sourceCode);

		$this->body->compile($context, $stream);
		$stream->writeSource('++$idx;');
		$stream->write($this->strFormat('ENDFORLOOP'));
    

        
		$stream->writeSource('array_push($_forstack, compact("idx", "length", "loop", "parent"));');

		$context->pop();
	}
}

h2o::add_tag('cycle', 'Cycle_Tag');
class Cycle_Tag extends TagNode {
	var $asVar;

	function __construct($argstring, &$parser, $position){
		$args = $this->parseArguments($argstring, $position);
		$argc = count($args);
		if ($argc < 2)
		throw new H2o_TemplateSyntaxError('Cycle_Tag require aleast two values, (example: cycle value1, value2 )',
		$parser->filename,
		$position,
		$argstring);

		foreach ($args as $i => $arg) {
			if (H2o_Utils::is_string($arg)) $args[$i] = substr($arg, 1,-1);
				
			if ($arg == 'as') {
				if ($args[$i+1])
				$next = $args[$i+1];
				else
				throw new H2o_TemplateSyntaxError('Cycle_Tag mssing "AS" keyword (example: cycle value1, value2 as variable )',
				$parser->filename,
				$position,
				$argstring);
				$this->asVar = $args[$i+1];
				unset($args[$i], $args[$i+1]);
			}
		}
		$this->sequence = $args;
		$this->uid = "__cycle__".$position;
	}

	function compile(&$context, &$stream){
		$id = $this->uid;
		$stream->writeSource('
			if (!isset($seq'.$id.'))
				$seq'.$id.'  = '.$this->passVars($this->sequence).';
			$uid'.$id.' = "'.$id.'";
			$item'.$id.' = isset($storage["cycle_item"][$uid'.$id.'])?($storage["cycle_item"][$uid'.$id.']+1) % count($seq'.$id.'):0;
			$storage["cycle_item"][$uid'.$id.'] = $item'.$id.';
			$as = $seq'.$id.'[$item'.$id.'];
		');


		$output = 'echo $as;';

		$stream->writeSource($output);
	}
}

h2o::add_tag('block', 'Block_Tag');
class Block_Tag extends TagNode {
    var $stack;
    var $name;
    var $pos;
    var $superPath;
    var $cachePath;
    var $parser;
    function __construct($argstring, &$parser, $position) {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $argstring)) {
                throw new H2o_TemplateSyntaxError('Block tag expects a name, example: block [content]', 
                            $parser->filename,
                            $position,
                            $argstring);
        }
        $this->name = $argstring;
        $this->stack = array($parser->parse('endblock', "endblock $this->name"));
        if (!isset($parser->storage['blocks'])){
            $parser->storage['blocks'] = array();
        }
        if (isset($parser->storage['blocks'][$this->name])) {
                throw new H2o_TemplateSyntaxError('Block name exists, Please select a different block name', 
                            $parser->filename,
                            $position,
                            $argstring);    
        }
        $parser->storage['blocks'][$this->name] =& $this;
        $this->pos = $position;
        $this->parser = & $parser;
        
        $this->cachePath = $parser->env->options['compile_path'];
        $this->filename = $parser->filename;
    }
    
    function addLayer(&$nodelist){
        array_push($this->stack, $nodelist);
    }
    
    function compile(&$context, &$stream, $index = 1){
        $current = count($this->stack) - $index;
        $nodelist =& $this->stack[$current];
        $this->superPath = $this->getSuperPath($nodelist->parser->filename);
        
        $block = new H2o_Block($this->name,$index+1, $this->superPath);
        $context->push(array('block' =>$block));
        
        if ($nodelist){
            $this->compileParent($context, $this->superPath, $index);
            if ($current == $index -1 )
             $stream->writeSource('$block = new H2o_block("'.$block->name().'",'.$block->depth().',"'.addslashes($block->path).'")');
            $nodelist->compile($context, $stream);
        }
        $context->pop();
    }
    
    function compileParent($context, $path, $index){
        $current = count($this->stack) - $index -1;
        if ($current >= 0) {
            $nodelist =& $this->stack[$current];
            $block = new H2o_Block($this->name,
                                    $index+1,
                                    $this->getSuperPath($this->filename));
            $context->push(array('block' => $block));

            if ($nodelist) {
                $stream = new H2o_StreamWriter;
                $nodelist->compile($context, $stream, $index+1);
                $output = trim($stream->close());

                // ignore empty blocks
                if (isset($output{1})) {
                    $startSrc = '<?php 
                        if (isset($block)) $_block = $block;$block = new H2o_block("'.$block->name().'",'.$block->depth().',"'.addslashes($block->path).'") ?>';
                    $endSrc = '<?php if (isset($_block)) $block = $_block ?>';
                    file_put_contents($path, $startSrc. $output .$endSrc);
                }
            }
            $context->pop();
        }
    }
    
    function getSuperPath($filename){
        $unqiue = md5($filename.$this->name).'.object';
        return $this->cachePath.DS.$unqiue;
    }
}

h2o::add_tag('extends', 'Extends_Tag');
class Extends_Tag extends TagNode {
	var $nodelist;
	function __construct($argstring, &$parser, $position) {

		if (!$parser->first)
		throw new H2o_TemplateSyntaxError('extends must be first in file',
		$parser->filename ,
		$position);
		if (!preg_match('/(["\'])(.*?)\\1$/', $argstring, $match))
		throw new H2o_TemplateSyntaxError('invalid syntax for extends tag, file name should be quoted',
		$parser->filename,
		$position,
		$argstring);
		$doc = $parser->parse();
		$filename = stripcslashes(substr($argstring, 1, -1));

		if ($filename == $parser->filename) {
			throw new H2o_Error('Run time error: template cannot extend itself',
			$parser->filename,
			$position,
			$argstring);
		}

		$subparser = new H2o_Parser($parser->env->loadSubTemplate($filename),
		$parser->env,
		$filename);
		$this->nodelist =& $subparser->parse();
		if (isset($this->nodelist->parser->storage['blocks'])) {
			$blocks =& $this->nodelist->parser->storage['blocks'];
			if (isset($parser->storage['blocks'])) {
				foreach ($parser->storage['blocks'] as $name => $tag) {
					if (isset($blocks[$name])) {
						$blocks[$name]->addLayer($tag);
					}
				}
			}
		}
	}

	function compile(&$context, &$stream){
		$this->nodelist->compile($context, $stream);
	}
}

h2o::add_tag('include', 'Include_Tag');
class Include_Tag extends TagNode {
	var $body;

	function __construct($argstring, &$parser, $position){
		$args = $this->parseArguments($argstring, $position);
		if (count($args) != 1) {
			throw new H2o_TemplateSyntaxError('invalid include tag, are you missing filename?',
			$parser->filename,
			$position,
			$argstring);
		}
		if (!preg_match('/(["\'])(.*?)\\1$/', $argstring, $match))
		throw new H2o_TemplateSyntaxError('invalid syntax for include tag, file name should be quoted',
		$parser->filename,
		$position,
		$argstring);

		$filename = array_pop($args);
		if (H2o_Utils::is_string($filename)) {
			$filename = substr($filename, 1, -1);
		}
		$subparser = new H2o_Parser($parser->env->loadSubTemplate($filename),
		$parser->env,
		$filename);
		$this->body = $subparser->parse();
	}

	function compile(&$context, &$stream){
		$this->body->compile($context, $stream);
	}
}


h2o::add_tag('debug', 'Debug_Tag');
class Debug_Tag extends TagNode {
	var $argument;
	function __construct($argstring, &$parser, $position){
		$this->argument = $argstring;
	}

	function compile(&$context, &$stream){
		$output = '<div class="debug" style="color:#666;border:1px solid #333;background:#fffff5;padding:1em;margin:1em">
					Debug %s object<pre>%s</pre></div>';
		if (is_null($this->argument)) {
			$viewVars = $this->_listChild($context->namespace[0]);
		} else {
			$viewVars = $this->_listChild($context->resolve($this->argument));
		}
		$output = sprintf($output, $this->argument ,$viewVars);
		$stream->write($output);
	}

	function _listChild($variable){
		foreach($variable as $name => $prop) {
			if (is_object($prop) && is_subclass_of($prop, 'helper')) {
				unset($variable[$name]);
			}
		}
		
		
		return str_replace('Array', '', print_r($variable, true));
	}
}

h2o::add_tag('comment', 'Comment_Tag');
class Comment_Tag extends TagNode {
	var $comment;
	function __construct($argstring, &$parser, $position){
		$this->comment = $parser->skipTo('endcomment');
	}
	function compile(&$context, &$stream){
	}
}

h2o::add_tag('now', 'Now_Tag');
class Now_Tag extends TagNode {
	var $dateformat = 'jS F Y H:i:s';
	function __construct($argstring, &$parser, $position){
		$args = $this->parseArguments($argstring, $position);
		$format = array_pop($args);

		if ( isset($format) && H2o_Utils::is_string($format)) {
			$this->dateformat = substr($format, 1, -1);
		}
	}

	function compile(&$context, &$stream){
		$stream->writeSource(' echo date("'.$this->dateformat.'");');
	}
}

h2o::add_tag('spaceless', 'Spaceless_Tag');
class Spaceless_Tag extends TagNode {
	var $body;
	function __construct($argstring, &$parser, $position){
		$this->body = $parser->parse('endspaceless');
	}
	function compile(&$context, &$stream){
		$storage = new H2o_StreamWriter;
		$this->body->compile($context ,$storage);
		$storage = preg_replace("/>\s+</sm", '><', $storage->close());
		$stream->write($storage);
	}
}


h2o::add_tag('with', 'With_Tag');
class With_Tag extends TagNode {
	var $name;
	var $shortcut;
	var $body;

	function __construct($argstring, &$parser, $position){
		$args = $this->parseArguments($argstring, $position);
		if (count($args) !== 3) {
			throw new H2o_TemplateSyntaxError('invalid syntax for with tag, example : with [long.variable] as [var]',
			$parser->filename,
			$position,
			$argstring);
		}
		$this->shortcut = array_pop($args);
		$as = array_pop($args);
		if ('as' !== $as)
		throw new H2o_TemplateSyntaxError('keyword "as" is not found, example : with [long.variable] as [var]',
		$parser->filename,
		$position,
		$argstring);
			
		$this->name = array_pop($args);
		$this->body = $parser->parse('endwith');
	}

	function compile(&$context, &$stream){
		$context->push();
		$name = $context->resolve($this->name, true);
		$object = $context->resolve($this->name);
		$context->setVariable($this->shortcut, $object);
		$mapping = array('first'=>0);
		// Hack to force a name, only works for array
		if (!isset($name)) {
			$parts = explode('.', $this->name);
			if (!empty($parts)) {
				$name = '$'.array_shift($parts);
			}
			foreach ($parts as $part) {
				if (isset($mapping[$part]))
				$part = $mapping[$part];
				$name.= '["'.$part.'"]';
			}
		}

		if (!isset($name)) {
			throw H2o_Error('Context error: '.$name.' is undefined');
		}

		$stream->writeSource('$'.$this->shortcut .'=null;if (isset('.$name.')) $'.$this->shortcut .'='. $name);
		$this->body->compile($context, $stream);
		$stream->writeSource('unset($'.$this->shortcut.')');

		$context->pop();
	}
}


h2o::add_tag('cache', 'Cache_Tag');
class Cache_Tag extends TagNode {
	var $name;
	var $body;
	var $path;
	var $source;

	function __construct($argstring, &$parser, $position){
		$this->path = $parser->env->options['compile_path'];
		$args = $this->parseArguments($argstring, $position);
		if (count($args) <2 ) {
			throw new H2o_TemplateSyntaxError('Wrong argument count, expecting 2 arguments cache [seconds to live] [cache_block_name] ',
			$parser->filename,
			$position,
			$argstring);
		}

		$this->name = array_pop($args);
		$this->expires = array_pop($args);
		$this->cache_obj = $this->path.DS.md5('__CACHE_'.$this->name).'.object';

		/*	Stored cached paths	*/
		if (isset($parser->storage['block_cache'][$this->name])) {
			throw new H2o_TemplateSyntaxError('Cache name exists, please use a unique name for the cache block',
			$parser->filename,
			$position,
			$argstring);
		}
		$parser->env->storage['block_cache'][$this->name]=$this->cache_obj;

		if ($this->_checkCache($this->cache_obj)){
			$this->source = file_get_contents($this->cache_obj);
			$this->body = $parser->skipTo('endcache');
		}
		else
		$this->body = $parser->parse('endcache');
	}

	function compile(&$context, &$stream){
		if (isset($this->source))
		return $stream->write($this->source);

		// Compile content to output stream
		$output = new H2o_StreamWriter;
		$this->body->compile($context, $output);
		$output = trim($output->close());
		// Render compiled source code
		if (isset($output{1})){
			file_put_contents($this->cache_obj, $output);
			ob_start();
			extract($context->namespace[0]);
			include($this->cache_obj);
			$output = ob_get_clean();
		}
		// Cache and output html output
		file_put_contents($this->cache_obj, $output);
		$stream->write($output);
	}

	function _checkCache($object){
		if (!file_exists($object))
		return false;
		if (filemtime($object) + intval($this->expires) <= CURRENT_TIME)
		return false;
		return true;
	}
}
?>