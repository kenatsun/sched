function updateChangeControlDisplay(this_control_id, request_type="original") {

	if (1) console.log("\n\nupdateChangeControlDisplay(" + request_type + ") ************************************");
	if (0) console.log("updateChangeControlDisplay(" + request_type + "): this_control_id = " + this_control_id); 

	const this_control = document.getElementById(this_control_id);
	if (this_control.type == "select-one") {
		var this_option = this_control.options[this_control.selectedIndex];
	} else {
		var this_option = this_control;
	}
	const this_request = [this_control, this_option];

	if (0) console.log("updateChangeControlDisplay(" + request_type + "): this_request = " + this_request); 
	if (1) console.log(elAtts("updateChangeControlDisplay(" + request_type + "): this_control", this_control)); 
	if (0) console.log(obAtts("updateChangeControlDisplay(" + request_type + "): this_control.dataset", this_control.dataset)); 
	if (0) console.log("updateChangeControlDisplay(" + request_type + "): this_control.selectedIndex = " + this_control.selectedIndex); 
	if (1) console.log(elAtts("updateChangeControlDisplay(" + request_type + "): this_option", this_option)); 
	if (0) console.log(obAtts("updateChangeControlDisplay(" + request_type + "): this_option.dataset", this_option.dataset)); 

	// Determine whether this request is for a change 
	const change_count = document.getElementById("change_count");
	const changed = changeRequested(this_control);	
	if (0) console.log("updateChangeControlDisplay(" + request_type + "): changed = " + changed);

	if (0) console.log("updateChangeControlDisplay(" + request_type + "): change_count @ A = " + change_count.value);
	setChangeCountAndColor(this_control, changed, change_count);
	if (0) console.log("updateChangeControlDisplay(" + request_type + "): change_count @ B = " + change_count.value);

	resetSaveActionsRows(change_count);
	
	resetDisplayOfPublishButtons(change_count);	

	preventInvalidChanges(this_control, this_option, changed, request_type); 

	// When a worker is moved from one shift to another (via "moveout" or "movein" or "trade"), 
	// show the change on the other shift as well
	if (0) console.log(elAtts("updateChangeControlDisplay(" + request_type + "): this_option", this_option)); 

	if (request_type == "original") {
		if (['moveout', 'movein', 'trade'].includes(this_option.dataset.ac)) {
			// Get the other control and option for the NEW request (whose selectors are in this_OPTION.dataset)
			var new_that_request;
			var new_that_control;
			var new_that_option;	
			if (this_option.dataset.s2) {
				new_that_request = getThatControl(this_option.dataset);
				new_that_control = new_that_request[0]; 
				new_that_option = new_that_request[1];	
			} else {
				new_that_request = null;
				new_that_control = null;
				new_that_option = null;			
			}
			if (0) console.log(elAtts("updateChangeControlDisplay(" + request_type + "): new_that_control", new_that_control)); 
			if (0) console.log(obAtts("updateChangeControlDisplay(" + request_type + "): new_that_control.dataset", new_that_control.dataset)); 
			if (0) console.log(elAtts("updateChangeControlDisplay(" + request_type + "): new_that_option", new_that_option)); 
			if (0) console.log(obAtts("updateChangeControlDisplay(" + request_type + "): new_that_option.dataset", new_that_option.dataset)); 
			
			// Reset the other control of the NEW moveout choice (if any) to the complement of this this_control's setting
			if (new_that_request) {
				
				// Set the other option (in this case, the option required by the new choice) as the selected option
				setThatControl(new_that_request, "new");

				// Recurse to update the new_that_control
				updateChangeControlDisplay(new_that_control.id, "new");
			}
			
			// Get the other control and option for the OLD request (whose selectors are in this_CONTROL.dataset)
			var old_that_request;
			var old_that_control;
			var old_that_option;	
			if (0) console.log("updateChangeControlDisplay(" + request_type + "): this_option.dataset.ac = " + this_option.dataset.ac)
			if (this_control.dataset.s2) {
				old_that_request = getThatControl(this_control.dataset);
				old_that_control = old_that_request[0];
				old_that_option = old_that_request[1];	
			} else {
				old_that_request = null;
				old_that_control = null;
				old_that_option = null;			
			}
			if (0) console.log(elAtts("updateChangeControlDisplay(" + request_type + "): old_that_control", old_that_control)); 
			if (0) console.log(obAtts("updateChangeControlDisplay(" + request_type + "): old_that_control.dataset", old_that_control.dataset)); 
			if (0) console.log(elAtts("updateChangeControlDisplay(" + request_type + "): old_that_option", old_that_option)); 
			if (0) console.log(obAtts("updateChangeControlDisplay(" + request_type + "): old_that_option.dataset", old_that_option.dataset)); 
		
			// Reset the other control of the OLD moveout choice (if any) to no change	 
			if (old_that_request) {
			
				// Set the other control's option (in this case, the no-change option) as the selected option
				setThatControl(old_that_request, "old");

				// Recurse to update the old_that_control
				updateChangeControlDisplay(old_that_control.id, "old");
			}			
		}			
	}

	// Update the dataset on this_control to the new option dataset (so it will serve as the "old" dataset next time)
	if (0) console.log(obAtts("updateChangeControlDisplay(" + request_type + "): this_control.dataset before", this_control.dataset)); 
	if (this_option.dataset.s2) this_control.dataset.s2 = this_option.dataset.s2; else delete this_control.dataset.s2;
	if (this_option.dataset.w2) this_control.dataset.w2 = this_option.dataset.w2; else delete this_control.dataset.w2;
	if (0) console.log(obAtts("updateChangeControlDisplay(" + request_type + "): this_control.dataset after", this_control.dataset)); 

	
	if (1) console.log("\n************************************ updateChangeControlDisplay(" + request_type + ")\n\n"); 
} 


////////////////////////////////////////////////////////////// Supporting Functions

function getThatControl(dataset) {
	// Get the other this_control based on its identifying attributes
	if (0) console.log(obAtts("getThatControl(): dataset", dataset));

	let that_option_selector = "option";
	if (dataset.ac == "moveout") {
		that_option_selector += "[data-ac='movein'][data-s1='" + dataset.s2 + "'][data-w2='" + dataset.w1 + "']";
	} else if (dataset.ac == "movein") {
		that_option_selector += "[data-ac='moveout'][data-s1='" + dataset.s2 + "'][data-w1='" + dataset.w2 + "']";
	} else if (dataset.ac == "trade") {
		that_option_selector += "[data-ac='trade'][data-s1='" + dataset.s2 + "'][data-w1='" + dataset.w2 + "'][data-s2='" + dataset.s1 + "'][data-w2='" + dataset.w1 + "']";
	} 
	if (0) console.log(obAtts("getThatControl(): that_option_selector", that_option_selector)); 
	const that_option = document.querySelector(that_option_selector);  
	if (0) console.log(elAtts("getThatControl(): that_option", that_option)); 
	if (that_option) {
		const that_control = that_option.parentElement; 
		if (0) console.log(elAtts("getThatControl(): that_control", that_control)); 
		const request = [that_control, that_option]; 
		return request;
	} else {
		return;	
	}
}


function setThatControl(that_request, that_request_type) {
	// var that_op; 
	switch (that_request_type) {
		case "new":
			const that_option = that_request[1];
			if (0) console.log("setThatControl(): that_option.index = " + that_option.index);
			if (0) console.log("setThatControl(): that_option.selected before?: " + that_option.selected); 
			that_option.selected = true;
			if (0) console.log("setThatControl(): that_option.selected after?: " + that_option.selected); 
			break;
		case "old": 
			const that_control = that_request[0];
			if (0) console.log(elAtts("setThatControl(): that_control", that_control));
			zero_option = that_control[0]; // Get option with index 0 
			if (0) console.log("setThatControl(): zero_option.index = " + zero_option.index);
			if (0) console.log("setThatControl(): zero_option.selected before?: " + zero_option.selected); 
			zero_option.selected = true;
			if (0) console.log("setThatControl(): zero_option.selected after?: " + zero_option.selected);
			break;
	}
}
	
	
function changeRequested(control) {

	// Determine whether this request is for a change 
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
	if (0) console.log("changeRequested(): control.type = " + control.type);
	if (0) console.log("changeRequested(): control.selectedIndex = " + control.selectedIndex);
	if (0) console.log("changeRequested(): changed = " + changed);
	if (0) console.log("changeRequested(): change_count @ A = " + change_count.value);
	
	return changed;
}
	
	
function preventInvalidChanges(this_control, this_option, changed, request_type) {

	if (1) console.log("\npreventInvalidChanges(" + request_type + ") :::::::::::::::::::::::::::::::::::::\n ");
	if (changed) {
		var dataset = this_option.dataset;
	} else {
		var dataset = this_control.dataset;
	}
	if (0) console.log("preventInvalidChanges(" + request_type + "): actions_that_remove = " + actions_that_remove);
	if (0) console.log("preventInvalidChanges(" + request_type + "): this_control.id = " + this_control.id);
	if (0) console.log(obAtts("preventInvalidChanges(" + request_type + "): this_control.dataset", this_control.dataset));
	if (1) console.log(obAtts("preventInvalidChanges(" + request_type + "): dataset", dataset));
	if (0) console.log("preventInvalidChanges(" + request_type + "): this_control.name = " + this_control.name);
	if (1) console.log("preventInvalidChanges(" + request_type + "): changed?: " + changed);
	
	// If this action would remove this worker from this shift...
	const actions_that_remove = ["remove", "moveout", "trade"];
	if (actions_that_remove.includes(this_control.dataset.ac)) { 
		
		// Hide the other controls on this shift that would remove this worker from this shift
		if (0) console.log("preventInvalidChanges(" + request_type + "): this_control.dataset.s1 = " + dataset.s1);
		const controls_that_remove = document.querySelectorAll("input[class=control_for_shift_" + dataset.s1 + "][type=checkbox]," + "select[class=control_for_shift_" + dataset.s1 + "]");
		if (0) console.log("preventInvalidChanges(" + request_type + "): controls_that_remove.length = " + controls_that_remove.length);
		if (0) console.log("preventInvalidChanges(" + request_type + "): controls_that_remove", controls_that_remove);
		for(let control_that_removes of controls_that_remove) { 
			if (0) console.log(elAtts("preventInvalidChanges(" + request_type + "): control_that_removes", control_that_removes));
			if (((!control_that_removes.dataset.w1) || (control_that_removes.dataset.w1 == this_control.dataset.w1)) && (control_that_removes.id != this_control.id ) && (actions_that_remove.includes(control_that_removes.dataset.ac))) {
				const this_shift_removing_tr = document.getElementById("tr." + control_that_removes.id);
				if (changed == true) {
					if (0) console.log("preventInvalidChanges(" + request_type + "): gonna hide " + this_shift_removing_tr.id);
					this_shift_removing_tr.style.display = "none";
				} else {
					if (0) console.log("preventInvalidChanges(" + request_type + "): gonna show " + this_shift_removing_tr.id);
					this_shift_removing_tr.style.display = "table-row";  
				}
			}	
		}
		
		// Hide the options on all shift controls that would remove this worker from this shift 
		const remover_selector = "option[data-s2='" + dataset.s1 + "'][data-w2='" + dataset.w1 + "']";
		if (0) console.log ("preventInvalidChanges(" + request_type + "): remover_selector = " + remover_selector);
		const options_that_remove = document.querySelectorAll(remover_selector);
		if (0) console.log("preventInvalidChanges(" + request_type + "): options_that_remove.length = " + options_that_remove.length);
		if (1) console.log("preventInvalidChanges(" + request_type + "): options_that_remove", options_that_remove);
		for(let option_that_removes of options_that_remove) { 
			if (0) console.log(elAtts("preventInvalidChanges(" + request_type + "): option_that_removes", option_that_removes));
			if (changed == true) {
				if (0) console.log("preventInvalidChanges(" + request_type + "): gonna hide " + option_that_removes.id);
				option_that_removes.style.display = "none";
			} else {
				if (0) console.log("preventInvalidChanges(" + request_type + "): gonna show " + option_that_removes.id);
				option_that_removes.style.display = "block"; 
			}
		}
	}

	// If this action would add that worker to this shift...
	const actions_that_add = ["add", "movein", "trade"];	
	if (actions_that_add.includes(this_option.dataset.ac)) {
		
		// Hide the options on all shift controls that would add this worker to this shift 
		const adder_selector = "option[data-s2='" + dataset.s1 + "'][data-w1='" + dataset.w2 + "']";
		if (0) console.log ("preventInvalidChanges(" + request_type + "): adder_selector = " + adder_selector);
		const options_that_add = document.querySelectorAll(adder_selector);
		if (0) console.log("preventInvalidChanges(" + request_type + "): options_that_add.length = " + options_that_add.length);
		if (1) console.log("preventInvalidChanges(" + request_type + "): options_that_add", options_that_add);
		for(let option_that_adds of options_that_add) { 
			if (0) console.log(elAtts("preventInvalidChanges(" + request_type + "): option_that_adds", option_that_adds));
			if (changed == true) {
				if (0) console.log("preventInvalidChanges(" + request_type + "): gonna hide " + option_that_adds.id);
				option_that_adds.style.display = "none";
			} else {
				if (0) console.log("preventInvalidChanges(" + request_type + "): gonna show " + option_that_adds.id);
				option_that_adds.style.display = "block"; 
			}
		}
	}
	if (1) console.log("\n:::::::::::::::::::::::::::::::::::::: preventInvalidChanges(" + request_type + ")\n ");	
}


function setChangeCountAndColor(control, changed, change_count) {
	// Set the background of an unsaved change to the "changed color", and increment change_count
	// Set the background of an unchanged field to the "unchanged color", and decrement change_count

	const my_tr_id = "tr." + control.id;
	if (0) console.log("setChangeCountAndColor(): my_tr_id = " + my_tr_id);
	const my_tr = document.getElementById("tr." + control.id);

	if (0) console.log("setChangeCountAndColor(): changed = " + changed);
	if (0) console.log("setChangeCountAndColor(): my_tr.id = " + my_tr.id);
	if (0) console.log("setChangeCountAndColor(): control.id = " + control.id);
	if (0) console.log("setChangeCountAndColor(): control.backgroundColor = " + control.backgroundColor);
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
	if (0) console.log("setChangeCountAndColor(): change_count = " + change_count);
}
	

function resetFormDisplayAfterDiscard() {
	if (0) console.log("resetFormDisplayAfterDiscard(): start");
	if (0) console.log("resetFormDisplayAfterDiscard(): $('#change_count').attr('value') = " + $('#change_count').attr('value'));
	$('#change_count').attr('value', 0);			// No pending changes
	if (0) console.log("resetFormDisplayAfterDiscard(): $('#change_count').attr('value') = " + $('#change_count').attr('value'));
	$("tr[name=change_control_tr]").show();		// Show all the change control trs
	$("tr[name=change_control_tr]").css("background-color", unchanged_color);	// Mark all change control trs as unchanged
	$("[name*=move]").css("background-color", unchanged_color);		// Mark "move" change controls as unchanged
	$("[name*=trade]").css("background-color", unchanged_color);	// Mark "trade" change controls as unchanged
	$("[name*=add]").css("background-color", unchanged_color);		// Mark "add" change controls as unchanged
	$("tr[name=save_actions_row]").hide();		// Hide the "save changes" rows
	resetDisplayOfPublishButtons(0);	
}


function resetSaveActionsRows(change_count) {
	const save_actions_rows = document.getElementsByName("save_actions_row");
	// for (i = 0;  i < save_actions_rows.length; i++) { 
	for (let save_actions_row of save_actions_rows) { 
		if (change_count.value > 0) {
			save_actions_row.style.display = "table-row";
		} else {
			save_actions_row.style.display = "none";
		}
	}
}


function resetDisplayOfPublishButtons(change_count) {
	// Show publish buttons only when there are no unsaved changes	
	if (0) console.log(obAtts("resetDisplayOfPublishButtons(): change_count", change_count));
	const publish_buttons = document.getElementsByName("publish_buttons"); 
	if (0) console.log("resetDisplayOfPublishButtons(): publish_buttons.length = " + publish_buttons.length);
	for (let publish_button of publish_buttons) { 
		if (change_count.value > 0) {
			publish_button.style.display = "none";
		} else { 
			publish_button.style.display = "inline";	
		}
	}
}

// Prevent blank input fields from being submitted
// source: https://stackoverflow.com/questions/5904437/jquery-how-to-remove-blank-fields-from-a-form-before-submitting
function disableValuelessInputs() {
	$("#assignments_form").find(':input[value=""]').attr("disabled", "disabled"); 
	return true; // ensure form still submits
}
