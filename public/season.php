<?php 
session_start(); 

require_once 'globals.php';
require_once 'utils.php';
require_once 'display/includes/header.php';
$dir = BASE_DIR;

// Read the current data for this season (if it exists)
if (0) deb("season.php: _POST =", $_POST);
if (0) deb("season.php: _FILES =", $_FILES);
if (0) deb("season.php: _GET =", $_GET);
if (0) deb("season.php: array_key_exists('season_id', _POST['season_id']) =", array_key_exists('season_id', $_POST));

// If request is a POST to create or update a season, get the season_id from it
if (array_key_exists('season_id', $_POST)) $season_id = $_POST['season_id'];
// If request is a GET to display an existing season, get the season_id from it
elseif ($_GET) $season_id = $_GET['season_id'];	
// If request is to display an empty form to create a new season, do that
else $season_id = null;

if (array_key_exists('season_data', $_POST)) saveChangesToSeason($_POST);
if (array_key_exists('import_workers', $_POST)) importWorkersFromGather($_FILES); 
if (array_key_exists('update_workers', $_POST)) saveChangesToWorkers($_POST);

// Get the season (if any) to display
$season = sqlSelect("*", SEASONS_TABLE, "id = {$season_id}", "", (0), "season.php: season")[0];

// Display the page
$page = "";
$page .= renderHeadline((($season) ? $season['name'] : "New") . " Season", HOME_LINK . SEASONS_LINK);  
$page .= renderSeasonForm($season);
$page .= renderWorkerUploadForm($season);
$page .= renderWorkerEditForm($season);
print $page;


//////////////////////////////////////////////////////////////// DISPLAY FUNCTIONS

// Render the form to display, create, or update this season
function renderSeasonForm($season) {
	if (0) deb("season.renderSeasonForm(): season_id =", $season['id']);

	$form = "";
	$form .= '<form action="season.php" method="post" name="season_data">';
	$form .= '<input type="hidden" name="season_id" value="' . $season['id'] . '">';
	$form .= '<input type="hidden" name="season_data">';
	$form .= '<table style="font-size:11pt;">';

	// Season name
	$form .= '<tr><td style="text-align:right">name:</td>
		<td><input type="text" name="name" value="' . $season['name'] . '"></td></tr>';

	// Season start and end dates
	if (0) deb("season.renderSeasonForm(): season['start_date'] =", $season['start_date']);
	$start_month_value = renderUpcomingMonthsSelectList("season_start_month", $season['start_date'], 2);
	$end_month_value = renderUpcomingMonthsSelectList("season_end_month", $season['end_date'], 2);
	if (0) deb("season.renderSeasonForm(): start_month_value =", $start_month_value);
	$form .= '<tr><td style="text-align:right">first month of season:</td><td>' . $start_month_value . '</td></tr>';
	if (0) deb("season.renderSeasonForm(): end_month_value =", $end_month_value);
	$form .= '<tr><td style="text-align:right">last month of season:</td><td>' . $end_month_value . '</td></tr>';	

	// Survey opening date
	$survey_opening_date = renderDateInputFields($season['survey_opening_date'], "survey_opening");
	$form .= '<tr><td style="text-align:right">first day of survey (mm/dd/yyyy):</td><td>' . $survey_opening_date . '</td></tr>';

	// Survey closing date
	$survey_closing_date = renderDateInputFields($season['survey_closing_date'], "survey_closing");
	$form .= '<tr><td style="text-align:right">last day of survey (mm/dd/yyyy):</td><td>' . $survey_closing_date . '</td></tr>';
	
	// Manually extend closed season, or re-close it
	$checked = (sqlSelect("*", SEASONS_TABLE, "id = " . SEASON_ID, "")[0]['survey_extended']) ? "checked" : ""; 
	$form .= '<tr><td style="text-align:right">extend survey?:</td><td><input type="checkbox" name="extend_survey" ' . $checked . '></td></tr>';

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

function renderWorkerUploadForm($season) {
	$form = '';
	$form .= '<br><br>';
	$form .= '<h3>Update Worker List from Gather File</h3>';
	$form .= '<form enctype="multipart/form-data" action="season.php" method="POST">';
	$form .= '<input type="hidden" name="season_id" value="' . $season['id'] . '">';
	$form .= '<input type="hidden" name="import_workers">';
    $form .= '<input type="hidden" name="MAX_FILE_SIZE" value="30000" />';
    $form .= '<p>File of Workers from Gather: <input name="userfile" type="file" /></p>';
    $form .= '<p><input type="submit" value="Update Workers" /></p>';
	$form .= '</form>';
	return $form;
}

function renderWorkerEditForm() {
	$form = '';
	$form .= '<br><br>';
	$season_id = SEASON_ID;
	$season = sqlSelect("*", SEASONS_TABLE, "id = {$season_id}", "")[0];
	$form .= '<h3>Workers for the ' . $season['name'] . ' Season</h3>';
	$form .= '<form enctype="multipart/form-data" action="season.php" method="POST">';
	$form .= '<input type="hidden" name="season_id" value="' . $season['id'] . '">';
	$form .= '<input type="hidden" name="update_workers">';
	$form .= renderWorkerTable($season);
    $form .= '<p><input type="submit" value="Save Changes" /><input type="reset" value="Cancel Changes" /></p>';
	$form .= '</form>';
	return $form;	
}

function renderWorkerTable($season) { 
	$table = '';
	$table .= '<table>';
	$workers = sqlSelect("*", AUTH_USER_TABLE, "", "current desc, first_name asc, last_name asc", (0), "renderWorkerTable(): workers");
	$last_current = -1;
	foreach($workers as $worker) {
		if ($last_current != $worker['current']) {  // Print a header dividing current from not-current workers
			$not = ($worker['current']) ? "" : "<i>not</i> ";
			$table .= '<tr><th colspan="50"><i>Workers ' . $not . 'in the ' . $season['name'] . ' season:</i></th></i></tr>';
			$table .= '<tr><th>In?</th><th>First Name</th><th>Last Name</th><th>Email</th><th>Unit</th><th>Delete?</th></tr>';
			$last_current = $worker['current'];
		}
		$from_gather = ($worker['gid']) ? 1 : 0;  // Was the worker imported from Gather or manually entered into MO?
		if ($from_gather) {
			$first_name_field = $worker['first_name'];
			$last_name_field = $worker['last_name'];
			$delete_field = '';
		} else {
			$first_name_field = '<input type="text" name="' . $worker['id'] . '_first_name" value="' . $worker['first_name'] . '">';
			$last_name_field = '<input type="text" name="' . $worker['id'] . '_last_name" value="' . $worker['last_name'] . '">';
			$delete_field = '<input type="checkbox" name="' . $worker['id'] . '_delete">';
		}
		$checked = ($worker['current']) ? "CHECKED" : "";
		$row = '<tr>';
		$row .= '<td>' . '<input type="checkbox" name="' . $worker['id'] . '_current" ' . $checked . ' ></td>';
		$row .= '<td>' . $first_name_field . '</td>';
		$row .= '<td>' . $last_name_field . '</td>';
		$row .= '<td>' . '<input type="text" name="' . $worker['id'] . '_email" value="' . $worker['email'] . '">' . '</td>';
		$row .= '<td>' . $worker['unit'] . '</td>';
		$row .= '<td>' . $delete_field . '</td> ';
		$row .= '</tr>';
		$table .= $row;
	}
	$table .= '<tr><th colspan="50"><i>Add a Worker ( * means required field):</i></th></tr>';
	$table .= '<tr><th>In?</th><th>First Name *</th><th>Last Name *</th><th>Email *</th></tr>';
	$row = '<tr>';
	$row .= '<td>' . '<input type="checkbox" name=" new_current" CHECKED></td>';
	$row .= '<td>' . '<input type="text" name=" new_first_name">' . '</td>';
	$row .= '<td>' . '<input type="text" name=" new_last_name">' . '</td>';
	$row .= '<td>' . '<input type="text" name=" new_email">' . '</td>';
	// $row .= '<td>' . '</td>';
	// $row .= '<td>' . '<input type="checkbox" name=" new_dont_add"></td>';
	$row .= '</tr>';
	$table .= $row;
	$table .= '</table>';
	return $table;
}


//////////////////////////////////////////////////////////////// DATABASE FUNCTIONS

// Create or update season in the database
function saveChangesToSeason($post) {
	if (0) deb("season.saveChangesToSeason(): post =", $post);
	
	$season_id = $post['season_id'];
	$name = $post['name'];
	$start_date = ($post['season_start_month']) ? $post['season_start_month'] . "-01" : ""; 
	$end_date = ($post['season_end_month']) ? $post['season_end_month'] . "-" . date("t", strtotime($post['season_end_month']) . "-01") : ""; 
	$year = ($post['season_end_month']) ? date("Y", strtotime($post['season_end_month'])) : "";
	if (0) deb("season.saveChangesToSeason(): post['extend_survey'] =", $post['extend_survey']);
	$opening_month = $post['survey_opening_month'];
	$opening_day = $post['survey_opening_day'];
	$opening_year = $post['survey_opening_year'];
	if ($opening_month && $opening_day && $opening_year) {
		$survey_opening_date = 
			(checkdate($opening_month, $opening_day, $opening_year)) ? $opening_year."-".$opening_month."-".$opening_day : ""; 
	} else {
		$survey_opening_date = "";
	}
	$closing_month = $post['survey_closing_month'];
	$closing_day = $post['survey_closing_day'];
	$closing_year = $post['survey_closing_year'];
	if ($closing_month && $closing_day && $closing_year) {
		$survey_closing_date = 
			(checkdate($closing_month, $closing_day, $closing_year)) ? $closing_year."-".$closing_month."-".$closing_day : ""; 
	} else {
		$survey_closing_date = "";
	}
	$extend_survey = ($post['extend_survey']) ? 1 : 0;
	
	// If season_id exists, it's an existing season, so update its data
	if ($season_id) {
		$set = "name = '$name', 
			start_date = '$start_date', 
			end_date = '$end_date', 
			year = '$year', 
			survey_opening_date = '$survey_opening_date',
			survey_closing_date = '$survey_closing_date',
			survey_extended = '$extend_survey'
			";
		$where = "id = $season_id";
		sqlUpdate(SEASONS_TABLE, $set, $where, (0), "season.saveChangesToSeason(): update");
	}
	// Else create a new season
	else {
		$columns = "name, 
			start_date, 
			end_date, 
			year, 
			survey_opening_date,
			survey_closing_date
			";
		$values = "'$name', 
			'$start_date', 
			'$end_date', 
			'$year', 
			'$survey_opening_date',
			'$survey_closing_date'
			";
		if (0) deb("season.saveChangesToSeason(): columns =", $columns);
		if (0) deb("season.saveChangesToSeason(): values =", $values);
		sqlInsert(SEASONS_TABLE, $columns, $values, (0), "seasons.saveChangesToSeason()");
		$season_id = sqlSelect("max(id) as id", SEASONS_TABLE, "", "")[0]['id'];
	}

	// Generate the jobs for this season
	$jobs = generateJobsForSeason($season_id);
	
	// Generate the meals and shifts for this season
	generateMealsForSeason($season_id, $jobs);
	
	// Record the number of shifts this season for each job
	foreach($jobs as $i=>$job) {
		$shift_count = sqlSelect("count(distinct id) as count", SCHEDULE_SHIFTS_TABLE, "job_id = " . $job['id'], "", (0), "season.saveChangesToSeason(): shifts count")[0]['count'];
		$workers_count = $shift_count * $job['workers_per_shift'];
		sqlUpdate(SURVEY_JOB_TABLE, "instances = $workers_count", "id = {$job['id']}", (0), "season.saveChangesToSeason(): shifts count");
	}

}

function generateJobsForSeason($season_id) {
	// For now, these are just a copy of last season's jobs
	// and we assume the last season had an id one less than this one
	$prev_id = $season_id - 1;
	$prev_jobs = sqlSelect("*", SURVEY_JOB_TABLE, "season_id = " . $prev_id, "display_order", (0), "season.generateJobsForSeason(): last season's jobs");
	foreach ($prev_jobs as $i=>$prev_job) {
		if (!sqlSelect("*", SURVEY_JOB_TABLE, "season_id = " . $season_id . " and description = '" . $prev_job['description'] . "'", "")[0]) {
			$columns = "season_id, active, description, display_order, constant_name, workers_per_shift";
			$values = "$season_id, '{$prev_job['active']}', '{$prev_job['description']}', {$prev_job['display_order']}, '{$prev_job['constant_name']}', {$prev_job['workers_per_shift']}";
			sqlInsert(SURVEY_JOB_TABLE, $columns, $values, (0), "season.generateJobsForSeason(): insert new job");
		}
	}
	return sqlSelect("*", SURVEY_JOB_TABLE, "season_id = " . $season_id, "display_order", (0), "season.generateJobsForSeason()");
}

function generateMealsForSeason($season_id, $jobs) {
	$season = sqlSelect("*", SEASONS_TABLE, "id = $season_id", "", (0), "generateMealsForSeason()")[0];
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
			if (0) deb("season.generateMealsForSeason(): meal_date = $meal_date");
			if (!sqlSelect("*", MEALS_TABLE, "date in ('" . $meal_date . "')", "", (0))[0]) {
				sqlInsert(MEALS_TABLE, "season_id, date", $season_id . ", '" . $meal_date . "'", (0), "generateMealsForSeason()");
			}
			$meal = sqlSelect("*", MEALS_TABLE, "date = '" . $meal_date . "'", "", (0))[0];
			
			// Generate the shifts for this meal
			if (!$meal['skip_indicator']) {
				generateShiftsForMeal($season_id, $jobs, $meal);
			}
			
			// A skipped meal should have no shifts
			if ($meal['skip_indicator']) {
				sqlDelete(SCHEDULE_SHIFTS_TABLE, "meal_id = {$meal['id']}");
			}
		}
	}	
}
	
function generateShiftsForMeal($season_id, $jobs, $meal) {
	// $jobs = sqlSelect("*", SURVEY_JOB_TABLE, "season_id = " . $season_id, "display_order", (0), "season.generateShiftsForSeason()");
	foreach($jobs as $i=>$job) {
		if (!sqlSelect("*", SCHEDULE_SHIFTS_TABLE, "job_id = {$job['id']} and meal_id = {$meal['id']}", "", (0))[0]) {
			sqlInsert(SCHEDULE_SHIFTS_TABLE, "job_id, meal_id", "{$job['id']}, {$meal['id']}", (0), "generateShiftsForMeal()");
		}	
	}
}

function importWorkersFromGather($files) {
	global $dbh;
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
	
	// Mark workers in workers who are not in workers_temp as not current
	$where = "w.current = 1 and not exists (select 1 from workers_temp wt where w.gid = wt.gid)";
	$order_by = "first_name, last_name";
	$former_workers = sqlSelect("*", $workers_table . " as w", $where, $order_by, (0), "importWorkersFromGather(): workers not in workers_temp");
	foreach($former_workers as $former_worker) {
		sqlUpdate($workers_table, "current = 0", "id = " . $former_worker['id'], (0), "importWorkersFromGather(): mark former workers as not current", TRUE);
	} 
	
	// Update attributes of pre-existing workers from workers_temp
	$where = "w.current = 1 and exists (select 1 from workers_temp wt where w.gid = wt.gid)";
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
		sqlUpdate($workers_table, $set, "gid = " . $worker['gid'], (0), "importWorkersFromGather(): mark former workers as not current", TRUE);
	}
	
	updateSeasonWorkers(SEASON_ID);
	
	// Drop workers_temp table
	$sql = "drop table if exists " . $workers_temp_table;
	if (0) deb("season.import_workers() drop table sql =", $sql);
	$dbh->exec($sql);
}

function updateSeasonWorkers($season_id) {
	// Insert into and delete from season_workers table so it contains (for the specified season) exactly the workers who are "current" in the workers table (as of the time the function is executed).

	$workers_table = AUTH_USER_TABLE;
	$season_workers_table = SEASON_WORKER_TABLE;
	
	// Add to season_workers for this season all workers who are current
	$where = "w.current = 1 
	and not exists (
		select 1 from season_workers sw where w.id = sw.worker_id 
			and sw.season_id = {$season_id}
		)";
	$order_by = "first_name, last_name";
	$new_workers = sqlSelect("*", $workers_table . " as w", $where, $order_by, (0), "importWorkersFromGather(): current workers not in season_workers for this season");
	if ($new_workers) {
		foreach($new_workers as $new_worker) {
			sqlInsert($season_workers_table, "worker_id, season_id", "{$new_worker['id']}, {$season_id}", (0), "importWorkersFromGather(): inserting workers into season_workers", TRUE);
		}
	}
	
	// Delete all workers who are not current from season_workers for the new season
	$where = "sw.season_id = {$season_id} 
	and not exists (
		select 1 from workers w where sw.worker_id = w.id  
			and w.current = 1
		)";
	$not_season_workers = sqlSelect("*", $season_workers_table . " as sw", $where, "", (0), "importWorkersFromGather(): season_workers for this season not current in workers"); 
	if ($not_season_workers) {
		foreach($not_season_workers as $not_season_worker) {
			$where = "worker_id = {$not_season_worker['worker_id']} and season_id = {$season_id}";
			sqlDelete($season_workers_table, $where, (0), "importWorkersFromGather(): deleting workers from season_workers", TRUE);
		}
	}
}

function saveChangesToWorkers($post) {
	$workers_table = AUTH_USER_TABLE;
	$workers = sqlSelect("*", $workers_table, "", "first_name, last_name", (0), "saveChangesToWorkers(): workers");
	// Update and delete existing workers
	foreach ($workers as $worker) {
		$current = (array_key_exists($worker['id'] . '_current', $post)) ? 1 : 0;
		if ($current != $worker['current']) {
			sqlUpdate($workers_table, "current = " . $current, "id = " . $worker['id'], (1), "saveChangesToWorkers(): updating current", TRUE);
		}
		$first_name = $post[$worker['id'] . "_first_name"]; 
		if ($first_name && $first_name != $worker['first_name']) {
			sqlUpdate($workers_table, "first_name = '" . $first_name . "'", "id = " . $worker['id'], (1), "saveChangesToWorkers(): updating first_name", TRUE);
		}
		$last_name = $post[$worker['id'] . "_last_name"];
		if ($last_name && $last_name != $worker['last_name']) {
			sqlUpdate($workers_table, "last_name = '" . $last_name . "'", "id = " . $worker['id'], (1), "saveChangesToWorkers(): updating last_name", TRUE);
		}
		$email = $post[$worker['id'] . "_email"]; 
		if ($email && $email != $worker['email']) {
			sqlUpdate($workers_table, "email = '" . $email . "'", "id = " . $worker['id'], (1), "saveChangesToWorkers(): updating email", TRUE);
		}
		$delete = (array_key_exists($worker['id'] . '_delete', $post)) ? 1 : 0;
		if ($delete) {
			$has_assignments = sqlSelect('x', ASSIGNMENTS_TABLE, 'worker_id = ' . $worker['id'], (1), "saveChangesToWorkers(): checking worker for past assignments")[0];
			// Do requested delete only if worker has no past assignments (which we want to preserve)
			if (!$has_assignments) {  
				sqlDelete($workers_table, "id = " . $worker['id'], (1), "saveChangesToWorkers(): deleting", TRUE);
			}
		}
	}
	// Add a new worker, if all required columns have been filled out in the new worker form
	if ($post['new_first_name'] && $post['new_last_name'] && $post['new_email']) {
		$current = (array_key_exists('new_current', $post)) ? 1 : 0;
		$columns = "current, first_name, last_name, email";
		$values = "{$current}, '{$post['new_first_name']}', '{$post['new_last_name']}', '{$post['new_email']}'";
		sqlInsert($workers_table, $columns, $values, (1), "saveChangesToWorkers(): creating", TRUE);
	}
	
	// Update season workers to reflect any changes to anyone's "current" status
	updateSeasonWorkers($season_id);
}


?>