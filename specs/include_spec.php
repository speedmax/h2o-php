<?php
require_once 'spec_helper.php';

class Describe_include_tag extends SimpleSpec {
    function prepare() {
        $this->option = array('loader' => hash_loader(array(
            'page.html' => 
                "{% include '_header.html' %}{% block body %}layout text{% endblock %}{% include '_footer.html' %} ",
            '_menu.html' => 
                "<div id='menu'>page menu</div>",
            '_header.html' => 
                '<div id="header">{% include "_menu.html" %}</div>',
            '_footer.html' =>
                '<div id="footer">Page footer</div>'
        )));
    }
    
    function should_include_SubTemplate() {
        $h2o = new H2o('_header.html', $this->option);
        $result = $h2o->render();
        expects($result)->should_match('/page menu/');
    }
    
    function should_be_able_to_include_in_nested_fashion() {
        $h2o = new H2o('page.html', $this->option);
        $result = $h2o->render();
        expects($result)->should_match('/layout text/');
        expects($result)->should_match('/Page footer/');
        expects($result)->should_match('/page menu/');
    }
}

?>