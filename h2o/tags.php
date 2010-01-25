<?php
/**
 * 
 * @author taylor.luk
 * @todo tags need more test coverage
 */

class If_Tag extends H2o_Node {
    private $body;
    private $else;
    private $negate;
    
    function __construct($argstring, $parser, $position = 0) {
        if (preg_match('/\s(and|or)\s/', $argstring)) 
            throw new TemplateSyntaxError('H2o doesn\'t support multiple expressions');

        $this->body = $parser->parse('endif', 'else');
        
        if ($parser->token->content === 'else')
            $this->else = $parser->parse('endif');

        $this->args = H2o_Parser::parseArguments($argstring);

        $first = current($this->args);
        if (isset($first['operator']) && $first['operator'] === 'not') {
            array_shift($this->args);
            $this->negate = true;
        }
    }

    function render($context, $stream) {
        if ($this->test($context)) 
            $this->body->render($context, $stream);
        elseif ($this->else)
            $this->else->render($context, $stream);
    }

    function test($context) {
        $test = Evaluator::exec($this->args, $context);
        return $this->negate? !$test : $test;
    }
}

class For_Tag extends H2o_Node {
    public $position;
    private $iteratable, $key, $item, $body, $else, $limit, $reversed;
    private $syntax = '{
        ([a-zA-Z][a-zA-Z0-9-_]*)(?:,\s?([a-zA-Z][a-zA-Z0-9-_]*))?
        \s+in\s+
        ([a-zA-Z][a-zA-Z0-9-_]*(?:\.[a-zA-Z_0-9][a-zA-Z0-9_-]*)*)\s*   # Iteratable name
        (?:limit\s*:\s*(\d+))?\s*
        (reversed)?                                                     # Reverse keyword
    }x';

    function __construct($argstring, $parser, $position) {
        if (!preg_match($this->syntax, $argstring, $match))
            throw new TemplateSyntaxError("Invalid for loop syntax ");
        
        $this->body = $parser->parse('endfor', 'else');
        
        if ($parser->token->content === 'else')
            $this->else = $parser->parse('endfor');

        @list(,$this->key, $this->item, $this->iteratable, $this->limit, $this->reversed) = $match;
        
        if ($this->limit)
            $this->limit = (int) $this->limit;

        # Swap value if no key found
        if (!$this->item) {
            list($this->key, $this->item) = array($this->item, $this->key);
        }
        $this->iteratable = symbol($this->iteratable);
        $this->reversed = (bool) $this->reversed;
    }

    function render($context, $stream) {
        $iteratable = $context->resolve($this->iteratable);

        if ($this->reversed)
            $iteratable = array_reverse($iteratable);

        if ($this->limit)
            $iteratable = array_slice($iteratable, 0, $this->limit);

        $length = count($iteratable);
        
        if ($length) {
            $parent = $context['loop'];
            $context->push();
            $rev_count = $is_even = $idx = 0;
            foreach($iteratable as $key => $value) {
                $is_even =  $idx % 2;
                $rev_count = $length - $idx;
                
                if ($this->key) {
                    $context[$this->key] = $key;
                }
                $context[$this->item] =  $value;
                $context['loop'] = array(
                    'parent' => $parent,
                    'first' => $idx === 0, 
                    'last'  => $rev_count === 1,
                    'odd'   => !$is_even,
                    'even'  => $is_even,
                    'length' => $length,
                    'counter' => $idx + 1,
                    'counter0' => $idx,
                    'revcounter' => $rev_count,
                    'revcounter0' => $rev_count - 1
                );
                $this->body->render($context, $stream);
                ++$idx;                
            }
            $context->pop();
        } elseif ($this->else)
            $this->else->render($context, $stream);
    }
}

class Block_Tag extends H2o_Node {
    public $name;
    public $position;
    public $stack;
    private $syntax = '/^[a-zA-Z_][a-zA-Z0-9_-]*$/';
    
    function __construct($argstring, $parser, $position) {
        if (!preg_match($this->syntax, $argstring))
            throw new TemplateSyntaxError('Block tag expects a name, example: block [content]');

        $this->name = $argstring;

        if (isset($parser->storage['blocks'][$this->name]))
            throw new TemplateSyntaxError('Block name exists, Please select a different block name');

        $this->filename = $parser->filename;
        $this->stack = array($parser->parse('endblock', "endblock {$this->name}"));

        $parser->storage['blocks'][$this->name] = $this;
        $this->position = $position;
    }

    function addLayer(&$nodelist) {
        $nodelist->parent = $this;
        array_push($this->stack, $nodelist);
    }

    function render($context, $stream, $index = 1) {
        $key = count($this->stack) - $index;

        if (isset($this->stack[$key])) {
            $context->push();
            $context['block'] = new BlockContext($this, $context, $index);
            $this->stack[$key]->render($context, $stream);
            $context->pop();
        }
    }
}

class Extends_Tag extends H2o_Node {
    public $filename;
    public $position;
    public $nodelist;
    private $syntax = '/^["\'](.*?)["\']$/';
    
    function __construct($argstring, $parser, $position = 0) {
      if (!$parser->first)
            throw new TemplateSyntaxError('extends must be first in file');

      if (!preg_match($this->syntax, $argstring))
            throw new TemplatesyntaxError('filename must be quoted');

        $this->filename = stripcslashes(substr($argstring, 1, -1));

        # Parse the current template
        $parser->parse();

        # Parse parent template
        $this->nodelist = $parser->runtime->loadSubTemplate($this->filename, $parser->options);
        $parser->storage['templates'] = array_merge(
            $parser->storage['templates'], $this->nodelist->parser->storage['templates']
        );
        $parser->storage['templates'][] = $this->filename;
        
        if (!isset($this->nodelist->parser->storage['blocks']) || !isset($parser->storage['blocks']))
            return ;

        # Blocks of parent template
        $blocks =& $this->nodelist->parser->storage['blocks'];

        # Push child blocks on top of parent blocks
        foreach($parser->storage['blocks'] as $name => &$block) {
            if (isset($blocks[$name])) {
                $blocks[$name]->addLayer($block);
            }
        }
    }
    
    function render($context, $stream) {
        $this->nodelist->render($context, $stream);
    }
}

class Include_Tag extends H2o_Node {
    private $nodelist;
    private $syntax = '/^["\'](.*?)["\']$/';
    
    function __construct($argstring, $parser, $position = 0) {
        if (!preg_match($this->syntax, $argstring)) 
            throw new TemplateSyntaxError();

        $this->filename = stripcslashes(substr($argstring, 1, -1));
        $this->nodelist = $parser->runtime->loadSubTemplate($this->filename, $parser->options);
        $parser->storage['templates'] = array_merge(
            $this->nodelist->parser->storage['templates'], $parser->storage['templates']
        );
        $parser->storage['templates'][] = $this->filename;
    }

    function render($context, $stream) {
        $this->nodelist->render($context, $stream);
    }
}

class With_Tag extends H2o_Node {
    public $position;
    private $variable, $shortcut;
    private $nodelist;
    private $syntax = '/^([\w]+(:?\.[\w]+)?)\s+as\s+([\w]+(:?\.[\w]+)?)$/';
    
    function __construct($argstring, $parser, $position = 0) {
        if (!preg_match($this->syntax, $argstring, $matches))
            throw new TemplateSyntaxError('Invalid with tag syntax');
            
        # extract the long name and shortcut
        list($this->variable, $this->shortcut) = $matches;
        $this->nodelist = $parser->parse('endwith');
    }
    
    function render($context, $stream) {
        $variable = $context->getVariable($this->variable);
        
        $context->push(array($this->shortcut => $variable));
        $this->nodelist->render($context, $stream);
        $context->pop();
    }
}

class Cycle_Tag extends H2o_Node {
    private $uid;
    private $sequence;
    
    function __construct($argstring, $parser, $pos) {
        $args = h2o_parser::parseArguments($argstring);
        
        if (count($args) < 2) {
            throw new Exception('Cycle tag require more than two items');
        }
        $this->sequence = $args;        
        $this->uid = '__cycle__'.$pos;
    }
    
    function render($context, $stream) {
        if (!is_null($item = $context->getVariable($this->uid))) {
            $item = ($item + 1) % count($this->sequence);
        } else {
            $item = 0;
        }
        $stream->write($context->resolve($this->sequence[$item]));
        $context->set($this->uid, $item);
    }
}

class Load_Tag extends H2o_Node {
    public $position;
    private $searchpath = array(H2O_ROOT);
    private $extension;

    function __construct($argstring, $parser, $pos = 0) {
        $this->extension = stripcslashes(preg_replace("/^[\"'](.*)[\"']$/", "$1", $argstring));
        
        if ($parser->runtime->searchpath)
            $this->appendPath($parser->runtime->searchpath);
            
        $parser->storage['included'][$this->extension] = $file = $this->load();
        $this->position = $pos;
    }

    function render($context, $stream) {
        $this->load();
    }

    function appendPath($path) {
        $this->searchpath[] = $path;
    }
    
    private function load() {
        if (isset(h2o::$extensions[$this->extension])) {
            return true;
        }
        foreach($this->searchpath as $path) {
            $file = $path.'ext'.DS.$this->extension.'.php';
            if (is_file($file)) {
                h2o::load($this->extension, $file);
                return $file;
            }
        }
        throw new H2o_Error(
            "Extension: {$this->extension} cannot be loaded, please confirm it exist in extension path"
        );
    }
}

class Debug_Tag extends H2o_Node {
    private $argument;
    function __construct($argstring, $parser, $pos = 0) {
        $this->argument = $argstring;
    }
    
    function render($context, $stream) {
        if ($this->argument) {
            $object = $context->resolve(symbol($this->argument));
        } else {
            $object = $context->scopes[0];
        }
        $output = "<pre>". print_r($object, true). "</pre>";
        $stream->write($output);
    }
}

class Now_Tag extends H2o_Node {
    function __construct($argstring, $parser, $pos=0) {
        $this->format = $argstring;
        if (!$this->format) {
            $this->format = "D M j G:i:s T Y";
        }
    }
    
    function render($contxt, $stream) {
        sleep(1);
        $time = date($this->format);
        $stream->write($time);
    }
}

class Autoescape_Tag extends H2o_Node {
    protected $enable;
    
    function __construct($argstring, $parser, $pos = 0) {
        if ($argstring === 'on')
            $this->enable = true;
        elseif ($argstring === 'off')
            $this->enable = false;
        else throw new H2o_Error(
            "Invalid syntax : autoescape on|off "
        );
    }
    
    function render($context, $stream) {
        $context->autoescape = $this->enable;
    }
}

class Csrf_token_Tag extends H2o_Node {
    function render($context, $stream) {
        $token = "";
        if (isset($_COOKIE["csrftoken"]))
            $token = $_COOKIE["csrftoken"];
        else {
            global $SECRET_KEY;
            if (defined('SECRET_KEY'))
                $token = md5(mt_rand() . SECRET_KEY);
            else
                $token = md5(mt_rand());
        }
        setcookie("csrftoken", $token, time()+60*60*24*365, "/");
        $stream->write("<div style='display:none'><input type=\"hidden\" value=\"$token\" name=\"csrfmiddlewaretoken\" /></div>");
    }
}

H2o::addTag(array('block', 'extends', 'include', 'if', 'for', 'with', 'cycle', 'load', 'debug', 'now', 'autoescape', 'csrf_token'));
?>
