function toggleMode(mode) {
	if (0) console.log("toggleMode(): mode= " + mode);
	const edit_controls = document.getElementsByName("edit_control");
	edit_controls.forEach(control => {
		if (mode == "edit") {
				control.style.display = 'inline';
		}
		else {
				control.style.display = 'none';
		}	
	});
	const view_controls = document.getElementsByName("view_control");
	view_controls.forEach(control => {
		if (mode == "view") {
				control.style.display = 'inline';
		}
		else {
				control.style.display = 'none';
		}	
	});
}