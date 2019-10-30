<?php

// connect to SQLite database
// function create_sqlite_connection() {
	global $dbh;
	global $db_is_writable;
	$db_is_writable = FALSE;

	try {
		global $relative_dir;
		if (!isset($relative_dir)) { 
			$relative_dir = '';
		}
		else {
			$relative_dir .= '/';
		}

		$db_fullpath = getDatabaseFullpath();  // This function is in git_ignored.php because production & development use different databases
		$db_is_writable = is_writable($db_fullpath);
		$db_file = "sqlite:{$db_fullpath}";
		$dbh = new PDO($db_file);
		$timeout = 5; // in seconds
		$dbh->setAttribute(PDO::ATTR_TIMEOUT, $timeout);
		// Enable foreign keys enforcement in database
		$dbh->exec("PRAGMA foreign_keys = ON;");
	}
	catch(PDOException $e) {
		echo "problem loading sqlite file [$db_fullpath]: {$e->getMessage()}\n";
		exit;
	}
// }

// function sqlSelectQuery($select, $from, $where=NULL, $order_by=NULL) {
	// global $dbh;
	// $sql = <<<EOSQL
// SELECT {$select} 
// FROM {$from} 
// EOSQL;
	// if ($where) {
		// $sql .= <<<EOSQL
		
// WHERE {$where}
// EOSQL;
	// }
	// if ($order_by) {
		// $sql .= <<<EOSQL
		
// ORDER BY {$order_by}
// EOSQL;
	// }
	// $rows = array();
	// $found = $dbh->query($sql);
	// if ($found) {
		// foreach($dbh->query($sql) as $row) {
			// // Get rid of the numbered elements that get stuck into these row-arrays,  
			// // leaving only named attributes as elements in the rows array
			// foreach($row as $key=>$value) {
				// if (is_int($key)) unset($row[$key]); 
			// }
			// $rows[] = $row;
		// }
	// }
	// $out = array(
		// "sql" => $sql,
		// "rows" => $rows,
	// );
	// return $out;
// }


?>