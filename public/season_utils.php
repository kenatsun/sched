<?php
require_once 'start.php';
require_once 'admin_utils.php';


//////////////////////////////////////////////////////////////// DISPLAY FUNCTIONS

function renderPageBody($season, $parent_process_id) {
	
	if (0) deb("season_utils.renderPageBody(): season = ", $season);
	if (0) deb("season_utils.renderPageBody(): curr season = " . getSeason('id')); 
	$where = "type = 'Step' 
		and season_id = " . getSeason('id') . "
		and parent_process_id = " . SET_UP_SEASON_ID;
	$steps = sqlSelect("*", ADMIN_PROCESSES_TABLE, $where, "display_order", (0), "season_utils.renderPageBody():");
	$body = "";
	
	// Render the page components for each step
	foreach ($steps as $step) {
		$body .= '<a name="' . $step['process_id'] . '"></a>'; 
		$body .= '<br><br><h3>Step ' . ++$n . ': ' . $step['name'] . '</h3>';
		switch ($step['process_id']) {
			case EDIT_SEASON_ID:
				$body .= renderEditSeasonForm($season, $parent_process_id); 
				break;
			case EDIT_MEALS_CALENDAR_ID:
				$body .= renderEditMealsCalendarForm($season, $parent_process_id);
				break;
			case EXPORT_MEALS_ID:
				$body .= renderExportMealsForm($season, "create");
				break;
			// case EXPORT_MEALS_ID:
				// $body .= renderExportMealsForm($season, "season.php", "create");
				// break;
			case IMPORT_WORKERS_ID:
				$body .= renderWorkerImportForm($season, $parent_process_id);
				break;
			case EDIT_WORKERS_ID:
				$body .= renderWorkerEditForm($season, $parent_process_id);
				break;
			case EDIT_LIAISONS_ID:
				$body .= renderLiaisonEditForm($season, $parent_process_id);
				break;
			case SET_SURVEY_DATES_ID:
				$body .= renderSurveySetupForm($season, "season.php", $parent_process_id);
				break;  
			case ANNOUNCE_SURVEY_ID:
				$body .= '<p><a style="margin-left:2em;" href="docs/announcement - poster version.doc" download>Download Announcement (poster version)</a></p>';
				$body .= '<p><a style="margin-left:2em;" href="docs/announcement - mail merge version.doc" download>Download Announcement (mail merge version)</a></p>';
				$filename = "docs/announcement - workers list.csv";
				if (0) deb("survey_steps.renderPageBody(): calling exportSurveyAnnouncementCSV()"); 
				exportSurveyAnnouncementCSV($season, $filename); 
				$body .= '<p><a style="margin-left:2em;" href="' . $filename . '" download>Download Workers List</a></p>';
				break;
		}
	}
	return $body;
}


// Render the form to display, create, or update this season
function renderEditSeasonForm($season, $parent_process_id) {
	
	if (0) deb("season.renderEditSeasonForm(): start: season =", $season);
	$season_status = ($season) ? "existing" : "new";
	if ($season_status == "existing") {		// Existing season's start and end months are not updatable
		$start_month_value = date("F Y", strtotime($season['start_date']));
		$end_month_value = date("F Y", strtotime($season['end_date']));
		$required = "";
	} else {			// New season's start and end months are writable
		$start_month_value = renderUpcomingMonthsSelectList("season_start_month", $season['start_date'], 2);
		$end_month_value = renderUpcomingMonthsSelectList("season_end_month", $season['end_date'], 2);
		$required = REQUIRED_MARKER;
	}
	$form = "";
	$form .= '<p>' . REQUIRED_MARKER . ' marks required fields</p>';
	$form .= '<form action="' . makeURI("season.php", PREVIOUS_CRUMBS_IDS, "", EDIT_SEASON_ID) . '" method="post" name="edit_season_form">'; 
	$form .= '<input type="hidden" name="season_id" value="' . $season['id'] . '">';
	$form .= '<input type="hidden" name="season_status" value="' . $season_status . '">';
	$form .= '<table style="font-size:11pt;">';

	// Season name
	$form .= '<tr><td style="text-align:right">name (without year):</td>
		<td><input type="text" id="name_without_year" onblur="setSeasonFullName()" name="name_without_year" value="' . $season['name_without_year'] . '"> ' . REQUIRED_MARKER . '</td></tr>';
	$form .= '<tr><td style="text-align:right">year:</td>
		<td><input type="text" id="year" onblur="setSeasonFullName()" name="year" value="' . $season['year'] . '"></td></tr>';
	$form .= '<tr><td style="text-align:right">full season name:</td>
		<td id="full_name">' . $season['name'] . '</td></tr>';
	$form .= '<input type="hidden" id="name" name="name" value="' . $season['name'] . '">';
	

	// Season start and end dates
	if (0) deb("season.renderEditSeasonForm(): season['start_date'] =", $season['start_date']);
	if (0) deb("season.renderEditSeasonForm(): start_month_value =", $start_month_value);
	$form .= '<tr><td style="text-align:right">first month of season:</td><td>' . $start_month_value . ' ' . $required . '</td></tr>';
	if (0) deb("season.renderEditSeasonForm(): end_month_value =", $end_month_value);
	$form .= '<tr><td style="text-align:right">last month of season:</td><td>' . $end_month_value . ' ' . $required . '</td></tr>';
	
	// Communities invited to dine
	$form .= '<tr><td style="text-align:right">communities invited:</td><td>';
	$communities = sqlSelect("*", COMMUNITIES_TABLE, "", "this desc, name asc", (0));
	foreach($communities as $community) {
		$where = "community_id = " . $community['id'] . " AND season_id = " . $season['id'];
		$checked = (sqlSelect("'x'", COMMUNITIES_INVITED_TO_MEALS_TABLE, $where, "", (0))[0]) ? "checked" : "";
		$form .= '<input type="checkbox" id="id_invited_' . $community['id'] . '" name="invited_' . $community['id'] . '" value="' . $community['id'] . '" ' . $checked . '>' . $community['name'];
	}
	$form .= '</td></tr>';
	
	// Season is deletable?
	$checked = ($season['deletable']) ? "checked" : "";
	$form .= '<tr><td style="text-align:right">deletable?:</td><td><input type="checkbox" name="deletable" ' . $checked . '></td></tr>';	
	
	$form .= '</table>'; 
	$form .= '<br>'; 
	$form .= '<input type="submit" value="Save Changes"> <input type="reset" value="Cancel Changes">';
	$form .= '</form>'; 
	
	return $form;
}


function renderDateInputFields($date, $prefix="") {
	if ($date) {
		$month = date("m", strtotime($date));
		$day = date("d", strtotime($date));
		$year = date("Y", strtotime($date));
	}
	if ($prefix) $prefix .= "_";
	$fields = "";
	$fields .= '<input type="text" style="width: 24px;" name="' . $prefix . 'month" value="' . $month . '"> / ';
	$fields .= '<input type="text" style="width: 24px;" name="' . $prefix . 'day" value="' . $day . '"> / ';
	$fields .= '<input type="text" style="width: 48px;" name="' . $prefix . 'year" value="' . $year . '">';
	return $fields;	
}


function renderEditMealsCalendarForm($season, $parent_process_id) {
	if (!$season) return;
	$meals = sqlSelect("*", MEALS_TABLE, "season_id = {$season['id']}", "date", (0), "renderEditMealsCalendarTable(): meals in season");
	if (!$meals) return;

	$form = '';
	if (0) deb("season.renderEditMealsCalendarForm() season from arg = ", $season);
	$form .= '<form enctype="multipart/form-data" action= ' . makeURI("season.php", PREVIOUS_CRUMBS_IDS, "", EDIT_MEALS_CALENDAR_ID) . ' method="POST" name="edit_meals_calendar_form">'; 
	$form .= '<input type="hidden" name="season_id" value="' . $season['id'] . '">';
	$form .= '<input type="hidden" name="edit_meals">';
	$form .= renderEditMealsCalendarTable($season, $meals);
  $form .= '<p><input type="submit" value="Save Changes"><input type="reset" value="Cancel Changes"></p>';
	$form .= '</form>';
	return $form;	
}


function renderEditMealsCalendarTable($season, $meals) {  
	$table = '';
	$table .= '<table style="table-layout:auto; width:1px; vertical-align:middle;" border="1" >';
	$table .= '<tr>
		<th style="width:1px; white-space:nowrap; text-align:center; padding:4px;">Meal Date</th>
		<th style="width:1px; white-space:nowrap; text-align:center; padding:4px;">Include in Calendar?</th>
		<th style="width:1px; white-space:nowrap; text-align:center; padding:4px;">Reason for No Meal</th>
		</tr>';
	foreach($meals as $meal) {
		$row = '<tr>';
		$date = date_format(date_create($meal['date']), "D M j");
		$row .= '<td style="width:1px; white-space:nowrap; padding:4px; vertical-align:middle;">' . $date . '</td>';
		$checked = ($meal['skip_indicator']) ? "" : "CHECKED";
		$row .= '<td style="width:1px; white-space:nowrap; text-align:center; padding:4px; vertical-align:middle;">' . '<input type="checkbox" name="' . $meal['id'] . '_include" ' . $checked . ' ></td>';
		$row .= '<td style="width:1px; white-space:nowrap; padding:4px; vertical-align:middle;">' . '<input type="text" name="' . $meal['id'] . '_reason" value="' . $meal['skip_reason'] . '">' . '</td>';
		$row .= '</tr>';
		$table .= $row;
	}
	
	// XXX KEEPING THIS CODE (originally from renderWorkerTable()) IN CASE WANT TO ENABLE ADDING A MEAL
	// $table .= '<tr><th colspan="50"><i>Add a Meal ( * means required field):</i></th></tr>';
	// $table .= '<tr>
		// <th style="width:1px; white-space:nowrap; text-align:center; padding:4px;">In Season?</th>
		// <th style="width:1px; white-space:nowrap; text-align:center; padding:4px;">First Name *</th>
		// <th style="width:1px; white-space:nowrap; text-align:center; padding:4px;">Last Name *</th>
		// <th style="width:1px; white-space:nowrap; text-align:center; padding:4px;">Email *</th>
		// </tr>';
	// $row = '<tr>';
	// $row .= '<td>' . '<input type="checkbox" name=" new_current" CHECKED></td>';
	// $row .= '<td>' . '<input type="text" name=" new_first_name">' . '</td>';
	// $row .= '<td>' . '<input type="text" name=" new_last_name">' . '</td>';
	// $row .= '<td>' . '<input type="text" name=" new_email">' . '</td>';
	// $row .= '<td>' . '</td>';
	// $row .= '<td>' . '<input type="checkbox" name=" new_dont_add"></td>';
	// $row .= '</tr>';
	// $table .= $row;
	$table .= '</table>';
	return $table;
}


function renderWorkerImportForm($season, $parent_process_id) {
	if (!$season) return;
	$form = '';
	$form .= '<form enctype="multipart/form-data" action="' . makeURI("season.php", PREVIOUS_CRUMBS_IDS, "", IMPORT_WORKERS_ID) . '" method="POST" name="import_workers_form">';
	$form .= '<input type="hidden" name="season_id" value="' . $season['id'] . '">';
	$form .= '<input type="hidden" name="import_workers">';
    $form .= '<input type="hidden" name="MAX_FILE_SIZE" value="30000" />';
    $form .= '<p>Select file to import: <input name="userfile" type="file" /></p>';
    $form .= '<p><input type="submit" value="Import Workers" /><input type="reset" value="Cancel Import"></p>';
	$form .= '</form>';
	return $form;
}


function renderWorkerEditForm($season, $parent_process_id) {
	if (!$season) return;
	if (!sqlSelect("id", SEASON_WORKERS_TABLE, "season_id = {$season['id']}", "", (0), "renderWorkerEditForm(): season_workers")) return;
	$form = '';
	if (0) deb("season.renderWorkerEditForm() season from arg = ", $season);
	$form .= '<form enctype="multipart/form-data" action="' . makeURI("season.php", PREVIOUS_CRUMBS_IDS, "", EDIT_WORKERS_ID) . '" method="POST" name="edit_workers_form">';
	$form .= '<input type="hidden" name="season_id" value="' . $season['id'] . '">';
	$form .= '<input type="hidden" name="update_workers">';
	$form .= renderWorkerTable($season);
  $form .= '<p><input type="submit" value="Save Changes" /><input type="reset" value="Cancel Changes" /></p>';
	$form .= '</form>';
	return $form;	
}


function renderWorkerTable($season) { 
	$table = '';
	$table .= '<table style="table-layout:auto; width:1px; vertical-align:middle;" border="1" >';
	$workers = sqlSelect("*", AUTH_USER_TABLE, "", "current desc, first_name asc, last_name asc", (0), "renderWorkerTable(): workers");
	$last_current = -1;
	foreach($workers as $worker) {
		if ($last_current != $worker['current']) {  // Print a header dividing current from not-current workers
			$not = ($worker['current']) ? "" : "<i>not</i> ";
			$table .= '<tr><th colspan="50" style="width:1px; white-space:nowrap; padding:4px;"><i>Workers ' . $not . 'in the ' . $season['name'] . ' season:</i></th></i></tr>';
			$table .= '<tr>
				<th style="width:1px; white-space:nowrap; text-align:center; padding:4px;">In Season?</th>
				<th style="width:1px; white-space:nowrap; text-align:center; padding:4px;">First Name</th>
				<th style="width:1px; white-space:nowrap; text-align:center; padding:4px;">Last Name</th>
				<th style="width:1px; white-space:nowrap; text-align:center; padding:4px;">Email</th>
				<th style="width:1px; white-space:nowrap; text-align:center; padding:4px;">Unit</th>
				<th style="width:1px; white-space:nowrap; text-align:center; padding:4px;">Delete?</th>
				</tr>';
			$last_current = $worker['current'];
		}
		$from_gather = ($worker['gid']) ? 1 : 0;  // Was the worker imported from Gather or manually entered into MO?
		if ($from_gather) {
			$first_name_field = $worker['first_name'];
			$last_name_field = $worker['last_name'];
			$unit_field = '<input type="text" name="' . $worker['id'] . '_unit" value="' . $worker['unit'] . '">';
			$delete_field = '';
		} else {
			$first_name_field = '<input type="text" name="' . $worker['id'] . '_first_name" value="' . $worker['first_name'] . '">';
			$last_name_field = '<input type="text" name="' . $worker['id'] . '_last_name" value="' . $worker['last_name'] . '">';
			$unit_field = '';
			$delete_field = '<input type="checkbox" name="' . $worker['id'] . '_delete">';
		}
		$checked = ($worker['current']) ? "CHECKED" : "";
		$row = '<tr>';
		$row .= '<td style="text-align:center">' . '<input type="checkbox" name="' . $worker['id'] . '_current" ' . $checked . ' ></td>';
		$row .= '<td>' . $first_name_field . '</td>';
		$row .= '<td>' . $last_name_field . '</td>';
		$row .= '<td>' . '<input type="text" name="' . $worker['id'] . '_email" value="' . $worker['email'] . '">' . '</td>';
		// $row .= '<td>' . '<input type="text" name="' . $worker['id'] . '_unit" value="' . $worker['unit'] . '">' . '</td>';
		$row .= '<td>' . $unit_field . '</td>';
		$row .= '<td style="text-align:center">' . $delete_field . '</td> ';
		$row .= '</tr>';
		$table .= $row;
	}
	$table .= '<tr><th colspan="50"><i>Add a non-' . COMMUNITY . ' Worker ( ' . REQUIRED_MARKER . ' marks required fields ):</i></th></tr>';
	$table .= '<tr>
		<th style="width:1px; white-space:nowrap; text-align:center; padding:4px;">In Season?</th>
		<th style="width:1px; white-space:nowrap; text-align:center; padding:4px;">First Name ' . REQUIRED_MARKER . '</th>
		<th style="width:1px; white-space:nowrap; text-align:center; padding:4px;">Last Name ' . REQUIRED_MARKER . '</th>
		<th style="width:1px; white-space:nowrap; text-align:center; padding:4px;">Email ' . REQUIRED_MARKER . '</th>
		</tr>';
	$row = '<tr>';
	$row .= '<td style="text-align:center">' . '<input type="checkbox" name=" new_current" CHECKED></td>';
	$row .= '<td>' . '<input type="text" name=" new_first_name">' . '</td>';
	$row .= '<td>' . '<input type="text" name=" new_last_name">' . '</td>';
	$row .= '<td>' . '<input type="text" name=" new_email">' . '</td>';
	$row .= '</tr>';
	$table .= $row;
	$table .= '</table>';
	return $table;
}


function renderLiaisonEditForm($season, $parent_process_id) {
	if (0) deb("season.renderLiaisonEditForm() season from arg = ", $season);
	if (!$season) return;
	if (!sqlSelect("id", SEASON_LIAISONS_TABLE, "season_id = {$season['id']}", "", (0), "renderLiaisonEditForm(): season_liaisons")) return;
	$form = '';
	if (0) deb("season.renderLiaisonEditForm() season from arg = ", $season);
	$form .= '<form enctype="multipart/form-data" action="' . makeURI("season.php", PREVIOUS_CRUMBS_IDS, "", EDIT_WORKERS_ID) . '" method="POST" name="edit_liaisons_form">';
	$form .= '<input type="hidden" name="season_id" value="' . $season['id'] . '">';
	$form .= '<input type="hidden" name="update_liaisons">';
	$form .= renderLiaisonTable($season);
	$form .= '<p><input type="submit" value="Save Changes" /><input type="reset" value="Cancel Changes" /></p>';
	$form .= '</form>';
	return $form;	
}

function renderLiaisonTable($season) { 
	$select = "w.*";
	$from = AUTH_USER_TABLE . " as w, " . SEASON_LIAISONS_TABLE . " as l";
	$where = "season_id = " . SEASON_ID .
		" AND l.worker_id = w.id";
	$order_by = "first_name asc, last_name asc";
	$liaisons = sqlSelect($select, $from, $where, $order_by, (0), "renderLiaisonTable(): liaisons");
	$table = '<table style="table-layout:auto; width:1px; vertical-align:middle;" border="1" >';
	// $table .= '<tr><th colspan="50"><i>Helpers:</i></th></tr>';
	$table .= '
		<tr>
			<th style="width:1px; white-space:nowrap; text-align:center; padding:4px;">Name</th>
			<th style="width:1px; white-space:nowrap; text-align:center; padding:4px;">Delete?</th>
		</tr>'
	;
	foreach($liaisons as $liaison) {
		$row = '<tr>';
		$row .= '<td>' . $liaison['first_name'] . " " . $liaison['last_name'] . '</td>';
		$row .= '<td style="text-align:center"><input type="checkbox" name="delete_liaisons[]" value="' . $liaison['id'] . '"></td> ';
		$row .= '</tr>';
		$table .= $row;
	}

	$select = "w.*";
	$from = AUTH_USER_TABLE . " as w, " . SEASON_WORKERS_TABLE . " as sw";
	$where = "sw.season_id = " . SEASON_ID . " " .
		"AND sw.worker_id = w.id " .
		"AND NOT (w.id IN (SELECT worker_id FROM " . SEASON_LIAISONS_TABLE . " WHERE season_id = " . SEASON_ID . "))";
	$order_by = "first_name asc, last_name asc";
	$workers = sqlSelect($select, $from, $where, $order_by, (0), "renderLiaisonTable(): workers");
	$table .= '<tr><th colspan="50"><i>Add a helper:</i></th></tr>';
	$table .= '
		<tr>
			<td>' . '
				<select name="add_liaison">
					<option value=""> '
	;
	foreach($workers as $worker) {
		$table .= '
			<option value="' . $worker['id'] . '"> ' .
				$worker['first_name'] . ' ' . $worker['last_name'] . ' 
			</option>'
		;
	}
	$table .= '
			</td>
		</tr>'
	;
	$table .= '</table>';
	return $table;
}


function renderSurveySetupForm($season, $next, $parent_process_id=null) {
	if (!$season) return;

	if (0) deb("season.renderSurveySetupForm(): season_id =", $season['id']);
	$form = "";
	$form .= '<form action="' . makeURI($next, PREVIOUS_CRUMBS_IDS, "", SET_SURVEY_DATES_ID) . '" method="post" name="set_survey_dates_form">';
	$form .= '<input type="hidden" name="season_id" value="' . $season['id'] . '">';
	$form .= '<input type="hidden" name="survey_setup">';
	$form .= '<table style="font-size:11pt;">';

	// Survey opening date
	$survey_opening_date = renderDateInputFields($season['survey_opening_date'], "survey_opening");
	$form .= '<tr><td style="text-align:right">first day of survey (mm/dd/yyyy):</td><td>' . $survey_opening_date . '</td></tr>';

	// Survey closing date
	$survey_closing_date = renderDateInputFields($season['survey_closing_date'], "survey_closing");
	$form .= '<tr><td style="text-align:right">last day of survey (mm/dd/yyyy):</td><td>' . $survey_closing_date . '</td></tr>';
	
	// Manually extend closed survey, or re-close it
	$checked = (sqlSelect("*", SEASONS_TABLE, "id = " . $season['id'], "", (0))[0]['survey_extended']) ? "checked" : ""; 
	$form .= '<tr><td style="text-align:right">extend survey past last day?:</td><td><input type="checkbox" name="survey_extended" ' . $checked . '></td></tr>';

	// Scheduling start date
	$scheduling_start_date = renderDateInputFields($season['scheduling_start_date'], "scheduling_start");
	$form .= '<tr><td style="text-align:right">first day of scheduling (mm/dd/yyyy):</td><td>' . $scheduling_start_date . '</td></tr>';

	// Change request end date
	$change_request_end_date = renderDateInputFields($season['change_request_end_date'], "change_request_end");
	$form .= '<tr><td style="text-align:right">last day to submit change requests (mm/dd/yyyy):</td><td>' . $change_request_end_date . '</td></tr>';

	// Scheduling end date
	$scheduling_end_date = renderDateInputFields($season['scheduling_end_date'], "scheduling_end");
	$form .= '<tr><td style="text-align:right">last day of scheduling (mm/dd/yyyy):</td><td>' . $scheduling_end_date . '</td></tr>';
	
	$form .= '</table>'; 
	$form .= '<br>'; 
	$form .= '<input type="submit" value="Save Changes"> <input type="reset" value="Cancel Changes">';
	$form .= '</form>'; 
	
	// $filename = "announce.csv";
	// print exportSurveyAnnouncementCSV($season, $filename); 
	// $form .= '<br><a href="' . $filename . '" download><strong>Download Announcement File</strong></a>';
	
	return $form;
}

//////////////////////////////////////////////////////////////// DATABASE FUNCTIONS

// Create or update season in the database
function saveChangesToSeason($post) {
	if (1) deb("season.saveChangesToSeason(): post =", $post);
	$season_id = getSeason('id');
	
	$postcols = array();

	// Process data from the season details form
	if (array_key_exists('season_status', $post)) {
		if ($post['name_without_year']) $postcols[] = array("sql"=>"name_without_year", "value"=>$post['name_without_year']);
		else $required_fields_missing .= "name_without_year&";

		$postcols[] = array("sql"=>"year", "value"=>$post['year']);
		
		if ($post['name_without_year'] && $post['year']) $separator = " "; else $separator = "";
		$postcols[] = array("sql"=>"name", "value"=>$post['name_without_year'] . $separator . $post['year']);

		if ($post['season_start_month']) $postcols[] = array("sql"=>"start_date", "value"=>$post['season_start_month'] . "-01");
		else $required_fields_missing .= "start_date&";

		if ($post['season_end_month']) $postcols[] = array("sql"=>"end_date", "value"=>$post['season_end_month'] . "-" . date("t", strtotime($post['season_end_month']) . "-01"));
		else $required_fields_missing .= "end_date&";
		if (0) deb("season.saveChangesToSeason(): required_fields_missing = $required_fields_missing");
		
		if ($post['season_end_month']) $postcols[] = array("sql"=>"year", "value"=>date("Y", strtotime($post['season_end_month'])));
		
		$deletable = $post['deletable'] ? 1 : 0;
		$postcols[] = array("sql"=>"deletable", "value"=>$deletable); 
		
	}

	// Process data from the survey setup form
	if (array_key_exists('survey_setup', $post)) {
		$postcol = date_postcol($post['survey_opening_year'], $post['survey_opening_month'], $post['survey_opening_day'], "survey_opening_date");
		if ($postcol) $postcols[] = $postcol;
		$postcol = date_postcol($post['survey_closing_year'], $post['survey_closing_month'], $post['survey_closing_day'], "survey_closing_date");
		if ($postcol) $postcols[] = $postcol;
		$postcol = date_postcol($post['scheduling_start_year'], $post['scheduling_start_month'], $post['scheduling_start_day'], "scheduling_start_date");
		if ($postcol) $postcols[] = $postcol;
		$postcol = date_postcol($post['change_request_end_year'], $post['change_request_end_month'], $post['change_request_end_day'], "change_request_end_date");
		if ($postcol) $postcols[] = $postcol;
		$postcol = date_postcol($post['scheduling_end_year'], $post['scheduling_end_month'], $post['scheduling_end_day'], "scheduling_end_date");
		if ($postcol) $postcols[] = $postcol;
		$postcols[] = array("sql"=>"survey_extended", "value"=>$post['survey_extended']);
		}

	// Make SQL elements from each postcol
	foreach($postcols as $postcol) {
		if ($set) $set .= ", ";
		$set .= $postcol['sql'] . " = '" . $postcol['value'] . "'";
		if ($columns) $columns .= ", ";
		$columns .= $postcol['sql'];
		if ($values) $values .= ", ";
		$values .= "'" . $postcol['value'] . "'";	
	} 	
	if (0) deb("season_utils.saveChangesToSeason(): postcols =", $postcols);
	if (0) deb("season_utils.saveChangesToSeason(): set =", $set);
	if (0) deb("season_utils.saveChangesToSeason(): columns =", $columns);
	if (0) deb("season_utils.saveChangesToSeason(): values =", $values);
	if (0) deb("season_utils.saveChangesToSeason(): season_id =", $season_id);
	
	// If season_status is new, and no required fields are empty create a new season and generate its components
	if ($post['season_status'] == 'new' && !$required_fields_missing) {
		if (0) deb("season.saveChangesToSeason(): columns =", $columns);
		if (0) deb("season.saveChangesToSeason(): values =", $values);
		$new_id = sqlSelect("max(id) as max_id", SEASONS_TABLE, "", "", (0))[0]['max_id'] + 1;
		sqlInsert(SEASONS_TABLE, "id, " . $columns, $new_id . ", " . $values, (0), "seasons.saveChangesToSeason()");
		$season_id = sqlSelect("max(id) as id", SEASONS_TABLE, "", "")[0]['id'];
		sqlUpdate(SESSIONS_TABLE, "season_id = " . $season_id, "session_id = '" . SESSION_ID . "'");
		// setSeason($season_id);
		if (0) deb("season_utils.saveChangesToSeason(): new season id: $season_id, new current season id: " . getSeason("id"));
		
		// Set new season as current_season
		sqlUpdate(SEASONS_TABLE, "current_season = NULL", "", "", (0));
		sqlUpdate(SEASONS_TABLE, "current_season = 1", "id = {$season_id}", "", (0), "season.saveChangesToSeason(): set new season as current_season");	

		// Generate the admin processes for this new season
		generateAdminProcessesForSeason($season_id);
		
		// Generate the default-invited communities for this new season
		generateInvitedCommunitiesForSeason($season_id);
		
		// Generate the jobs for this new season
		generateJobsForSeason($season_id);
		
		// Generate the meals and shifts for this new season
		generateMealsForSeason($season_id);
		
		// Generate the liaisons for this new season (can't do till after workers are imported)
		generateLiaisonsForSeason($season_id);
		
		// Record the number of shifts this season for each job
		$jobs = sqlSelect("*", SURVEY_JOB_TABLE, "season_id = " . $season_id, "display_order", (0), "season.generateJobsForSeason(): job types");
		foreach($jobs as $i=>$job) {
			$shift_count = sqlSelect("count(distinct id) as count", SCHEDULE_SHIFTS_TABLE, "job_id = " . $job['id'], "", (0), "season.saveChangesToSeason(): shifts count")[0]['count'];
			$workers_count = $shift_count * $job['workers_per_shift'];
			sqlUpdate(SURVEY_JOB_TABLE, "instances = $workers_count", "id = {$job['id']}", (0), "season.saveChangesToSeason(): shifts count");
		}
	}
	// Else update the existing season
	else {
		$where = "id = {$season_id}";
		sqlUpdate(SEASONS_TABLE, $set, $where, (0), "season.saveChangesToSeason(): update");
	}
	
	// Populate the COMMUNITIES_INVITED_TO_MEALS_TABLE
	sqlDelete(COMMUNITIES_INVITED_TO_MEALS_TABLE, "season_id = " . $season_id, (1), "season_utils.saveChangesToSeason(): delete invited communities", TRUE);
	foreach($post as $post_item) {
		if (1) deb("season.saveChangesToSeason(): key($post_item): " . key($post));
		if (substr_count(key($post),"invited_")) {
			$columns = "season_id, community_id";
			$values = $season_id . ", " . $post_item;
			sqlInsert(COMMUNITIES_INVITED_TO_MEALS_TABLE, $columns, $values, (1), "season_utils.saveChangesToSeason(): insert invited community", TRUE);
		}
		next($post);
	}

	return $season_id;
}

function date_postcol($year=0, $month=0, $day=999, $column_name="") {
	if (checkdate(intval($month), intval($day), intval($year))) {		// If a valid date, return it
		return array("sql"=>$column_name, "value"=>date_format(date_create($year."-".$month."-".$day), "Y-m-d"));
	} elseif (!$month && !$day && !$year) {		// If a null date, return null
		return array("sql"=>$column_name, "value"=>null);
	} else { 
		return null;
	}
}


function generateInvitedCommunitiesForSeason($season_id) {
	$communities = sqlSelect("*", COMMUNITIES_TABLE, "invited_default = 1", "", (1), "season.generateInvitedCommunitiesForSeason(): invited defaults");
	foreach ($communities as $i=>$community) {
		$columns = "season_id, community_id";
		$values = $season_id . ", '{$community['id']}'";
		sqlInsert(COMMUNITIES_INVITED_TO_MEALS_TABLE, $columns, $values, (1), "season.generateInvitedCommunitiesForSeason(): insert new default-invited community");
	}
}

function generateJobsForSeason($season_id) {
	$job_types = sqlSelect("*", JOB_TYPES_TABLE, "active = 1", "display_order", (0), "season.generateJobsForSeason(): job types");
	foreach ($job_types as $i=>$job_type) {
		if (!sqlSelect("*", SURVEY_JOB_TABLE, "season_id = " . $season_id . " and description = '" . $job_type['description'] . "'", "")[0]) {
			$columns = "season_id, active, description, display_order, constant_name, workers_per_shift, job_type_id";
			$values = "$season_id, '{$job_type['active']}', '{$job_type['description']}', {$job_type['display_order']}, '{$job_type['constant_name']}', {$job_type['workers_per_shift']}, {$job_type['id']}";
			sqlInsert(SURVEY_JOB_TABLE, $columns, $values, (0), "season.generateJobsForSeason(): insert new job");
		}
	}
}


function generateMealsForSeason($season_id) {
	$season = sqlSelect("*", SEASONS_TABLE, "id = " . $season_id, "", (0), "generateMealsForSeason()")[0];
	$start_date = new DateTime($season['start_date']);
	$end_date = new DateTime($season['end_date']);
	$end_date->modify("+1 day"); // so the last date gets included in the season
	$interval = DateInterval::createFromDateString('1 day');
	$dates = new DatePeriod($start_date, $interval, $end_date); 
	if (0) deb("season.generateMealsForSeason(): dates =", $dates);
	$meal_dows = get_weekday_meal_days(); 
	foreach ($dates as $date) {
		if (in_array(date_format($date, "w"), $meal_dows)) {
			// Insert the meal if not already in database
			$meal_date = $date->format("Y-m-d");
			$meal_dow = $date->format("w");
			$meal_time = sqlSelect("meal_time", MEAL_TIMES_TABLE, "day_number = " . $meal_dow, "", (0))[0]['meal_time'];
			if (0) deb("season.generateMealsForSeason(): meal_date = $meal_date");
			if (!sqlSelect("*", MEALS_TABLE, "date in ('" . $meal_date . "')", "", (0))[0]) { 
				sqlInsert(MEALS_TABLE, "season_id, date, time", $season_id . ", '" . $meal_date . "', '" . $meal_time . "'", (0), "generateMealsForSeason()");
			}
			$meal = sqlSelect("*", MEALS_TABLE, "date = '" . $meal_date . "'", "", (0))[0];
			
			// Generate the shifts for this meal
			generateShiftsForMeal($season_id, $meal); 
		}
	}	
}
	
	
function saveChangesToMealsCalendar($post, $season_id) {
	if (0) deb("season.saveChangesToMealsCalendar(): post=", $post);
	if (0) deb("season.saveChangesToMealsCalendar(): season_id = $season_id");
	$meals_table = MEALS_TABLE;
	$meals = sqlSelect("*", $meals_table, "season_id = {$season_id}", "date", (0), "saveChangesToMealsCalendar(): meals");

	// Update and delete existing meals
	foreach ($meals as $meal) {

		// Update skip_indicator
		$skip_indicator = (array_key_exists($meal['id'] . '_include', $post)) ? 0 : 1;
		sqlUpdate($meals_table, "skip_indicator = " . $skip_indicator, "id = " . $meal['id'], (0), "saveChangesToMealsCalendar(): updating skip_indicator", TRUE);

		// Update skip_reason (set to null if skip_indicator = 0)
		$skip_reason = ($skip_indicator == 1) ? $post[$meal['id'] . "_reason"] : ""; 
		sqlUpdate($meals_table, "skip_reason = '" . $skip_reason . "'", "id = " . $meal['id'], (0), "saveChangesToMealsCalendar(): updating skip_reason", TRUE);

		// Create and delete shifts for this meal
		generateShiftsForMeal($season_id, $meal);
	}

	// KEEPING THIS CODE (originally from updateSeasonWorkers()) IN CASE WANT TO ENABLE ADDING A MEAL
	// // Add a new meal
	// if ($post['new_first_name'] && $post['new_last_name'] && $post['new_email']) {
		// $current = (array_key_exists('new_current', $post)) ? 1 : 0;
		// $columns = "current, first_name, last_name, email";
		// $values = "{$current}, '{$post['new_first_name']}', '{$post['new_last_name']}', '{$post['new_email']}'";
		// sqlInsert($meals_table, $columns, $values, (0), "saveChangesToMealsCalendar(): creating", TRUE);
	// }
	
	// // Update season meals to reflect any changes to anyone's "current" status
	// updateSeasonWorkers($season_id);
}


function generateShiftsForMeal($season_id, $meal) {
	$jobs = sqlSelect("*", SURVEY_JOB_TABLE, "season_id = " . $season_id, "display_order", (0), "season.generateJobsForSeason(): job types");
	//if ($meal['skip_indicator']) {		// A skipped meal should have no shifts
		// sqlDelete(SCHEDULE_SHIFTS_TABLE, "meal_id = {$meal['id']}", (0));
	// } else {							// Create any missing shifts for this meal
		foreach($jobs as $i=>$job) {
			if (!sqlSelect("*", SCHEDULE_SHIFTS_TABLE, "job_id = {$job['id']} and meal_id = {$meal['id']}", "", (0))[0]) {
				sqlInsert(SCHEDULE_SHIFTS_TABLE, "job_id, meal_id", "{$job['id']}, {$meal['id']}", (0), "generateShiftsForMeal()");
			}
		}
	//}
}


function importWorkersFromGather($files, $season_id) {
	global $dbh;
	// $season_id = $season['id'];
	if (0) deb("season.import_workers(): season_id = $season_id");
	$workers_table = AUTH_USER_TABLE;
	$workers_temp_table = "workers_temp";
	
	// Create workers_temp table
	$sql = "create table if not exists " . $workers_temp_table . " as select * from workers where 0";
	if (0) deb("season.import_workers(): create table sql =", $sql);
	$dbh->exec($sql);
	$sql = "delete from " . $workers_temp_table;
	if (0) deb("season.import_workers(): truncate table sql =", $sql);
	$rows_affected = $dbh->exec($sql);
	
	// Import from .csv into workers_temp
	$cols = sqlSelect("*", WORKERS_TABLE_COLUMNS, "", "gather_column_number", (0), "season.import_workers(): workers_table_columns");
	$mo_columns = "";
	foreach($cols as $col) {
		if ($mo_columns) $mo_columns .= ", ";
		$mo_columns .= $col['mo_column_name'];
	}
	
	$filename = $files["userfile"]["tmp_name"];
	if (0) deb("season.import_workers() filename = $filename");
	
	if ($files['userfile']['size'] > 0) {
		$gather_file = fopen($filename, "r");
		if (0) deb("season.import_workers(): file =", $gather_file);
		$rown = 0;
		while (($w = fgetcsv($gather_file, 30000, ",")) !== FALSE) {
			$gather_values = "";
			foreach($cols as $i=>$col) {
				if ($gather_values) $gather_values .= ", ";
				$quote = $col['mo_datatype'] == "string" ? "'" : "";
				$gather_values .= $quote . $w[$col['gather_column_number']] . $quote;
			}
			if ($rown > 0) {  // Skip first row (which consists of column headers) 
				sqlInsert($workers_temp_table, $mo_columns, $gather_values, (0), "importWorkersFromGather(): insert into wokers_temp", TRUE);
			}
			$rown++;
		}		
		fclose($gather_file);	
	}	 
	sqlSelect("*", $workers_temp_table, "", "first_name, last_name", (0), "importWorkersFromGather(): workers_temp table");

	// Mark pre-existing workers who are also in workers_temp as current; others as not current
	$workers = sqlSelect("*", $workers_table, "", "first_name, last_name", (0), "importWorkersFromGather(): workers table before marking");
	foreach($workers as $worker) {
		$current = (sqlSelect("'x' as dummy", $workers_temp_table, "gid = {$worker['gid']}", "", (0))[0]) ? 1 : 0;
		sqlUpdate($workers_table, "current = {$current}", "id = {$worker['id']}", (0), "importWorkersFromGather(): mark existing workers in temp_workers as current; others as not current", TRUE);
	} 
	$workers = sqlSelect("*", $workers_table, "", "first_name, last_name", (0), "importWorkersFromGather(): workers table after marking");
	
	// Insert new workers from workers_temp into workers
	$where = "not exists (select 1 from workers w where w.gid = wt.gid)";
	$order_by = "first_name, last_name";
	$new_workers = sqlSelect("*", $workers_temp_table . " as wt", $where, $order_by, (0), "importWorkersFromGather(): workers_temp not in workers");
	foreach ($new_workers as $new_worker) {
		$values = "";
		foreach($cols as $i=>$col) {
			if ($values) $values .= ", ";
			$quote = $col['mo_datatype'] == "string" ? "'" : "";
			$values .= $quote . $new_worker[$col['mo_column_name']] . $quote;
		}
		$values .= ", '" . $new_worker['first_name'] . " " . $new_worker['last_name'] . "', 1";
		sqlInsert($workers_table, $mo_columns . ", username, current", $values, (0), "importWorkersFromGather(): insert from workers_temp into workers", TRUE);
	}
	
	// Update attributes of workers from workers_temp
	$where = "exists (select 1 from workers_temp wt where w.gid = wt.gid)";
	$order_by = "first_name, last_name";
	$workers = sqlSelect("*", $workers_table . " as w", $where, $order_by, (0), "importWorkersFromGather(): workers in both workers and workers_temp");
	foreach($workers as $worker) {
		$set = "";
		foreach($cols as $i=>$col) {
			if ($set) $set .= ", ";
			$quote = $col['mo_datatype'] == "string" ? "'" : "";
			$set .= $col['mo_column_name'] . " = " . $quote . $worker[$col['mo_column_name']] . $quote;
		}
		$set .= ", username = '" . $worker['first_name'] . " " . $worker['last_name'] . "'";
		if ((0) && $worker['first_name'] == "Eric") deb("set = $set");
		sqlUpdate($workers_table, $set, "gid = " . $worker['gid'], (0), "importWorkersFromGather(): Update attributes of pre-existing workers from workers_temp", TRUE);
	}
	
	updateSeasonWorkers($season_id);
	generateLiaisonsForSeason($season_id);
	
	// Drop workers_temp table
	$sql = "drop table if exists " . $workers_temp_table;
	if (0) deb("season.import_workers() drop table sql =", $sql);
	$dbh->exec($sql);
}


function saveChangesToWorkers($post, $season_id) {
	if (0) deb("season.saveChangesToWorkers(): post=", $post);
	if (0) deb("season.saveChangesToWorkers(): season_id = $season_id");
	$workers_table = AUTH_USER_TABLE;
	$workers = sqlSelect("*", $workers_table, "", "first_name, last_name", (0), "saveChangesToWorkers(): workers");
	// Update and delete existing workers
	foreach ($workers as $worker) {
		$current = (array_key_exists($worker['id'] . '_current', $post)) ? 1 : 0;
		sqlUpdate($workers_table, "current = " . $current, "id = " . $worker['id'], (0), "saveChangesToWorkers(): updating current", TRUE);

		$first_name = $post[$worker['id'] . "_first_name"]; 
		if ($first_name && $first_name != $worker['first_name']) {
			sqlUpdate($workers_table, "first_name = '" . $first_name . "'", "id = " . $worker['id'], (0), "saveChangesToWorkers(): updating first_name", TRUE);
		}
		$last_name = $post[$worker['id'] . "_last_name"];
		if ($last_name && $last_name != $worker['last_name']) {
			sqlUpdate($workers_table, "last_name = '" . $last_name . "'", "id = " . $worker['id'], (0), "saveChangesToWorkers(): updating last_name", TRUE);
		}
		$email = $post[$worker['id'] . "_email"]; 
		if ($email && $email != $worker['email']) {
			sqlUpdate($workers_table, "email = '" . $email . "'", "id = " . $worker['id'], (0), "saveChangesToWorkers(): updating email", TRUE);
		}
		$unit = $post[$worker['id'] . "_unit"]; 
		if ($unit && $unit != $worker['unit']) {
			sqlUpdate($workers_table, "unit = '" . $unit . "'", "id = " . $worker['id'], (0), "saveChangesToWorkers(): updating unit", TRUE);
		}
		$delete = (array_key_exists($worker['id'] . '_delete', $post)) ? 1 : 0;
		if ($delete) {
			$has_assignments = sqlSelect('id', ASSIGNMENTS_TABLE, 'worker_id = ' . $worker['id'], "", (0), "saveChangesToWorkers(): checking worker for past assignments")[0];
			// Do requested delete only if worker has no past assignments (which we want to preserve)
			if (!$has_assignments) {  
				sqlDelete($workers_table, "id = " . $worker['id'], (0), "saveChangesToWorkers(): deleting", TRUE);
			}
		}
	}
	// Add a new worker, if all required columns have been filled out in the new worker form
	if ($post['new_first_name'] && $post['new_last_name'] && $post['new_email']) {
		$current = (array_key_exists('new_current', $post)) ? 1 : 0;
		$columns = "current, first_name, last_name, username, email";
		$values = "{$current}, '{$post['new_first_name']}', '{$post['new_last_name']}', '{$post['new_first_name']}' || ' ' || '{$post['new_last_name']}', '{$post['new_email']}'";
		sqlInsert($workers_table, $columns, $values, (0), "saveChangesToWorkers(): creating", TRUE);
	}
	
	// Update season workers to reflect any changes to anyone's "current" status
	updateSeasonWorkers($season_id);
}


function updateSeasonWorkers($season_id) {
	// Insert into and delete from season_workers table so it contains (for the specified season) exactly the workers who are "current" in the workers table (as of the time the function is executed).

	if (0) deb("season.updateSeasonWorkers(): season_id = $season_id");
	$workers_table = AUTH_USER_TABLE;
	$season_workers_table = SEASON_WORKERS_TABLE;
	
	// Add to season_workers for this season all workers who are current
	$from = $workers_table . " as w";
	$where = "w.current = 1 
	and not exists (
		select 'x' as dummy from season_workers sw where w.id = sw.worker_id 
			and sw.season_id = {$season_id}
		)";
	$order_by = "first_name, last_name";
	$new_workers = sqlSelect("*", $from, $where, $order_by, (0), "importWorkersFromGather(): insert current workers not in season_workers for this season into season_workers");
	if ($new_workers) {
		foreach($new_workers as $new_worker) {
			sqlInsert($season_workers_table, "worker_id, season_id", "{$new_worker['id']}, {$season_id}", (0), "importWorkersFromGather(): inserting workers into season_workers", TRUE); 
		}
	}
	
	// Delete from season_workers for the season all workers who are not current
	$from = $season_workers_table . " as sw";
	$where = "sw.season_id = {$season_id} 
	and not exists (
		select w.id from workers w where sw.worker_id = w.id  
			and w.current = 1
		)";
	$not_season_workers = sqlSelect("*", $from, $where, "", (0), "importWorkersFromGather(): delete from season_workers for this season all who are not current in workers"); 
	if ($not_season_workers) {
		foreach($not_season_workers as $not_season_worker) {
			$where = "worker_id = {$not_season_worker['worker_id']} and season_id = {$season_id}";
			sqlDelete($season_workers_table, $where, (0), "importWorkersFromGather(): deleting workers from season_workers", TRUE);
		}
	}
}


// XXX NOTE 12/5/19: COMMENTING THIS HALF-COMPLETED FUNCTION OUT FOR NOW.  WILL RESTORE AND COMPLETE AFTER THIS ORGANIZING SEASON.
// function generateWorkerOffersForSeason($worker_id) {
	// // Insert a row for each job into offers table for this worker
	// // Initialize the offers for this season to the worker's offers from last season (if any)
	// $prev_season_id = getPrevSeason()['id'];
	// $jobs = getJobs($season_id);
	// foreach ($jobs as $job) {
		// if (!sqlSelect("1", OFFERS_TABLE, "worker_id = " . $worker_id . " AND season_id = " . $season_id . " AND job_type_id = " . $job['job_type_id'], "", (0))) {
			// $where = "worker_id = " . $worker_id . " AND season_id = " . $prev_season_id . " AND job_type_id = " . $job['job_type_id'];
			// $instances = sqlSelect("instances", OFFERS_TABLE, $where, "", (0))[0]['instances'];
			// if (!$instances) $instances = null; 
			// $columns = "worker_id, job_id, season_id, instances";
			// $values = $worker_id . ", " . $job['id'] . ", " . $season_id . ", " . $instances;
			// sqlInsert(OFFERS_TABLE, $columns, $values);
		// }
	// }
// }


function generateLiaisonsForSeason($season_id) {
	// Get id of the previous season
	$last_season_id = sqlSelect("id", SEASONS_TABLE, "NOT id = " . $season_id, "start_date desc", (0))[0]['id'];

	// Get the liaisons from the previous season
	$liaisons = sqlSelect("*", SEASON_LIAISONS_TABLE, "season_id = " . $last_season_id, "", (0));
	// Clean out any pre-existing liaisons for this season (there shouldn't be any)
	sqlDelete(SEASON_LIAISONS_TABLE, "season_id = " . $season_id);
	// Create last season's liaisons as this season's starter set
	foreach ($liaisons as $liaison) {
		sqlInsert(SEASON_LIAISONS_TABLE, "season_id, worker_id", $season_id . ", " . $liaison['worker_id'], (0), "", true);	
	}
	
	// Get the worker liaisons from the previous season
	$season_workers = sqlSelect("*", SEASON_WORKERS_TABLE, "season_id = " . $last_season_id, "", (0));
	// Null out any pre-existing worker liaisons for this season (there shouldn't be any)
	sqlUpdate(SEASON_WORKERS_TABLE, "liaison_id = NULL", "season_id = " . $season_id, (0), "", true);	
	// Create last season's liaisons as this season's starter set
	if ($season_workers) {
		foreach ($season_workers as $season_worker) {
			$liaison_id = ($season_worker['liaison_id']) ? $season_worker['liaison_id'] : "NULL";
			$set = "liaison_id = " . $liaison_id . ", liaison_action = '" . $season_worker['liaison_action'] . "'";
			$where = "season_id = " . $season_id . " AND worker_id = " . $season_worker['worker_id'];
			sqlUpdate(SEASON_WORKERS_TABLE, $set, $where, (0), "", true);	
		}
	}
}

function updateSeasonLiaisons($post, $season_id) {
	// Insert into and delete from season_liaisons table.

	if (0) deb("season.updateSeasonLiaisons(): post = ", $post);
	// $workers_table = AUTH_USER_TABLE;
	$season_liaisons_table = SEASON_LIAISONS_TABLE;
	 
	// Delete season liaisons in the delete_liaisons POST array
	$liaisons_to_delete = $post['delete_liaisons'];
	if (0) deb("season.updateSeasonLiaisons(): liaisons_to_delete = ", $liaisons_to_delete);
	if ($liaisons_to_delete) {
		foreach($liaisons_to_delete as $liaison) {
			$where = "season_id = " . $season_id . " 
				AND worker_id = " . $liaison;
			sqlDelete(SEASON_LIAISONS_TABLE, $where, (0), "", true);
		}	
	}

	// Add season liaisons in the add_liaisons POST array
	$liaison_to_add = $post['add_liaison'];
	if (0) deb("season.updateSeasonLiaisons(): liaison_to_add = ", $liaison_to_add);
	if ($liaison_to_add) {
		$columns = "season_id, worker_id";
		$values = $season_id . ", " . $liaison_to_add;
		sqlInsert(SEASON_LIAISONS_TABLE, $columns, $values, (0), "", true);
	}
}
?>