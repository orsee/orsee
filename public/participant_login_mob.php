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
    html__mobile_header();

    if (isset($_SESSION['message_text'])) $message_text=$_SESSION['message_text']; else $message_text="";
    $_SESSION['message_text']="";

    echo '<ons-page id="login-page">';
    echo '      <ons-toolbar style="background-color: '.$color['html_header_menu_background'].';">
                  <div class="center" style="color: '.$color['menu_item'].';">'.$settings['default_area'].'</div>
                </ons-toolbar>';

    echo '  <ons-list>    
                <ons-list-item>
                  <div style="text-align: center; width: 100%; font-weight: bold;">'.lang('profile_login').'</div>
                </ons-list-item>
            </ons-list>';

    if ($message_text) {
        echo '<ons-card style="border: 2px solid red;">';
        echo '    <div class="content" style="color: red;">'.lang('message').': '.$message_text.'</div>';
        echo '</ons-card>';
    }
        echo '<ons-list modifier="inset">';

        echo '<form action="'.thisdoc().'" method="post" id="login_form">';
        echo '<INPUT name="login" type="submit" id="login_submit" style="display: none;">';

        echo '<ons-list-item>
                <ons-input name="email" modifier="underbar" placeholder="'.lang('email').'" type="email" float required></ons-input>
              </ons-list-item>';
        echo '<ons-list-item>
                <ons-input name="password" modifier="underbar" placeholder="'.lang('password').'" type="password" float required></ons-input>
            </ons-list-item>';
        echo '<ons-list-item>';
        echo '<ons-button type="submit" ripple modifier="large" onclick="document.getElementById(\'login_submit\').click();">'.lang('login').'</ons-button>';
        echo '</ons-list-item>';
        echo '</form>';
        echo '</ons-list>';
        echo '<br/><br/><center><A HREF="participant_reset_pw.php">'.lang('forgot_your_password?').'</A></center>';
        echo '</ons-page>';

    html__mobile_footer();
}
?>