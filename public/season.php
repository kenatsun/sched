<?php
session_start(); 

require_once 'globals.php';
require_once 'utils.php';
require_once 'display/includes/header.php';
$dir = BASE_DIR;

// Read the current data for this season (if it exists)
if (0) deb("season.php: _POST =", $_POST);
if (0) deb("season.php: _GET =", $_GET);
if (0) deb("season.php: array_key_exists('season_id', _POST['season_id']) =", array_key_exists('season_id', $_POST));

// If request is to create or update a season, update the database and display the result
if (array_key_exists('season_id', $_POST)) $season_id = saveChangesToSeason($_POST);
// If request is to display an existing season, do that
elseif ($_GET) $season_id = $_GET['season_id'];	
// If request is to display an empty form to create a new season, do that
else $season_id = null;

// Get the season (if any) to display
$season = sqlSelect("*", SEASONS_TABLE, "id = {$season_id}", "", (0), "season.php: season")[0];

// Display the page
$page = "";
$page .= renderHeadline((($season) ? $season['name'] : "New") . " Season", HOME_LINK . SEASONS_LINK);  
// $page .= '<p><a href="' . $dir . '/seasons.php">Back to Seasons List</a></p>';
$page .= renderSeasonForm($season);
print $page;


//////////////////////////////////////////////////////////////// FUNCTIONS

// Render the form to display, create, or update this season
function renderSeasonForm($season) {
	if (0) deb("season.renderSeasonForm(): season_id =", $season['id']);

	$form = "";
	$form .= '<form action="season.php" method="post">';
	$form .= '<input type="hidden" name="season_id" value="' . $season['id'] . '">';
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
	// $survey_opening_date = ($season['survey_opening_date']) ? date("m-d-Y", strtotime($season['survey_opening_date'])) : "";
	// $survey_opening_value = '<input type="text" name="survey_opening_date" value="' . $survey_opening_date . '">';
	// $form .= '<tr><td style="text-align:right">first day of survey:</td><td>' . $survey_opening_value . '</td></tr>';

	// Survey closing date
	$survey_closing_date = renderDateInputFields($season['survey_closing_date'], "survey_closing");
	$form .= '<tr><td style="text-align:right">last day of survey (mm/dd/yyyy):</td><td>' . $survey_closing_date . '</td></tr>';
	// $survey_closing_date = ($season['survey_closing_date']) ? date("m-d-Y", strtotime($season['survey_closing_date'])) : "";
	// $survey_closing_value = '<input type="text" name="survey_closing_date" value="' . $survey_closing_date . '">';
	// $form .= '<tr><td style="text-align:right">last day of survey:</td><td>' . $survey_closing_value . '</td></tr>';
	
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
	$survey_opening_date = (checkdate($opening_month, $opening_day, $opening_year)) ? $opening_year."-".$opening_month."-".$opening_day : ""; 
	$closing_month = $post['survey_closing_month'];
	$closing_day = $post['survey_closing_day'];
	$closing_year = $post['survey_closing_year'];
	$survey_closing_date = (checkdate($closing_month, $closing_day, $closing_year)) ? $closing_year."-".$closing_month."-".$closing_day : ""; 
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
		sqlInsert(SEASONS_TABLE, $columns, $values, (0), "seasons.saveChangesToSeason()", TRUE);
		$season_id = sqlSelect("max(id) as id", SEASONS_TABLE, "", "")[0]['id'];
		// generateMealsForSeason($season_id);
	}
	generateMealsForSeason($season_id);
	return $season_id;

}

function generateMealsForSeason($season_id) {
	$season = sqlSelect("*", SEASONS_TABLE, "id = $season_id", "", (0), "generateMealsForSeason()")[0];
	$start_date = new DateTime($season['start_date']);
	$end_date = new DateTime($season['end_date']);
	$interval = DateInterval::createFromDateString('1 day');
	$period = new DatePeriod($start_date, $interval, $end_date);
	$meal_dows = get_weekday_meal_days();
	foreach ($period as $date) {
		if (in_array(date_format($date, "w"), $meal_dows)) {
			$meal_date = $date->format("Y-m-d");
			if (!sqlSelect("*", MEALS_TABLE, "date in ('" . $meal_date . "')", "", (0))[0]) {
				sqlInsert(MEALS_TABLE, "season_id, date", $season_id . ", '" . $meal_date . "'", (0), "generateMealsForSeason()", TRUE);
			}
			$meal_id = sqlSelect("*", MEALS_TABLE, "date = '" . $meal_date . "'", "", (0))[0]['id'];
			generateShiftsForMeal($meal_id, $season_id);
		}
	}
}
	
function generateShiftsForMeal($meal_id, $season_id) {
	$jobs = sqlSelect("*", SURVEY_JOB_TABLE, "season_id = " . $season_id, "display_order", (0), "season.generateShiftsForSeason()");
	foreach($jobs as $i=>$job) {
		if (!sqlSelect("*", SCHEDULE_SHIFTS_TABLE, "job_id = $job_id and meal_id = $meal_id", "", (0))[0]) {
			sqlInsert(SCHEDULE_SHIFTS_TABLE, "job_id, meal_id", $job['id'] . ", " . $meal_id, (0), "generateShiftsForMeal()", TRUE);
		}		
	}
}

?>