<?php
require_once 'spec_helper.php';

class Describe_local_variable extends SimpleSpec {

    function should_retrive_variable_using_array_interface() {
        $c = create_context(array(
            'name' => 'peter',
            'hobbies' => array('football', 'basket ball', 'swimming'),
            'location' => array(
                'address' => '1st Jones St',
                'city' => 'Marry hill', 'state' => 'NSW', 'postcode' => 2320
            ) 
        ));
        expects($c['name'])->should_be("peter");
        expects($c['name'])->should_not_be("peter wong");
    }

    function should_set_variable_using_array_interface() {
        $c = create_context();
        $c['name'] = 'peter';
        $c['age'] = 18;
        
        expects($c['name'])->should_be("peter");
        expects($c['age'])->should_be(18);
    }
    
    function should_ensure_scope_remain_local_in_a_stack_layer() {
        $c= new H2o_Context(array('name'=> 'peter'));

            $c->push(array('name'=>'wong'));
            
                $c->push(array('name'=>'lee'));
                    expects($c['name'])->should_be('lee');
                    expects($c->resolve(':name'))->should_be('lee');
                $c->pop();
                expects($c->resolve(':name'))->should_be('wong');

            $c->pop();
        expects($c['name'])->should_be('peter');
    }
}

class Describe_context_lookup_basic_data_types extends SimpleSpec {

    function should_resolve_a_integer() {
        $c= create_context();
        
        expects($c->resolve('0000'))->should_be(0);
        expects($c->resolve('-00001'))->should_be(-1);
        expects($c->resolve('20000'))->should_be(20000);
    }
    
    function should_resolve_a_float_number() {
        $c= create_context();
        
        # Float
        expects($c->resolve('0.001'))->should_be(0.001);
        expects($c->resolve('99.999'))->should_be(99.999);
    }
    
    function should_resolve_a_negative_number() {
        $c= create_context();
        
        expects($c->resolve('-00001'))->should_be(-1);   
    }
    
    function should_resolve_a_string() {
        $c= create_context();
        expects($c->resolve('"something"'))->should_be('something');
        expects($c->resolve("'he hasn\'t eat it yet'"))->should_be("he hasn't eat it yet");
    }
    
    function should_escape_output_by_default() {
        $a = '<script>danger</script>';
        $b = '<h1>Welcome</h1>';
        $c = create_context(compact('a','b'));
        
        expects(h2o('{{ a }}')->render($c))->should_be('&lt;script&gt;danger&lt;/script&gt;');
        expects(h2o('{{ b|safe }}')->render($c))->should_be('<h1>Welcome</h1>');
        
        # disable autoescape as option
        expects(h2o('{{ a }}', array('autoescape' => false))->render(compact('a','b')))->should_be($a);
        
        # disable autoescape on context object
        $c->autoescape = false;
        expects(h2o('{{ a }}')->render($c))->should_be($a);
        
    }
}

class Describe_array_lookup extends SimpleSpec {

    function should_be_access_by_array_index() {
        $c= create_context(array(
            'numbers'   => array(1,2,3,4,1,2,3,4,5),
        ));
        
        expects($c->resolve(':numbers.0'))->should_be(1);
        expects($c->resolve(':numbers.1'))->should_be(2);
        expects($c->resolve(':numbers.8'))->should_be(5);
    }
    
    function should_be_access_by_array_key() {
        $c= create_context(array('person' => array(
            'name' => 'peter','age' => 26, 'tasks'=> array('shopping','sleep')
        )));
        
        expects($c->resolve(':person.name'))->should_be('peter');
        expects($c->resolve(':person.age'))->should_be(26);
        expects($c->resolve(':person.tasks.first'))->should_be('shopping');
    }
    
    function should_resolve_array_like_objects() {
        $c= create_context(array(
            'list' => new ArrayObject(array(
                'item 1', 'item 2', 'item 3'
            )),
            'dict' => new ArrayObject(array(
                'name' => 'peter','seo-url' => 'http://google.com'
            ))
        ));
        expects($c->resolve(':list.0'))->should_be('item 1');
        expects($c->resolve(':list.length'))->should_be(3);
        expects($c->resolve(':dict.name'))->should_be('peter');
        expects($c->resolve(':dict.seo-url'))->should_be('http://google.com');
    }
    
    function should_resolve_additional_array_property() {
       $c = create_context(array(
           'hobbies'=> array('football', 'basket ball', 'swimming')
       ));
       
       expects($c->resolve(':hobbies.first'))->should_be('football');
       expects($c->resolve(':hobbies.last'))->should_be('swimming');
       expects($c->resolve(':hobbies.length'))->should_be(3);
       expects($c->resolve(':hobbies.size'))->should_be(3);
    }
}

class Describe_object_context_lookup extends SimpleSpec {
    
    function should_use_dot_to_access_object_property() {
        $c = create_context(array(
            'location' => (object) array(
                'address' => '1st Jones St',
                'city' => 'Marry hill', 'state' => 'NSW', 
                'postcode' => 2320
            ),
        ));
        expects($c->resolve(':location.address'))->should_be('1st Jones St');
        expects($c->resolve(':location.city'))->should_be('Marry hill');
    }
    
    function should_return_null_for_undefined_or_private_object_property() {
        $c = create_context(array(
            'document' => new Document(
                'my business report', 
                'Since Augest 2005, financial projection has..')
        ));
        expects($c->resolve(':document.uuid'))->should_be_null();   // Private
        expects($c->resolve(':document.undefined_property'))->should_be_null();
    }

    function should_use_dot_to_perform_method_call() {
        $c = create_context(array(
            'document' => new Document(
                'my business report', 
                'Since Augest 2005, financial projection has..')
        ));
        expects($c->resolve(':document.to_pdf'))->should_match('/PDF Version :/');
        expects($c->resolve(':document.to_xml'))->should_match('/<title>my business report<\/title>/');
    }
    
    function should_return_null_for_undefined_or_private_method_call() {
        $c = create_context(array(
            'document' => new Document(
                'my business report', 
                'Since Augest 2005, financial projection has..')
        ));
        
        expects($c->resolve(':document._secret'))->should_be_null();   // Private
        expects($c->resolve(':document.undefined_method'))->should_be_null();
    }
    
    
    function should_resolve_overloaded_attributes() {
        $c = create_context(array(
            'person' => $p = new Person
        ));

        expects($c->resolve(':person.name'))->should_be('The king');
        expects($c->resolve(':person.age'))->should_be(19);
    }
}

class Document {
    var $h2o_safe = array('to_pdf', 'to_xml');
    private $uuid;

    function __construct($title, $content) {
        $this->title = $title;
        $this->content = $content;
        $this->uuid = md5($title.time());
    }

    function to_pdf() {
        return "PDF Version : {$this->title}";
    }

    function to_xml() {
        return "<title>{$this->title}</title><content>{$this->content}</content>";
    }

    function _secret() {
        return "secret no longer";
    }
}

class Person {
    var $data = array(
        'name' => 'The king',
        'age' => 19
    );
    
    function __get($attr) {
        if (isset($this->data[$attr]))
            return $this->data[$attr];
        return null;
    }
    
    function __isset($attr) {
        return !is_null($this->$attr);
    }
}

function create_context($c = array()) {
    return new H2o_Context($c);
}

?>