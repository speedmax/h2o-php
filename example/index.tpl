<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>{{ page.title, "something" | capitalize }}</title>
<meta name="keywords" content="" />
<meta name="generator" content="{{ H2O_NAME }}" />
<meta name="description" content="" />
<link href="default.css" rel="stylesheet" type="text/css" />
</head>
<body>
<div id="header">
    <div id="logo">
        <h1><a href="#">H2O</a></h1>

        <h2><a>Templates Engine</a></h2>
    </div>
    <div id="menu">
        <ul>
            {% for title, url in menus limit 5 %}
	            <li {% if loop.first %}class="first"{% endif %}>
	            <a href="{{ url }}">{{ title }}</a>
	            </li>
            {% endfor %}
        </ul>
    </div>
</div>
<div id="content">
    <div id="colOne">
        <div class="post">
            <h2 class="title">{{ page.title | capitalize }}</h2>

            <h3 class="posted">by : {{ page.author | capitalize }}</h3>
            <div class="story">
                {{ page.description | linebreaks }}
            </div>

        </div>
        <div class="post">
            {{ page.content | linebreaks }}
        </div>
    </div>
    <div id="colTwo">
       <div>
	       <h2>Sample code</h2>
	        {{ page.sample |escape| highlight_h2o 1}}
       </div>
       
        <div style="clear: both;">&nbsp;</div>
       
    </div>
</div>
<div id="footer">
    <p>
        Copyright &copy; 2007 . Made possible by H2O, template from <a href="http://www.freecsstemplates.org/">neonix</a></p>
</div>

</body>
</html>
