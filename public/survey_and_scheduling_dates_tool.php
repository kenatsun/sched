<?php

require_once 'start.php';
require_once 'admin_utils.php';
require_once 'season_utils.php'; 

// $season = getSeason();
// if (0) deb("survey_and_scheduling_dates_tool.php: season =", $season);
if (0) deb("survey_and_scheduling_dates_tool.php: _POST =", $_POST);

// If request is to store season data, do that
if (array_key_exists('season_status', $_POST) || array_key_exists('survey_setup', $_POST)) saveChangesToSeason($_POST);
// if (array_key_exists('season_status', $_POST) || array_key_exists('survey_setup', $_POST)) {
	// $season_id = saveChangesToSeason($_POST);
// }

$season = getSeason();
if (0) deb("survey_and_scheduling_dates_tool.php: season =", $season);

// Display the page
$page = "";
$page .= renderHeadline("Adjust Survey & Scheduling Dates", "", 1); 
$page .= "<br>";
$page .= renderSurveySetupForm($season, "survey_and_scheduling_dates_tool.php"); 
print $page;


?>