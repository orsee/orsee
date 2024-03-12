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
                    session__build_name($session).". ".
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

    html__mobile_header();

    // GENERAL NAVIGATION SPLIT IN TOOLBAR AND TABBAR
    echo '
    <ons-navigator id="appNavigator" swipeable swipe-target-width="80px">
      <ons-page>
        <ons-splitter id="appSplitter">
          <ons-splitter-content page="tabbar.html"></ons-splitter-content>
        </ons-splitter>
      </ons-page>
    </ons-navigator>';
    
    // TOP AND BOTTOM NAVIGATION
    echo '
    <template id="tabbar.html">
      <ons-page id="tabbar-page">
        <ons-toolbar>
          <div class="center">'.lang('mobile_invitations').'</div>
          <div class="right">
          <ons-toolbar-button>
                <ons-icon icon="fa-sign-out"></ons-icon>
                <span onclick="fn.loadLink(\'participant_logout.php?mobile=true\')">'.lang('logout').'</a>
            </ons-toolbar-button>
          </div>
        </ons-toolbar>
        <ons-tabbar swipeable id="appTabbar" position="auto">
          <ons-tab class="tab-with-badge1" label="'.lang('mobile_invitations').'" icon="fa-inbox" page="invitations.html" active></ons-tab>
          <ons-tab class="tab-with-badge2" label="'.lang('mobile_enrolments').'" icon="fa-calendar" page="registered.html"></ons-tab>
          <ons-tab class="tab-with-badge3" label="'.lang('mobile_history').'" icon="fa-list-alt" page="participated.html"></ons-tab>
        </ons-tabbar>
        
        ';

    // SCRIPT TO HANDLE PAGE NAMES FROM TABBAR NAVIGATION
echo '<script>
            ons.getScriptPage().addEventListener("prechange", function(event) {
                if (event.target.matches("#appTabbar")) {
                    event.currentTarget.querySelector("ons-toolbar .center").innerHTML = event.tabItem.getAttribute("label");
                }
            });
    </script>';

echo '        
      </ons-page>
    </template>
 ';

    
    // INVITATIONS PAGE (MAIN PAGE)
    echo '<template id="invitations.html">';
    echo '<ons-page id="invitations-page">';
    echo '
    <p class="intro">
      '.lang('please_check_availability_before_register').'
    </p>';
   
    $first=true; $now=time();
    foreach ($invited as $s) {
        if ($s['new_experiment']) {
            if (!$first) echo '</ons-list>';
            else $first=false;
            echo '<ons-list modifier="inset"><ons-list-header>'.$s['experiment_public_name'].'</ons-list-header>';
            if (or_setting('allow_public_experiment_note') && isset($s['public_experiment_note']) && trim($s['public_experiment_note'])) {
                echo '<ons-list-item>'.lang('note').': '.trim($s['public_experiment_note']).'</ons-list-item>';
            }
        }
        if ((!$s['session_full']) && ($s['registration_unixtime'] >= $now)) {
            echo '<ons-list-item tappable modifier="inset chevron" '
                    .'onclick="fn.pushPage({\'id\': \'session_'.$s['session_id'].'.html\'})">'
                    .'<span class="list-item__title">'.$s['session_name'].'</span>'
                    .'<span class="list-item__subtitle">'.$labs[$s['laboratory_id']]['lab_name'].'</span>';
            if (or_setting('allow_public_session_note') && isset($s['public_session_note']) && trim($s['public_session_note'])) {
                echo '<span class="list-item__subtitle">'.lang('note').': '.trim($s['public_session_note']).'</span>';
            }
            echo '</ons-list-item>';
        } elseif ($s['registration_unixtime'] < $now) {
            echo '<ons-list-item>
                    <span class="list-item__title">'.$s['session_name'].'</span>'
                    .'<span class="list-item__subtitle">'.$labs[$s['laboratory_id']]['lab_name'].'</span>'
                    .'<span class="list-item__subtitle" style="color: '.$color['session_public_expired'].';">'.lang('expired').'</span>'
                    .'</ons-list-item>';
        } else {
            echo '<ons-list-item>
                    <span class="list-item__title">'.$s['session_name'].'</span>'
                    .'<span class="list-item__subtitle">'.$labs[$s['laboratory_id']]['lab_name'].'</span>'
                    .'<span class="list-item__subtitle" style="color: '.$color['session_public_complete'].';">'.lang('complete').'</span>'
                    .'</ons-list-item>';
        }
    }
    
    if (count($invited)>0) {
        echo '</ons-list>';
    } else {
        echo '<ons-card>';
        echo '    <div class="content">'.lang('mobile_no_current_invitations').'</div>';
        echo '      </ons-card>';
    }
    
echo '
      </ons-page>
    </template>
';

    // INDIVIDUAL ENROLMENT PAGES
    foreach ($invited as $s) {
        echo '
        <template id="session_'.$s['session_id'].'.html">
            <ons-page id="session_'.$s['session_id'].'-page">
                <ons-toolbar>
                  <div class="left">
                    <ons-back-button>'.lang('back').'</ons-back-button>
                  </div>
                  <div class="center">'.lang('mobile_session_details').'</div>
                </ons-toolbar>';

        echo '<form action="'.thisdoc().'" method="get" id="form-'.$s['session_id'].'">';
        echo '<INPUT type=hidden name="s" value="'.$s['session_id'].'">';
        if ($token_string) echo '<INPUT type=hidden name="p" value="'.$participant['participant_id_crypt'].'">';
        echo '<INPUT type="hidden" name="register" value="true">';
        echo '<INPUT type="submit" id="regsubmit-'.$s['session_id'].'" style="display: none;">';

        echo '<ons-list modifier="inset">';
        echo '<ons-list-header>'.lang('mobile_you_can_enroll_for').'</ons-list-header>';
        echo '<ons-list-item>
                <span class="list-item__title">'.lang('experiment').':</span>
                <span class="list-item__subtitle">'.$s['experiment_public_name'].'</span>
              </ons-list-item>';
        if (or_setting('allow_public_experiment_note') && isset($s['public_experiment_note']) && trim($s['public_experiment_note'])) {
            echo '  <ons-list-item>
                    <span class="list-item__subtitle">'.lang('note').': '.trim($s['public_experiment_note']).'</span>
                    </ons-list-item>';
        }
        echo '<ons-list-item>
                <span class="list-item__title">'.lang('date_and_time').':</span>
                <span class="list-item__subtitle">'.$s['session_name'].'</span>
            </ons-list-item>';
        if (or_setting('allow_public_session_note') && isset($s['public_session_note']) && trim($s['public_session_note'])) {
            echo '  <ons-list-item>
                        <span class="list-item__subtitle">'.lang('note').': '.trim($s['public_session_note']).'</span>
                    </ons-list-item>';
        }
        echo '<ons-list-item>
                <span class="list-item__title">'.lang('laboratory').':</span>'
                .'<span class="list-item__subtitle">'.$labs[$s['laboratory_id']]['lab_name'].'</span>'
                .'<span class="list-item__subtitle">'.$labs[$s['laboratory_id']]['lab_address'].'</span>
                </ons-list-item>';
        echo '<ons-list-item>';
        echo '<ons-button type="submit" ripple onclick="fn.submitRegForm(\'regsubmit-'.$s['session_id'].'\');">'.lang('mobile_sign_up').'</ons-button>';
        echo '</ons-list-item>';
        echo '</ons-list>';
                
        echo '</form>';
        echo '</ons-page>';
        echo '</template>';
    }

// CURRENT ENROLMENTS PAGE
    echo '
    <template id="registered.html">
      <ons-page id="registered-page">';

    
        if (count($registered)>0) {
            echo '<ons-list modifier="inset">';
        }
        foreach ($registered as $s) {
            echo '<ons-list-item tappable modifier="chevron" '
                    .'onclick="fn.pushPage({\'id\': \'reg_'.$s['session_id'].'.html\'})">'
                    .'<span class="list-item__title">'.$s['session_name'].'</span>'
                    .'<span class="list-item__subtitle">'.lang('experiment').': '.$s['experiment_public_name'].'</span>'
                    .'<span class="list-item__subtitle">'.$labs[$s['laboratory_id']]['lab_name'].'</span>';
            echo '</ons-list-item>';
        }
        if (count($registered)>0) {
            echo '</ons-list>';
        } else {
            echo '<ons-card>';
            echo '<div class="content">'.lang('mobile_no_current_registrations').'</div>';
            echo '</ons-card>';
        }
        echo '</ons-page>';
        echo '</template>';

// REGISTRATION DETAILS AND CANCELLATION PAGES

     foreach ($registered as $s) {
        echo '
        <template id="reg_'.$s['session_id'].'.html">
            <ons-page id="reg_'.$s['session_id'].'-page">
                <ons-toolbar>
                  <div class="left">
                    <ons-back-button>'.lang('back').'</ons-back-button>
                  </div>
                  <div class="center">'.lang('mobile_session_details').'</div>
                </ons-toolbar>';
        
        echo '<ons-list modifier="inset">';
        echo '<ons-list-header>'.lang('mobile_you_are_enrolled_for').'</ons-list-header>';
        echo '<ons-list-item>
                <span class="list-item__title">'.lang('experiment').':</span>
                <span class="list-item__subtitle">'.$s['experiment_public_name'].'</span>
              </ons-list-item>';
        if (or_setting('allow_public_experiment_note') && isset($s['public_experiment_note']) && trim($s['public_experiment_note'])) {
            echo '  <ons-list-item>
                    <span class="list-item__subtitle">'.lang('note').': '.trim($s['public_experiment_note']).'</span>
                    </ons-list-item>';
        }
        echo '<ons-list-item>
                <span class="list-item__title">'.lang('date_and_time').':</span>
                <span class="list-item__subtitle">'.$s['session_name'].'</span>
            </ons-list-item>';
        if (or_setting('allow_public_session_note') && isset($s['public_session_note']) && trim($s['public_session_note'])) {
            echo '  <ons-list-item>
                        <span class="list-item__subtitle">'.lang('note').': '.trim($s['public_session_note']).'</span>
                    </ons-list-item>';
        }
        echo '<ons-list-item>
                <span class="list-item__title">'.lang('laboratory').':</span>'
                .'<span class="list-item__subtitle">'.$labs[$s['laboratory_id']]['lab_name'].'</span>'
                .'<span class="list-item__subtitle">'.$labs[$s['laboratory_id']]['lab_address'].'</span>
                </ons-list-item>';

      if (isset($settings['allow_subject_cancellation']) && $settings['allow_subject_cancellation']=='y') {
            $s['cancellation_deadline']=sessions__get_cancellation_deadline($s);
            if ($s['cancellation_deadline']>time()) {
                echo '<form action="'.thisdoc().'" method="get" id="cancel-'.$s['session_id'].'">';
                echo '<INPUT type=hidden name="s" value="'.$s['session_id'].'">';
                if ($token_string) echo '<INPUT type=hidden name="p" value="'.$participant['participant_id_crypt'].'">';
                echo '<INPUT type="hidden" name="cancel" value="true">';
                echo '<INPUT type="submit" id="cancelsubmit-'.$s['session_id'].'" style="display: none;">';
        
                echo '<ons-list-item>';
                echo '<ons-button style="background-color: #AA0000; color: white;" type="submit" ripple onclick="fn.submitCancelForm(\'cancelsubmit-'.$s['session_id'].'\');">'.lang('mobile_cancel_signup').'</ons-button>';
                echo '</ons-list-item>';
                echo '</form>';
            }
        }

        echo '</ons-list>';
        echo '</ons-page>';
        echo '</template>';
    }

// HISTORY PAGE
    echo '
    <template id="participated.html">
      <ons-page id="participated-page">';

        $pstatuses=expregister__get_participation_statuses();
        $payment_types=payments__load_paytypes();

        echo '<ons-card>';
        echo '<div class="content">'.lang('registered_for').' '.$participant['number_reg'].'</div>';
        echo '<div class="content">'.lang('not_shown_up').' '.$participant['number_noshowup'].'</div>';
        echo '</ons-card>';
              
        if (count($history)>0) {
            echo '<ons-list modifier="inset">';
        }

        foreach ($history as $s) {
            echo '<ons-list-item>'
                    .'<span class="list-item__title">'.$s['session_name'].'</span>'
                    .'<span class="list-item__subtitle">'.lang('experiment').': '.$s['experiment_public_name'].'</span>'
                    .'<span class="list-item__subtitle">'.$labs[$s['laboratory_id']]['lab_name'].'</span>';
            echo '<span class="list-item__subtitle">'.lang('showup?').' ';
            if ($s['session_status']=="completed" || $s['session_status']=="balanced") {
                if ($pstatuses[$s['pstatus_id']]['noshow']) {
                    $tcolor=$color['shownup_no'];
                } else {
                    $tcolor=$color['shownup_yes'];
                }
                $ttext=$pstatuses[$s['pstatus_id']]['display_name'];
                echo '<span style="color: '.$tcolor.'; font-weight: bold;">'.$ttext.'</span>';
            } else {
                echo '<span style="color: grey; font-weight: bold;">'.lang('three_questionmarks').'</span>';
            }
            echo '</span>';
            if ($settings['enable_payment_module']=='y' && $settings['payments_in_part_history']=='y' &&
                $s['session_status']=="balanced") {
                echo '<span class="list-item__subtitle">'.lang('payment_type_abbr').': ';
                if (isset($payment_types[$s['payment_type']])) {
                    echo $payment_types[$s['payment_type']]; 
                } else {
                    echo '-';
                }
                echo ', '.lang('payment_amount_abbr').': ';
                if ($s['payment_amt']!='') {
                    echo $s['payment_amt'];
                } else {
                    echo '-';
                }
                echo '</span>';
            }
            echo '</ons-list-item>';
        }
        if (count($history)>0) {
            echo '</ons-list>';
        } else {
            echo '<ons-card>';
            echo '<div class="content">'.lang('mobile_no_past_enrolments').'</div>';
            echo '</ons-card>';
        }

        echo '</ons-page>';
        echo '</template>';

    // CSS FOR badges on tabbar icons        
    echo '<style>';
    if (count($invited)>0) {
        echo '
            .tab-with-badge1 ons-icon::after {
                content: "'.count($invited).'"; /* Badge number */
                position: relative;
                top: -27px;
                right: -25px;
                font-size: 12px;
                background: red;
                color: white;
                width: 18px;
                height: 18px;
                border-radius: 50%;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            ';
        }
    if (count($registered)>0) {
        echo '
            .tab-with-badge2 ons-icon::after {
                content: "'.count($registered).'"; /* Badge number */
                position: relative;
                top: -27px;
                right: -25px;
                font-size: 12px;
                background: #0076ff;
                color: white;
                width: 18px;
                height: 18px;
                border-radius: 50%;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            ';
        }

    if (count($history)>0) {
        echo '
            .tab-with-badge3 ons-icon::after {
                content: "'.count($history).'"; /* Badge number */
                position: relative;
                top: -27px;
                right: -25px;
                font-size: 12px;
                background: #999;
                color: white;
                width: 18px;
                height: 18px;
                border-radius: 50%;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            ';
        }
        
echo '</style>';

    // SOME STYLING FOR ALL LISTS
    echo '
    <style>
      .intro {
        text-align: center;
        padding: 0 20px;
        margin-top: 20px;
      }

      ons-list-item {
        cursor: pointer;
        color: #333;
      }

      .list-item__title {
        font-size: 17px;
      }
      
      .list-header {
            font-size: 18px;
            line-height: 25px;
        }
    </style>';


echo '<script>

window.fn = {};

window.fn.loadLink = function (url) {
  window.location.assign(url);
};

window.fn.pushPage = function (page, anim) {
  if (anim) {
    document.getElementById("appNavigator").pushPage(page.id, {animation: anim });
  } else {
    document.getElementById("appNavigator").pushPage(page.id);
  }
};

window.fn.submitRegForm = function (submit_id) {
    ons.notification.confirm({
        message: "'.lang('mobile_do_you_really_want_to_signup').'",
        title: "'.lang('mobile_confirmation').'",
        buttonLabels: ["'.lang('mobile_sorry_no').'", "'.lang('mobile_yes_please').'"],
        callback: function(index) {
            if (index === 1) { // OK button
                document.getElementById(submit_id).click();
            }
        }
    });
};

window.fn.submitCancelForm = function (submit_id) {
    ons.notification.confirm({
        message: "'.lang('mobile_do_you_really_want_to_cancel_signup').'",
        title: "Confirmation",
        buttonLabels: ["'.lang('mobile_sorry_no').'", "'.lang('mobile_yes_please').'"],
        callback: function(index) {
            if (index === 1) { // OK button
                document.getElementById(submit_id).click();
            }
        }
    });
};

';

if (isset($message_text) && $message_text) {

    echo '
            document.addEventListener("DOMContentLoaded", function() {
                setTimeout(function() {
                    ons.notification.toast(\''.$message_text.'\', {
                        timeout: 2000, // The toast stays for 2000ms
                        animation: "fall" // Optional: Animation style
                    });
                }, 500); // 500ms delay
            });
';

}

echo '
</script>';



    html__mobile_footer();

}
?>
