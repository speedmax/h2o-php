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
 * Extend tag
 * 
 * @extends h2o_Tag
 */
class h2o_Tag_Extends extends h2o_Tag {
    private $_nodes;

    public function __construct($arguments, h2o_Parser $parser) {
        if (!$parser->First) {
            throw new RuntimeException('Extend must be the first line in a template');
        }

        if (!preg_match('/^\s*(\'|")(?P<file>.*)(\1)\s*/', $arguments, $matches)) {
            throw new RuntimeException('Filename must be encompassed in quotes: "'.$arguments.'"');
        }

        $parser->parse(); // Finish parsing the current template

        $this->_nodes = $parser->Runtime->parseToNodes($matches['file']);

        if (!isset($this->_nodes->Parser->storage['blocks']) || !isset($parser->storage['blocks'])) {
            return;
        }

        $blocks =& $this->_nodes->Parser->storage['blocks'];

        foreach($parser->storage['blocks'] as $name => &$block) {
            if (isset($blocks[$name])) {
                $blocks[$name]->addLayer($block);
            }
        }
    }

    public function render(h2o_Context $context) {
        return $this->_nodes->render($context);
    }
}
