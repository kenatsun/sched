<?php
global $relative_dir;
if (!isset($relative_dir)) {
	$relative_dir = './';
}
require_once $relative_dir . 'globals.php';
require_once 'PeopleList.php';
require_once $relative_dir . 'utils.php';

class OffersList {
		
	public function __construct($person_id) {
		global $dbh;
		$season_id = SEASON_ID;
		$this->offers_table = OFFERS_TABLE;
		$jobs_table = SURVEY_JOB_TABLE;
		$sql = "
			SELECT j.description, j.id as job_id, o.id, o.instances, o.season_id, {$person_id} as person_id
			FROM {$jobs_table} j LEFT JOIN {$this->offers_table} o 
				ON j.id = o.job_id
				AND o.season_id = {$season_id} 
				AND o.worker_id = {$person_id}
			WHERE j.season_id = {$season_id}";

		if (0) deb("OffersList->__construct(): SQL for offers:", $sql);

		foreach($dbh->query($sql) as $row) {
			$this->offers[] = $row;
		}
		
		// $this->offers = array();
		foreach($this->offers as $key=>$offer) {
			$this->offers[$key] = array(
				'description' => $offer['description'],
				'job_id' => $offer['job_id'],
				'person_id' => $offer['person_id'],
				'instances' => $offer['instances'],
				'season_id' => $offer['season_id'],
			);
		}
		if (0) deb("OffersList->__construct(): offers array:", $this->offers);
		}
	
	public function renderOffersList($offers) {
		
		if (0) deb("OffersList->renderOffersList(): offers:", $offers);
		$html = <<<EOHTML
		<table ><tr><td style="background:yellow">
			<table style="border-spacing: 3px;" border="1" cellpadding="8"> 
				<tr class="day_labels" style="background: Yellow; ">
					<th class="day_of_week" style="text-align:center; ">job</th>
					<th class="day_of_week" style="text-align:center">how many times?</th>
				</tr>
EOHTML;
		foreach($offers as $offer) {
			if (0) deb("OffersList->renderOffersList(): offer:", $offer);
			$html .= <<<EOHTML
				<tr>
					<td style="text-align:right; vertical-align:middle; font-size:11pt;"><strong>{$offer['description']}</strong></td> 
					<td><input style="width:80px" type:"number" name="{$offer['job_id']}" value="{$offer['instances']}"></td>
EOHTML;
		}
		$html .= "</table>
		</td></tr></table>";
		if (0) deb("OffersList->renderOffersList():", $html);
		return $html;
	}
}

?>
