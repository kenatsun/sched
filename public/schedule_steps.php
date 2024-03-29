<?php
require_once 'start.php';

$season = getSeason(); 

// Display the page
$page .= renderHeadline("Create the " . $season['name'] . " Teams", "", 1, 1); 
$page .= renderPageBody($season); 
print $page;


////////////////////////////////////////////////////////////// DISPLAY FUNCTIONS

function renderPageBody($season) {
	
	if (0) deb("schedule_steps.renderPageBody(): season = ", $season);
	$where = "type = 'Step' 
		and season_id = " . $season['id'] . "
		and parent_process_id = " . ASSIGN_ID;
	$steps = sqlSelect("*", ADMIN_PROCESSES_TABLE, $where, "display_order", (0), "schedule_steps.renderPageBody():");
	if (0) deb("schedule_steps.renderPageBody(): steps = ", $steps);
	
	// Render the page components for each step
	foreach ($steps as $step) {
		$step_name = $step['href'] ? ' <a href="' . makeURI($step['href'], NEXT_CRUMBS_IDS, $step['query_string'] . 'caller_url=' . $_SERVER['PHP_SELF']) . '">' . $step['name'] . '</a> ' : $step['name'];
		$body .= '<br><br><h3>Step ' . ++$n . ': ' . $step_name . '</h3>';
		switch ($step['process_id']) {
			case RUN_SCHEDULER_ID:
				break;
			case REFINE_ASSIGNMENTS_ID:
				break;  
			case EXPORT_TEAMS_ID:
				$body .= renderExportMealsForm($season, "update");
				if (0) deb("schedule_steps.renderPageBody(): body = ", $body);
				break;
		}
	}

	$where = "type = 'Tool' 
		and season_id = " . $season['id'] . "
		and parent_process_id = " . MONITOR_MANAGE_SURVEY_ID;
	$tools = sqlSelect("*", ADMIN_PROCESSES_TABLE, $where, "display_order", (0), "season_utils.renderPageBody():");
	if (0) deb("schedule_steps.renderPageBody(): tools = ", $tools);
	return $body; 
}


?>