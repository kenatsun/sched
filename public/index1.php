<?php
session_start();

require_once 'utils.php';
require_once 'globals.php';
require_once 'classes/calendar.php';
require_once 'classes/PeopleList.php';
require_once 'classes/OffersList.php';
require_once 'classes/person.php';
require_once 'classes/survey1.php';
require_once 'classes/roster1.php';

require_once 'display/includes/header.php';
require_once 'participation.php';

$season_id = SEASON_ID;
$season_name = get_season_name_from_db($season_id);

echo <<<EOHTML
<h1>Sunward More Meals Scheduling Survey</h1>
<p>Season: {$season_name} </p>
<p>(Introductory and instruction text goes here)</p>
EOHTML;

// ----- check to see if the database is writable:
global $db_is_writable;
if (!$db_is_writable) {
	echo <<<EOHTML
		<div class="warning">
			ERROR: Database is not writable
		</div>
EOHTML;
}

$dir = BASE_DIR;
$report_link = <<<EOHTML
<p class="summary_report">See the <a href="{$dir}/report.php">summary report</a></p>
EOHTML;

// ----- deadline check ----
$now = time();
if ($now > DEADLINE) {
	$formatted_date = date('r', DEADLINE);
	echo <<<EOHTML
		<h2>Survey has closed</h2>
		<p>As of {$formatted_date}</p>
		{$report_link}
EOHTML;
}
else {
	// If a person has been specified in the request, do their survey
	// else display the list of all respondents as links
	$respondent_id = array_get($_GET, 'person');
	if (0) deb("index1: person from array_get:", $respondent_id);
	if (0) deb("index1: array dollarsign_get:", $_GET);
	
	if (!is_null($respondent_id)) {
		build_survey($survey, $respondent_id);
	} else {
		// display_respondents();
		display_person_menu();
		print $report_link;		
	}
}
print <<<EOHTML
</body>
</html>
EOHTML;

// -----------------------------------------------------

// function display_respondents() {
	// display the responders summary
	// $r = new Respondents();
	// echo <<<EOHTML
		// <div class="special_info">
			// {$r->getTimeRemaining()}
			// {$r->getSummary()}
		// </div>
// EOHTML;
// }

function display_person_menu() {
	$respondents = getPeopleListAsLinks();
	if (empty($respondents)) {
		echo "<h2>No respondents configured</h2>\n";
		return;
	}
	$html = <<<EOHTML
		<div class="respondents_list">
			{$respondents}
		</div>
EOHTML;
	print $html;
}

/**
 * Display the list of people as links in order to select their survey.
 */
function getPeopleListAsLinks() {
	$list = new PeopleList();
	$respondents = $list->getPeople();
	if (0) deb("index1.getPeopleAsLinks: respondents:", $respondents);
	$out = $lines = '';
	$count = 0;

	$dir = BASE_DIR;
	foreach($respondents as $respondent) {
		$line = <<<EOHTML
			<li><a href="{$dir}/index1.php?person={$respondent["id"]}">{$respondent["name"]}</a></li>
EOHTML;
		$lines .= $line;
		if (0) deb("index1.getPeopleAsLinks: html line:", $line);

		$count++;
		if (($count % 10) == 0) {
			if (0) deb("PeopleList.getPeopleListAsLinks(): html lines:", $lines);
			$out .= "<ol>{$lines}</ol>\n";
			$lines = '';
		}
		if (0) deb("PeopleList.getPeopleListAsLinks(): person-as-link:", $respondent);
	}

	if ($lines != '') {
		$out .= "<ol>{$lines}</ol>\n";
	}

	return $out;
}


/**
 * @param[in] survey Survey for this respondent to take
 * @param[in] respondent string person's id from the _GET array
 */
function build_survey($survey, $respondent_id) {
	if (0) deb("index1.build_survey: respondent_id = ", $respondent_id);
	$survey = new Survey1($respondent_id);
	// $survey->setRespondent($respondent_id);
	if (0) deb("index1.build_survey: survey = ", $survey);
	print $survey->toString();
}
?>
