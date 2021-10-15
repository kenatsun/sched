<?php

require_once "start.php";
require_once "teams_utils.php";
print '<script src="js/publish.js"></script>';

$headline = renderHeadline("Publish Changes?", "", "", 1);

$form .= '<form method="POST" id="publish_form" name="publish_form" action="' . makeURI("/teams.php", PREVIOUS_CRUMBS_IDS) . '">';  

// Which edition is being published? (this affects labeling of the display and printout) 
$form .= '<p>Which edition of the schedule is this?</p>';
$form .= '<input type="radio" name="edition" value="first">First<br>'; 
$form .= '<input type="radio" name="edition" value="revised">Revised<br>';  
$form .= '<input type="radio" name="edition" value="final">Final<br>'; 
$form .= '<br>';  

// Provide printable previews of the schedule
$url = makeURI("/teams.php", "", "printable=1&controls_display=hide&change_markers_display=show&edition=");
if (0) deb("publish.php: url = ", $url);
$form .= '<a href="#" onClick="publishTeams(\'' . $url . '\')">Show teams with change markers</a><br>';
$form .= '<br>'; 
$url = makeURI("/teams.php", "", "printable=1&controls_display=hide&change_markers_display=hide&edition=");
$form .= '<a href="#" onClick="publishTeams(\'' . $url . '\')">Show teams without change markers</a><br>';
$form .= '<br>';

// Render publish action buttons and legend 
$form .= '
			<input type="submit" name="publish" id="publish" value="Publish"> 
			&nbsp;
			<input type="submit" name="dont_publish" value="Don\'t publish">
	';

$page = <<<EOHTML
	{$headline}
	{$form}
EOHTML;

if (0) deb("publish.php: before print page");
print $page; 
if (0) deb("publish.php: after  print page");

?>