<?php


//////////////////////////////////////////////// FUNCTIONS 

/*
Print a headline for a page
*/
function renderHeadline($text, $crumbs_str="", $subhead="", $show_admin_link=1) {
	if (0) deb ("headline.renderHeadline(): text =", $text);
	if (0) deb ("headline.renderHeadline(): crumbs_str = '" . $crumbs_str . "'");
	if (0) deb ("headline.renderHeadline(): labeled_crumbs =", $labeled_crumbs);

	$community_logo = (COMMUNITY == "Sunward" ? '/display/images/sunward_logo.png' : '/display/images/great_oak_logo.png');
	$new_status .= userIsAdmin() ? "guest" : "admin";
	if (0) deb ("headline.renderHeadline(): REQUEST_QUERY_STRING = " . REQUEST_QUERY_STRING);
	$community_logo_link = '
		<form name="community_logo_link" id="community_logo_link" action="' . makeURI(SCRIPT_URL, "", REQUEST_QUERY_STRING) . '" method="post">
			<input type="image" name="submit" src="' . $community_logo . '"  >
			<input type="hidden" id="sign_in_as" name="sign_in_as" value="' . $new_status . '"  >
		</form>';
	
	$td_style = 'background-color:white;'; 

	// Render admin notice (shown iff user is admin)
	$color = '"color:red"'; 
	if (userIsAdmin() && !$_GET['printable'] == 1) $admin_notice =  
		'
		<div style=' . $color . '><p>
		<p><strong>You are signed into this session as an admin.&nbsp;&nbsp;Click the ' . COMMUNITY . ' icon to sign in as a plain old user.</strong>
		</p></div>';
	if (0) deb ("headline.renderHeadline(): NEXT_CRUMBS_IDS = " . NEXT_CRUMBS_IDS);
	if (userIsAdmin() && $show_admin_link) $admin_link =  
		'<div style=' . $color . '>
			<a style=' . $color . ' href="'. makeURI("/admin.php", NEXT_CRUMBS_IDS) . '"><strong>Open the Admin Dashboard</strong></a>
		</div>
		<br>';

	// Render instance notice (for development instance only)
	$instance = INSTANCE;
	$database = DATABASE;
	$instance_notice = $instance && !$_GET['printable'] == 1 ? "<p style={$color}><strong>You're looking at the {$instance} instance.  Database is {$database}.</strong></p><br>" : "";

	// if ($instance_notice && !userIsAdmin()) $instance_notice .= "<br>";

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
		
	// return 
	// $instance_notice .
	// $admin_notice .
	// $admin_link .
	// '<table>' .
		// $crumbs_display .
		// '<tr>
			// <td style="' . $td_style . '"><a href="' . makeURI(SCRIPT_URL, "", REQUEST_QUERY_STRING) . '"><img src=' . $community_logo . '></a></td>' .
			// $headline .	 
		// '</tr>
	// </table>
	// ';
	return 
	$admin_notice .
	$instance_notice .
	$admin_link .
	'<table>' .
		$crumbs_display .
		'<tr>
			<td style="' . $td_style . '">' . $community_logo_link . '</td>' . $headline .	 
		'</tr>
	</table>
	';
}


?>