<?php
class H2o_Utils{
	static function is_string($source){
		return self::getType($source) == 'string';
	}
	
	static function is_numeric($source) {
		return self::getType($source) == 'number';
	}
	
	static function is_name($source) {
		return self::getType($source) == 'name';
	}
	
	static function getType($source){
		return H2o_ArgumentParser::type($source);
	}
	
	static function parseArguments($source = null, $fpos){
		$parser = new H2o_ArgumentParser($source, $fpos);
        $result = array();
        $current_buffer = &$result;
        $filter_buffer = array();

        foreach ($parser->parse() as $token) {
            list($token, $data) = $token;
            if ($token == 'filter_start') {
                $filter_buffer = array();
                $current_buffer = &$filter_buffer;
            }
            elseif ($token == 'filter_end') {
                if (count($filter_buffer))
                    array_push($result, $filter_buffer);
                $current_buffer = &$result;
            }
            elseif ($token == 'name' || $token == 'number' ||
                    $token == 'string') {
                array_push($current_buffer, $data);
            }
            elseif ($token == 'named_argument') {
            	list($name,$value) = preg_split('/:/',$data,2);
                $current_buffer[trim($name)] = trim($value);                
            }elseif( $token == 'operator') {
            	  array_push($current_buffer, array('operator'=>$data));
            }
        }
        return $result;
	}
	
	static function makeArray($object) {
        if (is_array($object))
            return array_values($object);
        elseif (is_object($object)) {
            $result = array();
            foreach ($object as $value) {
                array_push($result, $value);
            }
            return $result;
        }
        return array();
    }
    
	static function applyFilters($data, $filters){
		global $H2O_DEFAULT_FILTERS;

		foreach ($filters as $filter){
			$filtername = array_shift($filter);
			array_unshift($filter, $data);
			$args = implode(', ',$filter);
			
			if (isset($H2O_DEFAULT_FILTERS[$filtername])) {
				$filter = $H2O_DEFAULT_FILTERS[$filtername];
			} else
				$filter = false;

			if ($filter)
			$data = $filter."(".$args.")";
		}
		return $data;
	}		
}
?>