<?php

/**
 * Context object
 *  encapsulate context, resolve name
 */
class H2o_Context implements ArrayAccess {
    public $safeClass = array('stdClass', 'BlockContext');
    public $scopes;
    public $options;
    public $autoescape = true;
    
    private $arrayMethods = array('first'=> 0, 'last'=> 1, 'length'=> 2, 'size'=> 3);
    static $lookupTable = array();
    
    function __construct($context = array(), $options = array()){
        if (is_object($context))
           $context = get_object_vars($context);
        $this->scopes = array($context);
        
        if (isset($options['safeClass'])) 
            $this->safeClass = array_merge($this->safeClass, $options['safeClass']);
            
        if (isset($options['autoescape'])) 
            $this->autoescape = $options['autoescape'];
            
        $this->options = $options;
    }

    function push($layer = array()){
        return array_unshift($this->scopes, $layer);
    }

    /**
     * pop the most recent layer
     */
    function pop() {
        if (!isset($this->scopes[1]))
            throw new Exception('cannnot pop from empty stack');
        return array_shift($this->scopes);
    }

    function offsetExists($offset) {
        foreach ($this->scopes as $layer) {
            if (isset($layer[$offset])) return true;
        }
        return false;
    }

    function offsetGet($key) {
        foreach ($this->scopes as $layer) {
            if (isset($layer[$key]))
                return $layer[$key];
        }
        return;
    }
    
    function offsetSet($key, $value) {
        if (strpos($key, '.') > -1)
            throw new Exception('cannot set non local variable');
        return $this->scopes[0][$key] = $value;
    }
    
    function offsetUnset($key) {
        foreach ($this->scopes as $layer) {
            if (isset($layer[$key])) unset($layer[$key]);
        }
    }

    function extend($context) {
        $this->scopes[0] = array_merge($this->scopes[0], $context);
    }

    function set($key, $value) {
        return $this->offsetSet($key, $value);
    }

    function get($key) {
        return $this->offsetGet($key);
    }

    function isDefined($key) {
        return $this->offsetExists($key);
    }
    /**
     * 
     * 
     * 
     *  Variable name
     * 
     * @param $var variable name or array(0 => variable name, 'filters' => filters array)
     * @return unknown_type
     */
    function resolve($var) {

        # if $var is array - it contains filters to apply
        $filters = array();
        if ( is_array($var) ) {
        	
            $name = array_shift($var);
            $filters = isset($var['filters'])? $var['filters'] : array();
        
        } 
        else $name = $var;
        
        $result = null;
	
        # Lookup basic types, null, boolean, numeric and string
        # Variable starts with : (:users.name) to short-circuit lookup
        if ($name[0] === ':') {
            $object =  $this->getVariable(substr($name, 1));
            if (!is_null($object)) $result = $object;
        } else {
            if ($name === 'true') {
                $result = true;
            }
            elseif ($name === 'false') {
                $result = false;
            } 
            elseif (preg_match('/^-?\d+(\.\d+)?$/', $name, $matches)) {
                $result = isset($matches[1])? floatval($name) : intval($name);
            }
            elseif (preg_match('/^"([^"\\\\]*(?:\\.[^"\\\\]*)*)"|' .
                           '\'([^\'\\\\]*(?:\\.[^\'\\\\]*)*)\'$/', $name)) {            
                $result = stripcslashes(substr($name, 1, -1));
            }
        }
        if (!empty(self::$lookupTable) && $result == Null) {
            $result = $this->externalLookup($name);
        }
        $result = $this->applyFilters($result,$filters);
        return $result;
    }
        
    function getVariable($name) {
        # Local variables. this gives as a bit of performance improvement
        if (!strpos($name, '.'))
            return $this[$name];

        # Prepare for Big lookup
        $parts = explode('.', $name);
        $object = $this[array_shift($parts)];

        # Lookup context
        foreach ($parts as $part) {
            if (is_array($object) or $object instanceof ArrayAccess) {
                if (isset($object[$part])) {
                    $object = $object[$part];
                } elseif ($part === 'first') {
                    $object = isset($object[0])?$object[0]:null;
                } elseif ($part === 'last') {
                    $last = count($object)-1;
                    $object = isset($object[$last])?$object[$last]:null;
                } elseif ($part === 'size' or $part === 'length') {
                    return count($object);
                } else {
                    return null;
                }
            }
            elseif (is_object($object)) {
                if (isset($object->$part))
                    $object = $object->$part;
                elseif (is_callable(array($object, $part))) {
                    $methodAllowed = in_array(get_class($object), $this->safeClass) || 
                        (isset($object->h2o_safe) && (
                            $object->h2o_safe === true || in_array($part, $object->h2o_safe)
                        )
                    );
                    $object = $methodAllowed ? $object->$part() : null;
                }
                else return null;
            }
            else return null;
        }
        return $object;
    }

    function applyFilters($object, $filters) {
        
        foreach ($filters as $filter) {
            $name = substr(array_shift($filter), 1);
            $args = $filter;
            
            if (isset(h2o::$filters[$name])) {                
                foreach ($args as $i => $argument) {
                    # name args
                    if (is_array($argument)) {
                        foreach ($argument as $n => $arg) {
                            $args[$i][$n] = $this->resolve($arg);
                        }
                    } else {
                    # resolve argument values
                       $args[$i] = $this->resolve($argument);
                    }
                }
                array_unshift($args, $object);
                $object = call_user_func_array(h2o::$filters[$name], $args);
            }
        }
        return $object;
    }

    function escape($value, $var) {
		
        $safe = false;
        $filters = (is_array($var) && isset($var['filters']))? $var['filters'] : array();

        foreach ( $filters as $filter ) {
        	
            $name = substr(array_shift($filter), 1);
            $safe = !$safe && ($name === 'safe');
        
            $escaped = $name === 'escape';
        }
        
        $should_escape = $this->autoescape || isset($escaped) && $escaped;
        
        if ( ($should_escape && !$safe)) {
            $value = htmlspecialchars($value);
        }		
        
        return $value;
	}

    function externalLookup($name) {
        if (!empty(self::$lookupTable)) {
            foreach (self::$lookupTable as $lookup) {
                $tmp = call_user_func_array($lookup, array($name, $this));
                if ($tmp !== null)
                return $tmp;
            }
        }
        return null;
    }
}

class BlockContext {
    var $h2o_safe = array('name', 'depth', 'super');
    var $block, $index;
    private $context;
    
    function __construct($block, $context, $index) {
        $this->block =& $block;
        $this->context = $context;
        $this->index = $index;
    }

    function name() {
        return $this->block->name;
    }

    function depth() {
        return $this->index;
    }

    function super() {
        $stream = new StreamWriter;
        $this->block->parent->render($this->context, $stream, $this->index+1);
        return $stream->close(); 
    }
    
    function __toString() {
        return "[BlockContext : {$this->block->name}, {$this->block->filename}]";
    }
}
?>
