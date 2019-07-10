// alert("dashboard.js: loaded");

function colorChangedControl(control) {
	var changed;
	var tr = document.getElementById("tr_" + control.id);
	var change_count = document.getElementById("change_count");
	var i;
	var my_form = control.form;
	// alert("colorChangedControl(): start");
	// alert("colorChangedControl(): control.id = " + control.id + "  tr.id = " + tr.id);
	
	
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
	// alert("colorChangedControl(): changed = " + changed);
	
	
	// Turn the background of an unsaved change to the "changed color"
	
	if (changed) {
		if (tr.style.backgroundColor == unchanged_color) {
			change_count.value++;
		}
		control.style.backgroundColor = changed_color;
		tr.style.backgroundColor = changed_color;
	} else {
		if (tr.style.backgroundColor == changed_color) {
			change_count.value--;
		}
		control.style.backgroundColor = unchanged_color;
		tr.style.backgroundColor = unchanged_color; 
	}
	// alert("colorChangedControl(): change_count = " + change_count);
	
	
	// Show save changes buttons only when there are unsaved changes
	
	var save_actions_rows = document.getElementsByName("save_actions_row");
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


