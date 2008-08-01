<?php

	require '../../h2o.php';

	$template = new H2O('rss.tpl', array('cache'=>true));
	
	// data get from database or file
	$articles = array(
		array('id' => 1, 'title' => 'first article', 'description'=>'description of first article'), 
		array('id' => 2, 'title' => 'second article', 'description'=>'description of second article'), 
	);
	
	$rss = array(
		'title' => 'testing my feed',
		'url' => $_SERVER['REQUEST_URI'],
		'description' => 'showing you how to create a rss feed',
		'created' => 'today',
		'language' => 'en'
	);

		
	//Header management will leave to yourself, i won't do too much.
	header("Content-Type: application/rss+xml");

	$template->evaluate(compact('articles', 'rss'));
	
?>