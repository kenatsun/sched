<?php
session_start(); 

require_once 'globals.php';
require_once 'utils.php';
require_once 'display/includes/header.php';
$dir = BASE_DIR;
if (0) deb("seasons.php: _COOKIE =", $_COOKIE);
if (0) deb("season.php: _POST =", $_POST);
if (0) deb("season.php: _GET =", $_GET);

$seasons = sqlSelect("*", SEASONS_TABLE, "", "start_date", (0), "Seasons"); 

$page = "";
$page .= renderHeadline("Manage Seasons", HOME_LINK); 
$page .= renderSeasonsForm($seasons);
$page .= renderCurrentSeasonControl($seasons);
print $page;

function renderSeasonsForm($seasons) {
	$td_style = 'style="font-size:11pt; border: 1px solid lightgray; text-align:center;"'; 
	$form = "";
	$form .= '<form action="season.php" method="post">';
	$form .= '<table style="width:50%; font-size:11pt; border-collapse: collapse;">';
	$form .= '<tr><th style="text-align:center;">Season</th><th style="text-align:center;">Months</th><th style="text-align:center;">Survey Starts</th><th style="text-align:center;">Survey Ends</th><th style="text-align:center;">Current?</th></tr>';

	// Get data for Seasons table
	foreach($seasons as $i=>$season) {
		// Render season data table
		$months = date("M", strtotime($season['start_date'])) . " - " . date("M", strtotime($season['end_date'])) . " " . $season['year'];
		$current = ($season['id'] == SEASON_ID) ? "checked" : "";
		$gold_star = ($current) ? '<img src="/display/images/goldstar02.png" height="12">' : '';
		$form .= '<tr><td ' . $td_style . '><a href="' . $dir . '/season.php?season_id=' . $season['id'] . '">' . $season['name'] . '</a></td>
		<td ' . $td_style . '>' . $months . '</td>
		<td ' . $td_style . '>' . date("M d Y", strtotime($season['survey_opening_date'])) . '</td>
		<td ' . $td_style . '>' . date("M d Y", strtotime($season['survey_closing_date'])) . '</td>
		<td ' . $td_style . '>' . $gold_star . '</td></tr>'
		;
	
	}
	$form .= '</table>';
	$form .= '<br>';
	// Show? delete checkbox or button 

	// Show create & edit season button (which leads to "seasons.php" form) and cancel button
	$form .= '<input type="submit" value="Create New Season"> <input type="reset" value="Cancel">';
	$form .= '</form>';
	$form .= '<br><br>';	
	return $form;
}

function renderCurrentSeasonControl($seasons) {

	$form = "";
	$form .= '<form action="seasons.php" method="post">';
	$form .= '<table style="font-size:11pt;">';
	$select .= '<select name="current_season_id">';

	// Get seasons for the current season selector
	if (0) deb("seasons.renderCurrentSeasonControl(): SEASON_ID = " . SEASON_ID);	
	if (0) deb("seasons.renderCurrentSeasonControl(): post['current_season_id'] = " . $_POST['current_season_id']);	
	foreach($seasons as $i=>$season) {
		// $selected = ($season['id'] == $_POST['current_season_id']) ? 'selected ' : '';
		$selected = ($season['current_season'] == 1) ? 'selected ' : '';
		$select .= '<option value="' . $season['id'] . '" ' . $selected . '>' . $season['name'] . '</option>';
	}
	$form .= '<tr><td style="text-align:right">current season:</td><td>' . $select . '</td></tr>';
	$form .= '</select>';
	$form .= '</table>'; 
	$form .= '<br>';

	// Show save and cancel buttons (which lead back to this page)
	$form .= '<input type="submit" value="Save Changes"> <input type="reset" value="Cancel Changes">';
	if ($refresh_link) $form .= $refresh_link;
	$form .= '<br><br>';
	return $form;
}

?>