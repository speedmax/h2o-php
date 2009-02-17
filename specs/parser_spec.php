<?php
require_once 'spec_helper.php';

class Describe_Parser_Patterns extends SimpleSpec {

    function should_match_basic_operators () {
        $a = array('items'=> array(1,2,3));
        
        expects($a)->should_have(3, 'items');
        expects(H2O_RE::$whitespace)->should_match(' ');
        expects(H2O_RE::$whitespace)->should_match('         ');
    }

    function should_match_numeric_values() {
        expects(H2O_RE::$number)->should_match('0.555');
        expects(H2O_RE::$number)->should_match('2000');
    }
    
    function should_match_string() {
        # Double quote
        expects(H2O_RE::$string)->should_match('"this is a string"');
        expects(H2O_RE::$string)->should_match('"She has \"The thing\""');
        
        # Single Quote
        expects(H2O_RE::$string)->should_match("'the doctor is good'");
        expects(H2O_RE::$string)->should_match("'She can\'t do it'");
    }
    
    function shuold_match_i18n_string() {
        expects(H2O_RE::$i18n_string)->should_match("_('hello world')");
    }
    
    function should_match_variable_name() {
        expects(H2O_RE::$name)->should_match('something');
        expects(H2O_RE::$name)->should_match('somet-hing');
        expects('something.something')->should_match(H2O_RE::$name);
    }
    
    function should_match_named_arguments() {
        expects(H2O_RE::$named_args)->should_match("name: 'peter'");
        expects(H2O_RE::$named_args)->should_match("name: variable");
        expects(H2O_RE::$named_args)->should_match("price: 0.55 ");
        expects(H2O_RE::$named_args)->should_match("age: 199 ");
        expects(H2O_RE::$named_args)->should_match("alt: _('image alt tag')");
    }
    
    function should_match_operators() {
        $operators = array('==', '>', '<', '>=', '<=', '!=');
        $logics = array('!','not', 'and', 'or');
        foreach($operators+$logics as $op) {
            expects($op)->should_match(H2O_RE::$operator);
        }
    }
}

class Describe_Argument_Lexer extends SimpleSpec {
    
    function should_parse_named_arguments() {
        $result = $this->parse("something | filter 11, name: 'something', age: 18, var: variable, active: true");
        $expected = array(
            ':something', array(':filter', 11, array('name' => "'something'", 'age' => 18, 'var' => ':variable', 'active'=>'true'))
        );
        expects($result)->should_be($expected);
    }
    
    function should_parse_variable_contains_operators() {
        expects($this->parse("org"))->should_be(array(':org'));
        expects($this->parse("dand"))->should_be(array(':dand'));
        expects($this->parse("xor"))->should_be(array(':xor'));
        expects($this->parse("notd"))->should_be(array(':notd'));
    }
    
    private function parse($string) {
        return H2o_Parser::parseArguments($string);
    }
}

class Describe_Lexer extends SimpleSpec {}

class Describe_Parser extends SimpleSpec {}
?>