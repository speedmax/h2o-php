<?php

class H2O_StreamWriter {
    var $buffer = array();
    var $close;

    function __construct() {
        $this->close = false;
    }
    
    function write($data) {
        if ($this->close)
            new Exception('tried to write to closed stream');
        array_push($this->buffer, $data);
    }
    
    function writeSource($data) {
    	$source = sprintf(constant('PHP_TAG'),$data);
    	$this->write($source);
    }
    
    function close() {
        $this->close = true;
        return implode('', $this->buffer);
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
            new Exception('cannot feed closed stream');
        array_push($this->stream, new H2o_Token($type, $contents, $position));
    }

    function push($token) {
        if (is_null($token))
            new Exception('cannot push NULL');
        if ($this->closed)
            array_push($this->pushed, $token);
        else
            array_push($this->stream, $token);
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

?>