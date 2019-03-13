<?php
session_start(); 

require_once 'globals.php';
require_once 'utils.php';
require_once 'display/includes/header.php';

$season = getSeason();

// Display the page
$page = "";
$page .= renderHeadline($season['name'] . " Season Survey Tools", HOME_LINK . SEASONS_LINK); 
$page .= renderPageBody($season, $parent_process_id); 
print $page;


//////////////////////////////////////////////////////////////// DISPLAY FUNCTIONS

function renderPageBody($season, $parent_process_id) {
	
	if (0) deb("survey_tools.renderPageBody(): season = ", $season);
	$where = "type = 'Tool' 
		and season_id = " . $season['id'] . "
		and parent_process_id = " . CONDUCT_SURVEY_ID;
	$tools = sqlSelect("*", ADMIN_PROCESSES_TABLE, $where, "display_order", (0), "season_utils.renderPageBody():");
	if (0) deb("survey_tools.renderPageBody(): tools = ", $tools);
	$body = "";
	
	// Render the page components for each tool
	foreach ($tools as $tool) {
		$body .= '<br><br><h3>Tool ' . ++$n . ': ' . $tool['name'] . '</h3>';
		// $body .= '<br><br><h3>Tool ' . ++$n . ': ' . $tool['name'] . '</h3>';
		switch ($tool['process_id']) {
			case VIEW_SURVEY_RESULTS_ID:
				// $body .= renderEditSeasonForm($season, $parent_process_id); 
				// $body .= render_report_link("Click here to see the responses to date"); 
				$body .= renderLink("Click here to see the responses to date", $tool['href']); 
				break;
			case TAKE_SURVEY_ID:
				// $body .= renderEditMealsCalendarForm($season, $parent_process_id);
				$body .= renderLink("Click here to take the survey for someone else", $tool['href']);
				break;
			case RUN_SCHEDULER_PREVIEW_ID:
				// $body .= renderWorkerImportForm($season, $parent_process_id);
				$body .= renderLink("Click here to see what the Scheduler would generate from the responses received so far", $tool['href']); 
				break;
		}
	}
	return $body;
}


?>