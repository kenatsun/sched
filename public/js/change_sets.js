// When a checked radio button is clicked on, uncheck it
window.onload = function() {
	nullRadio();
}

/* 
Note 8/31/22:  This is dysfunctional because the "control" variable gets typed as an HTMLInputElement object
of type 'radio' when there's only one change set to potentially undo, but as a RadiNodeList object when there are multiple change sets.



See files in \documents\coho\dinners\organizer software\mo development\change_sets*
*/
function markUndos(control) {
	// If a change set is marked for undo, also mark the background color of all later-saved changes for undo
	var checkedRadioIndex = -1;
	var i, j;
	var controls;
	if (0) console.log("markUndos(): changed_color = " + changed_color);
	if (0) console.log("markUndos(): control = " + control + "  control length = " + control.length); 
	if (0) console.log("markUndos(): control type = " + control.type); 
	if (0) console.log("markUndos(): control name = " + control.name + "  control value = " + control.value); 

	if (control.type == "radio") {
		controls = document.getElementsByName(control.name); 
//		controls = document.getElementsByName(control.name); 
	}
	if (0) console.log("markUndos(): controls.length = " + controls.length);

	for(i = 0; i < controls.length; i++) { 
		if (0) console.log("markUndos(): controls[i].name = " + controls[i].name + "  controls[i].value = " + controls[i].value);
		if (controls[i].checked) {
			checkedRadioIndex = i; 	
			break;
		} 
	}
	if (0) console.log("markUndos(): checkedRadioIndex = " + i);

	for(j = 0; j < controls.length; j++) {
		// if (0) console.log("markUndos(): j = " + j + "  checkedRadioIndex = " + checkedRadioIndex + "  controls[j].value = " + controls[j].value);
		if (j <= checkedRadioIndex) {
			// if (0) console.log("markUndos(): j = " + j + "  set to " + changed_color);
			document.getElementById("undo_tr_" + controls[j].value).style.backgroundColor = changed_color;
		} else {
			// if (0) console.log("markUndos(): j = " + j + "  set to " + unchanged_color);
			document.getElementById("undo_tr_" + controls[j].value).style.backgroundColor = unchanged_color;					
		}
	}
	
	if (checkedRadioIndex != -1) {
		// Show controls for undoing the changes with legend
		document.getElementById("action_row").style.display = "table-row";
	} else {
		// Hide controls for undoing the changes with legend
		document.getElementById("action_row").style.display = "none";
	}
}

