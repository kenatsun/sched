<?php
require_once 'start.php';
require_once 'classes/calendar.php';
require_once 'classes/PeopleList.php'; 
require_once 'classes/OffersList.php';
require_once 'classes/person.php';
require_once 'classes/survey1.php';
require_once 'participation.php';
require_once 'season_utils.php';
require_once 'admin_utils.php';

// Replace any missing admin processes for all seasons
$seasons = sqlSelect("*", SEASONS_TABLE, "", "");
foreach($seasons as $season) {
	generateAdminProcessesForSeason($season['id']);
}

if (0) deb("admin.php: _REQUEST['global_season_id'] = " . $_REQUEST['global_season_id']);

$season_id = getSeason('id');
$season_name = getSeason('name');
if (0) deb("admin.php: season_id = " . $season_id);

$page .= renderHeadline("Administrator Dashboard", "currently working on the {$season_name} season", 0);
$page .= '<br><h3><em>Work on this season</em></h3>';
$stages = sqlSelect("*", ADMIN_PROCESSES_TABLE, "type = 'Stage' and season_id = {$season_id}", "display_order", (0));
foreach ($stages as $stage) {
	$page .= renderProcessLink($stage, ++$n);
}
$page .= '<br><h3><em>Work on another season</em></h3>'; 
$page .= renderNewSeasonLink("Create a new season");
$page .= render_seasons_link("Select a season to work on");

print $page;

?>