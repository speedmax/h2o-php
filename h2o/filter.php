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
 * Base filter object
 *
 * @abstract
 */
abstract class h2o_Filter {
    /**
     * Limit {@link add()} recursion to this amount
     *
     * @var int
     * @access private
     */
    static private $_depthLimit = 10;

    /**
     * Collection of filters
     *
     * @var array
     * @access private
     */
    static private $_filters = array();

    /**
     * Add a filter to the mix
     *
     * An array or traversable object may be passed as the first parameter to
     * easily add a collection of filters. The abstract class
     * {@link Filter_Collection} can be used to create a collection of complex
     * filters, or you may use your own custom iterator.
     *
     * @access public
     * @static
     * @param string $filter
     * @param callback $callback [$filter]
     * @return void
     * @see $_depthLimit
     */
    static public function add($filter, $callback = null) {
        static $depth = 0;

        if (($filter instanceOf Traversable) || ($depth == 0 && is_array($filter))) {
            if ($depth > self::$_depthLimit) {
                throw new RuntimeException(sprintf('Filter::add has breached the '.
                    'maximum depth of `%d`', self::$_depthLimit));
            }

            foreach ($filter as $key => $callback) {
                $depth++;
                if ($callback instanceOf Traversable) {
                    self::add($callback);
                } else {
                    self::add($key, $callback);
                }
                $depth--;
            }

            return;
        }

        if (is_null($callback)) {
            $callback = $filter;
        }

        if (!is_callable($callback)) {
            var_dump($filter, $callback);
            throw new RuntimeException('Filter must be a valid callback');
        }

        self::$_filters[$filter] = $callback;
    }

    /**
     * Run a filter over the given content
     *
     * @access public
     * @static
     * @param string $filter
     * @param string $content
     * @param array $arguments [array()]
     * @return string
     */
    static public function run($filter, $content, array $arguments = array()) {
        if (!isset(self::$_filters[$filter])) {
            return $content;
        }

        $callback = self::$_filters[$filter];
        array_unshift($arguments, $content);

        return call_user_func_array($callback, $arguments);
    }
}

abstract class Filter_Collection implements IteratorAggregate {
    protected $_auto = true;

    private $_cache;

    protected $_valid = array();

    public function getIterator() {
        if (is_null($this->_cache)) {
            $cache = new ArrayObject($this->_valid);

            if ($this->_auto) {
                $schema = new ReflectionClass(get_class($this));

                foreach ($schema->getMethods() as $method) {
                    if ($method->isPublic() && $method->isStatic()) {
                        $cache[$method->name] = array($schema->name, $method->name);
                    }
                }
            }
            
            $this->_cache = $cache;
        }

        return $this->_cache->getIterator();
    }
}
