<?php
/**
 * H2O Template
 *
 * @author James Logsdon <dwarf@girsbrain.org>
 * @author Taylor Luk <taylor.luk@idealian.net>
 * @package h2o-php
 * @copyright Copyright (c) 2008 Taylor Luk
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Layered context object
 *
 * @implements ArrayAccess
 */
class h2o_Context implements ArrayAccess {
    /**
     * Options for this context instance
     *
     * @var array
     * @access private
     */
    private $_options = array(
        'SAFE_CLASS' => array('stdClass')
    );

    private $_autoescape = true;

    /**
     * Available layers for this context. Always has at least one index
     *
     * @var array
     * @access private
     */
    private $_scopes = array();

    /**
     * Context creation
     * 
     * @access public
     * @param array $data
     * @param array $options [array()]
     * @return void
     */
    public function __construct(array $data, array $options = array()) {
        $this->_scopes = array($data);

        if (isset($options['SAFE_CLASS'])) {
            $this->_options['SAFE_CLASS'] = array_merge(
                $this->_options['SAFE_CLASS'], $options['SAFE_CLASS']);
        }

        if (isset($options['autoescape'])) {
            $this->_autoescape = (bool)$options['autoescape'];
        }
    }

    public function __set($key, $val) {
        switch ($key) {
            case 'autoescape':
                $this->_autoescape = (bool)$val;
                break;
        }
    }

    /**
     * Push another layer into this context
     * 
     * @access public
     * @param array $data Array to initialize this layer with [array()]
     * @return int The number of layers in the context, including the new one
     */
    public function push(array $data = array()) {
        return array_unshift($this->_scopes, $data);
    }

    /**
     * If there are more than one layers, pop the most recent one off and pass
     * it back.
     *
     * @access public
     * @return array
     */
    public function pop() {
        if (!isset($this->_scopes[1])) {
            throw new Exception('Cannnot pop from empty stack');
        }

        return array_shift($this->_scopes);
    }

    /**
     * Resolve a string to its real value
     *
     * @todo add a lookup table for extensibility
     */
    public function resolve($key) {
        if ($key[0] == ':') {
            return $this->lookup(sym_to_str($key));
        }

        if ($key == 'true') {
            return true;
        } else if ($key == 'false') {
            return false;
        } else if (preg_match('/^-?\d+(\.\d+)?$/', $key, $matches)) {
            return isset($matches[1]) ? floatval($key) : intval($key);
        } else if (preg_match('/^"([^"\\\\]*(?:\\.[^"\\\\]*)*)"|'.
                            '\'([^\'\\\\]*(?:\\.[^\'\\\\]*)*)\'$/', $key)) {
            return stripcslashes(substr($key, 1, -1));
        }

        return null;
    }

    /**
     * Attempt to expand a dot-notation string and locate the value from the
     * current scope.
     *
     * Dot-notation allows us to use complex variable references to pull values
     * from objects and arrays in a context layer. If the object implements
     * ArrayAccess, it will be treated as an array and not an object.
     *
     * If a property is not found on an object, the lookup will check for a method
     * of the same name. For security, you must specify safe methods in the context
     * options.
     *
     * When looking up array values, you may use the special keys 'first', 'last',
     * 'length' and 'size'.
     * 
     * @access public
     * @param string $key
     * @see offsetGet()
     * @see $options
     * @return mixed NULL if the key is not found in this context, otherwise the value
     */
    public function lookup($key) {
        if (strpos($key, '.') === false) {
            return $this[$key];
        }

        $keys   = explode('.', $key);
        $object = $this[array_shift($keys)];

        foreach ($keys as $key) {
            if (is_array($object) or $object instanceof ArrayAccess) {
                $countable =  is_array($object) || ($object instanceof Countable);
                if (isset($object[$key])) {
                    $object = $object[$key];
                } else if ($key == 'first' && isset($object[0])) {
                    $object = $object[0];
                } else if ($key == 'last' && $countable) {
                    $key = count($object) - 1;

                    if (!isset($object[$key])) {
                        return null;
                    }

                    $object = $object[count($object) - 1];
                } else if ($countable && ($key == 'size' || $key == 'length')) {
                    return count($object);
                } else {
                    return null;
                }
            } else if (is_object($object)) {
                if (isset($object->$key)) {
                    $object = $object->$key;
                } else if (is_callable(array($object, $key))) {
                    $class = get_class($object);
                    $safe = isset($object->h2o_safe) ? $object->h2o_safe : array();

                    if (in_array($class, $this->_options['SAFE_CLASS'])) {
                        $object = $object->$key();
                    } else if (isset($this->_options['SAFE_CLASS'][$class])
                        && (in_array($key, $this->_options['SAFE_CLASS'][$class]))) {
                        $object = $object->$key();
                    } else if (in_array($key, $safe)) {
                        $object = $object->$key();
                    } else {
                        return null;
                    }
                } else {
                    return null;
                }
            } else {
                return null;
            }
        }

        return $object;
    }

    /**
     * Check if the key exists in one of the layers
     * 
     * @access public
     * @param string $key
     * @return bool
     */
    public function offsetExists($key) {
        foreach ($this->_scopes as $layer) {
            if (isset($layer[$key])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the key from the first layer that has it
     * 
     * @access public
     * @param mixed $key
     * @return mixed
     */
    public function offsetGet($key) {
        foreach ($this->_scopes as $layer) {
            if (isset($layer[$key])) {
                return $layer[$key];
            }
        }

        return null;
    }

    /**
     * Set a key on the most recent layer
     * 
     * @access public
     * @param string $key
     * @param mixed $val
     * @return void
     */
    public function offsetSet($key, $val) {
        if (strpos($key, '.') > -1) {
            throw new Exception('Cannot set non-local variable');
        }

        $this->_scopes[0][$key] = $val;
    }

    /**
     * Unset a key in every layer
     * 
     * @access public
     * @param string $key
     * @return void
     */
    public function offsetUnset($key) {
        foreach ($this->_scopes as $layer) {
            if (isset($layer[$key])) {
                unset($layer[$key]);
            }
        }
    }

    public function shouldEscape() {
        return $this->_autoescape;
    }
}
