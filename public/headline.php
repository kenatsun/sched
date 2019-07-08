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

// Get the needed data from the request
$request_session_id = $_REQUEST['PHPSESSID'];
$request_crumbs_ids = $_REQUEST['breadcrumbs'];
$my_url = $_SERVER['SCRIPT_URL']; 
$request_query_string = $_SERVER['QUERY_STRING']; 

// Normalize the root url
if ($my_url == "/") $my_url = "/index.php";
if (0) deb("headline.php: my_url = " . $my_url);  

// Purge obsolete breadcrumbs from CRUMBS_TABLE whenever returning to root
if (!$request_crumbs_ids) sqlDelete(CRUMBS_TABLE, "session_id = '" . $request_session_id . "'", (0));

// Render crumbs of calling path, creating the CRUMBS_QUERY constant
if ($request_crumbs_ids) {
	$request_crumbs_arr = sqlSelect("*", CRUMBS_TABLE, "id in (" . $request_crumbs_ids . ")", "when_created asc, id asc", (0)); 
	// If this page was called from itself, remove it from crumbs
	if ($request_crumbs_arr) $last_query_crumb_url = end($request_crumbs_arr)['url'];
	end($request_crumbs_arr);
	$last_query_crumb_index = key($request_crumbs_arr);
	if ($my_url == $last_query_crumb_url) unset($request_crumbs_arr[$last_query_crumb_index]);
	if (0) deb("headline.php: my_url = " . $my_url); 
	if (0) deb("headline.php: last_query_crumb_url = " . $last_query_crumb_url); 
	foreach($request_crumbs_arr as $i=>$crumb) {
		$crumbs_query .= '&nbsp;&nbsp;<a href="'. $crumb['url'] . '?' . $crumb['query_string'] . '">' . $crumb['crumb_label'] . '</a>'; 
		$my_crumbs_ids .= $my_crumbs_ids ? "," . $crumb['id'] : $crumb['id'];
	}
}
if (0) deb("headline.php: crumb_ids = " . $request_crumbs_ids);
if (0) deb("headline.php: crumbs_display = " . $crumbs_query);
if (0) deb("headline.php: request_crumbs_arr = ", $request_crumbs_arr); 

// Construct my crumb, the one that leads back to this page (with its original query string) - CRUMBS_TABLE version
if ($request_crumbs_ids) $query_string = "breadcrumbs=" . $request_crumbs_ids;
parse_str($request_query_string, $queries_arr);
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
$crumb_label = sqlSelect("crumb_label", PAGES_TABLE, "url = '" . $my_url . "'", "", (0))[0]['crumb_label'];
$columns = "session_id, url, query_string, crumb_label, when_created";
$values = "'" . $_REQUEST['PHPSESSID'] . "', '" . $my_url . "', '" . $query_string . "', '" . $crumb_label . "', '" . date("Y-m-d H:i:s") . "'";
sqlInsert(CRUMBS_TABLE, $columns, $values, (0));  
 
// Append my_crumb_id to crumb_ids to form NEXT_CRUMBS_IDS, which will be passed in forward links
// unless it duplicates the last
$my_crumb = sqlSelect("max(id) as id, url", CRUMBS_TABLE, "", "", (0))[0];
$my_crumb_id = $my_crumb['id'];
$my_crumb_url = $my_crumb['url'];
if ($request_crumbs_ids) $last_crumb_url = end($request_crumbs_arr)['url'];
if (0) deb("headline.php: my_crumb_url = " . $my_crumb_url); 
if (0) deb("headline.php: last_crumb_url = " . $last_crumb_url); 
if ($last_crumb_url == $my_crumb_url) {
	$next_crumbs_ids = $request_crumbs_ids;		
} else {
	if ($request_crumbs_ids && $my_crumb_id) $separator = ",";  
	$next_crumbs_ids = $request_crumbs_ids . $separator . $my_crumb_id;	
}

// Define global constants for this session
define("PREVIOUS_CRUMBS_IDS", $_REQUEST['breadcrumbs']);  
define("CRUMBS_IDS", $my_crumbs_ids);
define("CRUMBS_QUERY", $crumbs_query);
define("NEXT_CRUMBS_IDS", $next_crumbs_ids);  
if (0) deb("headline.php: NEXT_CRUMBS_IDS = ", NEXT_CRUMBS_IDS); 

//////////////////////////////////////////////// FUNCTIONS 

/*
Print a headline for a page
*/
function renderHeadline($text, $crumbs_str="", $subhead="", $show_admin_link=1) {
	if (0) deb ("headline.renderHeadline(): text =", $text);
	if (0) deb ("headline.renderHeadline(): crumbs_str = '" . $crumbs_str . "'");
	if (0) deb ("headline.renderHeadline(): labeled_crumbs =", $labeled_crumbs);

	$community_logo = (COMMUNITY == "Sunward" ? '/display/images/sunward_logo.png' : '/display/images/great_oak_logo.png');
	$td_style = 'background-color:white;';

	// Render instance notice (for development instance only)
	$instance = INSTANCE;
	$database = DATABASE;
	$color = '"color:red"';
	$instance_notice = $instance && !$_GET['printable'] == 1 ? "<p style={$color}><strong>You're looking at the {$instance} instance.  Database is {$database}.</strong></p>" : "";
	if ($instance_notice && !userIsAdmin()) $instance_notice .= "<br>";

	// Render admin notice (shown iff user is admin)
	if (userIsAdmin() && !$_GET['printable'] == 1) $admin_notice = 
		'
		<div style=' . $color . '><p>
		<form method="post" action="' . makeURI($_SERVER['PHP_SELF'], CRUMBS_IDS) . '" name="guest_form"> 
			<input type="hidden" name="sign_in_as_guest" value="1"> 
			<p><strong>You are signed into this session as an admin. </strong>
			<input type="submit" name="sign_in_as_user" value="Sign in as a plain old ordinary user.">
		</form>
		</p><br></div>';
	if (0) deb ("headline.renderHeadline(): NEXT_CRUMBS_IDS = " . NEXT_CRUMBS_IDS);
	if (userIsAdmin() && $show_admin_link) $admin_link =  
		'<div style=' . $color . '>
			<a style=' . $color . ' href="'. makeURI("/admin.php", NEXT_CRUMBS_IDS) . '"><strong>Open the Admin Dashboard</strong></a>
		</div>
		<br>';

	// Render breadcrumbs display
	if (CRUMBS_QUERY) {
		$crumbs_display = '
			<tr style="font-size:10pt; font-style:italic">
				<td colspan="2" style="text-align:right; ' . $td_style . '"><<<<< &nbsp;&nbsp;go back to:' . CRUMBS_QUERY . '</td>
			</tr>';
	}
	if (0) deb ("headline.renderHeadline(): crumbs_display =", $crumbs_display);

	// Render headline
	if ($subhead) {
		$headline = '<td style="$td_style" class="headline">' . $text;
		$headline .= '<br><span style="font-size:18px">' . $subhead . '</span>';
	} 
	else {
		$headline = '<td style="$td_style" class="headline">' . $text . '</td>';
	}

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


?>