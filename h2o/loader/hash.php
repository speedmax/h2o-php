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
 * Hash loader
 */
class h2o_Loader_Hash extends h2o_Loader {
    private $_templates;

    public function __construct(array $templates) {
        if (isset($templates['searchpath'])) {
            throw new RuntimeException('You must provide a template hash for the Hash loader');
        }

        $this->_templates = $templates;
    }

    public function load($template) {
        if (!isset($this->_templates[$template])) {
            throw new RuntimeException(sprintf('Template %s not found', $template));
        }

        return $this->_templates[$template];
    }

    public function mtime($template) {
        return time();
    }
}
