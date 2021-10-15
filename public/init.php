<?php

// connect to SQLite database
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

?>