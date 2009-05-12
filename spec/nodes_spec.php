<?php
require_once 'spec_helper.php';

class Describe_text_node extends SimpleSpec {
    function should_display_text_content() {
        $text = '<h1>someone is nice</h1>';
        expects(h2o($text)->render())->should_be($text);
    }
}

class Describe_comment_node extends SimpleSpec {
    function should_not_display_comments () {
        $person = 'taylor luk';
        expects(h2o('{* comment *}')->render())->should_be('');
        
        $rs = h2o('{{ person }}{* print out person *}')->render(compact('person'));
        expects($rs)->should_be($person);
    }
}

class Describe_variable_node extends SimpleSpec {

    function should_render_name_in_a_h2o_context() {
        $abc = 'oh my god';
        $result = h2o('{{ abc }}')->render(compact('abc'));
        expects($result)->should_be('oh my god');
    }

    function should_apply_filter_if_available() {
        $name = 'taylor luk';
        $result = h2o('{{ name|capitalize }}')->render(compact('name'));
        expects($result)->should_be('Taylor Luk');
    }
}
?>