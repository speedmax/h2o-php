<?php
/**
 * 
 * @author taylor.luk
 * @todo FileLoader need more test coverage
 */
class H2o_Loader {
    public $parser;
    public $runtime;
    public $cached = false;
    private $cache = false;
    public $searchpath = false;
    
    function read($filename) {}
    function cache_read($file, $object, $ttl = 3600) {}
}

class H2o_File_Loader extends H2o_Loader {

    function __construct($searchpath, $options = array()) {
        if (is_file($searchpath)) {
            $searthpath = dirname($searchpath).DS;
        }
        if (!is_dir($searchpath))
            throw new TemplateNotFound($filename);

        $this->searchpath = realpath($searchpath) . DS;
        $this->setOptions($options);
    }

    function setOptions($options = array()) {
        if (isset($options['cache']) && $options['cache']) {
            $this->cache = h2o_cache($options);
        }
    }
    
    function read($filename) {
        if (!is_file($filename))
            $filename = $this->searchpath . $filename;

        if (is_file($filename)) {
            $source = file_get_contents($filename);
            return $this->runtime->parse($source);
        } else {
            throw new TemplateNotFound($filename);
        }
    }

    function read_cache($filename) {
        if (!$this->cache)
             return $this->read($filename);

        if (!is_file($filename))
            $filename = $this->searchpath . $filename;

        $filename = realpath($filename);
        $cache = md5($filename);
        $object = $this->cache->read($cache);
        $this->cached = $object && !$this->expired($object);
        
        if (!$this->cached) {
            $nodelist = $this->read($filename);
            $object = (object) array(
                'filename' => $filename,
                'content' => serialize($nodelist),
                'created' => time(),
                'templates' => $nodelist->parser->storage['templates'],
                'included' => $nodelist->parser->storage['included'] + array_values(h2o::$extensions)
            );
            $this->cache->write($cache, $object);
        } else {
            foreach($object->included as $ext => $file) {
                include_once (h2o::$extensions[$ext] = $file);
            }
        }
        return unserialize($object->content);
    }

    function flush_cache() {
        $this->cache->flush();
    }

    function expired($object) {
        if (!$object) return false;
        
        $files = array_merge(array($object->filename), $object->templates);
        foreach ($files as $file) {
            if (!is_file($file))
                $file = $this->searchpath.$file;
            
            if ($object->created < filemtime($file))
                return true;
        }
        return false;
    }
}

function file_loader($file) {
    return new H2o_File_Loader($file);
}

class H2o_Hash_Loader {

    function __construct($scope, $options = array()) {
        $this->scope = $scope;
    }
    
    function setOptions() {}

    function read($file) {
        if (!isset($this->scope[$file]))
            throw new TemplateNotFound;
        return $this->runtime->parse($this->scope[$file], $file);
    }
    
    function read_cache($file) {
        return $this->read($file);
    }
}

function hash_loader($hash = array()) {
    return new H2o_Hash_Loader($hash);
}

/**
 * Cache subsystem
 *
 */
function h2o_cache($options = array()) {
    $type = $options['cache'];
    $className = "H2o_".ucwords($type)."_Cache";

    if (class_exists($className)) {
        return new $className($options);
    }
    return false;
}

class H2o_File_Cache {
    var $ttl = 3600;
    var $prefix = 'h2o_';
    
    function __construct($options = array()) {
        if (isset($options['cache_dir']) && is_writable($options['cache_dir'])) {
            $path = $options['cache_dir'];
        } else {
            $path = dirname($tmp = tempnam(uniqid(rand(), true), ''));

            if (file_exists($tmp)) unlink($tmp);
        }
        if (isset($options['cache_ttl'])) {
            $this->ttl = $options['cache_ttl'];
        }
        if(isset($options['cache_prefix'])) {
            $this->prefix = $options['cache_prefix'];
        }
        
        $this->path = realpath($path). DS;
    }

    function read($filename) {
        if (!file_exists($this->path . $this->prefix. $filename))
            return false;

        $content = file_get_contents($this->path . $this->prefix. $filename);
        $expires = (int)substr($content, 0, 10);

        if (time() >= $expires) 
            return false;
        return unserialize(trim(substr($content, 10)));
    }

    function write($filename, &$object) {
        $expires = time() + $this->ttl;
        $content = $expires . serialize($object);
        return file_put_contents($this->path . $this->prefix. $filename, $content);   
    }
    
    function flush() {
        foreach (glob($this->path. $this->prefix. '*') as $file) {
            @unlink($file);
        }
    }
}

class H2o_Apc_Cache {
    var $ttl = 3600;
    var $prefix = 'h2o_';
    
    function __construct($options = array()) {
        if (!function_exists('apc_add'))
            throw new Exception('APC extension needs to be loaded to use APC cache');
            
        if (isset($options['cache_ttl'])) {
            $this->ttl = $options['cache_ttl'];
        } 
        if(isset($options['cache_prefix'])) {
            $this->prefix = $options['cache_prefix'];
        }
    }
    
    function read($filename) {
        return apc_fetch($this->prefix.$filename);
    }

    function write($filename, $object) {
        return apc_store($this->prefix.$filename, $object, $this->ttl);   
    }
    
    function flush() {
        return apc_clear_cache('user');
    }
}

class H2o_Memcache_Cache {
    function __construct($scope, $options = array()) {
    }
    
    function read($filename) {
    }
    
    function write($filename, $content) {
    }
    
    function flush() {}
}

?>