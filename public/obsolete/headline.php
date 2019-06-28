<?php

if (0) deb("headline.php: _SERVER['SCRIPT_URI'] = " . $_SERVER['SCRIPT_URI'] . "");
if (0) deb("headline.php: _SERVER['SCRIPT_URL'] = " . $_SERVER['SCRIPT_URL'] . ""); 
if (0) deb("headline.php: _SERVER['QUERY_STRING'] = " . $_SERVER['QUERY_STRING'] . ""); 
if (0) deb("headline.php: _SERVER['HTTP_FORWARDED_REQUEST_URI'] = " . $_SERVER['HTTP_FORWARDED_REQUEST_URI'] . ""); 
if (0) deb("headline.php: _SERVER =", $_SERVER); 
if (0) deb("headline.php: _SESSION =", $_SESSION); 
if (0) deb("headline.php: _REQUEST =", $_REQUEST); 
if (0) deb("headline.php: _REQUEST['breadcrumbs'] = " . $_REQUEST['breadcrumbs']); 
if (0) deb("headline.php: _GET =", $_GET); 
if (0) deb("headline.php: _POST =", $_POST);  

$request_session_id = $_REQUEST['PHPSESSID'];
$request_crumbs = $_REQUEST['breadcrumbs'];
$script_url = $_SERVER['SCRIPT_URL'];
$request_query_string = $_SERVER['QUERY_STRING'];

// Normalize the root url
if ($script_url == "/") $script_url = "/index.php";
if (0) deb("headline.php: script_url = " . $script_url); 

// Purge obsolete breadcrumbs from CRUMBS_TABLE
if (!$request_crumbs) sqlDelete(CRUMBS_TABLE, "request_session_id = '" . $request_session_id . "'", (0));

// Render crumbs of calling path - CRUMBS_TABLE version
$crumb_ids = $request_crumbs;
if ($crumb_ids) {
	$request_crumbs_arr = sqlSelect("*", CRUMBS_TABLE, "id in (" . $crumb_ids . ")", "id", (0));
	if ($request_crumbs_arr) {
		foreach($request_crumbs_arr as $i=>$crumb) {
			$crumbs_display .= '&nbsp;&nbsp;<a href="'. $crumb['url'] . '?' . $crumb['query_string'] . '">' . $crumb['crumb_label'] . '</a>'; 
		}
	}
}
define("CRUMBS", $crumbs_display);
if (0) deb("headline.php: crumb_ids = " . $crumb_ids);
if (0) deb("headline.php: crumbs_display = " . $crumbs_display);
if (0) deb("headline.php: CRUMBS = " . CRUMBS); 
if (0) deb("headline.php: crumbs_arr = " . $request_crumbs_arr); 


// Construct my crumb, the one that leads back to this page (with its original query string) - CRUMBS_TABLE version
if ($request_crumbs) $query_string = "breadcrumbs=" . $request_crumbs;
parse_str($_SERVER['QUERY_STRING'], $queries_arr);
if (0) deb("headline.php: queries before unset = ", $queries_arr);
unset($queries_arr['breadcrumbs']);
if (0) deb("headline.php: queries after unset = ", $queries_arr); 
if ($queries_arr) {
	foreach($queries_arr as $key=>$query) {
		if ($query_string) $query_string .= "&";
		$query_string .= $key . "=" . $query;
	}
}
if (0) deb("headline.php: query_string = ", $query_string); 
$crumb_label = sqlSelect("crumb_label", PAGES_TABLE, "url = '" . $script_url . "'", "", (0))[0]['crumb_label'];
$columns = "session_id, url, query_string, crumb_label";
$values = "'" . $_REQUEST['PHPSESSID'] . "', '" . $script_url . "', '" . $query_string . "', '" . $crumb_label . "'";
sqlInsert(CRUMBS_TABLE, $columns, $values, (0));  
 

// Append my_crumb_id to crumb_ids to form NEXT_CRUMBS_IDS, which will be passed in forward links
$my_crumb_id = sqlSelect("max(id) as id", CRUMBS_TABLE, "", "", (0))[0]['id'];
if ($crumb_ids && $my_crumb_id) $separator = ",";  
$next_crumb_ids = $crumb_ids . $separator . $my_crumb_id;
define("NEXT_CRUMBS_IDS", $next_crumb_ids);  
if (0) deb("headline.php: NEXT_CRUMBS_IDS = ", NEXT_CRUMBS_IDS); 


//////////////////////////////////////////////// FUNCTIONS 

/*
Print a headline for a page
*/
function renderHeadline($text, $crumbs_str="", $subhead="", $show_admin_link=1) {
	if (0) deb ("headline.renderHeadline(): text =", $text);
	if (0) deb ("headline.renderHeadline(): crumbs_str = '" . $crumbs_str . "'");
	if (0) deb ("headline.renderHeadline(): labeled_crumbs =", $labeled_crumbs);
	$td_style = 'background-color:white;';
	// $labeled_crumbs = labelCrumbs($crumbs_str);
	// if ($labeled_crumbs) {
		// foreach($labeled_crumbs as $i=>$labeled_crumb) {
			// $crumbs_display .= '&nbsp;&nbsp;<a  href="'. $labeled_crumb['url'] . '">' . $labeled_crumb['label'] . '</a>'; 
		// }
		// $crumbs_display = '
			// <tr style="font-size:10pt; font-style:italic">
				// <td colspan="2" style="text-align:right; ' . $td_style . '"><<<<< &nbsp;&nbsp;go back to:' . $crumbs_display . '</td>
			// </tr>';
	// }
	if (CRUMBS) {
		$crumbs_display = '
			<tr style="font-size:10pt; font-style:italic">
				<td colspan="2" style="text-align:right; ' . $td_style . '"><<<<< &nbsp;&nbsp;go back to:' . CRUMBS . '</td>
			</tr>';
	}
	if (0) deb ("headline.renderHeadline(): crumbs_display =", $crumbs_display);

	$community_logo = (COMMUNITY == "Sunward" ? '/display/images/sunward_logo.png' : '/display/images/great_oak_logo.png');

	if ($subhead) {
		$headline = '<td style="$td_style" class="headline">' . $text;
		$headline .= '<br><span style="font-size:18px">' . $subhead . '</span>';
	} 
	else {
		$headline = '<td style="$td_style" class="headline">' . $text . '</td>';
	}
	
	$instance = INSTANCE;
	$database = DATABASE;
	$color = '"color:red"';
	$instance_notice = $instance && !$_GET['printable'] == 1 ? "<p style={$color}><strong>You're looking at the {$instance} instance.  Database is {$database}.</strong></p>" : "";
	if ($instance_notice && !userIsAdmin()) $instance_notice .= "<br>";
	// $end_session_label = "End this session and start a new one.";
	// $sign_in_as_guest_label = "Sign in as a guest";
	if (userIsAdmin() && !$_GET['printable'] == 1) $admin_notice = 
		'
		<div style=' . $color . '><p>
		<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
			<input type="hidden" name="sign_in_as_guest" value="1">
			<p><strong>You are signed into this session as an admin. </strong>
			<input type="submit" value="Sign in as a plain old ordinary user.">
		</form>
		</p><br></div>';
	if (0) deb ("headline.renderHeadline(): NEXT_CRUMBS_IDS = " . NEXT_CRUMBS_IDS);
	if (userIsAdmin() && $show_admin_link) $admin_link =  
		'<div style=' . $color . '>
			<a style=' . $color . ' href="'. makeURI("/admin.php", NEXT_CRUMBS_IDS) . '"><strong>Open the Admin Dashboard</strong></a>
		</div>
		<br>';

	if (0) deb ("headline.renderHeadline(): userIsAdmin() =", userIsAdmin()); 
	if (0) deb ("headline.renderHeadline(): admin_notice =", $admin_notice);
		
	return <<<EOHTML
	{$instance_notice}
	{$admin_notice}
	{$admin_link}
	<table>
		{$crumbs_display}
		<tr>
			<td style="$td_style"><img src={$community_logo}></td>
			{$headline}	
		</tr>
	</table>
EOHTML;
}


// CRUMBS FUNCTIONS - start ----------------------------------------------


// function addCrumb($crumbs="", $crumb_to_add="") {
	// if ($crumbs && $crumb_to_add) $crumbs .= CRUMB_SEPARATOR;
	// $crumbs .= $crumb_to_add;
	// if (0) deb("headline.addCrumb(): crumbs after add = {$crumbs}");
	// return $crumbs; 
// }

// function removeCrumbs($crumbs="", $n=1) {
	// // Remove the rightmost $n crumbs from a crumbs string
	// if (0) deb("headline.removeCrumbs(): crumbs before removing {$n} = {$crumbs}");
	// for ($i=1; $i<=$n; $i++) {
		// if (strrpos($crumbs, ",")) {
			// $crumbs = substr($crumbs, 0, $i);
		// } else {
			// $crumbs = "";
		// }
	// }
	// if (0) deb("headline.removeCrumbs(): crumbs after removing {$n} = {$crumbs}");
	// return $crumbs;
// }


// function makeCrumbs($first="", $last="") {
	// $crumbs_arr = array();
		// $crumbs_arr[] = '/index.php';
		// if (userIsAdmin()) $crumbs_arr[] = '/admin.php';
	// }
	// if ($_GET['edition']) $crumbs_arr = "";  // no breadcrumbs for printable presentation of schedule
	// define('CRUMBS_ARR', $crumbs_arr);
// }
// ---------- constants for crumbs ---------

// define('HOME_CRUMB', 'index.php');
// define('SURVEY_SIGNUPS_CRUMB', 'survey_page_1.php?person=');
// define('SURVEY_CALENDAR_CRUMB', 'survey_page_2.php?person=');
// define('ADMIN_CRUMB', 'dashboard.php');
// define('CONDUCT_SURVEY_CRUMB', 'survey_steps.php?parent_process_id=2');
// define('CONDUCT_SURVEY_CRUMB', 'survey_steps.php?parent_process_id=2');
// define('CREATE_TEAMS_CRUMB', 'survey_steps.php?parent_process_id=3');
// define('REFINE_TEAMS_CRUMB', 'dashboard.php');


// function labelCrumbs($crumbs_str) { 
	// // Returns the crumbs in CRUMBS_ARR,
	// // that appear in the $crumb_labels array",
	// // in an array containing the 'url' and the 'label' to be displayed in the crumbs list.
	
	// $crumb_labels = array(
		// '/' => 'Home',
		// '/index.php' => 'Home',
		// '/seasons.php' => 'Manage Seasons',
		// '/season.php' => 'Edit the Season',
		// '/survey_steps.php' => 'Conduct the Survey',
		// '/dashboard.php' => 'Edit the Teams', 
		// '/change_sets.php' => 'Undo Changes',
		// '/admin.php' => 'Admin Dashboard',
		// '/schedule_steps.php' => 'Create Schedule',
		// '/survey_page_1.php' => 'Signups',
		// '/survey_page_2.php' => 'Preferences'
	// );
	// $labeled_crumbs = array();
	
	
	
	// if (0) deb ("headline.labelCrumbs(): crumbs_str =", $crumbs_str);
	// if ($crumbs_str) {
		// $crumbs_arr = explode(CRUMB_SEPARATOR, $crumbs_str);
	// } else {
		// $crumbs_arr = CRUMBS_ARR;
	// }
	// if (0) deb ("headline.labelCrumbs(): crumbs_str =", $crumbs_str);
	// if (0) deb ("headline.labelCrumbs(): CRUMBS_ARR =", CRUMBS_ARR);
	// // if ($crumbs_arr) {
		// // foreach($crumbs_arr as $i=>$crumb) {
	// if (CRUMBS_ARR) {
		// foreach(CRUMBS_ARR as $i=>$crumb) {
			// $crumb_url = explode("?", $crumb, 2)[0];
			// if (!$crumb_url) $crumb_url = $crumb;
			// if (0) deb ("headline.labelCrumbs(): crumb_url =", $crumb_url);
			// if ($crumb_labels[$crumb_url]) {
				// $labeled_crumb = array (
					// 'url' => $crumb,
					// 'label' => $crumb_labels[$crumb_url]
				// );
			// }
			// $labeled_crumbs[] = $labeled_crumb; 
		// }
	// }
	// if (0) deb ("headline.labelCrumbs(): labeled_crumbs =", $labeled_crumbs);
	// return $labeled_crumbs; 
// }


// CRUMBS FUNCTIONS - end ----------------------------------------------

///////////////////////////////////////// old versions of CRUMBS stuff:

// // The query string received by the current page
// define('QUERY_STRING', $_SERVER['QUERY_STRING']);
// if (0) deb("headline.php: QUERY_STRING = " . QUERY_STRING); 

// // The current crumbs (caller stack of the current page)
// $crumbs = $_REQUEST['breadcrumbs'];
// if (0) deb("headline.php: crumbs before removing own link = " . $crumbs); 
// $crumbs = str_replace(CRUMB_SEPARATOR . SCRIPT_URL, "", $_REQUEST['breadcrumbs']);  // Don't add "my" url to "my" crumbs
// if (0) deb("headline.php: crumbs after removing own link = ". $crumbs); 
// define('CRUMBS', $crumbs);
// // define('CRUMBS', $_REQUEST['breadcrumbs']);
// if (0) deb("headline.php: CRUMBS = " . CRUMBS); 

// // The crumbs to be shown on any page that the current page is calling 
// // $extra_queries = str_replace("breadcrumbs=" . CRUMBS . "&", "", QUERY_STRING);
// // if (0) deb("headline.php: extra_queries = " . $extra_queries); 

// // $my_crumb = ($extra_queries) ? SCRIPT_URL . "?" . $extra_queries : SCRIPT_URL;
// $my_crumb = SCRIPT_URL;
// if (!strpos(CRUMBS, $my_crumb)) {		// Add my crumb to CRUMBS to make NEXXT_CRUMBS
	// if (CRUMBS && $my_crumb) $separator = CRUMB_SEPARATOR;
	// define('NEXXT_CRUMBS', CRUMBS . $separator . $my_crumb);
// } else {																	// Unless it's already there
	// define('NEXXT_CRUMBS', $my_crumb);
// }
// if (0) deb("headline.php: NEXXT_CRUMBS = " . NEXXT_CRUMBS); 

// // The crumbs to be shown on any page to which the current page is returning 
// define('PREVIOUS_CRUMBS', removeCrumbs(CRUMBS));
// if (0) deb("headline.php: PREVIOUS_CRUMBS = " . PREVIOUS_CRUMBS); 

// ---------- constants for crumbs ---------

// define('HOME_LINK', 'Home,index.php'); 
// define('SIGNUPS_LINK', 'Manage&nbsp;Seasons,seasons.php'); 
// define('SURVEY_LINK', 'Manage&nbsp;Survey,survey_steps.php');  
// define('ASSIGNMENTS_LINK', 'Refine&nbsp;the&nbsp;Schedule,dashboard.php'); 
// define('CHANGE_SETS_LINK', 'Change&nbsp;Sets,change_sets.php');
// define('ADMIN_LINK', 'Admin&nbsp;Dashboard,admin.php');
// define('CREATE_SCHEDULE_LINK', 'Create&nbsp;the&nbsp;Schedule,schedule_steps.php');


// Older versions of crumb stuff (retired 6/25/19)

// define('CRUMB_SEPARATOR', ';');
// if (0) deb("headline.php: CRUMB_SEPARATOR = " . CRUMB_SEPARATOR);  
// define('CRUMB_SEPARATOR_2', ',');
// if (0) deb("headline.php: CRUMB_SEPARATOR_2 = " . CRUMB_SEPARATOR_2);

// // The url of the current page (including query string if any)
// if ($_SERVER['SCRIPT_URL'] == "/") 
	// define('SCRIPT_URL', $_SERVER['PHP_SELF']);
// else 
	// define('SCRIPT_URL', $_SERVER['SCRIPT_URL']);
// if (0) deb("headline.php: SCRIPT_URL = " . SCRIPT_URL); 

// define('CRUMMY', false); // Iff CRUMMY is true, crumbs will be shown.  This is to get on with other things while crumbs don't work
// if (CRUMMY) {

	// // The URL that accessed this page
	// if ($_SERVER['HTTP_FORWARDED_REQUEST_URI'] == "/") 
		// $my_crumb = $_SERVER['PHP_SELF'];
	// else 
		// $my_crumb = $_SERVER['HTTP_FORWARDED_REQUEST_URI'];
	// $my_crumb = str_replace(CRUMB_SEPARATOR, CRUMB_SEPARATOR_2, $my_crumb); 
	// define('MY_CRUMB', $my_crumb);
	// if (0) deb("headline.php: MY_CRUMB = " . MY_CRUMB); 

	// // The crumbs to be displayed on this page (previous calling sequence)
	// define('HTTP_FORWARDED_REQUEST_URI', $_SERVER['HTTP_FORWARDED_REQUEST_URI']);
	// define('QUERY_STRING', $_SERVER['QUERY_STRING']);
	// $extra_queries = str_replace("breadcrumbs=" . $crumbs . "&", "", QUERY_STRING);
	// if (0) deb("headline.php: extra_queries = " . $extra_queries);  
	// if (0) deb("headline.php: HTTP_FORWARDED_REQUEST_URI = " . HTTP_FORWARDED_REQUEST_URI);
	// // $crumbs = $_REQUEST['breadcrumbs'];
	// $crumbs = substr(HTTP_FORWARDED_REQUEST_URI, strpos(HTTP_FORWARDED_REQUEST_URI, "=")+1); 
	// // $crumbs = substr($_SERVER['QUERY_STRING'], strpos($_SERVER['QUERY_STRING'], "?breadcrumbs=")+1); 
	// // $crumbs = substr($_SERVER['QUERY_STRING'], strpos($_SERVER['QUERY_STRING'], "breadcrumbs=")+7); 
	// // $crumbs = $_SERVER['QUERY_STRING']; 
	// define('CRUMBS', $crumbs);
	// if (0) deb("headline.php: CRUMBS = " . CRUMBS); 
	// // $crumbs_arr = explode(CRUMB_SEPARATOR, CRUMBS);
	// // define ('CRUMBS_ARR', $crumbs_arr);
	// define ('CRUMBS_ARR', explode(CRUMB_SEPARATOR, CRUMBS));
	// if (0) deb("headline.php: CRUMBS_ARR = ", CRUMBS_ARR); 

	// // The crumbs to be shown on any page that the current page is calling 
	// if (CRUMBS && MY_CRUMB) $separator = CRUMB_SEPARATOR;
	// define('NEXXT_CRUMBS', CRUMBS . $separator . MY_CRUMB);
	// if (0) deb("headline.php: NEXXT_CRUMBS = " . NEXXT_CRUMBS);
	// // $next_crumbs_arr = explode(CRUMB_SEPARATOR, NEXXT_CRUMBS);
	// // if (0) deb("headline.php: next_crumbs_arr = ", $next_crumbs_arr); 
	// define('NEXXT_CRUMBS_ARR', explode(CRUMB_SEPARATOR, NEXXT_CRUMBS));
	// if (0) deb("headline.php: NEXXT_CRUMBS_ARR = ", NEXXT_CRUMBS_ARR); 

	// // The crumbs to be shown on any page to which the current page is returning 
	// define('PREVIOUS_CRUMBS', removeCrumbs(CRUMBS));
	// if (0) deb("headline.php: PREVIOUS_CRUMBS = " . PREVIOUS_CRUMBS); 
// } elseif (0) {
	// define('MY_CRUMB', "");
	// define('CRUMBS', "");
	// define('NEXXT_CRUMBS', "");
	// define('PREVIOUS_CRUMBS', "");
	// if (SCRIPT_URL != '/index.php') {
		// $crumbs_arr = array();
		// $crumbs_arr[] = '/index.php';
		// if (userIsAdmin()) $crumbs_arr[] = '/admin.php';
	// }
	// if ($_GET['edition']) $crumbs_arr = "";  // no breadcrumbs for printable presentation of schedule
	// define('CRUMBS_ARR', $crumbs_arr);
	// if (0) deb("headline.php: CRUMBS_ARR = ", CRUMBS_ARR); 
// } 

// Another attempt at crumbs, NOT using CRUMBS_TABLE

// // Render crumbs of calling path 
// if ($request_crumbs) {
	// $request_crumbs_arr = explode(";", $request_crumbs);
	// if ($request_crumbs_arr) {
		// foreach($request_crumbs_arr as $crumb_str) {
			// $crumb_arr = explode(",", $crumb_str);
			// $crumbs_display .= '&nbsp;&nbsp;<a href="'. $crumb_arr[0] . '?' . $crumb_arr[1] . '">' . $crumb_arr[2] . '</a>'; 
		// }
	// }
// }
// define("CRUMBS", $crumbs_display);
// if (0) deb("headline.php: request_crumbs: " . $request_crumbs);
// if (0) deb("headline.php: request_crumbs_arr: ", $request_crumbs_arr);
// if (0) deb("headline.php: crumbs_display: " . $crumbs_display);
// if (0) deb("headline.php: CRUMBS = " . CRUMBS); 
// if (0) deb("headline.php: crumbs_arr = " . $request_crumbs_arr); 


// // Construct my crumb, the one that leads back to this page (with its original query string)
// if ($request_crumbs) $query_string = "breadcrumbs=" . $request_crumbs;
// parse_str($request_query_string, $queries_arr);
// if (0) deb("headline.php: queries_arr before unset = ", $queries_arr);
// unset($queries_arr['breadcrumbs']);
// if (0) deb("headline.php: queries_arr after unset = ", $queries_arr); 
// if ($queries_arr) {
	// foreach($queries_arr as $key=>$query) {
		// if ($query_string) $query_string .= "&";
		// $query_string .= $key . "=" . $query;
	// }
// }
// if (0) deb("headline.php: query_string = ", $query_string); 
// // $my_crumb_uri = $script_url . "?" . $query_string;
// // if (0) deb("headline.php: my_crumb = ", $my_crumb_uri); 
// $crumb_label = sqlSelect("crumb_label", PAGES_TABLE, "url = '" . $script_url . "'", "", (0))[0]['crumb_label'];

// // Append my_crumb to crumbs to form NEXXT_CRUMBS, which will be passed in forward links
// $my_crumb = $script_url . "?" . $query_string . "," . $crumb_label;
// if ($request_crumbs && $my_crumb) $separator = ";";  
// $next_crumbs = $request_crumbs . $separator . $my_crumb;
// define("NEXXT_CRUMBS", $next_crumbs);  

// Yet another attempt, NOT using CRUMBS_TABLE

// $my_crumb = sqlSelect("*", PAGES_TABLE, "url = '" . $script_url . "'", "", (0))[0];
// if (0) deb("headline.php: my_crumb = ", $my_crumb); 
// if (0) deb("headline.php: my_crumb['id'] = ", $my_crumb['id']); 
// if ($crumb_ids) $separator = ",";  
// $next_crumb_ids = $crumb_ids . $separator . $my_crumb['id'];
// $next_crumbs_arr = $request_crumbs_arr;
// $next_crumbs_arr[] = $my_crumb;
// define("NEXXT_CRUMBS", $next_crumb_ids);  
// if (0) deb("headline.php: next_crumb_ids = ", $next_crumb_ids);  
// if (0) deb("headline.php: next_crumbs_arr = ", $next_crumbs_arr);
// if (0) deb("headline.php: NEXXT_CRUMBS = ", $NEXXT_CRUMBS);

// // Get crumbs of calling path
// $select = "p.url, c.query_string, p.crumb_label";
// $from = CRUMBS_TABLE . " as c, " . PAGES_TABLE . " as p";
// $request_crumbs_arr = sqlSelect($select, $from, "", "c.id asc", (0)); 
// if ($request_crumbs_arr) {
	// foreach($request_crumbs_arr as $i=>$crumb) {
		// $crumbs_display .= '&nbsp;&nbsp;<a  href="'. $crumb['url'] . '">' . $crumb['crumb_label'] . '</a>'; 
	// }
// }


?>