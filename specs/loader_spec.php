<?php
require_once 'spec_helper.php';

class Describe_file_loader extends SimpleSpec {
    function prepare() {
    }
    
    function should_be_able_to_read_template() {}
    
    function should_load_subtemplate_upon_extends_tag() {}
    
    function should_load_subtemplate_upon_include_tag() {}
    
    function shouble_cache_main_template() {}
    
    function should_invalidate_cache_if_any_subtemplates_has_updated() {
    }
}

class Describe_hash_loader extends SimpleSpec {
    function prepare() {
        $this->h2o = new H2o('layout.html', array('loader'=>hash_loader(array(
            'layout.html' => 
                "{% block body %}layout text{% endblock %} {% include '_menu.html' %}",
            'index.html' => 
                "{% extends 'layout.html' %} {% block body %} {{ block.depth }} {{ block.super }} - index text  {% endblock %} ",
            '_menu.html' => 
                "<div id='menu'>page menu</div>",
        ))));
    }

    function should_read_files_to_loader() {
        expects($this->h2o->loader->read('layout.html'))->should_be_a('Nodelist');
    }

    function should_read_sub_template_in_extends_tag() {
        $this->h2o->loadTemplate('index.html');
        expects($this->h2o->render())->should_match('/layout text - index text/');
    }
    
    function should_read_sub_template_in_include_tag() {
        $this->h2o->loadTemplate('index.html');
        expects($this->h2o->render())->should_match('/page menu/');
    }
}
?>