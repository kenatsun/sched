<?php

/**
 * Track the list of people.
 * "People" means everybody in the workers table, whether or not they have any offers (ASSIGN_TABLE rows)
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
		// $this->dbh = $dbh;
		$auth_user_table = AUTH_USER_TABLE;
		$sql = "SELECT id, first_name, last_name, email, username, unit 
			FROM {$auth_user_table} {$where} 
			ORDER BY first_name, last_name;";
		if (0)deb("PeopleList.__construct(): SQL to retrieve person:", $sql);
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
		// if (empty($this->people)) {
			// $this->load();
		// }
		if (0) deb("PeopleList->getPeople(): people:", $this->people);
		return $this->people;
	}


}
