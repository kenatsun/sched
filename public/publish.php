<?php

require_once "start.php";
require_once "dashboard_utils.php";

$headline = renderHeadline("Publish Changes?", CRUMBS, "", 0);  

$form .= '<form method="POST" id="publish_form" name="publish_form" action="' . makeURI("dashboard.php", CRUMBS) . '">';

// Which version is being published? (this affects labeling of the display and printout)
$form .= '<p>Which version of the schedule is this?</p>';
$form .= '<input type="radio" name="version" value="first">First<br>';
$form .= '<input type="radio" name="version" value="revised">Revised<br>'; 
$form .= '<input type="radio" name="version" value="final">Final<br>'; 
$form .= '<br>';  

// Provide printable previews of the schedule
$url = makeURI("dashboard.php", "", "printable=1&controls_display=hide&change_markers_display=show&version=");
$form .= '<a href="#" onClick="publishVersion(\'' . $url . '\')">Show teams with change markers</a><br>';
$form .= '<br>'; 
$url = makeURI("dashboard.php", "", "printable=1&controls_display=hide&change_markers_display=hide&version=");
$form .= '<a href="#" onClick="publishVersion(\'' . $url . '\')">Show teams without change markers</a><br>';
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
print $page; 

?>