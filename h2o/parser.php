<?php
class H2o_Lexer {
    function __construct($options = array()) {
        $this->options = $options;
        
        if ($this->options['TRIM_TAGS'])
            $trim = '(?:\r?\n)?';

        $this->pattern = ('/\G(.*?)(?:' .
            preg_quote($this->options['BLOCK_START']). '(.*?)' .preg_quote($this->options['BLOCK_END']) . $trim . '|' .
            preg_quote($this->options['VARIABLE_START']). '(.*?)' .preg_quote($this->options['VARIABLE_END']) . '|' .
            preg_quote($this->options['COMMENT_START']). '(.*?)' .preg_quote($this->options['COMMENT_END']) . $trim . ')/sm'
        );
    }

    function tokenize($source) {
        $result = new TokenStream;
        $pos = 0;
        $matches = array();
        preg_match_all($this->pattern, $source, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if ($match[1])
            $result->feed('text', $match[1], $pos);
            $tagpos = $pos + strlen($match[1]);
            if ($match[2])
            $result->feed('block', trim($match[2]), $tagpos);
            elseif ($match[3])
            $result->feed('variable', trim($match[3]), $tagpos);
            elseif ($match[4])
            $result->feed('comment', trim($match[4]), $tagpos);
            $pos += strlen($match[0]);
        }
        if ($pos < strlen($source)){
            $result->feed('text', substr($source, $pos), $pos);
        }
        $result->close();
        return $result;
    }
}

class H2o_Parser {
    var $first;
    var $storage = array();
    var $filename;
    var $runtime;
    
    function __construct($source, $filename, $runtime, $options) {
        $this->options = $options;
        //$this->source = $source;
        $this->runtime = $runtime;
        $this->filename = $filename;
        $this->first = true;
        
        $this->lexer = new H2o_Lexer($options);
        $this->tokenstream = $this->lexer->tokenize($source);
        $this->storage = array(
          'blocks' => array(),
          'templates' => array(),
          'included' => array()
        );
    }

    function &parse() {
        $until = func_get_args();
        $nodelist = new NodeList($this);
        while($token = $this->tokenstream->next()) { 
            //$token = $this->tokenstream->current();
            switch($token->type) {
                case 'text' :
                    $node = new TextNode($token->content, $token->position);
                    break;
                case 'variable' :
                    $args = H2o_Parser::parseArguments($token->content, $token->position);
                    $variable = array_shift($args);
                    $filters = $args;
                    $node = new VariableNode($variable, $filters, $token->position);
                    break;
                case 'comment' :
                    $node = new CommentNode($token->content);
                    break;
                case 'block' :
                    if (in_array($token->content, $until)) {
                        $this->token = $token;                      
                        return $nodelist;
                    }
                    @list($name, $args) = preg_split('/\s+/',$token->content, 2);
                    $node = H2o::createTag($name, $args, $this, $token->position);
                    $this->token = $token;
            }
            $this->searching = join(',',$until);
            $this->first = false;
            $nodelist->append($node);
        }

        if ($until) {
            throw new TemplateSyntaxError('Unclose tag, expecting '. $until[0]);
        }
        return $nodelist;
    }

    function skipTo($until) {
        $this->parse($until);
        return null;
    }

    # Parse arguments
    static function parseArguments($source = null, $fpos = 0){
        $parser = new ArgumentLexer($source, $fpos);
        $result = array();
        $current_buffer = &$result;
        $filter_buffer = array();
        $tokens = $parser->parse();
        foreach ($tokens as $token) {
            list($token, $data) = $token;
            if ($token == 'filter_start') {
                $filter_buffer = array();
                $current_buffer = &$filter_buffer;
            }
            elseif ($token == 'filter_end') {
                if (count($filter_buffer))
                    $result[] = $filter_buffer;
                $current_buffer = &$result;
            }
            elseif ($token == 'boolean') {
                $current_buffer[] = ($data === 'true'? true : false);
            }            
            elseif ($token == 'name') {
                $current_buffer[] = symbol($data);
            }
            elseif ($token == 'number' || $token == 'string') { 
                $current_buffer[] = $data;
            } 
            elseif ($token == 'named_argument') {
                $last = $current_buffer[count($current_buffer) - 1];
                if (!is_array($last))
                    $current_buffer[] = array();

                $namedArgs =& $current_buffer[count($current_buffer) - 1]; 
                list($name,$value) = array_map('trim', explode(':', $data, 2));
                
                # if argument value is variable mark it
                $value = self::parseArguments($value);
                $namedArgs[$name] = $value[0];
            }
            elseif( $token == 'operator') {
                $current_buffer[] = array('operator'=>$data);
            }
        }
        return $result;
    }
}

class H2O_RE {
    static $whitespace, $seperator, $parentheses, $pipe, $filter_end, $operator, $boolean, $number,  $string, $i18n_string, $name, $named_args;

    function init() {
        $r = 'strip_regex';
        
        self::$whitespace   = '/\s+/m';
        self::$parentheses  = '/\(|\)/m';
        self::$filter_end   = '/;/';
        self::$boolean    = '/true|false/';
        self::$seperator    = '/,/';
        self::$pipe         = '/\|/';
        self::$operator     = '/\s?(>|<|>=|<=|!=|==|!|and |not |or )\s?/i';
        self::$number       = '/\d+(\.\d*)?/';
        self::$name         = '/[a-zA-Z][a-zA-Z0-9-_]*(?:\.[a-zA-Z_0-9][a-zA-Z0-9_-]*)*/';
        
        self::$string       = '/(?:
                "([^"\\\\]*(?:\\\\.[^"\\\\]*)*)" |   # Double Quote string   
                \'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\' # Single Quote String
        )/xsm';
        self::$i18n_string  = "/_\({$r(self::$string)}\) | {$r(self::$string)}/xsm";

        self::$named_args   = "{
            ({$r(self::$name)})(?:{$r(self::$whitespace)})?
            : 
            (?:{$r(self::$whitespace)})?({$r(self::$i18n_string)}|{$r(self::$number)}|{$r(self::$name)})
        }x";
    }
}
H2O_RE::init();

class ArgumentLexer {
    private $source;
    private $match;
    private $pos = 0, $fpos, $eos;
    private $operator_map = array(
        '!' => 'not', '!='=> 'ne', '==' => 'eq', '>' => 'gt', '<' => 'lt', '<=' => 'le', '>=' => 'ge'
    );

    function __construct($source, $fpos = 0){
        if (!is_null($source))
          $this->source = $source;
        $this->fpos=$fpos;
    }

    function parse(){
        $result = array();
        $filtering = false;
        while (!$this->eos()) {
            $this->scan(H2O_RE::$whitespace);
            if (!$filtering) {
                if ($this->scan(H2O_RE::$operator)){
                    $operator = trim($this->match);
                    if(isset($this->operator_map[$operator]))
                        $operator = $this->operator_map[$operator];
                    $result[] = array('operator', $operator);
                }
                elseif ($this->scan(H2O_RE::$boolean))
                    $result[] = array('boolean', $this->match);
                elseif ($this->scan(H2O_RE::$named_args))
                    $result[] = array('named_argument', $this->match);                      
                elseif ($this->scan(H2O_RE::$name))
                    $result[] = array('name', $this->match);
                elseif ($this->scan(H2O_RE::$pipe)) {
                    $filtering = true;
                    $result[] = array('filter_start', $this->match);
                }
                elseif ($this->scan(H2O_RE::$seperator))
                    $result[] = array('separator', null);
                elseif ($this->scan(H2O_RE::$i18n_string))
                    $result[] = array('string', $this->match);
                elseif ($this->scan(H2O_RE::$number))
                    $result[] = array('number', $this->match);
                else
                    throw new TemplateSyntaxError('unexpected character in filters : "'. $this->source[$this->pos]. '" at '.$this->getPosition());
            } 
            else {
                // parse filters, with chaining and ";" as filter end character
                if ($this->scan(H2O_RE::$pipe)) {
                    $result[] = array('filter_end', null);
                    $result[] = array('filter_start', null);
                }
                elseif ($this->scan(H2O_RE::$seperator))
                    $result[] = array('separator', null);
                elseif ($this->scan(H2O_RE::$filter_end)) {
                    $result[] = array('filter_end', null);
                    $filtering = false;
                }
                elseif ($this->scan(H2O_RE::$boolean))
                    $result[] = array('boolean', $this->match);
                elseif ($this->scan(H2O_RE::$named_args))
                    $result[] = array('named_argument', $this->match);
                elseif ($this->scan(H2O_RE::$name))
                    $result[] = array('name', $this->match);
                elseif ($this->scan(H2O_RE::$i18n_string))
                    $result[] = array('string', $this->match);
                elseif ($this->scan(H2O_RE::$number))
                    $result[] = array('number', $this->match);          
                else
                    throw new TemplateSyntaxError('unexpected character in filters : "'. $this->source[$this->pos]. '" at '.$this->getPosition());
            }
        }
        // if we are still in the filter state, we add a filter_end token.
        if ($filtering)
            $result[] = array('filter_end', null);
        return $result;
    }

    # String scanner
    function scan($regexp) {
        if (preg_match($regexp . 'A', $this->source, $match, null, $this->pos)) {
            $this->match = $match[0];
            $this->pos += strlen($this->match);
            return true;
        }
        return false;
    }

    function eos() {
        return $this->pos >= strlen($this->source);
    }
    
    /**
     * return the position in the template
     */
    function getPosition() {
        return $this->fpos + $this->pos;
    }
}
?>