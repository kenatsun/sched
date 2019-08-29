function toggleMode(mode) {
	if (0) console.log("toggleMode(): mode= " + mode);
	const controls = document.getElementsByName("edit_control");
	controls.forEach(control => {
		if (mode == "edit") {
				control.style.display = 'inline';
		}
		else {
				control.style.display = 'none';
		}	
	});
}