<?php

require_once 'start.php';
// require_once 'globals.php';
// require_once 'display/includes/header.php';
require_once 'classes/person.php';
require_once 'classes/PeopleList.php';
require_once 'classes/OffersList.php';

class Survey1 {
	protected $person;
	protected $name;
	protected $username;
	protected $id;
	protected $avoid_list;
	protected $prefer_list;
	protected $saved = 0;
	protected $summary;
	protected $results = array();

	// track the number of jobs marked positively
	protected $shifts_offered_count;

	protected $is_save_request = FALSE;

	protected $insufficient_prefs_msg;

	public function __construct($respondent_id=NULL) {
		global $dbh;
		$this->dbh = $dbh;
		$this->season_id = SEASON_ID;
		$this->season_name = get_season_name_from_db($this->season_id);
		if (0) deb("survey1.__construct(): respondent_id:", $respondent_id);
		if (!$respondent_id == NULL) {
			$this->person = new Person($respondent_id);
			$this->offers_list = new OffersList($respondent_id);
			if (0) deb("survey1.__construct(): this->offers_list:", $this->offers_list);
			$this->offers = $this->offers_list->offers;
		}
		if (0) deb("survey1.__construct(): Person data:", $this->person);
		if (0) deb("survey1.__construct(): this->offers:", $this->offers);
		if (0) deb("survey1.__construct(): _SESSION = ", $_SESSION);
		if (0) deb("survey1.__construct(): userIsAdmin() = " . userIsAdmin());
	}

	/**
	 * Set the assigned respondent, and load their info from the database
	 *
	 * @param[in] id int, the unique ID for this person.
	 */
	public function setRespondent($id) {
		if (0) deb("survey1.setRespondent(): id:", $id);
		$this->person = new Person($id);
		if (0) deb("survey1.setRespondent(): Person data:", $this->person);
	}

	public function renderOffersList() {
		if (0) deb("survey1.renderOffersList(): _GET", $_GET);
		if (0) deb("survey1.renderOffersList(): id:", $this->person->name);
		if (0) deb("survey1.renderOffersList(): offers:", $this->offers);
		if (0) deb("survey1.renderOffersList(): this->person->username:", $this->person->username);
		if (0) deb("survey1.renderOffersList(): _GET['person']:", $_GET['person']);
		if ($this->is_save_request) {
			$out = $this->renderSaved();
			$this->sendEmail($this->person->username, $out);
			return <<<EOHTML
<div class="saved_notification">{$out}</div>
EOHTML;
		}
		$headline = renderHeadline("Step 1: Sign Up for Dinner Jobs", BREADCRUMBS); 
		$send_email = renderSendEmailControl($this->person->name);
		return <<<EOHTML
		{$headline}
		<p>Welcome, {$this->person->name}!</p>
		<form method="POST" action="process_survey1.php">
			<input type="hidden" name="person" value="{$_GET['person']}">
			<input type="hidden" name="username" value="{$this->person->username}">
			<input type="hidden" name="posted" value="0">
			{$this->renderInstructions()}
			{$this->renderHints()}
			{$this->offers_list->renderOffersList($this->offers)}
			{$send_email}
			<button class="pill" type="submit" value="Save" id="end">Next</button>
		</form>
EOHTML;
	}

	protected function renderInstructions() {
		$season_name = get_season_name_from_db();
		return <<<EOHTML
				<br>
				<p class="question">How many times are you willing and able to do each of these meal jobs during {$season_name}?</p>  
				<br>
EOHTML;
		}
		
	protected function renderHints() {
		$season_name = get_season_name_from_db();
		$months = get_current_season_months();
		if (0) deb("survey1.renderHints: months = ", $months);
		$month_names = array_values($months);
		if (0) deb("survey1.renderHints: month_names = ", $month_names);
		return <<<EOHTML
<!--			<p>Hopefully helpful hints:</p> -->
			<ul>
				<li style="list-style-type:circle">We're scheduling for the {$season_name} season, which consists of {$month_names[0]}, {$month_names[1]}, and {$month_names[2]}.</li>
				<li style="list-style-type:circle">The number you enter is how many times you will do this job in this whole three-month period.  So for example if you want to be an assistant cook once a month, enter a 3 next to "asst cook".</li>
				<li style="list-style-type:circle">If you don't want to do a particular job at all this season, enter a 0.</li>
			</ul>
			<br>
EOHTML;
		}

/* ------------------------------------------------ */

	public function run() {
		$this->lookupUsername();
		$this->lookupPersonId();
		if (0) deb("survey1.run(): POST =", $_POST);
		if (0) deb("survey1.run(): POST[person] =", $_POST['person']);
		if (0) deb("survey1.run(): POST[username] =", $_POST['username']);
		$this->setRespondent($_POST['person']);

		$this->processPost();

		// save the survey
		$this->saveOffers();
	}

	/**
	 * Get username from post.
	 */
	protected function lookupUsername() {
		if (!isset($_POST['username'])) {
			echo "<p class=\"error\">Missing username</p>\n";
			exit;
		}
		$this->username = $_POST['username'];
	}


	protected function lookupPersonId() {
		$auth_user_table = AUTH_USER_TABLE;
		$sql = <<<EOSQL
select id from {$auth_user_table} where username='{$this->username}'
EOSQL;

		// get this person's ID
		$this->id = NULL;
		foreach($this->dbh->query($sql) as $row) {
			$this->id = $row['id'];
			return;
		}
	}

	protected function processPost() {
		// is this a posting? save that and delete from POST.
		if (isset($_POST['posted'])) {
			$this->is_save_request = TRUE;
			unset($_POST['posted']);
		}

		// process the remaining post vars
		if (0) deb("Survey1: processPost: POST = ", $_POST);
		foreach($_POST as $job_id=>$offer) {
			if ($job_id == 'username') {
				continue;
			}
			
			$job_name = get_job_name($job_id);
			// All offers except positive integers are treated as 0, meaning "I don't want to do this job"
			if ($offer == NULL || $offer < 0 || !is_numeric($offer)) $offer = 0;
			if (is_float($offer)) $offer = round($is_offer);
			if (0) deb("Survey1: processPost: job_name = offer: ", $job_name." = ".$offer);
			$this->results[$job_id] = $offer;
		}
		if (0) deb("Survey1: processPost: Results:", $this->results);
	}

	/**
	 * Save the stated offers.
	 */
	protected function saveOffers() {
		$this->saved = 0;
		$OFFERS_TABLE = OFFERS_TABLE;
		$this->job_count = 0;  // number of jobs in this survey
		$this->jobs_offered_count = 0;  // number of jobs for which this person is willing to do some shifts (offer > 0)
		$this->jobs_nixed_count = 0;  // number of jobs this person doesn't want to do (offer = 0)
		$this->jobs_null_count = 0;  // number of jobs this person hasn't decided about yet (offer is NULL)
		$this->shifts_offered_count = 0;  // total number of shifts this person has offered to do (across all jobs)
		$this->offer_summary = '';
		$this->zero_summary = '';
		$this->null_summary = '';
		
		foreach($this->results as $job_id=>$offer) {
			if ($job_id == 'username') {
				continue;
			}
			$job_name = get_job_name($job_id);
			if ($offer == NULL) $offer = 0;
			if (0) deb("Survey1: saveOffers(): job_id=offer", $job_name." = ".$offer);
			$sql = "
				REPLACE INTO {$OFFERS_TABLE} (worker_id, job_id, season_id, instances, type) 
					VALUES({$this->id}, {$job_id}, {$this->season_id}, {$offer}, 'a')";
			if (0) deb("Survey1: saveOffers(): SQL:", $sql);
			$success = $this->dbh->exec($sql);
			if (0) deb("Survey1: saveOffers(): success?", $success);
			if ($success) {
				if ($offer !== 'NULL') {
					$this->shifts_offered_count += $offer;
				}
				// Increment the jobs, responses, and shifts offered
				// Add a line to the summary
				if ($offer == 'NULL') {
					$this->jobs_null_count++;
					$this->null_summary .= "<li>{$job_name}</li>";
				} elseif ($offer == 0) {
					$this->jobs_nixed_count++;
					$this->zero_summary .= "<li>{$job_name}</li>";		
				} else {
					$this->jobs_offered_count++;
					$this->offer_summary .= "<li>{$job_name}, {$offer} times</li>";
				}
			$this->job_count++;
			}
		}
		if (0) deb("Survey1: saveOffers(): Job count:", $this->job_count);
		if (0) deb("Survey1: saveOffers(): Job response count:", $this->job_response_count);
		if (0) deb("Survey1: saveOffers(): Shift count:", $this->shifts_offered_count);
		if (0) deb("Survey1: saveOffers(): jobs_offered_count:", $this->jobs_offered_count);
		if (0) deb("Survey1: saveOffers(): jobs_nixed_count:", $this->jobs_nixed_count);
		if (0) deb("Survey1: saveOffers(): jobs_null_count:", $this->jobs_null_count);
		if (0) deb("Survey1: saveOffers(): offer_summary:", $this->offer_summary);
		if (0) deb("Survey1: saveOffers(): zero_summary:", $zero_summary);
		if (0) deb("Survey1: saveOffers(): null_summary:", $this->null_summary);
	}


	/**
	 * Render a notification message when the data has been saved.
	 * @return string HTML of the notification message.
	 */
	public function renderSaved() {
		$summary_text = '';

		if ($this->offer_summary) {
			if ($this->jobs_offered_count == 1) {$phrase = "this job";} else {$phrase = "these jobs";};
			$summary_text .= "You have offered to do {$phrase} during {$this->season_name}:
<ul>
{$this->offer_summary}
</ul>";
		} 
		if ($this->zero_summary) {
			if ($this->jobs_nixed_count == 1) {$phrase = "this job";} else {$phrase = "these jobs";};
			$summary_text .= "You don't want to do {$phrase} at all during {$this->season_name}:
<ul>
{$this->zero_summary}
</ul>";
		} 
		if ($this->null_summary) {
			if ($this->jobs_null_count == 1) {$phrase = "this job";} else {$phrase = "these jobs";};
			$summary_text .= "You haven't decided yet about doing {$phrase} during {$this->season_name}:
<ul>
{$this->null_summary}
</ul>";
		} 

		if (0) deb("survey1: renderSaved(): summary_text:", $summary_text);
		$dir = BASE_DIR;
		return <<<EOHTML
	<style>
	li {
		list-style-type: circle;
	} 
	</style>
	<h2>Job Offers Summary</h2>
	</div>
	{$summary_text}
	<br>
	<p>You may <a href="{$dir}/index.php?person={$this->person->id}">revise your offers</a>
		or <a href="{$dir}/index.php?worker={$this->person->id}">proceed to your preferences survey</a>.</p>
	<br><br>
EOHTML;
	}

}

?>
