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
 * Autoescape tag
 * 
 * @extends h2o_Tag
 */
class h2o_Tag_Autoescape extends h2o_Tag {
    private $_enable;

    public function __construct($arguments, h2o_Parser $parser) {
        if ($arguments == 'on') {
            $this->_enable = true;
        } else if ($arguments == 'off') {
            $this->_enable = false;
        } else {
            throw new RuntimeException('Invalid syntax: autoescape on|off');
        }
    }

    public function render(h2o_Context $context) {
        $context->autoescape = $this->_enable;
    }
}
