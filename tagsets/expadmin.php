<?php
// orsee admin functions. part of orsee. see orsee.org

// login form
function admin__login_form() {
	global $lang;
	echo '<form name="login" action="admin_login.php" method=post>
		<INPUT type=hidden name=redirect value="';
		if ($_REQUEST['redirect']) echo $_REQUEST['redirect'];
			else echo 'admin/index.php';
	echo '">
		'.$lang['username'].':
		<input type=integer size=20 maxlength=20 name=adminname onChange="gotoPassword()"><BR>
		'.$lang['password'].':
		<input type=password size=20 maxlength=20 name=password onChange="sendForm()"><BR>
		<input type=submit name=login value="'.$lang['login'].'">
		</form>';
}

// checks username and password
// if ok, redirect
function admin__check_login($username,$password) {
	global $lang;
	$query="SELECT * FROM ".table('admin')." 
      		WHERE adminname='".$username."'
		AND password='".$password."'";
	$result=mysql_query($query) or die("Database error: " . mysql_error());
	$num_rows = mysql_num_rows($result);

	if ($num_rows==1) {
		$expadmindata=mysql_fetch_assoc($result);
		// load admin rights
		$expadmindata['rights']=admin__load_admin_rights($expadmindata['admin_type']);
		if ($expadmindata['rights']['login']) {
			$_SESSION['expadmindata']=$expadmindata;
			return true;
			} else {
			message($lang['error_not_allowed_to_login']);
			return false;
			}
		}
	   else {
		return false;
		}
}

function admin__load_admin_rights($admin_type) {
	$admin_type=orsee_db_load_array("admin_types",$admin_type,"type_name");
	$trights=explode(",",$admin_type['rights']);
	$rights=array();
	foreach ($trights as $right) $rights[$right]=true;
	return $rights;
}

function check_allow($right,$redirect="") {
	global $expadmindata, $lang;
	if ($expadmindata['rights'][$right]) return true;
	   else {
		if ($redirect) {
			message ($lang['error_not_authorized_to_access_this_function']);
			redirect("admin/".$redirect);
			}
		return false;
		}
}


function admin__logout() {
	global $expadmindata;
	$expadmindata=array();
	$SESSION['expadmindata']=$expadmindata;
	session_destroy();
}


// Updating password for admin
function admin__set_password($password,$userid) {
	$query="UPDATE ".table('admin')." 
         	SET password='$password'
         	WHERE admin_id='$userid'";
	$done=mysql_query($query) or die("Database error: " . mysql_error());
}

// admin type selection list
function admin__select_admin_type($fieldname,$selected="") {
	$query="SELECT * from ".table('admin_types')."
		ORDER by type_name";
	$result=mysql_query($query) or die("Database error: " . mysql_error());
	echo '<SELECT name="'.$fieldname.'">';
	while ($line=mysql_fetch_assoc($result)) {
		echo '<OPTION value="'.$line['type_name'].'"';
		if ($line['type_name']==$selected) echo ' SELECTED';
		echo '>'.$line['type_name'].'</OPTION>';
		}
	echo '</SELECT>';
}

?>
