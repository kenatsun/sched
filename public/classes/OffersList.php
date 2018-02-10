<?php
global $relative_dir;
if (!isset($relative_dir)) {
	$relative_dir = './';
}
require_once $relative_dir . 'globals.php';
require_once 'PeopleList.php';
require_once $relative_dir . 'utils.php';

class OffersList {
	// public $offers = array();
	// protected $id;
	// protected $type;
	// protected $instances;
	// protected $person_id;
	// protected $job_id;
	// protected $season_id;
	// protected $job_offers;
	
//	protected job_offers array();
	
		
	public function __construct($person_id) {
		global $dbh;
		$season_id = SEASON_ID;
		$this->offers_table = ASSIGN_TABLE;
		$jobs_table = SURVEY_JOB_TABLE;
		$sql = "
			SELECT j.description, j.id as job_id, o.id, o.instances, {$person_id} as person_id
			FROM {$jobs_table} j LEFT JOIN {$this->offers_table} o 
				ON j.id = o.job_id
				AND o.season_id = {$season_id} 
				AND o.worker_id = {$person_id}";

		if (0) deb("OffersList->__construct(): SQL for offers:", $sql);

		foreach($dbh->query($sql) as $row) {
			$this->offers[] = $row;
		}
		
		// $this->offers = array();
		foreach($this->offers as $key=>$offer) {
			// if (!array_key_exists($offer['id'], $this->offers)) {
				// $this->offers[$offer['id']] = array();
			// }
			$this->offers[$key] = array(
				'description' => $offer['description'],
				'job_id' => $offer['job_id'],
				'person_id' => $offer['person_id'],
				'instances' => $offer['instances'],
			);
		}
		if (0) deb("OffersList->__construct(): offers array:", $this->offers);
		}
	
	// public function getOffers($person_id) {
		// $season_id = SEASON_ID;
		// $this->offers_table = ASSIGN_TABLE;
		// $jobs_table = SURVEY_JOB_TABLE;
		// $sql = "
			// SELECT j.description, j.id as job_id, o.id, o.instances, {$person_id} as person_id
			// FROM {$jobs_table} j LEFT JOIN {$this->offers_table} o 
				// ON j.id = o.job_id
				// AND o.season_id = {$season_id} 
				// AND o.worker_id = {$person_id}";

		// if (0) deb("OffersList->getOffers(): SQL for offers:", $sql);

		// $this->offers = array();
		// global $dbh;
		// foreach($dbh->query($sql) as $row) {
			// $this->offers[] = $row;
		// }
		
		// $this->offers = array();
		// foreach($this->offers as $offer) {
			// if (!array_key_exists($offer['description'], $this->offers)) {
				// $this->offers[$key] = array(
					// 'description' = $offer['description'];
				// );
			// }
			// 'description' = $offer['description'],
			// 'job_id'] = $offer['job_id'];
			// 'person_id'] = $offer['person_id'];
			// 'instances'] = $offer['instances'];
		// }
		// if (0) deb("OffersList->getOffers(): offers array:", $this->offers);
		
		// return $this->offers;
	// }
	
	public function toString($offers) {
		
		if (0) deb("OffersList->toString(): offers:", $offers);
		$html = <<<EOHTML
			<table cellspacing="1" border="0">
EOHTML;
		foreach($offers as $offer) {
			if (0) deb("OffersList->toString(): offer:", $offer);
			$html .= <<<EOHTML
				<tr>
					<td style="text-align:right;">{$offer['description']}:</td> 
					<td><input type:"number" name="{$offer['job_id']}" value="{$offer['instances']}"></td>
				</tr>
EOHTML;
		}
		$html .= "</table>";
		if (0) deb("OffersList->toString():", $html);
		return $html;
	}
}

?>
