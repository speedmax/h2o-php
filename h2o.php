<?php
ini_set('display_errors', 1);

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
define('H2O_ROOT', dirname(__FILE__).DS);

require 'Krumo/class.krumo.php';
function pd() { $args = func_get_args();krumo($args);die;}
require H2O_ROOT.'h2o/nodes.php';
require H2O_ROOT.'h2o/tags.php';
require H2O_ROOT.'h2o/errors.php';
require H2O_ROOT.'h2o/filters.php';
require H2O_ROOT.'h2o/context.php';


$h2o = new H2o('./inherit.html');
$context = array(
    'page' => array(
    	'title' => 'this is a page title',
    	'description' => 'this is a a page description',
    	'body' => 'This is page body'
    ),
    'links' => array('http://www.google.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com', 'http://yahoo.com')
);

$start = microtime(true);
echo $h2o->render($context);
echo microtime(true) - $start;

class H2o {
    var $searchpath;
    var $context;
    
    static $registeredTags = array();
    static $registeredFilters = array();

    function __construct($file, $options = array()) {
        # Init a environment
        $this->options = H2o::getOptions($options);
        
        # Load using this environment
        $this->nodelist = H2o::load($file, $this->options);
	}
	
    # Build a finalized nodelist from template ready to be cached

    # Render the nodelist
    function render($context) {
        $this->stream = new StreamWriter;
        $this->nodelist->render(new H2o_Context($context), $this->stream);
        return $this->stream->close();
    }


	static function load($file, $env = null) {
	    if (!$env) {
	        $env = H2o::getOptions();
	    }
	    $filename = realpath($file);
//        $cache = md5($fn);
//        
//        # Cache hit
//        if (is_file($cache) && (time() - filemtime($cache)) < 10) {
//            $nodelist = unserialize(file_get_contents($cache));
//        } else {
        if (!class_exists('H2o_Parser'))
            require H2O_ROOT.'h2o/parser.php';
            
            $source = file_get_contents($filename);
            $parser = new H2o_Parser($source, $filename, $env);
            $nodelist = $parser->parse();
//            file_put_contents($cache, serialize($this->nodelist));
//        }
        return $nodelist;
	}

    static function getOptions($options = array()) {
        return array_merge(array(
            'loader'			=>    'H2o_FileLoader',
                // Enviroment setting
        	'BLOCK_START'		=>		'{%',
        	'BLOCK_END'			=>		'%}',
        	'VARIABLE_START'	=>		'{{',
        	'VARIABLE_END'		=>		'}}',
        	'COMMENT_START'		=>      '{*',
        	'COMMENT_END'		=>      '*}',
        	'TRIM_TAGS'     => true
         ), $options);
    }
    
    static function createTag($tag, $args = null, $parser, $position = 0) {
        $tag = ucwords($tag);
        if (isset(self::$registeredTags[$tag])) {
            $tagClass = "{$tag}Tag";
            return new $tagClass($args, $parser, $position);
        }
    }

    # Static method to add Tag
    static function addTag($tag, $class = null) {
        $tags = array();
        if (is_string($tag)) {
            if (is_null($class)) $class = "{$tag}Tag";
            $tags[$tag] = $class;
        } elseif (is_array($tag)) {
            $tags = $tag;
        }

        foreach ($tags as $tag => $tagClass) {
            if (is_integer($tag)) {
                $tags[$tagClass] = "{$tagClass}Tag";
                unset($tags[$tag]);
            }
        }
        self::$registeredTags = array_merge(self::$registeredTags, $tags);
    }

    static function addFilter($filter, $callback = null) {
        if (is_array($filter)) {
            $filters = $filter;
            foreach($filters as $key => $filter) {
                if (is_numeric($key))
                    self::addFilter($filter);
                else
                    self::addFilter($key, $filter);
            }
            return true;
        }

        if (is_null($callback)) {
            $callback = $filter;
        }
        if (!is_callable($callback)){
            return false;
        }
        self::$registeredFilters[$filter] = $callback;
    }
}




class StreamWriter {
    var $buffer = array();
    var $close;

    function __construct() {
        $this->close = false;
    }

    function write($data) {
        if ($this->close)
        new Exception('tried to write to closed stream');
        $this->buffer[] = $data;
    }

    function close() {
        $this->close = true;
        return implode('', $this->buffer);
    }
}



class H2o_FileLoader {
    function read($filename) {
       $filepath = $this->env->searchpath . '/' . $filename;
       $cache = md5($fn);
        if (is_file($cache) && (time() - filemtime($cache)) < 3600) {
            $result = unserialize(file_get_contents($cache));
            return $result;
        }
    }
    
    function write($filename) {
        file_put_contents($cache, serialize($nodelist));   
    }
}



/**
 * $type of token, Block | Variable
 */
class H2o_Token {
    function __construct ($type, $content, $position) {
        $this->type = $type;
        $this->content = $content;
        $this->result='';
        $this->position = $position;
    }

    function write($content){
        $this->result= $content;
    }
}


/**
 * a token stream
 */
class TokenStream  {
    var $pushed;
    var $stream;
    var $closed;
    var $c;

    function __construct() {
        $this->pushed = array();
        $this->stream = array();
        $this->closed = false;
    }

    function pop() {
        if (count($this->pushed))
        return array_pop($this->pushed);
        return array_pop($this->stream);
    }

    function feed($type, $contents, $position) {
        if ($this->closed)
            throw new Exception('cannot feed closed stream');
        $this->stream[] =& new H2o_Token($type, $contents, $position);
    }

    function push($token) {
        if (is_null($token))
            throw new Exception('cannot push NULL');
        if ($this->closed)
            $this->pushed[] = $token;
        else
            $this->stream[] = $token;
    }

    function close() {
        if ($this->closed)
        new Exception('cannot close already closed stream');
        $this->closed = true;
        $this->stream = array_reverse($this->stream);
    }

    function isClosed() {
        return $this->closed;
    }

    function current() {
        return $this->c ;
    }

    function next() {
        return $this->c = $this->pop();
    }
}

?>