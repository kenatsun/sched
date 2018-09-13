<?php
require_once 'globals.php';
require_once 'display/includes/header.php';
require_once 'classes/person.php';
require_once 'classes/survey.php';

$season_name = get_season_name_from_db($season_id);

global $dbh;

resetPostedVariable(); // Not sure this is needed 
if (0) deb("process_survey1: POST =", $_POST);
$respondent = getRespondentFromPost();
if (0) deb("process_survey1: respondent =", $respondent);
$shifts_offered_count = saveOffers($respondent, $offers);
if (0) deb("process_survey1: shifts_offered_count =", $shifts_offered_count);
displayNextPage($respondent, $shifts_offered_count);


function getRespondentFromPost() {
	if (0) deb("process_survey1.getRespondentFromPost(): POST =", $_POST);
	if (0) deb("process_survey1.getRespondentFromPost(): POST[id] =", $_POST['person']);
	if (0) deb("process_survey1.getRespondentFromPost(): POST[username] =", $_POST['username']);
	$respondent = new Person($_POST['person']);
	if (0) deb("process_survey1.getRespondentFromPost(): respondent", $respondent);
	return $respondent;
}

function resetPostedVariable() {
	// is this a posting? save that and delete from POST.
	if (isset($_POST['posted'])) {
		unset($_POST['posted']);
	}
}

function getOffers() {
	foreach($_POST as $job_id=>$offer) {
		if ($job_id == 'username' || $job_id == 'person' || $job_id == 'posted') continue;
		$offer = normalizeOffer($offer);
		if (0) deb("process_survey1.getOffers(): job_id = offer: ", $job_id." = ".$offer);
		if (0) deb("process_survey1.getOffers(): job_name = offer: ", get_job_name($job_id)." = ".$offer);
		$offers[$job_id] = $offer;
	}
	if (0) deb("process_survey1.getOffers(): offers:", $offers);
	return offers;
}

/**
 * Save the stated offers.
 */
function saveOffers($respondent, $offers) {
	$season_id = SEASON_ID;
	$assign_table = ASSIGN_TABLE;
	$shifts_offered_count = 0;  // total number of shifts this person has offered to do (across all jobs)
	// $saved = 0;
	// $job_count = 0;  // number of jobs in this survey
	// $jobs_offered_count = 0;  // number of jobs for which this person is willing to do some shifts (offer > 0)
	// $jobs_nixed_count = 0;  // number of jobs this person doesn't want to do (offer = 0)
	// $jobs_null_count = 0;  // number of jobs this person hasn't decided about yet (offer is NULL)
	// $offer_summary = '';
	// $zero_summary = '';
	// $null_summary = '';

	global $dbh;
	foreach($_POST as $job_id=>$offer) {
		// if ($job_id == 'username' || $job_id == 'person' || $job_id == 'posted') continue;
		if (!is_numeric($job_id)) continue;
		$job_name = get_job_name($job_id);
		$offer = normalizeOffer($offer);
		if (0) deb("Survey1: saveOffers(): job_name=offer", $job_name." = ".$offer);
		$sql = "
			REPLACE INTO {$assign_table} (worker_id, job_id, season_id, instances, type) 
				VALUES({$respondent->id}, {$job_id}, {$season_id}, {$offer}, 'a')";
		if (0) deb("Survey1: saveOffers(): SQL:", $sql);
		$success = $dbh->exec($sql);
		if (0) deb("Survey1: saveOffers(): success?", $success);
		if ($success) {
			$shifts_offered_count += $offer;
			// Increment the jobs, responses, and shifts offered
			// Add a line to the summary
			// if ($offer == 'NULL') {
				// $jobs_null_count++;
				// $null_summary .= "<li>{$job_name}</li>";
			// } elseif ($offer == 0) {
				// $jobs_nixed_count++;
				// $zero_summary .= "<li>{$job_name}</li>";		
			// } else {
				// $jobs_offered_count++;
				// $offer_summary .= "<li>{$job_name}, {$offer} times</li>";
			// }
		// $job_count++;
		}
	}
	if (0) deb("Survey1: saveOffers(): Shift count:", $shifts_offered_count);
	// if (0) deb("Survey1: saveOffers(): Job count:", $job_count);
	// if (0) deb("Survey1: saveOffers(): Job response count:", $job_response_count);
	// if (0) deb("Survey1: saveOffers(): jobs_offered_count:", $jobs_offered_count);
	// if (0) deb("Survey1: saveOffers(): jobs_nixed_count:", $jobs_nixed_count);
	// if (0) deb("Survey1: saveOffers(): jobs_null_count:", $jobs_null_count);
	// if (0) deb("Survey1: saveOffers(): offer_summary:", $offer_summary);
	// if (0) deb("Survey1: saveOffers(): zero_summary:", $zero_summary);
	// if (0) deb("Survey1: saveOffers(): null_summary:", $null_summary);
	return $shifts_offered_count;
}

function normalizeOffer($offer) {
	// All offers except positive integers are treated as 0, meaning "I don't want to do this job"
	if ($offer == NULL || $offer < 0 || !is_numeric($offer)) $offer = 0;
	// Round real number offers to integers
	if (is_float($offer)) $offer = round($is_offer);
	return $offer;
}

function displayNextPage($respondent, $shifts_offered_count) {
	if ($shifts_offered_count > 0) {
		displayPreferencesSurvey($respondent);
	} else {
		displayNoShiftsOfferedPage($respondent);
	}
}

function displayPreferencesSurvey($respondent) {
	$_GET['person'] = $respondent->username;  // survey.php is looking for username in the 'person' hidden input field
	// $_GET['worker'] = $respondent->username;  // survey.php is looking for username in the 'worker' hidden input field
	if (0) deb("process_survey1.displayPreferencesSurvey(): GET =", $_GET);
	if (0) deb("process_survey1.displayPreferencesSurvey(): respondent", $respondent);
	$survey = new Survey();
	// $survey->setIsSaveRequest(TRUE);
	$survey->setWorker($respondent->username, $respondent->id, $respondent->first_name, $respondent->last_name);
	// $survey->run();
	print $survey->toString();
}

function displayNoShiftsOfferedPage($respondent) {
	finishSurvey(NULL, $respondent->id);
	// print "No shifts offered by {$respondent->name} (need a page for this, providing a link to retake offers survey etc)";	
}

?>