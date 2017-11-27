<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="public_register";
$title="reset_password";
include ("header.php");

if ($proceed) {
    if (isset($_REQUEST['t']) && $_REQUEST['t']) {
        $_SESSION['pw_reset_token']=$_REQUEST['t'];
        redirect("public/participant_reset_pw.php");
    }
}

if ($proceed) {
    if (isset($_SESSION['pw_reset_token']) && $_SESSION['pw_reset_token'] &&
        isset($_REQUEST['reset_email']) && isset($_REQUEST['password']) && isset($_REQUEST['password2']) &&
        ( $_REQUEST['reset_email'] || $_REQUEST['password'] || $_REQUEST['password2']) ) {

        $continue=true;

        $_SESSION['reset_email_address']=trim($_REQUEST['reset_email']);
        // captcha
        if ($continue) {
            if ($_REQUEST['captcha']!=$_SESSION['captcha_string']) {
                $continue=false;
                message(lang('error_wrong_captcha'));
                redirect("public/participant_reset_pw.php");
            }
        }
        if ($continue) {
            // check password, token, and email address
            $status_clause=participant_status__get_pquery_snippet("access_to_profile");
            $pars=array(':token'=>$_SESSION['pw_reset_token']);
            $query="SELECT * FROM ".table('participants')."
                    WHERE pwreset_token= :token
                    AND ".$status_clause;
            $participant=orsee_query($query,$pars);
            if (!isset($participant['participant_id'])) {
                //if token not ok, redirect to main page without comment
                $continue=false;
                message('token not found');
                redirect ("public/");
            } elseif ($participant['pwreset_request_time']+60*60<time()) {
                //if token validity elapsed, show message and redirect
                message(lang('password_reset_token_not_valid_anymore'));
                $continue=false;
                redirect ("public/");
            }
        }
        if ($continue) {
            if (strtolower($participant['email'])!=strtolower(trim($_REQUEST['reset_email']))) {
            //if email address not ok: save email address to session, show message, redirect
                message(lang('password_reset_provided_email_address_not_correct'));
                $continue=false;
                redirect("public/participant_reset_pw.php");
            }
        }
        if ($continue) {
            $pw_ok=participant__check_password($_REQUEST['password'],$_REQUEST['password2']);
            if (!$pw_ok) {
                //if passwords not ok: save email address to session, show message, redirect
                $continue=false;
                redirect("public/participant_reset_pw.php");
            }
        }
        if ($continue) {
        //if all ok, save new password (reset reset_request, token), reset token, password, email address, set OK, redirect
            $participant['password_crypted']=unix_crypt($_REQUEST['password']);
            $pars=array(':password'=>$participant['password_crypted'],
                        ':participant_id'=>$participant['participant_id']);
            $query="UPDATE ".table('participants')."
                    SET password_crypted = :password,
                    pwreset_token= NULL
                    WHERE participant_id = :participant_id";
            $participant=or_query($query,$pars);
            unset($_SESSION['pw_reset_token']);
            unset($_SESSION['captcha_string']);
            unset($_SESSION['reset_email_address']);
            $_SESSION['password_has_been_changed']=true;
            redirect("public/participant_reset_pw.php");
        }
    }
}

if ($proceed) {
    if (isset($_SESSION['pw_reset_token']) && $_SESSION['pw_reset_token']) {
        // show form, captcha
        echo '  <center>';
        show_message();

        if (isset($_SESSION['reset_email_address']) && $_SESSION['reset_email_address'])
            $email=$_SESSION['reset_email_address'];
        else $email='';
        echo '<form action="participant_reset_pw.php" method="POST">';
        echo '<table class="or_formtable" style="width: 50%;">';
        echo '<tr><td colspan="2">'.lang('reset_pw_please_enter_email_and_new_password').'</TD></TR>';
        echo '<TR><TD>'.lang('email').'<BR>
                <input type="text" name="reset_email" size="30" max-length="100" value="'.$email.'">
            </td></tr>';
        echo participant__password_form_fields(true,false);
        echo '<TR><TD>'.lang('captcha_text').'<br><IMG src="captcha.php"><BR>
                <INPUT type="text" name="captcha" size="8" maxlength="8" value="">
                </TD></TR>';
        echo '<tr><td align="center">
            <input class="button" type="submit" name="submit" value="'.lang('change').'">
            </td></tr>
        </table>
        </form>';
    $proceed=false;
    }
}

if ($proceed) {
    if (isset($_SESSION['password_has_been_changed']) && $_SESSION['password_has_been_changed']) {
        message(lang('password_changed'));
        unset($_SESSION['password_has_been_changed']);
        $proceed=false;
        echo '<center>';
        show_message();
        echo '</center>';
    }
}

if ($proceed) {
    if (isset($_REQUEST['email']) && $_REQUEST['email']) {
        $continue=true;
        // captcha
        if ($continue) {
            if ($_REQUEST['captcha']!=$_SESSION['captcha_string']) {
                $continue=false;
                message(lang('error_wrong_captcha'));
                redirect("public/participant_reset_pw.php");
            }
        }

        if ($continue) {
            $status_clause=participant_status__get_pquery_snippet("access_to_profile");
            $pars=array(':email'=>$_REQUEST['email']);
            $query="SELECT * FROM ".table('participants')."
                    WHERE email= :email
                    AND ".$status_clause;
            $participant=orsee_query($query,$pars);
            if (isset($participant['participant_id'])) {
                // create and save token
                $participant['pwreset_token']=create_random_token(get_entropy($participant));
                $pars=array(':token'=>$participant['pwreset_token'],
                        ':participant_id'=>$participant['participant_id'],
                        ':now'=>time());
                $query="UPDATE ".table('participants')."
                        SET pwreset_token = :token,
                        pwreset_request_time = :now
                        WHERE participant_id= :participant_id";
                $done=or_query($query,$pars);
                // send reset email
                $done=experimentmail__mail_pwreset_link($participant);
                message(lang('password_reset_link_sent_if_email_exists'));
                redirect('public/');
            } else {
                // to not reveal which email addresses exist, just do as if
                message(lang('password_reset_link_sent_if_email_exists'));
                redirect('public/');
            }
        }
    }
}

if ($proceed) {
    echo '  <center><BR><BR>';
            show_message();

    echo '<form action="participant_reset_pw.php" method="POST">';
    echo '<table class="or_formtable" style="width: 50%;">
            <tr><td colspan="2">'.lang('reset_pw_please_enter_your_email_address').'</TD></TR>
            <TR><TD>'.lang('email').'</TD><TD>
                <input type="text" name="email" size="30" max-length="100">
            </td></tr>';
    echo '<TR><TD>'.lang('captcha_text').'</TD>
                <TD><IMG src="captcha.php"><BR>
                <INPUT type="text" name="captcha" size="8" maxlength="8" value="">
                </TD></TR>';
    echo '<tr><td align="center" colspan="2">
            <input class="button" type="submit" name="submit" value="'.lang('submit').'">
            </td></tr>
        </table>
        </form>';
    echo '</center>';

}

include("footer.php");
?>