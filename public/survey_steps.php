<?php
require_once 'start.php';

$season = getSeason();

// Display the page
$page = "";
$page .= renderHeadline("Conduct the " . $season['name'] . " Season Survey", "", 1); 
$page .= renderPageBody($season); 
print $page;


//////////////////////////////////////////////////////////////// DISPLAY FUNCTIONS

function renderPageBody($season) {
	
	if (0) deb("survey_steps.renderPageBody(): season = ", $season);
	$where = "type = 'Step' 
		and season_id = " . $season['id'] . "
		and parent_process_id = " . CONDUCT_SURVEY_ID;
	$steps = sqlSelect("*", ADMIN_PROCESSES_TABLE, $where, "display_order", (0), "season_utils.renderPageBody():");
	if (0) deb("survey_steps.renderPageBody(): steps = ", $steps);
	
	// Render the page components for each step
	foreach ($steps as $step) {
		$body .= '<br><br><h3>Step ' . ++$n . ': ' . $step['name'] . '</h3>';
		switch ($step['process_id']) {
			case ANNOUNCE_SURVEY_ID:
				$body .= '<p><a style="margin-left:2em;" href="docs/announcement - poster version.doc" download>Download Announcement (poster version)</a></p>';
				$body .= '<p><a style="margin-left:2em;" href="docs/announcement - mail merge version.doc" download>Download Announcement (mail merge version)</a></p>';
				$filename = "docs/announcement - workers list.csv";
				if (0) deb("survey_steps.renderPageBody(): calling exportSurveyAnnouncementCSV()"); 
				print exportSurveyAnnouncementCSV($season, $filename); 
				$body .= '<p><a style="margin-left:2em;" href="' . $filename . '" download>Download Workers List</a></p>';
				break;
			case MONITOR_MANAGE_SURVEY_ID:
				$where = "type = 'Tool' 
					and season_id = " . $season['id'] . "
					and parent_process_id = " . MONITOR_MANAGE_SURVEY_ID;
				$tools = sqlSelect("*", ADMIN_PROCESSES_TABLE, $where, "display_order", (0), "season_utils.renderPageBody():");
				if (0) deb("survey_steps.renderPageBody(): tools = ", $tools); 
				$body .= renderToolsList($tools, "Actions you can take while the survey is in process:");
				break;
		}
	}
	return $body;
}


?>