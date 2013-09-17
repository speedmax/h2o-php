H2O template markup
========================
Being a martial arts fan, I borrow a quote.



H2O template
------------------------
H2O is markup language for PHP that has taken a lot of inspiration from Django.


__Features__
 * Readable and human-friendly syntax.
 * Easy to use and maintain
 * Encourages reuse in templates by allowing template inclusion and inheritance.
 * Highly extensible through filters, tags, and template extensions.
 * Includes a rich set of filters and tags for string formatting, HTML helpers and 
   internationalization support.


Requirement
------------------------

 - PHP 5.1 +


News
------------------------

 - version 0.4 
   1. **Breaking changes** autoescape is now turned on by default
   2. Improved searchpath and file loading handling
   3. Improved handling on PHP overloaded objects
   4. Plenty of bug fixes
 - version 0.3
   1. Support internationalized templates and translation parsing toolkit
   2. Performance optimization on context lookup
   3. Fixed operator parsing

Getting started
------------------------

### Getting h2o

Download

[<img src="http://github.com/images/modules/download/zip.png">](http://code.google.com/p/h2o-template/downloads)
 
With Git

`git clone http://github.com/speedmax/h2o-php.git`

With SVN 

`svn checkout http://h2o-template.googlecode.com/svn/trunk/ h2o`

### Installation
 1. Download and extract h2o into your project path or your php include path

    Sample file structure setup 
     
        myawesome_app/
            index.php
            templates/
              index.html
            h2o/


 2. Use `require 'h2o/h2o.php'` in your php files to include the h2o library.
 3. Below is a basic code sample to get your project going. 
 3. Check out the *\example* and *\specs* dirs to see some of h2o's more interesting features in action. 
 
*templates/index.html*

    <body>
        <head><title>Hello world</title></head>
        <body>
            Greetings {{ name }}
        </body>
    </body>

*index.php*

    <?php
        require 'h2o/h2o.php';
        $h2o = new h2o('templates/index.html');
        echo $h2o->render(array('name'=>'Peter Jackson'));
    ?>


Useful links
------------------------

 * Please submit patches or bug reports to our [lighthouse bug tracker][issue].
 * Check out our [Google group][group] for h2o-related discussion.

 [issue]:http://idealian.lighthouseapp.com/projects/11041-h2o-template-language
 [group]:http://groups.google.com/group/h2o-template-php


Syntax explanation
------------------------

## variable
`{{ variable }}`

Use dot (.) to access attributes of a variable

#### variable lookup order
1. key of an associative array
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
    
Let's say that you have assigned the value `Peter Jackson` to a 'person' variable in your php script. The following 
variable tag will print out `Peter Jackson`.

## Filters

Filters are variable modifiers that manipulate or format the value of a variable. 
A filter usually looks like this `{{ person.name|capitalize }}`, a pipe ( | ) 
character after a variable, plus a filter name, will cause H2O to apply the filter.

__Filter chaining__

![filter chaining](http://wiki.shopify.com/upload/8/8c/Filterchain.jpg)  
Let me borrow the image from liquid template

You can chain multiple filters together and use a pipe ( | ) character to separate 
them. `{{ document.body|escape|nl2br }}`

__Filter arguments__  
Filters can accept arguments. For example:
`{{ document.description|truncate 20 }}` 
will display the first 20 characters of the document's description.

Moreover, there are cases where you might want to pass in multiple arguments. 
Use commas ( , ) to separate them:
`{{ person.bio|truncate 20, "..." }}`

__Filter named arguments__  
h2o uses colons ( : ) for named arguments. These allow you to build 'optional argument' arrays. 

`{{ '/images/logo.png' | image_tag width:450, height:250, alt:"company logo" }}`

The above code translated to php will be like the below snippet, which resembles what happens internally:
    
    <?php
        echo image_tag("/image/logo.png", array(
            'width' => 450, 
            'height' => 250, 
            'alt'=>'company logo'
        ));
    ?>

Note: Difference with Django, Smarty 
H2o does not use the colon ( : ) character to separate arguments for readability reasons, 
H2o uses the comma ( , ) which is more logical.

 
## Tag  

`{% tag %}`

Tags are very powerful, as they control the logical flow and structure of a template, 
There are inline tags `{% inline_tag %}` or tags that requires a 
close tag. For example: `{% if condition %} ... {% endif %}` 


### The "if" tag

`if` tags evaluate either a variable or a simple expression. If the result of the `if` 
expression is *true*, then the contents of the `if` block will be allowed to render.
    
    {% if person.is_adult %}
        You are old enough.
    {% else %}
        sorry, you are too young for that.
    {% endif %}

### The "for" tag

`for` tags allow iteratation over an array of items. 
 
    {% for task in tasks %}
        {{ task }}
    {% endfor %}

The above snippet will print out each "task" in the "tasks" array.

Template inheritance
------------------------
H2o supports template inheritance. Inheritance allows you to factor out a lot 
of common code that would otherwise be duplicated across most of your templates.

Template inheritance is implemented using the `block` and `extends` tags, with child templates
*extending* their parent templates.
**Word of Caution:**
 * H2o templates only support single inheritance (just like PHP!), and currently do not support deep inheritance chains.


Quote from the Django docs:
> ... a base skeleton template that contains all the common elements of your 
> site and defines blocks that child templates can override.


*Example:*

_base.html_ - defines the base structure of our pages.

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

As you can see, the base template is a typical web page using a two column layout. 
We defined two blocks (`content` and `sidebar`) and HTML code common across all of our pages.


_page.html_ - defines a page-specific template.

    {% extends 'base.html' %}
    
    {% block content %}
        <h1> extended page </h1>
        <p> Body of extended page </p>
    {% endblock %}
    
    {% block sidebar %}
        Sidebar contains use links.
    {% endblock %}


The `page.html` extends `base.html`, allowing us to override any block 
previously defined in `base.html`. 

Below is an excellent article about template inheritance in Django. If you wish to understand H2o's
template-inheritance system, this would be a great spot to start, since H2o's template-inheritance system 
is strongly influenced by Django's.

[Power of inheritance][3] is a very good blog post explaining inheritance 

 [3]:http://www2.jeffcroft.com/blog/2006/feb/25/django-templates-the-power-of-inheritance/

*Tips*

* If you have found that you have several common elements inside the same template, it may be a 
  good idea to put that portion of the template inside a `block` in a base template. 
* `block` give you a hook, which is useful, since these can help with javascript and css too.
* When defining a block use a short and distinctive name



### Configuration
There are a range of options for configuring the template engine.

    <?php
        $h2o = new H2o('template.tpl', array(
            [option_name] => [option_value]
        ));
    ?>

#### Loader
The name of the loader or an instance of H2o_Loader

__Use file loader [default]__

` $template = new H2o('index.html', array('loader'=>'file'); `


__Advanced setup__
    <?php
    $loader = new H2o_File_Loader($custom_searchpath);
    $template = new H2o('index.html', array('loader'=> $loader );
    ?>
    
__Use dictionary loader__

If you want to load templates from resources other than files, then this will be your
friend. H2o uses `dict_loader()` for testing.

    <?php
        $loader = dict_loader(array(
            "index.html" => 'Hello {{ person }}'
        ));
        $template = new H2o('index.html', array('loader' => $loader'));
    ?> 

#### Searchpath

default: this will be the base path of your template

H2o use this path to load additional templates and extensions. 

You can either explicity set the search path,

`$template = new H2o('index.html', array('searchpath' => '/sites/common_templates'));`

or h2o will try to find the searchpath for you.

`$template = new H2o('/sites/common_templates/index.html');`

#### Cache
You can define the type of caching engine h2o should use, if any. 
Set 'cache' to false to disable caching.
You can read more about performance and caching in following sections

Use file cache [default]

`$template = new H2o('index.html', array('cache'=>'file'));`

Use apc cache:

`$template = new H2o('index.html', array('cache'=>'apc'));`

Use memcache cache

`$template = new H2o('index.html', array('cache'=>'memcache'));`

Disable caching

`$template = new H2o('index.html', array('cache'=>false));`

#### Cache_dir
When the file cache is used, you can define where you want templates to be cached. 

It will put a cached template in same location as the normal template

`$template = new H2o('index.html', array('cache_dir'=>'/tmp/cached_templates'));`

#### Cache_ttl
"cache_ttl" specifies how long a cached template should be used (defaults: 1 hour) before it is recompiled. The template fragment cache
that is bundled in the cache extension will use this as default ttl value.

`$template = new H2o('index.html', array('cache_ttl' => 3600));`


Performance and Caching
------------------------

Caching can increase performance since it skips step of inefficient template parsing. 
H2o caches the template objects (the internal data structure of a template) and the bundled
caching backends include File, APC, and Memcache.

### File cache
By default h2o uses the file cache to store template objects. Change h2o option `cache_dir` to where you 
want to store template cache (ie: /tmp).
  
    <?php
        $template = new H2o('homepage.tpl', array(
            'cache' => 'file',
            'cache_dir' => '/tmp'
        ));
    ?>

### APC cache
APC is an op-code and object cache extension for php whose performance is 
generally 10-30% better than just plain file caching. 

    <?php
        $template = new h2o('homepage.tpl', array('cache' => 'apc'));
    ?>

### Memcache
Currently not implemented


Extending H2o
------------------------


Known issues
------------------------
Yes, h2o has them. However, if you are careful, these shouldn't hinder your template development.
The deep inheritance issue is a bit problematic for some template architectures, but again, if you 
are careful, and perhaps a bit inventive, it won't hinder you terribly much.

 * `{{ block.super }}` doesn't work with more than 1 level of inheritance yet, so if `{{ block.super }}`
   invokes another `{{ block.super }}` it won't work just yet.  
 * 'if' conditions don't support multiple expressions or mathematical expressions yet, like: 
   `{% if something > 3 or something < 2 %}` or `{% if something + else > 12 %}`
    These likely will not be implemented in the future unless some daring soul implements them and 
    contributes the code back to the h2o-php project.
    

Contributors
---
  - Taylor Luk - Founder of [Issue publishing](http://issueapp.com)
  - jlogsdon - Major refactoring (wip) and bug fixes
  - cyberklin - Added filter support for any context resolve
  - idlesign - Added if_changed tag support
  - metropolis - Improved our test coverage
  - plus many others


Credit
------------------------
H2o borrows ideas and/or concepts from the following projects:

 - Django template - Django web development framework.
 - Ptemplates - Armin Ronacher's pet project for a django port in PHP.
 - Jinja - Django inspired template in Python.

Special Thanks: Armin Ronacher, since early versions of h2o were based off of his Ptemplates project.

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
