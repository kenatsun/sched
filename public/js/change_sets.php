<?php
require_once 'start.php';
require_once "change_sets_utils.php";
print '<script src="js/change_sets.js"></script>';

if (0) deb("change_sets.php: start");
if (0) deb("change_sets.php: PREVIOUS_CRUMBS_IDS = " . PREVIOUS_CRUMBS_IDS . " CRUMBS_IDS = " . CRUMBS_IDS);

$scheduler_run_id = scheduler_run()['id'];
if (0) deb("change_sets.php: scheduler_run_id = " . $scheduler_run_id);

// Delete change sets of this scheduler run that were never saved.
purgeUnsavedChangeSets();

if (0) deb("change_sets.php: PREVIOUS_CRUMBS_IDS = " . PREVIOUS_CRUMBS_IDS); 

$headline = renderHeadline("Undo Changes?", "Latest changes shown first; undoing a change undoes all later changes too.", 1); 
$change_sets = sqlSelect("*", CHANGE_SETS_TABLE, "scheduler_run_id = " . $scheduler_run_id . " and published = 0", "when_saved desc", (0));

$change_sets_table = '<table style="table-layout:auto; width:1px; border-spacing: 0px; border-style: solid; border-width: 1px; border-color:LightGray;" >'; 

// Render undo action button and legend (initially hidden)
if (0) deb("change_sets.php: CHANGED_BACKGROUND_COLOR = " . CHANGED_BACKGROUND_COLOR);
$change_sets_table .= '
	<tr id="action_row" style="border-style:solid; border-width:1px; display:none; background-color:' . CHANGED_BACKGROUND_COLOR . '">
		<td colspan="4" style="border-style: solid; border-width: 1px; vertical-align:middle; border-color:LightGray; padding:8px; background-color:rgba(0,0,0,0);">
			<span style="font-size:11pt">
				Undo changes marked in ' . CHANGED_BACKGROUND_COLOR . '? 
			</span>
			<input type="submit" name="undo" value="Yes"> <input type="submit" name="no_undo" value="No">
		</td>
	</tr>
	';

// Render change sets table header
$change_sets_table .= '
	<tr style="border-style: solid; border-width: 1px;">
		<th style="border-style: solid; border-width: 1px; text-align:center; border-color:LightGray; padding:8px; white-space: nowrap; font-weight: bold; font-size: 150%; background-color:white;">change set</th> 
		<th style="border-style: solid; border-width: 1px; text-align:center; border-color:LightGray; padding:8px; white-space: nowrap; font-weight: bold; font-size: 150%; background-color:white;">saved at</th>
		<th style="border-style: solid; border-width: 1px; text-align:center; border-color:LightGray; padding:8px; white-space: nowrap; font-weight: bold; font-size: 150%; background-color:white;">undo?</th>
	</tr>';

$undo_name="undo_back_to_change_set_id";

// Render change sets
foreach($change_sets as $i=>$change_set) {
	$dt = new DateTime($change_set['when_saved']);
	$saved_date = $dt->format('F j');
	$saved_time = $dt->format('g:i a');
	$change_sets_table .= '
		<tr id="undo_tr_' . $change_set['id'] . '">
			<td style="border-style: solid; border-width: 1px; vertical-align:middle; border-color:LightGray; padding:8px; background-color:rgba(0,0,0,0);">' . renderChangeSet($change_set['id'], FALSE) . '</td>
			<td style="border-style: solid; border-width: 1px; vertical-align:middle; border-color:LightGray; padding:8px; white-space: nowrap; background-color:rgba(0,0,0,0);">' . $saved_date . '<br>' . $saved_time . '</td>
			<td style="border-style: solid; border-width: 1px; vertical-align:middle; border-color:LightGray; padding:8px; text-align: center; background-color:rgba(0,0,0,0);"><input type="radio" name="' . $undo_name . '" value="' . $change_set['id'] . '"></td>
		</tr>';
// }	$change_sets_table .= '
		// <tr id="undo_tr_' . $change_set['id'] . '">
			// <td style="border-style: solid; border-width: 1px; vertical-align:middle; border-color:LightGray; padding:8px; background-color:rgba(0,0,0,0);">' . renderChangeSet($change_set['id'], FALSE) . '</td>
			// <td style="border-style: solid; border-width: 1px; vertical-align:middle; border-color:LightGray; padding:8px; white-space: nowrap; background-color:rgba(0,0,0,0);">' . $saved_date . '<br>' . $saved_time . '</td>
			// <td style="border-style: solid; border-width: 1px; vertical-align:middle; border-color:LightGray; padding:8px; text-align: center; background-color:rgba(0,0,0,0);"><input type="radio" name="' . $undo_name . '" value="' . $change_set['id'] . '" onclick="nullRadio(\'' . $undo_name . '_' . $change_set['id'] . '\')" onchange="markUndos(' . $undo_name . ')" ></td>
		// </tr>';
}
// onclick="nullRadio(\'' . $undo_name . '_' . $change_set['id'] . '\')"
// id="' . $undo_name . '_' . $change_set['id'] . ' 
// onchange="markUndos(' . $undo_name . ')"

$change_sets_table .= '</table>';

$change_sets_form = '
	<form action="' . makeURI("teams.php", PREVIOUS_CRUMBS_IDS) . '" method="post">' .
		$change_sets_table . '
		<tr>
			<td colspan={$ncols}>
				<h2>&nbsp;&nbsp;&nbsp; 
					<input type="submit" name="undo" value="Confirm Undos"> 
					<input type="submit" name="discard" value="Discard Undos">
				</h2> 
			</td>
		<tr>
	</form>';

if (!$change_sets) $change_sets_form = "<br>There are no change sets at this time.";

$page = <<<EOHTML
	{$headline}
	{$change_sets_form}
EOHTML;
print $page;


?>