<?php
class StreamWriter {
    var $buffer = array();
    var $close;

    function __construct() {
        $this->close = false;
    }

    function write($data) {
        if ($this->close)
            new Exception('tried to write to closed stream');
        $this->buffer[] = $data;
    }

    function close() {
        $this->close = true;
        return implode('', $this->buffer);
    }
}

class Evaluator {
    function gt($l, $r) { return $l > $r; }
    function ge($l, $r) { return $l >= $r; }

    function lt($l, $r) { return $l < $r; }
    function le($l, $r) { return $l <= $r; }

    function eq($l, $r) { return $l == $r; }
    function ne($l, $r) { return $l != $r; }

    function not_($bool) { return !$bool; }
    function and_($l, $r) { return ($l && $r); }
    function or_($l, $r) { return ($l && $r); }

    # Currently only support single expression with no preceddence ,no boolean expression
    #    [expression] =  [optional binary] ? operant [ optional compare operant]
    #    [operant] = variable|string|numeric|boolean
    #    [compare] = > | < | == | >= | <=
    #    [binary]    = not | !
    function exec($args, $context) {
        $argc = count($args);
        $first = array_shift($args);
        $first = $context->resolve($first);
        switch ($argc) {
            case 1 :
                return $first;
            case 2 :
                if (is_array($first) && isset($first['operator']) && $first['operator'] == 'not') {
                    $operant = array_shift($args);
                    $operant = $context->resolve($operant);
                    return !($operant);
                }
            case 3 :
                list($op, $right) = $args;
                $right = $context->resolve($right);
                return call_user_func(array("Evaluator", $op['operator']), $first, $right);
            default:
                return false;
        }
    }
}

/**
 * $type of token, Block | Variable
 */
class H2o_Token {
    function __construct ($type, $content, $position) {
        $this->type = $type;
        $this->content = $content;
        $this->result='';
        $this->position = $position;
    }

    function write($content){
        $this->result= $content;
    }
}

/**
 * a token stream
 */
class TokenStream  {
    var $pushed;
    var $stream;
    var $closed;
    var $c;

    function __construct() {
        $this->pushed = array();
        $this->stream = array();
        $this->closed = false;
    }

    function pop() {
        if (count($this->pushed))
        return array_pop($this->pushed);
        return array_pop($this->stream);
    }

    function feed($type, $contents, $position) {
        if ($this->closed)
            throw new Exception('cannot feed closed stream');
        $this->stream[] = new H2o_Token($type, $contents, $position);
    }

    function push($token) {
        if (is_null($token))
            throw new Exception('cannot push NULL');
        if ($this->closed)
            $this->pushed[] = $token;
        else
            $this->stream[] = $token;
    }

    function close() {
        if ($this->closed)
        new Exception('cannot close already closed stream');
        $this->closed = true;
        $this->stream = array_reverse($this->stream);
    }

    function isClosed() {
        return $this->closed;
    }

    function current() {
        return $this->c ;
    }

    function next() {
        return $this->c = $this->pop();
    }
}

class H2o_Info {
    var $h2o_safe = array('filters', 'extensions', 'tags');
    var $name = 'H2o Template engine';
    var $description = "Django inspired template system";
    var $version = H2O_VERSION;

    function filters() {
        return array_keys(h2o::$filters);
    }
    
    function tags() {
        return array_keys(h2o::$tags);
    }
    
    function extensions() {
        return array_keys(h2o::$extensions);
    }
}

/**
 * Functions
 */
function sym_to_str($string) {
    return substr($string, 1);
}

function is_sym($string) {
    return isset($string[0]) && $string[0] === ':';
}

function symbol($string) {
    return ':'.$string;
}

function strip_regex($regex, $delimiter = '/') {
    return substr($regex, 1, strrpos($regex, $delimiter)-1);
}
?>