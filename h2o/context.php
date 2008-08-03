<?php
/**
 * Context object
 * 	encapsulate context, resolve name
 */
class H2o_Context {
	public $safeClass = array();
	private $scopes = array();
	private $arrayMethods = array('first'=> 0, 'last'=> 1, 'length'=> 2, 'size'=> 3);

	function __construct(&$context = array()){
		$this->scopes = array($context);
	}

	function push( $layer = array()){
		array_unshift($this->scopes, $layer);
	}

	/**
	 * pop the most recent layer
	 */
	function pop() {
		if (count($this->scopes) <= 1)
			throw new Exception('cannnot pop from empty stack');
		return array_shift($this->scopes);
	}

	function set($key, $value) {
		if (strpos($key, '.') > -1)
			throw new Exception('cannot set non local variable');
		$this->scopes[0][$key] = $value;
	}

	function get($key) {
	  foreach ($this->scopes as $layer) {
			if (isset($layer[$key]))
				return $layer[$key];
	  }
	  return null;
	}

	function resolve($name) {
		if (ctype_digit($name))
			return !strpos($name, '.') ? intval($name) : floatval($name);
		elseif (preg_match('/^["\'](?:.*)["\']$/', $name))
			return stripcslashes(substr($name, 1, -1));
		else
			return $this->resolveVariable($name);
	}

	function resolveVariable($name) {
	    # Local variables. this gives as a bit of performance improvement
	    if (!strpos($name, '.'))
	        return $this->get($name);

	    # Prepare for Big lookup
	    $parts = explode('.', $name);
	    $name = array_shift($parts);
	    $object = $this->get($name);

	    # Lookup context
	    foreach ($parts as $part) {
	        if (is_array($object)) {
	            if (isset($object[$part])) {
	                $object = $object[$part];
	            }
	
	            # Support array short cuts
	            elseif (isset($this->arrayMethods[$part])) {
	                $size = count($object);
	                $shortcut = array_combine(
	                    array_flip($this->arrayMethods), 
	                    array(0, $size - 1, $size, $size)
	                );
	
	                if ($part === 'size' || $part === 'length')
	                    return $object = $shortcut[$part];
	                else
	                    return $object[$shortcut[$part]];
	            } 
	            else return null;
	        }
	
	        elseif (is_object($object)) {
	            $methodAllow = isset($object->h2o_safe) && in_array($part, $object->h2o_safe);
	            $classAllow =  in_array(get_class($object), $this->safeClass);

	            if (method_exists($object, $part) &&  ($methodAllow || $classAllow)){
	                $node = call_user_func(array($object, $part));
	            } elseif (property_exists($object, $part)) {
	                $tmp = get_object_vars($object);
	                $object = $tmp[$part];
	            }
	            else return null;
	        }
	        else return null;
	    }
    
	    # External Context lookup
	    if ( !empty($this->lookupTable) )
	        $object = $this->externalLookup($name);

	    return $object;
	}

	function applyFilters($object, $filters) {
	    $registeredFilters = H2o::$registeredFilters;
	    foreach ($filters as $filter) {
	        $name = array_shift($filter);
	        if (isset($registeredFilters[$name])) {
	            $args = $filter;
	            array_unshift($args, $object);
	            $object = call_user_func_array($registeredFilters[$name], $args);
	        }
	    }
	    return $object;
	}

	function externalLookup($name, $compile = false){
	    if (!empty($this->lookupTable)) {
	        foreach ($this->lookupTable as $lookup) {
	            $tmp = $lookup($name, $this, $compile);
	            if ($tmp !== null)
	            return $tmp;
	        }
	    }
	    return null;
	}

	function isDefined($name) {
	    foreach ($this->namespace as $layer) {
	        if (isset($layer[$name]))
	        return true;
	    }
	    return null;
	}
}


class BlockContext {
	private $block, $name, $depth, $index;
	
	function __construct($block, $context, $stream, $index) {
		$this->block = $block;
		$this->context = $context;
		$this->stream = $stream;
		$this->index = $index;
	}

	function name() {
		return $this->name;
	}

	function depth() {
		return $this->index + 1;
	}

	function super() {
		$this->block->render($this->context, $this->stream, $this->index+1);
	}
}
?>