<?php
require_once 'constants.inc';
require_once 'git_ignored.php';
require_once 'init.php';
require_once 'utils.php';

/* 
NOTE:  The deb() calls in this can be turned on for debugging purposes, 
but when they are on the generated output file will not be valid.
This is because the header() calls in downloadCSV() must not be preceded
by any output.  See https://www.php.net/manual/en/function.header.php.
*/

if (0) deb("download.php: start");
if (0) deb("download.php: _POST:", $_POST);

if($_POST['file_to_download']) {
	if (0) deb("download.php: _POST:", $_POST);
	$filepath = $_POST['file_to_download'];
	$parameters = $_POST['download_parameters'];
	$season_id = $_POST['season_id'];

	// Call the generator function (if any)
	switch ($filepath) {
		case MEALS_EXPORT_FILE:
			$rows = generateMealsCSV($season_id, $parameters);
			break;
	}

	downloadCSV($rows, $filepath);
}



function generateMealsCSV($season_id, $action="create") {
	if (0) deb("download.generateMealsCSV: start");	
	if (0) deb("download.generateMealsCSV: filename = " . $filename);

	// Make the rows array, which will be returned
	$rows = array();
	
	// Make the column headings row
	$column_headings = array();
	$column_headings[] = "Action";
	$column_headings[] = "Date/Time"; 
	$column_headings[] = "Locations";
	$column_headings[] = "Communities";
	$jobs = sqlSelect("*", SURVEY_JOB_TABLE, "season_id = " . $season_id, "display_order", (0)); 
	foreach($jobs as $job) {
		$column_headings[] = $job['gather_name'];
	}
	if (0) deb("download.generateMealsCSV(): header_row =", $column_headings);
	$rows[] = $column_headings;
	

	// Get the communities invited to this season's meals
	$select = "name";
	$from = "
		" . COMMUNITIES_TABLE . " as c, 
		" . COMMUNITIES_INVITED_TO_MEALS_TABLE . " as i";
	$where = "
		c.id = i.community_id
		AND i.season_id = " . $season_id
	;
	$communities = sqlSelect($select, $from, $where, null, (0), "utils.generateMealsCSV: communities q");
	$community_names = implode(';', array_column($communities, 'name'));
	
	// Make a row for each meal
	$meals = sqlSelect("*", MEALS_TABLE, "season_id = " . $season_id, "date, time asc", (0), "utils.generateMealsCSV()");	
	foreach ($meals as $i=>$meal) { 
		if (!($action == "create" && $meal['skip_indicator'])) {	// Don't create a meal if it's marked to skip
			$row = array();
			$row[] = ($meal['skip_indicator'] ? "destroy" : $action);	// Destroy an existing meal if it's marked to skip
			$row[] = $meal['date'] . "T" . $meal['time'];
			$row[] = 'Kitchen & Dining Room';
			$row[] = $community_names;
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
				$row[] = implode(';', array_column($assignments, 'gather_id'));
			}
			if (0) deb("download.generateMealsCSV(): row =", $row);
			$rows[] = $row;
		}
	}
	if (0) deb("download.generateMealsCSV: rows = ", $rows);
	if (0) deb("download.generateMealsCSV: end");
	return $rows;
}


// Downloads the 2-dimensional array $rows as a CSV file to $filepath
// function downloadCSV($rows, $filename, $filepath) {	
function downloadCSV($rows, $filepath) {	
	if (0) deb("download.downloadCSV(): rows =", $rows);
	if (0) deb("download.downloadCSV(): filepath = '" . $filepath . "'");
	if (0) deb("download.downloadCSV(): file exists? '" . file_exists($filepath) . "'");

	// Assemble the CSV file
	$file = fopen($filepath,"w");	
	// $file = fopen(basename($filepath),"w");	
	foreach($rows as $row) {
		fputcsv($file, $row);
	}
	fclose($file);
	
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
?>
