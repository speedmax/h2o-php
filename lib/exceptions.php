<?php
if(!class_exists('Exception')){
	class Exception {
		var $_message = '';
		var $_code = 0;
		var $_line = 0;
		var $_file = '';
		var $_trace = null;

		function __construct($message = 'Unknown exception', $code = 0){
			$this->_message = $message;
			$this->_code = $code;
			$this->_trace = debug_backtrace();
			$x = array_shift($this->_trace);
			$this->_file = $x['file'];
			$this->_line = $x['line'];
		}
		function getMessage(){
			return $this->_message;
		}
		function getCode(){
			return $this->_code;
		}
		function getFile(){
			return $this->_file;
		}
		function getLine(){
			return $this->_line;
		}
		function getTrace(){
			return $this->_trace;
		}
		function getTraceAsString(){
			$s = '';
			foreach($this->_trace as $i=>$item){
				foreach($item['args'] as $j=>$arg)
					$item['args'][$j] = print_r($arg, true);
				$s .= "#$i " . (isset($item['class']) ? $item['class'] . $item['type'] : '') . $item['function']
				. '(' . implode(', ', $item['args']) . ") at [$item[file]:$item[line]]\n";
			}
			return $s;
		}
		function printStackTace(){
			echo $this->getTraceAsString();
		}
		function toString(){
			return $this->getMessage();
		}
		function __toString(){
			return $this->toString();
		}
	}
}


class H2o_Error extends Exception {
	var $source;
	var $position;
	var $message;
	var $name = 'H2o Error';
	function __construct($message, $filename, $position = 0, $highlight = null){
		global $H2O_RUNTIME;
		
		$this->env = $H2O_RUNTIME;
		$this->filename = $filename;
		$this->position = $position;
		$this->message = $message;
		$this->highlight = $highlight;
		$this->showError();
	}
	
	function getSourceLine(){
		 return preg_match_all("/\r?\n/", substr($this->source, 0, $this->position), $matches) + 1;
	}
	
	function showError(){

		if (!file_exists($this->filename)) {
			$this->filename = TEMPLATE_PATH.DS.$this->filename;
		}
		
		$this->source = file_get_contents($this->filename);
		$title = "$this->name";
		$source_code ='';
		
		$description = $this->message;
		if (!is_null($this->filename)) {
			$description .= ' in file '.$this->filename;
			$filename = $this->filename;
		}
		
		if ($this->position) {
			$pos = $this->position -200;
			if ($pos < 0) $pos = 0; 
			
			$around = "  ..... \n".htmlentities(substr($this->source, $pos, 500))."\n  .....";
			$around = highlight_h2o($around , $this->getSourceLine()-1);
			
			if ($this->highlight != null) {
				$around = str_replace($this->highlight, "<b style='color:pink'>$this->highlight</b>", $around);
			}

			$description .= ', around line '.$this->getSourceLine().'.';
		}
		
		$source_code = highlight_h2o(htmlentities($this->source), 1);
		
		include(H2O_LIBPATH.'template/error.tpl');
	}

}



class H2o_TemplateSyntaxError extends H2o_Error {
	var $name = 'H2o Template syntax error';
	
}

class H2o_FilterNotFound extends H2o_Error {
	var $name = 'H2o Filter not found';
	
}

class H2o_TagNotFound extends H2o_Error {
	var $name = 'H2o tag not found';
	
}

class H2o_TemplateNotFound extends H2o_Error {
	var $name = 'H2o template not found';
	
}
?>