<?php
class Describe_Template extends SimpleSpec {

    function should_be_able_to_create_from_string() {
        $result = h2o::parseString("<h1>{{ a }}</h1>")->render(array('a'=>'hello world'));
        $this->expect($result)->should_be("<h1>hello world</h1>");
    }
}

# x tag for testing
class Sample_Tag extends Tag {}

class Describe_h2o_tag_registration extends SimpleSpec {
    function should_be_pre_registered_with_default_tags() {
        foreach (w('Block_Tag,Extends_Tag,Include_Tag,If_Tag,For_Tag,With_Tag') as $tag) {
            $this->expect(h2o::$tags)->should_contain($tag);
        }
    }

    function should_be_able_to_register_a_new_tag() {
        
        h2o::addTag('sample', 'Sample_Tag');        
        $this->expect(h2o::$tags)->should_contain('Sample_Tag');
        unset(h2o::$tags['sample']);


        h2o::addTag('sample');
        $this->expect(h2o::$tags)->should_contain('Sample_Tag');
        unset(h2o::$tags['sample']);
    }
    
    function should_be_able_to_register_tag_using_alias() {        
        h2o::addTag(array(
            'sample'=>'Sample_Tag', 
            's'=>'Sample_Tag'
        ));
        $this->expect(h2o::$tags)->should_contain('Sample_Tag');
        $this->expect(isset(h2o::$tags['s']))->should_be_true();
        unset(h2o::$tags['sample'], h2o::$tags['s']);
        
        h2o::addTag(array(
            'sample', 
            's'=>'Sample_Tag'
        ));
        $this->expect(h2o::$tags)->should_contain('Sample_Tag');
        $this->expect(isset(h2o::$tags['s']))->should_be_true();
        unset(h2o::$tags['sample'], h2o::$tags['s']);
    }

}

function sample_filter($value) { 
    return "i am a filter";
}

class SampleFilters extends FilterCollection {
    function hello($value) {
        return "says hello to : {$value}";
    }
}

class Describe_h2o_filter_registration extends SimpleSpec {

    function should_pre_register_default_filters() {
        $filters = array_keys(h2o::$filters);
        
        # Safe Native php functions as filter
        $this->expect($filters)->should_contain(
            w('md5, sha1, join, wordwrap, trim, upper, lower')
        );
        
        # All core filters
        $this->expect($filters)->should_contain(get_class_methods('CoreFilters'));
        
        # All Html Filters
        $this->expect($filters)->should_contain(get_class_methods('HtmlFilters'));
        
        # All StringFilters
        $this->expect($filters)->should_contain(get_class_methods('StringFilters'));
        
        # All NumberFilters
        $this->expect($filters)->should_contain(get_class_methods('NumberFilters'));
        
        # All DatetimeFilters
        $this->expect($filters)->should_contain(get_class_methods('DatetimeFilters'));
    }
    
    function should_be_able_to_register_a_filter() {   
        h2o::addFilter('sample_filter');
        $result= h2o::parseString('{{ something | sample_filter }}')->render();
        $this->expect($result)->should_be('i am a filter');
    }
    
    function should_be_able_to_register_a_filter_collection() {
        h2o::addFilter('SampleFilters');
        
        echo $result = h2o::parseString('{{ "peter" }}')->render(array('name'=>'peter'));
    }
}


function w($str) {
    return array_map('trim', explode(',', $str));
}

function map($list, $func){}
?>