<?php
/**
 * H2o Template system
 * 	H2o is a powerful php template engine
 * 
 * It is written in php which allow template designer to develop template
 * in native html mixin with H20 template syntax.
 * 
 * H2O supports
 * 		-	lightweight implementation
 * 		-	template Compile into native php
 * 		-	flexible
 *
 * auther@ <Taylor luk subjective@gmail.com>
 * 			Armin Ronacher http://lucumr.pocoo.org/trac/repos/ptemplates/
 *
 * credits@
 *
 * 	Armin Ronacher http://lucumr.pocoo.org/trac/repos/ptemplates/
 *		 There are components such as Context, tokenizer, ArgumentParser, partially burrowed from ptemplate
 * 
 *	Inspired by django templates and of course Smarty.
 * 		Django project -http://djangoproject.com
 * 		Smarty - http://php.com/smarty
 * 
 * 
 * DONE: compiled cache, optional setting to turn it off;
 * DONE: loading sub tempalate, partially done, need to test for more dynamic behaviour
 * DONE: cake view adapter
 * TODO: ignore h2o syntax in string
 * DONE: refactor a loose coupled tags, filters system, able to extend 'em on run time
 * DONE: IMPORTANT! foorloop degrade nicely, now it breaks on undefined vars
 * DONE: IMPORTANT! find a way to access cake viewHelper
 * DONE: multiple comment out source code
 * DONE: break dependencies, cached template shoud work standalone, implment a lightweight include file 
 * DONE: run though xdebug profiler squeeze more speed
 **/

// Global Vars
$H2O_DEFAULT_FILTERS = array();
$H2O_DETAULT_TAGS = array();
$H2O_DEFAULT_PLUGINS = array();
$H2O_CONTEXT_LOOKUP = array();
$H2O_RUNTIME;

// Constants
if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}
define('H2O_ROOT', dirname(__FILE__).DS);
define('H2O_LIBPATH', H2O_ROOT.'lib'.DS);
define('H2O_VERSION', '0.2 dev');
define('H2O_NAME', 'H2O Template - version : '.H2O_VERSION);
define('PHP_TAG', "<?php %s ?>");
define('PHP_PRINT', " echo %s ");
define('PHP_IF', "if ( %s ) : ");
define('PHP_IFELSE', "elseif ( %s ) :");
define('PHP_ELSE', "else:");
define('PHP_ENDIF', "endif");
define('PHP_FORLOOP', "foreach ( %s  ) :");
define('PHP_ENDFORLOOP', "endforeach");
define('CURRENT_TIME', isset($_SERVER) ? $_SERVER['REQUEST_TIME'] : time());

// Load Library
require H2O_LIBPATH.'core.php';
require H2O_LIBPATH.'filters.php';
require H2O_LIBPATH.'tags.php';

class H2O {
	var $options;
	var $source;
	var $filename;
	var $compiled_file;
	var $loader;
	var $context;
	var $tags;
	var $filters;
	var $plugins;
	var $storage;
	
	function getOptions(){
		return array(
		// Enviroment setting
		'BLOCK_START'		=>		'{%',
		'BLOCK_END'			=>		'%}',
		'VARIABLE_START'	=>		'{{',
		'VARIABLE_END'		=>		'}}',
		'COMMENT_START'		=>      '{*',
		'COMMENT_END'		=>      '*}',
		'TRIM_TAGS'			=> 		true,
		
		// Application setting
		'template_path'		=>		H2O_ROOT,
		'compile_path'		=>		H2O_ROOT.DS.'compile',
		'compiled_extension'=>		'.php',
		'cache'				=>		true,
		'output_cache'		=>		false,
		'auto_render'		=>		true,
		'debug'				=>		true,
		'namespace'			=>		array(),
		'bindHelper'		=>		null,
		'safeClass'         =>      array(),
		// Caching setting
		'expires'			=>		5,					// 	0 never expire, or how many seconds compile file lives
		'cache_expires'		=>		30,
		'force_check'		=>		true,
		);
	}

	function __construct($filename, $options = array()) {
		global $H2O_DEFAULT_FILTERS, $H2O_DETAULT_TAGS, $H2O_DEFAULT_PLUGINS, $H2O_RUNTIME;
		
		/*	Setup options	*/
		if (!empty($options))
		$this->options = array_merge($this->getOptions(), $options);;
		$this->filename = $filename;
		
		$this->tags = &$H2O_DETAULT_TAGS;
		$this->filters = &$H2O_DEFAULT_FILTERS;
		$this->plugins = &$H2O_DEFAULT_PLUGINS;
		$this->storage = array();
		$H2O_RUNTIME = $this;
		
		define('TEMPLATE_PATH', $this->options['template_path']);
		
		/*	Disable output cache when cache = off*/
		if (isset($options['cache']) && !$options['cache']) {
			$this->options['output_cache'] = false;
		}
	}

	function set($data, $value = null){
		if (is_string($data)) {
			$data = array($data=>$value);
		}
		foreach($data as $name => $value) {
			$this->options['namespace'][$name] = $value;
		}
	}
	
	function setContext($context = array()){
		if (!empty($context)) {
			$this->options['namespace'] = $context;
		}
	}
	
	function evaluate($context = array()) {
		$namespace =& $this->options['namespace'];
		
		/* Merge context set before evauation and existing context*/
		if (!empty($context)) {
			$namespace = array_merge($namespace, $context);
		}
		
		/*	Setup template file loader	*/
		$this->loader = new H2o_File($this->filename, $this);

		/*	If found cache */
		if ($output = $this->loader->tryLoad()) {
			return $this->render($output);
		}
		
		/*	Setup context for object */
		if (!isset($this->context)) {	
			require H2O_ROOT.'lib/context.php';
			$this->loader->context = $this->context = new H2o_Context($namespace);
            $this->context->safeClass = $this->options['safeClass'];
			if (isset($this->helpers)) {
				$this->context->push($this->helpers);
			}
		}
		
		//	Compile template into native PHP
		$output = $this->compile();
		
		//	Save the compile result to cache
		$this->loader->save($output);
		
		return $this->render();
	}
	
	function loadSubTemplate($filename){
		if (!isset($this->storage['includes']))
			$this->storage['includes'] = array();
        
		if (!file_exists($filename))
	       $filename = realpath(TEMPLATE_PATH.DS.$filename);
			
		if (!isset($this->storage['includes'][$filename])) {
			$this->storage['includes'][$filename] = 1;
		}
		return $this->loader->read($filename);
	}
	
	function compile () {
		require H2O_LIBPATH.'datatype.php';
		require H2O_LIBPATH.'nodes.php';
		require H2O_LIBPATH.'parser.php';
		require H2O_LIBPATH.'utils.php';
		require H2O_LIBPATH.'exceptions.php';
		
		/*	Read	*/
		$this->source = $this->loader->read($this->filename);
		$this->filename = $this->loader->filename;
		
		/*	Parse	*/
		$this->parser = new H2o_Parser($this->source, $this, $this->filename);
		if (isset($this->storage['helpers'])){
			$this->parser->storage['helpers'] =& $this->storage['helpers'];
			unset($this->storage['helpers']);
		}
		$nodeList = $this->parser->parse();
		
		
		/*	Compile	*/
		$result = new H2O_StreamWriter;
		$result->writeSource("//<!--h2o|".addslashes(serialize($this->storage))."|h2o-->
								/*\n\tH2O compiled php code\n\t\$Compiled :".@date('l dS \of F Y h:i:s A').
								"\n\t\$file: ".$this->filename."\n*/\n\n\$storage = array();\n");
		$nodeList->compile($this->context, $result);
		return $result->close();
	}
	
	function render($mode = ''){
		if (!$this->options['auto_render'])
				return true;
		if ($mode === 'cache') {
			$output = $this->loader->loadCache();
		} else {
			$output = $this->loader->loadCompiled();
			if ($this->options['output_cache']) {
				$this->loader->saveCache($output);
			}		
		}
		echo $output;
		return $output ;
	}

	function createTag($tagName, $args, &$parser, $pos) {
		if (!isset($this->tags[$tagName])) {
			throw new H2o_TemplateSyntaxError('<ol><li>Unknow tag	"'. $tagName. '", or</li> 
											<li>Possibly a unclosed tag, parser is searching for "'.$parser->searching.'"</li></ol>',
											$parser->filename,
											$pos,
											$parser->searching);
		}
		return  new $this->tags[$tagName]($args, $parser, $pos);
	}
	
	/* Static methods */
	static function load_plugin($name) {
		global $H2O_DEFAULT_PLUGINS;
    	include (H2O_ROOT.DS."plugins".DS.$name.DS."init.php");
		$H2O_DEFAULT_PLUGINS[$name] = H2O_ROOT.DS."plugins".DS.$name.DS;
	}

	static function add_lookup($lookup, $callback = null){
		global $H2O_CONTEXT_LOOKUP;
		
		if (is_null($callback)) {
			$callback = $lookup;
		}
		if (!function_exists($callback)){
			return false;
		}
		$H2O_CONTEXT_LOOKUP[$lookup] = $callback;
	}
	
	static function add_filter($filter, $callback = null){
		if (is_array($filter)) {
			$filters = $filter;
			    foreach($filters as $key => $filter) {
	            if (is_numeric($key)){
	                self::add_filter($filter);
	            } else {
	                self::add_filter($key, $filter);
	            }
            }
			return true;
		}

		global $H2O_DEFAULT_FILTERS;
		
		if (is_null($callback)) {
			$callback = $filter;
		}
		if (!function_exists($callback)){
			return false;
		}
		$H2O_DEFAULT_FILTERS[$filter] = $callback;	
	}
	
	// compatible 
	static function add_filters($filters){
        self::add_filter($filters);
	}
	
	static function add_tag($tag, $className = null){
		if (is_array($tag)) {
			$tags = $tag;
			foreach($tags as $key => $tag) {
	            if (is_numeric($key)){
	                self::add_tag($tag);
	            } else {
	                self::add_tag($key, $tag);
	            }
	        }
	        return true;
		}

		global $H2O_DETAULT_TAGS;
		if (is_null($className)) {
			$className = $tag;
		}
		$H2O_DETAULT_TAGS[$tag] = $className;		
	}
}

if (!function_exists('debug')){
	function debug(){ 
		foreach(func_get_args() as $obj ){ 
			echo"<pre>"; print_r($obj); echo "</pre>"; 
		} 
	}
}

?>