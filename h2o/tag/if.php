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
 * If tag
 * 
 * @extends h2o_Tag
 */
class h2o_Tag_If extends h2o_Tag {
    private $_body;
    private $_else;
    private $_negate = false;

    public function __construct($arguments, h2o_Parser $parser) {
        if (preg_match('/\s(and|or)\s/', $arguments)) {
            throw new RuntimeException('H2o doesn\'t support multiple expressions');
        }

        $this->_body = $parser->parse('endif', 'else');

        if ($parser->Token['content'] == 'else') {
            $this->_else = $parser->parse('endif');
        }

        $this->_args = h2o_Argument::parse($arguments);

        $first = current($this->_args);
        if (isset($first['operator']) && $first['operator'] == 'not') {
            array_shift($this->_args);
            $this->_negate = true;
        }
    }

    public function render(h2o_Context $context) {
        $result = h2o_Evaluator::exec($this->_args, $context);

        if ($this->_negate) {
            $result = !$result;
        }

        if ($result) {
            return $this->_body->render($context);
        } else if (isset($this->_else)) {
            return $this->_else->render($context);
        }
    }
}
