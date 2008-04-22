<?php


h2o::add_filter('base_url');
    function base_url($url, $full = false) {
        $url = Router::url($url);
        $url = str_replace(STORE_RELATIVE, '', $url);
        return $url;
    }

    
h2o::add_filter('css', 'h2o_insert_css');
    function h2o_insert_css($url,$rel = 'stylesheet'){
        $css_url = sprintf('<link href="%s" rel="%s" type="text/css"  media="all" />', base_url($url), $rel);
        return $css_url;
    }

h2o::add_filter('script', 'h2o_insert_script');
    function h2o_insert_script($url) {
        return sprintf('<script src="%s" type="text/javascript"></script>', base_url($url));
    }

h2o::add_filter('image_small');
    function image_small($url) {
        return image_size($url, 'small');
    }
h2o::add_filter('image_thumb');
    function image_thumb($url) {
        return image_size($url, 'thumb');
    }
h2o::add_filter('image_medium');
    function image_medium($url) {

        return image_size($url, 'medium');
    }
    
h2o::add_filter('image_size');
    function image_size($url, $size) {
        if ($size == 'original')
            return $url;
        $sizes = array('medium','thumb','small');
        if (in_array($size, $sizes)) {
            return dirname($url).'/'.$size.'/'.basename($url);
        }
    }
    
h2o::add_filter('store_url'); 
    function store_url($url) {
        return '/mercury/'.r(DS,'/',STORE_RELATIVE).$url;
        $url = Router::url($url);
        debug($url);
        
    }
    
h2o::add_filter('image', 'h2o_insert_image');
    function h2o_insert_image($url) {
        return sprintf('<img src="%s">', base_url($url));
    }   

h2o::add_lookup('collection', 'collection_lookup');
function collection_lookup ($var, &$context, $output = false) {
    global $H2O_RUNTIME;
    $h2o = $H2O_RUNTIME;
    $collection = array('Navigation','Gallery');
    $vars = explode('.', $var, 2);
    if (count($vars) == 1) return null;
    list($name, $type) = $vars;
    if (in_array($name, $collection)) {
        $handle = $context->resolve('handle');    
        if (!isset($handle)) {
            return null;
        }
        if ($output){
            return '$handle->read(strtolower("'.$name.'"), "'.$type.'")';
        } else {
            return $handle->read(strtolower($name), $type);
        }
    }
    return null;
}


?>