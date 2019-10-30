<?php
global $html_title;
if (0) deb("header.php: start"); 

$favicon = getFavicon();
if (0) deb("header.php: favicon = " . $favicon);

$head = '
<!doctype html> 
<html lang="en"> 
<head>
	<title>' . $html_title . '</title>
	<link rel="icon" type="image/ico" href="display/images/' . $favicon . '">
	<link rel="stylesheet" href="display/styles/default.css" type="text/css">
	<link rel="stylesheet" href="select2/select2.min.css" type="text/css">
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<script src="http://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.3/jquery.dataTables.js"></script>
	<script src="select2/select2.full.min.js"></script>
	<script>
		$(document).ready(function() { 
			$("#per_worker").dataTable({
				"bPaginate": false
			});
		} );
	</script>
	<script>
        var unsaved = false;
        $(document).on("submit", "form", function () { 
            unsaved = false;
        });
        $(document).on("change", "form", function () {
            unsaved = true;
        });
        $(document).on("click", "select, textarea, .multiselector", function () {
           unsaved = true;
        });
        function unloadPage() {
            if (unsaved) {
                return "You have unsaved changes on this page."; 
            }
        }
		window.onbeforeunload = unloadPage; 
	</script> 
';
 
print $head;

?>








