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
     * @param $name
     * @return unknown_type
     */
    function resolve($name) {
        # Lookup basic types, null, boolean, numeric and string
        # Variable starts with : (:users.name) to short-circuit lookup
        if ($name[0] === ':') {
            $object =  $this->getVariable(substr($name, 1));
            if (!is_null($object)) return $object;
        } else {
            if ($name === 'true') {
                return true;
            }
            elseif ($name === 'false') {
                return false;
            } 
            elseif (preg_match('/^-?\d+(\.\d+)?$/', $name, $matches)) {
                return isset($matches[1])? floatval($name) : intval($name);
            }
            elseif (preg_match('/^"([^"\\\\]*(?:\\.[^"\\\\]*)*)"|' .
                           '\'([^\'\\\\]*(?:\\.[^\'\\\\]*)*)\'$/', $name)) {            
                return stripcslashes(substr($name, 1, -1));
            }
        }
        if (!empty(self::$lookupTable)) {
            return $this->externalLookup($name);
        }
        return null;
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
                if (isset($object[$part]))
                    $object = $object[$part];
                elseif ($part === 'first')
                    $object = $object[0];
                elseif ($part === 'last')
                    $object = $object[count($object) -1];
                elseif ($part === 'size' or $part === 'length')
                    return count($object);
                else return null;
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
        $safe = false;
        
        foreach ($filters as $filter) {
            $name = substr(array_shift($filter), 1);
            $args = $filter;
            $safe = !$safe && $name === 'safe';
            
            if ($this->autoescape && $escaped = $name === 'escape')
                continue;
            
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
        $should_escape = $this->autoescape || isset($escaped) && $escaped;
        
        if ($should_escape && !$safe) {
            $object = htmlspecialchars($object);
        }
        return $object;
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