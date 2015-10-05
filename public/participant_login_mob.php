<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="login";
$suppress_html_header=true;
include ("header.php");
if ($proceed) {
    if ($settings['enable_mobile_pages']!='y') redirect ("public/particpant_login.php");
}

if ($proceed) {

    if (isset($_REQUEST['logout']) && $_REQUEST['logout']) message(lang('logout'));

    if (isset($_REQUEST['requested_url']) && $_REQUEST['requested_url'])
        $_SESSION['requested_url']=$_REQUEST['requested_url'];

    if (isset($_REQUEST['login']) && isset($_REQUEST['email']) && isset($_REQUEST['password'])) {
        $logged_in=participant__check_login($_REQUEST['email'],$_REQUEST['password']);
        if ($logged_in) {
            if (isset($_SESSION['requested_url']) && $_SESSION['requested_url']) {
                $url=$_SESSION['requested_url'];
                unset($_SESSION['requested_url']);
                redirect($url);
            } else redirect("public/participant_show_mob.php");
        } else {
            redirect("public/participant_login_mob.php");
        }
    }
}


if ($proceed) {
    // render the page
    $footer='<div data-role="footer" data-theme="a"><h1>'.$settings['default_area'].'</h1></div></div>';
    //$footer='</div>';

    html__mobile_header();

    if (isset($_SESSION['message_text'])) $message_text=$_SESSION['message_text']; else $message_text="";
    $_SESSION['message_text']="";

    echo '
    <!-- index -->
    <div data-role="page" id="indexPage">
        <div data-role="header" data-theme="a">
            <h1>'.lang('profile_login').'</h1>
        </div>
        <div data-role="content">';
    if ($message_text) echo '<div data-role="content"><font color="red">'.lang('message').': '.$message_text.'</font></div>';
    echo '<form id="login_form" action="participant_login_mob.php" method="post" data-ajax="false">';
    echo '<fieldset>
            <div data-role="fieldcontain">
                <label for="email" class="ui-hidden-accessible">'.lang('email').'</label>
                <input type="text" value="" name="email" id="email" placeholder="'.lang('email').'"/>
            </div>
            <div data-role="fieldcontain">
                <label for="password" class="ui-hidden-accessible">'.lang('password').'</label>
                <input type="password" value="" name="password" id="password" placeholder="'.lang('password').'"/>
            </div>
            <input type="submit" data-theme="b" name="login" id="login" value="'.lang('login').'">
        </fieldset>
        </form>';

    echo '<br/><br/><center><A HREF="participant_reset_pw.php" data-ajax="false">'.lang('forgot_your_password?').'</A></center>';

    echo '
        </div>';

    echo $footer;

    html__mobile_footer();
}
?>