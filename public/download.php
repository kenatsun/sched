<?php

if($_POST['file_to_download']) {
	// $filepath = "docs/meals.csv";
	$filepath = $_POST['file_to_download'];
	$parameters = $_POST['download_parameters'];
	$season_id = sqlSelect("id", SEASONS_TABLE, "current_season=1","")[0]['id'];

	// Call the generator function (if any)
	switch ($filepath) {
		case MEALS_EXPORT_FILE:
			exportMealsCSV($season_id, $filepath, $parameters);
			break;
	}

	// Put out the file
	// Code from the PHP manual: https://www.php.net/manual/en/function.readfile.php
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');
	header('Content-Length: ' . filesize($filepath)); 
	readfile($filepath);
	exit;
}


function renderExportMealsForm($season, $action) {
	if (!$season) return;
	$form = '';
	$form .= '<form enctype="multipart/form-data" action="' . makeURI("season.php", PREVIOUS_CRUMBS_IDS, "", EXPORT_MEALS_ID) . '" method="POST" name="export_meals_form">';
		$form .= '<input type="hidden" name="file_to_download" value="' . MEALS_EXPORT_FILE . '">'; 
		$form .= '<input type="hidden" name="download_parameters" value="' . $action . '">'; 
    $form .= '<p><input type="submit" value="Download Export File" />';
	$form .= '</form>';
	if (0) deb("form = " , $form);
	return $form; 
}


function exportMealsCSV($season_id, $filename, $action="create") {
	if (0) deb("utils.exportMealsCSV: start");
	$file = fopen($filename,"w");

	// Make the header row
	$header = array();
	$header[] = "Action";
	$header[] = "Date/Time"; 
	$header[] = "Locations";
	$jobs = sqlSelect("*", SURVEY_JOB_TABLE, "season_id = " . $season_id, "display_order", (0)); 
	foreach($jobs as $job) {
		$header[] = $job['gather_name'];
	}
	if (0) deb("season_utils.exportMealsCSV(): header_row =", $columns);
	fputcsv($file, $header);

	// Make a row for each meal
	$meals = sqlSelect("*", MEALS_TABLE, "season_id = " . $season_id, "date, time asc", (0), "utils.exportMealsCSV()");	
	foreach ($meals as $i=>$meal) { 
		if (!($action == "create" && $meal['skip_indicator'])) {	// Don't create a meal if it's marked to skip
			$row = array();
			$row['Action'] = ($meal['skip_indicator'] ? "destroy" : $action);	// Destroy an existing meal if it's marked to skip
			$row['Time'] = $meal['date'] . "T" . $meal['time'];
			$row['Resources'] = 'Kitchen & Dining Room';
			foreach($jobs as $job) {
				$select = "w.gid as gather_id";
				$from = 
					ASSIGNMENTS_TABLE . " as a, " . 
					SCHEDULE_SHIFTS_TABLE . " as s, " . 
					AUTH_USER_TABLE . " as w";
				$where = 
					"a.shift_id = s.id 
					AND s.meal_id = " . $meal['id'] . " 
					AND s.job_id = " . $job['id'] . "
					AND a.worker_id = w.id";
				$assignments = sqlSelect($select, $from, $where, "", (0));
				$worker_ids = "";
				foreach($assignments as $assignment) {
					if ($worker_ids) $worker_ids .= ";";
					$worker_ids .= $assignment['gather_id'];
				}
				$row[] = $worker_ids; 
			}
			fputcsv($file, $row);
			unset($row);
		}
	}
	fclose($file);
}
?>
