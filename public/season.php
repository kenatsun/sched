<?php 
require_once 'start.php';
require_once 'season_utils.php';
$dir = BASE_DIR;
print '<script src="js/season.js"></script>';


// Read the current data for this season (if it exists)
if (0) deb("season.php: _REQUEST =", $_REQUEST); 
if (0) deb("season.php: _POST =", $_POST); 
if (0) deb("season.php: _GET =", $_GET);
if (0) deb("season.php: _FILES =", $_FILES);
if (0) deb("season.php: _GET['parent_process_id'] = {$_GET['parent_process_id']}");
if (0) deb("season.php: array_key_exists('season_id', _POST['season_id']) =", array_key_exists('season_id', $_POST));

if (!array_key_exists('new_season', $_REQUEST)) {
	$season_id = SEASON_ID;
}

// Get the id of the parent admin process (stage)
if ($_REQUEST['parent_process_id']) $parent_process_id = $_REQUEST['parent_process_id'];

// If request is to store season data, do that
if (array_key_exists('season_status', $_POST) || array_key_exists('survey_setup', $_POST)) $season_id = saveChangesToSeason($_POST);
if (array_key_exists('edit_meals', $_POST)) saveChangesToMealsCalendar($_POST, $season_id);
if (array_key_exists('import_workers', $_POST)) importWorkersFromGather($_FILES, $season_id); 
if (array_key_exists('update_workers', $_POST)) saveChangesToWorkers($_POST, $season_id);

// Get the season (if any) to display
$season = sqlSelect("*", SEASONS_TABLE, "id = " . $season_id, "", (0), "season.php: season")[0];

// Display the page
$page = "";
$page .= renderHeadline("Set Up " . (($season) ? "the " . $season['name'] : "a New") . " Season", "", 0); 
$page .= renderPageBody($season, $parent_process_id);
print $page;


?>