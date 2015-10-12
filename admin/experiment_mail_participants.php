<?php
// part of orsee. see orsee.org
ob_start();
$title="send_invitations";

include ("header.php");
if ($proceed) {
    if ($_REQUEST['experiment_id']) $experiment_id=$_REQUEST['experiment_id'];
            else redirect ("admin/");
}

if ($proceed) {
    $allow=check_allow('experiment_invitation_edit','experiment_show.php?experiment_id='.$experiment_id);
}

if ($proceed) {
    if (isset($_REQUEST['id'])) $id=$_REQUEST['id']; else $id="";

    if (isset($_REQUEST['preview']) && $_REQUEST['preview']) $preview=true; else $preview=false;
    if (isset($_REQUEST['save']) && $_REQUEST['save']) $save=true; else $save=false;
    if (isset($_REQUEST['send']) && $_REQUEST['send']) $send=true; else $send=false;
    if (isset($_REQUEST['sendall']) && $_REQUEST['sendall']) $sendall=true; else $sendall=false;

    if ($preview || $save || $send || $sendall) $action=true; else $action=false;


    $experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
    if (!check_allow('experiment_restriction_override'))
        check_experiment_allowed($experiment,"admin/experiment_show.php?experiment_id=".$experiment_id);
}

if ($proceed) {
    // load invitation languages
    $inv_langs=lang__get_part_langs();
    $installed_langs=get_languages();


    echo '<center>';
    echo '<TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'; width: 80%;">
            <TR><TD align="center">
            '.$experiment['experiment_name'].'
            </TD>';
    echo '</TR></TABLE>';


    if ($action) {

        $sitem=$_REQUEST;
        $sitem['content_type']='experiment_invitation_mail';
        $sitem['content_name']=$experiment_id;

        // prepare lang stuff
        foreach ($inv_langs as $inv_lang) {
            $sitem[$inv_lang]=$sitem[$inv_lang.'_subject']."\n".$sitem[$inv_lang.'_body'];
        }

        // well: just to be sure: for all other languages, copy the public default lang
        foreach ($installed_langs as $inst_lang) {
            if (!in_array($inst_lang,$inv_langs)) $sitem[$inst_lang]=$sitem[$settings['public_standard_language']];
        }

        // is unknown or known?
        if (!$id) $done=lang__insert_to_lang($sitem);
        else $done=orsee_db_save_array($sitem,"lang",$id,"lang_id");

        if ($done) message (lang('changes_saved'));
        else message (lang('database_error'));

        if ($preview) {
            redirect ('admin/experiment_mail_preview.php?experiment_id='.$experiment_id);
        } elseif ($send || $sendall) {
            // send mails!

            $allow=check_allow('experiment_invite_participants','experiment_mail_participants.php?experiment_id='.$experiment_id);

            if ($allow) {
                $whom= ($sendall) ? "all" : "not-invited";
                $measure_start=getmicrotime();
                $sent=experimentmail__send_invitations_to_queue($experiment_id,$whom);
                message ($sent.' '.lang('xxx_inv_mails_added_to_mail_queue'));
                $measure_end=getmicrotime();
                message(lang('time_needed_in_seconds').': '.round(($measure_end-$measure_start),5));
                log__admin("experiment_send_invitations","experiment:".$experiment['experiment_name']);
                redirect ("admin/experiment_mail_participants.php?experiment_id=".$experiment_id);
            }

        } else {
            message(lang('mail_text_saved'));
            log__admin("experiment_edit_invitation_mail","experiment:".$experiment['experiment_name']);
            redirect ('admin/'.thisdoc().'?experiment_id='.$experiment_id);
        }
    }
}

if ($proceed) {
    $pars=array(':experiment_id'=>$experiment_id);
    $query="SELECT * from ".table('lang')."
            WHERE content_type='experiment_invitation_mail'
            AND content_name= :experiment_id";
    $experiment_mail=orsee_query($query,$pars);

    if (!isset($experiment_mail['lang_id'])) {
        $experiment_mail=array('lang_id'=>'');
        foreach ($inv_langs as $inv_lang) $experiment_mail[$inv_lang]='';
    }

    // form

     echo '<FORM action="'.thisdoc().'" method="post">
            <INPUT type=hidden name="experiment_id" value="'.$experiment_id.'">
            <INPUT type=hidden name="id" value="'.$experiment_mail['lang_id'].'">

            <TABLE class="or_formtable" style="width: 80%;">';

    foreach ($inv_langs as $inv_lang) {
        // split in subject and text
        $subject=str_replace(strstr($experiment_mail[$inv_lang],"\n"),"",$experiment_mail[$inv_lang]);
        $body=substr($experiment_mail[$inv_lang],strpos($experiment_mail[$inv_lang],"\n")+1,strlen($experiment_mail[$inv_lang]));

        // set defaults if not existent
        if (!$subject) {
            $subject=load_language_symbol('def_expmail_subject',$inv_lang);
        }

        if (!$body) {
            $body=load_mail('default_invitation_'.$experiment['experiment_type'],$inv_lang);
        }

        if (count($inv_langs) > 1) {
            echo '<TR><TD colspan=2>
                        <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">
                            '.$inv_lang.':
                        </TD>
                        </TR></TABLE>
                    </TD></TR>';
        }

        echo '
            <TR>
                <TD>
                    '.lang('subject').':
                </TD>
                <TD>
                    <INPUT type=text name="'.$inv_lang.'_subject" size=30 maxlength=80 value="'.
                        stripslashes($subject).'">
                </TD>
            </TR>
                    <TR>
                <TD valign=top colspan=2>
                    '.lang('body_of_message').':<BR>
                    <FONT class="small">'.lang('experimentmail_how_to_rebuild_default').'</FONT>
                    <BR>

                    <center>
                    <textarea name="'.$inv_lang.'_body" wrap=virtual rows=20 cols=50>'.
                        stripslashes($body).'</textarea>
                    </center>
                </TD>
            </TR>';

        echo ' <TR><TD colspan=2>&nbsp;</TD></TR>';

    }

    echo '
            <TR><TD colspan=2>
                <TABLE class="or_option_buttons_box" style="background: '.$color['options_box_background'].';">
                <TR><TD colspan="2" align="left">
                    1. '.lang('save_mail_text_only').'
                </TD></TR>
                <TR class="empty"><TD align="left">
                    <INPUT class="button" type=submit name="preview" class="small" value="'.lang('mail_preview').'">
                </TD><TD align="right">
                    <INPUT class="button" type=submit name="save" value="'.lang('save').'">
                </TD></TR>
                </TABLE>
            </TD></TR>
            <TR>
                <TD colspan=2>
                    <TABLE class="or_option_buttons_box" style="background: '.$color['options_box_background'].';">
                    <TR>
                    <TD>'.lang('assigned_subjects').': '.experiment__count_participate_at($experiment_id).'</TD>
                    <TD>'.lang('invited_subjects').': '.experiment__count_participate_at($experiment_id,"","invited = :invited",array(':invited'=>1)).'</TD>
                    <TD>'.lang('registered_subjects').': '.experiment__count_participate_at($experiment_id,"","session_id != :session_id",array(':session_id'=>0)).'</TD>
                    </TR>
                    <TR class="empty">
                    <TD colspan=3>'.lang('inv_mails_in_mail_queue').': ';
                    $qmails=experimentmail__mails_in_queue("invitation",$experiment_id);
                    echo $qmails;

        if (check_allow('mailqueue_show_experiment')) {
                echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.button_link('experiment_mailqueue_show.php?experiment_id='.
                        $experiment['experiment_id'],lang('monitor_experiment_mail_queue'),'envelope-square');
        }
            echo '</TD></TR></TABLE>
                </TD>
            </TR>';


        if ($qmails>0) {
            echo '  <TR>
                    <TD colspan=2>
                        <TABLE class="or_option_buttons_box" style="background: '.$color['options_box_background'].';">
                            <TR><TD align="left" style="color: '.$color['important_note_textcolor'].';">
                                '.$qmails.' '.lang('xxx_inv_mails_for_this_exp_still_in_queue').'
                            </TD></TR>
                        </TABLE>
                    </TD>
                </TR>';
        } elseif (check_allow('experiment_invite_participants')) {
                    echo '  <TR><TD colspan=2>
                    <TABLE class="or_option_buttons_box" style="background: '.$color['options_box_background'].';">
                    <TR><TD align="left">
                                2. '.lang('mail_to_not_got_one').'
                    </TD></TR>
                    <TR><TD align="right">
                        <INPUT class="button" type=submit name="send" value="'.lang('send').'">
                    </TD></TR>
                    </TABLE>
                    </TD></TR>
                    <TR><TD colspan=2>
                    <TABLE class="or_option_buttons_box" style="background: '.$color['options_box_background'].';">
                    <TR><TD align="left">
                                3. '.lang('mail_have_got_it_already').'
                    </TD></TR>
                    <TR class="empty"><TD align="right">
                        <INPUT class="button" type=submit name="sendall" value="'.lang('send_to_all').'">
                    </TD></TR>
                    </TABLE>
                    </TD></TR>';
            }
    echo '
            </TABLE>
            </FORM>';

    echo '<BR><A HREF="experiment_show.php?experiment_id='.$experiment_id.'">'.
            lang('mainpage_of_this_experiment').'</A><BR><BR>

        </CENTER>';
}
include ("footer.php");
?>