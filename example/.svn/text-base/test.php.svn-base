<?php
    require '../h2o.php';
    require H2O_ROOT.'h2o/parser.php';
    
    H2O_RE::init();
    

    $regex = 'un';
    $str = "marry needs a bag";
    $b = '/bag/ixs';
    $c = '{bag}';
    var_dump(H2O_RE::$named_args, preg_match(H2O_RE::$name, "something.something.0"));
    var_dump(H2O_RE::$named_args, preg_match(H2O_RE::$named_args, "something: 2.1"));
    var_dump(H2O_RE::$named_args, preg_match(H2O_RE::$named_args, "something: variable"));
    
die;

$a = array('name' => 'variable');
$s = ':variable';

$time_start = microtime(true);
for($i=0; $i<1000000; ++$i)
    is_array($a);
echo microtime(true) - $time_start . '<br>';

$time_start = microtime(true);
for($i=0; $i<1000000; ++$i)
    $r = is_array($a) && isset($s['name']);
    
var_dump(substr(':variable', 1));
echo microtime(true) - $time_start . '<br>';
    
$time_start = microtime(true);
for($i=0; $i<1000000; ++$i)
    $s[0] === ':';
echo microtime(true) - $time_start . '<br>';


$time_start = microtime(true);
for($i=0; $i<1000000; ++$i)
    strpos($s, ':') === 0;
echo microtime(true) - $time_start . '<br>';

//function sym_to_str($string) {
//    return substr($string, 1);
//}
//
//function is_sym($string) {
//    return isset($string[0]) && $string[0] === ':';
//}
//
//function symbol($string) {
//    return ':'.$string;
//}


putenv("LC_ALL=de");
setlocale(LC_ALL, 'de');

bindtextdomain("messages", "./locale");
textdomain("messages");


 echo _("This is a h2o template internaltionalized");
 
  echo ngettext("item", 'items', 2);
?>