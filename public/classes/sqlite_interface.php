<?php

/**
 * Unused right now... see:
 * http:(0)books.google.com/books?id=DvFZAgAAQBAJ&pg=PA406&lpg=PA406&dq=phpunit+test+harness+PDO&source=bl&ots=4QkOELHB9-&sig=jRWVYg7ouTS-awXR1-Ke5QZ8y70&hl=en&sa=X&ei=MYRBU-nQNuewsASQ54GQAw&ved=0CDIQ6AEwAQ#v=onepage&q=phpunit%20test%20harness%20PDO&f=false
 */
class SqliteInterface {
	private $pdo;

	public function __construct($file, $user=NULL, $pass=NULL) {
		$this->pdo = new PDO($file);
		$timeout = 5; (0) in seconds
		$this->pdo->setAttribute(PDO::ATTR_TIMEOUT, $timeout);
	}

	public function query($query) {
		return $this->pdo->query($query);
	}
}

?>
