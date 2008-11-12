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
        $this->expect($c['name'])->should_be("peter");
        $this->expect($c['name'])->should_not_be("peter wong");
    }

    function should_set_variable_using_array_interface() {
        $c = new H2o_Context;
        $c['name'] = 'peter';
        $c['age'] = 18;
        $this->expect($c['name'])->should_be("peter");
        $this->expect($c['age'])->should_be(18);
    }
    
    function should_ensure_scope_remain_local_in_a_stack_layer() {
        $c = new H2o_Context(array('name'=> 'peter'));

            $c->push(array('name'=>'wong'));
                $c->push(array('name'=>'lee'));
                    $this->expect($c['name'])->should_be('lee');
                    $this->expect($c->resolve(':name'))->should_be('lee');
                $c->pop();
                $this->expect($c->resolve(':name'))->should_be('wong');

            $c->pop();
        $this->expect($c['name'])->should_be('peter');
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

class Describe_context_lookup extends SimpleSpec {
    function prepare() {
        $this->person = array('name' => 'peter','age' => 26, 'tasks'=>array());
        
        $this->location = (object) array(
            'address' => '1st Jones St',
            'city' => 'Marry hill', 'state' => 'NSW', 
            'postcode' => 2320
        );
        $this->doc = new Document(
            'my business report', 
            'Since Augest 2005, financial projection has..'
        );
        $this->context = new H2o_Context(array(
            'person'    => $this->person,
            'hobbies'   => array('football', 'basket ball', 'swimming'),
            'location'  => $this->location,
            'numbers'   => array(1,2,3,4,1,2,3,4,5),
            'document'  => $this->doc
        ));
    }

    function should_resolve_a_number() {
        $c = new H2o_Context;
        # Integer
        $this->expect($c->resolve('0000'))->should_be(0);
        $this->expect($c->resolve('-00001'))->should_be(-1);
        $this->expect($c->resolve('20000'))->should_be(20000);

        # Float
        $this->expect($c->resolve('0.001'))->should_be(0.001);
        $this->expect($c->resolve('99.999'))->should_be(99.999);
    }
    
    function should_resolve_a_string() {
        $c = new H2o_Context;
        $this->expect($c->resolve('"something"'))->should_be('something');
        $this->expect($c->resolve("'he hasn\'t eat it yet'"))->should_be("he hasn't eat it yet");
    }
    
    function should_resolve_a_variable() {
        $c = $this->context;
        $c['name'] = 'h2o template';
        $c['name'] = 'h2o template';
        
        $this->expect($c->resolve(':person'))->should_be($this->person);
        $this->expect($c->resolve(':location'))->should_be($this->location);  
    }
    
    function should_lookup_keyword_index_array_when_encounter_dot_in_variable() {
        $c = $this->context;
        $this->expect($c->resolve(':person.name'))->should_be('peter');
        $this->expect($c->resolve(':person.age'))->should_be(26);
        $this->expect($c->resolve(':person.tasks.length'))->should_be(0);
        
    }
    
    function should_use_dot_to_lookup_numeric_array_index() {
        $c = $this->context;
        $this->expect($c->resolve(':hobbies.0'))->should_be('football');
        $this->expect($c->resolve(':hobbies.1'))->should_be('basket ball');
        $this->expect($c->resolve(':hobbies.2'))->should_be('swimming');
    }
    
    function should_use_dot_to_access_object_property() {
        $c = $this->context;
        $this->expect($c->resolve(':location.address'))->should_be('1st Jones St');
        $this->expect($c->resolve(':document.title'))->should_be('my business report');
    }
    
    function should_return_null_for_undefined_or_private_object_property() {
        $c = $this->context;
        $this->expect($c->resolve(':document.uuid'))->should_be_null();   // Private
        $this->expect($c->resolve(':document.undefined_property'))->should_be_null();
    }

    function should_use_dot_to_perform_method_call() {
        $c = $this->context;
        $this->expect($c->resolve(':document.to_pdf'))->should_match('/PDF Version :/');
        $this->expect($c->resolve(':document.to_xml'))->should_match('/<title>my business report<\/title>/');
    }
    
    function should_return_null_for_undefined_or_private_method_call() {
        $c = $this->context;
        $this->expect($c->resolve(':document._secret'))->should_be_null();   // Private
        $this->expect($c->resolve(':document.undefined_method'))->should_be_null();
    }

    function should_use_dot_to_lookup_additional_array_methods() {
        $c = $this->context;
        $this->expect($c->resolve(':hobbies.first'))->should_be('football');
        $this->expect($c->resolve(':hobbies.last'))->should_be('swimming');
        $this->expect($c->resolve(':hobbies.length'))->should_be(3);
        $this->expect($c->resolve(':hobbies.size'))->should_be(3);
        $this->expect($c->resolve(':numbers.length'))->should_be(9);
    }
}

class Describe_object_context_lookup extends SimpleSpec {
    function prepare() {
        $this->context = new H2o_Context(array(
            'name' => 'peter',
            'hobbies' => array('football', 'basket ball', 'swimming'),
            'location' => array(
                'address' => '1st Jones St',
                'city' => 'Marry hill', 'state' => 'NSW', 'postcode' => 2320
            ) 
        ));
    }

    function should_perform_object_lookup() {
        $c = $this->context;
        $p = (object) array('name' =>'taylor','age' => 26);
        $c->set('person', $p);

        $this->expect($c->resolve(':person'))->should_be($p);
        $this->expect($c->resolve(':person.name'))->should_be('taylor');
        $this->expect($c->resolve(':person.age'))->should_be(26);
    }
    
    function cleanup() {
        unset($this->context);
    }
}


function create_context($context) {
    return new H2o_Context($context);
}
?>