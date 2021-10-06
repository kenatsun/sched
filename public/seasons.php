<?php
require_once 'start.php';
print '<script src="js/seasons.js"></script>';

if (0) deb("seasons.php: _COOKIE =", $_COOKIE);
if (0) deb("season.php: _POST =", $_POST); 
if (0) deb("season.php: _GET =", $_GET);

foreach($_POST as $key=>$value) {
	if (0) deb("season.php: key = " . $key . " value = " . $value . " strpos = " . strpos($key, "delete_season_")); 
	if (strpos($key, "delete_season_") === 0) deleteSeason($value);
}

$page = "";
$page .= renderHeadline("Manage Seasons"); 
$page .= '<br>';
$page .= 'The "Global Season" is the season that will be seen by all end users.<br>';
$page .= 'The "Session Season" is the season that you will see and work on in this session.<br><br>';
$page .= '<br>';
$page .= renderSeasonsForm();
print $page;


//////////////////////////////////////////////////////////////// DISPLAY FUNCTIONS

function renderSeasonsForm() {

	$seasons = sqlSelect("*", SEASONS_TABLE, "", "start_date", (0), "seasons_utils()"); 

	// Are any seasons marked as deletable?
	foreach($seasons as $season) {
		if (seasonIsDeletable($season)) $deletable_season_exists = 1;	
	}

	if (0) deb("seasons_utils.renderSeasonsForm(): _POST = ", $_POST);
	if (0) deb("seasons_utils.renderSeasonsForm(): getSeason('id') = " . getSeason('id'));
	if (0) deb("seasons_utils.renderSeasonsForm(): current_season_id = " . $current_season_id);
	if (0) deb("seasons_utils.renderSeasonsForm(): CRUMBS_IDS = " . CRUMBS_IDS);
	$td_style = 'style="font-size:11pt; border: 1px solid lightgray; text-align:center;"'; 
	$form = "";
	$form .= '<form action="' . makeURI("/seasons.php", CRUMBS_IDS, REQUEST_QUERY_STRING) . '" method="post">';
	$form .= '<form action="seasons.php" method="post" onsubmit="return false">';
	$form .= '<table style="width:50%; font-size:11pt; border-collapse: collapse;">';
	$form .= '<tr>';
		$form .= '<th style="text-align:center;">Season</th>';
		$form .= '<th style="text-align:center;">Months</th>';
		$form .= '<th style="text-align:center;">Set Global Season</th>';
		$form .= '<th style="text-align:center;">Set Session Season</th>';
		if ($deletable_season_exists) $form .= '<th style="text-align:center;">Delete Season(s)</th>';
	$form .= '</tr>';


	// Get data for Seasons table
	foreach($seasons as $season) {
		// Render season data table
		$months = date("M", strtotime($season['start_date'])) . " - " . date("M", strtotime($season['end_date'])) . " " . $season['year'];
		$global_checked = ($season['id'] == GLOBAL_SEASON_ID) ? "checked" : "";
		$session_checked = ($season['id'] == SEASON_ID) ? "checked" : "";
		$gold_star = ($checked) ? '<img src="/display/images/goldstar02.png" height="12">' : '';
		$form .= '<tr>';
		$form .= '<td ' . $td_style . '>' . $season['name'] . '</td>';
		$form .= '<td ' . $td_style . '>' . $months . '</td>';
		$form .= '<td ' . $td_style . '><input type="radio" name="global_season_id" value="' . $season['id'] . '" ' . $global_checked . '/></td>';  
		$form .= '<td ' . $td_style . '><input type="radio" name="session_season_id" value="' . $season['id'] . '" ' . $session_checked . '/></td>';
		if ($deletable_season_exists) {
			if (seasonIsDeletable($season)) {
					$form .= '<td ' . $td_style . '>' . '<input type="checkbox" name="delete_season_' . $season['id'] . '" value="' . $season['id'] . '"></td>';
				} else {
					$form .= '<td ' . $td_style . '></td>'; 
				}
		}
	}
	$form .= '</tr>';
	$form .= '</table>';
	$form .= '<br>';
	$form .= '<input type="submit" value="Save Changes">';
	$form .= '</form>';
	$form .= '<br><br>';	
	return $form;
}


//////////////////////////////////////////////////////////////// DATABASE FUNCTIONS 

function deleteSeason($season_id) {
	sqlDelete(SEASONS_TABLE, "id = " . $season_id, (0), "seasons_utils.deleteSeason()");
}

function seasonIsDeletable($season) {
	if (0) deb("seasons_utils.seasonIsDeletable(): season = ", $season);
	if (0) deb("seasons_utils.seasonIsDeletable(): season['deletable'] = ", $season['deletable']);
	$season_id = $season['id'];
	// Can't delete the global season
	if ($season['current_season']) return FALSE; 
	// Can't delete the session season
	if ($season['id'] == SEASON_ID) return FALSE; 
	// Can delete a season if it's marked deletable
	if ($season['deletable']) return TRUE; 
	return FALSE;
}

?>