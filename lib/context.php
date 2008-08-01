<?php

/**
 * Context object
 * 	encapsulate context, resolve name
 */
class H2o_Context {
	var $namespace = array();
	var $safeClass = array();
	
	function __construct(&$context){
		global $H2O_CONTEXT_LOOKUP;
		$this->namespace = array($context);
		$this->lookupTable =& $H2O_CONTEXT_LOOKUP;
	}

	function push( $layer = null){
        if (is_null($layer))
            $layer = array();
        array_push($this->namespace, $layer);
	}

	/**
     * pop the most recent layer
     */
    function pop() {
        if (count($this->namespace) <= 1)
            new Exception('cannnot pop from empty stack');
        return array_pop($this->namespace);
    }
    
	function setVariable($name, $value) {
        if (strpos($name, '.') > -1)
            new Exception('cannot set non local variable');
        $this->namespace[count($this->namespace) - 1][$name] = $value;  
	}
	function getVariable($name, $output = false) {
		foreach (array_reverse($this->namespace) as $layer) {
            if (isset($layer[$name]) && !$output)
                return $layer[$name];
            else if (isset($layer[$name]))
            	return "$$name";
        }
        return null;
	}
	
    /**
     * retreive a variable
     * 
     * 		is numeric
     * 		is string
     * 
     */
    function resolve($name, $compile = false, $default='') {
    	$pattern = array('numeric'=>'/^-?\d+(\.\d+)?$/', 
    					'string'=>'/^"([^"\\\\]*(?:\\.[^"\\\\]*)*)"|' .
                       				'\'([^\'\\\\]*(?:\\.[^\'\\\\]*)*)\'$/',
                       	'constant' => '/([A-Z])([A-Z_0-9]+)/',
                       	'parameter'=>'//',);
    	
        // resolve constants. numbers and strings
        if (preg_match($pattern['constant'], $name)) {
 			if (defined($name)){
                if ($compile) 
                	return $name;
            	return constant($name);
 			}
        }
        if (preg_match($pattern['numeric'], $name, $matches)) {
            if (isset($matches[1]))
                return floatval($name);
            return intval($name);
        }
        if (preg_match($pattern['string'], $name)) {
        	if ($compile)
        		return $name;
            return stripcslashes(substr($name, 1, -1));
        }
        
        //Local variables. this gives as a bit of performance improvement
        if (!strpos($name, '.')) {
            return $this->getVariable($name, $compile);
        }

    	//External Context lookup
    	if (!empty($this->lookupTable)) {
	        $result = $this->externalLookup($name, $compile);
	        if (!empty($result)){
	        	return $result;
	        }
    	}
    	
		//Prepare for Big lookup
        $parts = explode('.', $name);
        $name = array_shift($parts);
        $node = $this->getVariable($name);
        
        //Name lookup
        $nodeName = '';
        if ($compile) {
       		$nodeName = '$'.$name;
        }
        
        //Lookup context        
        foreach ($parts as $part) {
        	// Process a array
            if (is_array($node) ) {
            	// Array member
	            if (isset($node[$part])) {
	                $node = $node[$part];
	                $nodeName .= '["'.$part.'"]';     
	            }
	            // Perform additional lookup
	            else {
	            	// List shortcut
	            	$shortcut = array('first'=>0,'last'=>count($node)-1, 'length' => count($node));
	            	if ($part == 'length') {
	            		$node = $shortcut[$part];
	            		$nodeName = "count({$nodeName})";
	            		continue;
	            	}
                        	            	
	            	if (isset($shortcut[$part])) $part = $shortcut[$part];
	            	
	            	// Perform numeric index to hash index
	            	$keys = array_keys($node);
	            	$index = array_search($part, $keys);
	            	if (isset($keys[$part]) && isset($node[$keys[$part]])){
	            		$node = $node[$keys[$part]];
                		$nodeName .= '["'.$keys[$part].'"]';
	            	} else {
	            		return null;
	            	}
	            }
            }
        	// Process a array            
            elseif (is_object($node)) {
            	$methodAllow = isset($node->h2o_safe) && in_array($part, $node->h2o_safe);
            	$classAllow =  in_array(get_class($node), $this->safeClass);
            	
                if (method_exists($node, $part) &&  ($methodAllow || $classAllow)){
                    $node = call_user_func(array($node, $part));
					$nodeName .= "->".$part."()";                    
                } elseif (property_exists($node, $part)) {
                    $tmp = get_object_vars($node);
                    $node = $tmp[$part];
                    $nodeName .= '->'.$part;
                }
                else
                    return null;
            }
            else
                return null;
        }
        if ($compile)
        	return $nodeName;
        return $node;
    }

	function externalLookup($name, $compile =false){
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
        return false;
    }

}

?>