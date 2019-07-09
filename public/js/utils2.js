var script_url = 	document.getElementById("script_url").value;
var changed_color = document.getElementById("changed_background_color").value;
var unchanged_color = document.getElementById("unchanged_background_color").value;

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


function modifyInputFieldsOfForm(this_form, field_action, button_action){
	// Enable/disable/show/hide all input fields in a form
	var my_inputs = $(this_form).find("input");
	var my_input;
	var my_type;

	// alert("modifyInputFieldsOfForm(): disable_fields? " + disable_fields);
	// alert("modifyInputFieldsOfForm(): gonna " + field_action + " all fields and " + button_action + " all buttons of form '" + $(this_form).attr('name') + "', which contains " + my_inputs.length + " input fields");
	for (i = 0; i < my_inputs.length; i++) {
		my_input = my_inputs[i];  
		my_type = $(my_input).attr('type');
		// alert(i + ": " + $(my_input).attr('name') + " is a " + my_type + " input in form " + $(this_form).attr('name') + "   with value = '" + $(my_input).attr('value') + "' , and just got " + field_action + "d");
		if (['submit', 'reset'].includes(my_type)) {
			// alert(i + ": " + $(my_input).attr('name') + " is a " + my_type + " input in form " + $(this_form).attr('name') + "   with value = '" + $(my_input).attr('value') + "' , and just got " + field_action + "d");
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
			// alert(i + ": " + $(my_input).attr('name') + " is a " + my_type + " input in form " + $(this_form).attr('name') + "   with value = '" + $(my_input).attr('value') + "' , and I just got " + field_action + "d - that is, my disabled att = " + $(my_input).attr('disabled'));
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
			// alert(i + ": " + $(my_input).attr('name') + " is a " + my_type + " input in form " + $(this_form).attr('name') + "   with value = '" + $(my_input).attr('value') + "' , and I just got " + field_action + "d - that is, my disabled att = " + $(my_input).attr('disabled'));				
		}
	} 
}


function colorChangedControl(control) {
	var changed;
	var tr = document.getElementById("tr_" + control.id);
	var change_count = document.getElementById("change_count");
	var i;
	var my_form = control.form;
	// alert("colorChangedControl(control): my_form = " + my_form.id);
	
	
	// Determine whether this control is set to do a change 
	
	if (control.type == "select-one") {
		// First option in a select control means "no change"
		if (control.selectedIndex == "0") {
			changed = false;
		} 
		// Any other option in a select control means "change"
		else {
			changed = true;
		}
	}	else if (control.type == "checkbox") { 
		// An unchecked checkbox means "no change"
		if (control.checked) {
			changed = true;
		} 
		// An unchecked checkbox means "change"
		else {
			changed = false;
		}
	}
	
	// Turn the background of an unsaved change to the "changed color"
	
	if (changed) {
		if (tr.style.backgroundColor == unchanged_color) {
			// alert ("Incrementing changed_count");
			change_count.value++;
		}
		control.style.backgroundColor = changed_color;
		tr.style.backgroundColor = changed_color;
	} else {
		if (tr.style.backgroundColor == changed_color) {
			// alert ("Decrementing changed_count");
			change_count.value--;
		}
		control.style.backgroundColor = unchanged_color;
		tr.style.backgroundColor = unchanged_color; 
	}
	
	// Show save changes buttons only when there are unsaved changes
	
	var save_actions_rows = document.getElementsByName("save_actions_row");
	// alert("colorChangedControl(control): change_count.value = " + change_count.value + " \nsave_actions_rows.length = " + save_actions_rows.length);
	// for (i = 0;  i < save_actions_rows.length; i++) { 
		// alert ("i = " + i);
		// if (change_count.value > 0) {
			// modifyInputFieldsOfForm(my_form, "", "show");
		// } else {
			// modifyInputFieldsOfForm(my_form, "", "hide");
		// }
	// }
	for (i = 0;  i < save_actions_rows.length; i++) { 
		if (change_count.value > 0) {
			save_actions_rows[i].style.display = "table-row";
		} else {
			save_actions_rows[i].style.display = "none";
		}
	}
	
	// Show publish buttons only when there are no unsaved changes
	
	var publish_buttons = document.getElementsByName("publish_buttons");
	for (i = 0;  i < publish_buttons.length; i++) { 
		if (change_count.value > 0) {
			publish_buttons[i].style.display = "none";
		} else { 
			publish_buttons[i].style.display = "inline";	
		}
	}
} 

function setFormAction(form_id, action) { 
	// Change the submit action for this form.  Usually invoked by onclick from a submit button.
	document.getElementById(form_id).action = action;
}


function publishTeams(URL) {
	// alert ("publishTeams(): URL = " + URL); 
	var editions = document.getElementsByName("edition");
	var edition;
	// alert ("publishTeams(): editions = " + editions.length);
	for (i = 0; i < editions.length; i++) {
		// alert ("publishTeams(): editions.value = " + editions[i].value + " checked = " + editions[i].checked);
		// alert ("publishTeams(): editions.checked = " + editions[i].checked);
		if (editions[i].checked) {
			edition = editions[i].value;
			// alert ("publishTeams(): edition = " + edition);
			window.open(URL + edition); 
			return false;			
			// return editions[i].value; 
		}
	}
	alert ("You must select an edition");
}

function confirmDeleteSeason(id, name) {
	var txt;
	var r = confirm("Permanently delete the " + name + " season?");
	if (r == true) {
	let form = document.createElement('form');
	form.action = "seasons.php";
	form.method = 'POST';
	form.innerHTML = '<input name="delete_season" value="' + id + '">';
	document.body.append(form);
	form.submit();
	txt = "Season " + name + " has been deleted!";
	} else {
	txt = "Season " + name + " won't be deleted";
	}
	document.getElementById("result").innerHTML = txt;
}

function setSeasonFullName() {
	// var changed;
	var name_without_year = document.getElementById("name_without_year").value;
	var year = document.getElementById("year").value;
	var separator;
	if (name_without_year && year) separator = " "; else separator = "";
	var full_name = name_without_year + separator + year;
	// alert ("full_name = " + full_name); 
	document.getElementById("full_name").innerHTML = full_name;
	document.getElementById("name").value = full_name;
	// return false;
}

// When user clicks on a checked radio button, set it to unchecked
// This enables user to revert a radio button set that already has a checked button to having none checked
function nullRadio() {
	document.querySelectorAll("INPUT[type='radio']").forEach(function(this_radio){
		this_radio.addEventListener("mousedown", function(){
			// var script_url = 	document.getElementById("script_url").value;
			// var script_url = $("#script_url").value;
			// alert ("nullRadio(): script_url = " + script_url);
			// alert ("nullRadio(): this.checked? " + this.checked + "  this.value = " + this.value + "  this_radio.value = " + this_radio.value);
			if (this == this_radio) {
				if (this.checked == true) { 
					this.checked = false;
					// alert ("nullRadio(): value = " + this.value + " - set to false"); 
					// markUndos(this_radio);
				} else {
					this.checked = true;
					// alert ("nullRadio(): value = " + this.value + " - set to true");
					// markUndos(this_radio);
				}
				if (script_url == "/change_sets.php") markUndos(this_radio);  
			}
		})
	})
}

function toggleSignIn(user_is_admin) {
	alert("toggleSignIn(): user_is_admin = " + user_is_admin);
	var sign_in_as = document.getElementById("sign_in_as");
	if (user_is_admin == 1) {
		sign_in_as.value = "guest";
	} else {
		sign_in_as.value = "admin";
	}
	alert("toggleSignIn(): sign_in_as.value = " + sign_in_as.value);
}

function getPassword(new_status) {
	// alert("getPassword(): new_status = " + new_status);
	if (new_status == "admin") {
		var clink = document.getElementById("community_logo_link");
		alert ("getPassword(): clink = " + clink);
		alert ("getPassword(): clink.href = " + $(clink).attr("href"));
		var retVal = prompt("Enter admin password: ", "");
		if (retVal != "a") {
			alert ("Incorrect password: admin access denied.");
			alert ("getPassword(): clink before = " + clink);
			clink.value = clink.replace("sign_in_as=admin", "sign_in_as=guest");
			alert ("getPassword(): clink after = " + clink);
			return false;
		}
		// document.write("You have entered : " + retVal);		
	}
}