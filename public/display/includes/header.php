<?php
global $html_title;
if (0) deb("header.php: start"); 
if (0) deb("header: gonna call determineUserStatus()",'');

$favicon = getFavicon();

$head = <<<EOHTML
<!doctype html> 
<html> 
<head>
	<title>{$html_title}</title>
	<link rel="icon" type="image/ico" href="display/images/{$favicon}">
	<link rel="stylesheet" href="display/styles/default.css" type="text/css">
	<link rel="stylesheet" href="select2/select2.min.css" type="text/css">

	<script src="js/utils2.js"></script>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<script src="http://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.3/jquery.dataTables.js"></script>
	<script src="select2/select2.full.min.js"></script>
	<script>
		$(document).ready(function() { 
			$('#per_worker').dataTable({
				'bPaginate': false
			});
		} );
	</script>
	<script>
        var unsaved = false;
        $(document).on('submit', 'form', function () {
            unsaved = false;
        });
        $(document).on('change', 'form', function () {
            unsaved = true;
        });
        $(document).on('click', 'select, textarea, .multiselector', function () {
           unsaved = true;
        });
        function unloadPage() {
            if (unsaved) {
                return "You have unsaved changes on this page."; 
            }
        }
		window.onbeforeunload = unloadPage; 
    </script>
EOHTML;
 

print $head;

if (isset($_REQUEST['worker']) || isset($_REQUEST['person'])) {
	echo <<<EOHTML
	<script src="js/utils.js"></script>
	<script src="js/survey_library.js"></script>
EOHTML;
}

	// This script was moved to utils2.js
	// <script>
	// function confirmDeleteSeason(id, name) {
	  // var txt;
	  // var r = confirm("Permanently delete the " + name + " season?");
	  // if (r == true) {
		// let form = document.createElement('form');
		// form.action = "seasons.php";
		// form.method = 'POST';
		// form.innerHTML = '<input name="delete_season" value="' + id + '">';
		// document.body.append(form);
		// form.submit();
		// txt = "Season " + name + " has been deleted!";
	  // } else {
		// txt = "Season " + name + " won't be deleted";
	  // }
	  // document.getElementById("result").innerHTML = txt;
	// }
	// </script>


?>

</head>

<body>





