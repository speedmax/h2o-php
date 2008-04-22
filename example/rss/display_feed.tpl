<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <link rel="stylesheet" href="style.css" />
    <title>Reading feed from :{{ feed.channel.title }}</title>
</head>
<body>
	<h1>Example dealing with xml or rss data</h1>
	
	<h3>1. Rss feed reader </h3>
	<p> below, This is a perfect example to show the power of a good template engine, 
		it allows you to display a rss feed with so little effort
	</p> 
	
	<h3>2. Produce rss feed</h3>
	<p>
		<a href="rss.php">Sample rss feed</a>
	</p>
	<hr>
	
	
	
	
	
	<h2>Reading feed from :{{ feed.channel.title }}</h2>
    <div id="feeds">
        <ul>
        {% for entry in feed.channel.item %}
        
        	{% if loop.first %}
        	
	        	<h1>Latest entry</h1>
	        	<h2>{{ entry.title }}</h2>
	        	<small> published : {{ entry.pubDate | relative_datetime }} </small>
	            <p>{{ entry.description }}</p>
	        	<a href="{{ entry.link }}"> Read More...</a>
	        	
	        	<h3> Older entries </h3>
	        	
        	{% else %}
	        	<li style="background:{% cycle red,blue,yellow,pink,brown,black,grey,silver %}"><a href="{{ entry.link }}">{{ entry.title }}</a><small> published : {{ entry.pubDate | relative_datetime}} </small> <br>
	            	<p>{{ entry.description }}</p>
	            </li>
	            
        	{% endif %} 
        	
        {% endfor %}
        </ul>
        
        <textarea rows="10" cols="80">{{ rss_file }} </textarea>
        
    </div>
</body>
</html>