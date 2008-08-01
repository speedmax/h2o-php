<?php
class H2o_Parser {
	var $source;
	var $options;
	var $searching;
	var $token;
	var $storage;
	var $first;
	var $filename;
	function __construct($source, &$env, $filename = "?"){
		$this->options =& $env->options;
		$this->source = $source;
		$this->env =& $env;
		$this->first = true;
		$this->storage = array();
		$this->filename = $filename;
		$trim='';
		
		if ($env->options['TRIM_TAGS'])
			$trim = '(?:\r?\n)?';
			
		$this->tag_regex = '/(.*?)(?:' .
		preg_quote($this->options['BLOCK_START']). '(.*?)' .preg_quote($this->options['BLOCK_END']) . $trim . '|' .
		preg_quote($this->options['VARIABLE_START']). '(.*?)' .preg_quote($this->options['VARIABLE_END']) . '|' .
		preg_quote($this->options['COMMENT_START']). '(.*?)' .preg_quote($this->options['COMMENT_END']) . $trim . ')/sm';
		$this->tokenstream = $this->tokenize();
	}

	function tokenize() {
		$result = new TokenStream;
		$pos = 0;
		$matches = array();
		preg_match_all($this->tag_regex, $this->source, $matches, PREG_SET_ORDER);

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
		while( $this->tokenstream->next()) { 
			$token = $this->tokenstream->current();
			
			switch($token->type) {
				case 'text' :
					$node = new TextNode($token->content);
					break;
				case 'variable' :
					$objects = $filters = array();
					$args = H2o_Utils::parseArguments($token->content, $token->position);

					// Parse out filters and variables
					foreach ($args as $data){
						if (is_array($data)) {
							array_push($filters, $data);
						} else {
							array_push($objects, $data);
						}
					}
					$node = new VariableNode($objects, $filters, $token->position);
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
					$node = $this->env->createTag($name, $args, $this, $token->position);
					$this->token = $token;
					
			}
			$this->searching = join(',',$until);
			$this->first = false;
			$nodelist->append($node);
		}
		
		if ($until) {
			throw new H2o_TemplateSyntaxError('Unclose tag, expecting '. $until[0], 
											$this->filename, 
											$this->token->position);
		}
		return $nodelist;
	}

	function pop_token(){
		$token = clone($this->token);
		if ($this->token) {
			unset($this->token);
			return $token;
		}
		return false;
	}

	function skipTo($until) {
		$this->parse($until);
		return null;
	}
}


class H2o_ArgumentParser {
	/**
	 * Argument source
	 */
	var $source;
	var $match;
	var $pos =0;
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
		'OPERATOR_RE' => '/\s?+(>|<|=|>=|<=|!=|==|=|and|not|or)\s?+/i',
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
			if( !$filtering ){
				if ($this->scan($options['OPERATOR_RE'])){
					$operator = $this->match;
					if(isset($options['operator_replace'][trim($operator)]))
					$operator = $options['operator_replace'][trim($operator)];
					array_push($result, array('operator', $operator));
				}
				elseif ($this->scan($options['NAMED_ARGS_RE']))
				array_push($result, array('named_argument', $this->match));						
				elseif ($this->scan($options['NAME_RE']))
				array_push($result, array('name', $this->match));
				elseif ($this->scan($options['PIPE_RE'])) {
					$filtering = true;
					array_push($result, array('filter_start', $this->match));
				}
				elseif ($this->scan($options['SEPARATOR_RE']))
				array_push($result, array('separator', null));
				elseif ($this->scan($options['STRING_RE']))
				array_push($result, array('string', $this->match));
				elseif ($this->scan($options['NUMBER_RE']))
				array_push($result, array('number', $this->match));
				else
				//TODO: global error handing
				die ('unexpected character in filters : "'. $this->source[$this->pos]. '" at '.$this->getPosition());
			}
			else {
				// parse filters, with chaining and ";" as filter end character
				if ($this->scan($options['PIPE_RE'])) {
					array_push($result, array('filter_end', null));
					array_push($result, array('filter_start', null));
				}
				elseif ($this->scan($options['SEPARATOR_RE']))
				array_push($result, array('separator', null));
				elseif ($this->scan($options['FILTER_END_RE'])) {
					array_push($result, array('filter_end', null));
					$filtering= false;
				}
				elseif ($this->scan($options['NAMED_ARGS_RE']))
				array_push($result, array('named_argument', $this->match));					
				elseif ($this->scan($options['NAME_RE']))
				array_push($result, array('name', $this->match));
				elseif ($this->scan($options['STRING_RE']))
				array_push($result, array('string', $this->match));
				elseif ($this->scan($options['NUMBER_RE']))
				array_push($result, array('number', $this->match));			
				else
				//TODO: global error handing
				die ('unexpected character in filters : "'. $this->source[$this->pos]. '" at '.$this->getPosition());
			}
		}
		// if we are still in the filter state, we add a filter_end token.
		if ($filtering)
		array_push($result, array('filter_end', null));
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
	
	
	static function type($source){
		$option = self::getOptions();
		if(preg_match($option['STRING_RE'], $source))
			return 'string';
		elseif(preg_match($option['NAME_RE'], $source))
			return 'name';
		elseif(preg_match($option['NUMBER_RE'], $source))
			return 'number';
		else return false;
	}	
}
?>