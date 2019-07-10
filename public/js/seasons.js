function confirmDeleteSeason(id, name) {
	var txt;
	var r = confirm("Permanently delete the " + name + " season?");
	if (r == true) {
	let form = document.createElement('form');
	form.action = "seasons.php";
	form.method = 'POST';
	form.innerHTML = '<input name="delete_season" value="' + id + '">';
	document.body.append(form);
	form.submit();
	txt = "Season " + name + " has been deleted!";
	} else {
	txt = "Season " + name + " won't be deleted";
	}
	document.getElementById("result").innerHTML = txt;
}

