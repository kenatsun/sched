<?php

/*
NOTE 9/3/2022: This is a failed attempt at dynamic rendering of breadcrumbs based on the session's whole calling sequence.  This is called from start.php.  I've commented everything out except some instructions that set a constant (in this case, to "") that are referenced in the code.
*/

// if (0) deb("breadcrumbs.php: _SERVER['SCRIPT_URI'] = " . $_SERVER['SCRIPT_URI'] . "");
// if (0) deb("breadcrumbs.php: _SERVER['SCRIPT_URL'] = " . $_SERVER['SCRIPT_URL'] . ""); 
// if (0) deb("breadcrumbs.php: _SERVER['QUERY_STRING'] = " . $_SERVER['QUERY_STRING'] . ""); 
// if (0) deb("breadcrumbs.php: _SERVER['HTTP_FORWARDED_REQUEST_URI'] = " . $_SERVER['HTTP_FORWARDED_REQUEST_URI'] . ""); 
// if (0) deb("breadcrumbs.php: _SERVER =", $_SERVER); 
// if (0) deb("breadcrumbs.php: _SESSION =", $_SESSION); 
// if (0) deb("breadcrumbs.php: _REQUEST =", $_REQUEST); 
// if (0) deb("breadcrumbs.php: _REQUEST['breadcrumbs'] = " . $_REQUEST['breadcrumbs']); 
// if (0) deb("breadcrumbs.php: _REQUEST['sign_in_as'] = " . $_REQUEST['sign_in_as']); 
// if (0) deb("breadcrumbs.php: _GET =", $_GET); 
// if (0) deb("breadcrumbs.php: _POST =", $_POST);  

// // Get the needed data from the request
// $request_session_id = $_REQUEST['PHPSESSID'];
// $request_crumbs_ids = $_REQUEST['breadcrumbs'];
// $my_url = $_SERVER['SCRIPT_URL']; 
// $request_query_string = $_SERVER['QUERY_STRING']; 

// // Normalize the root url
// if ($my_url == "/") $my_url = "/index.php";
// if (0) deb("breadcrumbs.php: my_url = " . $my_url);  

// // Purge obsolete breadcrumbs from CRUMBS_TABLE whenever returning to root
// if (!$request_crumbs_ids) sqlDelete(CRUMBS_TABLE, "session_id = '" . $request_session_id . "'", (0));

// // Render crumbs of calling path, creating the CRUMBS_QUERY constant
// if ($request_crumbs_ids) {
	// $request_crumbs_arr = sqlSelect("*", CRUMBS_TABLE, "id in (" . $request_crumbs_ids . ")", "when_created asc, id asc", (0)); 
	// // If this page was called from itself, remove it from crumbs
	// if ($request_crumbs_arr) $last_query_crumb_url = end($request_crumbs_arr)['url'];
	// end($request_crumbs_arr);
	// $last_query_crumb_index = key($request_crumbs_arr);
	// if ($my_url == $last_query_crumb_url) unset($request_crumbs_arr[$last_query_crumb_index]);
	// if (0) deb("breadcrumbs.php: my_url = " . $my_url); 
	// if (0) deb("breadcrumbs.php: last_query_crumb_url = " . $last_query_crumb_url); 
	// foreach($request_crumbs_arr as $i=>$crumb) {
		// $crumbs_query .= '&nbsp;&nbsp;<a href="'. $crumb['url'] . '?' . $crumb['query_string'] . '">' . $crumb['crumb_label'] . '</a>'; 
		// $my_crumbs_ids .= $my_crumbs_ids ? "," . $crumb['id'] : $crumb['id'];
	// }
// }
// if (0) deb("breadcrumbs.php: crumb_ids = " . $request_crumbs_ids);
// if (0) deb("breadcrumbs.php: crumbs_display = " . $crumbs_query);
// if (0) deb("breadcrumbs.php: request_crumbs_arr = ", $request_crumbs_arr); 

// // Construct my crumb, the one that leads back to this page (with its original query string)
// if ($request_crumbs_ids) $crumbs_query_string = "breadcrumbs=" . $request_crumbs_ids;
// parse_str($request_query_string, $request_queries_arr);
// if (0) deb("breadcrumbs.php: queries before unset = ", $request_queries_arr);
// unset($request_queries_arr['breadcrumbs']);
// if (0) deb("breadcrumbs.php: queries after unset = ", $request_queries_arr); 
// if ($request_queries_arr) {
	// foreach($request_queries_arr as $key=>$query) {
		// if ($crumbs_query_string) $crumbs_query_string .= "&";
		// $crumbs_query_string .= $key . "=" . $query;
	// }
// }
// if (0) deb("breadcrumbs.php: crumbs_query_string = ", $crumbs_query_string); 
// $crumb_label = sqlSelect("crumb_label", PAGES_TABLE, "url = '" . $my_url . "'", "", (0))[0]['crumb_label'];
// $columns = "session_id, url, query_string, crumb_label, when_created";
// $values = "'" . $_REQUEST['PHPSESSID'] . "', '" . $my_url . "', '" . $crumbs_query_string . "', '" . $crumb_label . "', '" . date("Y-m-d H:i:s") . "'";
// sqlInsert(CRUMBS_TABLE, $columns, $values, (0));  
 
// // Append my_crumb_id to crumb_ids to form NEXT_CRUMBS_IDS, which will be passed in forward links
// // unless it duplicates the last
// $my_crumb = sqlSelect("max(id) as id, url", CRUMBS_TABLE, "", "", (0))[0];
// $my_crumb_id = $my_crumb['id'];
// $my_crumb_url = $my_crumb['url'];
// if ($request_crumbs_ids) $last_crumb_url = end($request_crumbs_arr)['url'];
// if (0) deb("breadcrumbs.php: my_crumb_url = " . $my_crumb_url); 
// if (0) deb("breadcrumbs.php: last_crumb_url = " . $last_crumb_url); 
// if ($last_crumb_url == $my_crumb_url) {
	// $next_crumbs_ids = $request_crumbs_ids;		
// } else {
	// if ($request_crumbs_ids && $my_crumb_id) $separator = ",";  
	// $next_crumbs_ids = $request_crumbs_ids . $separator . $my_crumb_id;	
// }

// // Remove caller's breadcrumb from end of list to form PREVIOUS_CRUMBS_IDS, which will be passed in return links
// $crumbs_ids_arr = explode(',', $request_crumbs_ids);
// if (0) deb("breadcrumbs.php: crumbs_ids_arr = ", $crumbs_ids_arr);
// end($crumbs_ids_arr);
// unset($crumbs_ids_arr[key($crumbs_ids_arr)]);
// if (0) deb("breadcrumbs.php: crumbs_ids_arr = ", $crumbs_ids_arr); 
// $previous_crumbs_ids = implode(',', $crumbs_ids_arr);

// // Define global crumbs constants for this session
define("PREVIOUS_CRUMBS_IDS", $previous_crumbs_ids);  
define("CRUMBS_IDS", $my_crumbs_ids);
define("CRUMBS_QUERY", $crumbs_query);
define("NEXT_CRUMBS_IDS", $next_crumbs_ids);  
if (1) deb("breadcrumbs.php: NEXT_CRUMBS_IDS = ", NEXT_CRUMBS_IDS); 


?>