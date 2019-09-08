<?php

/**
 * Track the list of people.
 * "People" means everybody in the workers table, whether or not they have any offers (offers_table rows)
 */
class PeopleList {
	public $people;

	/*
	 * Find all of the people.
	 */
	public function __construct($where="") {
		global $dbh;
		if ($where) $where = " AND " . $where;
		$worker_table = AUTH_USER_TABLE;
		$season_worker_table = SEASON_WORKER_TABLE;
		$season_id = SEASON_ID;
		$where = "id IN (SELECT worker_id FROM {$season_worker_table} WHERE season_id = {$season_id})" . $where;
		$people = sqlSelect("*", $worker_table, $where, "first_name, last_name", (0), "PeopleList.__construct()");
		$count = 0;
		foreach($people as $person) {
			// purge elements of $person array that have integer keys
			foreach($person as $key=>$dummy) {
				if (is_integer($key)) unset($person[$key]);
			}
			if ($person['first_name'] && $person['last_name']) $person['name'] = $person['first_name'] . " " . $person['last_name'];
			else $person['name'] = $person['first_name'] . $person['last_name'];
			if (0) deb("PeopleList.__construct(): person:", $person);
			$this->people[$person['id']] = $person;
			$count++;
		}
		if (0) deb("PeopleList.__construct(): count of people:", $count);
		if (0) deb("PeopleList.__construct(): people:", $this->people);
	}

	/**
	 * Get the list of people.
	 */
	public function getPeople() {
		if (0) deb("PeopleList->getPeople(): people:", $this->people);
		return $this->people;
	}


}
