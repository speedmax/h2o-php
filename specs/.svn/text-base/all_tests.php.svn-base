<?php
require 'spec_helper.php';

class AllTests extends TestSuite {
    function AllTests() {
        $this->TestSuite('All tests');
        
        $tests = array_merge(
            glob(dirname(__FILE__).'/*_test.php'),
            glob(dirname(__FILE__).'/*_spec.php')
            // glob(dirname(__FILE__).'/loader_spec.php')
        );

        foreach ($tests as $test) {
            $this->addFile($test);
        }
    }
}
?>