
function colorChangedControl(control) {
	var changed;
	var changed_color = document.getElementById("changed_background_color").value;
	var unchanged_color = document.getElementById("unchanged_background_color").value;
	var tr = document.getElementById("tr_" + control.id);
	var change_count = document.getElementById("change_count");
	var i;

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

window.onload = function(){
	document.querySelectorAll("INPUT[type='radio']").forEach(function(rd){
		rd.addEventListener("mousedown", function(){
			if (this.checked) {
				this.onclick=function(){
					this.checked=false;
					markUndos(rd);
				}
			} else {
				this.onclick=null;
			}
		})
	})
} 

function markUndos(control) {
	// If a change set is marked for undo, also mark the background color of all later-saved changes for undo
	var changed_color = document.getElementById("changed_background_color").value;
	var unchanged_color = document.getElementById("unchanged_background_color").value;
	var checkedRadio = -1;
	var i, j;

	// The following ugliness is because markUndos has to be called differently 
	// when a button is being selected (it's invoked from the HTML via onChange event)
	// vs when the selected button is being un-selected (it's invoked from the window.onload code above)
	if (control.type == "radio") {
		control = document.getElementsByName(control.name); 
	}

	for(i = 0; i < control.length; i++) {
		if (control[i].checked) {
			checkedRadio = i; 	
			break;
		}
	}
	for(j = 0; j < control.length; j++) {
		if (j <= checkedRadio) {
			document.getElementById("undo_tr_" + control[j].value).style.backgroundColor = changed_color;
		} else {
			document.getElementById("undo_tr_" + control[j].value).style.backgroundColor = unchanged_color;					
		}
	}
	
	if (checkedRadio != -1) {
		// Show controls for undoing the changes with legend
		document.getElementById("action_row").style.display = "table-row";
	} else {
		// Hide controls for undoing the changes with legend
		document.getElementById("action_row").style.display = "none";
	}
}


function setFormAction(form_id, action) { 
	document.getElementById(form_id).action = action;
	alert ("action = " + action);
}

