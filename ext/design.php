<?php

h2o::addTag('lorem');

class Lorem_Tag extends H2o_Node {
    private $common = true;
    private $syntax = '/^(\d+)?(?:\s+(p|w))?(?:\s?(random))?$/i';
    var $hint = "{% lorem [count] [format] [random]%}";

    function __construct($argstring, $parser, $position = 0) {
        if (!preg_match($this->syntax, trim($argstring), $match))
            throw new TemplateSyntaxError($this->hint);
        @list(,$count, $mode, $random) = $match;
        $this->count = $count? $count : 1;
        $this->mode = $mode? $mode : 'p';
        $this->common = !$random;
    }
    
    function render($context, $stream) {
        $output = '';
        switch($this->mode) {
            case 'w' :
                $output = lorem_words($this->count); break;
            case 'p':
                $output = "<p>".join("</p>\n<p>", lorem_paragraphs($this->count, $this->common)).'</p>';
                break;
        }
        $stream->write($output);
    }
}

function lorem_words($count, $common = false) {
    $words = $common ? lorem_dictionary('common') : array();
    $list = lorem_dictionary('words');
    $length = count($list);
    foreach (range(count($words), $count-1) as $step) {
        $words[] = $list[rand(0, $length-1)];
    }
    return join(' ', $words);
}

function lorem_sentences($count) {
    $sentenses = array();
    $delimiter = "?.";
    foreach(range(1, $count) as $s) {
        $parts = array();
        foreach (range(1, rand(1,5)) as $j)
            $parts[] = lorem_words(rand(3,12)); 
        $sentenses[] = join(', ', $parts);   
    }
    return join($delimiter[rand(0,1)].' ', $sentenses).$delimiter[rand(0,1)];
}

function lorem_paragraphs($count, $common = false) {
    $paras = array();
    foreach(range(1, $count) as $s) {
        if ($s == 1 && $common)
            $paras[] = lorem_dictionary('common_p');
        else 
            $paras[] = lorem_sentences(rand(1,4));
    }
    return $paras;
}

function lorem_dictionary($type) {
    $lorems = array(
    'common_p' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
    'common' => array('lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur',
        'adipisicing', 'elit', 'sed', 'do', 'eiusmod', 'tempor', 'incididunt',
        'ut', 'labore', 'et', 'dolore', 'magna', 'aliqua'),
    'words'=> array(
        'exercitationem', 'perferendis', 'perspiciatis', 'laborum', 'eveniet',
        'sunt', 'iure', 'nam', 'nobis', 'eum', 'cum', 'officiis', 'excepturi',
        'odio', 'consectetur', 'quasi', 'aut', 'quisquam', 'vel', 'eligendi',
        'itaque', 'non', 'odit', 'tempore', 'quaerat', 'dignissimos',
        'facilis', 'neque', 'nihil', 'expedita', 'vitae', 'vero', 'ipsum',
        'nisi', 'animi', 'cumque', 'pariatur', 'velit', 'modi', 'natus',
        'iusto', 'eaque', 'sequi', 'illo', 'sed', 'ex', 'et', 'voluptatibus',
        'tempora', 'veritatis', 'ratione', 'assumenda', 'incidunt', 'nostrum',
        'placeat', 'aliquid', 'fuga', 'provident', 'praesentium', 'rem',
        'necessitatibus', 'suscipit', 'adipisci', 'quidem', 'possimus',
        'voluptas', 'debitis', 'sint', 'accusantium', 'unde', 'sapiente',
        'voluptate', 'qui', 'aspernatur', 'laudantium', 'soluta', 'amet',
        'quo', 'aliquam', 'saepe', 'culpa', 'libero', 'ipsa', 'dicta',
        'reiciendis', 'nesciunt', 'doloribus', 'autem', 'impedit', 'minima',
        'maiores', 'repudiandae', 'ipsam', 'obcaecati', 'ullam', 'enim',
        'totam', 'delectus', 'ducimus', 'quis', 'voluptates', 'dolores',
        'molestiae', 'harum', 'dolorem', 'quia', 'voluptatem', 'molestias',
        'magni', 'distinctio', 'omnis', 'illum', 'dolorum', 'voluptatum', 'ea',
        'quas', 'quam', 'corporis', 'quae', 'blanditiis', 'atque', 'deserunt',
        'laboriosam', 'earum', 'consequuntur', 'hic', 'cupiditate',
        'quibusdam', 'accusamus', 'ut', 'rerum', 'error', 'minus', 'eius',
        'ab', 'ad', 'nemo', 'fugit', 'officia', 'at', 'in', 'id', 'quos',
        'reprehenderit', 'numquam', 'iste', 'fugiat', 'sit', 'inventore',
        'beatae', 'repellendus', 'magnam', 'recusandae', 'quod', 'explicabo',
        'doloremque', 'aperiam', 'consequatur', 'asperiores', 'commodi',
        'optio', 'dolor', 'labore', 'temporibus', 'repellat', 'veniam',
        'architecto', 'est', 'esse', 'mollitia', 'nulla', 'a', 'similique',
        'eos', 'alias', 'dolore', 'tenetur', 'deleniti', 'porro', 'facere',
        'maxime', 'corrupti'),
    );
    return $lorems[$type];
}
?>