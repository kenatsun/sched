var script_url = 	document.getElementById("script_url").value;
var changed_color = document.getElementById("changed_background_color").value;
var unchanged_color = document.getElementById("unchanged_background_color").value;

// console.log("utils2.js start");

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
// $(document).on('focus', ':input', function(){
$(document).on('mousedown', ':input', function(){
	// alert ("Hi world!");
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

