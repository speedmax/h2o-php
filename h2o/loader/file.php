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
 * File loader
 */
class h2o_Loader_File extends h2o_Loader {
    private $_path;

    public function __construct(array $options) {
        $this->_path = $options['searchpath'];
    }

    public function load($template) {
        $path = $this->_path.$template;

        if (!is_file($path)) {
            throw new RuntimeException(sprintf('Template %s not found', $path));
        }

        return file_get_contents($path);
    }

    public function mtime($template) {
        $path = $this->_path.$template;

        if (!is_file($path)) {
            return 0;
        }

        return filemtime($path);
    }
}
