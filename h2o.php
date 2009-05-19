<?php
define('H2O_VERSION', '0.3');
defined('DS') or define('DS', DIRECTORY_SEPARATOR);
defined('H2O_ROOT') or define('H2O_ROOT', dirname(__FILE__) . DS);

require H2O_ROOT.'h2o/datatype.php';
require H2O_ROOT.'h2o/loaders.php';
require H2O_ROOT.'h2o/nodes.php';
require H2O_ROOT.'h2o/tags.php';
require H2O_ROOT.'h2o/errors.php';
require H2O_ROOT.'h2o/filters.php';
require H2O_ROOT.'h2o/context.php';

/**
 * Example:
 *  $h2o = new H2O('./template.html', array("loader"=>'file'));
 *  
 *  
 *  $h2o = new H2O('template.html', array("loader"=>'hash'));
 */
class H2o {
    var $searchpath;
    var $context;
    var $loader = false;
    
    static $tags = array();
    static $filters = array();
    static $extensions = array();
    
    function getOptions($options = array()) {
        return array_merge(array(
            'loader'            =>       'file',
            'cache'             =>      'file',     // file | apc | memcache
            'cache_prefix'      =>      'h2o_',
            'cache_ttl'         =>      3600,     // file | apc | memcache
            'searchpath'        =>      false,
            'autoescape'        =>      true,
        
            // Enviroment setting
            'BLOCK_START'       =>      '{%',
            'BLOCK_END'         =>      '%}',
            'VARIABLE_START'    =>      '{{',
            'VARIABLE_END'      =>      '}}',
            'COMMENT_START'     =>      '{*',
            'COMMENT_END'       =>      '*}',
            'TRIM_TAGS'         =>      true
        ), $options);
    }
    
    function __construct($file = null, $options = array()) {
        # Init a environment
        $this->options = $this->getOptions($options);        
        $loader = $this->options['loader'];

        if (!$loader)
            return true;

        if (is_object($loader)) {
            $this->loader = $loader;
            $this->loader->setOptions($this->options);
        } else {
            $loader = "H2o_{$loader}_Loader";
            if (!class_exists($loader))
                throw new Exception('Invalid template loader');
                
            if (isset($options['searchpath']))
                $this->searchpath = realpath($options['searchpath']).DS;
            elseif ($file)
                $this->searchpath = dirname(realpath($file)).DS;
            else
                $this->searchpath = getcwd().DS;

            $this->loader = new $loader($this->searchpath, $this->options);        
        }
        $this->loader->runtime = $this;
        
        if (isset($options['i18n'])) {
            h2o::load('i18n');
            $this->i18n = new H2o_I18n($this->searchpath, $options['i18n']);
        }
    
        if ($file) {
            $this->nodelist = $this->loadTemplate($file);
        }
    }

    function loadTemplate($file) {
        return $this->nodelist = $this->loader->read_cache($file);
    }
    
    function loadSubTemplate($file) {
        return $this->loader->read($file);
    }
    
    # Build a finalized nodelist from template ready to be cached
    function parse($source, $filename = '', $env = null) {
        if (!$env)
            $env = $this->options;

        if (!class_exists('H2o_Parser'))
            require H2O_ROOT.'h2o/parser.php';

        $parser = new H2o_Parser($source, $filename, $this, $env);
        $nodelist = $parser->parse();
        return $nodelist;
    }

    function set($context, $value = null) {
        # replace with new context object
        if (is_object($context) && $context instanceof H2o_Context) {
            return $this->context = $context;
        }

        # Init context
        if (!$this->context) {
            $this->context = new H2o_Context($this->defaultContext(), $this->options);
        }
        
        # Extend or set value
        if (is_array($context)) {
            return $this->context->extend($context);
        } 
        elseif (is_string($context)) {
            return $this->context[$context] = $value;
        }
        return false;
    }
    
    # Render the nodelist
    function render($context = array()) {
        $this->set($context);

        $this->stream = new StreamWriter;
        $this->nodelist->render($this->context, $this->stream);
        return $this->stream->close();
    }

    static function parseString($source, $options = array()) {
        $instance = new H2o(null, array_merge($options, array('loader' => false)));
        $instance->nodelist = $instance->parse($source);
        return $instance;
    }

    static function &createTag($tag, $args = null, $parser, $position = 0) {
        if (!isset(self::$tags[$tag])) {
            throw new H2o_Error($tag . " tag doesn't exist");
        }
        $tagClass = self::$tags[$tag];
        $tag = new $tagClass($args, $parser, $position);
        return $tag;
    }

    /**
     * Register a new tag
     *
     * 
     * h2o::addTag('tag_name', 'ClassName');
     * 
     * h2o::addTag(array(
     *      'tag_name' => 'MagClass',
     *      'tag_name2' => 'TagClass2'
     * ));
     *
     *  h2o::addTag('tag_name');      // Tag_name_Tag
     * 
     * h2o::addTag(array('tag_name', 
     * @param unknown_type $tag
     * @param unknown_type $class
     */
    static function addTag($tag, $class = null) {
        $tags = array();
        if (is_string($tag)) {
            if (is_null($class)) 
                $class = ucwords("{$tag}_Tag");
            $tags[$tag] = $class;
        } elseif (is_array($tag)) {
            $tags = $tag;
        }
        
        foreach ($tags as $tag => $tagClass) {
            if (is_integer($tag)) {        
                unset($tags[$tag]);
                $tag = $tagClass;
                $tagClass = ucwords("{$tagClass}_Tag");
            }
            if (!class_exists($tagClass)) {
                throw new H2o_Error("{$tagClass} tag is not found");
            }
            $tags[$tag] = $tagClass;
        }
        self::$tags = array_merge(self::$tags, $tags);
    }

    /**
     * Register a new filter to h2o runtime
     *
     * @param unknown_type $filter
     * @param unknown_type $callback
     * @return unknown
     */
    static function addFilter($filter, $callback = null) {
        if (is_array($filter)) {
            $filters = $filter;
            foreach($filters as $key => $filter) {
                if (is_numeric($key))
                    $key = $filter;
                self::addFilter($key, $filter);
            }
            return true;
        } elseif (is_string($filter) && class_exists($filter) && is_subclass_of($filter, 'FilterCollection')) {
            foreach (get_class_methods($filter) as $f) {
                if (is_callable(array($filter, $f)))
                    self::$filters[$f] = array($filter, $f);
            }
            return true;
        }
        if (is_null($callback))
            $callback = $filter;
            
        if (!is_callable($callback)) {
            return false;
        }
        self::$filters[$filter] = $callback;
    }
    
    static function addLookup($callback) {
        if (is_callable($callback)) {
            H2o_Context::$lookupTable[] = $callback;
        } else die('damm it');
    }
    
    static function load($extension, $file = null) {
        if (!$file) {
            $file = H2O_ROOT.'ext'.DS.$extension.'.php';
        }
        if (is_file($file)) {
            include_once ($file);
            self::$extensions[$extension] = $file;
        }
    }

    function defaultContext() {
        return array('h2o' => new H2o_Info);
    }
}

/**
 * Convenient wrapper for loading template file or string
 * @param $name
 * @param $options - H2o options
 * @return Instance of H2o Template
 */
function h2o($name, $options = array()) {
    $is_file = '/([^\s]*?)(\.[^.\s]*$)/';
    
    if (!preg_match($is_file, $name)) {
        return H2o::parseString($name, $options); 
    }

    $instance = new H2o($name, $options);
    return $instance;
}

?>