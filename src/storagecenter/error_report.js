function getObjectProperties(obj) {
	if (Object.keys && Object.keys !== undefined) {
		return Object.keys(obj);
	} else {  // #4533 (IE8)
		var i = 0, obj_properties = [];
		for (i in obj) {
			if (obj.hasOwnProperty(i)) {
				obj_properties.push(i);
			}
		}
		return obj_properties;
	}
}

function getClientInfo() {
	if (window.top.client_browser_info !== undefined) {
		return window.top.client_browser_info;
	}
	var browser = "";
	var version = "";
	var os_name = "";
	var agent = navigator.userAgent.toLowerCase();
	var client_info = {browser: "", version: "", os: ""};
	
	// Finding OS: ( http://stackoverflow.com/questions/9514179/how-to-find-the-operating-system-version-using-javascript )
	if (agent.indexOf("windows") != -1) {client_info.os = "Windows";}
	if (agent.indexOf("windows nt 6.2") != -1) {client_info.os = "Windows 8";}
	if (agent.indexOf("windows nt 6.1") != -1) {client_info.os = "Windows 7";}
	if (agent.indexOf("windows nt 6.0") != -1) {client_info.os = "Windows Vista";}
	if (agent.indexOf("windows nt 5.1") != -1) {client_info.os = "Windows XP";}
	if (agent.indexOf("windows nt 5.0") != -1) {client_info.os = "Windows 2000";}
	if (agent.indexOf("mac")!=-1) {client_info.os = "Mac/iOS";}
	if (agent.indexOf("x11")!=-1) {client_info.os = "UNIX";}
	if (agent.indexOf("linux")!=-1) {client_info.os = "Linux";}
	
	// Finding browser: ( http://stackoverflow.com/questions/9847580/how-to-detect-safari-chrome-ie-firefox-and-opera-browser )
	if ((!!window.opr && !!opr.addons) || !!window.opera || navigator.userAgent.indexOf(' OPR/') >= 0) {
		client_info.browser = "Opera"; // Opera 8.0+
		client_info.version = getClientVersion(agent, 'opr/');
	}
	if (typeof InstallTrigger !== 'undefined') {
		client_info.browser = "Firefox"; // Firefox 1.0+
		//client_info.version = agent.split('firefox/')[1].split(' ')[0];
		client_info.version = getClientVersion(agent, 'firefox/');
	}
	if (Object.prototype.toString.call(window.HTMLElement).indexOf('Constructor') > 0) {
		client_info.browser = "Safari"; // At least Safari 3+: "[object HTMLElementConstructor]"
		client_info.version = getClientVersion(agent, 'safari/');
	}
	if (/*@cc_on!@*/false || !!document.documentMode) {
		client_info.browser = "IE"; // Internet Explorer 6-11
		client_info.version = getClientVersion(agent, 'msie ');
	}
	if (client_info.browser != "IE" && !!window.StyleMedia) {
		client_info.browser = "Edge"; // Edge 20+
	}
	if (!!window.chrome && !!window.chrome.webstore) {
		client_info.browser = "Chrome"; // Chrome 1+
		client_info.version = getClientVersion(agent, 'chrome/');
	}
	if (agent.indexOf('maxthon') != -1) {
		client_info.browser = "Maxthon"; // Maxthon - Added 07.Mar.2016
		client_info.version = getClientVersion(agent, 'maxthon/');
	}
	window.top.client_browser_info = client_info;
	return client_info;
}

function getClientVersion(agent, browser_name) {
	var version = "";
	var version_arr = agent.split(browser_name);
	if (version_arr.length > 1) {
		version = version_arr[1].split(' ')[0];
	}
	return version;
}


function getLoggedErrors() {
	if (window.top.logged_errors === undefined) {
		window.top.logged_errors = {};
	}
	return window.top.logged_errors;
}

function setLoggedErrors(value) {
	window.top.logged_errors = value;
}

method_name = ((window.addEventListener && window.addEventListener !== undefined) ? "addEventListener" : "attachEvent"); // #4533 (IE8)
window[method_name]( "error", function(e){
	
	// avoid recursive error events if errors are in this file:
	if (e.filename.indexOf("storagecenter/error_report.js") !== -1) { return; }
	
	var coords = 'pos_' + e.lineno + '_' + e.colno;
	var errors = getLoggedErrors();
	var file = e.filename;
	if (!file || file == "") { // if made from console
		return;
		//file = "VM";
	}
	if (errors[file] === undefined) {
		errors[file] = {};
	}
	if (errors[file][coords] === undefined) {
		errors[file][coords] = {
			message: e.message,
			time: Date.now(),
			count: 1
		};
	} else {
		if (errors[file][coords].count === undefined) {
			errors[file][coords].count = 1;
		}
		// if error already happened in current interval, just increase count
		errors[file][coords].count++;
	}
	setLoggedErrors(errors);
});

var err_xhttp = new XMLHttpRequest();
var send_errors_interval = 60000;

function sendErrorsToServer() {
	
	var errors = getLoggedErrors();
	if (getObjectProperties(errors).length == 0) {  // #4533 (IE8)
		return; // do not send if no new errors
	}
	var client_info = getClientInfo();
	
	var send_params = "opcode=log_js_errors";
	send_params+= "&browser="+client_info.browser;
	send_params+= "&browser_version="+client_info.version;
	send_params+= "&os="+client_info.os;
	send_params+= "&js_errors="+JSON.stringify(errors);
	
	err_xhttp.open("POST", "/softnas/snserver/snserv.php", true);
	err_xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	err_xhttp.send(send_params);
	
	// empty list of errors after it is saved in server
	setLoggedErrors({});
}

setInterval(function() { sendErrorsToServer(); }, send_errors_interval );
