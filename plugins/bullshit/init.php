  <?php
  
    include_once("nonsense.php");
    
	function bullshit($number = 1, $mode = 'sentence'){
		$nonsense = new NonSense;
		$nonsense->$mode($number);
		return $nonsense ->output;
	}
    
    h2o::add_tag('bullshit', 'Nonsense_Tag'); 
    class Nonsense_Tag extends TagNode {
      var $mode = "word";
      var $number = 10;
      
      function __construct ($argstring, &$parser, $position) {
        $args = H2o_Util::parseArguments($argstring, $position);
        $argc = count($args);

        if ($argc == 2) {
          $this->number = $args[0];
          $this->mode == $args[1];
        } elseif ($argc == 1) {
          $this->number = $args[0];
        }
      }
      
      function compile (&$context, &$stream) {
        $nonsense = new Nonsense;
        if ($this->mode == 'word') 
          $nonsense->word($this->number);
        else
          $nonsense->sentence($this->number);
        $stream->write($nonsense->output);
      
      }
    }
    
   ?>