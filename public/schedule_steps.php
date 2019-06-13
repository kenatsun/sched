<?php
require_once 'start.php';

// session_start(); 

// require_once 'globals.php';
// require_once 'utils.php';
// require_once 'display/includes/header.php';

$season = getSeason();

// Display the page
$page = "";
$page .= renderHeadline("Generate and Refine the " . $season['name'] . " Season Schedule", $breadcrumb, "", 0); 
// $page .= renderHeadline("Generate and Refine the " . $season['name'] . " Season Schedule", HOME_LINK . ADMIN_LINK, "", 0); 
$page .= renderPageBody($season); 
print $page;


////////////////////////////////////////////////////////////// DISPLAY FUNCTIONS

function renderPageBody($season) {
	global $next_breadcrumb;
	
	if (0) deb("schedule_steps.renderPageBody(): season = ", $season);
	$where = "type = 'Step' 
		and season_id = " . $season['id'] . "
		and parent_process_id = " . ASSIGN_ID;
	$steps = sqlSelect("*", ADMIN_PROCESSES_TABLE, $where, "display_order", (0), "schedule_steps.renderPageBody():");
	if (0) deb("schedule_steps.renderPageBody(): steps = ", $steps);
	
	// Render the page components for each step
	foreach ($steps as $step) {
		$step_name = $step['href'] ? ' <a href="' . $step['href'] . '?backto=' . $next_breadcrumb . '">' . $step['name'] . '</a> ' : $step['name'];
		$body .= '<br><br><h3>Step ' . ++$n . ': ' . $step_name . '</h3>';
		switch ($step['process_id']) {
			case RUN_SCHEDULER_ID:
				break;
			case REFINE_ASSIGNMENTS_ID:
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