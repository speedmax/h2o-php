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
 * Now tag
 * 
 * @extends h2o_Tag
 */
class h2o_Tag_Now extends h2o_Tag {
    private $_format;

    public function __construct($arguments, h2o_Parser $parser) {
        $this->_format = empty($arguments) ? 'D M j G:i:s T Y' : $arguments;
    }

    public function render(h2o_Context $context) {
        return date($this->_format);
    }
}
