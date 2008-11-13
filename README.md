H2O template markup
========================

Please download from 

 * [Google code][google code]


Intro
------------------------
H2O is markup language for PHP that taken a lot of inspiration from django.

 * Readable and human friendly syntax.
 * Easy to use and maintain
 * Encourage reuse in templates by template inclusion and inheritance.
 * highly extensible through filters, tags and template extensions.
 * Bundled rich set of filters and tags for string formatting, HTML helpers and 
   internationalization. 

Requirement
------------------------

 - PHP 5.1 +


Getting started
------------------------

### Download
 * [Google code][google code]
 * [Github][github]

 [google code]: http://code.google.com/p/h2o-template/downloads
 [github]: http://github.com/speedmax/h2o-php

### Installation
 1. Download and extract the source code to a desired path
 2. use `require 'h2o.php'` statement to include h2o library
 3. Here is the quick start and head to the example directory
 
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
        $h2o->render(array('name'=>'Peter Jackson'));
    ?>


### Configuration
There are a few configurable can pass-in as a optional array during h2o 
initialization.

    <?php
        $h2o = new H2o('template.tpl', array(
            [option_name] => [option_value]
        ));
    ?>
        
### Options

 - **loader** -  name of loader or a instance of H2o_Loader, [defaults 'file']
 - **searchpath** - search path h2o uses to load additional templates
 - **cache** - define type of caching engine h2o needs to use, set to false to 
    disable caching [defaults 'file']
 - **cache_dir** - path to store cached template objects [default same as searchpath]
 - **cache_ttl** - time to live value for cache
   

Syntax explanation
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
                'name' => 'Peter Jackson', 'age' => 25
        );
        $h2o->render(compact('person'));
    ?>
    
Let's say you have assigned a person variable in your php script, following 
variable tag will print out `Peter Jackson`

## Filters

Filters are variable modifiers to manipulate or format the value of a variable. 
A filter usually look like this `{{ person.name|capitalize }}`, a pipe ( | ) 
character after a variable will apply a filter.

__Filter chaining__

You can chain multiple filters together and use a pipe ( | ) character to separate 
them. `{{ document.body|escape|nl2br }}`

__Filter arguments__

Filters can accept arguments for example `{{ document.description|truncate 20 }}` 
will display first 20 character of
document description. Moreover, there are cases you want to pass multiple arguments 
and you can use comma( , ) to separate them
`{{ person.bio|truncate 20, "..." }}`

__Filter named arguments__
h2o uses colon ( : ) to for named arguments to build optional arguments array. 

`{{ '/images/logo.png' | image_tag width: 450, height: 250, alt: "company logo" }}`

Note: Difference with Django, Smarty 
H2o do not use colon ( : ) character to separate arguments for readability reasons, 
h2o uses comma ( , ) which is more logical.

 
## Tag  

`{% tag %}`

Tags are usually very powerful, they controls the logical flow or structure, 
iteration. there are inline tags `{% inline_tag %}` or tags that requires a 
close tag. for example: `{% if condition %} ... {% endif %}` 


### if tag

if tag evaluate a variable or a simple expression. when results true the if tag 
body will be render or else part will be rendered.
    
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
H2o supports template inheritance, it is very powerful and the concept is easy 
to understand. 

Template inheritance is implemented using block, extends tag, for programmers 
who is familiar with object oriented principles this is easy. 

Quote from Django doc
> ... a base skeleton template that contains all the common elements of your 
> site and defines blocks that child templates can override.


*Example:*

_base.html_ - to define the base structure of the page.

    <html>
     <head><title>{%block title %}This is a page title {% endblock %}</title></head>
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

As you can see, the base template is a typical web page using a two column layout, 
we defined two blocks (content, sidebar) and HTML code common across all your page.


_page.html_ - to define a template specific of a page.

    {% extends 'base.html' %}
    
    {% block content %}
        <h1> extended page </h1>
        <p> Body of extended page</p>
    {% endblock %}
    
    {% block sidebar %}
        Sidebar contains use links.
    {% endblock %}


The page.html extends base.html, now you will be able to override any block 
previously defined. 

There is a very good article about template inheritance in Django, in area of 
template inheritance h2o work exactly the same way.

[Power of inheritance][3] is a very good blog post explaining inheritance 

 [3]:http://www2.jeffcroft.com/blog/2006/feb/25/django-templates-the-power-of-inheritance/

*Tips*

* if you found you have a lot of common element inside the template, it may be a 
  good idea to put that portion of template in side a block in a base template. 
* block gives you a hook, especially useful they are useful for javascript, css
  too
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
APC is a opt-code cache php extension that also provides a robust object cache, 
and the performance is generally 10-30% faster than file caching. 

    <?php
        $template = new h2o('homepage.tpl', array('cache' => 'apc'));
    ?>

### Memcache
currently not implemented


Extending H2o
------------------------


Known issues
------------------------
Realistically these are very very rare cases, so don't let it stop you getting your
foot wet. 

 * `{{ block.super }}` doesn't work with very deep inheritance so if `{{ block.super }}`
   invokes another `{{ block.super }}` it won't work just yet.  
 * If conditions doesn't support multiple expression or math yet, 
   `{% if something > 3 or something < 2 %}` or `{% if something + else > 12 %}`
    and i don't think
   i plan to implement them that kind of force you to construct a better data
   api any way.


Bug Report
------------------------
Please submit patches or bug report at [Issue tracker on Google code][issue]

 [issue]:http://code.google.com/p/h2o-template/issues/list


Credit
------------------------
There are concepts or ideas burrowed from following projects, Very early version of
h2o was based on the code base of Ptemplates so thanks to Armin Ronacher.


 - Django template - Django web development framework.
 - Ptemplates - Armin Ronacher's pet project for a django port in PHP.
 - Jinja - Django inspired template in Python.

The MIT License
------------------------
Copyright (c) 2008 Taylor Luk 

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.