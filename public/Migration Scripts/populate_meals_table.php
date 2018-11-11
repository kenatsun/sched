<?php

// Populate the newly added (as of Nov 2018) meals table 
// and a foreign key referring to it.

require_once "utils.php";
create_sqlite_connection();

// Empty the meals table and the shifts fk pointing to it
sqlUpdate(SCHEDULE_SHIFTS_TABLE, "meal_id = NULL", "", (0));
sqlDelete(MEALS_TABLE, "", (0)); 
sqlUpdate("sqlite_sequence", "seq = 0", "name = '" . MEALS_TABLE . "'", (0)); 


// Populate meals table from shifts and jobs tables
$sql = "insert into meals (season_id, date) 
	select distinct season_id, string 
    from shifts s, jobs j
    where s.job_id = j.id";
if (0) deb("populate_meals_table.php: sql:", $sql);
$rows_affected = $dbh->exec($sql);
if (0) deb("populate_meals_table.php: rows_affected:", $rows_affected);

// Populate shifts.meal_id by matching shifts.string with meals.date
$shifts = sqlSelect("*", SCHEDULE_SHIFTS_TABLE, "", "");
foreach($shifts as $i=>$shift) {
	$meal = sqlSelect("*", MEALS_TABLE, "date = '{$shift['string']}'", "", (0))[0];
	sqlUpdate(SCHEDULE_SHIFTS_TABLE, "meal_id = {$meal['id']}", "string = '{$meal['date']}'", (0));
}

// Populate the other meals columns
$meals = sqlSelect("*", MEALS_TABLE, "", "", (0));
foreach($meals as $i=>$meal) {
	$date_arr = explode('/', $meal['date']);
	if (0) deb("normalize_meals_date(): date_arr = ", $date_arr);
	
	// Populate meals.skip_indicator and meals.skip_reason from skip_dates
	$month_num = intval($date_arr[0]);
	$day_num = intval($date_arr[1]);
	$where = "season_id = {$meal['season_id']}
		and month_number = {$month_num}
		and day_number = {$day_num}";
	$skip_date = sqlSelect("*", "skip_dates", $where, "", (0))[0];
	if ($skip_date) {
		sqlUpdate(MEALS_TABLE, "skip_indicator = 1, skip_reason = '{$skip_date['reason']}'", "id = {$meal['id']}", (0)); 
	}
	
	// Normalize meal.date to YYYY/MM/DD
	if (strlen($date_arr[1]) == 1) $date_arr[1] = "0" . $date_arr[1];
	if (strlen($date_arr[0]) == 1) $date_arr[0] = "0" . $date_arr[0];
	$normal_date = $date_arr[2] . "/" . $date_arr[0] . "/" . $date_arr[1];
	if (0) deb("normalize_meals_date(): normal_date = ", $normal_date);
	sqlUpdate(MEALS_TABLE, "date = '{$normal_date}'", "id = {$meal['id']}", (0)); 
}

?>