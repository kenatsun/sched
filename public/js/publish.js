
function publishTeams(URL) {
	var editions = document.getElementsByName("edition");
	var edition;
	for (i = 0; i < editions.length; i++) {
		if (editions[i].checked) {
			edition = editions[i].value;
			window.open(URL + edition); 
			return false;			
		}
	}
	alert ("You must select an edition");
}
