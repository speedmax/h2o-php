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
     *  - PATH  Directory h2o should look for templates in [CWD/templates]
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

    /**
     * Initialize the options array, providing any necessary defaults
     *
     * @access public
     * @param array $options Custom options and settings for this instance [array()]
     * @see $_options
     */
    public function __construct(array $options = array()) {
        $this->_options = $options += array(
            'PATH'           => dirname(__FILE__).'/templates/',
            'TRIM_TAGS'      => false,
            'TAG_START'      => '{%',
            'TAG_END'        => '%}',
            'VARIABLE_START' => '{{',
            'VARIABLE_END'   => '}}',
            'COMMENT_START'  => '{*',
            'COMMENT_END'    => '*}'
        );

        spl_autoload_register(array(__CLASS__, 'autoload'));
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
            return;
        }

        require_once $file;
    }

    /**
     * Attempt to load a template source
     *
     * @access public
     * @param string $template
     * @return string
     */
    public function load($template) {
        $file = $this->_options['PATH'].$template;

        if (!is_file($file)) {
            throw new Exception(sprintf('Template `%s` was not found', $template));
        }

        return file_get_contents($file);
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

    public function parseFile($template) {
        $source = $this->load($template);

        return $this->parse($source);
    }

    /**
     * Attempt to load, parse and return a rendered template
     *
     * @access public
     * @param string $template Name of the template to render
     * @param array $context An array of key-value pairs to pass to the template [array()]
     * @see h2o_Context
     * @return string
     */
    public function render($template, array $context = array()) {
        $nodes   = $this->parseFile($template);
        $context = new h2o_Context($context);

        return $nodes->render($context);
    }
}
