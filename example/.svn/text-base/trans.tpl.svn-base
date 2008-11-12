<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>{% trans "Insert title here" %}</title>
</head>
<body>

<h1>{{  _("This is a h2o template internaltionalized") | capitalize }}</h1>

<h3>{{ _("bullshit")  }}</h3>

<p>
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

</body>
</html>