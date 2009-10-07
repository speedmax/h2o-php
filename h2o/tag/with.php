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
class h2o_Tag_With extends h2o_Tag {
    private $_variable = '';
    private $_shortcut = '';
    private $_nodes;
    private $_pattern  = '/^([\w]+(:?\.[\w]+)?)\s+as\s+([\w]+(:?\.[\w]+)?)$/';

    public function __construct($arguments, h2o_Parser $parser) {
        if (!preg_match($this->_pattern, $arguments, $matches)) {
            throw new RuntimeException('Invalid with tag syntax');
        }

        list($this->_variable, $this->_shortcut) = $matches;
        $this->_nodes = $parser->parse('endwith');
    }

    public function render(h2_Context $context) {
        $variable = $context->lookup($this->_variable);

        $context->push(array($this->_shortcut => $variable));
        $output = $this->_nodes->render($context);
        $context->pop();

        return $output;
    }
}
