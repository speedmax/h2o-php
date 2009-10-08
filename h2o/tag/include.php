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
 * Include tag
 * 
 * @extends h2o_Tag
 */
class h2o_Tag_Include extends h2o_Tag {
    private $_nodes;

    public function __construct($arguments, h2o_Parser $parser) {
        if (!preg_match('/^\s*(\'|")(?P<file>.*)(\1)\s*/', $arguments, $matches)) {
            throw new RuntimeException('Filename must be encompassed in quotes: "'.$arguments.'"');
        }

        $this->_nodes = $parser->Runtime->parseFile($matches['file']);

        $parser->storage['templates'] = array_merge(
            $this->_nodes->Parser->storage['templates'], $parser->storage['templates']
        );

        $parser->storage['templates'][] = $matches['file'];
    }

    public function render(h2o_Context $context) {
        return $this->_nodes->render($context);
    }
}
