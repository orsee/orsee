<?php
ob_start();
$title="edit admin";
$menu__area="options";

include("header.php");

	if ($_REQUEST['admin_id']) $admin_id=$_REQUEST['admin_id'];
		elseif ($_REQUEST['new']) $admin_id="";
		else $admin_id=$expadmindata['admin_id'];

	if ($admin_id) $admin=orsee_db_load_array("admin",$admin_id,"admin_id");

	if ((!$admin_id) ||  $admin_id!=$expadmindata['admin_id']) 
		$allow=check_allow('admin_edit','admin_show.php');

	if ($_REQUEST['edit']) {

		$continue=true;

		if (!check_allow('admin_edit')) {
                        unset($_REQUEST['admin_type']);
                        unset($_REQUEST['experimenter_list']);
                        unset($_REQUEST['password']);
			unset($_REQUEST['password2']);
			unset($_REQUEST['adminname']);
                        }

  		if (isset($_REQUEST['adminname']) && !$_REQUEST['adminname']) {
  			message($lang['you_have_to_give_a_username']);
			$continue=false;
			}
		if (!$_REQUEST['fname']) {
                        message($lang['you_have_to_fname']);
                        $continue=false;
                        }
		if (!$_REQUEST['lname']) {
                        message($lang['you_have_to_lname']);
                        $continue=false;
                        }

		if (!$_REQUEST['email']) {
                        message($lang['you_have_to_give_email_address']);
                        $continue=false;
                        }

  		if ( $_REQUEST['password'] && (! $_REQUEST['password']==$_REQUEST['password2'])) {
  			message($lang['you_have_to_give_a_password']);
			$continue=false;
			$_REQUEST['password']="";
			$_REQUEST['password']="";
			}


		if ($continue) {
			if ($_REQUEST['password']) {
				$_REQUEST['password']=unix_crypt($_REQUEST['password']);
				message($lang['password_changed']);
				}
			else unset($_REQUEST['password']);

			$done=orsee_db_save_array($_REQUEST,"admin",$admin_id,"admin_id");
			message($lang['changes_saved']);
			log__admin("admin_edit",$_REQUEST['adminname']);
			if ($admin_id==$expadmindata['admin_id']) $nl="&new_language=".$_REQUEST['language']; else $nl="";
			redirect ("admin/admin_edit.php?admin_id=".$admin_id.$nl);

			}

		$admin=$_REQUEST;

		} 
 


	echo '  <center><BR><BR>
                <h4>'.$lang['edit_profile_for'].' ';
			if ($admin_id) echo $admin['adminname'];
				else echo $lang['new_administrator'];
		echo '</h4>
                ';

	show_message();

	echo '
   		<form action="admin_edit.php" method=post>

		<input type=hidden name="admin_id" value="';
		if ($admin_id) echo $admin_id; else echo time();
		echo '">

		<table border=0 width=80%>

			<tr>
                        	<td align=right>
					'.$lang['username'].':
				</td>
				<td>&nbsp;&nbsp;</td>
                        	<td>';
					if (check_allow('admin_edit')) 
                                		echo '<input name="adminname" type=text size=20 maxlength=40 value="'.
							$admin['adminname'].'">';
					else echo $admin['adminname'];
					echo '
				</td>
			</tr>

			<tr>
				<td align=right>
					'.$lang['firstname'].':
				</td>
				<td>&nbsp;&nbsp;</td>
				<td>
					<input name="fname" type=text size=20 maxlength=50 value="'.$admin['fname'].'">
				</td>
			</tr>

			<tr>
				<td align=right>
					'.$lang['lastname'].':
				</td>
				<td>&nbsp;&nbsp;</td>
				<td>
					<input name="lname" type=text size=20 maxlength=50 value="'.$admin['lname'].'">
				</td>
			</tr>

			<tr>
                                <td align=right>
                                        '.$lang['email'].':
                                </td>
				<td>&nbsp;&nbsp;</td>
                                <td>
                                        <input name="email" type=text size=40 maxlength=200 value="'.$admin['email'].'">
                                </td>
                        </tr>';
		if (check_allow('admin_edit')) {
		   echo '
			<tr>
                                <td align=right>
                                        '.$lang['type'].':
                                </td>
                                <td>&nbsp;&nbsp;</td>
                                <td>';
					if ($admin['admin_type']) $selected=$admin['admin_type']; 
						else $selected=$settings['default_admin_type'];
					admin__select_admin_type("admin_type",$selected);
                        echo '	</td>
                        </tr>
			';
			}

		echo '	<tr>
                                <td align=right>
                                        '.$lang['language'].':
                                </td>
                                <td>&nbsp;&nbsp;</td>
                                <td>';
        				$langs=get_languages();
                			$lang_names=lang__get_language_names();
                			if ($admin['language']) $clang=$admin['language'];
                        			else $clang=$settings['admin_standard_language'];
                        		echo '<SELECT name="language">';
                        		foreach ($langs as $language) {
                                		echo '<OPTION value="'.$language.'"';
                                		if ($language==$clang) echo ' SELECTED';
                                		echo '>'.$lang_names[$language].'</OPTION>
                                        		';
						}
					echo '</SELECT>';
                        echo '  </td>
                        </tr>';
		if (check_allow('admin_edit')) {
		   echo '
                        <tr>
                                <td align=right>
                                        '.$lang['is_experimenter'].':
                                </td>
				<td>&nbsp;&nbsp;</td>
                                <td>
                                        <input name="experimenter_list" type=radio value="y"';
						if ($admin['experimenter_list']!='n') echo ' CHECKED';
						echo '>'.$lang['yes'].'&nbsp;&nbsp;
					<input name="experimenter_list" type=radio value="n"';
                                                if ($admin['experimenter_list']=='n') echo ' CHECKED';
                                                echo '>'.$lang['no'].'&nbsp;&nbsp;'.help('experimenter_list').'
                                </td>
                        </tr>';
			}
		echo '
			<tr>
                                <td align=right>
                                        '.$lang['receives_periodical_calendar'].':
                                </td>
                                <td>&nbsp;&nbsp;</td>
                                <td>
                                        <input name="get_calendar_mail" type=radio value="y"';
                                                if ($admin['get_calendar_mail']!='n') echo ' CHECKED';
                                                echo '>'.$lang['yes'].'&nbsp;&nbsp;
                                        <input name="get_calendar_mail" type=radio value="n"';
                                                if ($admin['get_calendar_mail']=='n') echo ' CHECKED';
                                                echo '>'.$lang['no'].'&nbsp;&nbsp;
                                </td>
                        </tr>

			<tr>
                                <td align=right>
                                        '.$lang['receives_periodical_participant_statistics'].':
                                </td>
                                <td>&nbsp;&nbsp;</td>
                                <td>
                                        <input name="get_statistics_mail" type=radio value="y"';
                                                if ($admin['get_statistics_mail']!='n') echo ' CHECKED';
                                                echo '>'.$lang['yes'].'&nbsp;&nbsp;
                                        <input name="get_statistics_mail" type=radio value="n"';
                                                if ($admin['get_statistics_mail']=='n') echo ' CHECKED';
                                                echo '>'.$lang['no'].'&nbsp;&nbsp;
                                </td>
                        </tr>';

		if (check_allow('admin_edit')) {
		   echo '
			<tr>
				<td align=right>
					'.$lang['new_password'].':
				</td>
				<td>&nbsp;&nbsp;</td>
				<td>
					<input name=password type=password size=10 maxlength=20 value="">
				</td>
			</tr>

			<tr>
				<td align=right>
					'.$lang['repeat_new_password'].':
				</td>
				<td>&nbsp;&nbsp;</td>
				<td>
					<input name="password2" type=password size=10 maxlength=20 value="">
				</td>
			</tr>';
			}
		echo '
			<tr>
				<td colspan=3 align=center>
					<input name=edit type=submit value="';
					if ($admin_id) echo $lang['change']; else echo $lang['add'];
					echo '">
				</td>
			</tr>
		</table>
   		</form>

   		<BR>';

	if ($admin_id) {
	    if (check_allow('admin_delete')) {
		echo '	<form action="admin_delete.php">
			<input type=hidden name="admin_id" value="'.$admin_id.'">
			<table border=0 width=80%>
			<tr>
				<td align=right>
					<input type=submit name=submit value="'.$lang['delete'].'">
				<td>
			</tr>
			</table>
			</form>';
		}
	    }


	if ($adminright['admin_edit']) {
		echo '<BR><A href="admin_show.php">'.$lang['edit_administrators'].'</A><BR><BR>';
		}

	echo '</center>';


include ("footer.php");

?>
