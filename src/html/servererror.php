<?php ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $_SERVER['HTTP_HOST']; ?> - Server or Network Error Condition</title>
</head>

<body>
<div style="margin: 20px; 0 20px 20px;">
<img src="/softnas/images/Logo_300.png" />
<br />
<h1>&quot;Oops! We encountered an error with the server.&quot;</h1>
<div style="margin: 0 0 0 10px;">
<div>
<h3>Why am I seeing this message?</h3>
<p>SoftNAS&reg; has detected an an error condition when trying to contact the server system over the network. This can happen when there is an error on the server, the system is too busy or overloaded serving storage requests, or insufficient memory or CPU resources have been allocated to the storage server.</p>
<p>You may also have received an error message containing more details about the problem prior to this page appearing.</p>

<h3>What can I do to troubleshoot this issue?</h3>
<p>Ensure your storage server has enough memory and CPU resources allocated to it. If that does not resolve the issue, try rebooting the storage server during a maintenance window. If you are able to SSH into the storage server, try restarting the Apache web server with "service httpd restart" as the root user.</p>
<p>If the problem persists, you can <a href="https://www.softnas.com/wp/support/" target="_blank">Contact Support</a> if you are unsure why this is happening or need assistance to resolve it.</p>
<p><h3><strong><a href="/softnas/">Click Here to resume normal operation</a></strong> after the issue has been resolved or to try again.</h3></p>
<br />
<p></p>
</div>
</div>
</body>
</html>

