<?php
// part of orsee. see orsee.org
ob_start();
$title="customize_session_reminder_email";

include ("header.php");
if ($proceed) {
    if ($_REQUEST['experiment_id']) $experiment_id=$_REQUEST['experiment_id'];
    else redirect ("admin/");
}

if ($proceed) {
    if ($settings['enable_session_reminder_customization']!='y')
        redirect ('admin/experiment_show.php?experiment_id='.$experiment_id);
}

if ($proceed) {
    $allow=check_allow('experiment_customize_session_reminder','experiment_show.php?experiment_id='.$experiment_id);
}

if ($proceed) {
    if (isset($_REQUEST['id'])) $id=$_REQUEST['id']; else $id="";
    if (isset($_REQUEST['save_preview']) && $_REQUEST['save_preview']) $save_preview=true; else $save_preview=false;
    if (isset($_REQUEST['show_preview']) && $_REQUEST['show_preview']) $show_preview=true; else $show_preview=false;
    if (isset($_REQUEST['save']) && $_REQUEST['save']) $save=true; else $save=false;

    if ($save_preview || $save) $action=true; else $action=false;

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
    echo '</TR></TABLE><br>';

    if ($action) {

        $sitem=$_REQUEST;
        $sitem['content_type']='experiment_session_reminder_mail';
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

        if ($done) message(lang('mail_text_saved'));
        else message (lang('database_error'));

        log__admin("experiment_customize_session_reminder","experiment:".$experiment['experiment_name']);

        if ($save_preview) {
            redirect ('admin/experiment_customize_reminder.php?experiment_id='.$experiment_id.'&show_preview=true');
        } else {
            redirect ('admin/experiment_customize_reminder.php?experiment_id='.$experiment_id);
        }
    }
}

if ($proceed) {
    $pars=array(':experiment_id'=>$experiment_id);
    $query="SELECT * from ".table('lang')."
            WHERE content_type='experiment_session_reminder_mail'
            AND content_name= :experiment_id";
    $experiment_mail=orsee_query($query,$pars);

    $session=experimentmail__preview_fake_session_details($experiment_id);

    if ($show_preview) {
        echo '<TABLE class="or_formtable" style="width: 80%;">';

        echo '<TR><TD colspan=2>
            '.button_link('experiment_customize_reminder.php?experiment_id='.urlencode($experiment_id),
                            lang('back_to_mail_page'),'backward','font-size: 8pt;').'
            </TD></TR>';

        foreach ($inv_langs as $inv_lang) {
            // split in subject and text
            $subject=str_replace(strstr($experiment_mail[$inv_lang],"\n"),"",$experiment_mail[$inv_lang]);
            $body=substr($experiment_mail[$inv_lang],strpos($experiment_mail[$inv_lang],"\n")+1,strlen($experiment_mail[$inv_lang]));


            $lab=laboratories__get_laboratory_text($session['laboratory_id'],$inv_lang);

            $pform_fields=participant__load_participant_email_fields($inv_lang);
            $experimentmail=experimentmail__preview_fake_participant_details($pform_fields);
            $experimentmail['language']=$inv_lang;
            $experimentmail=experimentmail__get_session_reminder_details($experimentmail,$experiment,$session,$lab);
            if ($experiment['sender_mail']) $sendermail=$experiment['sender_mail']; else $sendermail=$settings['support_mail'];
            $email_text=process_mail_template(stripslashes($body),$experimentmail);

            if (count($inv_langs) > 1) {
                echo '<TR><TD colspan=2>
                        <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">
                            '.$inv_lang.':
                        </TD>
                        </TR></TABLE>
                    </TD></TR>';
            }
            echo '<TR><TD colspan=2><TABLE class="or_panel" style="background: '.$color['list_shade2'].'; width: 100%;">
                <TR>
                    <TD>'.load_language_symbol('email_from',$inv_lang).':</TD>
                    <TD>'.$sendermail.'</TD>
                </TR>
                <TR>
                    <TD>'.load_language_symbol('email_to',$inv_lang).':</TD>
                    <TD>'.$experimentmail['email'].'</TD>
                </TR>
                <TR bgcolor="'.$color['list_shade2'].'">
                    <TD>'.load_language_symbol('subject',$inv_lang).':</TD>
                    <TD>'.stripslashes($subject).'</TD>
                </TR>
                    <TR>
                        <TD valign=top bgcolor="'.$color['content_background_color'].'" colspan=2>
                            '.nl2br(process_mail_template(stripslashes($body),$experimentmail));
            if (isset($experimentmail['include_footer']) && $experimentmail['include_footer']=="y")
                        echo nl2br(stripslashes(experimentmail__get_mail_footer(0)));
            echo '      </TD>
                        </TR>
                        </TABLE></TD></TR>';
            echo '<TR><TD colspan=2>&nbsp;</TD></TR>';
        }

        echo '<TR><TD colspan=2>
                '.button_link('experiment_customize_reminder.php?experiment_id='.urlencode($experiment_id),
                                lang('back_to_mail_page'),'backward','font-size: 8pt;').'
                </TD></TR>';

        echo '</TABLE>';


        echo '<BR><A HREF="experiment_show.php?experiment_id='.$experiment_id.'">'.
                lang('mainpage_of_this_experiment').'</A><BR><BR>

                </CENTER>';

    } else {

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
                $subject=load_language_symbol('email_session_reminder_subject',$inv_lang);
            }

            if (!$body) {
                //$body=load_mail('default_invitation_'.$experiment['experiment_type'],$inv_lang);
                $body=load_mail('public_session_reminder',$inv_lang);
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
                    <TD>'.lang('subject').':</TD>
                    <TD><INPUT type=text name="'.$inv_lang.'_subject" size=30 maxlength=80 value="'.stripslashes($subject).'"></TD>
                </TR>
                    <TR><TD valign=top colspan=2>'.lang('body_of_message').':<BR>
                        <FONT class="small">'.lang('experimentmail_how_to_rebuild_default').'</FONT>
                        <BR>
                        <center>
                        <textarea name="'.$inv_lang.'_body" wrap=virtual rows=20 cols=50>'.
                            stripslashes($body).'</textarea>
                        </center>
                    </TD>
                </TR>';

            echo ' <TR class="empty"><TD colspan=2>&nbsp;</TD></TR>';
        }

        echo '
                <TR><TD colspan=2>
                <TABLE class="or_option_buttons_box" style="background: '.$color['options_box_background'].';">
                <TR><TD colspan="2" align="left">
                    '.lang('save_mail_text_only').'
                </TD></TR>
                <TR><TD align="left">
                    <INPUT class="button" type=submit name="save_preview" class="small" value="'.lang('mail_preview').'">
                </TD><TD align="right">
                    <INPUT class="button" type=submit name="save" value="'.lang('save').'">
                </TD></TR>
                </TABLE>
            </TD></TR>

            <TR>
                <TD colspan=2>
                    <TABLE class="or_option_buttons_box" style="background: '.$color['options_box_background'].';">
                    <TR>
                    <TD colspan=3>'.lang('reminder_mails_in_mail_queue').': ';
                    $qmails=experimentmail__mails_in_queue("session_reminder",$experiment_id);
                    echo $qmails;

        if (check_allow('mailqueue_show_experiment')) {
                echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.button_link('experiment_mailqueue_show.php?experiment_id='.
                        $experiment['experiment_id'],lang('monitor_experiment_mail_queue'),'envelope-square');
        }
            echo '</TD></TR></TABLE>
                </TD>
            </TR>';

        echo '
                </TABLE>
                </FORM>';

        echo '<BR><A HREF="experiment_show.php?experiment_id='.$experiment_id.'">'.
                lang('mainpage_of_this_experiment').'</A><BR><BR>

            </CENTER>';

    }

}
include ("footer.php");
?>