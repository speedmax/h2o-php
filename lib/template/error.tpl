<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
  <head>
    <title><?php echo $title ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<style type="text/css">
		body {
		background-color:#1A1A1A;
		color:#f5f5f5;
		font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
		font-size:13px;
		line-height:20px;
		margin:3em;
		padding:0pt;
		}

		table {
			width: 100%;
			color:#333;
		}
		
		a { color:white}
		
		pre{
      height:500px;
      overflow:auto;
      width:95%
		}
		
		#title {
			color: #ccc;
		}
		
	</style>
	<script>
	if (!$) {
		var $ = function(id){ return document.getElementById(id) };
	}
	</script>
  </head>
<body>
<h1 id="title"><?php echo $title?></h1>


<h2>
<?php echo $description?>
</h2>


<h3 style="color:orange"><?php echo $filename?></h3>
<a href="#" onclick="$('full').style.display='none';$('partial').style.display='block';">
around error</a> | 
<a  onclick="$('partial').style.display='none';$('full').style.display='block';" href="#">
template source</a>

<pre id="partial" style="border:1px solid #999">
<?php echo $around?>
</pre>

<pre id="full" style="display:none;border:1px solid #999">
<?php echo $source_code?>
</pre>
</pre>
</body>

</html>



