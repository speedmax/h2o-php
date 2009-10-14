<?php

class h2o_Cache_File extends h2o_Cache {
    public function get($key) {
        return null;
    }

    public function load() {
        return false;
    }

    public function mtime() {
        return 0;
    }

    public function save() {
    }

    public function set($key, $value) {
    }
}
