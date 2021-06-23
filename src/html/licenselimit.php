<?php ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $_SERVER['HTTP_HOST']; ?> - Invalid License Condition</title>
</head>

<body>
<div style="margin: 20px; 0 20px 20px;">
<img src="/softnas/images/Logo_300.png" />
<br />
<h1>&quot;Oops! We encountered an invalid license condition.&quot;</h1>
<div style="margin: 0 0 0 10px;">
<div>
<h3>Why am I seeing this message?</h3>
<p>SoftNAS&reg; has detected an invalid license condition. This can happen when a license has expired, more online storage is configured than is currently licensed for use, or there is an invalid license.</p>
<p>You may also have received an error message containing more details about the problem prior to this page appearing.</p>
<h3>What can I do to troubleshoot this issue?</h3>
<p>If more storage is configured than the licensed capacity, you can try to <a href="/softnas/applets/pools/" target="_blank">decrease the amount of storage being used by deleting one or more storage pools.</a><br />If you get a "busy" indication, that means there are NAS sessions already established via NFS, iSCSI or CIFS and the storage pool cannot be deleted at this time to free space.</p>
<p>If your subscription has expired or you need to add more licensed capacity, you can <a href="https://www.softnas.com/wp/products/pricing-and-plans/" target="_blank">get a new license key to extend your subscription or add storage capacity.</a>  Once you have acquired a new license key, you can <a href="/softnas/applets/license?redir=welcome">add the new license key to extend your subscription or add storage capacity.</a></p>
<p>If you just downloaded SoftNAS to try it out, you can <a href="https://www.softnas.com/wp/login/register/" target="_blank"> get a free trial or register and activate to receive additional free storage space.</a>  Once you have regisered and receive your license key, you can <a href="/softnas/applets/license?redir=welcome">add the new license key here</a>.</p>
<p><a href="/softnas/applets/update">Apply the latest Software Update</a></p>
<p>If the problem persists, you can <a href="https://www.softnas.com/wp/support/" target="_blank">Contact Support</a> if you are unsure why this is happening or need assistance to resolve it.</p>
<p><h3><strong><a href="/softnas/">Click Here to resume normal operation</a></strong> after the issue has been resolved.</h3></p>
<br />
<h3>What does an invalid license condition do?</h3>
<p>Exceeding the licensed storage capacity or operating with an invalid license condition causes this error message to appear and limits the available StorageCenter&trade; administration features that are available until it is corrected. Certain background operations such as scheduled snapshots, replication and other tasks may also be suspended due to the invalid license condition, so it is important to resolve this issue in a timely manner.</p>
<p>&nbsp;</p>
<p></p>
</div>
</div>
</body>
</html>

