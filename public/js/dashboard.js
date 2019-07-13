function updateChangeControlDisplay(control, shift_id="", worker_id="") {
	var changed;
	var my_tr = document.getElementById("tr_" + control.id);
	var change_count = document.getElementById("change_count");
	var i;
	// var my_form = control.form;
	var remove_control_names = ["remove[]", "move[]", "trade[]"];
	var my_name = control.name;
	// alert("updateChangeControlDisplay(): start");
	// alert("updateChangeControlDisplay(): control.id = " + control + "  shift_id = " + shift_id); 
	
	
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
	// alert("updateChangeControlDisplay(): changed = " + changed);
	
	
	// Turn the background of an unsaved change to the "changed color"
	
	if (changed) {
		if (my_tr.style.backgroundColor == unchanged_color) {
			change_count.value++;
		}
		control.style.backgroundColor = changed_color;
		my_tr.style.backgroundColor = changed_color;
	} else {
		if (my_tr.style.backgroundColor == changed_color) {
			change_count.value--;
		}
		control.style.backgroundColor = unchanged_color;
		my_tr.style.backgroundColor = unchanged_color; 
	}
	// alert("updateChangeControlDisplay(): change_count = " + change_count);

	
	// If change action would remove this person from this shift,
	// hide the other controls that would do the same thing
	// alert ("control.name = " + control.name);
	if (remove_control_names.includes(my_name)) {
		// alert ("class = " + "shift_control_" + shift_id);
		var shift_controls = document.getElementsByClassName("shift_control_" + shift_id);
		// alert ("shift_controls = " + shift_controls);
		// alert ("shift_controls.length = " + shift_controls.length);
		var control_names = "";
		for(i = 0;  i < shift_controls.length; i++) {
			var other_name = shift_controls[i].name;
			var other_tr = document.getElementById("tr_" + shift_controls[i].id);
			if ((other_name != my_name) && (remove_control_names.includes(other_name))) {
				if (changed == true) {
					// alert ("gonna hide " + other_name);
					$("#" + other_tr.id).hide();
				} else {
					// alert ("gonna show " + other_name);
					$("#" + other_tr.id).show();
				}
			}
		}
	}
	
	// When a person is moved from one shift to another, 
	// show the change on both shifts
	
	
	
	// Show save & cancel changes buttons only when there are unsaved changes
	
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

function resetFormDisplay() {
	// alert ("resetFormDisplay(): start");
	$("#change_count").attr("value", 0);			// No pending changes
	$("tr[name=change_control_tr]").show();		// Show all the change control trs
	$("tr[name=change_control_tr]").css("background-color", unchanged_color);	// Mark all change control trs as unchanged
	$("[name*=move]").css("background-color", unchanged_color);		// Mark "move" change controls as unchanged
	$("[name*=trade]").css("background-color", unchanged_color);	// Mark "trade" change controls as unchanged
	$("[name*=add]").css("background-color", unchanged_color);		// Mark "add" change controls as unchanged
	$("tr[name=save_actions_row]").hide();		// Hide the "save changes" rows 
}


