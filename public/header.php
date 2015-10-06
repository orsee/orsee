<?php
// part of orsee. see orsee.org
$debug__script_started=microtime();
include ("../config/settings.php");
include ("../config/system.php");
include ("../config/requires.php");

$proceed=true;

if ($proceed) {
    site__database_config();
    $settings=load_settings();
    $settings['style']=$settings['orsee_public_style'];
    $color=load_colors();
    session_set_save_handler("orsee_session_open", "orsee_session_close", "orsee_session_read", "orsee_session_write", "orsee_session_destroy", "orsee_session_gc");
    session_start();
    $_REQUEST=strip_tags_array($_REQUEST);
}

if ($proceed) {
    if ($settings['stop_public_site']=="y" && !isset($expadmindata['adminname']) && !(thisdoc()=="disabled.php"))
        redirect("public/disabled.php");
}

if ($proceed) {
    // with token-only, do not allow access to these pages
    $token_exclude=array("participant_reset_pw.php",
            "participant_change_pw.php",
            "participant_login.php",
            "participant_login_mob.php");
    if ($settings['subject_authentication']=='token' && in_array(thisdoc(),$token_exclude)) {
        redirect("public/");
    }
}


if ($proceed) {
    // if we work with tokens or do the migration, check for token on any page
    if ($settings['subject_authentication']=='token' || $settings['subject_authentication']=='migration') {
        $participant_id=site__check_token();
        if ($participant_id) {
            // get participant's language
            $participant=orsee_db_load_array("participants",$participant_id,"participant_id");
            $_SESSION['pauthdata']['language']=$participant['language'];
            unset ($participant);
            $show_logged_in_menu=true;
        }
    }
}

if ($proceed) {
    // determine language for page
    if (!isset($_SESSION['pauthdata']['language']) || !$_SESSION['pauthdata']['language']) $_SESSION['pauthdata']['language']=$settings['public_standard_language'];
    if (isset($_REQUEST['language'])) {
        $langarray=lang__get_public_langs();
        if (in_array($_REQUEST['language'],$langarray)) {
            $_SESSION['pauthdata']['language']=$_REQUEST['language'];
        }
    }
    $lang=load_language($_SESSION['pauthdata']['language']);
}

if ($proceed) {
    if (!in_array(thisdoc(),array('participant_create.php','captcha.php'))) {
        unset ($_SESSION['subpool_id']);
        unset ($_SESSION['rules']);
    }
}

if ($proceed) {
    // require participant login for the following pages
    $part_load=array("participant_edit.php",
            "participant_delete.php",
            "participant_show.php",
            "participant_show_mob.php",
            "participant_change_pw.php",
            "participant_logout.php");

    if (in_array(thisdoc(),$part_load)) {
        $token_string='';
        // if already logged in, just load participant data
        if (isset($_SESSION['pauthdata']['user_logged_in']) && $_SESSION['pauthdata']['user_logged_in'] &&
            isset($_SESSION['pauthdata']['participant_id']) && $_SESSION['pauthdata']['participant_id']) {
            $participant=orsee_db_load_array("participants",$_SESSION['pauthdata']['participant_id'],"participant_id");
            $participant_id=$participant['participant_id'];
        } else {
            if ($settings['subject_authentication']=='token' ) {
                // if we work with tokens, check whether we are logged in and load participant data
                if ($participant_id) {
                    $participant=orsee_db_load_array("participants",$participant_id,"participant_id");
                    $token_string="?p=".urlencode($participant['participant_id_crypt']);
                } else {
                    redirect("public/");
                }
            } elseif ($settings['subject_authentication']=='migration') {
                // if we migrate
                if ($participant_id) {
                    $participant=orsee_db_load_array("participants",$participant_id,"participant_id");
                    // if pw exists, the send to login page
                    if ($participant['password_crypted']) {
                        if (isset($mobile) && $mobile) redirect("public/participant_login_mob.php");
                        else redirect("public/participant_login.php");
                    } else {
                            // prepare password reset: generate token, save token to db and session
                            $participant['pwreset_token']=create_random_token(get_entropy($participant));
                            $pars=array(':token'=>$participant['pwreset_token'],
                                    ':participant_id'=>$participant['participant_id'],
                                    ':now'=>time());
                            $query="UPDATE ".table('participants')."
                                    SET pwreset_token = :token,
                                    pwreset_request_time = :now
                                    WHERE participant_id= :participant_id";
                            $done=or_query($query,$pars);
                            $_SESSION['pw_reset_token']=$participant['pwreset_token'];
                            // send to pw rest page
                            message(lang('please_choose_a_password_for_your_account'));
                            redirect("public/participant_reset_pw.php");
                    }
                } else {
                // and if we only allow username/passsword, send to login page
                    if (isset($mobile) && $mobile) redirect("public/participant_login_mob.php");
                    else redirect("public/participant_login.php");
                }
            } else {


            }
        }
        if ($proceed) {
            // do some other checks when we are logged in
            $statuses=participant_status__get_statuses();
            $statuses_profile=participant_status__get("access_to_profile");
            if (isset($participant) && !in_array($participant['status_id'],$statuses_profile)) {
                message ($statuses[$participant['status_id']]['error']." ".
                lang('if_you_have_questions_write_to')." ".support_mail_link());
                redirect("public/");
            }
        }
    }
}

if ($proceed) {
    $pagetitle=$settings['default_area'];

    if (!isset($title)) $title="";
    if ($title) $title=lang($title);
    $pagetitle=$pagetitle.': '.$title;
    if (!isset($suppress_html_header) || !$suppress_html_header) {
        html__header();
        html__show_style_header('public',$title);
        echo "<center>";
        show_message();
        echo "</center>";
    }
}
?>
