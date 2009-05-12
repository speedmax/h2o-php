<?php

function_exists('simpletest_autorun') or require 'simpletest/autorun.php';
class_exists('H2o') or require dirname(dirname(__FILE__)).'/h2o.php';
class_exists('SimpleSpec') or require dirname(__FILE__).'/spec.php';


?>