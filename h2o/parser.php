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
 * Parser and Tokenizer
 *
 * @property-read array $First true if the current token is the first
 * @property-read array $Runtime reference to the parent h2o object
 * @property-read array $Token the last tag token that was found
 */
class h2o_Parser {
    /**
     * Is this the first token?
     *
     * @var bool
     * @access private
     */
    private $_first = true;

    /**
     * The regex pattern
     * 
     * @var string
     * @access private
     */
    private $_pattern;

    /**
     * Reference to the parent h2o instance
     * 
     * @var h2o
     * @access private
     */
    private $_runtime;

    /**
     * Source we are parsing
     *
     * @var string
     * @access private
     */
    private $_source;
    
    public $storage = array('blocks' => array());

    /**
     * The last tag token that was found. Only tag tokens are stored in this
     * property.
     * 
     * @var array
     * @access private
     */
    private $_token;

    /**
     * Collection of tokens
     *
     * @var array
     * @access private
     * @see tokenize()
     */
    private $_tokens = array();

    /**
     * Construct the regex pattern for tokenizing and then tokenize the source
     *
     * @access public
     * @param string $source
     * @param string array $options options array from h2o
     * @return void
     * @see h2o::$_options
     * @see h2o::parse()
     */
    public function __construct(h2o &$runtime, $source, array $options) {
        $trim  = $options['TRIM_TAGS'] ? '(?:\r?\n)?' : '';

        $this->_pattern = ('/\G(.*?)(?:'.
            preg_quote($options['TAG_START']).'(.*?)'.preg_quote($options['TAG_END']).$trim.'|'.
            preg_quote($options['VARIABLE_START']).'(.*?)'.preg_quote($options['VARIABLE_END']).'|'.
            preg_quote($options['COMMENT_START']).'(.*?)'.preg_quote($options['COMMENT_END']).$trim.')/sm'
        );

        $this->_runtime = $runtime;
        $this->_source  = $source;
        $this->_tokens  = $this->tokenize($source);
    }

    public function __get($key) {
        switch ($key) {
            case 'First':
            case 'Runtime':
            case 'Token':
                $key = '_'.strtolower($key);
                return $this->$key;
        }

        return null;
    }

    /**
     * Turn tokens into nodes for rendering
     *
     * This method takes an unlimited number of arguments for tags the parser
     * should stop on. See {@link h2o_Tag_For} for an example.
     *
     * @access public
     * @return h2o_NodeStack
     * @see tokenize()
     */
    public function parse() {
        $until = func_get_args();
        $nodes = new h2o_NodeStack($this);

        while ($token = $this->_tokens->next()) {
            switch ($token['type']) {
                case 'text':
                    $node = new h2o_Node($token['content']);
                    break;
                case 'variable':
                    $node = new h2o_Node_Variable($token['content']);
                    break;
                case 'tag':
                    if (in_array(trim($token['content']), $until)) {
                        $this->_token = $token;
                        return $nodes;
                    }

                    @list($tag, $args) = preg_split('/\s+/', trim($token['content']), 2);
                    $class = 'h2o_Tag_'.ucwords($tag);

                    // Attempt to load the file so we can check for the class and fail gracefully
                    h2o::autoload($class);

                    if (!class_exists($class)) {
                        throw new RuntimeException(sprintf('invalid tag `%s` found at %d', $tag, $token['position']));
                        continue 2;
                    }

                    $node = new $class($args, $this);
                    $this->_token = $token;
                    break;
                default:
                    continue 2;
            }

            $nodes->append($node);
            $this->_first = false;
        }

        if ($until) {
            throw new RuntimeException('Unclosed tag, expecting '. $until[0]);
        }

        return $nodes;
    }

    /**
     * Takes a raw source and tokenizes it
     *
     * If a comment is encountered, it is ignored.
     *
     * @access public
     * @param string $source
     * @return h2o_TokenStack
     */
    public function tokenize($source) {
        $matches  = array();
        $result   = new h2o_TokenStack;
        $position = 0;

        preg_match_all($this->_pattern, $source, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if (!empty($match[1])) {
                $result->feed('text', $match[1], $position);
            }

            $tag_position = $position + strlen($match[1]);

            if (!empty($match[2])) {
                $result->feed('tag', trim($match[2]), $tag_position);
            } else if (!empty($match[3])) {
                $result->feed('variable', trim($match[3]), $tag_position);
            }

            $position += strlen($match[0]);
        }

        if ($position < strlen($source)) {
            $result->feed('text', substr($source, $position), $position);
        }

        $result->close();
        return $result;
    }
}
