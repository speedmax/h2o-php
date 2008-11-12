<?php
print_r(memory_get_usage());
include '../h2o.php';
h2o::load('i18n');
//// Set language to German

//$i18n = new H2o_I18n(dirname(__FILE__).DS, array(
//    'gettext_path' => dirname(__FILE__).DS.'bin/gettext/bin/' 
//));
//$i18n->setLocale('fr');
//
//$i18n->extract();
//$i18n->compile();
////
// Choose domain
//extract_translations(
//   realpath('trans.tpl'), array('tpl', 'html'), dirname(__FILE__).DS.'bin/gettext/bin/'
//);
//
//compile_translations(
//   realpath('trans.tpl'), null, dirname(__FILE__).DS.'bin/gettext/bin/'
//);
    
$template = new H2o('trans.tpl', array('cache'=> false, 'cache_dir' => dirname(__FILE__)));
$time_start = microtime(true);

for($i=0; $i<10; $i++)
$r = $template->render(array(
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
echo $r;

echo "in ".(microtime(true) - $time_start)." seconds\n<br/>";
