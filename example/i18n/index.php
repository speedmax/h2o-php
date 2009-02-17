<?php
require '../../h2o.php';

$template = new H2o('trans.html', array(
    'cache'=> false,
    'i18n' => array(
        'locale' => isset($_GET['lang']) ? $_GET['lang'] : false,
        'charset' => 'UTF-8',
        'gettext_path' => '../bin/gettext/bin/',
        'extract_message' => true,
        'compile_message' => true,
    )
));

# Setup custom gettext resolver


$time_start = microtime(true);

echo $template->render(array(
    'users' => array(
        array(
            'username' =>           'peter',
            'tasks' => array('school', 'writing'),
            'user_id' =>            1,
        ),
        array(
            'username' =>           'anton',
            'tasks' => array('go shopping'),
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

?>