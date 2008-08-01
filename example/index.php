<?php
/*
 * H2O Example;
 * 
 *	You may already see, Data below is trying to represent and very 90% similar to
 *	a real world dynamic web page
 */
 
  // Let's load it up
  require '../h2o.php';

  
  h2o::load_plugin('sample_data');
  
  // Setup h2o,
  // Don't worry about the cache, if you make changes to template file it will 
  // regenerate from latest copy.
  $template = new H2O('index.tpl', array('cache'=>true));


  
  // Package your data into context object
  $context = sample_data('index.txt');
  

  // Evaluate the template against context, then compile, save, display
  $template->evaluate($context);

 /*
  //Testing standard PHP include without the h2o involved 

  require '../lib/filters.php';
  $context = compact('url','menus','page','blog_entries','categories','articles');
  extract($context);
  include '../compile/'.md5('index.tpl').'.php';
*/

 echo(h2o_filesize(memory_get_peak_usage()));
debug(get_included_files());
?>