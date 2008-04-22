<?php
include(H2O_ROOT.'plugins/sample_data/spyc.php');


function sample_data ($file){
	if (!file_exists($file)){
		die ('File does not exist : please make sure you have right path to the sample data');
	}
	
	
	return Spyc::YAMLLoad($file);
	
}
?>