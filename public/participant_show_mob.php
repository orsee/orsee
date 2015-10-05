<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="my_registrations";
$suppress_html_header=true; $mobile=true;
include ("header.php");
if ($proceed) {
    if ($settings['enable_mobile_pages']!='y') redirect ("public/participant_show.php".$token_string);
}

if ($proceed) {
    if (isset($_REQUEST['s']) && $_REQUEST['s']) $session_id=trim($_REQUEST['s']); else $session_id="";

    if (isset($_REQUEST['register']) && $_REQUEST['register']) {
        $continue=true;
        if (!$session_id) {
            $continue=false;
            log__participant("interfere - no session_id",$participant_id);
            message(lang('error_session_id_register'));
            redirect("public/participant_show_mob.php".$token_string);
        } else {
            $session=orsee_db_load_array("sessions",$session_id,"session_id");
            if (!isset($session['session_id'])) {
                log__participant("interfere - invalid session_id",$participant_id);
                message(lang('error_session_id_register'));
                redirect("public/participant_show_mob.php".$token_string);
            }
        }
        if ($proceed) {
            $participate_at=expregister__get_participate_at($participant_id,$session['experiment_id']);
            if (!isset($participate_at['session_id'])) {
                $continue=false;
                redirect("public/participant_show_mob.php".$token_string);
            }
        }
        if ($proceed) {
            if ($settings['enable_enrolment_only_on_invite']=='y') {
                if (!$participate_at['invited']) {
                    $continue=false;
                    redirect("public/participant_show_mob.php".$token_string);
                }
            }
        }
        if ($proceed) {
            if (isset($participate_at['session_id']) && $participate_at['session_id']>0) {
                $continue=false;
                message(lang('error_already_registered'));
                redirect("public/participant_show_mob.php".$token_string);
            }
        }
        if ($proceed) {
            $registration_end=sessions__get_registration_end($session);
            $full=sessions__session_full($session_id,$session);
            $now=time();
            if ($registration_end < $now) {
                $continue=false;
                message(lang('error_registration_expired'));
                redirect("public/participant_show_mob.php".$token_string);
            }
        }
        if ($proceed) {
            if ($full) {
                 $continue=false;
                 message(lang('error_session_complete'));
                 redirect("public/participant_show_mob.php".$token_string);
            }
        }

        // if all checks are done, register ...
        if ($continue) {
            $done=expregister__register($participant,$session);
            $done=participant__update_last_enrolment_time($participant_id);
            $done=log__participant("register",$participant['participant_id'],
                    "experiment_id:".$session['experiment_id']."\nsession_id:".$session_id);
            message(lang('successfully_registered_to_experiment_xxx')." ".
                    experiment__get_public_name($session['experiment_id']).", ".
                    session__build_name($session).". ".
                    lang('this_will_be_confirmed_by_an_email'));
            $redir="public/participant_show_mob.php".$token_string;
            if ($token_string) $redir.="&"; else $redir.="?";
            $redir.="s=".$session_id;
            redirect($redir);
        }
    } elseif (isset($_REQUEST['cancel']) && $_REQUEST['cancel'] &&
            isset($settings['allow_subject_cancellation']) && $settings['allow_subject_cancellation']=='y') {
        $continue=true;
        if (!$session_id) {
            $continue=false;
            log__participant("interfere enrolment cancellation- no session_id",$participant_id);
            message(lang('error_session_id_register'));
            redirect("public/participant_show_mob.php".$token_string);
        } else {
            $session=orsee_db_load_array("sessions",$session_id,"session_id");
            if (!isset($session['session_id'])) {
                log__participant("interfere enrolment cancellation - invalid session_id",$participant_id);
                message(lang('error_session_id_register'));
                redirect("public/participant_show_mob.php".$token_string);
            }
        }
        if ($proceed) {
            $participate_at=expregister__get_participate_at($participant_id,$session['experiment_id']);
            if (!isset($participate_at['session_id']) || $participate_at['session_id']!=$session_id) {
                $continue=false;
                redirect("public/participant_show_mob.php".$token_string);
            }
        }
        if ($proceed) {
            $cancellation_deadline=sessions__get_cancellation_deadline($session);
            $now=time();
            if ($cancellation_deadline < $now) {
                $continue=false;
                message(lang('error_enrolment_cancellation_deadline_expired'));
                redirect("public/participant_show_mob.php".$token_string);
            }
        }
        // if all checks are done, cancel ...
        if ($continue) {
            $done=expregister__cancel($participant,$session);
            $done=participant__update_last_enrolment_time($participant_id);
            $done=log__participant("cancel_session_enrolment",$participant['participant_id'],
                        "experiment_id:".$session['experiment_id']."\nsession_id:".$session_id);
            message(lang('successfully_canceled_enrolment_xxx')." ".
                    experiment__get_public_name($session['experiment_id']).", ".
                    session__build_name($session_id).". ".
                    lang('this_will_be_confirmed_by_an_email'));
            redirect("public/participant_show_mob.php".$token_string);
        }
    }
}

if ($proceed) {

    $labs=laboratories__get_laboratories();

    // load the data
    // invitations
    $invdata=expregister__get_invitations($participant_id);
    $invited=$invdata['invited'];
    $inv_experiments=$invdata['inv_experiments'];

    // registrations
    $registered=expregister__get_registrations($participant_id);

    // history
    $history=expregister__get_history($participant_id);

    if (isset($_SESSION['message_text'])) $message_text=$_SESSION['message_text']; else $message_text="";
    $_SESSION['message_text']="";

    // render the page
    $footer='<div data-role="footer" data-theme="a"><h1>'.$settings['default_area'].'</h1></div></div>';
    //$footer='</div>';

    html__mobile_header();


    echo '
    <!-- index -->
    <div data-role="page" id="indexPage">
        <div data-role="header" data-theme="a">
            <h1>'.lang('mobile_experiment_registrations').'</h1>';
    if ($settings['subject_authentication']!='token') {
        echo '<a href="participant_logout.php?mobile=true" class="ui-btn-right" data-theme="b" data-ajax="false">'.lang('logout').'</a>';
    }
    echo '
        </div>
        <div data-role="content">';

        if ($message_text) echo '<div data-role="content"><font color="red">'.lang('message').': '.$message_text.'</font></div>';

        echo '
            <ul data-role="listview">
                <li>
                <a href="#invited" class="ui-btn ui-btn-icon-left ui-icon-mail">'.lang('mobile_new_invitations').' <span class="ui-li-count">'.count($inv_experiments).'</span></a>
                </li>
                <li>
               <a href="#registered" class="ui-btn ui-btn-icon-left ui-icon-calendar">'.lang('mobile_current_enrolments').' <span class="ui-li-count">'.count($registered).'</span></a>
                </li>
                <li>
               <a href="#participated" class="ui-btn ui-btn-icon-left ui-icon-bullets">'.lang('mobile_past_enrolments').' <span class="ui-li-count">'.count($history).'</span></a>
                </li>
            </ul>


        </div>';

    echo $footer;

    echo '
    <!-- invited -->
    <div data-role="page" id="invited">
        <div data-role="header" data-theme="a">
            <h1>'.lang('mobile_new_invitations').'</h1>
            <a href="#indexPage" class="ui-btn-left">'.lang('back').'</a>
        </div>

        <div data-role="content">
                '.lang('please_check_availability_before_register').'
           ';

    $first=true; $now=time();
    foreach ($invited as $s) {
        if ($s['new_experiment']) {
            if (!$first) echo '</ul>';
            else $first=false;
            echo '<h3 class="ui-bar ui-bar-a">'.$s['experiment_public_name'].'</h3>';
            if (or_setting('allow_public_experiment_note') && isset($s['public_experiment_note']) && trim($s['public_experiment_note'])) {
                echo '<i>'.lang('note').': '.trim($s['public_experiment_note']).'</i>';
            }
            echo '<ul data-role="listview" data-theme="a" data-inset="true">';
        }
        if ((!$s['session_full']) && ($s['registration_unixtime'] >= $now)) {
            echo '<li><a href="#s'.$s['session_id'].'" class="ui-mini">'.$s['session_name'].'<br><font color="grey">'.$labs[$s['laboratory_id']]['lab_name'].'';
            if (or_setting('allow_public_session_note') && isset($s['public_session_note']) && trim($s['public_session_note'])) {
                echo '<BR><i>'.lang('note').': '.trim($s['public_session_note']).'</i>';
            }
            echo '</font></a></li>';
        } elseif ($s['registration_unixtime'] < $now) {
            echo '<li class="ui-mini">'.$s['session_name'].'<br><font color="grey">'.$labs[$s['laboratory_id']]['lab_name'].'</font><br><FONT color="'.$color['session_public_expired'].'">'.lang('expired').'</FONT></li>';
        } else {
            echo '<li class="ui-mini">'.$s['session_name'].'<br><font color="grey">'.$labs[$s['laboratory_id']]['lab_name'].'</font><br><FONT color="'.$color['session_public_complete'].'">'.lang('complete').'</FONT></li>';
        }
    }

    if (count($invited)>0) echo '</ul>';
    else echo '<h4 class="ui-bar ui-bar-a">'.lang('mobile_no_current_invitations').'</h4>';

    echo '</div>';

    echo $footer;


    foreach ($invited as $s) {
        echo '<div data-role="page" id="s'.$s['session_id'].'" data-dialog="true">';
        echo '<div data-role="header" data-theme="a">
            <h1>'.lang('experiment_registration').'</h1>
            <a href="#invited" class="ui-btn-left">'.lang('back').'</a>
            </div>';
        echo '<div data-role="content">';
        show_message();
        echo '<h3 class="ui-bar ui-bar-a">'.lang('do_you_really_want_to_register_for_experiment').'</h3>';
        echo '<strong>'.lang('experiment').':</strong><br>'.$s['experiment_public_name'].'<br>';
        if (or_setting('allow_public_experiment_note') && isset($s['public_experiment_note']) && trim($s['public_experiment_note'])) {
            echo '<i>'.lang('note').': '.trim($s['public_experiment_note']).'</i><br>';
        }
        echo '<strong>'.lang('date_and_time').':</strong><br>'.$s['session_name'].'<br>';
        if (or_setting('allow_public_session_note') && isset($s['public_session_note']) && trim($s['public_session_note'])) {
            echo '<i>'.lang('note').': '.trim($s['public_session_note']).'</i><br>';
        }
        echo '<strong>'.lang('laboratory').':</strong><br>'.$labs[$s['laboratory_id']]['lab_name'].'<br>'.$labs[$s['laboratory_id']]['lab_address'].'<br>';
        echo '<form id="form-'.$s['session_id'].'" method="post" data-ajax="false">
                    <INPUT type=hidden name="s" value="'.$s['session_id'].'">';
        if ($token_string) echo '<INPUT type=hidden name="p" value="'.$participant['participant_id_crypt'].'">';
        echo '<INPUT type=hidden name="register" value="true">';
        echo '<input data-icon="check" type="submit" id="submit-s'.$s['session_id'].'" value="'.lang('yes_i_want').'">';
        echo '<a href="#invited" class="ui-btn ui-icon-delete ui-btn-icon-left">'.lang('no_sorry').'</a>';
        echo '</form>';
        echo '</div>';
        echo $footer;
    }



    echo '
    <!-- registered -->
    <div data-role="page" id="registered">
        <div data-role="header" data-theme="a">
            <h1>'.lang('mobile_current_enrolments').'</h1>
            <a href="#indexPage" class="ui-btn-left">'.lang('back').'</a>
        </div>
        <div data-role="content">';

        if (count($registered)>0) echo '<ul data-role="listview" data-theme="a" data-inset="true">';

        foreach ($registered as $s) {
            echo '<li><a href="#reg'.$s['session_id'].'" class="ui-mini">
            <font color="black">'.$s['session_name'].'</font><br>
            <font color="grey">'.lang('experiment').': '.$s['experiment_public_name'].'<br>
            '.$labs[$s['laboratory_id']]['lab_name'].'</font></a></li>
            ';
        }
        if (count($registered)>0) echo '</ul>';
        else echo '<h4 class="ui-bar ui-bar-a">'.lang('mobile_no_current_registrations').'</h4>';

    echo '</div>';

    echo $footer;


    foreach ($registered as $s) {
        echo '<div data-role="page" id="reg'.$s['session_id'].'">';
        echo '<div data-role="header" data-theme="a">
            <h1>'.lang('mobile_current_enrolments').'</h1>
            <a href="#registered" class="ui-btn-left">'.lang('back').'</a>
            </div>';
        echo '<div data-role="content">';
        echo '<strong>'.lang('experiment').':</strong><br>'.$s['experiment_public_name'].'<br>';
        if (or_setting('allow_public_experiment_note') && isset($s['public_experiment_note']) && trim($s['public_experiment_note'])) {
            echo '<i>'.lang('note').': '.trim($s['public_experiment_note']).'</i><br>';
        }
        echo '<strong>'.lang('date_and_time').':</strong><br>'.$s['session_name'].'<br>';
        if (or_setting('allow_public_session_note') && isset($s['public_session_note']) && trim($s['public_session_note'])) {
            echo '<i>'.lang('note').': '.trim($s['public_session_note']).'</i><br>';
        }
        echo '<strong>'.lang('laboratory').':</strong><br>'.$labs[$s['laboratory_id']]['lab_name'].'<br>'.$labs[$s['laboratory_id']]['lab_address'].'<br><br>';
        if (isset($settings['allow_subject_cancellation']) && $settings['allow_subject_cancellation']=='y') {
            $s['cancellation_deadline']=sessions__get_cancellation_deadline($s);
            if ($s['cancellation_deadline']>time()) {
                echo '<a href="#can'.$s['session_id'].'" class="ui-btn ui-icon-delete ui-btn-icon-left">'.lang('cancel_enrolment').'</a>';
            } else {
                echo '';
            }
        }

        echo '</div>';
        echo $footer;
    }

    if (isset($settings['allow_subject_cancellation']) && $settings['allow_subject_cancellation']=='y') {
        foreach ($registered as $s) {
            $s['cancellation_deadline']=sessions__get_cancellation_deadline($s);
            if ($s['cancellation_deadline']>time()) {
                echo '<div data-role="page" id="can'.$s['session_id'].'" data-dialog="true">';
                echo '<div data-role="header" data-theme="a">
                    <h1>'.lang('session_enrolment_cancellation').'</h1>
                    <a href="#reg'.$s['session_id'].'" class="ui-btn-left">'.lang('back').'</a>
                    </div>';
                echo '<div data-role="content">';
                show_message();
                echo '<h3 class="ui-bar ui-bar-a">'.lang('do_you_really_want_to_cancel_session_enrolment').'</h3>';
                echo '<strong>'.lang('experiment').':</strong><br>'.$s['experiment_public_name'].'<br>';
                if (or_setting('allow_public_experiment_note') && isset($s['public_experiment_note']) && trim($s['public_experiment_note'])) {
                    echo '<i>'.lang('note').': '.trim($s['public_experiment_note']).'</i><br>';
                }
                echo '<strong>'.lang('date_and_time').':</strong><br>'.$s['session_name'].'<br>';
                if (or_setting('allow_public_session_note') && isset($s['public_session_note']) && trim($s['public_session_note'])) {
                    echo '<i>'.lang('note').': '.trim($s['public_session_note']).'</i><br>';
                }
                echo '<strong>'.lang('laboratory').':</strong><br>'.$labs[$s['laboratory_id']]['lab_name'].'<br>'.$labs[$s['laboratory_id']]['lab_address'].'<br>';
                echo '<form id="form-can'.$s['session_id'].'" method="post" data-ajax="false">
                            <INPUT type=hidden name="s" value="'.$s['session_id'].'">';
                if ($token_string) echo '<INPUT type=hidden name="p" value="'.$participant['participant_id_crypt'].'">';
                echo '<INPUT type="hidden" name="cancel" value="true">';
                echo '<input data-icon="check" type="submit" id="submit-can'.$s['session_id'].'" value="'.lang('yes_i_want').'">';
                echo '<a href="#reg'.$s['session_id'].'" class="ui-btn ui-icon-back ui-btn-icon-left">'.lang('no_sorry').'</a>';
                echo '</form>';
                echo '</div>';
                echo $footer;
            }
        }
    }


    echo '
    <!-- participated -->
    <div data-role="page" id="participated">
        <div data-role="header">
            <h1>'.lang('mobile_past_enrolments').'</h1>
            <a href="#indexPage" class="ui-btn-left">'.lang('back').'</a>
        </div>
        <div data-role="content">
            '.lang('registered_for').' '.$participant['number_reg'].'<BR>
            '.lang('not_shown_up').' '.$participant['number_noshowup'].' ';

        if (count($history)>0) echo '<ul data-role="listview" data-theme="a" data-inset="true">';

        $pstatuses=expregister__get_participation_statuses();
        foreach ($history as $s) {
            echo '<li><strong>'.$s['session_name'].'</strong><br>
            '.lang('experiment').': '.$s['experiment_public_name'].'<br>
            '.$labs[$s['laboratory_id']]['lab_name'].'<br>
            '.lang('showup?').' ';
            if ($s['session_status']=="completed" || $s['session_status']=="balanced") {
                if ($pstatuses[$s['pstatus_id']]['noshow']) {
                    $tcolor=$color['shownup_no'];
                    //$ttext=lang('no');
                } else {
                    $tcolor=$color['shownup_yes'];
                    //$ttext=lang('yes');
                }
                $ttext=$pstatuses[$s['pstatus_id']]['display_name'];
                echo '<FONT color="'.$tcolor.'"><strong>'.$ttext.'</strong></FONT>';
            } else echo '<FONT color="grey"><strong>'.lang('three_questionmarks').'</strong></FONT>';
            echo '</li>';
        }
        if (count($history)>0) echo '</ul>';
        else echo '<h4 class="ui-bar ui-bar-a">'.lang('mobile_no_past_enrolments').'</h4>';

    echo '</div>';

    //echo $footer;

    html__mobile_footer();

}
?>
