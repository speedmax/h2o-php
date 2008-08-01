<?php
class H2o_File {
    var $filename;
    var $compiled_file;
    var $source;
    var $env;
    var $context;
    function __construct($filename, &$env){
        $this->env =& $env;
        $this->filename = $filename;
        $this->compiled_file = $env->compiled_file = $this->_compiledName();
        $this->context =& $env->context;
    }
    
    function read($filename) {
        if (file_exists($filename)){
        } elseif ($this->env->options['template_path'].DS.$filename) {
            $filename = $this->env->options['template_path'].DS.$filename;
        } else {
            throw new H2o_TemplateNotFound('Template is not found '.$filename);
        }
        return $this->source = file_get_contents($filename);
    }
    
    function tryLoad(){
        if ($this->env->options['output_cache'])
            if ($this->tryCached()){    
                return 'cache';
            }
            
        if ($this->env->options['cache'])
            if ($this->tryCompiled()){
                return true;
            }
        return false;
    }
    
    function save($output){
        $output = $this->saveCompiled($output);
        return $output;
    }
    
    /*  output caching  */
    function tryCached(){
        $options =& $this->env->options;
        // Tempaltes changes need update the compiled php and output cache
        if ($options['force_check']) {
            if (!$this->_checkCompiled())
            return false;
        }
        
        $cache_file = $this->_cacheName();
        
        /* If auto expiry is set */
        if (!file_exists($cache_file))
            return false;
            
        if ($options['cache_expires']){
            if (filemtime($cache_file) + $options['cache_expires'] < CURRENT_TIME )
                return false;
        }
        return true;
    }   
    
    function loadCache(){
        return file_get_contents($this->_cacheName());
    }
    
    function saveCache($output){
        return file_put_contents($this->_cacheName(), $output);
    }
    
    /*  Cache PHP compiled code */
    function tryCompiled(){
        //  Load cached copy of compiled template
        $this->readEmbeded($this->filename);
        if ($compiled = $this->_checkCompiled()) {
            $this->read($this->filename);   
            return true;
        }
        return false;
    }
    
    function readEmbeded(){
        $compiledName = $this->_compiledName();
        if (!file_exists($compiledName)) {
            return false;
        }
        
        $fp = fopen($compiledName,'r');
        $mete_regex = "/\<!--h2o\|(.*)?\|h2o--\>/";
        $content='';
        while(!feof($fp)){
            $content .= fread($fp, 32);
             if (preg_match($mete_regex,$content,$match))
                break;
        }
        fclose($fp);
        if (isset($match) && isset($match[1])) {
            $this->env->storage = unserialize(stripslashes($match[1]));
        }       
        return true;
    }
    
    function loadCompiled(){
        extract($this->env->options['namespace'], EXTR_REFS);
        if (file_exists($this->compiled_file)) {
            ob_start();
                include $this->compiled_file;
            return ob_get_clean();
        }
        return true;
    }
    
    function saveCompiled($output){
        file_put_contents($this->env->compiled_file, $output);
    }

    function _cacheName() {
        return $this->env->options['compile_path']. DS .
                bin2hex( md5( $this->filename, TRUE ) ). '.html';
    }
    
    function _compiledName(){
        return $this->env->options['compile_path']. DS .
                bin2hex( md5( $this->filename, TRUE ) ) . $this->env->options['compiled_extension'];
    }
/*
    if cache exists and require no update
*/
    function _checkCompiled(){
        $options =& $this->env->options;
        $filemtime = filemtime($this->filename);
        $storage =& $this->env->storage;
        
        /* not Compiled or don't cache ? */     
        if (!(file_exists($this->compiled_file)&& $options['cache'])){
            return false;
        }
        $compile_mtime = filemtime($this->compiled_file);
        
        /* Check if Parent or child file is modified */
        if ($options['force_check']) {
            $chain_update = false;
            $previous = $this->filename;
            if (isset($storage['includes'])) {
                foreach ($storage['includes'] as $file=>$number) {
                    $previous_time = filemtime($previous);
                    if (filemtime($file) > $previous_time){
                        if (!$chain_update) $chain_update = true;
                        touch($file, $previous_time);
                    }
                    $previous = $file;          
                }
                if ($chain_update)
                    return false;
            }
        }
        
        /* If auto expiry is set */
        if ($options['expires']){
            if ($compile_mtime + $options['expires'] < CURRENT_TIME )
                return false;
        }
        
        /* If cache is outdated */
        if ($filemtime > $compile_mtime) {
            return false;
        }
        return true;
    }
}


class H2o_Block {
    var $_name;
    var $_depth;
    var $path;
    var $h2o_safe = array('name','depth','super');
    
    function __construct($name, $index, $path) {
        $this->_name = $name;
        $this->_depth = $index +1;
        $this->path = $path;
    }
    
    function name(){
        return $this->_name;
    }
    
    function depth(){
        return $this->_depth;
    }
    
    function super(){
        global $H2O_RUNTIME;
        if (!file_exists($this->path))
            return null;
        
        extract($H2O_RUNTIME->options['namespace'], EXTR_REFS);
        ob_start();
        include($this->path);
        return ob_get_clean();
    }
}
?>