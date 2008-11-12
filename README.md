H2O template markup
========================

Please download from 

 * Google code - http://code.google.com/p/h2o-template/downloads


Intro
------------------------
H2O is a template markup language for PHP, a lot of syntax and philosophy was inspired by the template system
in django framework. 

 * Readable and human friendly syntax.
 * Easy to use and maintain
 * Encourage reuse in templates by template inclusion and inheritance.
 * higly extensible through filters, tags and template extensions.
 * Bundled rich set of filters and tags for string formatting, html helpers and internationlization 

Requirement
------------------------

 - PHP 5.1 +
  
 

Getting started
------------------------

### Download
 * Google code - http://code.google.com/p/h2o-template/downloads
 * Github - http://github.com/speedmax/h2o-php

### Installation
 1. Download and extract the source code to a desired path
 2. use `require 'h2o.php'` statement to include h2o library
 3. quick test
 
*index.html*

    <body>
        <head><title>Hello world</title></head>
        <body>
            Greetings {{ name }}
        </body>
    </body>

*in PHP*

    <?php
        require 'h2o.php';
        $h2o = new h2o('index.html');
        $h2o->render(array('name'=>'Peter jackson'));
    ?>


### Configuration
There are a few configurables can pass-in as a optional array during h2o initialization.

    <?php
        $h2o = new H2o('template.tpl', array(
            [option_name] => [option_value]
        ));
    ?>
        
### Options

 - **loader** -  name of loader or a instance of H2o_Loader, [defaults 'file']
 - **searchpath** - search path h2o uses to load additional templates
 - **cache** - define type of caching engine h2o needs to use, set to false to disable caching [defaults 'file']
 - **cache_dir** - path to store cached template objects [default same as searchpath]
 - **cache_ttl** - time to live value for cache
   

Syntax explaination
------------------------

## variable
`{{ variable }}`

Use dot (.) to access attribute of a variable

#### variable lookup order
1. key of associative array
2. array-index 
3. object attribute
4. object method call

**Example**

*in your template*

    {{ person.name }} 

*in php*

    <?php 
        $h2o = new H2o('template.tpl');
        $person =array(
                'name' => 'Peter jackson', 'age' => 25
        );
        $h2o->render(compact('person'));
    ?>
    
Let's say you have assigned a person variable in your php script, following variable 
tag will print out `Peter jackson`

## Filters

Filters are variable modifiers to manipulate or format the value of a variable. A filter usually look like this
`{{ person.name|capitalize }}`, a pipe ( | ) character after a variable will apply a filter.

__Filter chaining__

You can chain multiple filters together and use a pipe ( | ) character to seperate them.
`{{ document.body|escape|nl2br }}`

__Filter arguments__

Filters can accept arguments for example `{{ document.description|truncate 20 }}` will display first 20 character of
document descriptiom. Moreover, there are cases you want to pass multiple arguments and you can use comma( , ) to seperate them
`{{ person.bio|truncate 20, "..." }}`

__Filter named arguments__
h2o uses colon ( : ) to for named arguments to build optional arguments array. 

`{{ '/images/logo.png' | image_tag width: 450, height: 250, alt: "company logo" }}`

Note: Difference with django, smarty 
H2o do not use colon ( : ) character to seperate arguments for readibility reasons, 
h2o uses comma ( , ) which is more logical.

 
## Tag  

`{% tag %}`

Tags are usually very powerful, they controls the logical flow or structure, iteration. there are
inline tags `{% inline_tag %}` or tags that requires a close tag. for example: `{% if condition %} ... {% endif %}` 


### if tag

if tag evaluate a variable or a simple expression. when results true the if tag body will be render or 
else part will be rendered.
    
    {% if person.is_adult %}
        You are old enough.
    {% else %}
        sorry, you are too young for that.
    {% endif %}

### for tag

For tag will iterate over a array of items. 
 
    {% for task in tasks %}
        {{ task }}
    {% endfor %}

Above will print all the tasks.


Template inheritance
------------------------
H2o supports template inheritance, it is very powerful and the concept is easy to understand. 

Template inheritance is implemented using block, extends tag, for programmers who is familiar 
with object oriented principles this is easy. 

Quote from django
> ... a base skeleton template that contains all the common elements of your site and defines 
> blocks that child templates can override.


*Example:*

_base.html_ - to define the base structure of the page.

    <html>
     <head><title> {%block title %} This is a page title {% endblock %} </title></head>
     <body>
     <div id="content">
       {% block content%}
    
           <h1> Page title </h1>
           <p> H2O template inheritance is a powerful tool </p> 
       {% endblock %}
     </div>
    
     <div id="sidebar">
       {% block sidebar %}{% endblock %}
     </div>
     </body>
    </html>


As you can see, the base template is a typical web page using a two column layout, we 
defined two blocks (content, sidebar) and HTML code common across all your page.


_page.html_ - to define a template specific of a page.

    {% extends 'base.html' %}
    
    {% block content %}
        <h1> extended page </h1>
        <p> Body of extended page</p>
    {% endblock %}
    
    {% block sidebar %}
        Sidebar contains use links.
    {% endblock %}


The page.html extends base.html, now you will be able to override any block previously defined. 

There is a very good article about template inheritance in django, in area of template 
inheritance h2o work exactly the same way.

[Power of inheritance](http://www2.jeffcroft.com/blog/2006/feb/25/django-templates-the-power-of-inheritance/) 



*Tips*

* if you found you have a lot of common element inside the template, it may be a good idea to 
  put that portion of template in side a block in a base template. 
* block gives you a hook, especially useful they are useful for javascript, css too
* When defining a block use a short and distinctive name


Performance and Caching
------------------------

Caching can increase performance since it skips step of inefficient template parsing, 
H2o caches the template objects(internal data structure of a template) and bundled
multiple caching backend includes File, APC and memcache.

### File cache
By default h2o uses file cache to store template objects, change h2o option `cache_dir` to where you 
want to store template cache (ie: /tmp).
  
    <?php
        $template = new H2o('homepage.tpl', array(
            'cache' => 'file',
            'cache_dir' => '/tmp'
        ));
    ?>

### APC cache
APC is a opt-code cache php extension that also provides a robust object cache, and the performance
is generally 10% faster than file caching. 

    <?php
        $template = new h2o('homepage.tpl', array('cache' => 'apc'));
    ?>

### Memcache
currently not implemented


Extending H2o
------------------------


Bug Report
------------------------

[Issue tracker on Google code](http://code.google.com/p/h2o-template/issues/list)
