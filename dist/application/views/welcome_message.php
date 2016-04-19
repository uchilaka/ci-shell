<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Welcome to CodeIgniter</title>

	<style type="text/css">

	::selection { background-color: #E13300; color: white; }
	::-moz-selection { background-color: #E13300; color: white; }

	body {
		background-color: #fff;
		margin: 40px;
		font: 13px/20px normal Helvetica, Arial, sans-serif;
		color: #4F5155;
	}

	a {
		color: #003399;
		background-color: transparent;
		font-weight: normal;
	}

	h1 {
		color: #444;
		background-color: transparent;
		border-bottom: 1px solid #D0D0D0;
		font-size: 19px;
		font-weight: normal;
		margin: 0 0 14px 0;
		padding: 14px 15px 10px 15px;
	}

	code {
		font-family: Consolas, Monaco, Courier New, Courier, monospace;
		font-size: 12px;
		background-color: #f9f9f9;
		border: 1px solid #D0D0D0;
		color: #002166;
		display: block;
		margin: 14px 0 14px 0;
		padding: 12px 10px 12px 10px;
	}

	#body {
		margin: 0 15px 0 15px;
	}

	p.footer {
		text-align: right;
		font-size: 11px;
		border-top: 1px solid #D0D0D0;
		line-height: 32px;
		padding: 0 10px 0 10px;
		margin: 20px 0 0 0;
	}

	#container {
		margin: 10px;
		border: 1px solid #D0D0D0;
		box-shadow: 0 0 8px #D0D0D0;
	}
	</style>
</head>
<body>

<div id="container">
	<h1>Welcome to CodeIgniter (LarCity Edition)!</h1>

	<div id="body">
		<p>If you can see this page, congrats! You have successfully setup our CI inspired shell for apps at LarCity. We'll include all of our awesome features in this library for 
		cool stuff like:
			<ul>
			<li>Auth0 Integration</li>
			<li>Cloud storage integration</li>
			</ul>
			And tons more. The path to our project is: <a href="https://github.com/uchilaka/com.larcity.codeigniter.shell" target="_blank">https://github.com/uchilaka/com.larcity.codeigniter.shell</a>. 
		</p>
                
                <h3>Loaded Classes</h3>
                Here are all the classes automagically (got that from one of my co-students at a recent training ;)) included by 
                composer:
                <p />
                <?php 
                $loadedClasses = get_declared_classes();
                if(!empty($loadedClasses)) {
                    print_r($loadedClasses);
                } else {
                    echo "<em>No loaded classes passed to view.";
                }
                ?>
                
                <p>Here's an example of parameters passed to the view:</p>
                <?php 
                if(!empty($viewParameters)) {
                    print_r($viewParameters);
                } else {
                    echo "<em>No parameters to show. To test this, pass a variable into the view using CI syntax with a key `viewParameters`.</em>";
                }
                ?>

		<p>
		Got questions? <a href="https://twitter.com/intent/tweet?via=uchechilaka&text=Was wondering...&hashtags=ci-shell" target="_blank">Drop us a line on Twitter</a>. And now, some 
		messages from the awesome folks @ CodeIgniter.
		</p>

		<hr />

		<p>The page you are looking at is being generated dynamically by CodeIgniter.</p>

		<p>If you would like to edit this page you'll find it located at:</p>
		<code>application/views/welcome_message.php</code>

		<p>The corresponding controller for this page is found at:</p>
		<code>application/controllers/Welcome.php</code>

		<p>If you are exploring CodeIgniter for the very first time, you should start by reading the <a href="user_guide/">User Guide</a>.</p>
	</div>

	<p class="footer">Page rendered in <strong>{elapsed_time}</strong> seconds. <?php echo  (ENVIRONMENT === 'development') ?  'CodeIgniter Version <strong>' . CI_VERSION . '</strong>' : '' ?></p>
</div>

</body>
</html>