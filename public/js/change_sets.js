// When a checked radio button is clicked on, uncheck it
window.onload = function() {
	nullRadio();
}

function markUndos(control) {
	// If a change set is marked for undo, also mark the background color of all later-saved changes for undo
	var checkedRadioIndex = -1;
	var i, j;
	// alert("markUndos(): changed_color = " + changed_color);
	// alert("markUndos(): control.name = " + control.name + "  control.value = " + control.value);

	if (control.type == "radio") {
		controls = document.getElementsByName(control.name); 
	}
	// alert("markUndos(): controls.length = " + controls.length);

	for(i = 0; i < controls.length; i++) { 
		// alert("markUndos(): controls[i].name = " + controls[i].name + "  controls[i].value = " + controls[i].value);
		if (controls[i].checked) {
			checkedRadioIndex = i; 	
			break;
		} 
	}
	// alert("markUndos(): checkedRadioIndex = " + i);

	for(j = 0; j < controls.length; j++) {
		// alert("markUndos(): j = " + j + "  checkedRadioIndex = " + checkedRadioIndex + "  controls[j].value = " + controls[j].value);
		if (j <= checkedRadioIndex) {
			// alert("markUndos(): j = " + j + "  set to " + changed_color);
			document.getElementById("undo_tr_" + controls[j].value).style.backgroundColor = changed_color;
		} else {
			// alert("markUndos(): j = " + j + "  set to " + unchanged_color);
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

