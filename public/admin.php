<?php
session_start();

require_once 'utils.php';
require_once 'globals.php';
require_once 'classes/calendar.php';
require_once 'classes/PeopleList.php'; 
require_once 'classes/OffersList.php';
require_once 'classes/person.php';
require_once 'classes/survey1.php';
require_once 'display/includes/header.php';
require_once 'participation.php';
require_once 'seasons_utils.php';
require_once 'season_utils.php';
require_once 'admin_utils.php';

// Replace any missing admin processes for all seasons
$seasons = sqlSelect("*", SEASONS_TABLE, "", "");
foreach($seasons as $season) {
	generateAdminProcessesForSeason($season['id']);
}

if ($_POST['season_id']) {	
	setSeason($_POST['season_id']);
} elseif($_GET['season_id']) {	
	setSeason($_GET['season_id']);
}
$season_id = getSeason('id');
$season_name = getSeason('name');

$page .= renderHeadline("Administrator Dashboard", HOME_LINK, "currently working on the {$season_name} season", 0);
$page .= '<br><h3><em>Work on This Season</em></h3>';
$stages = sqlSelect("*", ADMIN_PROCESSES_TABLE, "type = 'Stage' and season_id = {$season_id}", "display_order", (0));
foreach ($stages as $stage) {
	$page .= renderProcessLink($stage, ++$n);
}
$page .= '<br><h3><em>Work on Another Season</em></h3>'; 
$page .= render_new_season_link("Create a New Season");
$page .= render_seasons_link("List of All Seasons");
// $page .= "<br>";

// $page .= '<br><h3><em>Other Administrative Bits</em></h3>'; 
// $page .= render_update_admin_tasks_link("Update Admin Tasks for All Seasons");

print $page;

?>