<?php
// part of orsee. see orsee.org
ob_start();

$title="admin_login_page";
include("header.php");
if ($proceed) {

    echo '<center>';

    if (isset($_REQUEST['logout']) && $_REQUEST['logout']) message(lang('logout'));

    if (isset($_REQUEST['pw']) && $_REQUEST['pw']) {
        message(lang('logout'));
        message (lang('password_changed_log_in_again'));
    }

    show_message();

    if (isset($_REQUEST['adminname']) && isset($_REQUEST['password'])) {
        $logged_in=admin__check_login($_REQUEST['adminname'],$_REQUEST['password']);
        if ($logged_in) {
            $expadmindata['admin_id']=$_SESSION['expadmindata']['admin_id'];
            log__admin("login");
            if (isset($_REQUEST['requested_url']) && $_REQUEST['requested_url']) redirect(urldecode($_REQUEST['requested_url']));
            else redirect("admin/index.php");
        } else {
            message(lang('error_password_or_username'));
            $add="";
            if (isset($_REQUEST['requested_url']) && $_REQUEST['requested_url'])
                $add="?requested_url=".$_REQUEST['requested_url'];
            redirect("admin/admin_login.php".$add);
        }
        $proceed=false;
    }
}

if ($proceed) {

    admin__login_form();

    echo '</center>';
}
include("footer.php");

?>
