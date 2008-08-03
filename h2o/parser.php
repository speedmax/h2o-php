<?php
class H2o_Parser {
    var $first;
    var $storage = array();
    protected $runtime;
    
	function __construct($source, $filename, $options) {
		$this->options = $options;
		$this->source = $source;
		$this->first = true;
		
        if ($this->options['TRIM_TAGS'])
			$trim = '(?:\r?\n)?';
			
		$this->pattern = ('/(.*?)(?:' .
			preg_quote($this->options['BLOCK_START']). '(.*?)' .preg_quote($this->options['BLOCK_END']) . $trim . '|' .
			preg_quote($this->options['VARIABLE_START']). '(.*?)' .preg_quote($this->options['VARIABLE_END']) . '|' .
			preg_quote($this->options['COMMENT_START']). '(.*?)' .preg_quote($this->options['COMMENT_END']) . $trim . ')/sm'
		);
		$this->tokenstream = $this->tokenize();
	}

	function tokenize() {
		$result = new TokenStream;
		$pos = 0;
		$matches = array();
		preg_match_all($this->pattern, $this->source, $matches, PREG_SET_ORDER);

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
		if ($pos < strlen($this->source)){
			$result->feed('text', substr($this->source, $pos), $pos);
		}
		$result->close();
		return $result;
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
					$variables = $filters = array();
					$args = H2o_Parser::parseArguments($token->content, $token->position);

					// Parse out filters and variables
					foreach ($args as $data){
						if (is_array($data)) {
							$filters[] = $data;
						} else {
							$variables[] = $data;
						}
					}
					$node = new VariableNode($variables, $filters, $token->position);
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
    static function parseArguments($source = null, $fpos){
        $parser = new ArgumentLexer($source, $fpos);
        $result = array();
        $current_buffer = &$result;
        $filter_buffer = array();
        foreach ($parser->parse() as $token) {
            list($token, $data) = $token;
            if ($token == 'filter_start') {
                $filter_buffer = array();
                $current_buffer = &$filter_buffer;
            }
            elseif ($token == 'filter_end') {
                if (count($filter_buffer))
                    $result [] = $filter_buffer;
                $current_buffer = &$result;
            }
            elseif ($token == 'name' || $token == 'number' || $token == 'string') {
                $current_buffer[] = $data;
            }
            elseif ($token == 'named_argument') {
                list($name,$value) = preg_split('/:/',$data,2);
                $current_buffer[trim($name)] = trim($value);
            }
            elseif( $token == 'operator') {
                $current_buffer[] = array('operator'=>$data);
            }
        }
        return $result;
    }
}

class ArgumentLexer {
	/**
	 * Argument source
	 */
	var $source;
	var $match;
	var $pos = 0;
	var $fpos;
	var $eos;
	var $options;

	static function getOptions(){
		return array(
		/*	Argument regex	*/
		'WHITESPACE_RE' => '/\s+/m',
		'PARENTHESES_RE' => '/\(|\)/m',
		'NAME_RE' => '/[a-zA-Z_][a-zA-Z0-9-_]*(\.[a-zA-Z_0-9][a-zA-Z0-9_-]*)*/',
		'PIPE_RE' => '/\|/' ,
		'SEPARATOR_RE' => '/,/',
		'FILTER_END_RE' => '/;/',
		'STRING_RE' => '/(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\')/sm',
		'NUMBER_RE' => '/\d+(\.\d*)?/',
		'OPERATOR_RE' => '/\s?(>|<|=|>=|<=|!=|==|=|and|not|or)\s/i',
		'NAMED_ARGS_RE' => '/([a-zA-Z_][a-zA-Z0-9_-]*(?:\.[a-zA-Z_][a-zA-Z0-9_-]*)*)\s?:\s?((?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\')|\d+(\.\d*)?|[a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z_][a-zA-Z0-9_]*)*)/',

		/*	Replace operator	*/
		'operator_replace' => array('and'	=> '&&',
		'or'	=> '||',
		'not'	=> '!',
		'='		=> '=='),
		);
	}

	function __construct($source, $fpos){
		$this->pos = 0;
		if(!is_null($source))
		$this->source = $source;
		$this->options = $this->getOptions();
		$this->fpos=$fpos;
	}

	function parse(){
		$result = array();
		$filtering = false;
		$options = $this->options;
		while (!$this->eos()) {
			$this->scan($options['WHITESPACE_RE']);
			if (!$filtering) {
				if ($this->scan($options['OPERATOR_RE'])){
					$operator = $this->match;
					if(isset($options['operator_replace'][trim($operator)]))
					$operator = $options['operator_replace'][trim($operator)];
					$result[] = array('operator', $operator);
				}
				elseif ($this->scan($options['NAMED_ARGS_RE']))
				    $result[] = array('named_argument', $this->match);						
				elseif ($this->scan($options['NAME_RE']))
				    $result[] = array('name', $this->match);
				elseif ($this->scan($options['PIPE_RE'])) {
					$filtering = true;
					$result[] = array('filter_start', $this->match);
				}
				elseif ($this->scan($options['SEPARATOR_RE']))
				    $result[] = array('separator', null);
				elseif ($this->scan($options['STRING_RE']))
				    $result[] = array('string', $this->match);
				elseif ($this->scan($options['NUMBER_RE']))
				    $result[] = array('number', $this->match);
				else
				//TODO: global error handing
				die ('unexpected character in filters : "'. $this->source[$this->pos]. '" at '.$this->getPosition());
			}
			else {
				// parse filters, with chaining and ";" as filter end character
				if ($this->scan($options['PIPE_RE'])) {
				    $result[] = array('filter_end', null);
				    $result[] = array('filter_start', null);
				}
				elseif ($this->scan($options['SEPARATOR_RE']))
				    $result[] = array('separator', null);
				elseif ($this->scan($options['FILTER_END_RE'])) {
				    $result[] = array('filter_end', null);
					$filtering= false;
				}
				elseif ($this->scan($options['NAMED_ARGS_RE']))
				    $result[] = array('named_argument', $this->match);
				elseif ($this->scan($options['NAME_RE']))
				    $result[] = array('name', $this->match);
				elseif ($this->scan($options['STRING_RE']))
				    $result[] = array('string', $this->match);
				elseif ($this->scan($options['NUMBER_RE']))
				    $result[] = array('number', $this->match);			
				else
				//TODO: global error handing
				die ('unexpected character in filters : "'. $this->source[$this->pos]. '" at '.$this->getPosition());
			}
		}
		// if we are still in the filter state, we add a filter_end token.
		if ($filtering)
		    $result[] = array('filter_end', null);
		return $result;
	}

	function eos() {
		return $this->pos >= strlen($this->source);
	}

	function scan($regexp) {
		if (preg_match($regexp . 'A', $this->source, $match, null, $this->pos)) {
			$this->match = $match[0];
			$this->pos += strlen($this->match);
			return true;
		}
		return false;
	}
	/**
	 * return the position in the template
	 */
	function getPosition() {
		return $this->fpos + $this->pos;
	}

}
?>