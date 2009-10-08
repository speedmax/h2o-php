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

defined('H2O_PATH') or define('H2O_PATH', dirname(__FILE__).'/');

require_once H2O_PATH.'/h2o/stacks.php';

spl_autoload_register(array('h2o', 'autoload'));

// Setup base tags and filters
require_once H2O_PATH.'/ext/filters.php';
h2o_Tag::add(array('if', 'for', 'extends', 'include', 'with', 'block'));

/**
 * H2O is markup language for PHP that taken a lot of inspiration from Django.
 *
 *  - Readable and human friendly syntax.
 *  - Easy to use and maintain
 *  - Encourage reuse in templates by template inclusion and inheritance.
 *  - highly extensible through filters, tags and template extensions.
 *  - Bundled rich set of filters and tags for string formatting, HTML helpers and internationalization. 
 */
class h2o {
    /**
     * Options and settings for this h2o instance
     *
     * Available settings are:
     *
     *  - searchpath  Directory h2o should look for templates in [CWD/templates]
     *  - autoescape  If set, output will be escaped automatically [true]
     *
     * Available parser options are:
     *
     *  - TRIM_TAGS       If true, all whitespace will be trimmed off the end of
     *                    a tag [false]
     *  - TAG_START       Token to mark the start of a tag. [{%]
     *  - TAG_END         Token to mark the end of a tag [%}]
     *  - VARIABLE_START  Token to mark the start of a variable [{{]
     *  - VARIABLE_END    Token to mark the end of a variable [}}]
     *  - COMMENT_START   Token to mark the start of a comment [{*]
     *  - COMMENT_END     Token to mark the end of a comment [*}]
     *
     * @var array
     * @access private
     */
    private $_options;

    private $_template;

    private $_nodes;

    /**
     * Initialize the options array, providing any necessary defaults
     *
     * For backwards compatibility you may pass both a template name and the
     * options array to the constructor. New behaviour is to pass only the
     * options and specify the template at {@link render()} time.
     *
     * @access public
     * @param array $options Custom options and settings for this instance [array()]
     * @see $_options
     */
    public function __construct($options = array(), array $optional = array()) {
        // Handle the old constructor
        if (!is_array($options)) {
            if (is_string($options)) {
                $this->_template = $options;
            }

            $options = $optional;
        }

        $this->_options = $options += array(
            'searchpath'     => getcwd(),
            'autoescape'     => true,
            'loader'         => 'file',

            'TRIM_TAGS'      => true,
            'TAG_START'      => '{%',
            'TAG_END'        => '%}',
            'VARIABLE_START' => '{{',
            'VARIABLE_END'   => '}}',
            'COMMENT_START'  => '{*',
            'COMMENT_END'    => '*}'
        );

        if ($this->_options['searchpath'][0] != '/') {
            $this->_options['searchpath'] = realpath($this->_options['searchpath']);
        }

        if (substr($this->_options['searchpath'], -1) != '/') {
            $this->_options['searchpath'] .= '/';
        }

        $this->setLoader($this->_options['loader']);
    }

    public function __get($key) {
        switch ($key) {
            case 'Loader':
                return $this->_loader;
        }

        return null;
    }

    /**
     * Attempt to load class files
     *
     * h2o_Node_Variable becomes H2O_PATH/h2o/node/variable.php
     *
     * @access public
     * @static
     * @param mixed $className
     * @return void
     */
    static public function autoload($className) {
        if (substr($className, 0, 4) != 'h2o_') {
            return;
        }

        $file = H2O_PATH.'/'.strtolower(str_replace('_', '/', $className)).'.php';

        if (!is_file($file)) {
            return false;
        }

        require_once $file;

        return true;
    }

    public function setLoader($loader) {
        if ($loader instanceOf h2o_Loader) {
            $this->_loader = $loader;
            return true;
        }

        $loaderClass = 'h2o_Loader_'.$loader;

        if (!self::autoload($loaderClass)) {
            throw new RuntimeException(sprintf('Loader `%s` not found', $loader));
        }

        $this->_loader = new $loaderClass($this->_options);
    }

    /**
     * Parse a template and return a node stack
     *
     * @access public
     * @param string $source
     * @param mixed $options Custom parser settings for this run [null]
     * @see $_options
     * @return h2o_NodeStack
     */
    public function parse($source, array $options = null) {
        $parser = new h2o_Parser($this, $source, is_null($options) ? $this->_options : $options);

        return $parser->parse();
    }

    public function parseToNodes($template) {
        $source = $this->_loader->load($template);

        return $this->parse($source);
    }

    public function parseString($template) {
        return ($this->_nodes = $this->parse($template));
    }

    public function loadTemplate($template) {
        $this->_nodes = $this->parseToNodes($template);
    }

    /**
     * Attempt to load, parse and return a rendered template
     *
     * To preserve backwards compatibility, the first parameter may be either
     * an array or a string. If an array is passed, {@link $_template}
     * is used as the template.
     *
     * @access public
     * @param string $template Name of the template to render
     * @param array $context An array of key-value pairs to pass to the template [array()]
     * @see h2o_Context
     * @return string
     */
    public function render($template = null, array $context = array()) {
        // Handle the old render syntax
        if ((is_null($template) && is_array($context)) || is_array($template) || ($template instanceOf h2o_Context)) {
            if (empty($this->_template) && empty($this->_nodes)) {
                throw new RuntimeException('Using old h2o::render snytax with new h2o::__construct');
            }

            if (!is_null($template)) {
                $context  = $template;
            }

            $template = $this->_template;
        }

        if (empty($this->_nodes)) {
            $this->_nodes = $this->parseToNodes($template);
        }

        if (is_array($context)) {
            $context = new h2o_Context($context, $this->_options);
        }

        return $this->_nodes->render($context);
    }
}

/**
 * Convenience wrapper for loading templates or parsing a string
 *
 * @param string $name Name of the template to load or a raw template string
 * @param array $options Options to pass to the h2o instance
 * @return h2o
 */
function h2o($name, array $options = array()) {
    if (preg_match('/([^\s]*?)(\.[^.\s]*$)/', $name)) {
        return new h2o($name, $options);
    } else {
        $instance = new h2o($options);
        $instance->parseString($name);
        return $instance;
    }
}

function sym_to_str($string) {
    return substr($string, 1);
}

function is_sym($string) {
    return isset($string[0]) && $string[0] === ':';
}

function symbol($string) {
    return ':'.$string;
}
