<?php
$time = microtime(true);
require_once '../../h2o.php';


//h2o::load_plugin('bullshit');

$template = new H2O('extended.html', array('cache'=>true, 'output_cache'=>true));


$template->evaluate(array(
	'page' => array('title'=>'',
					'content'=>'extending parent content',
					'test'=>array('testing'=>array('last'=>'')),
					'editable'=>true, 'deletable'=> false),
					
	'links' => array('home page' =>'http://example.org', 'help page'=>'http://xo.com', 'link 1'=>'http://xo.com', 'link2'=>'http://xo.com', 'link3'=>'http://xo.com'),

));
echo(h2o_filesize(memory_get_peak_usage()));
debug(get_included_files());


debug(microtime(true) - $time);
?>