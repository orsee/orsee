<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="my_registrations";
$title="experiments";
include("header.php");
if ($proceed) {
    if ($settings['enable_mobile_pages']=='y') {
        $useragent=$_SERVER['HTTP_USER_AGENT'];
        if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4)))
            redirect("public/participant_show_mob.php".$token_string);
    }
}
if ($proceed) {
    if (isset($_REQUEST['s']) && $_REQUEST['s']) $session_id=trim($_REQUEST['s']); else $session_id="";

    if (isset($_REQUEST['register']) && $_REQUEST['register']) {
        $continue=true;

        if (isset($_REQUEST['betternot']) && $_REQUEST['betternot']) {
            redirect("public/participant_show.php".$token_string);
        }

        if ($proceed) {
            if (!$session_id) {
                $continue=false;
                log__participant("interfere enrolment - no session_id",$participant_id);
                message(lang('error_session_id_register'));
                redirect("public/participant_show.php".$token_string);
            }
        }
        if ($proceed) {
            $session=orsee_db_load_array("sessions",$session_id,"session_id");
            if (!isset($session['session_id'])) {
                log__participant("interfere enrolment - invalid session_id",$participant_id);
                message(lang('error_session_id_register'));
                redirect("public/participant_show.php".$token_string);
            }
        }
        if ($proceed) {
            $participate_at=expregister__get_participate_at($participant_id,$session['experiment_id']);
            if (!isset($participate_at['session_id'])) {
                $continue=false;
                redirect("public/participant_show.php".$token_string);
            }
        }
        if ($proceed) {
            if ($settings['enable_enrolment_only_on_invite']=='y') {
                if (!$participate_at['invited']) {
                    $continue=false;
                    redirect("public/participant_show.php".$token_string);
                }
            }
        }

        if ($proceed) {
            if (isset($participate_at['session_id']) && $participate_at['session_id']>0) {
                $continue=false;
                message(lang('error_already_registered'));
                redirect("public/participant_show.php".$token_string);
            }
        }

        if ($proceed) {
            $registration_end=sessions__get_registration_end($session);
            $full=sessions__session_full($session_id,$session);
            $now=time();
            if ($registration_end < $now) {
                $continue=false;
                message(lang('error_registration_expired'));
                redirect("public/participant_show.php".$token_string);
            }
        }

        if ($proceed) {
            if ($full) {
                 $continue=false;
                 message(lang('error_session_complete'));
                 redirect("public/participant_show.php".$token_string);
            }
        }

        if ($proceed) {
            if (isset($_REQUEST['reallyregister']) && $_REQUEST['reallyregister']) {
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
                    $redir="public/participant_show.php".$token_string;
                    if ($token_string) $redir.="&"; else $redir.="?";
                    $redir.="s=".$session_id;
                    redirect($redir);
                }
            } else {
                echo '<center><TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'">
                        <TR><TD align="center">
                            '.lang('experiment_registration').'
                        </TD></TABLE>';

                echo '<BR><BR>
                    <form action="participant_show.php">
                    <INPUT type=hidden name="s" value="'.$_REQUEST['s'].'">';

                if ($token_string) echo '<INPUT type=hidden name="p" value="'.$participant['participant_id_crypt'].'">';
                echo '<INPUT type=hidden name="register" value="true">
                    <TABLE class="or_formtable">
                    <TR>
                        <TD colspan=2 align=center>
                            <B>'.lang('do_you_really_want_to_register_for_experiment').'</B>
                        </TD>
                    </TR>
                    <TR>
                        <TD>
                            '.lang('experiment').':
                        </TD>
                        <TD>
                            '.experiment__get_public_name($session['experiment_id']).'
                        </TD>
                    </TR>
                    <TR>
                        <TD>
                            '.lang('date_and_time').':
                        </TD>
                        <TD>
                            '.session__build_name($session).'
                        </TD>
                    </TR>
                    <TR>
                        <TD>
                            '.lang('laboratory').':
                        </TD>
                        <TD>
                            '.laboratories__get_laboratory_name($session['laboratory_id']).'
                        </TD>
                    </TR>
                    <TR>
                        <TD colspan=2>&nbsp;</TD>
                    </TR>
                    <TR>
                        <TD align=center colspan=2>
                            <INPUT class="button" type=submit name="reallyregister" value="'.lang('yes_i_want').'">
                            &nbsp;&nbsp;&nbsp;&nbsp;
                            <INPUT class="button" type=submit name="betternot" value="'.lang('no_sorry').'">
                        </TD>
                    </TR>
                    </TABLE>
                    </FORM>
                    </center>

                    ';
            }
        }

    } elseif (isset($_REQUEST['cancel']) && $_REQUEST['cancel'] &&
            isset($settings['allow_subject_cancellation']) && $settings['allow_subject_cancellation']=='y') {
        $continue=true;

        if (isset($_REQUEST['betternot']) && $_REQUEST['betternot']) {
            redirect("public/participant_show.php".$token_string);
        }

        if ($proceed) {
            if (!$session_id) {
                $continue=false;
                log__participant("interfere enrolment cancellation- no session_id",$participant_id);
                message(lang('error_session_id_register'));
                redirect("public/participant_show.php".$token_string);
            }
        }
        if ($proceed) {
            $session=orsee_db_load_array("sessions",$session_id,"session_id");
            if (!isset($session['session_id'])) {
                log__participant("interfere enrolment cancellation - invalid session_id",$participant_id);
                message(lang('error_session_id_register'));
                redirect("public/participant_show.php".$token_string);
            }
        }
        if ($proceed) {
            $participate_at=expregister__get_participate_at($participant_id,$session['experiment_id']);
            if (!isset($participate_at['session_id']) || $participate_at['session_id']!=$session_id) {
                $continue=false;
                redirect("public/participant_show.php".$token_string);
            }
        }

        if ($proceed) {
            $cancellation_deadline=sessions__get_cancellation_deadline($session);
            $now=time();
            if ($cancellation_deadline < $now) {
                $continue=false;
                message(lang('error_enrolment_cancellation_deadline_expired'));
                redirect("public/participant_show.php".$token_string);
            }
        }

        if ($proceed) {
            if (isset($_REQUEST['reallycancel']) && $_REQUEST['reallycancel']) {
                // if all checks are done, register ...
                if ($continue) {
                    $done=expregister__cancel($participant,$session);
                    $done=participant__update_last_enrolment_time($participant_id);
                    $done=log__participant("cancel_session_enrolment",$participant['participant_id'],
                        "experiment_id:".$session['experiment_id']."\nsession_id:".$session_id);
                    message(lang('successfully_canceled_enrolment_xxx')." ".
                        experiment__get_public_name($session['experiment_id']).", ".
                        session__build_name($session).". "
                        .lang('this_will_be_confirmed_by_an_email')
                        );
                    redirect("public/participant_show.php".$token_string);
                }
            } else {
                echo '<center>';

                echo '<TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'">
                        <TR><TD align="center">
                            '.lang('session_enrolment_cancellation').'
                        </TD>';

                echo '<BR><BR>
                    <form action="participant_show.php">
                    <INPUT type="hidden" name="s" value="'.$_REQUEST['s'].'">';

                if ($token_string) echo '<INPUT type="hidden" name="p" value="'.$participant['participant_id_crypt'].'">';
                echo '<INPUT type=hidden name="cancel" value="true">
                    <TABLE style="outline: 1px solid black;">
                    <TR>
                        <TD colspan=2 align=center>
                            <B>'.lang('do_you_really_want_to_cancel_session_enrolment').'</B>
                        </TD>
                    </TR>
                    <TR>
                        <TD>
                            '.lang('experiment').':
                        </TD>
                        <TD>
                            '.experiment__get_public_name($session['experiment_id']).'
                        </TD>
                    </TR>
                    <TR>
                        <TD>
                            '.lang('date_and_time').':
                        </TD>
                        <TD>
                            '.session__build_name($session).'
                        </TD>
                    </TR>
                    <TR>
                        <TD>
                            '.lang('laboratory').':
                        </TD>
                        <TD>
                            '.laboratories__get_laboratory_name($session['laboratory_id']).'
                        </TD>
                    </TR>
                    <TR>
                        <TD colspan=2>&nbsp;</TD>
                    </TR>
                    <TR>
                        <TD align=center colspan=2>
                            <INPUT class="button" type=submit name="reallycancel" value="'.lang('yes_i_want').'">
                            &nbsp;&nbsp;&nbsp;&nbsp;
                            <INPUT class="button" type=submit name="betternot" value="'.lang('no_sorry').'">
                        </TD>
                    </TR>
                    </TABLE>
                    </FORM>
                    </center>

                    ';
            }
        }
    } else {

        if (!isset($preloaded_laboratories)) $preloaded_laboratories=laboratories__get_laboratories();
        echo '<center>';

        echo '<p align="right"><TABLE border=0>';
        echo '<TR><TD>'.button_link('participant_edit.php'.$token_string,
                            lang('edit_your_profile'),'pencil-square-o').'</TD></TR>';
        echo '</TABLE></P>';

        echo '<table border="0" width="90%">';
        echo '<TR><TD>
                <TABLE width="100%" class="or_panel" style="width: 100%;">
                    <TR><TD>
                        <TABLE width="100%" border=0 class="or_panel_title"><TR>
                            <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">
                                '.lang('experiments_you_are_invited_for').'
                            </TD>
                        </TR></TABLE>
                    </TD></TR>
                    <TR><TD>
                        '.lang('please_check_availability_before_register').'
                    </TD></TR>
                    <TR><TD>';
        $labs=expregister__list_invited_for($participant);
        echo '      </TD></TR>
                </TABLE>
            </TD></TR>';
        echo '<TR><TD>&nbsp;</TD></TR>';
        echo '<TR><TD>
                <TABLE width="100%" class="or_panel" style="width: 100%;">
                    <TR><TD>
                        <TABLE width="100%" border=0 class="or_panel_title"><TR>
                            <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">
                                '.lang('experiments_already_registered_for').'
                            </TD>
                        </TR></TABLE>
                    </TD></TR>
                    <TR><TD>';
        $labs2=expregister__list_registered_for($participant,$session_id);
        echo '      </TD></TR>
                </TABLE>
            </TD></TR>';
        echo '<TR><TD>&nbsp;</TD></TR>';
        $laboratories=array_unique(array_merge($labs,$labs2));
        if (count($laboratories)>0) {
            echo '<TR><TD>
                    <table border="0" class="or_panel" style="width: 100%;">
                        <TR><TD colspan=2><B>';
            if (count($laboratories)==1) echo lang('laboratory_address');
            else echo lang('laboratory_addresses');
            echo '      </B></TD></TR>';
            foreach ($laboratories as $laboratory_id) {
                if (isset($preloaded_laboratories[$laboratory_id])) {
                    echo '<TR><TD valign=top>';
                    echo $preloaded_laboratories[$laboratory_id]['lab_name'];
                    echo '</TD><TD>';
                    $address=$preloaded_laboratories[$laboratory_id]['lab_address'];
                    echo str_replace("\n","<BR>",$address);
                    echo '</TD></TR>';
                }
            }
            echo '  </table>
                </TD></TR>';
            echo '<TR><TD>&nbsp;</TD></TR>';
        }
        echo '<TR><TD>
                <TABLE width="100%" class="or_panel" style="width: 100%;">
                    <TR><TD>
                        <TABLE width="100%" border=0 class="or_panel_title"><TR>
                            <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">
                                '.lang('experiments_you_participated').'
                            </TD>
                        </TR></TABLE>
                    </TD></TR>
                    <TR><TD>
                        '.lang('registered_for').' '.$participant['number_reg'].',
                        '.lang('not_shown_up').' '.$participant['number_noshowup'].'
                </TD></TR>
                    <TR><TD>';
        expregister__list_history($participant);
        echo '      </TD></TR>
                </TABLE>
            </TD></TR>';
        echo '</TABLE>';

        echo '</center>';
    }

}
include("footer.php");
?>