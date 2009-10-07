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
 * Base tag object
 *
 * @extends h2o_Node
 * @abstract
 */
abstract class h2o_Tag extends h2o_Node {
    static private $_tags = array();

    static public function exists($tag) {
        $tag = strtolower($tag);

        return isset(self::$_tags[$tag]);
    }

    static public function add($tag, $class = null) {
        if (is_array($tag)) {
            foreach ($tag as $t) {
                self::add($t);
            }

            return;
        }

        $tag = strtolower($tag);

        if (is_null($class)) {
            $class = 'h2o_Tag_'.ucwords($tag);
        }

        self::$_tags[$tag] = $class;
    }

    static public function remove($tag) {
        if (is_array($tag)) {
            foreach ($tag as $t) {
                self::remove($t);
            }

            return;
        }

        $tag = strtolower($tag);
        unset(self::$_tags[$tag]);
    }

    static public function load($tag) {
        $tag = strtolower($tag);

        return new self::$_tags[$tag];
    }
}
