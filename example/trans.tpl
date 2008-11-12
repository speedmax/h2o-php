{% load 'cache' %}
{% cache 3 %}

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>{% trans "Internationalized templates" %}</title>
</head>
<body>

<div id="header">
	<h1>{{  _("Internationalized templates") }}</h1>
	<p>{{ _("bullshit")  }}</p>
</div>
{% now %}


<div id="content">
	{% blocktrans count=users.length %}
	    there is one {{ count }} item.
	{% plural %}
	    there are number of {{ count }} items.
	{% endblocktrans %}
	</p>
	
	<ul>
	{% for index, user in users %}
	    {{ user.username }} - {{ user.tasks.length }}
	<li style="color:{% cycle 'grey', 'pink' %}">
	    {{ index }}
		{% blocktrans count=user.tasks.length, name=user.username|capitalize %}
		    {{ name }} has {{ count }} task
		{% plural %}
		    {{ name }} has {{ count }} tasks
		{% endblocktrans %}
	
		<ol>
		{% for task in user.tasks %}
		   <li>{{ task }}</li>
		{% endfor %}
		</ol>
	</li>
	{% endfor %}
	</ul>
</div>

<div id="footer">
    
</div>
</body>
</html>
{% endcache %}
