<?php
ob_start();

$menu__area="options";
$title="change password";
include ("header.php");

	if (isset($_REQUEST['submit']) && $_REQUEST['submit']) {


		if (isset($_REQUEST['passold'])) $passold=$_REQUEST['passold']; else $passold="";
		if (isset($_REQUEST['password'])) $password=$_REQUEST['password']; else $password="";
		if (isset($_REQUEST['password2'])) $password2=$_REQUEST['password2']; else $password2="";

		// password tests
		$continue=true;
	
		if (!$passold || !$password || !$password2) { 
	    		message ($lang['error_please_fill_in_all_fields']);
			$continue=false;
			}
	
                if ($password!=$password2) {
                	message ($lang['error_password_repetition_does_not_match']);
			$continue=false;
                	}

		$passoldcrypt=unix_crypt($passold);
        	if ($passoldcrypt!=$expadmindata['password']) {
        		message ($lang['error_old_password_wrong']);
			$continue=false;
			}

		if ($password==$expadmindata['adminname']) {
	   		message($lang['error_do_not_use_username_as password']);
			$continue=false;
			}


		if ($continue==false) {
			message ($lang['error_password_not_changed']);
			redirect ("admin/admin_pw.php");
			}
		   else {
	       		admin__set_password(unix_crypt($password),$expadmindata['admin_id']);
			message ($lang['password_changed_log_in_again']);
			log__admin("admin_password_change",$expadmindata['adminname']);
			log__admin("logout");
			admin__logout();
			redirect("admin/admin_login.php?pw=true");
			}

		}

	echo '	<center><BR><BR>
			<h4>'.$lang['change_my_password'].'</h4>';
	show_message();

	echo '
		<form action="admin_pw.php" method=POST>
		<table border=0>
		<tr>
			<td>
				'.$lang['old_password'].':
			</td>
			<td>
				<input type=password name=passold length=20>
			</td>
		</tr>
		<tr>
			<td>
				'.$lang['new_password'].':
			</td>
			<td>
				<input type=password name=password length=20>
			</td>
		</tr>
		<tr>
			<td>
				'.$lang['repeat_new_password'].':
			</td>
			<td>
				<input type=password name=password2 length=20>
			</td>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type=submit name=submit value="'.$lang['change'].'">
			</td>
		</tr>
		</table>
		</form>

		</center>';

include ("footer.php");

?>
