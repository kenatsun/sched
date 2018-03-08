<?php
require_once 'constants.inc';
require_once 'git_ignored.php';

/**
 * Get an element from an array, with a backup.
 */
function array_get($array, $key, $default=NULL) {
	if (is_array($array) && !empty($array) && isset($array[$key])) {
		return $array[$key];
	}

	return $default;
}

/**
 * Get the upcoming season's ID.
 */
function get_season_id() {
	$start_date = 'March 1, 2018, 12pm';
	$start = new DateTime($start_date);

	$now = new DateTime();
	$diff = date_diff($start, $now);

	$out = ($diff->y * 3) + floor($diff->m / 3);
	return $out;
}

/* 
 * Get the season's name from database based on SEASON_ID
 */
function get_season_name_from_db() {
	global $dbh;
	$season_table = SEASON_TABLE;
	$season_id = SEASON_ID;
	$sql = <<<EOSQL
		SELECT description FROM {$season_table} WHERE id = {$season_id};
EOSQL;
	$season_name = array();
	foreach($dbh->query($sql) as $row) {
		$season_name[] = $row['description'];
		break;
	}
	if (0) deb("	Utils: Season name from DB:", $season_name);
	return $season_name[0];
}

/**
 * Get the months contained in the current season.
 *
 * @return array list of month names contained in the requested season.
 */
function get_current_season() {
	switch(SEASON_NAME) {

		case SPRING:
			return [
				4=>'April',
				5=>'May',
				6=>'June',
			];

		case SUMMER:
			return [	
				7=>'July',
				8=>'August',
				9=>'September',
			];

		case FALL:
			return [
				10=>'October',
				11=>'November',
				12=>'December',
			];

		case WINTER:
			return [
				1=>'January',
				2=>'February',
				3=>'March',
			];

		case 'test':
			return [
				1=>'January',
			];
	}
}

/**
 * #!# WTF... why is this off by 2 months?
 * Is that for planning purposes? That shouldn't be done here...
 */
function get_season_name($date=NULL) {
	if (is_null($date)) {
		$date = time();
	}
	$month = date('n', $date);

	switch($month) {
		case 3:
		case 4:
		case 5:
			return SPRING;

		case 6:
		case 7:
		case 8:
			return SPRING;

		case 9:
		case 10:
		case 11:
			return FALL;

		case 12:
		case 1:
		case 2:
			return WINTER;

	}
}

/**
 * Add the easter date to the holidates array.
 */
function add_easter($holidays) {
	// add easter, which floats between march and april
	$easter_month = date('n', easter_date(SEASON_YEAR));
	$easter_day = date('j', easter_date(SEASON_YEAR));
	$holidays[$easter_month][] = $easter_day;

	return $holidays;
}

/*
 * Get the list of all holidays.
 * @return associative array where the keys are the months, and the values are
 *     dates in the months.
 */
function get_holidays() {
	$holidays = [
		1 => [1],
		7 => [4],
		10 => [31],
		12 => [24,25, 31],
	];

	$holidays = add_easter($holidays);

	// add memorial day
	$mem_day = date('j', strtotime('last monday of May, ' . SEASON_YEAR));
	// sunday, day before
	$holidays[5][] = ($mem_day - 1);
	// monday, memorial day
	$holidays[5][] = $mem_day;

	// sunday before labor day
	// if last day of aug is sunday, then next day is labor day... skip
	$last_aug = date('D', strtotime('last day of August, ' . SEASON_YEAR));
	if ($last_aug == 'Sun') {
		$holidays[8][] = 31;
	}

	// labor day
	$labor_day = date('j', strtotime('first monday of September, ' . SEASON_YEAR));
	// if the Sunday before is in Sept, then skip it
	if ($labor_day > 1) {
		$holidays[9][] = ($labor_day - 1);
	}
	$holidays[9][] = $labor_day;

	// thanksgiving
	$thx_day = date('j', strtotime('fourth thursday of November, ' . SEASON_YEAR));
	$holidays[11][] = $thx_day;
	$last_sunday = date('j', strtotime('last sunday of November, ' . SEASON_YEAR));
	if ($last_sunday > $thx_day) {
		$holidays[11][] = $last_sunday;
	}

	ksort($holidays);
	$yr = SEASON_YEAR;
	if (0) {deb("Holidays for {$yr}:", $holidays);}
	return $holidays;
}

/**
 * Get the first key from the array
 */
function get_first_associative_key($dict) {
	if (empty($dict)) {
		return NULL;
	}

	// do this in 2 steps to avoid errors / warnings
	$tmp = array_keys($dict);
	return array_shift($tmp);
}

/* 
Print debug data to the web page
*/
//SUNWARD
function deb($label, $data) {
	$print_data = print_r($data, TRUE);
	echo <<<EOHTML
<tr>
	<td colspan="4"> <br>{$label}
		<pre>{$print_data}</pre>
	</td>
</tr>
EOHTML;
}

/*
Print a headline for a page
*/
function renderHeadline($text) {
	$community_logo = (COMMUNITY == "Sunward" ? '/display/images/sunward_logo.png' : '/display/images/great_oak_logo.png');
	$instance = INSTANCE;
	$database = DATABASE;
	$instance_header = ($instance ? "<p>This is from the {$instance} instance.  Database is {$database}.</p>" : "");
	return <<<EOHTML
	{$instance_header}
	<table><tr>
		<td><img src={$community_logo}></td>
		<td class="headline">{$text}</td>
	</tr></table>
EOHTML;
}

// Generic SQL SELECT
function sqlSelect($select, $from, $where, $order_by) {
	global $dbh;
	$sql = <<<EOSQL
		SELECT {$select} 
		FROM {$from} 
EOSQL;
	if ($where) {
		$sql .= <<<EOSQL
		
		WHERE {$where}
EOSQL;
	}
	if ($order_by) {
		$sql .= <<<EOSQL
		
		ORDER BY {$order_by}
EOSQL;
	}
	if (0) deb("utils.sqlSelect: sql:", $sql);
	$results = array();
	foreach($dbh->query($sql) as $row) {
		// Get rid of the numbered elements that get stuck into these row-arrays,  
		// leaving only named attributes as elements in the results array
		foreach($row as $key=>$value) {
			if (is_int($key)) unset($row[$key]);
		}
		$results[] = $row;
	}
	if (0) deb("utils.sqlSelect: results:", $results);
	return $results;
}

// Generic SQL REPLACE
// REPLACE INTO apparently works with SQLite and MySQL but not PostgreSQL, 
// so would have to rewrite this function for PostgreSQL
function sqlReplace($table, $columns, $values) {
	global $dbh;
	$sql = <<<EOSQL
		REPLACE INTO {$table} ({$columns})
		VALUES ({$from}) 
EOSQL;
	$results = array();
	foreach($dbh->query($sql) as $row) {
		// Get rid of the numbered elements that get stuck into these row-arrays, 
		// leaving only named attributes as elements in the results array
		foreach($row as $key=>$value) {
			if (is_int($key)) unset($row[$key]);
		}
		$results[] = $row;
	}
	if (0) deb("utils.sqlReplace: sql:", $sql);
	$success = $this->dbh->exec($sql);
	if (0) deb("utils.sqlSelect: success:", $success);
	return $success;
}
?>
