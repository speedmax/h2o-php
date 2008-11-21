<?php
class Describe_Template extends SimpleSpec {

    function should_be_able_to_create_from_string() {
        $result = h2o("<h1>{{ a }}</h1>")->render(array('a'=>'hello world'));
        expects($result)->should_be("<h1>hello world</h1>");
    }
}

# x tag for testing
class Sample_Tag extends H2o_Node {}

class Describe_h2o_tag_registration extends SimpleSpec {
    function should_be_pre_registered_with_default_tags() {
        foreach (w('Block_Tag,Extends_Tag,Include_Tag,If_Tag,For_Tag,With_Tag') as $tag) {
            expects(h2o::$tags)->should_contain($tag);
        }
    }

    function should_be_able_to_register_a_new_tag() {
        h2o::addTag('sample', 'Sample_Tag');        
        expects(h2o::$tags)->should_contain('Sample_Tag');
        unset(h2o::$tags['sample']);

        h2o::addTag('sample');
        expects(h2o::$tags)->should_contain('Sample_Tag');
        unset(h2o::$tags['sample']);
    }
    
    function should_be_able_to_register_tag_using_alias() {        
        h2o::addTag(array(
            'sample'=>'Sample_Tag', 
            's'=>'Sample_Tag'
        ));
        expects(h2o::$tags)->should_contain('Sample_Tag');
        expects(isset(h2o::$tags['s']))->should_be_true();
        unset(h2o::$tags['sample'], h2o::$tags['s']);
        
        h2o::addTag(array(
            'sample', 
            's'=>'Sample_Tag'
        ));
        expects(h2o::$tags)->should_contain('Sample_Tag');
        expects(isset(h2o::$tags['s']))->should_be_true();
        unset(h2o::$tags['sample'], h2o::$tags['s']);
    }
}

class Describe_h2o_filter_registration extends SimpleSpec {
    function should_pre_register_default_filters() {
        $filters = array_keys(h2o::$filters);
        
        # Safe Native php functions as filter
        expects($filters)->should_contain(
            w('md5, sha1, join, wordwrap, trim, upper, lower')
        );
        
        # All core filters
        expects($filters)->should_contain(get_class_methods('CoreFilters'));
        
        # All Html Filters
        expects($filters)->should_contain(get_class_methods('HtmlFilters'));
        
        # All StringFilters
        expects($filters)->should_contain(get_class_methods('StringFilters'));
        
        # All NumberFilters
        expects($filters)->should_contain(get_class_methods('NumberFilters'));
        
        # All DatetimeFilters
        expects($filters)->should_contain(get_class_methods('DatetimeFilters'));
    }
    
    function should_be_able_to_register_a_filter() {   
        h2o::addFilter('sample_filter');
        $result= h2o('{{ something | sample_filter }}')->render();
        expects($result)->should_be('i am a filter');
    }

    function should_be_able_to_register_a_filter_collection() {
        h2o::addFilter('SampleFilters');

        $result = h2o('{{ person | hello }}')->render(array('person'=>'peter'));
        expects($result)->should_be('says hello to peter');
    }
}

function sample_filter($value) { 
    return "i am a filter";
}

class SampleFilters extends FilterCollection {
    function hello($value) {
        return "says hello to {$value}";
    }
}

function w($str) {
    return array_map('trim', explode(',', $str));
}

?>