<?php
class Describe_Template extends SimpleSpec {

    function should_be_able_to_create_from_string() {
        $result = h2o("<h1>{{ a }}</h1>")->render(array('a'=>'hello world'));
        expects($result)->should_be("<h1>hello world</h1>");
    }
}

# x tag for testing
class Sample_Tag extends h2o_Node {}

class Describe_h2o_tag_registration extends SimpleSpec {
    function should_be_pre_registered_with_default_tags() {
        foreach (w('Block,Extends,Include,If,For,With') as $tag) {
            expects(h2o_Tag::exists($tag))->should_be_true();
        }
    }

    function should_be_able_to_register_a_new_tag() {
        h2o_Tag::add('sample', 'Sample_Tag');
        expects(h2o_Tag::exists('sample'))->should_be_true();
        h2o_Tag::remove('sample');

        h2o_Tag::add('sample');
        expects(h2o_Tag::exists('sample'))->should_be_true();
        h2o_Tag::remove('sample');
    }
    
    function should_be_able_to_register_tag_using_alias() {        
    }
}

class Describe_h2o_filter_registration extends SimpleSpec {
    function should_pre_register_default_filters() {
        $filters = array_keys(h2o_Filter::export());
        
        # Safe Native php functions as filter
        expects($filters)->should_contain(
            w('md5, sha1, join, wordwrap, trim, upper, lower')
        );
        
        # All core filters
        expects($filters)->should_contain(FilterCollection::keys('CoreFilters', true));
        
        # All Html Filters
        expects($filters)->should_contain(FilterCollection::keys('HtmlFilters', true));
        
        # All StringFilters
        expects($filters)->should_contain(FilterCollection::keys('StringFilters', true));
        
        # All NumberFilters
        expects($filters)->should_contain(FilterCollection::keys('NumberFilters', true));
        
        # All DatetimeFilters
        expects($filters)->should_contain(FilterCollection::keys('DatetimeFilters', true));
    }
    
    function should_be_able_to_register_a_filter() {   
        h2o_Filter::add('sample_filter');
        $result= h2o('{{ something | sample_filter }}')->render();
        expects($result)->should_be('i am a filter');
    }

    function should_be_able_to_register_a_filter_collection() {
        h2o_Filter::add('SampleFilters');

        $result = h2o('{{ person | hello }}')->render(array('person'=>'peter'));
        expects($result)->should_be('says hello to peter');
    }
}

function sample_filter($value) { 
    return "i am a filter";
}

class SampleFilters extends FilterCollection {
    static public function hello($value) {
        return "says hello to {$value}";
    }
}

function w($str) {
    return array_map('trim', explode(',', $str));
}
