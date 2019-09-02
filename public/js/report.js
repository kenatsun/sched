function toggleMode(mode) {
	if (0) console.log("toggleMode(): mode = " + mode);
	const edit_elements = document.getElementsByName("edit_element");
	edit_elements.forEach(element => {
		if (0) console.log("toggleMode(): " + mode + " element id = " + element.id);		
		if (mode == "edit") {
				element.style.display = 'inline';
		}
		else {
				element.style.display = 'none';
		}	
		if (0) console.log("toggleMode(): " + mode + " " + element.type + " id '" + element.id + "' set to " + element.style.display);
	});
	const view_elements = document.getElementsByName("view_element");
	view_elements.forEach(element => {
		if (mode == "view") {
				element.style.display = 'inline';
		}
		else {
				element.style.display = 'none';
		}	
		if (0) console.log("toggleMode(): " + mode + " " + element.type + " id '" + element.id + "' set to " + element.style.display);
	});
}