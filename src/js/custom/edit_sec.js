document.addEventListener("DOMContentLoaded", function() {
	
	/**
	 * Form validation for CIFS settings -> edit security
	 * #4042
	 * Nemanja Lazc <nlazic@softnas.com>
	 **/ 
	var form = document.querySelector('[action="save_sec.cgi"]');
	
	if (typeof form === 'undefined') {
		return;
	}

	form.addEventListener("submit", function(event) {
		event.preventDefault();
		
		var hostAllow, hostDeny, validUser, invalidUser, same= null;

		hostAllow   = document.getElementById('allow_hosts');
		hostDeny    = document.getElementById('deny_hosts');
		validUser   = document.getElementById('valid_users_u');
		invalidUser = document.getElementById('invalid_users_u');

		if (hostAllow.value != "") {
			
			hostsA = hostAllow.value.split(" ");
			hostsD = hostDeny.value.split(" ");

			//get same users in both  arrays
			same = hostsA.filter(function(n) {
				return hostsD.indexOf(n) != -1;
			});

			if (same != null && same.length > 0) {

				var plural = " host";
				if (same.length > 1) {
					plural = " hosts"
				}

				window.top.Ext.Msg.alert("Validation failed","Error: '" + 
					same.toString() + "'" + plural
					+ ", can't be allowed and denied at the same time.");
				return;
			}
		}

		if (validUser.value != "") {
			
			validU = validUser.value.split(" ");
			invalidU = invalidUser.value.split(" ");

			//get same users in both  arrays
			same = validU.filter(function(n) {
				return invalidU.indexOf(n) != -1;
			});

			if (same != null && same.length > 0) {
				
				var plural = " user";
				if (same.length > 1) {
					plural = " users"
				}

				window.top.Ext.Msg.alert("Validation failed", "Error: '" + 
					same.toString() + "'" + plural 
					+ ", can't be valid and invalid at the same time.");
				return;
			}
		}

		//submit if no error
		form.submit();
	}); 
});
