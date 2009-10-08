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
 * Base loader
 */
abstract class h2o_Loader {
    abstract public function load($template);

    public function __get($key) {
        switch ($key) {
            case 'cached':
                return false;
        }

        return null;
    }

    public function flush_cache() {}
}
