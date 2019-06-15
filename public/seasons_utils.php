<?php

require_once 'start.php';

//////////////////////////////////////////////////////////////// DISPLAY FUNCTIONS

function renderSeasonsForm($seasons) {
		if (0) deb("seasons_utils.renderSeasonsForm(): _POST = ", $_POST);
	if (0) deb("seasons_utils.renderSeasonsForm(): getSeason('id') = " . getSeason('id'));
	if (0) deb("seasons_utils.renderSeasonsForm(): current_season_id = " . $current_season_id);
	$td_style = 'style="font-size:11pt; border: 1px solid lightgray; text-align:center;"'; 
	$form = "";
	$form .= '<form action="seasons.php" method="post" onsubmit="return false">';
	$form .= '<table style="width:50%; font-size:11pt; border-collapse: collapse;">';
	$form .= '<tr>';
		$form .= '<th style="text-align:center;">Season</th>';
		$form .= '<th style="text-align:center;">Months</th>';
		$form .= '<th style="text-align:center;">Current</th>';
		$form .= '<th style="text-align:center;"></th>';
	$form .= '</tr>';

	// Get data for Seasons table
	foreach($seasons as $season) { 
		// Render season data table
		$months = date("M", strtotime($season['start_date'])) . " - " . date("M", strtotime($season['end_date'])) . " " . $season['year'];
		// $checked = ($season['id'] == SEASON_ID) ? "checked" : "";
		$checked = ($season['id'] == getSeason('id')) ? "checked" : "";
		$gold_star = ($checked) ? '<img src="/display/images/goldstar02.png" height="12">' : '';
		$form .= '<tr>';
		$form .= '<td ' . $td_style . '><a href="' . $dir . '/admin.php?season_id=' . $season['id'] . '&parent_process_id=' . SET_UP_SEASON_ID . '">' . $season['name'] . '</a></td>';
		$form .= '<td ' . $td_style . '>' . $months . '</td>';
		// $form .= '<td ' . $td_style . '>' . date("M d Y", strtotime($season['survey_opening_date'])) . '</td>';
		// $form .= '<td ' . $td_style . '>' . date("M d Y", strtotime($season['survey_closing_date'])) . '</td>';
		// $form .= '<td ' . $td_style . '>' . '<input type="radio" name="season_id" id="season_id" value="' .  $season['id'] . '" ' . $checked . '>' . '</td>';
		$form .= '<td ' . $td_style . '>' . $gold_star . '</td>';  
	if (seasonIsDeletable($season)) {
			$form .= '<td ' . $td_style . '>' . '<input type="submit" onClick="confirmDeleteSeason(' . $season['id'] . ', &quot;'. $season['name'] . '&quot;)"  name="' . $season['id'] . '_delete" value="Delete this Season">' . '</td>';
		} else {
			$form .= '<td ' . $td_style . '></td>';
		}
		$form .= '</tr>';
	}
	$form .= '</table>';
	$form .= '<br>';

	// // Show save and cancel buttons (which lead back to this page)
	// $form .= '<input type="submit" value="Save Changes"> <input type="reset" value="Cancel Changes">';
	// if ($refresh_link) $form .= $refresh_link;

	// // Show create & edit season button (which leads to "season.php" form) and cancel button
	// $form .= '<input type="hidden" name="season_id" value="">';
	// $form .= '<input type="submit" value="Create New Season"> <input type="reset" value="Cancel">';
	
	$form .= '</form>';
	$form .= '<br><br>';	
	return $form;
}

function renderCurrentSeasonControl($action, $seasons=null) {

	if (!$seasons) $seasons = sqlSelect("*", SEASONS_TABLE, "", "start_date", (0), "Seasons"); 

	$form = "";
	$form .= '<form action="' . $action . '" method="post">';
	$form .= '<table style="font-size:11pt;">';
	$select .= '<select name="current_season_id">';

	// Get seasons for the current season selector
	if (0) deb("seasons.renderCurrentSeasonControl(): SEASON_ID = " . SEASON_ID);	
	if (0) deb("seasons.renderCurrentSeasonControl(): post['current_season_id'] = " . $_POST['current_season_id']);	
	$select .= '<option value="new">' . 'Create New Season' . '</option>';
	foreach($seasons as $season) {
		$selected = ($season['current_season'] == 1) ? 'selected ' : '';
		$select .= '<option value="' . $season['id'] . '" ' . $selected . '>' . $season['name'] . '</option>';
	}
	$form .= '<tr><td style="text-align:right">select season to work on:</td><td>' . $select . '</td></tr>';
	$form .= '</select>';
	$form .= '</table>'; 
	$form .= '<br>';

	// Show save and cancel buttons (which lead back to this page)
	$form .= '<input type="submit" value="Save Changes"> <input type="reset" value="Cancel Changes">';
	if ($refresh_link) $form .= $refresh_link;
	$form .= '<br><br>';
	return $form;
}


//////////////////////////////////////////////////////////////// DATABASE FUNCTIONS 

function deleteSeason($season_id) {
	sqlDelete(SEASONS_TABLE, "id = " . $season_id, (0), "seasons_utils.deleteSeason()", TRUE);
}

function seasonIsDeletable($season) {
	if (0) deb("seasons_utils.seasonIsDeletable(): season = ", $season);
	$season_id = $season['id'];
	// Can't delete the current season
	if ($season['current_season']) return FALSE; 
	// Can delete a season if it's marked deletable
	if ($season['deletable']) return TRUE; 
	return FALSE;
}
?>