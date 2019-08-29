var script_url = 	document.getElementById("script_url").value;
var changed_color = document.getElementById("changed_background_color").value;
var unchanged_color = document.getElementById("unchanged_background_color").value;

if (0) console.log("utils2.js start");

// Disable 'Enter' key to submit a form
// Code from https://stackoverflow.com/questions/895171/prevent-users-from-submitting-a-form-by-hitting-enter
$(document).ready(function() {
	$(window).keydown(function(event){
		var kc = event.which || event.keyCode; // Firefox only recognizes "event.which"
		if(kc == 13) {
			event.preventDefault();
			return false;
		}
	});
});
 

// Save old value of an input field before onchange event
// Code from https://stackoverflow.com/questions/29118178/input-jquery-get-old-value-before-onchange-and-get-value-after-on-change
// jquery .val is documented at https://api.jquery.com/val/
$(document).on('mousedown', ':input', function(){
	if (0) console.log("\n********************* utils2.js:");
	if (0) console.log("utils2.js: Saving value " + $(this).val()); 
	$(this).data('val', $(this).val());
	if (0) console.log("utils2.js: $(this).val() = " + $(this).val());
	if (0) console.log("utils2.js: $(this).data('val') = " + $(this).data('val'));
});


function modifyInputFieldsOfForm(this_form, field_action, button_action){ 	
	// Enable/disable/show/hide all input fields in a form
	var my_inputs = $(this_form).find("input");
	var my_input;
	var my_type;

	for (i = 0; i < my_inputs.length; i++) {
		my_input = my_inputs[i];  
		my_type = $(my_input).attr('type');
		if (['submit', 'reset'].includes(my_type)) {
			switch (button_action) {
				case "enable":
					my_input.disabled = false;
					break;
				case "disable":
					my_input.disabled = true;
					break;
				case "show":
					my_input.style.display = "inline";
					break;
				case "hide":
					my_input.style.display = "none";
					break;
			}
		} else if (!['hidden', 'file'].includes(my_type)) {
			switch (field_action) {
				case "enable":
					my_input.disabled = false;
					break;
				case "disable":
					my_input.disabled = true;
					break;
				case "show":
					my_input.style.display = "block";
					break;
				case "hide":
					my_input.style.display = "none";
					break;
			}
		}
	} 
}


function setFormAction(form_id, action) { 
	// Change the submit action for this form.  Usually invoked by onclick from a submit button.
	document.getElementById(form_id).action = action;
}


// When user clicks on a checked radio button, set it to unchecked
// This enables user to revert a radio button set that already has a checked button to having none checked
function nullRadio() {
	document.querySelectorAll("INPUT[type='radio']").forEach(function(this_radio){
		this_radio.addEventListener("mousedown", function(){
			if (this == this_radio) {
				if (this.checked == true) { 
					this.checked = false;
				} else {
					this.checked = true;
				}
				if (script_url == "/change_sets.php") markUndos(this_radio);  
			} 
		})
	})
}

function elAtts(label, elt) {
	if (elt) {
		var s = label + ":";
		Array.prototype.slice.call(elt.attributes).forEach(function(item) {
			s = s + ('\n    ' + item.name + ': "'+ item.value + '"');
		})
		return s;	
	} else {
		return label + ": " + null;
	}
}

function obAtts(label, elt) {
	if (elt) {
		return label + ": " + JSON.stringify(elt, null, 4);
	} else {
		return label + ": " + null;
	}
}

// From https://stackoverflow.com/questions/4672984/how-to-change-toggle-text-on-show-hide-using-javascript
function showHide(divid, linkid) { 
	if (0) console.log("divid = " + divid + " linkid = " + linkid);
	var divid = document.getElementById(divid);
	var toggleLink = document.getElementById(linkid);
	if (divid.style.display == 'block') {
			// toggleLink.innerHTML = 'click to hide';
			toggleLink.innerHTML = '<img src="display/images/triangle_pointing_right.png" alt="" height="16" width="16"></img><span style="font-size: 9pt; "> click to show </span>';
			// toggleLink.innerHTML = '<img src="display/images/triangle_pointing_right.png" alt="click to show" height="16" width="16">';
			divid.style.display = 'none';
	}
	else {
			// toggleLink.innerHTML = '<img src="display/images/triangle_pointing_down.png" alt="click to hide" height="16" width="16">'; 
			// toggleLink.innerHTML = 'click to show'; 
			toggleLink.innerHTML = '<img src="display/images/triangle_pointing_down.png" alt="click to hide" height="16" width="16"></img><span style="font-size: 9pt; "> click to hide </span>'; 
			divid.style.display = 'block';
	}
	return false;
}