<?php
session_start();

require_once 'utils.php';
require_once 'globals.php';
require_once 'classes/calendar.php';
require_once 'classes/PeopleList.php'; 
require_once 'classes/OffersList.php';
require_once 'classes/person.php';
require_once 'classes/survey1.php';
// require_once 'display/images';

require_once 'display/includes/header.php';
require_once 'participation.php';

$season_id = SEASON_ID;
$season_name = get_season_name_from_db($season_id);



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

// ----- deadline check ----
$now = time();
if ($now <= DEADLINE) {
	// If a person has been specified in the request, do their survey
	// else display the list of all respondents as links
	$respondent_id = array_get($_GET, 'person');
	if (0) deb("index: person from array_get:", $respondent_id);
	if (0) deb("index: array dollarsign_get:", $_GET);
	if (!is_null($respondent_id)) {
		build_survey($survey, $respondent_id);
	} else {
		display_headline();
		// display_introduction();
		display_countdown();
		display_instructions();
		// display_respondents();
		display_person_menu();
		display_footer();
		display_report_link();		
	}
}
else {
	$formatted_date = date('r', DEADLINE);
	echo <<<EOHTML
		<h2>Survey has closed</h2>
		<p>As of {$formatted_date}</p>
		{$report_link}
EOHTML;
}
print <<<EOHTML
</body>
</html>
EOHTML;

// -----------------------------------------------------

function display_headline() {
	$season_name = get_season_name_from_db($season_id);
	$headline = renderHeadline("Sunward Meals Scheduling - {$season_name} Questionnaire");
	echo $headline;
}

function display_introduction() {
	$season_name = get_season_name_from_db($season_id);
	$month_names = get_current_season();
	$deadline = date('l, F j', DEADLINE);
	if (0) deb("index.display_introduction(): deadline = ", $deadline);
	echo <<<EOHTML
<h2>Introduction</h2>
<p>This is an experiment to get Sunward dinners staffed and scheduled <em>in advance</em> for every Thursday and Sunday of the {$season_name} season ({$month_names[4]}, {$month_names[5]}, and {$month_names[6]}).</p>
<p>To help the community accomplish this, please fill out this questionnaire.  In doing so, you will tell us:</p>
<ul style="list-style-type:circle">
<li style="list-style-type:circle"><em>What</em> Sunward dinner jobs (if any) you are willing and able to do during the {$season_name} season, </li>
<li style="list-style-type:circle"><em>How many times</em> you're available to do each job during this season,</li>
<li style="list-style-type:circle"><em>When</em> you would prefer to do these jobs,</li> 
<li style="list-style-type:circle"><em>Who</em> you would and wouldn't like to work with, and</li> 
<li style="list-style-type:circle">Any other work preferences you may have.</li> 
</ul>
<p>From the questionnaire results, a computer program created by Willie Northway and used for several years at Great Oak will do its best to generate a full schedule of {$season_name} dinners that honors all of our availabilities and preferences. </p>
<p>Then we'll negotiate person-to-person as needed to optimize the schedule.</p>
<p>Then we'll post the finalized meals to <a href="https://gather.coop/meals">Gather</a>, so we can all kick back, sign up, and chow down!</p>
</ul>
EOHTML;
}

function display_instructions() {
	$month_names = get_current_season();
	$deadline = date('ga l, F j', DEADLINE);
	if (0) deb("index.display_introduction(): deadline = ", $deadline);
	echo <<<EOHTML
<p>  
<br>
<p class="question">To get started, just click on your name in this list:</p>
<!--
<p>To get started: </p>
<ul>
<li style="list-style-type:circle">Get out the calendar you use to schedule your activities (whether it's on paper, on your computer, or just in your head).</li>
<li style="list-style-type:circle">Click on your name in the list below.</p>
</ul>
<br>
-->
EOHTML;
}

function display_countdown() {
	$r = new Respondents();
	echo <<<EOHTML
		<div class="special_info">
			{$r->getTimeRemaining()}
		</div>
EOHTML;
}

function display_respondents() {
	// display the responders summary
	$r = new Respondents();
	if (0) deb("inex1.display_respondents: Respondents =", $r);
	echo <<<EOHTML
		<div class="special_info">
			{$r->getTimeRemaining()}
			{$r->getSummary()}
		</div>
EOHTML;
}

function display_person_menu() {
	$respondents = renderPeopleListAsLinks();
	if (empty($respondents)) {
		echo "<h2>No respondents configured</h2>\n";
		return;
	}
	$gold_star = '<img src="/display/images/goldstar02.png" height="12">&nbsp';
	$html = <<<EOHTML
		<table  class="workers_list"  style="font-size:11pt">
			<tr>
			{$respondents}
			</tr>
		</table>
		{$gold_star} marks the wonderful people who have responded to the questionnaire so far
	<br><br>
EOHTML;
	print $html;
}

function display_footer() {
	$html = <<<EOHTML
	<div>
	<p>If you have any questions, concerns, or problems with this questionnaire, please send us a note at <a href="mailto:moremeals@sunward.org?Subject=Help with the Willing Workers Questionnaire" target="_top">moremeals@sunward.org</a>.</p> 
	<p>We <em>do</em> make house calls!  Just ask.</p> 
	</div>
EOHTML;
	print $html;
}

function display_report_link() {
	$dir = BASE_DIR;
	$community = COMMUNITY;
	echo <<<EOHTML
<p class="summary_report"><strong><a href="{$dir}/report.php">Take a look at {$community}'s responses so far</a></strong></p>
EOHTML;
}

/**
 * Render the list of people as links in order to select their survey.
 */
function renderPeopleListAsLinks() {
	$dir = BASE_DIR;
	$list = new PeopleList();
	$respondents = $list->getPeople();
	if (0) deb("index.getPeopleAsLinks: respondents:", $respondents); 
	$lines = '';
	$count = 0;
	$signups_table = ASSIGN_TABLE;
	$responder_ids = getResponders(); 
	if (0) deb("index.renderPeopleListAsLinks(): responder_ids =", $responder_ids); 
	$gold_star = '&nbsp<img src="/display/images/goldstar02.png" height="12">';

	foreach($respondents as $respondent) {
		
		$responded = (in_array($respondent['id'], $responder_ids) ? $gold_star : "");
		$line = <<<EOHTML
			<li><a href="{$dir}/index.php?person={$respondent["id"]}">{$respondent["name"]}</a>{$responded}</li>
EOHTML;
		$lines .= $line;
		if (0) deb("index.getPeopleAsLinks: html line:", $line);

		$count++;
		if (($count % 12) == 0) {
			if (0) deb("PeopleList.renderPeopleListAsLinks(): html lines:", $lines);
			$out .= "<td><ol>{$lines}</ol></td>\n";
			$lines = '';
		}
		if (0) deb("PeopleList.renderPeopleListAsLinks(): person-as-link:", $respondent);
	}

	if ($lines != '') {
		$out .= "<td><ol>{$lines}</ol></td>\n";
	}

	if (0) deb("PeopleList.renderPeopleListAsLinks(): out:", $out);
	return $out;
}


/**
 * @param[in] survey Survey for this respondent to take
 * @param[in] respondent string person's id from the _GET array
 */
function build_survey($survey, $respondent_id) {
	if (0) deb("index.build_survey: respondent_id = ", $respondent_id);
	$survey = new Survey1($respondent_id);
	// $survey->setRespondent($respondent_id);
	if (0) deb("index.build_survey: survey = ", $survey);
	print $survey->toString();
}
?>
