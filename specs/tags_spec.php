<?php
require_once 'spec_helper.php';

class Describe_if_tag extends SimpleSpec {
    
    function should_evaluate_boolean_expression() {
        $results = h2o('{% if 4 > 3 %}yes{% endif %}')->render();
        expects($results)->should_be('yes');
    }
}


class Describe_for_tag extends SimpleSpec {
    function should_iterate_over_an_array_of_objects() {
        $result = h2o('{% for e in items %}{{ e }}{% endfor %}')->render(
            array('items'=> array(1,2,3,4,5))
        );
        expects($result)->should_be('12345');    
    }
    
    function should_reverse_array_when_reversed_keyword_supplied() {
        $result = h2o('{% for e in items reversed %}{{ e }}{% endfor %}')->render(array(
            'items'=> array(1,2,3,4,5)
        ));
        expects($result)->should_be('54321');
    }
    
    function should_only_iterable_over_limit_of_time_when_limit_keyword_supplied() {
        $result = h2o('{% for e in items limit:3 %}{{ e }}{% endfor %}')->render(array(
            'items'=> array(1,2,3,4,5)
        ));
        expects($result)->should_be('123');
        
        $result = h2o('{% for e in items limit:3 reversed %}{{ e }}{% endfor %}')->render(array(
            'items'=> array(1,2,3,4,5)
        ));
        expects($result)->should_be('543');
    }
    
    function should_provide_variable_loop_in_for_block() {
        $context =  array('items'=> array(1,2,3,4,5));
        
        $rs= h2o('{% for e in items %}{{ loop.counter }}{%endfor%}')->render($context);
        expects($rs)->should_be('12345');
        
        $rs = h2o('{% for e in items %}{{ loop.counter0 }}{%endfor%}')->render($context);
        expects($rs)->should_be('01234');
        
        $rs = h2o('{% for e in items %}{{ loop.revcounter }}{%endfor%}')->render($context);
        expects($rs)->should_be('54321');
        
        $rs = h2o('{% for e in items %}{{ loop.revcounter0 }}{%endfor%}')->render($context);
        expects($rs)->should_be('43210');
        
        $rs = h2o('{% for e in items %}{% if loop.first %}first{% else %}{{ e }}{% endif %}{%endfor%}')->render($context);
        expects($rs)->should_be('first2345');
        
        $rs = h2o('{% for e in items %}{% if loop.last %}last{% else %}{{ e }}{% endif %}{%endfor%}')->render($context);
        expects($rs)->should_be('1234last');
        
        $rs = h2o('{% for e in items %}{% if loop.even%}even{% else %}{{ e }}{% endif %}{%endfor%}')->render($context);
        expects($rs)->should_be('1even3even5');
        
        $rs = h2o('{% for e in items %}{% if loop.odd%}odd{% else %}{{ e }}{% endif %}{%endfor%}')->render($context);
        expects($rs)->should_be('odd2odd4odd');
    }
}
?>