function updateChangeControlDisplay(control_id) {
	var change_count = document.getElementById("change_count");
	var control = document.getElementById(control_id);
	var changed;
	var i;
	var old_value = $(control).data('val');
	
	if (0) console.log("\n************************************ updateChangeControlDisplay():");
	if (0) console.log("control.id = " + control.id + "\nold value = " + old_value + "\nnew value = " + control.value); 
	if (0) console.log("$(control).data('val') = " + $(control).data('val'));
	if (0) console.log("control_id = " + control_id);
	if (0) console.log("control.id = " + control.id);
	if (0) console.log("old_value = " + old_value + "\nnew value = " + control.value);

	// Get the static elements of the control
	var action = "";
	var this_shift_id = "";
	var shift_arr = control.id.split(" ");
	var shift_arr2;
	for(i = 0;  i < shift_arr.length; i++) {
		shift_arr2 = shift_arr[i].split(":");
		switch(shift_arr2[0]) {
			case "action":
				action = shift_arr2[1];
				break;	
			case "this_shift":
				this_shift_id = shift_arr2[1];
				break;
		}
	}

	// Get the elements of the request
	var request_arr = control.value.split(" ");
	var request_arr2;
	var this_worker_id = "";
	var that_shift_id = "";
	var that_worker_id = "";
	for(i = 0;  i < request_arr.length; i++) {
		request_arr2 = request_arr[i].split(":");
		switch(request_arr2[0]) {
			case "this_worker":
				this_worker_id = request_arr2[1];
				break;
			case "that_shift":
				that_shift_id = request_arr2[1];
				break;
			case "that_worker":
				that_worker_id = request_arr2[1];
				break;
		}
	}

	// Get the pre-existing elements of the field
	var old_request_arr = old_value.split(" ");
	var old_request_arr2;
	var old_this_worker_id = "";
	var old_that_shift_id = "";
	var old_that_worker_id = "";
	for (i = 0;  i < old_request_arr.length; i++) {
		if (0) console.log("updateChangeControlDisplay(): old_request_arr[" + i + "] = " + old_request_arr[i]);
		old_request_arr2 = old_request_arr[i].split(":");
		if (0) console.log("updateChangeControlDisplay(): old_request_arr2[0] = " + old_request_arr2[0] + ", old_request_arr2[1] = " + old_request_arr2[1]);
		switch(old_request_arr2[0]) { 
			case "this_worker":
				old_this_worker_id = old_request_arr2[1];
				break;
			case "that_shift":
				old_that_shift_id = old_request_arr2[1];
				break;
			case "that_worker":
				old_that_worker_id = old_request_arr2[1];
				break;
		}
	}

	if (0) console.log("\naction = " + action 
		+ "\nthis_worker_id = " + this_worker_id 
		+ "\nold_this_worker_id = " + old_this_worker_id 
		+ "\nthis_shift_id = " + this_shift_id 
		+ "\nold_this_shift_id = " + old_this_shift_id 
		+ "\nthat_shift_id = " + that_shift_id 
		+ "\nold_that_shift_id = " + old_that_shift_id 
		+ "\nthat_worker_id = " + that_worker_id
		+ "\nold_that_worker_id = " + old_that_worker_id
		); 
	if (0) console.log("\naction = " + action + "\nthis_worker_id = " + this_worker_id + "\nthis_shift_id = " + this_shift_id + "\nthat_shift_id = " + that_shift_id + "\nthat_worker_id = " + that_worker_id); 


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
		// A checked checkbox means "change"
		if (control.checked) {
			changed = true;
		} 
		// An unchecked checkbox means "no change"
		else {
			changed = false;
		}
	}

	if (0) console.log("changed = " + changed);
	if (0) console.log("change_count @ A = " + change_count.value);
	setChangeCountAndColor(control, changed, change_count);
	if (0) console.log("change_count @ B = " + change_count.value);
	
	// When a worker is moved from one shift to another (via "moveout" or "movein" or "trade"), 
	// show the change on both shifts
	if (0) console.log("action = " + action + "\n['moveout', 'movein'].includes(action) = " + ['moveout', 'movein'].includes(action));
	if (['moveout', 'movein'].includes(action)) {
		if (action == "moveout") {
			this_action = "moveout";
			that_action = "movein";
		} else if (action == "movein") {
			this_action = "movein";
			that_action = "moveout";			
		}
		if (0) console.log("this_action = " + this_action + "  that_action = " + that_action);

		// Reset the other control of the NEW moveout choice (if any) to the complement of this control's setting
		if (that_shift_id) {
			var that_control_id;
			if (action == "moveout") {
				that_control_id = "action:" + that_action + " this_shift:" + that_shift_id;
			} else if (action == "movein") {
				that_control_id = "action:" + that_action + " this_shift:" + that_shift_id + " this_worker:" + this_worker_id;				
			}
			if (0) console.log("that_control_id = " + that_control_id);
			var that_control = document.querySelector("select[id='" + that_control_id + "']");
			if (0) console.log("that_control.id = " + that_control.id);
			var that_control_value = "action:" + that_action + " this_shift:" + that_shift_id + " this_worker:" + this_worker_id + " that_shift:" + this_shift_id;
			if (0) console.log("that_control_value = " + that_control_value);
			var that_option = document.querySelector("option[value='" + that_control_value + "']");
			if (0) console.log("that_option.index = " + that_option.index);
			if (0) console.log("that_option.selected @ 1= " + that_option.selected);
			that_option.selected = true;
			if (0) console.log("that_option.selected @ 2= " + that_option.selected);
			setChangeCountAndColor(that_control, true, change_count);
			if (0) console.log("change_count @ C = " + change_count.value);
			hideRedundantRemoveControls(that_control, true, that_shift_id);
		}
		
		// Reset the other control of the OLD moveout choice (if any) to no change	
		if (old_that_shift_id) {
			var old_that_control_id;
			if (action == "moveout") {
				old_that_control_id = "action:" + that_action + " this_shift:" + old_that_shift_id;
			} else if (action == "movein") {
				old_that_control_id = "action:" + that_action + " this_shift:" + old_that_shift_id + " this_worker:" + old_this_worker_id;
			}
			if (0) console.log("old_that_control_id = " + old_that_control_id);
			var old_that_control = document.querySelector("select[id='" + old_that_control_id + "']");
			if (0) console.log("old_that_control.id = " + old_that_control.id);
			var old_that_option = old_that_control.options[0];
			if (0) console.log("old_that_option.index = " + old_that_option.index);
			if (0) console.log("old_that_option.selected @ 1= " + old_that_option.selected);
			old_that_option.selected = true;
			if (0) console.log("old_that_option.selected @ 2= " + old_that_option.selected);
			setChangeCountAndColor(old_that_control, false, change_count);  
			if (0) console.log("change_count @ D = " + change_count.value); 
			hideRedundantRemoveControls(old_that_control, false, old_that_shift_id);
		}

	}
	
	hideRedundantRemoveControls(control, changed, this_shift_id);
	
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


function hideRedundantRemoveControls(control, changed, shift_id) {
	// If change action would remove this person from this shift,
	// hide the other controls that would do the same thing
	var remove_control_names = ["remove[]", "moveout[]", "trade[]"];
	if (0) console.log("\n\ncontrol.id = " + control.id);
	if (0) console.log("control.name = " + control.name);
	if (0) console.log("changed = " + changed);
	if (remove_control_names.includes(control.name)) {
		if (0) console.log("class = " + "shift_control_" + this_shift_id);
		var shift_controls = document.getElementsByClassName("shift_control_" + shift_id);
		if (0) console.log("shift_controls = " + shift_controls);
		if (0) console.log("shift_controls.length = " + shift_controls.length);
		var control_names = "";
		for(i = 0;  i < shift_controls.length; i++) {
			var other_name = shift_controls[i].name;
			var other_tr = document.getElementById("tr_" + shift_controls[i].id);
			if (0) console.log("other_tr.id = " + other_tr.id);
			if ((other_name != control.name) && (remove_control_names.includes(other_name))) { 
			if (changed == true) {
					if (0) console.log("gonna hide " + other_tr.id);
					other_tr.style.display = "none";
					// $("#" + other_tr.id).hide();
				} else {
					if (0) console.log("gonna show " + other_tr.id);
					// $("#" + other_tr.id).show();
					other_tr.style.display = "table-row";
				}
			}
		}
	}
	

}
function setChangeCountAndColor(control, changed, change_count) {
	// Set the background of an unsaved change to the "changed color"
	// Set the background of an unchanged field to the "unchanged color"
	var my_tr = document.getElementById("tr_" + control.id);
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
	if (0) console.log("updateChangeControlDisplay(): change_count = " + change_count);
}
	


function resetFormDisplay() {
	if (0) console.log("resetFormDisplay(): start");
	$("#change_count").attr("value", 0);			// No pending changes
	$("tr[name=change_control_tr]").show();		// Show all the change control trs
	$("tr[name=change_control_tr]").css("background-color", unchanged_color);	// Mark all change control trs as unchanged
	$("[name*=move]").css("background-color", unchanged_color);		// Mark "move" change controls as unchanged
	$("[name*=trade]").css("background-color", unchanged_color);	// Mark "trade" change controls as unchanged
	$("[name*=add]").css("background-color", unchanged_color);		// Mark "add" change controls as unchanged
	$("tr[name=save_actions_row]").hide();		// Hide the "save changes" rows 
}


// Prevent blank input fields from being submitted
// source: https://stackoverflow.com/questions/5904437/jquery-how-to-remove-blank-fields-from-a-form-before-submitting
function disableValuelessInputs() {
	$("#assignments_form").find(':input[value=""]').attr("disabled", "disabled"); 
	return true; // ensure form still submits
}


