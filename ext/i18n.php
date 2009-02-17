<?php
h2o::addTag('trans');
h2o::addTag('blocktrans');
h2o::addLookup(array('H2o_I18n', 'gettext'));

class Trans_Tag extends H2o_Node {
    var $text = null;
    function __construct($argstring, $parser, $position = 0) {
        $this->text = stripcslashes(substr($argstring, 1, -1));
    }
    
    function render($context, $stream) {
        if ($this->text) {
            $gettext = H2o_I18n::$gettext;
            $stream->write(call_user_func($gettext, $this->text));
        }
    }
}

class Blocktrans_Tag extends H2o_Node {
    private $singular, $plural;
    private $vars = array();
    private $aliases = array();
    private $count;

    function __construct($argstring, $parser, $position = 0) {
        # get all aliases alias
        if (!empty($argstring)) { 
            $arguments = array_map('trim', explode(',', $argstring));
            foreach($arguments as $argument) {
                if (strpos($argument, '=')) {
                    list($alias, $variable) = explode('=', $argument);
                } else {
                    $variable = $alias = $argument;
                }    
                $this->aliases[trim($alias)] = H2o_Parser::parseArguments($variable);
            }
        }

        # Parse singular and plural block
        $this->singular = $parser->parse('plural', 'endblocktrans');
        
        if ($parser->token->content === 'plural') {
            $this->plural = $parser->parse('endblocktrans');
        }
        
        # compile nodes into string format
        if ($this->singular)
            $this->singular = $this->compile_nodes($this->singular);

        if ($this->plural)
            $this->plural = $this->compile_nodes($this->plural);
    }
    
    function render($context, $stream) {
        $context->push();
        $this->count = false;
        $cache = array();
        foreach($this->aliases as $alias => $data) {
            $variable = array_shift($data);
            $filters = $data;
            $object = $context->resolve($variable);
            
            if (is_null($object)) continue;
            
            if (!empty($filters))
                $object = $context->applyFilters($object, $filters);

            $cache[$alias] = $object;
            if ($this->count === false)
                $this->count = $object;
        }
        
        # Implicit object count lookup
        if (!is_integer($this->count))
            $this->count = count($this->count);
            
        # Get translation
        $ngettext = H2o_I18n::$ngettext;
        $gettext = H2o_I18n::$gettext;
        if ($this->plural)
            $output = call_user_func($ngettext, $this->singular, $this->plural, $this->count);
        else
            $output = call_user_func($gettext, $this->singular);

        # Variable in output
        foreach(array_keys($this->vars) as $var) {
            $object = isset($cache[$var])? $cache[$var]: $context->resolve($var);
            if (!is_null($object)) {
                $output = str_replace("%({$var})", $object, $output);
            }
        }
        $context->pop();
        $stream->write($output);
    }
    
    function compile_nodes($nodes) {
        $output = array();
        foreach ($nodes as $node) {
            if ($node instanceOf VariableNode) {
                if (is_sym($node->variable)) 
                    $var = sym_to_str($node->variable);
                else 
                    $var = $node->variable;
                $output[] = "%({$var})";
                $this->vars[$var] = 1;
            } elseif ($node instanceOf TextNode) {
                $output[] = str_replace("\r", '', $node->content);
            }
        }
        return join('', $output);
    }
}

class H2o_I18n {
    var $locale;
    var $charset = 'UTF-8';
    var $locale_dir;
    var $tmp_dir;
    var $extensions = array('html','tpl');
    var $gettext_path = '';
    var $gettext_setup = false;
    static $gettext = 'gettext';
    static $ngettext = 'ngettext';
    
    function __construct($path, $options = array()) {
        if (is_file($path))
            $path = dirname($path) . DS;

        $this->searchpath = realpath($path).DS;
        $this->locale_dir = $this->searchpath .'locale'.DS;
        $this->options = $options;
        
        if (isset($options['tmp_dir']))
            $this->tmp_dir = $options['tmp_dir'];
        else
            $this->tmp_dir = $this->searchpath.'tmp' .DS;
        
        if (isset($options['ext']))
            $this->extensions = $options['ext'];
            
        if (isset($options['charset']))
            $this->charset = $options['charset'];
            
        if (isset($options['locale']) && $options['locale'])
            $this->setLocale($options['locale']);
        
        if (isset($options['extract_message']) && $options['extract_message'])
            $this->extract();
        
        if (isset($options['compile_message']) && $options['compile_message'])
            $this->compile();
        
        if (!is_dir($this->locale_dir) && !mkdir($this->locale_dir))
            throw new Exception('locale directory not found and failed to created '.$this->searchpath);
    }
    
    function gettext($name, $context) {
        $gettext = self::$gettext;
        if (!is_string($name)) return ;
        $syntax = '/_\(((?:".*?")|(?:\'.*?\'))\)/';
        if (preg_match($syntax, $name, $match)) {
            $text = stripcslashes(substr($match[1], 1, -1));
            return call_user_func($gettext, $text);
        }
    }
    
    function setupGettext() {
        if (isset($this->options['gettext_path']))
            $this->gettext_path = $this->options['gettext_path'];

        if (!file_exists($this->gettext_path))
            $this->gettext_path = $this->searchpath.$this->gettext_path;

        $this->gettext_path = realpath($this->gettext_path).DS;

        if (!exec($this->gettext_path."xgettext -V")) {
            throw new Exception(
                "xgettext binary cannot be found, if you are using Windows system either install through cygwin
                or read instruction here http://docs.djangoproject.com/en/dev/topics/i18n/#gettext-on-windows"
            );
        }
        
        $this->gettext_setup = true;
    }
    
    function extract() {
        if (!$this->gettext_setup)
            $this->setupGettext();

        if (!class_exists('H2o_Lexer'))
            require H2O_ROOT.'h2o/parser.php';
        # get all tempaltes
        $templates = $this->getTemplates($this->searchpath, $this->extensions, array(dirname(dirname($this->gettext_path))));
        # Get all locales in translation path
        if (!is_dir($this->tmp_dir))
            mkdir($this->tmp_dir);
        
        # foreach locale
        foreach(glob($this->locale_dir.'*') as $dir) {
            if (!is_dir($dir)) continue;
            $locale = basename($dir);
            $lc_messages = $dir . DS . 'LC_MESSAGES'. DS;
            $pot_file = $lc_messages."messages.pot";
            $po_file = $lc_messages."messages.po";
    
            if (!is_dir($lc_messages))
                mkdir($lc_messages);
            
            if (is_file($pot_file))
                unlink($pot_file);
            
            # Compile messages into php file
            $sourcecode = array();
            foreach ($templates as $file) {
                # compile template into php code
                $compiled_src = templize(file_get_contents($file));
                if (!$compiled_src)
                  continue;
                $sourcecode[] = $compiled_src;
            }
            $compiled_file = $this->tmp_dir. 'template_source.php';
            file_put_contents($compiled_file, "<?php ". join(";\n", $sourcecode) ." ?>" );

            # run xgettext to extract all translation string
            $return = '';
            $extra_arg = '';
            if (is_file($pot_file)) 
                $extra_arg = "--omit-header"; 

            $cmd = "{$this->gettext_path}xgettext -L PHP {$extra_arg} --from-code UTF-8 -o - \"{$compiled_file}\"";
            if (!exec($cmd, $return)){
                throw new Exception('Failed to parse template file');
            }
            list($nplurals, $plural) = $this->getPluralForm($locale);
            $replace = array(
                'charset=CHARSET' =>  'charset=UTF-8',
                "#: {$compiled_file}" => "#: {$file}",
                "INTEGER" => $nplurals,
                "EXPRESSION" => $plural,
                "PACKAGE VERSION" => "template translation",
                "\"Language-Team: LANGUAGE <LL@li.org>\\n\"\n" => ""
            );
            $return = join("\n", $return);
            $return = str_replace(array_keys($replace), $replace , $return ."\n");
            file_put_contents($pot_file, $return, FILE_APPEND);
            
            # merge messages for each language
            if (is_file($pot_file)) {
                $return = $error = '';
                $cmd = $this->gettext_path."msguniq --to-code=utf-8 \"{$pot_file}\"";
                
                if (!exec($cmd, $return))
                    throw new Exception('Msgunique failed');
                
                file_put_contents($pot_file, join("\n", $return));
                if (is_file($po_file) && trim(file_get_contents($po_file)) !== '') {
                    $return = '';
                    $cmd = sprintf($this->gettext_path.'msgmerge -q "%s" "%s"', $po_file, $pot_file);
                    exec($cmd, $return);
                    file_put_contents($po_file, join("\n", $return));
                } else {
                    copy($pot_file, $po_file);
                }
            }
            @unlink($pot_file);
        }
        @unlink($compiled_file);
        @rmdir($this->tmp_dir);
    }

    function compile() {
        if (!$this->gettext_setup)
            $this->setupGettext();
        
        foreach(glob($this->locale_dir.'*') as $dir) {
          if (!is_dir($dir)) continue;
          $locale = basename($dir);
          $lc_messages = $dir.DS.'LC_MESSAGES'.DS;
          $po_file = $lc_messages."messages.po";
          $mo_file = $lc_messages."messages.mo";
    
          if (!is_dir($lc_messages))
              mkdir($lc_messages);
    
          $cmd = $this->gettext_path."msgfmt --check-format -o {$mo_file} {$po_file}";
          exec($cmd, $return);
      }
    }
    
    function setLocale($locale, $charset = null) {
        $this->locale = $locale;
        if (!$charset)
            $charset = $this->charset;
        
        putenv("LC_ALL={$locale}");
        setlocale(LC_ALL, $locale);
        if (!is_dir($this->locale_dir))
            throw new Exception('Cannot find Locale message path');
        bindtextdomain("messages", $this->locale_dir);
        bind_textdomain_codeset('messages', $charset);
        textdomain("messages");
    }
    
    function getPluralForm($locale = 'en') {
        $default= array("2", "(n != 1)");
        $default_locale = array(
            'en', 'fi', 'de', 'da', 'el', 'eo', 'es', 'et', 'eu', 'af', 'az', 'bg', 'bn', 'ca', 'nn', 'no', 'pt', 'sv'
        );
        // In-regular plural forms
        $map = array(
            'fr' => array("2", "(n >= 1)"),
            'ja' => array("1", "0"), 'zh' => array("1", "0"),
            'pl' => array("3", "(n==1 ? 0 : n%10>=2 && n%10< =4 && (n%100<10 or n%100>=20) ? 1 : 2)"),
            'ro' => array("3", "(n==1 ? 0 : (n==0 or (n%100 > 0 && n%100 < 20)) ? 1 : 2)"),
            'ru' => array("3", "(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10< =4 && (n%100<10 or n%100>=20) ? 1 : 2)"),
        );
        $locale = current(explode('-', $locale));
        if (isset($map[$locale])) {
            return $map[$locale];
        }
        return $default;
    }
    
    function getTemplates($path, $exts, $exclude = array()) {
        $results = array();
        foreach(new DirectoryIterator($path) as $f) {
            if ($f->isDot()) continue;
    
            if ($f->isFile()) {
                $ext = end(explode(".", $f->getFilename()));
                if ($ext && in_array($ext, $exts) ) {
                    $results[] = $f->getPathname();
                }
            } elseif ($f->isDir() && !in_array($f->getPathname(), $exclude)) {
                $results = array_merge($results, $this->getTemplates($f->getPathname(), $exts, $exclude));
            }
        }
        return $results;
    }
}

/*
    Compile
*/
function templize($source) {
    $output  = array();
    $inline_re = '/^\s*trans\s+("(?:[^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'(?:[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\')\s*/';
    $block_re = "/^\s*blocktrans(?:\s+|$)/";
    $endblock_re = "/^\s*endblocktrans$/";
    $plural_re = "/^\s*plural$/";
    $var_re = '{
        _\(
            (
            "[^"\\\\]*(?:\\\\.[^"\\\\]*)*" |   # Double Quote string   
            \'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\' # Single Quote String
            )
        \)
    }x';
    
    $lexer = new H2o_Lexer(h2o::getOptions());
    $tokenstream = $lexer->tokenize($source);
    
    $in_block = false;
    $is_plural = false;
    
    $singulars = array();
    $plurals = array();
    
    while($t = $tokenstream->next()) {
        if ($in_block) {
            if ($t->type == 'block' && $t->content == 'endblocktrans') {
                if ($is_plural) {
                    $output[] = sprintf(
                        " ngettext('%s', '%s', \$count)", join('', $singulars), join('', $plurals)
                    ); 
                } else {
                    $output[] = sprintf(" gettext('%s')", join('', $singulars)); 
                }
                $singulars = $plurals = array();
                $in_block = $is_plural = false ;
            } 
            elseif ($t->type == 'block' && $t->content == 'plural') {
                $is_plural = true;
            }
            elseif ($t->type == 'text') {
                if ($is_plural)
                    $plurals[] = addslashes($t->content);
                else
                    $singulars[] = addslashes($t->content);
            }
            elseif ($t->type == 'variable') {
                @list($var, $filters ) = explode('|', $t->content);
                if ($is_plural)
                    $plurals[] = sprintf("%%(%s)", $var);
                else
                    $singulars[] = sprintf("%%(%s)", $var);
            }
            elseif ($t->type == 'block') {
                throw new Exception('No block tag is allowed in translation block');
            }
        } else {
            if ($t->type == 'block') {
                if (preg_match($inline_re, $t->content, $matches)) {
                    $output[] = sprintf(" gettext(%s)", $matches[1]);
                }
                elseif (preg_match($block_re, $t->content, $matches)) {
                    $in_block = true;
                } 
            } elseif ($t->type == 'variable') {
                if (preg_match($var_re, $t->content, $matches)) {
                    $output[] = sprintf(" gettext(%s)", ($matches[1]));
                }
            }
        }
    }
    $result = str_replace("\r", '', implode(";\n", $output));
    if ($result)
    return "\n".$result . ";\n";
}

?>