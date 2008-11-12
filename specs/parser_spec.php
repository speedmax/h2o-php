<?php
require_once 'spec_helper.php';

class Describe_Parser_Patterns extends SimpleSpec {

    function should_match_basic_operators () {
        $this->expect(' ')->should_match(H2O_RE::$whitespace);
        $this->expect('         ')->should_match(H2O_RE::$whitespace);
    }

    function should_match_numeric_values() {
        $this->expect('0.555')->should_match(H2O_RE::$number);
        $this->expect('2000')->should_match(H2O_RE::$number);
    }
    
    function should_match_string() {
        # Double quote
        $this->expect('"this is a string"')->should_match(H2O_RE::$string);
        $this->expect('"She has \"The thing\""')->should_match(H2O_RE::$string);
        
        # Single Quote
        $this->expect("'the doctor is good'")->should_match(H2O_RE::$string);
        $this->expect("'She can\'t do it'")->should_match(H2O_RE::$string);
    }
    
    function shuold_match_i18n_string() {
        $this->expect("_('hello world')")->should_match(H2O_RE::$i18n_string);
    }
    
    function should_match_variable_name() {
        $this->expect('something')->should_match(H2O_RE::$name);
        $this->expect('somet-hing')->should_match(H2O_RE::$name);
        $this->expect('something.something')->should_match(H2O_RE::$name);
    }
    
    function should_match_named_arguments() {
        $this->expect("name: 'peter'")->should_match(H2O_RE::$named_args);
        $this->expect("name: variable")->should_match(H2O_RE::$named_args);
        $this->expect("price: 0.55 ")->should_match(H2O_RE::$named_args);
        $this->expect("age: 199 ")->should_match(H2O_RE::$named_args);
        $this->expect("alt: _('image alt tag')")->should_match(H2O_RE::$named_args);
    }
    
    function should_match_operators() {
        $operators = array('==', '>', '<', '>=', '<=', '!=');
        $logics = array('!','not', 'and', 'or');
        foreach($operators+$logics as $op) {
            $this->expect($op)->should_match(H2O_RE::$operator);
        }
    }
}

class Describe_Argument_Lexer extends SimpleSpec {
    
    function should_parse_named_arguments() {
        $result = $this->parse("something | filter 11, name: 'something', age: 18");
        $expected = array(
            ':something', array(':filter', 11, array('name' => "'something'", 'age' => 18))
        );
        $this->expect($result)->should_be($expected);
    }
    
    private function parse($string) {
        return H2o_Parser::parseArguments($string);
    }
}

class Describe_Lexer extends SimpleSpec {}

class Describe_Parser extends SimpleSpec {}
?>