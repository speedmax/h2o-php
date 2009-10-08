<?php

class h2o_TokenStack {
    private $_closed = false;
    private $_pushed = array();
    private $_stream = array();

    public function isClosed() {
        return $this->_closed;
    }

    public function feed($type, $content, $position) {
        if ($this->_closed) {
            throw new RuntimException('You may not feed a closed token stream');
        }

        $this->push(compact('type', 'content', 'position'));
    }

    public function pop() {
        if (count($this->_pushed) > 0) {
            return array_pop($this->_pushed);
        }

        return array_pop($this->_stream);
    }

    public function push($item) {
        if (is_null($item)) {
            throw new RuntimeException('NULL is not allowed in a stream');
        }

        if ($this->_closed) {
            array_push($this->_pushed, $item);
        } else {
            array_push($this->_stream, $item);
        }
    }

    public function close() {
        if ($this->_closed) {
            throw new RuntimeException('You may not close an already closed token stream');
        }

        $this->_closed = true;
        $this->_stream = array_reverse($this->_stream);
    }

    function next() {
        return $this->pop();
    }
}

class h2o_NodeStack implements IteratorAggregate {
    private $_nodes = array();

    private $_parser;

    public $parent;

    public function __construct(h2o_Parser $parser = null) {
        $this->_parser = $parser;
    }

    public function __get($key) {
        switch($key) {
            case 'Parser':
                return $this->_parser;
        }

        return null;
    }

    public function append(h2o_Node $node) {
        array_push($this->_nodes, $node);
    }

    public function getIterator() {
        return new ArrayIterator($this->_nodes);
    }

    public function render(h2o_Context $context) {
        $stream = '';

        foreach ($this as $node) {
            $stream .= $node->render($context);
        }

        return $stream;
    }
}
