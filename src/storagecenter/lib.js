
function show_pendingreboot_message(pendingreboot) {
	
	if (pendingreboot === undefined || !pendingreboot) {
		return;
	}
	
	Ext.Msg.show({
		title:'Instance needs to be rebooted',
		msg: pendingreboot,
		buttons: Ext.Msg.YESCANCEL,
		buttonText: {'yes': 'Reboot Now', 'cancel': 'Continue Anyway'},
		fn: function(btn){
			if (btn == 'yes') {
				Ext.Msg.confirm('Reboot', 'Are you sure? Storage services will be unavailable while the system is restarted.',
				function(btn){
					if (btn == 'yes') {
						window.top.reboot_message = true; // #3528 , #3877
						Ext.Ajax.request({
							url: "/softnas/snserver/snserv.php?opcode=restart",
							scope:  this,
							method : 'POST',
							params : {
								opcode: 'restart',
							},
							timeout: 30000,
							success: function(response, opts) 
							{
								if(response !== undefined)
								{
									console.log(response);
									if (response.responseText !== undefined) {
    									var reply = Ext.decode(response.responseText);
									    if (reply && reply.success !== undefined) {
									        if (reply.success == true) {
                        						Ext.Msg.alert('Reboot', 'Rebooting...');
            									//window.top.location.href = "/softnas/logout.php";
            									window.top.location.reload();
									            return true;
									        } else {
									            var err_msg = reply.msg !== undefined ? reply.msg : "Unknown error";
									            Ext.Msg.alert('Reboot failed', err_msg);
									            return false;
									        }
									        return false;
									    }
									}
								}
								return true;
							},
							failure: function(response, opts) {
								console.log(response);
								var reply;
								Ext.Msg.alert('Fail', 'Request failed');
							}
						});
					}
				});
			}
			if (btn == 'cancel') {
				set_reboot_warning_seen();
			}
		}
	});
}

function show_reboot_message_for_s3() {
	var s3_message = getCookie("pendingreboot3025");
	if (s3_message && s3_message != "") {
		setTimeout(function(){ show_pendingreboot_message(s3_message); }, 300);
	}
}

function is_reboot_warning_seen() {
	var seen = getCookie("reboot_warning_seen");
	if (seen && seen != "") {
		return true;
	} else {
		return false;
	}
}
function set_reboot_warning_seen() {
	setCookie("reboot_warning_seen", "true", "session");
}

function deleteAllRebootCookies() {
	var cookies = window.top.document.cookie.split(";");
	
	for (var i = 0; i < cookies.length; i++) {
		var cookie = cookies[i];
		var eqPos = cookie.indexOf("=");
		var name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
		name = name.trim();
		if(name.indexOf("pendingreboot") === 0){
			window.top.document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
		}
	}
}

function setCookie(cname, cvalue, exdays) {
	var d = new Date();
	d.setTime(d.getTime() + (exdays*24*60*60*1000));
	var expires = "expires="+d.toUTCString();
	if(exdays == 'session') {
		expires = "";
	}
	window.top.document.cookie = cname + "=" + cvalue + "; " + expires;
}

function getCookie(cname) {
	var name = cname + "=";
	var ca = window.top.document.cookie.split(';');
	for(var i=0; i<ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1);
		if (c.indexOf(name) == 0) return c.substring(name.length, c.length);
	}
	return "";
}

function simplePopupMessage(title, message) {
	
	// #3954, #3957, #3958 - Find previous message and destroy it if it exists
	var prevMsg = Ext.get('msg-div');
	if(prevMsg) {
		prevMsg.destroy();
	}
	
	msgCt = Ext.core.DomHelper.insertFirst(document.body, {id:'msg-div'}, true);
	var msgHtml = '<div class="msg"><h3>' + title + '</h3><p>' + message + '</p></div>';
	var msgBox = Ext.core.DomHelper.append(msgCt, msgHtml, true);
	msgBox.hide();
	msgBox.slideIn('t').ghost("t", { delay: 1500, remove: true});
}