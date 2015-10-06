<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="change_my_password";
include ("header.php");

if ($proceed) {
    if (isset($_REQUEST['submit']) && $_REQUEST['submit']) {

        if (isset($_REQUEST['passold'])) $passold=$_REQUEST['passold']; else $passold="";
        if (isset($_REQUEST['password'])) $password=$_REQUEST['password']; else $password="";
        if (isset($_REQUEST['password2'])) $password2=$_REQUEST['password2']; else $password2="";

        // password tests
        $continue=true;

        if (!$passold || !$password || !$password2) {
            message (lang('error_please_fill_in_all_fields'));
            $continue=false;
        }

        if ($password!=$password2) {
            message (lang('error_password_repetition_does_not_match'));
            $continue=false;
        }

        if (!crypt_verify($passold,$expadmindata['password_crypt'])) {
            message (lang('error_old_password_wrong'));
            $continue=false;
        }

        if ($password==$expadmindata['adminname']) {
            message(lang('error_do_not_use_username_as_password'));
            $continue=false;
        }
        if ($settings['admin_password_change_require_different']=='y') {
            if ($passold==$password) {
                message(lang('error_new_password_must_be_different_from_old_password'));
            $continue=false;
        }

        }

        if (!preg_match('/'.$settings['admin_password_regexp'].'/',$password)) {
            message(lang('error_password_does_not_meet_requirements'));
            $continue=false;
        }


        if ($continue==false) {
            message (lang('error_password_not_changed'));
            redirect ("admin/admin_pw.php");
        } else {
            admin__set_password($password,$expadmindata['admin_id']);
            message (lang('password_changed_log_in_again'));
            log__admin("admin_password_change",$expadmindata['adminname']);
            log__admin("logout");
            admin__logout();
            redirect("admin/admin_login.php?pw=true");
        }
        $proceed=false;
    }
}

if ($proceed) {
    echo '<center><BR>';
    show_message();

    echo '
        <form action="admin_pw.php" method="POST">
        <table class="or_formtable" style="max-width: 50%">
        <tr>
            <td>
                '.lang('old_password').':
            </td>
            <td>
                <input type="password" name="passold" size="20" max-length="40">
            </td>
        </tr>
        <tr>
            <td colspan=2 style="background: #FFFFCC; border: 2px solid #AAA">
                '.lang('admin_password_strength_requirements').':
            </td>
        </tr>
        <tr>
            <td>
                '.lang('new_password').':
            </td>
            <td>
                <input type="password" name="password" size="20" max-length="40">
            </td>
        </tr>
        <tr>
            <td>
                '.lang('repeat_new_password').':
            </td>
            <td>
                <input type="password" name="password2" size="20" max-length="40">
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                <input  class="button" type="submit" name="submit" value="'.lang('change').'">
            </td>
        </tr>
        </table>
        </form>

        </center>';

}
include ("footer.php");

?>
