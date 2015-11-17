<?php
// part of orsee. see orsee.org
ob_start();
$title="user_management";
$menu__area="options";
include("header.php");
if ($proceed) {

    if (isset($_REQUEST['admin_id']) && $_REQUEST['admin_id']) $admin_id=$_REQUEST['admin_id'];
        elseif (isset($_REQUEST['new']) && $_REQUEST['new']) $admin_id="";
        else $admin_id=$expadmindata['admin_id'];

    if ($admin_id) $admin=orsee_db_load_array("admin",$admin_id,"admin_id"); else {
        $admin=array('adminname'=>'','fname'=>'','lname'=>'','email'=>'','admin_type'=>'','language'=>'',
                'experimenter_list'=>'','get_calendar_mail'=>'','get_statistics_mail'=>'','disabled'=>'n',
                'locked'=>0,'last_login_attempt'=>0,'failed_login_attempts'=>0,'pw_update_requested'=>0);
    }

    if ((!$admin_id) ||  $admin_id!=$expadmindata['admin_id'])
        $allow=check_allow('admin_edit','admin_show.php');

    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {

        $continue=true;

        if (!check_allow('admin_edit')) {
            unset($_REQUEST['admin_type']);
            unset($_REQUEST['experimenter_list']);
            unset($_REQUEST['password']);
            unset($_REQUEST['password2']);
            unset($_REQUEST['adminname']);
        }

        if (isset($_REQUEST['adminname']) && !$_REQUEST['adminname']) {
            message(lang('you_have_to_give_a_username'));
            $continue=false;
        }
        if (!$_REQUEST['fname']) {
            message(lang('you_have_to_fname'));
            $continue=false;
        }
        if (!$_REQUEST['lname']) {
            message(lang('you_have_to_lname'));
            $continue=false;
        }

        if (!$_REQUEST['email']) {
            message(lang('you_have_to_give_email_address'));
            $continue=false;
        }

        if ( !$admin_id && !$_REQUEST['password'] ) {
            message(lang('you_have_to_give_a_password'));
            $continue=false;
            $_REQUEST['password']="";
            $_REQUEST['password2']="";
        }


        if ( $_REQUEST['password'] && (! $_REQUEST['password']==$_REQUEST['password2'])) {
            message(lang('you_have_to_give_a_password'));
            $continue=false;
            $_REQUEST['password']="";
            $_REQUEST['password2']="";
        }

        if ($continue && isset($_REQUEST['adminname'])) {
            $_REQUEST['adminname']=trim($_REQUEST['adminname']);
            $pars=array(':adminname'=>$_REQUEST['adminname']);
            $query="SELECT admin_id FROM ".table('admin')."
                    WHERE adminname = :adminname";
            $existing_admin=orsee_query($query,$pars);
            if (isset($existing_admin['admin_id']) && $existing_admin['admin_id']!=$admin_id) {
                $continue=false;
                message(lang('error_username_exists'));
            }
        }

        if ($continue) {
            if ($_REQUEST['password']) {
                // no password strength checks when account created by super-admin?
                $_REQUEST['password_crypt']=unix_crypt($_REQUEST['password']);
                message(lang('password_changed'));
            } else unset($_REQUEST['password']);

            if (!$admin_id) $admin_id=time();
            foreach (array('fname','lname') as $k) $_REQUEST[$k]=trim($_REQUEST[$k]);
            $done=orsee_db_save_array($_REQUEST,"admin",$admin_id,"admin_id");
            message(lang('changes_saved'));
            log__admin("admin_edit",$_REQUEST['adminname']);
            if ($admin_id==$expadmindata['admin_id']) $nl="&new_language=".$_REQUEST['language']; else $nl="";
            redirect ("admin/admin_edit.php?admin_id=".$admin_id.$nl);
            $proceed=false;
        }

        if ($proceed) {
            foreach ($admin as $k=>$v) if (isset($_REQUEST[$k])) $admin[$k]=$_REQUEST[$k];
        }
    }
}

if ($proceed) {

    echo '  <center>';

    show_message();

    echo '
        <form action="admin_edit.php" method=post>';

    if ($admin_id) echo '<input type=hidden name="admin_id" value="'.$admin_id.'">';
    else echo '<input type=hidden name="new" value="true">';

    echo '
        <table class="or_formtable">
            <TR><TD colspan="3">
                <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
                            '.lang('edit_profile_for').' ';
    if ($admin_id) echo $admin['adminname'];
    else echo lang('new_administrator');
    echo '
                        </TD>
                </TR></TABLE>
            </TD></TR>
            <tr>
                <td align=right>
                    '.lang('username').':
                </td>
                <td>&nbsp;&nbsp;</td>
                            <td>';
                    if (check_allow('admin_edit'))
                                        echo '<input name="adminname" type="text" size="20" maxlength="40" value="'.
                            $admin['adminname'].'">';
                    else echo $admin['adminname'];
                    echo '
                </td>
            </tr>

            <tr>
                <td align=right>
                    '.lang('firstname').':
                </td>
                <td>&nbsp;&nbsp;</td>
                <td>
                    <input name="fname" type="text" size="20" maxlength="50" value="'.$admin['fname'].'">
                </td>
            </tr>

            <tr>
                <td align=right>
                    '.lang('lastname').':
                </td>
                <td>&nbsp;&nbsp;</td>
                <td>
                    <input name="lname" type="text" size="20" maxlength="50" value="'.$admin['lname'].'">
                </td>
            </tr>

            <tr>
                                <td align=right>
                                        '.lang('email').':
                                </td>
                <td>&nbsp;&nbsp;</td>
                                <td>
                                        <input name="email" type="text" size="40" maxlength="200" value="'.$admin['email'].'">
                                </td>
                        </tr>';
        if (check_allow('admin_edit')) {
           echo '
            <tr>
                                <td align=right>
                                        '.lang('type').':
                                </td>
                                <td>&nbsp;&nbsp;</td>
                                <td>';
                    if ($admin['admin_type']) $selected=$admin['admin_type'];
                        else $selected=$settings['default_admin_type'];
                    echo admin__select_admin_type("admin_type",$selected);
                        echo '  </td>
                        </tr>
            ';

            echo '
                        <tr>
                                <td align=right>
                                        '.lang('account').':
                                </td>
                                <td>&nbsp;&nbsp;</td>
                                <td>
                                    <input name="disabled" type="radio" value="n"';
                                        if ($admin['disabled']!='y') echo ' CHECKED';
                                        echo '>'.lang('account_enabled').'&nbsp;&nbsp;
                                    <input name="disabled" type="radio" value="y"';
                                                if ($admin['disabled']=='y') echo ' CHECKED';
                                                echo '>'.lang('account_disabled').'
                                </td>
                        </tr>
            ';
            }

        echo '  <tr>
                                <td align=right>
                                        '.lang('language').':
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
                                        '.lang('is_experimenter').':
                                </td>
                <td>&nbsp;&nbsp;</td>
                                <td>
                                        <input name="experimenter_list" type="radio" value="y"';
                        if ($admin['experimenter_list']!='n') echo ' CHECKED';
                        echo '>'.lang('yes').'&nbsp;&nbsp;
                    <input name="experimenter_list" type="radio" value="n"';
                                                if ($admin['experimenter_list']=='n') echo ' CHECKED';
                                                echo '>'.lang('no').'
                                </td>
                        </tr>';
            }
        echo '
            <tr>
                                <td align=right>
                                        '.lang('receives_periodical_calendar').':
                                </td>
                                <td>&nbsp;&nbsp;</td>
                                <td>
                                        <input name="get_calendar_mail" type="radio" value="y"';
                                                if ($admin['get_calendar_mail']!='n') echo ' CHECKED';
                                                echo '>'.lang('yes').'&nbsp;&nbsp;
                                        <input name="get_calendar_mail" type="radio" value="n"';
                                                if ($admin['get_calendar_mail']=='n') echo ' CHECKED';
                                                echo '>'.lang('no').'&nbsp;&nbsp;
                                </td>
                        </tr>

            <tr>
                                <td align=right>
                                        '.lang('receives_periodical_participant_statistics').':
                                </td>
                                <td>&nbsp;&nbsp;</td>
                                <td>
                                        <input name="get_statistics_mail" type="radio" value="y"';
                                                if ($admin['get_statistics_mail']!='n') echo ' CHECKED';
                                                echo '>'.lang('yes').'&nbsp;&nbsp;
                                        <input name="get_statistics_mail" type="radio" value="n"';
                                                if ($admin['get_statistics_mail']=='n') echo ' CHECKED';
                                                echo '>'.lang('no').'&nbsp;&nbsp;
                                </td>
                        </tr>';

        if (check_allow('admin_edit')) {
           echo '
            <tr';
            if ($admin['locked']) echo ' bgcolor="orange"';
            echo '>
                        <td align=right>
                                '.lang('account_locked_due_to_failed_logins').':
                        </td>
                        <td>&nbsp;&nbsp;</td>
                        <td>';
            if ($admin['locked']) {
                echo '<B>'.lang('yes').'</B>
                    <input name="locked" type="radio" value="1"';
                if ($admin['locked']) echo ' CHECKED';
                echo '>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.
                        lang('unlock').'<input name="locked" type="radio" value="0"';
                if (!$admin['locked']) echo ' CHECKED';
                echo '>';
            } else {
                echo lang('no');
            }
            echo '
                        </td>
                </tr>

            <tr>
                <td align=right>
                    '.lang('last_login_attempt').':
                </td>
                <td>&nbsp;&nbsp;</td>
                <td>
                    ';
            if ($admin['last_login_attempt']) echo ortime__format($admin['last_login_attempt'],'hide_second:false');
            else echo lang('never');
            echo '
                </td>
            </tr>

            <tr>
                <td align=right>
                    '.lang('failed_login_attempts').':
                </td>
                <td>&nbsp;&nbsp;</td>
                <td>
                    '.$admin['failed_login_attempts'].'
                </td>
            </tr>

            <tr>
                    <td align=right>
                            '.lang('request_passwort_update').':
                    </td>
                    <td>&nbsp;&nbsp;</td>
                    <td>
                            <input name="pw_update_requested" type="radio" value="1"';
                                    if ($admin['pw_update_requested']) echo ' CHECKED';
                                    echo '>'.lang('yes').'&nbsp;&nbsp;
                            <input name="pw_update_requested" type="radio" value="0"';
                                    if (!$admin['pw_update_requested']) echo ' CHECKED';
                                    echo '>'.lang('no').'&nbsp;&nbsp;
                    </td>
            </tr>

            <tr>
                <td align=right>
                    '.lang('new_password').':
                </td>
                <td>&nbsp;&nbsp;</td>
                <td>
                    <input name="password" type="password" size="10" maxlength="20" value="">
                </td>
            </tr>

            <tr>
                <td align=right>
                    '.lang('repeat_new_password').':
                </td>
                <td>&nbsp;&nbsp;</td>
                <td>
                    <input name="password2" type="password" size="10" maxlength="20" value="">
                </td>
            </tr>';
            }
        echo '
            <tr>
                <td colspan=3 align=center>
                    <input class="button" name="edit" type="submit" value="';
                    if ($admin_id) echo lang('change'); else echo lang('add');
                    echo '">
                </td>
            </tr>
        </table>
        </form>

        <BR>';

    if ($admin_id) {
        if (check_allow('admin_delete')) {
        echo '
            <table border=0 width=80%>
            <tr>
                <td align=right>
                    '.button_link('admin_delete.php?admin_id='.urlencode($admin_id),
                            lang('delete'),'trash-o').'
                <td>
            </tr>
            </table>
            ';
        }
    }


    if (check_allow('admin_edit')) {
        echo '<BR><A href="admin_show.php">'.lang('user_management').'</A><BR><BR>';
        }


    if($admin_id && (check_allow('calendar_export_my') || check_allow('calendar_export_all'))) {
        echo '<BR><BR><TABLE witdh="80%">';
        if(check_allow('calendar_export_my')) {
            echo '<TR><TD bgcolor="lightgrey">'.lang('if_you_want_to_export_own_calendar').'<TD></TR>';
            echo '<TR><TD bgcolor="white">'.$settings__root_url.'/admin/calendar_ics.php?cal='.'p'.calendar__gen_ics_token($admin['admin_id'],$admin['password_crypt']).'</TD></TR>';
            echo '<TR><TD>&nbsp;</TD></TR>';
        }
        if (check_allow('calendar_export_all')) {
            echo '<TR><TD bgcolor="lightgrey">'.lang('if_you_want_to_export_full_calendar').'<TD></TR>';
            echo '<TR><TD bgcolor="white">'.$settings__root_url.'/admin/calendar_ics.php?cal='.'a'.calendar__gen_ics_token($admin['admin_id'],$admin['password_crypt']).'</TD></TR>';
        }
        echo '</TABLE>';
    }

    echo '</center>';

}
include ("footer.php");

?>
