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
 * Basic node
 *
 * All node types (tag, variable) inherit this base class. The most basic usage
 * of this class is to leave the base render() and __construct() active, which
 * will render the node as raw. This is how text nodes work (so there is no
 * h2o_Node_Text class).
 */
class h2o_Node {
    /**
     * Raw contents of the node
     * 
     * @var string
     * @access private
     */
    private $_raw;

    public function __construct($contents) {
        $this->_raw = $contents;
    }

    /**
     * Render the node and return it's contents
     *
     * @access public
     * @param h2o_Context $context
     * @return void
     */
    public function render(h2o_Context $context) {
        return $this->_raw;
    }
}
