<?php
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ipaddr = preg_replace('/,.*/', '', $_SERVER['HTTP_X_FORWARDED_FOR']);
} else {
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ipaddr = $_SERVER['HTTP_CLIENT_IP'];
    } else {
        $ipaddr = $_SERVER['REMOTE_ADDR'];
    }
}
echo $ipaddr;

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