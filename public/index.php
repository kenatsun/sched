<?php
session_start();


require_once 'utils.php';
require_once 'globals.php';
require_once 'classes/calendar.php';
require_once 'classes/PeopleList.php'; 
require_once 'classes/OffersList.php';
require_once 'classes/person.php';
require_once 'classes/survey1.php';

require_once 'display/includes/header.php';
require_once 'participation.php';

if (0) deb("index.php: start _COOKIE =", $_COOKIE);

$dir = BASE_DIR;

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

// ----- deadline check ----
global $extended;
if (0) deb("index: userIsAdmin() = " . userIsAdmin());
$now = time();
if ($now <= DEADLINE || $extended || userIsAdmin()) {
	// If a person has been specified in the request, do their survey
	// else display the list of all respondents as links
	$respondent_id = array_get($_GET, 'person');
	if (0) deb("index: person from array_GET:", $respondent_id);
	if (0) deb("index: array dollarsign_GET:", $_GET);
	if (is_null($respondent_id)) {
		display_headline();
		display_countdown();
		if ($extended) echo "...but briefly re-opened to admit a few last-minute responses!";
		display_instructions();
		display_person_menu();
		display_footer();
		$community = COMMUNITY;
		display_job_signups("<h3><em>What we've signed up for so far</em></h3>", FALSE);
		display_report_link("Click here for details of the responses");	
		
	} else {
		build_survey($respondent_id);
	}
}
else {
	$formatted_date = date('r', DEADLINE);
	display_headline();
	display_countdown();
	display_report_link("View the schedule");		
}
if (userIsAdmin()) {
	echo "<br><p><h3><em>Admin Tools</em></h3></p>";
	display_seasons_link("Manage Seasons");
	display_scheduler_link("Run the Scheduler");
	display_dashboard_link("Edit Assignments");	
	echo "<br><br>";
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

function display_instructions() {
	$month_names = get_current_season_months();
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
	print $html;
	$html = <<<EOHTML
	<div>
	<p>If you have any questions, concerns, or problems with this questionnaire, please send us a note at <a href="mailto:moremeals@sunward.org?Subject=Help with the Willing Workers Questionnaire" target="_top">moremeals@sunward.org</a>.</p> 
	<p>We <em>do</em> make house calls!  Just ask.</p> 
	</div>
EOHTML;
}

function display_job_signups($headline, $display_details) {
	$signups = renderJobSignups($headline, $display_details);
	$html = <<<EOHTML
	<div>
	{$signups}
	</div>
EOHTML;
	print $html;
}

function display_report_link($text) {
	$dir = PUBLIC_DIR;
	echo '<p class="summary_report"><a href="'. PUBLIC_DIR . '/report.php">' . $text . '</a></p>';
}

function display_seasons_link($text) {
	$dir = BASE_DIR;
	echo '<p class="summary_report"><strong><a href="'. PUBLIC_DIR . '/seasons.php">' . $text . '</a></strong></p>';
}

function display_scheduler_link($text) {
	echo '<p class="summary_report"><strong><a href="'. PUBLIC_DIR . '/run_scheduler_from_web.php">' . $text . '</a></strong></p>';
}

function display_dashboard_link($text) {
	$dir = BASE_DIR;
	echo '<p class="summary_report"><strong><a href="'. PUBLIC_DIR . '/dashboard.php">' . $text . '</a></strong></p>';
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
	$signups_table = OFFERS_TABLE;
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
 * @param[in] respondent_id string person's id from the _GET array
 */
// function build_survey($survey, $respondent_id) {
function build_survey($respondent_id) {
	if (0) deb("index.build_survey: respondent_id = ", $respondent_id);
	$survey = new Survey1($respondent_id);
	if (0) deb("index.build_survey: survey = ", $survey);
	print $survey->renderOffersList();
}
?>
