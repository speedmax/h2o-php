<?php
require_once 'spec_helper.php';

class Describe_variable_node extends SimpleSpec {

    function should_render_name_in_a_h2o_context() {
        $abc = 'oh my god';
        $result = h2o::parseString('{{ abc }}')->render(compact('abc'));
        expects($result)->should_be('oh my god');
    }

    function should_apply_filter_if_available() {
        $name = 'taylor luk';
        $result = h2o::parseString('{{ name|capitalize }}')->render(compact('name'));
        expects($result)->should_be('Taylor Luk');
    }
}
?>