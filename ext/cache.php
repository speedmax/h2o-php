<?php

class Cache_Tag extends H2o_Node {
    private $cache, $ttl, $uid;
    var $syntax = '/\d+/';
    
    function __construct($argstring, $parser, $pos = 0) {
        if (!empty($argstring) && !preg_match($this->syntax, $argstring)) {
            throw TemplateSyntaxError('Please specify time to live value for this cache block');
        }
        
        $this->body = $parser->parse('endcache');
        $this->uid = md5($parser->filename.$pos);
        $this->ttl = (int) $argstring;
        
        $options = $parser->options;
        
        if ($this->ttl) {
            $options['cache_ttl'] = $this->ttl;
        }
        
        if (!$options['cache']) {
            $options['cache'] = 'file';
        }
        $this->cache = h2o_cache($options);
    }
    
    function render($context, $stream) {
        if ($output = $this->cache->read($this->uid)) {
            $stream->write($output);
            return;
        }
        
        $output = new StreamWriter;
        $this->body->render($context, $output);
        
        $output = $output->close();
        $this->cache->write($this->uid, $output);

        $stream->write($output);
    }
}

h2o::addTag('cache');
?>