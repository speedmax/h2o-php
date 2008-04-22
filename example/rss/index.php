<?php
	
	if (function_exists('ini_set'))
		ini_set('user_agent', 'Mozilla 4');
	
	require '../../h2o.php';

	// Get data
	$rss_file = file_get_contents('http://www.digg.com/rss/index.xml');
	$feed =  @new SimpleXMLElement($rss_file);
	
	// Display
	$template = new H2O('display_feed.tpl', array('cache'=>false));
	$template->evaluate(compact('feed', 'rss_file'));
	
?>