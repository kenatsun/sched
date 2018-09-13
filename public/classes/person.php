<?php
class Person {
	// public $person_id;
	public $id;
	public $first_name;
	public $last_name;
	public $name;
	public $email;
	public $username;
	public $unit;

	/**
	 * One person
	 */
	public function __construct($id) {
		$auth_user_table = AUTH_USER_TABLE;
		if (0) deb("person->__construct(): person_id:", $id);		
		global $dbh;
		if ($id == NULL) {
			echo "Person.__construct(): Can't construct a person with no id.";
		} else {
			$sql = "SELECT id, first_name, last_name, email, username, unit FROM {$auth_user_table} WHERE id = {$id};";
			if (0) if ($id == 1) deb("Person->__construct(): SQL to retrieve person with id = 1:", $sql);
			foreach($dbh->query($sql) as $row) {
				if (0) deb("Person->__construct(): person:", $row);
				$this->id = $row['id'];
				$this->person_id = $row['id'];
				$this->first_name = $row['first_name'];
				$this->last_name = $row['last_name'];
				$this->email = $row['email'];
				$this->username = $row['username'];
				$this->unit = $row['unit'];
				$this->name = "";
				if (!is_null($row['first_name'])) {
					$this->name .= $row['first_name'];
				}
				if (!is_null($row['last_name'])) {
					if ($this->name !== "") $this->name .= " ";
					$this->name .= $row['last_name'];
				}
				$row['name'] = $this->name;
				if (0) deb("Person.__construct: row = ", $row);
				// foreach($row as $key=>$value) {
					// if (!is_integer[$key]) $this->key = $row[$key];
				// }
				break;
				// return $row;
			}
			if (0) deb("Person.__construct: person name =", $this->name);
		}
	}
}
?>
