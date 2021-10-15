var session_mode = "view";
var session_others_display = "show";

function toggleMode(mode, others_display) {
	if (0) console.log("toggleMode() start: mode = " + mode + ", session_mode = " + session_mode);
	if (0) console.log("toggleMode() start: others_display = " + others_display + ", session_others_display = " + session_others_display);
	
	if (mode) {
		session_mode = mode;
	} else {
		mode = session_mode;
	}
	if (others_display) {
		session_others_display = others_display;
	} else {
		others_display = session_others_display;
	}
	
	if (0) console.log("toggleMode() after assmt: mode = " + mode + ", session_mode = " + session_mode);
	if (0) console.log("toggleMode() after assmt: others_display = " + others_display + ", session_others_display = " + session_others_display);

	const edit_elements = document.getElementsByName("edit_element");
	edit_elements.forEach(element => {
		if (0) console.log("toggleMode(): " + mode + " element id = " + element.id);		
		if (mode == "edit" && (isCurrentSeasonReport(element) || others_display == "show")) {
			element.style.display = 'inline'; 
		}
		else {
			element.style.display = 'none';
		}	
		if (0) console.log("toggleMode(): " + mode + " " + element.type + " id '" + element.id + "' set to " + element.style.display);
	});
	
	const view_elements = document.getElementsByName("view_element");
	view_elements.forEach(element => {
		if (mode == "view" && (isCurrentSeasonReport(element) || others_display == "show")) {
			element.style.display = 'inline';
		}
		else {
			element.style.display = 'none';
		}	
		if (0) console.log("toggleMode(): " + mode + " " + element.type + " id '" + element.id + "' set to " + element.style.display);
	});
	
	const all_season_control = document.getElementById("show_all_reports");
	const this_season_only_control = document.getElementById("show_this_season_reports");
	if (others_display == "hide") {
		all_season_control.style.display = 'inline';
		this_season_only_control.style.display = 'none';
	} else {
		all_season_control.style.display = 'none';
		this_season_only_control.style.display = 'inline';		
	}
}


function isCurrentSeasonReport(element) {
	if (element.getAttribute("data-from-this-season") == 1) return true; 
	else return false; 
}


// function toggleOtherSeasonReports(inline_or_none) {
	// var reports;
	// reports = document.getElementsByName("view_element");
	// reports.forEach(report => {
		// if (0) console.log("report.name =  = " + divid + " linkid = " + linkid);
		// report.display = inline_or_none;
	// })
	// reports = document.getElementsByName("edit_element");
	// reports.forEach(report => {
		// report.display = inline_or_none;
	// })
	
// }