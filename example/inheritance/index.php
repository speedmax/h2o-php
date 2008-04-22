<?php
require '../../h2o.php';

$template = new H2O('index.html', array('cache'=>true, 'output_cache'=>false));
//h2o::load_plugin('bullshit');
$output = $template->evaluate(array(
	'page' => array('title'=>time(),
					'content'=>'This is page content',
					'test'=> array('testing'=> array('last'=>'somethig reall deep')),
					'editable'=> true, 'deletable'=> false),
					
	'links' => array('home page' =>'http://example.org', 'help page'=>'http://xo.com', 'link 1'=>'http://xo.com', 'link2'=>'http://xo.com', 'link3'=>'http://xo.com'),

));




echo(h2o_filesize(memory_get_peak_usage()));
debug(get_included_files());
?>

