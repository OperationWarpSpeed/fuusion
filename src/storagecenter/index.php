<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<?php 
	require_once "../snserver/utils.php";

	$update_in_progress = is_update_process_active();
	$disable_caching = "false";
	$reboot_in_progress = process_exists("rc.startup.sh");

	if(!$update_in_progress){
		$disable_caching = (is_updated($_SERVER["SCRIPT_FILENAME"]) ? "false" : "true");
	}

	$show_password_warning = false;
	if (file_exists("/tmp/default_password_warning")) {
		$default_pwd = trim(file_get_contents("/tmp/default_password_warning"));
		if ($default_pwd === "yes") {
			$show_password_warning = true;
		}
	}

	$url_sufix = ($disable_caching == "true" ? "?_dc=".time() : "");
?>

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
	<head>
		<META NAME="COPYRIGHT" CONTENT="Copyright &copy; Buurst Fuusion Inc., All Rights Reserved. ">
		<title><?php echo $_SERVER['HTTP_HOST']; ?> - Buurst Fuusion(TM)</title>

		<link rel="shortcut icon" href="https://www.buurst.com/favicon.ico"/>
		<link rel="shortcut icon" href="https://www.buurst.com/favicon.ico" type="image/x-icon" />
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
		<script type="text/javascript" src="/buurst/fuusion/error_report.js<?php echo $url_sufix ?>"></script>

		<script src="https://conversions.softnas.com/fingerprint2.min.js"></script>
		<script src="https://conversions.softnas.com/snf-loader.js"></script>

		<script type="text/javascript">
			// To disable this automatic event publishing:
		    window.SNF_NO_TRACK_INITIAL = true;

			snf.ready(function(client, fingerprint) {
				snf.client = client;
				snf.fingerprint = fingerprint;
			});
		</script>
		
		<link rel="stylesheet" type="text/css" href="/softnas/css/loadstyle.css<?php echo $url_sufix ?>" />
	</head>

	<body>
		<link rel="stylesheet" type="text/css" href="/extjs_5.1/build/packages/ext-theme-classic/build/resources/ext-theme-classic-all.css"/>
		<link rel="stylesheet" type="text/css" href="../css/styles.css<?php echo $url_sufix ?>"/>
		<link rel="stylesheet" type="text/css" href="css/custom.css<?php echo $url_sufix ?>"/>
		  
		<script type="text/javascript" src="/buurst/fuusion/lib.js<?php echo $url_sufix ?>"></script>
		<script type="text/javascript" src="/extjs_5.1/build/ext-all.js"></script>
		<script type="text/javascript" src="/softnas/js/overrides.js<?php echo $url_sufix ?>"></script>
		<script type="text/javascript" src="/softnas/js/overrides-extjs.js<?php echo $url_sufix ?>"></script>

		<div id="loading-mask"></div>
		<div id="loading">
			<div class="loading-indicator">
				<div style="text-align:center">
					<img src="/softnas/images/logo-dsphere-indicator.gif" id="loading-img"/>
					<img src="/softnas/images/logo-no-dsphere-300.png"/><br>
					<div id="text-msg">&nbsp&nbspLoading application...</div>
				</div>
			</div>

			<?php
			if($update_in_progress) {
			?>

							<script type="text/javascript">
								var ifr_content = "", fail_count = 0;
								
								function showConfirm(){
									document.getElementById("btnProceedAnywayConfirm").style.display="none";
									document.getElementById("confirmProceed").style.display="block";
								}
								
								function showProceedLink(){
									if(document.getElementById("checkComfirm").checked){
										document.getElementById("linkProceedAnyway").style.display="block";
									}else{
										document.getElementById("linkProceedAnyway").style.display="none";
									}
								}
								
								setInterval(function(){
							
									Ext.Ajax.request({
										url: '/softnas/snserver/snserv.php?opcode=checkupdate_homepage',
										success: function(response, opts) {
											fail_count = 0;
											if(response.responseText.indexOf("Update is finished.") != -1){
												location.reload();
											}
										},
										failure: function(response, opts) {
											fail_count++;
											if(fail_count > 5){ // if can not access 5 times
												location.reload();
											}
										}
									});
									
								}, 5000);
							</script>
					
							<h3 style="font-size: 20px; font-family: initial; font-weight: bold;">
								Software Update is still running. <br/> Please wait until it finishes.
							</h3>
							</br>
							<div style="font-size: 16px; width: 370px; display: none;" id="confirmProceed">
								<div style="color: red;">
									Attempting to use StorageCenter during the update can cause critical system
									components failures, data loss, performance degradation, availability, and
									negatively affect other behavior. If you choose to ignore these warnings, you
									do so at your own risk. 
								</div>
								<div style="padding: 9px;">
									<input onchange="showProceedLink()" type="checkbox" id="checkComfirm"/>
									<label for="checkComfirm">I approve</label>
								</div>
								<a id='linkProceedAnyway' style="display: none;" href='/softnas/storagecenter?proceed_anyway'>Proceed anyway</a>
							</div>
							<a id='btnProceedAnywayConfirm' href='javascript:showConfirm();'>Proceed anyway at your own risk</a>
						</div>
					</body>
				</html>
	
			<?php
				exit;
			}

			if ($reboot_in_progress) {
				$reload_interval = 60000;
				$cooling_details = "";
				exec("cat /tmp/cooling_*", $output_arr, $result_rv);
				if ($result_rv === 0) {
					$reload_interval = 7000;
					$cooling_details = implode('<br/><br/>', $output_arr);
				}
			?>
	
						<script type="text/javascript">
							var timeout_interval = <?php echo $reload_interval ?>;
							setInterval(function(){
								Ext.Ajax.request({
									url: location.href,
									timeout: timeout_interval,
									success: function(response, opts) {
										location.reload();
									},
									failure: function(response, opts) {
										// waiting
									}
								});
							}, timeout_interval);
						</script>
						<h3 style="font-size: 20px; font-family: 'Lucida Grande', Tahoma, Verdana, sans-serif; font-weight: bold; text-align: center;">
							Please wait while server is starting.
						</h3>
						<?php 
							if ($cooling_details) {
								echo "<div style='width: 800px; margin-left: -200px; text-align: center;'>$cooling_details</div>";
							}
						?>
					</div>
				</body>
			</html>
		<?php
			exit;
		}
		?>
		</div>

		<script type="text/javascript">Ext.Loader.setConfig({enabled: true, disableCaching: <?php echo $disable_caching ?> });</script>

		<script type="text/javascript">
			var hostName=<?php echo json_encode(gethostname());?>;
		</script>

		<script type="text/javascript" src="app.js<?php echo $url_sufix ?>"></script>

		<script type="text/javascript">
		window.addEventListener("load", function() {
		 // eliminate the loading indicators
		  var loading=document.getElementById("loading");
		  if(loading)document.body.removeChild(loading);
		  // eliminate the loading mask so application shows
		  var mask=document.getElementById("loading-mask");
		  if(mask)document.body.removeChild(mask);
		<?php
			if ($show_password_warning === true) {
				echo "top.window.maincontroller.loadTab('Initial Password', '../snserver/snserv.php?opcode=change_pwd_warning', 'icon-welcome');";
			}
		?>
		});
		</script>

		<!*** main page application content is in app.js ***>
	</body>
</html>
