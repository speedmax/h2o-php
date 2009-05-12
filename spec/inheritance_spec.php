<?php
require_once 'spec_helper.php';

$GLOBALS['h2o_option'] = array('loader'=> hash_loader(array(
    'page.html' => "{% block body %}page body{% endblock %}",
    'layout.html' => "{% block header %}header{% endblock %}".
                    "{% block body %},body{% endblock %}".
                    "{% block footer %},footer{% endblock %}",
    'inherited.html' => "{% extends 'layout.html' %}".
                        "{% block body %},extended body{% endblock %}",
    'nested' => "{% block a %}h2o ".
                    "{% block b %}template ".
                        "{% block c %}rocks ".
                            "{% block d %}so hard !{% endblock %}".
                        "{% endblock %}".
                    "{% endblock %}".
                "{% endblock %}",
    'nested_inherit' => "{% extends 'nested' %}".
                        "{% block d %}{{ block.super }} and plays so hard{% endblock %}",
)));

class Describe_H2o_block_tag extends SimpleSpec {
    function prepare() {
        $this->option = $GLOBALS['h2o_option'];
    }

    function should_render_template_content() {
        $h2o = new H2o('page.html', $this->option);
        expects($h2o->render())->should_be('page body');

        $h2o->loadTemplate('layout.html');
        expects($h2o->render())->should_be('header,body,footer');
        
        $h2o->loadTemplate('nested');
        expects($h2o->render())->should_be('h2o template rocks so hard !');
    }
}

class Describe_h2o_extends_tag extends SimpleSpec {
    function prepare() {
        $this->option = $GLOBALS['h2o_option'];
    }
    function should_render_inherited_template() {
        $h2o = new H2o('inherited.html', $this->option);
        expects($h2o->render())->should_be("header,extended body,footer");
        
        # extend nested blocks
        $h2o->loadTemplate('nested_inherit');
        expects($h2o->render())->should_be('h2o template rocks so hard ! and plays so hard');
    }
}

class Describe_h2o_block_variable extends SimpleSpec {
    function prepare() {
        $this->option = array('loader' => hash_loader(array(
            'page' => "{% block body %}{{ block.name }}{% endblock %}",
            'blog' => "{% block body %}depth: {{ block.depth }}{% endblock %}",
            'layout' => "{% block body %}depth: {{ block.depth }}- parent content{% endblock %}",
            'home' => "{% extends 'layout' %} {% block body %}{{ block.super}}, depth: {{ block.depth }}- child content{% endblock %}",
        )));
    }
    function should_output_name_of_block() {
        $h2o = new h2o('page', $this->option);
        expects($h2o->render())->should_be('body');
        
        $h2o->loadTemplate('blog');
        expects($h2o->render())->should_be('depth: 1');
        
        $h2o->loadTemplate('home');
        expects($h2o->render())->should_be('depth: 2- parent content, depth: 1- child content');
    }
    
    function should_display_correct_block_depth_level() {
        $h2o = new h2o('blog', $this->option);
        expects($h2o->render())->should_be('depth: 1');
        
        $h2o->loadTemplate('home');
        expects($h2o->render())->should_match('/depth: 2/');
    }
    
    function should_be_able_to_output_parent_template_using_blog_super() {
        $h2o = new h2o('home', $this->option);
        expects($h2o->render())->should_be(
            'depth: 2- parent content, depth: 1- child content'
        );
    }
}
?>
