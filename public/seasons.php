<?php
require_once 'start.php';
require_once 'seasons_utils.php';

$dir = BASE_DIR;
if (0) deb("seasons.php: _COOKIE =", $_COOKIE);
if (0) deb("season.php: _POST =", $_POST); 
if (0) deb("season.php: _GET =", $_GET);

if ($_POST['set_current_season']) {	
	setSeason($_POST['set_current_season']);
} elseif($_GET['set_current_season']) {	
	setSeason($_GET['set_current_season']);
}

if ($_POST['delete_season']) deleteSeason($_POST['delete_season']);

$seasons = sqlSelect("*", SEASONS_TABLE, "", "start_date", (0), "Seasons"); 

$page = "";
$page .= renderHeadline("Manage Seasons", CRUMBS_DISPLAY); 
$page .= '<br>';
$page .= 'To work on a different season, click on its name.<br><br>';
$page .= renderSeasonsForm($seasons);
// $page .= renderCurrentSeasonControl("seasons.php", $seasons);
print $page;

?>