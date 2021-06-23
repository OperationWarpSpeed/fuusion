document.addEventListener("DOMContentLoaded", function() {
	
	/**
	 * Form validation for  CIFS Shares -> Create a new file share
	 * #4895
	 * Nemanja Lazc <nlazic@softnas.com>
	 **/ 
	var form = document.querySelector('[action="save_fshare.cgi"]');
	
	if (typeof form === 'undefined') {
		return;
	}

	var submitButton = form.querySelector('[value="Save"]');

	if (typeof submitButton === 'undefined') {
		return;
	}

	submitButton.addEventListener("click", function(event) {
		event.preventDefault();
		
		var share, radioButton;

		share = document.getElementById('share');
		radioButton = document.getElementById('homes_0');

		if (share == null || radioButton == null) {
			// #5179
			form.submit();
			return;
		}

		if ( share.value.trim() == "" && radioButton.checked ) {
			
			window.top.Ext.Msg.alert("Validation failed","Error: " + 
					"Invalid share name");

			return;
		}

		//submit if no error
		form.submit();
	}); 
});
