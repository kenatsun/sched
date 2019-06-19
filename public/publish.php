<?php

require_once "start.php";
require_once "dashboard_utils.php";

$headline = renderHeadline("Publish Changes?", CRUMBS, "", 0);  

$form .= '<p>NOTE:  There will be controls here to pick whether this is the Tentative vs Revised vs Final schedule, and to print out the schedule (with or without revision marks).</p><br>';
// $form .= renderAssignmentsForm(false); 


$form .= '<form method="POST" id="publish_form" name="publish_form" action="' . makeURI("dashboard.php", CRUMBS) . '">';
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