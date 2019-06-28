<?php 
require_once 'start.php';
require_once 'season_utils.php';
$dir = BASE_DIR;

// Read the current data for this season (if it exists)
if (0) deb("season.php: _POST =", $_POST); 
if (0) deb("season.php: _FILES =", $_FILES);
if (0) deb("season.php: _GET =", $_GET);
if (0) deb("season.php: _GET['parent_process_id'] = {$_GET['parent_process_id']}");
if (0) deb("season.php: array_key_exists('season_id', _POST['season_id']) =", array_key_exists('season_id', $_POST));

// If request is a POST to display an existing season, POST['season_id'] will indicate the season
// If request is a POST to display an empty form to create a new season, POST['season_id'] will have value null
if (array_key_exists('season_id', $_POST)) {
	$season_id = $_POST['season_id'];
// If request is a GET to display an existing season, get the season_id from it
// If request is a GET to display an empty form to create a new season, GET['season_id'] will have value null
} elseif (array_key_exists('season_id', $_GET)) {
	$season_id = $_GET['season_id'];
	if ($season_id) setSeason($season_id);
}
// If request is to display the current season, do that
else $season_id = getSeason('id');

// Get the id of the parent admin process (stage)
if ($_GET['parent_process_id']) $parent_process_id = $_GET['parent_process_id'];
elseif ($_POST['parent_process_id']) $parent_process_id = $_POST['parent_process_id'];

// If request is to store season data, do that
if (array_key_exists('season_status', $_POST) || array_key_exists('survey_setup', $_POST)) $season_id = saveChangesToSeason($_POST);
if (array_key_exists('edit_meals', $_POST)) saveChangesToMealsCalendar($_POST, $season_id);
if (array_key_exists('import_workers', $_POST)) importWorkersFromGather($_FILES, $season_id); 
if (array_key_exists('update_workers', $_POST)) saveChangesToWorkers($_POST, $season_id);

// Get the season (if any) to display
$season = sqlSelect("*", SEASONS_TABLE, "id = " . $season_id, "", (0), "season.php: season")[0];

// Display the page
$page = "";
if (1) deb("season.php: NEXT_CRUMBS_IDS =", NEXT_CRUMBS_IDS); 
if (1) deb("season.php: CRUMBS_DISPLAY = " . CRUMBS_DISPLAY); 
$page .= renderHeadline("Set Up " . (($season) ? "the " . $season['name'] : "a New") . " Season", CRUMBS_DISPLAY, "", 0); 
$page .= renderPageBody($season, $parent_process_id);
print $page;


?>