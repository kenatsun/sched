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
	$where = "type = 'Tool' 
		and season_id = " . $season['id'] . "
		and parent_process_id = " . CONDUCT_SURVEY_ID;
	$tools = sqlSelect("*", ADMIN_PROCESSES_TABLE, $where, "display_order", (0), "season_utils.renderPageBody():");
	if (0) deb("survey_steps.renderPageBody(): tools = ", $tools);
	$body .= renderToolsList($tools, "Tools for managing and monitoring the survey:");
	
	return $body;
}


?>