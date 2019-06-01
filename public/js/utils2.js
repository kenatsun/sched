// Turn the background of an unsaved select element to yellow 
// if the selection is other than the first option (the option with index 0)

function colorChangedControl(control) {
	var changed;
	var changed_color = document.getElementById("changed_background_color").value;
	var unchanged_color = document.getElementById("unchanged_background_color").value;
	var tr = document.getElementById("tr_" + control.id);
	var change_count = document.getElementById("change_count");
	// alert("colorChangedControl(): changed = " + control.changed);
	// alert("colorChangedControl(): change_count before = " + change_count.value);

	if (control.type == "select-one") {
		if (control.selectedIndex == "0") {
			changed = false;
		} else {
			changed = true;
		}
	}	else if (control.type == "checkbox") { 
		if (control.checked) {
			changed = true;
		} else {
			changed = false;
		}
	}
	
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
	
	// alert("colorChangedControl(): change_count after = " + change_count.value); 
} 


