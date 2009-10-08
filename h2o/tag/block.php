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
 * Block tag
 * 
 * @extends h2o_Tag
 */
class h2o_Tag_Block extends h2o_Tag {
    private $_name;
    private $_stack;

    public function __construct($arguments, h2o_Parser $parser) {
        if (!preg_match('/^\s*(\'|")?(?P<name>.*)(\1)?\s*/', $arguments, $matches)) {
            throw new RuntimeException('Blocks require a name');
        }

        $this->_name = $name = $matches['name'];

        if (isset($parser->storage['blocks'][$name])) {
            throw new RuntimeException(sprintf('Block %s already exists in this template', $name));
        }

        $this->_stack = array($parser->parse('endblock', 'endblock '.$this->_name));

        $parser->storage['blocks'][$name] = $this;
    }

    public function __get($key) {
        switch ($key) {
            case 'Name':
                return $this->_name;
        }

        return null;
    }

    public function addLayer(&$nodes) {
        $nodes->parent = $this;

        array_push($this->_stack, $nodes);
    }

    public function render(h2o_Context $context, $index = 1) {
        $key    = count($this->_stack) - $index;
        $output = '';

        if (isset($this->_stack[$key])) {
            $context->push();
            $context['block'] = new h2o_Context_Block($this, $context, $index);
            $output .= $this->_stack[$key]->render($context);
            $context->pop();
        }

        return $output;
    }
}

class h2o_Context_Block {
    private $_block;
    private $_context;
    private $_index;

    function __construct(h2o_Tag_Block $block, h2o_Context $context, $index) {
        $this->_block   =& $block;
        $this->_context = $context;
        $this->_index   = $index;
    }

    function name() {
        return $this->_block->Name;
    }

    function depth() {
        return $this->_index;
    }

    function super() {
        return $this->_block->parent->render($this->_context, $this->_index + 1);
    }
}
