<?php
require_once 'spec_helper.php';

class Describe_file_loader extends SimpleSpec {

    function should_be_able_to_read_template() {
        chdir(dirname(__FILE__));
        $h2o = new h2o(array('searchpath' => 'templates'));
        
        expects($h2o->parseToNodes('a.html'))->should_be_a('h2o_NodeStack');
        expects($h2o->parseToNodes('b.html'))->should_be_a('h2o_NodeStack');
        expects($h2o->parseToNodes('emails/base.html'))->should_be_a('h2o_NodeStack');
    }
    
    function should_be_able_to_load_template_lazily() {
        $h2o = new h2o('a.html', array('searchpath' => 'templates'));
        expects($h2o->render())->should_not_be_empty();
        
        $h2o = new h2o(null, array('searchpath' => 'templates'));
        $h2o->loadTemplate('b.html');
        expects($h2o->render())->should_not_be_empty();
    }
    
    function should_read_from_alternitive_working_path() {
        $h2o = h2o('emails/base.html', array(
            'searchpath' => dirname(__FILE__).'/templates'
        ));
        expects($h2o->render())->should_match('/Dear Customer/');
    }
    
    function should_load_subtemplate_upon_extends_tag() {
        $h2o = h2o('emails/campaign1.html', array(
            'searchpath' => dirname(__FILE__).'/templates'
        ));
        expects($h2o->render())->should_match('/Dear Customer/');

        $h2o->loadTemplate('emails/campaign2.html');
        expects($h2o->render())->should_match('/Hello Customer/');
    }
    
    function should_load_subtemplate_upon_include_tag() {
        $h2o = h2o('emails/campaign3.html', array(
            'searchpath' => dirname(__FILE__).'/templates'
        ));
        expects($h2o->render())->should_match('/abcWidgets Logo are registered trademarks/');
    }
    
    function shouble_cache_main_template() {
        $h2o = h2o('templates/a.html', array('cache' => false));
        expects($h2o->cached)->should_be(false);
        
        $h2o = h2o('template/a.html', array('cache'=>true));
        expects($h2o->cached)->should_be(true);
    }
    
    function should_invalidate_cache_if_any_subtemplates_has_updated() {
        $opt = array('searchpath' => dirname(__FILE__).'/templates');
        
        # Load template twice to make sure its cached
        $h2o = h2o('emails/campaign1.html', $opt);
        $h2o->loadTemplate('emails/campaign1.html');
        expects($h2o->isCached())->should_be(true);
        
        # Touch parent template
        sleep(1);
        touch(dirname(__FILE__).'/templates/emails/base.html');

        $h2o->loadTemplate('emails/campaign1.html');
        expects($h2o->Loader->cached)->should_be(false);
        $h2o->Loader->flush_cache();
    }
}

class Describe_hash_loader extends SimpleSpec {
    function prepare() {
        $this->h2o = new h2o(array('loader' => new h2o_Loader_Hash(array(
            'layout.html' => 
                "{% block body %}layout text{% endblock %} {% include '_menu.html' %}",
            'index.html' => 
                "{% extends 'layout.html' %} {% block body %} {{ block.depth }} {{ block.super }} - index text  {% endblock %} ",
            '_menu.html' => 
                "<div id='menu'>page menu</div>",
        ))));
    }

    function should_read_files_to_loader() {
    }

    function should_read_sub_template_in_extends_tag() {
        expects($this->h2o->render('index.html'))->should_match('/layout text - index text/');
    }
    
    function should_read_sub_template_in_include_tag() {
        expects($this->h2o->render('index.html'))->should_match('/page menu/');
    }
}
