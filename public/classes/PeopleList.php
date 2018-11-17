<?php

/**
 * Track the list of people.
 * "People" means everybody in the workers table, whether or not they have any offers (OFFERS_TABLE rows)
 */
class PeopleList {
	public $people;
	// private $people = [];

	/*
	 * Find all of the people.
	 */
	// public function load($where="") {
	public function __construct($where="") {
		global $dbh;
		if ($where != "") $where = " AND " . $where;
		$auth_user_table = AUTH_USER_TABLE;
		$season_worker_table = SEASON_WORKER_TABLE;
		$season_id = SEASON_ID;
		$sql = "SELECT id, first_name, last_name, email, username, unit 
			FROM {$auth_user_table} 
			WHERE id IN (SELECT worker_id FROM {$season_worker_table} WHERE season_id = {$season_id}) $where
			ORDER BY first_name, last_name;";
		if (0) deb("PeopleList.__construct(): SQL to retrieve person:", $sql);
		$count = 0;
		foreach($dbh->query($sql) as $row) {
			// purge elements of $row array that have integer keys
			foreach($row as $key=>$dummy) {
				if (is_integer($key)) unset($row[$key]);
			}
			$name = "";
			if (!is_null($row['first_name'])) {
				$name .= $row['first_name'];
			}
			if (!is_null($row['last_name'])) {
				if ($this->name !== "") $name .= " ";
				$name .= $row['last_name'];
			}
			$row['name'] = $name;
			if (0) deb("PeopleList.__construct(): person:", $row);
			$this->people[$row['id']] = $row;
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
