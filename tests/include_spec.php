<?php
require_once 'spec_helper.php';

class Describe_include_tag extends SimpleSpec {
    private $_options = array();

    function prepare() {
        $this->_options = array(
            'searchpath' => dirname(__FILE__).'/templates/include'
        );
    }

    function should_include_SubTemplate() {
        define('D', true);
        $h2o = new h2o('_header.html', $this->_options);
        $result = $h2o->render();
        expects($result)->should_match('/page menu/');
    }
    
    function should_be_able_to_include_in_nested_fashion() {
        $h2o = new h2o('page.html', $this->_options);
        $result = $h2o->render();
        expects($result)->should_match('/layout text/');
        expects($result)->should_match('/Page footer/');
        expects($result)->should_match('/page menu/');
    }
}
