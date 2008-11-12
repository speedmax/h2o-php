<?php

require '../../h2o.php';
h2o::load('i18n');

$i18n = new H2o_I18n(dirname(__FILE__).DS, array(
   #  For windows users you can specify where is the path to gettext binary
   # 'gettext_path' => dirname(__FILE__).DS.'bin/gettext/bin/' 
));

if (isset($_GET['lang'])) {
    $i18n->setLocale($_GET['lang']);
}

$i18n->extract();   // Extract translation string to PO files
$i18n->compile();   // Compiles PO files to MO files

$template = new H2o('trans.html', array('cache'=> false));

ender(array(
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

?>