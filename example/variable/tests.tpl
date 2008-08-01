{* 

	H20 Template file : Example
			- it shows you how to do things


	enjoy !
	
	Taylor Luk
*}
<html>
<head>
<title>{{ page.title }}</title>
<style type="text/css">
hr {
	height: 0;
	width: 100%;
	padding: 0;
	margin: 0;
	border-bottom: 1px solid #6f6
}
pre {
	background : #f5f5f5;
	border: 1px solid #ccc;
	width:80%;
	margin: 1em auto;
}
</style>
</head>
<body>
{% cache 5 page_title %}
{% with page.title as title %}
  <h1>{{ title }}</h1>

{% endwith %}


	<h1>{{ "H2O Template Engine: Examples" |capitalize }} asdf </h1>
	{% now %}



asdfasdfasdf
{*	
		Testing some undefined variables for following reasons
			security, 
			
			graceful output,
			
			protection and 
			
			of course error page makes people sad !
*}
{{ aasdfasdfasdf }} 
{{ abc_is_the_king }} 
{{ phpinfo }}
{{ __FILE__ }}
{{ _REQUEST }}
{{ _SERVER }}



	<h2>Syntax</h2>
	A template contains variables, which get replaced with values when the template is evaluated, 
	and tags, which control the logic of the template.
	<h4>1. Variable { { variable_name } }</h4>
	all variable is CASE-SENSITIVE !!!
	<p>
		Variable tag is used to print out a variable, All data passed in H2O_Context object is available here 
	</p>
	<p>
	<b>The magical Dot (.)</b>
		It is used to access member or attributes of a variable regardless the type of the variable
		it could be 
			<ul>
				<li>List (PHP:standard array)</li>
				<li>Hash (PHP:associative array)</li>
				<li>Object</li>
			</ul>
	<pre>
// to print out $person['name'] or $person->name
{ { person.name } }
	</pre> 
	

	<h4>2. Control block { % Control block % }</h4>
	<h3>Basic control flow</h3>
	<pre>
{ %  if not person % }
	<span class="nt">&lt;span&gt;</span><span class=""> no person found</span><span class="nt">&lt;/span&gt;</span><span class="">
{ %  else % }
	</span><span class="nt">&lt;ul&gt;</span><span class="">
	{ %  for index, person in persons  % }
	 </span><span class="nt">&lt;li&gt;</span><span class="">{ {person.id } } -  { {person.name} }</span><span class="nt">&lt;/li&gt;</span><span class="">
	
	{ %  endfor % }
	</span><span class="nt">&lt;/ul&gt;</span><span class="">
{ % endif % }
	</span>
	</pre>



<h2>Testing standard output on large text</h2>
with filters wordwrap, tight 

{% if page.content %}
	<p>
		{{ page.content | tight | wordwrap 100, '<br />' }}
	</p>

{% endif %}

<hr>

<h2>Test to encode a secret</h2>

<code> {{ axis | sha1 }} <b>VS.</b> {{ axis | md5 }}</code>
<hr>

<h2>Test date formatting</h2>
{{ page.created | date "Y-M-d" }}

<b> Vs.</b>

{{ page.created | relative_date }}
<hr>

<h2>Test filter chaining</h2>

{% if not page.description %}

	<p>there is no page description</p>

{% else %} 

	<p>{{ page.description | escape | truncate 20, '...' }} </p>

{% endif %} 



{% if page.title and not page.content %}

	<p>this is a good looking page</p>
	
{% else %} 

	<p>oh yeah i have find you </p>

{% endif %}


<hr>
<h2>Testing nested loops</h2>

{% for index, keyword in page.keywords %}

	<li>
	Page keywords {{ index }} - {{ keyword }} :
	
	<div style="margin-left: 5em"> Loop again: 
		{% for key in page.keywords %}
		<b>{{ key }}</b>, 
		{% endfor %}
	</div>
	</li>
	
{% endfor %} 


<hr>
<h2>Test iteration on objects - output object properties</h2>
<ol>
	{% for person in happy_people %}
	<li>
    NAME : {{ person.name }} <br />
    AGE : {{ person.age }} <br />
	<br>
	</li>
	{% endfor %}
</ol>
<hr>

<h2>Test Test iteration on objects with method execution</h2>
<p>Safety of method execution is ensure by declaring


<h4>Object.h2o_safe = [methodName, methodName ...]</h4>

Note: Person.evil_method is tested, it is ignored and result should be
empty
</p>
<ol>
	{% for person in happy_people %}
	<li>Name : {{ person.name }} <br>

	Hashed Password : {{ person.password }} <br>
	Hobbies : {{ person.show_hobbies }} <br>
	Non-safe method: {{ person.evil_method }} <br>
	<br>
	</li>
	{% endfor %}
</ol>
<hr>

<div id='footer'>
<h2>Test Load external file</h2>
{% include 'footer.tpl' %}</div>
</body>
{% endcache %}
</html>
