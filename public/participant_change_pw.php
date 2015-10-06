<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="my_data";
$title="change_my_password";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['submit']) && $_REQUEST['submit']) {

        if (isset($_REQUEST['passold'])) $passold=$_REQUEST['passold']; else $passold="";
        if (isset($_REQUEST['password'])) $password=$_REQUEST['password']; else $password="";
        if (isset($_REQUEST['password2'])) $password2=$_REQUEST['password2']; else $password2="";

        // password tests
        $continue=true;

        if ($continue) {
            if (!$passold) {
                message (lang('error_please_fill_in_all_fields'));
                $continue=false;
            }
        }
        if ($continue) {
            if (!crypt_verify($passold,$participant['password_crypted'])) {
                message(lang('error_old_password_wrong'));
                message(lang('for_security_reasons_we_logged_you_out'));
                $continue=false;
                participant__logout();
                redirect("public/participant_login.php");
            }
        }
        if ($continue) {
            $continue=participant__check_password($password,$password2);
        }

        if ($continue==false) {
            message (lang('error_password_not_changed'));
            redirect ("public/participant_change_pw.php");
        } else {
            participant__set_password($password,$participant['participant_id']);
            message (lang('password_changed_log_in_again'));
            log__participant("participant_password_change",$participant['participant_id']);
            log__participant("logout",$participant['participant_id']);
            participant__logout();
            redirect("public/participant_login.php?pw=true");
        }
        $proceed=false;
    }
}

if ($proceed) {
    echo '  <center>';
            show_message();

    echo '<TABLE border=0><TR><TD align="center">';
    echo '<form action="participant_change_pw.php" method="POST">';
    echo '<table class="or_formtable" style="width: 50%;">
            <tr><td>'.lang('old_password').'<BR>
                <input type="password" name="passold" size="20" max-length="30">
            </td></tr>';
    echo participant__password_form_fields(true,false);
    echo '<tr><td align="center">
            <input class="button" type="submit" name="submit" value="'.lang('change').'">
            </td></tr>
        </table>
        </form>';
    echo '</TD><TD align="right" valign="top">';
    echo '<TABLE border=0>';
    echo '<TR><TD>'.button_link('participant_edit.php',
                            lang('edit_your_profile'),'pencil-square-o').'</TD></TR>';
    echo '<TR><TD>'.button_link('participant_show.php',
                            lang('my_registrations'),'calendar-o').'</TD></TR>';
    echo '</TABLE>';

    echo '</TD><TR></TABLE>';
    echo '</center>';
}
include("footer.php");
?>