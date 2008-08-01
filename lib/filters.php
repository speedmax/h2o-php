<?php

/*	Ultizie php funciton as Filters */
h2o::add_filters(array(
	'md5', 'sha1', 'join', 'wordwrap', 'trim',
	'lower' => 'strtolower',
	'upper' => 'strtoupper',
	'length'=> 'count',
	'wordcount'	=> 'str_word_count',
	'numberformat' => 'number_format',
));

h2o::add_filter('currency');
function currency($amount, $currency = 'USD', $precision = 2, $negateWithParentheses = false) {
    $definition = array('EUR' => array('€','.',','), 
                    'GBP' => '£', 'JPY' => '¥', 'USD'=>'$', 'AU' => '$', 'CAN' => '$');
    $negative = false;
    $separator = ','; 
    $decimals = '.';
    $currency = strtoupper($currency);
    
    // Is negative 
    if (strpos('-', $amount)!== false) {
        $negative = true;
        $amount = str_replace("-","",$amount);
    }
    $amount = (float) $amount;
    
    if (!$negative) {
        $negative = $amount < 0;
    }
    if ($negateWithParentheses) {    
        $amount = abs($amount);
    }
    // Get rid of negative zero
    $zero = round(0, $precision);
    if (round($amount, $precision) == $zero) {
        $amount = $zero;
    }

    if (isset($definition[$currency])) {
        $symbol = $definition[$currency];
        if (is_array($symbol))
            @list($symbol, $separator, $decimals) = $symbol;
    } else {
        $symbol = $currency;
    }
    $amount = number_format($amount, $precision, $decimals, $separator);

    if ($negateWithParentheses) {
       return "({$symbol}{$amount})";
    }
    return "{$symbol}{$amount}";
}

h2o::add_filter('default', 'h2o_set_default');
	function h2o_set_default($string, $default){
		if ($string == null)
			return $default;
		return $string;
	}

h2o::add_filter('strip_tags', 'h2o_strip_tags');
    function h2o_strip_tags($text) {
      $text = preg_replace('/</',' <',$text);
      $text = preg_replace('/>/','> ',$text);
      return strip_tags($text);
    }
    
h2o::add_filter('humanize');
    function humanize($string) {
        $string = preg_replace('/\s+/', ' ', trim(preg_replace('/[^A-Za-z0-9()!,?$]+/', ' ', $string)));
        return capfirst($string);
    }   
    
h2o::add_filters(array('capitalize', 'title'=>'capitalize'));
	function capitalize($string) {
		return ucwords(strtolower($string)) ;
	}


h2o::add_filter('capfirst');
	function capfirst($string) {
		$string = strtolower($string);
		return strtoupper($string{0}). substr($string, 1, strlen($string));
	}

h2o::add_filter('tighten_space');
	function tighten_space($value) {
		return preg_replace("/\s{2,}/", ' ', $value);
	}

h2o::add_filters(array('escape', 
                    'e'=>'escape'));
	function escape($value, $attribute=false) {
		return htmlspecialchars($value, $attribute ? ENT_QUOTES : ENT_NOQUOTES);
	}

h2o::add_filter('truncate');
	function truncate ($string, $max = 50, $rep = '...') {
		$leave = $max - strlen ($rep);
		return substr_replace($string, $rep, $leave);
	}

h2o::add_filter('date', 'h2o_date');
	function h2o_date($time, $format = 'jS F Y H:i'){
		return date($format, strtotime($time));
	}

h2o::add_filter('relative_time');
	function relative_time($timestamp, $format = 'g:iA') {
		$timestamp = strtotime($timestamp);
		$time	= mktime(0, 0, 0);
		$delta	= time() - $timestamp;
	
		if ($timestamp < $time - 86400) {
			return date("F j, Y, g:i a", $timestamp);
		}
		if ($delta > 86400 && $timestamp < $time) {
			return "Yesterday at " . date("g:i a", $timestamp);
		}
		$string	= '';
		if ($delta > 7200)
			$string	.= floor($delta / 3600) . " hours, ";
		else if ($delta > 3660)
			$string	.= "1 hour, ";
		else if ($delta >= 3600)
			$string	.= "1 hour ";
		$delta	%= 3600;
		if ($delta > 60)
			$string	.= floor($delta / 60) . " minutes ";
		else
			$string .= $delta . " seconds ";
		return "$string ago";
	}

h2o::add_filter('relative_date');
	function relative_date($time) {
		$time = strtotime($time);
		$today = strtotime(date('M j, Y'));
		$reldays = ($time - $today)/86400;
		if ($reldays >= 0 && $reldays < 1) {
			return 'today';
		} else if ($reldays >= 1 && $reldays < 2) {
			return 'tomorrow';
		} else if ($reldays >= -1 && $reldays < 0) {
			return 'yesterday';
		}
		if (abs($reldays) < 7) {
			if ($reldays > 0) {
				$reldays = floor($reldays);
				return 'in ' . $reldays . ' day' . ($reldays != 1 ? 's' : '');
			} else {
				$reldays = abs(floor($reldays));
				return $reldays . ' day'  . ($reldays != 1 ? 's' : '') . ' ago';
			}
		}
		if (abs($reldays) < 182) {
			return date('l, F j',$time ? $time : CURRENT_TIME);
		} else {
			return date('l, F j, Y',$time ? $time : CURRENT_TIME);
		}
	}


h2o::add_filter('relative_datetime');
	function relative_datetime($time) {
		$date = relative_date($time);
		if ($date == 'today') {
			return relative_time($time);
		}
		return $date;
	}

h2o::add_filter('filesize', 'h2o_filesize');
	function h2o_filesize ($bytes, $round = 1) {
        if ($bytes==0)
            return '0 bytes';
        elseif ($bytes==1)
            return '1 byte';
        
        $units = array('bytes' => pow(2, 0),
                       'kB'    => pow(2, 10),
                       'BM'    => pow(2, 20),
                       'GB'    => pow(2, 30),
                       'TB'    => pow(2, 40),
                       'PB'    => pow(2, 50),
                       'EB'    => pow(2, 60),
                       'ZB'    => pow(2, 70),
                       'YB'    => pow(2, 80));
        $lastUnit = 'bytes';
        foreach ($units as $unitName => $unitFactor) {
            if ($bytes >= $unitFactor) {
                $lastUnit = $unitName;
            } else {
            	$number = round($bytes/$units[$lastUnit], $round);
           		return number_format($number).' '.$lastUnit;
            }
        }
	}

h2o::add_filter('linebreaks', 'linebreaks');
	function linebreaks($value, $format = 'p') {
		if ($format == 'br')
			return h2o_nl2br($value);
		return nl2pbr($value);
	}

h2o::add_filter('nl2br', 'h2o_nl2br');
	function h2o_nl2br($value) {
		return str_replace("\n", "<br />\n", $value);
	}

h2o::add_filter('nl2pbr');
	function nl2pbr($value) {
		$result = array();
		$parts = preg_split('/(\r?\n){2,}/m', $value);
		foreach ($parts as $part) {
			array_push($result, '<p>' . h2o_nl2br($part) . '</p>');
		}
		return implode("\n", $result);
	}

h2o::add_filter('first');
	function first($value){
		return $value[0];
	}

h2o::add_filter('last', 'h2o_last');
	function h2o_last ($value){
		return $value[count($value) - 1];
	}


h2o::add_filter('urlencode', 'h2o_urlencode');
    function h2o_urlencode($data) {
    	if (is_array($data)) {
			$result;
			foreach ($data as $name => $value) {
			     $result=$name.'='.urlencode($value).'&'.$querystring;
			}
			$querystring=substr($result,0,strlen($result)-1);
			return htmlentities($result);
    	} else {
    		return urlencode($data);
    	}
    }	
    
h2o::add_filter('hyphenize');
    function hyphenize ($string){
        $rules = array('/[^\w\s-]+/'=>'','/\s+/'=>'-', '/-{2,}/'=>'-');
    
        $string = preg_replace(array_keys($rules), $rules, trim($string));
        return $string = trim(strtolower($string));
    }
    
    
h2o::add_filter('urlize');
	function urlize($url, $truncate = false){
		if(preg_match('/^(http|https|ftp:\/\/([^\s"\']+))/i', $url, $match)){
			$url = '<a href="'.$url.'">'. ($truncate ? truncate($url,$truncate): $url).'</a>';
		}
		return $url;
	}

h2o::add_filter('limitwords');
    function limitwords($text, $limit = 50, $ending = '...') {
      if (strlen($text) > $limit) {
          $words = str_word_count($text, 2);
          $pos = array_keys($words);
          
          if (isset($pos[$limit])) {
          $text = substr($text, 0, $pos[$limit]) . $ending;
          }
      }
      return $text;
    }	
    
h2o::add_filter('highlight_h2o');
    function highlight_h2o($source, $line = 0){
        $replace = array(
            // html tags
            "/(&lt;\/?.*?&gt;)(.*?)?(&lt;\/?.*?&gt;)?/m"
                                        =>'<span style="color:#aaa">$1$2$3</span>',
            // h2o keywords
            '/({%.*?)(block|endblock|for|endfor|if|endif|else|with|endwith|include|extends|debug)\s+?([A-Za-z0-9.-_]*).*?/'
                                        => '$1<b style="color:#9c0">$2</b> <b style="color:#40FFFF">$3</b>',
            // quotes
            '/((:?&quot;|\')(:?.*)?(:?&quot;|\'))/m'
                                        => '<span style="color:orange">$1</span>',                      
            // h2o variable
            '/({{.*?)\s+?([A-Za-z0-9.-_]*)[^|}]*?/'
                                        => '$1 <b style="color:#40FFFF">$2</b>',
            // h2o variable/tag node
            '/({{|}}|{%|%})/m'          => "<b style='color:#EDF080'>$1</b>",
            );
                            
        $source = preg_replace(array_keys($replace), $replace, $source);
        $src = (preg_split('/\r?\n/', $source));
        $output ='<ol class="highlight" start="'.$line.'">';
        foreach ($src as $number=> $code){
            $stlye = $number % 2 == 1 ? 'style="background:#262626""' : '';
            $output .= "<li $stlye>$code</li>";
        }
        $output .= '</ol>';
        return ($output);
    }

?>