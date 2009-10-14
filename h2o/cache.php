<?php

abstract class h2o_Cache {
    abstract public function get($key);
    abstract public function load();
    abstract public function mtime();
    abstract public function save();
    abstract public function set($key, $value);
}
