<?php
require_once 'globals.php';
require_once 'display/includes/header.php';
require_once 'classes/person.php';
require_once 'classes/survey.php';

$season_id = SEASON_ID;
$season_name = get_season_name_from_db($season_id);;
$assign_table = ASSIGN_TABLE;

global $dbh;
$results = [];

if (1) deb("process_survey1.php: POST =", $_POST);
if (1) deb("process_survey1.php: POST[id] =", $_POST['person']);
if (1) deb("process_survey1.php: POST[username] =", $_POST['username']);
$_GET['person'] = $_POST['username'];
if (1) deb("process_survey1.php: GET =", $_GET);
$respondent_id = $_POST['person'];

if (0) deb("process_survey1.php: respondent", $respondent_id);
$respondent = new Person($respondent_id);


foreach($_POST as $job_id=>$offer) {
	if ($job_id == 'username') continue;
	
	$job_name = get_job_name($job_id);
	// All offers except positive integers are treated as 0, meaning "I don't want to do this job"
	if ($offer == NULL || $offer < 0 || !is_numeric($offer)) $offer = 0;
	if (is_float($offer)) $offer = round($is_offer);
	if (0) deb("process_survey1.php: job_name = offer: ", $job_name." = ".$offer);
	$results[$job_id] = $offer;
}
if (0) deb("process_survey1.php: results:", $results);

/**
 * Save the stated offers.
 */
$saved = 0;
$job_count = 0;  // number of jobs in this survey
$jobs_offered_count = 0;  // number of jobs for which this person is willing to do some shifts (offer > 0)
$jobs_nixed_count = 0;  // number of jobs this person doesn't want to do (offer = 0)
$jobs_null_count = 0;  // number of jobs this person hasn't decided about yet (offer is NULL)
$shifts_offered_count = 0;  // total number of shifts this person has offered to do (across all jobs)
$offer_summary = '';
$zero_summary = '';
$null_summary = '';

foreach($results as $job_id=>$offer) {
	if ($job_id == 'username') {
		continue;
	}
	$job_name = get_job_name($job_id);
	if ($offer == NULL) $offer = 0;
	if (0) deb("Survey1: saveOffers(): job_id=offer", $job_name." = ".$offer);
	$sql = "
		REPLACE INTO {$assign_table} (worker_id, job_id, season_id, instances, type) 
			VALUES({$respondent_id}, {$job_id}, {$season_id}, {$offer}, 'a')";
	if (0) deb("Survey1: saveOffers(): SQL:", $sql);
	$success = $dbh->exec($sql);
	if (0) deb("Survey1: saveOffers(): success?", $success);
	if ($success) {
		if ($offer !== 'NULL') {
			$shifts_offered_count += $offer;
		}
		// Increment the jobs, responses, and shifts offered
		// Add a line to the summary
		if ($offer == 'NULL') {
			$jobs_null_count++;
			$null_summary .= "<li>{$job_name}</li>";
		} elseif ($offer == 0) {
			$jobs_nixed_count++;
			$zero_summary .= "<li>{$job_name}</li>";		
		} else {
			$jobs_offered_count++;
			$offer_summary .= "<li>{$job_name}, {$offer} times</li>";
		}
	$job_count++;
	}
}
if (0) deb("Survey1: saveOffers(): Job count:", $job_count);
if (0) deb("Survey1: saveOffers(): Job response count:", $job_response_count);
if (0) deb("Survey1: saveOffers(): Shift count:", $shifts_offered_count);
if (0) deb("Survey1: saveOffers(): jobs_offered_count:", $jobs_offered_count);
if (0) deb("Survey1: saveOffers(): jobs_nixed_count:", $jobs_nixed_count);
if (0) deb("Survey1: saveOffers(): jobs_null_count:", $jobs_null_count);
if (0) deb("Survey1: saveOffers(): offer_summary:", $offer_summary);
if (0) deb("Survey1: saveOffers(): zero_summary:", $zero_summary);
if (0) deb("Survey1: saveOffers(): null_summary:", $null_summary);

if ($shifts_offered_count > 0) {
	$survey = new Survey();
	$survey->setWorker($respondent->username, $respondent->id, $respondent->first_name, $respondent->last_name);
	print $survey->toString();
} else {
	print 'No shifts offered (need a page for this, providing a link to retake offers survey etc)';
}

// /**
// * Render a notification message when the data has been saved.
// * @return string HTML of the notification message.
// */
// $summary_text = '';

// if ($offer_summary) {
	// if ($jobs_offered_count == 1) {$string = "this job";} else {$string = "these jobs";};
	// $summary_text .= "You have offered to do {$string} during {$season_name}:
// <ul>
// {$offer_summary}
// </ul>";
// } 
// if ($zero_summary) {
	// if ($jobs_nixed_count == 1) {$string = "this job";} else {$string = "these jobs";};
	// $summary_text .= "You don't want to do {$string} at all during {$season_name}:
// <ul>
// {$zero_summary}
// </ul>";
// } 
// if ($null_summary) {
	// if ($jobs_null_count == 1) {$string = "this job";} else {$string = "these jobs";};
	// $summary_text .= "You haven't decided yet about doing {$string} during {$season_name}:
// <ul>
// {$null_summary}
// </ul>";
// } 

// if (0) deb("survey1: renderSaved(): summary_text:", $summary_text);
// $dir = BASE_DIR;
// $out = <<<EOHTML
// <style>
// li {
// list-style-type: disc;
// } 
// </style>
// <h2>Thank you, {$respondent->name}</h2>
// </div>
// {$summary_text}
// <br>
// <p>You may <a href="{$dir}/index1.php?person={$respondent_id}">revise your offers</a>
// or <a href="{$dir}/index.php?worker={$respondent->id}">proceed to your preferences survey</a>.</p>
// <br><br>
// EOHTML;

// print $out;

// /**
 // * Send an email message with the results.
 // * @param[in] content string the summary results.
 // */

// if (!SKIP_EMAIL) {
	// $sent = mail($respondent->email,
		// 'Meal Scheduling Survey1 preferences saved',
		// strip_tags($out),
		// 'From: ken@sunward.org');

	// if (!$sent) {
		// error_log("Unable to send email to: $to");
	// }

	// // if user is under pref level, then send warning email
	// if (!is_null($insufficient_prefs_msg)) {
		// $sent = mail('ken@sunward.org',
			// 'Meal Scheduling Survey1 preferences saved under limit',
			// $person . "\n" . strip_tags($out . "\n" .
				// insufficient_prefs_msg),
			// 'From: ken@sunward.org');
	// }
// }

// return $sent;

?>