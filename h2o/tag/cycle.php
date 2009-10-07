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
 * Cycle tag
 * 
 * @extends h2o_Tag
 */
class h2o_Tag_Cycle extends h2o_Tag {
    private $_args;
    private $_uid;
    static private $_counter = 0;

    public function __construct($arguments, h2o_Parser $parser) {
        $this->_args = h2o_Argument::parse($arguments);

        if (count($this->_args) < 2) {
            throw new RuntimeException('Cycle tag requires two or more items');
        }

        $this->_uid = '__cycle__'.(self::$_counter++);
    }

    public function render(h2o_Context $context) {
        $item = $context->lookup($this->_uid);

        if (!is_null($item)) {
            $item = ($item + 1) % count($this->_args);
        } else {
            $item = 0;
        }

        $context[$this->_uid] = $item;
        return $context->resolve($this->_args[$item]);
    }
}
