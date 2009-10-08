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

        if (is_string($filter) && class_exists($filter)) {
            $filter = FilterCollection::keys($filter);

            if (count($filter) == 0) {
                return;
            }
        }

        if (($filter instanceOf FilterCollection) || is_array($filter)) {
            if ($depth > self::$_depthLimit) {
                throw new RuntimeException(sprintf('Filter::add has breached the '.
                    'maximum depth of `%d`', self::$_depthLimit));
            }

            foreach ($filter as $key => $callback) {
                $depth++;
                if ($callback instanceOf Traversable) {
                    self::add($callback);
                } else if (is_numeric($key)) {
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
            throw new RuntimeException('Filter must be a valid callback');
        }

        self::$_filters[$filter] = $callback;
    }

    static public function exists($filter) {
        return isset(self::$_filters[$filter]);
    }

    static public function export() {
        return self::$_filters;
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

abstract class FilterCollection {
    static public function keys($collection, $keys = false) {
        static $cache = array();

        if (is_null($cache[$collection])) {
            $data   = array();
            $schema = new ReflectionClass($collection);

            foreach ($schema->getMethods() as $method) {
                if ($method->name == 'keys') continue;

                if ($method->isPublic() && $method->isStatic()) {
                    $data[$method->name] = array($schema->name, $method->name);
                }
            }

            $cache[$collection] = $data;
        }

        return !$keys ? $cache[$collection] : array_keys($cache[$collection]);
    }
}
