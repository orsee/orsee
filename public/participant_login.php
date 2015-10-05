<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="login";
$title="profile_login";
include("header.php");
if ($proceed) {
    if (isset($_REQUEST['logout']) && $_REQUEST['logout']) message(lang('logout'));
    if (isset($_REQUEST['pw']) && $_REQUEST['pw']) {
        message(lang('logout'));
        message (lang('password_changed_log_in_again'));
    }

    if (isset($_REQUEST['requested_url']) && $_REQUEST['requested_url'])
        $_SESSION['requested_url']=$_REQUEST['requested_url'];

    if (isset($_REQUEST['login']) && isset($_REQUEST['email']) && isset($_REQUEST['password'])) {
        $logged_in=participant__check_login($_REQUEST['email'],$_REQUEST['password']);
        if ($logged_in) {
            if (isset($_SESSION['requested_url']) && $_SESSION['requested_url']) {
                $url=$_SESSION['requested_url'];
                unset($_SESSION['requested_url']);
                redirect($url);
            } else redirect("public/participant_show.php");
        } else {
            redirect("public/participant_login.php");
        }
        $proceed=false;
    }
}

if ($proceed) {
    echo '<CENTER>';
    show_message();

    echo '<BR><BR><form name="login" action="participant_login.php" method=post>
        <table class="or_formtable">
        <TR><TD>'.lang('email').':</TD><TD>
        <input type="text" size="30" maxlength="100" name="email">
        </TD></TR>
        <TR><TD>
        '.lang('password').':</TD><TD>
        <input type="password" size="20" maxlength="30" name="password">
        </TD></TR>
        <TR><TD colspan="2" align="center">
        <input class="button" type=submit name=login value="'.lang('login').'">
        </TD></TR>
        </TABLE>

        <BR><BR>
        <A HREF="participant_reset_pw.php"><FONT class="small">'.lang('forgot_your_password?').'</FONT></A>
        ';

    echo '</CENTER>';
}
include("footer.php");
?>