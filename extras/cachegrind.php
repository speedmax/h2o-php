<?php
/**
 * Cachegrind parser for Performance Testing.
 *
 * @package WordPress_Plugin
 * @subpackage Performance_Testing
 * @version $Id: cachegrind.php 149 2008-07-10 05:19:38Z dragonwing@dragonu.net $
 * @since 0.3
 * @author Jacob Santos <plugin-dev@santosj.name>
 * @license New BSD
 */

$c=  new Cachegrind_File_Parser('/workspace/tmp/\'cachegrind.out\'');

echo $c->run();


/**
 * File Parser class, which is called to parse the file into tokens to be
 * process later.
 *
 * @since 0.3
 */
class Cachegrind_File_Parser
{
	private $filename = '';
	private $fileID = 0;

	public function __construct($file)
	{
		$this->filename = $file;
	}
	
	private function getMatch($line)
	{
		if( false !== ($token = Cachegrind_Token_Version::isMatch($line)) )
		{
			return new Cachegrind_Token_Version($token);
		}
		else if( false !== ($token = Cachegrind_Token_Cmd::isMatch($line)) )
		{
			return new Cachegrind_Token_Cmd($token);
		}
		else if( false !== ($token = Cachegrind_Token_Part::isMatch($line)) )
		{
			return new Cachegrind_Token_Part($token);
		}
		else if( false !== ($token = Cachegrind_Token_Events::isMatch($line)) )
		{
			return new Cachegrind_Token_Events($token);
		}
		else if( false !== ($token = Cachegrind_Token_FunctionLocation::isMatch($line)) )
		{
			return new Cachegrind_Token_FunctionLocation($token);
		}
		else if( false !== ($token = Cachegrind_Token_FunctionName::isMatch($line)) )
		{
			return new Cachegrind_Token_FunctionName($token);
		}
		else if( false !== ($token = Cachegrind_Token_Costs::isMatch($line)) )
		{
			return new Cachegrind_Token_Costs($token);
		}
		else if( false !== ($token = Cachegrind_Token_CallsFunctionName::isMatch($line)) )
		{
			return new Cachegrind_Token_CallsFunctionName($token);
		}
		else if( false !== ($token = Cachegrind_Token_CallsFunctionAmount::isMatch($line)) )
		{
			return new Cachegrind_Token_CallsFunctionAmount($token);
		}
		else
		{
			return new Cachegrind_Token_Unknown($line);
		}
	}

	public function run()
	{
		$this->lexer();
	}

	/**
	 * First pass with lexer.
	 *
	 * We can simplify the processing by doubly passing the tokens and doing
	 * some of the processing now and allowing the process to just do a few
	 * minor task if needed and just insert into the table.
	 *
	 * @access private
	 * @param array $tokens Passed by Reference token array to fill.
	 */
	private function lexer()
	{
		$temp = array();

		$handle = fopen($this->filename, 'r');

		$content = '';
		while( feof($handle) !== true )
		{
			$content .= fread($handle, 1048576);

			$save = true;
			if( substr($content, -1, 1) == "\n" )
			{
				$save = false;
			}

			$lines = explode("\n", $content);

			$last = count($lines)-1;

			foreach($lines as $i => $line)
			{
				$line = trim($line);
	
				if( empty($line) )
				{
					$this->parser($temp);
					$temp = array();
					continue;
				}

				if( $i != $last)
				{
					$temp[] = $this->getMatch($line);
				}
			}

			if( $save === false )
			{
				$content = '';
			}

		}

		if( !empty($content) ) {
			$temp[] = $this->getMatch($content);
			$this->parser($temp);
		}
		fclose($handle);
		unlink($this->filename);
	}

	private function parser(array &$tokens)
	{
		global $wpdb;

		if( $tokens[0] instanceof Cachegrind_Token_Version )
		{
			$this->fileID = $wpdb->query($wpdb->prepare("INSERT INTO `pt_file` (`file_version`, `filename`, `part`, `the_date`) VALUES (%s, %s, %u, NOW())",
				$tokens[0]->retrieve(),
				$tokens[1]->retrieve(),
				$tokens[2]->retrieve()));
			return;
		}

		if( ($tokens[0] instanceof Cachegrind_Token_FunctionLocation) )
		{
			if( count($tokens) == 3 )
			{
				$cost = $tokens[2]->retrieve();

				$wpdb->query($wpdb->prepare("INSERT INTO `pt_function` (`file_ID`, `function_location`, `function_name`, `line_number`, `total_time`) VALUES (%u, %s, %s, %u, %u)",
						$this->fileID,
						$tokens[0]->retrieve(),
						$tokens[1]->retrieve(),
						$cost['line'],
						$cost['time']));
				return;
			}
			else if( count($tokens) > 3 )
			{
				$location = array_shift($tokens);
				$name = array_shift($tokens);
				$cost = array_shift($tokens)->retrieve();
				
				$function_ID = 0;
				$function_ID = $wpdb->query($wpdb->prepare("INSERT INTO `pt_function` (`file_ID`, `function_location`, `function_name`, `line_number`, `total_time`) VALUES (%u, %s, %s, %u, %u)",
					$this->fileID,
					$location->retrieve(),
					$name->retrieve(),
					$cost['line'],
					$cost['time']));

				$limit = count($tokens);
				for($i=0; $i < $limit; $i=$i+3)
				{
					if( $tokens[$i] instanceof Cachegrind_Token_CallsFunctionName )
					{
						$amount = $tokens[$i+1]->retrieve();
						$cost = $tokens[$i+2]->retrieve();

						$wpdb->query($wpdb->prepare("INSERT INTO `pt_called_function` 
								(`function_ID`, `function_name`, `called_amount`, `called_time`, `called_line`, `time_overhead`) 
							VALUES 
								(%u, %s, %u, %u, %u, %u)",
							$function_ID,
							$tokens[$i]->retrieve(),
							$amount[0],
							$amount[1],
							$cost['line'],
							$cost['time']));
					}
				}

				return;
			}
		}
	}
}

/**
 * Avoid duplicating a lot of code. Most tokens use most of the same code and
 * is implemented here.
 *
 * @since 0.3
 * @abstract
 * @uses Cachegrind_Token_Interface Implements interface.
 */
abstract class Cachegrind_Token
{
	public $content = null;
	
	public function __construct($content)
	{
		$this->content = $content;
	}

	public function retrieve()
	{
		return $this->content;
	}
	
	abstract static public function isMatch($line);
}

/**
 * Version header token
 *
 * @since 0.3
 */
class Cachegrind_Token_Version extends Cachegrind_Token
{
	static public function isMatch($line)
	{
		if( !preg_match('/^version:\s([.0-9]+)/', $line, $match) )
			return false;
		return $match[1];
	}
}

/**
 * Cmd header token
 *
 * @since 0.3
 */
class Cachegrind_Token_Cmd extends Cachegrind_Token
{
	static public function isMatch($line)
	{
		if( !preg_match('/^cmd:\s(.*)/', $line, $match) )
			return false;
		return $match[1];
	}
}

/**
 * Part header token
 *
 * @since 0.3
 */
class Cachegrind_Token_Part extends Cachegrind_Token
{
	static public function isMatch($line)
	{
		if( !preg_match('/^part:\s(.*)/', $line, $match) )
			return false;
		return $match[1];
	}
}

/**
 * Events header token
 *
 * @since 0.3
 */
class Cachegrind_Token_Events extends Cachegrind_Token
{
	static public function isMatch($line)
	{
		if( !preg_match('/^events:\s(.*)/', trim($line), $match) )
			return false;
		return $match[1];
	}
}

/**
 * Function Location token
 *
 * @since 0.3
 */
class Cachegrind_Token_FunctionLocation extends Cachegrind_Token
{
	static public function isMatch($line)
	{
		if( !preg_match('/^(fl|fi|fe)=\s?(.*)/', trim($line), $match) )
			return false;
		return $match[2];
	}
}

/**
 * Function name token
 *
 * @since 0.3
 */
class Cachegrind_Token_FunctionName extends Cachegrind_Token
{
	static public function isMatch($line)
	{
		if( !preg_match('/^fn=\s?(.*)/', trim($line), $match) )
			return false;
		return $match[1];
	}
}

/**
 * This class sort of fudges the costs line, because the costs line needs to be
 * based off of the events line, so the amount of numbers needs match the amount
 * of events header subjects.
 *
 * I'm assuming however, that we are only going to parse XDebug cachegrind files
 * which will always sort of have the same format. In which case, when the
 * format does change, I will need to update this class. It is worth doing it
 * this way to save development time.
 *
 * @since 0.3
 */
class Cachegrind_Token_Costs extends Cachegrind_Token
{
	static public function isMatch($line)
	{
		if( !preg_match('/^([0-9]+)\s([\.0-9]+)/', trim($line), $match) )
			return false;
		return array('line' => $match[1], 'time' => $match[2]);
	}
}

/**
 * The function name that is called by the function token.
 *
 * @since 0.3
 */
class Cachegrind_Token_CallsFunctionName extends Cachegrind_Token
{
	static public function isMatch($line)
	{
		if( !preg_match('/^cfn=\s?(.*)/', trim($line), $match) )
			return false;
		return $match[1];
	}
}

/**
 * The amount of times the previous function is called and amount of overhead.
 *
 * @since 0.3
 */
class Cachegrind_Token_CallsFunctionAmount extends Cachegrind_Token
{
	static public function isMatch($line)
	{
		if( !preg_match('/^calls=\s?([0-9]+)\s?([0-9]+)?\s?([0-9]+)?\s?/', trim($line), $match) )
			return false;
		array_shift($match);
		return $match;
	}
}

/**
 * All unknown tokens, which are unsupported are defined here. Mostly used for
 * debugging and adding support for missing tokens.
 *
 * @since 0.3
 */
class Cachegrind_Token_Unknown extends Cachegrind_Token
{
	static public function isMatch($line)
	{
		return $line;
	}
}