<?php
/**
 *   Simple example rendering a user list
 *   ------------------------------------
 *   
 *   @credit - adapt from ptemplates sample
 */
require '../../h2o.php';

$template = new H2o('index.html', array(
    'cache_dir' => dirname(__FILE__)
));

$time_start = microtime(true);

echo $template->render(array(
    'users' => array(
        array(
            'username' =>           'peter <h1>asdfasdf</h1>',
            'tasks' => array('school', 'writing'),
            'user_id' =>            1,
        ),
        array(
            'username' =>           'anton',
            'tasks' => array('go shopping <h1>je</h1'),
            'user_id' =>            2,
        ),
        array(
            'username' =>           'john doe',
            'tasks' => array('write report', 'call tony', 'meeting with arron'),
            'user_id' =>            3
        ),
        array(
            'username' =>           'foobar',
            'tasks' => array(),
            'user_id' =>            4
        )
    )
));

echo "in ".(microtime(true) - $time_start)." seconds\n<br/>";
