<?php


h2o::add_filter('img_get_size');
function img_get_size ($string, $type) {
	$path = pathinfo($string);
	$path['dirname'] .= '/'.$type;
	return $path['dirname'].'/'.$path['basename'];
}


h2o::add_filter('img_get_thumb');
function img_get_thumb ($string) {
	return img_get_size($string, 'thumb');
}

h2o::add_filter('img_get_medium');
function img_get_medium ($string) {
	return img_get_size($string, 'medium');
}


h2o::add_filter('img_get_large');
function img_get_large ($string) {
	return img_get_size($string, 'large');
}

h2o::add_tag('image', 'ImageTag'); 
class ImageTag extends TagNode {
    var $arguments;
    
    function __construct($argstring, &$parser, $position){
        $args = $this->parseArguments($argstring, $position);
        if (!in_array($args[0], array('single', 'set'))) {
            throw new H2o_TemplateSyntaxError('Display image syntax error, (example: image [single/set], image_url, image_description, image_alt )', 
                        $parser->filename,
                        $position,
                        $argstring);
        } else {
            $this->arguments = $args = $this->parseArguments($argstring, $position);
        }
    }
    
    function compile(&$context, &$stream){
        foreach ($this->arguments as $arg) {
           $rel_type = $this->arguments[0]; 
           $href = $this->arguments[1];
           $title = $this->arguments[2];
           $alt = $this->arguments[3];
        }
        if ($rel_type == 'single') {
            $rel = 'lightbox';  
        } else {
            $rel = 'lightbox[atomium]';
        }
        $stream->write('<a href='.$href.' rel="'.$rel.'" title='.$title.'><img src='.$href.' alt='.$alt.' /></a>');
    }
}

h2o::add_tag('gallery', 'GalleryTag'); 
class GalleryTag extends TagNode {
    var $handle;
    var $column;
    
    function __construct($argstring, &$parser, $position){
    	$args = $this->parseArguments($argstring, $position);
    	
        if (is_null($args[0]) || !is_numeric($args[1])) {
            throw new H2o_TemplateSyntaxError('Display gallery syntax error, (example: gallery Gallery.handle-name, number-in-a-row )', 
                        $parser->filename,
                        $position,
                        $argstring);
        }  else {
            $this->handle = $args[0];
            $this->column = $args[1];
        }
    }
    
    function compile(&$context, &$stream){
        $args = $context->resolve($this->handle);
           
        $output = '';
        if (!empty($args)) {
        	$output .= "<table cellspacing=\"0\" cellpadding=\"0\"><tr>";
            foreach ($args as $i => $arg) {
                $href = '/'.$arg["media_path"];
                $title = $arg["description"];
                $alt = $arg["name"];
                $output .= '<td><a href="'.$href.'" rel="lightbox[atomium]" title="'.$title.'"><img src="'.$href.'" alt="'.$alt.'" /></a></td>';
                $output .= ($i+1)%$this->column ? "" : "</tr><tr>";
            }
            $output .= "</tr></table>";
        }
        $stream->write($output);
    }
}

h2o::add_lookup('collection', 'collection_lookup');
function collection_lookup ($var, &$context, $output = false){
	global $H2O_RUNTIME;
	$h2o = $H2O_RUNTIME;
	$collection = array('Navigation','Gallery');
	$vars = explode('.', $var, 2);
	if (count($vars) == 1) return null;
	list($name, $type) = $vars;
	if (in_array($name, $collection)) {
		if (!isset($h2o->parser->storage['helpers']['handle'])) {
			return null;
		}
		$handle = $h2o->parser->storage['helpers']['handle'];
		//$handle = $context->resolve('handle');	
		if ($output){
			return '$handle->read(strtolower("'.$name.'"), "'.$type.'")';
		} else {
			return $handle->read(strtolower($name), $type);
		}
	}
	return null;
}


?>