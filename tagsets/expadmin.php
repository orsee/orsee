<?php
// part of orsee. see orsee.org

// login form
function admin__login_form() {
	global $lang;
	echo '<form name="login" action="admin_login.php" method=post>
		<INPUT type=hidden name=redirect value="';
		if (isset($_REQUEST['redirect']) && $_REQUEST['redirect']) echo $_REQUEST['redirect'];
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
                WHERE adminname='".mysqli_real_escape_string($GLOBALS['mysqli'],$username)."'
                AND password='".mysqli_real_escape_string($GLOBALS['mysqli'],$password)."'";
        $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
        $num_rows = mysqli_num_rows($result);

	if ($num_rows==1) {
		$expadmindata=mysqli_fetch_assoc($result);
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
	$rights=update_admin_rights_with_new_fields($rights);
	return $rights;
}

function update_admin_rights_with_new_fields($rights) {
	global $default_new_rights;
	foreach($default_new_rights as $m) {
		if(isset($rights[$m['old']]) && $rights[$m['old']] && (!isset($rights[$m['new']]))) $rights[$m['new']]=true;
	}
	return $rights;
}

function check_allow($right,$redirect="") {
	global $expadmindata, $lang;
	if (isset($expadmindata['rights'][$right]) && $expadmindata['rights'][$right]) return true;
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
         	SET password='".mysqli_real_escape_string($GLOBALS['mysqli'],$password)."'
         	WHERE admin_id='".$userid."'";
	$done=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
}

// admin type selection list
function admin__select_admin_type($fieldname,$selected="") {
	$query="SELECT * from ".table('admin_types')."
		ORDER by type_name";
	$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
	echo '<SELECT name="'.$fieldname.'">';
	while ($line=mysqli_fetch_assoc($result)) {
		echo '<OPTION value="'.$line['type_name'].'"';
		if ($line['type_name']==$selected) echo ' SELECTED';
		echo '>'.$line['type_name'].'</OPTION>';
		}
	echo '</SELECT>';
}

?>
