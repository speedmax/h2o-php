<?php

class h2o_Cache_File extends h2o_Cache {
    private $_path;

    private $_file;

    private $_save = false;

    private $_data = array();

    public function __construct(array $options) {
        if (!isset($options['cachepath'])) {
            $path = '/tmp';
        } else {
            $path = $options['cachepath'];
        }

        if (!is_dir($path) || !is_writable($path)) {
            trigger_error(sprintf('Cannot write to cache path `%s`', $path), E_USER_WARNING);
        }

        $this->_path = $path.'/';
    }

    public function __destruct() {
        if (!$this->_save) {
            return;
        }

        $this->save();
    }

    public function get($key) {
        if (isset($this->_data[$key])) {
            return $this->_data[$key];
        }

        return null;
    }

    public function load() {
        if (!is_file($this->_path.$this->_file)) {
            return false;
        }

        $data = file_get_contents($this->_path.$this->_file);

        if (($data = @unserialize($data)) !== false) {
            $this->_data = $data;
            return true;
        }

        if (isset($this->_data['classes'])) {
            foreach ($this->_data['classes'] as $class) {
                h2o::autoload($class);
            }
        }

        return false;
    }

    public function mtime() {
        if (!is_file($this->_path.$this->_file)) {
            return 0;
        }

        return filemtime($this->_path.$this->_file);
    }

    public function save() {
        if ($this->_save && is_writable($this->_path)) {
            file_put_contents($this->_path.$this->_file, serialize($this->_data));
        }
    }

    public function set($key, $value) {
        if (!strpos($key, '[')) {
            $this->_data[$key] = $value;
        }

        $key = substr($key, 0, strpos($key, '['));

        if (!isset($this->_data[$key])) {
            $this->_data[$key] = array();
        }

        $this->_data[$key][] = $value;
        $this->_data[$key] = array_unique($this->_data[$key]);
    }

    public function setFile($file) {
        if (!$this->_save) {
            $this->_save = true;
            $this->_file = 'h2o_'.md5($file);
            $this->load();
        }
    }
}
