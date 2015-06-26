<?php
// part of orsee. see orsee.org

// login form
function admin__login_form() {
	global $lang;
	echo '<form name="login" action="admin_login.php" method=post>
		'.lang('username').':
		<input type=text size=20 maxlength=20 name=adminname onChange="gotoPassword()"><BR>
		'.lang('password').':
		<input type=password size=20 maxlength=20 name=password onChange="sendForm()"><BR>';
	if (isset($_REQUEST['requested_url']) && $_REQUEST['requested_url'])
		echo '<input type=hidden name="requested_url" value="'.$_REQUEST['requested_url'].'">';
	echo '<input class="button" type=submit name=login value="'.lang('login').'">
		</form>';
}

// checks username and password
// if ok, redirect
function admin__check_login($username,$password) {
	global $lang;
	$pars=array(':adminname'=>$username);
	$query="SELECT * FROM ".table('admin')." 
            WHERE adminname= :adminname";
	$admin=orsee_query($query,$pars);
	
	$continue=true;
	$not_allowed=false; $locked=false;
	if ($continue) {
		if (!isset($admin['admin_id'])) {
			$continue=false;
			log__admin('login_admin_wrong_username','used_username:'.$username);
			//message('id');
		}
	}

	if ($continue) {
		$admin=admin__check_has_lockout($admin);
		if ($admin['locked']) {
			$continue=false;
			log__admin('login_admin_locked_out','username:'.$username);
			$locked=admin__track_unsuccessful_login($admin);
			//message('locked');
		}
	}
	
	if ($continue) {
		$check_pw=crypt_verify($password,$admin['password_crypt']);
		if (!$check_pw) {
			$continue=false;
			log__admin('login_admin_wrong_password','username:'.$username);
			$locked=admin__track_unsuccessful_login($admin);
			//message('wrong_pw');
		}
	}

	if ($continue) {
		$expadmindata=$admin;
		// load admin rights
		$expadmindata['rights']=admin__load_admin_rights($expadmindata['admin_type']);
		if ((!$expadmindata['rights']['login']) || $expadmindata['disabled']=='y') {
			$continue=false;
			$not_allowed=true;
			//message('not_allowed');
		}
	}
	
	if ($continue) {
		$_SESSION['expadmindata']=$expadmindata;
		$done=admin__track_successful_login($admin);
		return true;
	} else {
		//if ($locked) message(lang('error_locked_out'));
		if ($not_allowed) message(lang('error_not_allowed_to_login'));
		return false;
	}
}

function admin__check_has_lockout($admin) {
	global $settings;
	if (isset($settings['lockout_period_minutes_after_failed_logins']) && $settings['lockout_period_minutes_after_failed_logins']>0)
		$lockout_minutes=$settings['lockout_period_minutes_after_failed_logins'];
	else $lockout_minutes=20;
	if ($admin['locked'] && ($admin['last_login_attempt'] + ($lockout_minutes*60)) < time()) {
		// unlock
		$admin['failed_login_attempts']=0;
		$admin['locked']=0;
	}
	return $admin;
}


function admin__track_unsuccessful_login($admin) {
	global $settings;
	if (isset($settings['max_number_of_failed_logins_before_lockout']) && $settings['max_number_of_failed_logins_before_lockout']>0)
		$limit=$settings['max_number_of_failed_logins_before_lockout'];
	else $limit=3;
	if (isset($settings['lockout_period_minutes_after_failed_logins']) && $settings['lockout_period_minutes_after_failed_logins']>0)
	$lockout_minutes=$settings['lockout_period_minutes_after_failed_logins'];
	else $lockout_minutes=20;
	
	$last_login_attempt=time(); 
	$failed_login_attempts=$admin['failed_login_attempts']+1;
	if ($failed_login_attempts>=$limit) {
		$locked=1;
	} else {
		$locked=0;
	}
	$pars=array(':admin_id'=>$admin['admin_id'],
				':last_login_attempt'=>$last_login_attempt,
				':failed_login_attempts'=>$failed_login_attempts,
				':locked'=>$locked,
				);
	$query="UPDATE ".table('admin')."
			SET last_login_attempt = :last_login_attempt,
			failed_login_attempts = :failed_login_attempts,
			locked = :locked
            WHERE admin_id= :admin_id";
    $done=or_query($query,$pars);
    return $locked;
}

function admin__track_successful_login($admin) {
	$pars=array(':admin_id'=>$admin['admin_id'],
				':last_login_attempt'=>time(),
				':failed_login_attempts'=>0,
				':locked'=>0,
				);
	$query="UPDATE ".table('admin')."
			SET last_login_attempt = :last_login_attempt,
			failed_login_attempts = :failed_login_attempts,
			locked = :locked
            WHERE admin_id= :admin_id";
    $done=or_query($query,$pars);
    return $done;
}



function admin__load_admin_rights($admin_type) {
	$admin_type=orsee_db_load_array("admin_types",$admin_type,"type_name");
	$trights=explode(",",$admin_type['rights']);
	$rights=array();
	foreach ($trights as $right) $rights[$right]=true;
	return $rights;
}

function check_allow($right,$redirect="") {
	global $expadmindata, $lang, $proceed;
	if (isset($expadmindata['rights'][$right]) && $expadmindata['rights'][$right]) return true;
	else {
		if ($redirect) {
			message (lang('error_not_authorized_to_access_this_function'));
			redirect("admin/".$redirect);
			$proceed=false;
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
	$pars=array(':admin_id'=>$userid,
				':password'=>unix_crypt($password));
	$query="UPDATE ".table('admin')." 
         	SET password_crypt= :password,
         	pw_update_requested = 0 
         	WHERE admin_id= :admin_id";
	$done=or_query($query,$pars);
}

// admin type selection list
function admin__select_admin_type($fieldname,$selected="",$return_var="type_name",$hide=array()) {
	global $settings, $preloaded_admintypes;
	$out='';
	if (!isset($preloaded_admintypes) || !is_array($preloaded_admintypes)) {
		$preloaded_admintypes=array();
		$query="SELECT * from ".table('admin_types')."
				ORDER by type_name";
		$result=or_query($query);
		while ($line=pdo_fetch_assoc($result)) {
			$preloaded_admintypes[$line['type_name']]=$line;
		}
	}
	if (!isset($preloaded_admintypes[$selected])) $selected=$settings['default_admin_type'];
	$out.='<SELECT name="'.$fieldname.'">';
	foreach ($preloaded_admintypes as $line) {
		if(!in_array($line['type_id'],$hide)) {
			$out.='<OPTION value="'.$line[$return_var].'"';
			if ($line[$return_var]==$selected || $line['type_name']==$selected) $out.=' SELECTED';
			$out.='>'.$line['type_name'].'</OPTION>';
		}
	}
	$out.='</SELECT>';
	return $out;
}

?>
