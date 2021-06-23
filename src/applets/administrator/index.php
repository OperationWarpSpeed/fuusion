<!DOCTYPE html>
<?php 
require_once "../../snserver/utils.php";
$disable_caching = (is_updated($_SERVER["SCRIPT_FILENAME"]) ? "false" : "true");
$url_sufix = ($disable_caching == "true" ? "?_dc=".time() : "");
?>
<html>
<head>
    <META NAME="COPYRIGHT" CONTENT="Copyright &copy; SoftNAS Inc., All Rights Reserved. ">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    
    <link rel="shortcut icon" href="https://www.softnas.com/favicon.ico"/>
    <link rel="shortcut icon" href="https://www.softnas.com/favicon.ico" type="image/x-icon" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

    <title><?php echo $_SERVER['HTTP_HOST']." - Administrator";?></title>
    <script type="text/javascript" src="/softnas/js/overrides.js<?php echo $url_sufix ?>"></script>
<script type="text/javascript" src="/softnas/storagecenter/error_report.js<?php echo $url_sufix ?>"></script>
<link rel="stylesheet" type="text/css" href="/softnas/css/loadstyle.css<?php echo $url_sufix ?>" />

</head>
<body>

<div id="loading-mask"></div>
<div id="loading">
   <div class="loading-indicator">
    <div style="text-align:center">
      <img style="width: 55px;" src="/softnas/images/logo-dsphere-indicator.gif" id="loading-img"/>
      <img style="width: 250px" src="/softnas/images/logo-no-dsphere-300.png"/><br>
      <div id="text-msg">&nbsp&nbspLoading application...</div>
    </div>
  </div>
</div>


<script type="text/javascript" src="/softnas/storagecenter/lib.js<?php echo $url_sufix ?>"></script>
<script type="text/javascript" src="/extjs_5.1/build/ext-all.js"></script>
<script src="/extjs_5.1/build/packages/ext-theme-classic/build/ext-theme-classic.js"></script>
<link rel="stylesheet" type="text/css" href="/extjs_5.1/build/packages/ext-theme-classic/build/resources/ext-theme-classic-all.css"/>
<link rel="stylesheet" type="text/css" href="css/custom.css<?php echo $url_sufix ?>"/>
<script type="text/javascript">Ext.Loader.setConfig({enabled: true, disableCaching: <?php echo $disable_caching ?> });</script>
<script type="text/javascript" src="/softnas/js/overrides-extjs.js<?php echo $url_sufix ?>"></script>
<script type="text/javascript" src="app.js<?php echo $url_sufix ?>"></script>

<script type="text/javascript">
window.addEventListener("load", function() {
 // eliminate the loading indicators
  var loading=document.getElementById("loading");
  if(loading)document.body.removeChild(loading);
  // eliminate the loading mask so application shows
  var mask=document.getElementById("loading-mask");
  if(mask)document.body.removeChild(mask);
});
</script>

</body>
</html>
