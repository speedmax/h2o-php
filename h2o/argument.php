<?php

class h2o_Argument {
    static public function parse($source) {
        $lexer  = new h2o_ArgumentLexer($source);
        $tokens = $lexer->parse();
        $result = array();

        $b_current = &$result;
        $b_filter  = array();

        foreach ($tokens as $token) {
            list($token, $data) = $token;

            if ($token == 'filter_start') {
                $b_filter  = array();
                $b_current = &$b_filter;
            } else if ($token == 'filter_end') {
                if (count($b_filter)) {
                    array_push($result, $b_filter);
                }

                $b_current = &$result;
            } else if ($token == 'boolean') {
                array_push($b_current, ($data === 'true' ? true : false));
            } else if ($token == 'name') {
                array_push($b_current, array(symbol($data)));
            } else if ($token == 'number' || $token == 'string') { 
                array_push($b_current, $data);
            } else if ($token == 'named_argument') {
                $last = $current_buffer[count($b_current) - 1];
                if (!is_array($last)) {
                    array_push($b_current, array());
                }

                $namedArgs =& $b_current[count($b_current) - 1]; 
                list($name,$value) = array_map('trim', explode(':', $data, 2));

                $value = self::parseArguments($value);
                $namedArgs[$name] = $value[0];
            } else if ($token == 'operator') {
                array_push($b_current, array('operator' => $data));
            }
        }
        return $result;

        print_r($tokens);
        exit;
    }
}

class h2o_ArgumentLexer {
    private $_source;

    private $_match;

    private $_source_pos = 0;

    private $_filter_pos = 0;

    private $operator_map = array(
        '!' => 'not', '!='=> 'ne', '==' => 'eq', '>' => 'gt', '<' => 'lt', '<=' => 'le', '>=' => 'ge'
    );

    public function __construct($source, $filter_pos = 0) {
        if (!is_null($source)) {
            $this->_source = $source;
        }

        $this->_filter_pos = $filter_pos;
    }

    public function getPatterns() {
        static $patterns;

        if (is_null($patterns)) {
            $r = 'strip_regex';

            $whitespace  = '/\s+/m';
            $parentheses = '/\(|\)/m';
            $filter_end  = '/;/';
            $boolean     = '/true|false/';
            $separator   = '/,/';
            $pipe        = '/\|/';
            $operator    = '/\s?(>|<|>=|<=|!=|==|!|and |not |or )\s?/i';
            $number      = '/\d+(\.\d*)?/';
            $name        = '/[a-zA-Z][a-zA-Z0-9-_]*(?:\.[a-zA-Z_0-9][a-zA-Z0-9_-]*)*/';

            $string      = '/(?:
                    "([^"\\\\]*(?:\\\\.[^"\\\\]*)*)" |   # Double Quote string   
                    \'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\' # Single Quote String
            )/xsm';
            $string      = "/_\({$r($string)}\) | {$r($string)}/xsm";
    
            $named_arg   = "{
                ({$r($name)})(?:{$r($whitespace)})?
                : 
                (?:{$r($whitespace)})?({$r($string)}|{$r($number)}|{$r($name)})
            }x";

            $patterns = compact(
                'whitespace', 'filter_end', 'boolean', 'separator',
                'pipe', 'operator', 'number', 'name', 'named_arg', 'string'
            );
        }

        return $patterns;
    }

    public function parse() {
        $result = array();
        $filter = false;
        $length = strlen($this->_source);
        $regex  = $this->getPatterns();

        $pat_start  = array('operator', 'named_arg', 'name', 'pipe', 'separator', 'string', 'number');
        $pat_filter = array('pipe', 'separator', 'filter_end', 'boolean', 'named_arg', 'name', 'string', 'number');

        while ($this->_source_pos < $length) {
            $this->_scan($regex['whitespace']);
            $pats = $filter ? $pat_filter : $pat_start;

            foreach ($pats as $pat) {
                if (!$this->_scan($regex[$pat])) {
                    continue;
                }

                if (!$filter && $pat == 'operator') {
                    $operator = trim($this->_match);

                    if (isset($this->operator_map[$operator])) {
                        $operator = $this->operator_map[$operator];
                    }

                    array_push($result, array('operator', $operator));
                    continue 2;
                } else if (!$filter && $pat == 'pipe') {
                    $filter = true;
                } else if ($filter && $pat == 'pipe') {
                    array_push($result, array('filter_end', null));
                    array_push($result, array('filter_start', null));
                    continue 2;
                } else if ($filter && $pat == 'filter_end') {
                    $filter = false;
                }

                array_push($result, array($pat, $this->_match));
                continue 2;
            }

            throw new RuntimeException(sprintf('Unexpected character in argument: "%s" at %d',
                $this->_source[$this->_source_pos], $this->getPosition()));
        }

        if ($filter) {
            array_push($result, array('filter_end', null));
        }

        return $result;
    }

    private function _scan($pattern) {
        if (preg_match($pattern.'A', $this->_source, $match, null, $this->_source_pos)) {
            $this->_match = $match[0];
            $this->_source_pos += strlen($this->_match);

            return true;
        }

        return false;
    }

    function getPosition() {
        return $this->_filter_pos + $this->_source_pos;
    }
}

/**
 * Removes the delimiters from a regex string
 *
 * @access public
 * @param string $regex
 * @param string $delimiter [/]
 * @return string
 */
function strip_regex($regex, $delimiter = '/') {
    return substr($regex, 1, strrpos($regex, $delimiter) - 1);
}
