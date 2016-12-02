<?php
// part of orsee. see orsee.org

function email__retrieve_incoming() {
    global $settings, $settings__email_server_type, $settings__email_server_name, $settings__email_server_port,
            $settings__email_username, $settings__email_password, $settings__email_ssl;

    $continue=true; $result=array(); $result['errors']=array();
    if (!isset($settings__email_server_type) || !in_array($settings__email_server_type,array('pop3','imap'))) {
        $result['errors'][]='No valid email server type given.';
        $continue=false;
    }
    if (!isset($settings__email_server_name) || !$settings__email_server_name) {
        $result['errors'][]='No email server name given.';
        $continue=false;
    }
    if (!isset($settings__email_username) || !$settings__email_username) {
        $result['errors'][]='No email username name given.';
        $continue=false;
    }
    if (!isset($settings__email_password) || !$settings__email_password) {
        $result['errors'][]='No email username name given.';
        $continue=false;
    }
    if (!isset($settings__email_server_port) || !$settings__email_server_port) {
        $settings__email_server_port=NULL;
    }
    if (!isset($settings__email_ssl) || !$settings__email_ssl) {
        $settings__email_ssl=FALSE;
    } else {
        $settings__email_ssl=TRUE;
    }

    if ($continue) {
        include_once('../tagsets/class.fmailbox.php');
        $mailbox = new fMailbox($settings__email_server_type, $settings__email_server_name, $settings__email_username, $settings__email_password, $settings__email_server_port, $settings__email_ssl);
        $messages = $mailbox->listMessages();
        $count=0;
        foreach ($messages as $message) {

            $continue=true;

            if (isset($settings['email_module_delete_emails_from_server']) && $settings['email_module_delete_emails_from_server']=='n') {
                if (!isset($all_email_ids)) {
                    $query="SELECT message_id FROM ".table('emails');
                    $qresult = or_query($query);
                    $all_email_ids=array();
                    while ($m=pdo_fetch_assoc($qresult)) {
                        $all_email_ids[]=$m['message_id'];
                    }
                }
                if (in_array($message['message_id'],$all_email_ids)) $continue=false;
            }

            if ($continue) {
                // download message
                $email = $mailbox->fetchMessage($message['uid'],TRUE);

                // prepare and save to db
                if (isset($email['text'])) $body = email__strip_html($email['text']);
                elseif (isset($email['html'])) $body = email__strip_html($email['html']);
                if (isset($email['attachment']) && count($email['attachment'])>0) {
                    $has_attachments=1;
                    $data_string=email__attachment_array_to_dbstring($email['attachment']);
                } else {
                    $has_attachments=0;
                    $data_string='';
                }
                $to_adds=array(); $cc_adds=array();
                foreach ($email['headers']['to'] as $to_add) $to_adds[]=$to_add['mailbox']."@".$to_add['host'];
                if (isset($email['headers']['cc']) && is_array($email['headers']['cc'])) {
                    foreach ($email['headers']['cc'] as $cc_add) $cc_adds[]=$cc_add['mailbox']."@".$cc_add['host'];
                }
                $pars=array();
                $pars[':message_id']=$message['message_id'];
                $pars[':message_type']='incoming';
                $pars[':timestamp']=strtotime($message['date']);
                $pars[':from_address']=$email['headers']['from']['mailbox'] . "@" . $email['headers']['from']['host'];
                $pars[':from_name']=(isset($email['headers']['from']['personal']))?$email['headers']['from']['personal']:'';
                $pars[':reply_to_address']='';
                if (isset($email['headers']['reply-to'])) $pars[':reply_to_address']=$email['headers']['reply-to']['mailbox'] . "@" . $email['headers']['reply-to']['host'];
                $pars[':to_address']=implode(",",$to_adds);
                $pars[':cc_address']=implode(",",$cc_adds);
                $pars[':subject']=email__strip_html($message['subject']);
                if (!$pars[':subject']) $pars[':subject']="no subject";
                $pars[':body']=$body;
                $pars[':has_attachments']=$has_attachments;
                $pars[':attachment_data']=$data_string;

                $pars[':thread_id']=$message['message_id'];
                $pars[':thread_time']=$pars[':timestamp'];

                $pars[':mailbox']='not_assigned';

                $query="INSERT IGNORE INTO ".table('emails')."
                        SET message_id= :message_id,
                        message_type= :message_type,
                        timestamp= :timestamp,
                        from_address= :from_address,
                        from_name= :from_name,
                        reply_to_address= :reply_to_address,
                        to_address= :to_address,
                        cc_address= :cc_address,
                        subject= :subject,
                        body= :body,
                        has_attachments= :has_attachments,
                        attachment_data= :attachment_data,
                        thread_id = :thread_id,
                        thread_time = :thread_time,
                        mailbox = :mailbox
                        ";
                $done=or_query($query,$pars);
                if (pdo_num_rows($done) > 0 ) $count++;
                // delete from server
                if (! (isset($settings['email_module_delete_emails_from_server']) && $settings['email_module_delete_emails_from_server']=='n')) {
                    $mailbox->deleteMessages($message['uid']);
                }
            }
        }
        $result['count']=$count;
    }

    return $result;
}

function email__show_email($email,$open_reply=false,$open_note=false) {
    global $color, $settings, $expadmindata;

    // load remaining email thread
    $pars=array(':thread_id'=>$email['thread_id']);
    $query="SELECT * FROM ".table('emails')."
            WHERE thread_id = :thread_id
            AND message_id != thread_id
            ORDER BY timestamp";
    $result = or_query($query,$pars);
    $replies=array();
    while ($r=pdo_fetch_assoc($result)) $replies[]=$r;

    // set mail thread as read when is unread
    if (!$email['flag_read'] || (!$email['flag_assigned_to_read'])) {
        $flags=array();
        if (!$email['flag_read']) $flags['read']=1;
        if ($settings['email_module_allow_assign_emails']=='y') {
            if (!$email['flag_assigned_to_read']) {
                global $expadmindata;
                $assigned_to=db_string_to_id_array($email['assigned_to']);
                if (in_array($expadmindata['admin_id'],$assigned_to)) {
                    $flags['assigned_to_read']=1;
                }
            }
        } else {
            if (!$email['flag_assigned_to_read']) $flags['assigned_to_read']=1;
        }
        if (count($flags)>0) email__update_flags($email['thread_id'],$flags);
    }

    // guess participant if not already set
    $guess_parts=array(); $guess_part_message="";
    if (!$email['participant_id']) {
        $guess_parts=email__guess_participant($email);
        if (count($guess_parts)==0) {
            $guess_part_message=lang('cannot_guess');
        } else {
            $guess_part_message=lang('guess');
            $email['participant_id']=$guess_parts[0]['participant_id'];
            $participant=$guess_parts[0];
        }
    } else $participant=orsee_db_load_array("participants",$email['participant_id'],"participant_id");
    if (!isset($participant['participant_id'])) $participant=array();

    // guess experiment/session if not already set
    $guess_exp_sess=array(); $guess_expsess_message="";
    if (!$email['mailbox'] && !$email['experiment_id']) {
        $guess_exp_sess=email__guess_expsess($email);
        if (count($guess_exp_sess)==0) {
            $guess_expsess_message=lang('cannot_guess');
        } else {
            $guess_expsess_message=lang('guess');
            $email['experiment_id']=$guess_exp_sess[0]['experiment_id'];
            $email['session_id']=$guess_exp_sess[0]['session_id'];
        }
    } else {
        if ($email['session_id']) $session=orsee_db_load_array("sessions",$email['session_id'],"session_id");
        if (isset($session['experiment_id'])) $email['experiment_id']=$session['experiment_id'];
        if ($email['experiment_id']) $experiment=orsee_db_load_array("experiments",$email['experiment_id'],"experiment_id");
        if (!isset($session['session_id'])) $session=array();
    }
    if (!isset($session['session_id'])) $session=array();
    if (!isset($experiment['experiment_id'])) $experiment=array();

    $orig_to=explode(",",$email['to_address']);
    if ($email['cc_address']) $orig_cc=explode(",",$email['cc_address']); else $orig_cc=array();

    echo '<table class="or_formtable" style="background: '.$color['options_box_background'].'" CELLPADDING="3" CELLSPACING="3" >
        <TR class="emailtable"><TD align="right">';

    $allow_change=email__is_allowed($email,$experiment,'change');
    $allow_reply=email__is_allowed($email,$experiment,'reply');
    $allow_note=email__is_allowed($email,$experiment,'note');
    $allow_delete=email__is_allowed($email,$experiment,'delete');

    if ($allow_reply && (count($orig_to)+count($orig_cc)>1)) $reply_all_button=true; else $reply_all_button=false;
    email__show_buttons($email,$reply_all_button,$allow_delete,$allow_reply,$allow_note);

    echo '</TD></TR>';
    echo '<TR class="emailtable"><TD>';
    echo '  <FORM action="'.thisdoc().'" METHOD="POST"">
        <INPUT type="hidden" name="message_id" value="'.$email['message_id'].'">';
    if (isset($_REQUEST['hide_header']) && $_REQUEST['hide_header']) {
        echo '<INPUT type="hidden" name="hide_header" value="true">';
    }
    echo '<TABLE  class="or_panel" style="background: '.$color['content_background_color'].'; width: 100%; padding: 2px;" CELLPADDING="3" CELLSPACING="0">';


    // show settings to classify this email

    echo '<TR style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].';">
        <TD align=right>'.lang('mailbox_experiment_session').':</TD>
        <TD align=left valign=middle>';
    if ($allow_change) {
        email__expsess_select($email,$session,$experiment,$participant);
        if ($guess_expsess_message) {
            echo '<span class="small" style="border: 1px solid '.$color['message_border'].'; background: '.$color['message_background'].'; color: '.$color['message_text'].'"> ('.str_replace(" ","&nbsp",$guess_expsess_message).')</span>';
        }
    } else {
        if ($email['experiment_id']) {
            echo $experiment['experiment_name'];
            if ($email['session_id']) {
                echo ', '.session__build_name($related_sessions[$email['session_id']]);
            }
        } elseif ($email['mailbox']) {
            $mailboxes=email__load_mailboxes();
            echo $mailboxes[$email['mailbox']];
        } else {
            echo lang('mailbox_not_assigned');
        }
    }

    if ($email['experiment_id']) echo '<BR><A HREF="experiment_show.php?experiment_id='.urlencode($email['experiment_id']).'" style="color: '.$color['panel_title_textcolor'].';">['.str_replace(" ","&nbsp",lang('view_experiment')).']</A>';
    if ($email['session_id']) echo ' <A HREF="experiment_participants_show.php?experiment_id='.urlencode($email['experiment_id']).'&session_id='.urlencode($email['session_id']).'" style="color: '.$color['panel_title_textcolor'].';">['.str_replace(" ","&nbsp",lang('view_session')).']</A>';


    echo '  </TD>
            <TD align=center valign=middle rowspan=3>';

    echo lang('email_processed?').'<BR>';
    if ($allow_change) {
        echo '<select id="processed_switch" name="flag_processed">';
        echo '<option value="0"'; if (!$email['flag_processed']) echo ' SELECTED'; echo '></option>';
        echo '<option value="1"'; if ($email['flag_processed']) echo ' SELECTED'; echo '></option>';
        echo '</select>';
        $out="<script type=\"text/javascript\">
        $(function() {
            $('#processed_switch').switchy();
            $('#processed_switch').on('change', function(){
                var firstOption = $(this).children('option').first().val();
                var lastOption = $(this).children('option').last().val();
                var bgColor = '#bababa';
                if ($(this).val() == firstOption){
                    bgColor = '#DC143C';
                } else if ($(this).val() == lastOption){
                    bgColor = '#008000';
                }
                $(this).next().next().children().first().css(\"background-color\", bgColor);
            });
            $('#processed_switch').trigger('change');
        });
        </script>";
        echo $out;
    } else {
        if ($email['flag_processed']) echo lang('y'); else echo lang('n');
    }
    echo '  </TD>
            <TD align=center valign=middle rowspan=3>';

    if ($allow_change) echo '<INPUT class="button small" type="submit" name="update" value="'.lang('save').'">';
    echo '  </TD>
            </TR>';
    echo '  <TR style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].';">
            <TD align=right>'.lang('participant').':</TD>
            <TD align=left valign=middle>';
    if ($allow_change) {
        email__participant_select($email,$participant,$guess_parts);
        if ($guess_part_message) {
            echo '<span class="small" style="border: 1px solid '.$color['message_border'].'; background: '.$color['message_background'].'; color: '.$color['message_text'].'"> ('.str_replace(" ","&nbsp",$guess_part_message).')</span>';
        }
    } else {
        if ($email['participant_id']) {
            $cols=participant__get_result_table_columns('email_participant_guesses_list');
            $items=array();
            foreach ($cols as $k=>$c) {
                $items[]=$participant[$k];
            }
            echo implode(" ",$items);
        } else {
            echo lang('mailbox_not_assigned');
        }
    }
    if ($email['participant_id']) echo '&nbsp;<A HREF="participants_edit.php?participant_id='.urlencode($email['participant_id']).'" style="color: '.$color['panel_title_textcolor'].';">['.str_replace(" ","&nbsp",lang('view_profile')).']</A> ';
    echo '</TD>
        </TR>';
    if ($settings['email_module_allow_assign_emails']=='y')  {
        echo '  <TR style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].';">
                <TD align=right>'.lang('assign_email_to').':</TD>
                <TD align=left valign=middle class="small">';
                if ($allow_change) {
                    echo '<span style="color: '.$color['body_text'].';">'.experiment__experimenters_select_field("assigned_to",db_string_to_id_array($email['assigned_to']),true,array('cols'=>30)).'</span>';
                } else {
                    if ($email['assigned_to']) {
                        echo experiment__list_experimenters($email['assigned_to'],false,true);
                    } else {
                        echo '-';
                    }
                }
        echo '</TD>
            </TR>';
    }

    // show headers
    email__show_headers($email) ;

    // show email body
    email__show_body($email);

    // attachments
    email__show_attachments($email);

    echo '
        </TABLE>
        </FORM>
        </TD></TR>
        ';

    echo '<TR><TD>';
    echo '<TABLE width=100% border=0>';
    foreach ($replies as $remail) {
        echo '<TR><TD valign="top">';
        if ($remail['message_type']=='reply') echo icon('reply','',' fa-2x',' color: #666666;','reply');
        elseif ($remail['message_type']=='note') echo icon('file-text-o','',' fa-2x',' color: #666666;','internal note');
        elseif ($remail['message_type']=='incoming') echo icon('envelope-square','',' fa-2x',' color: #666666;','incoming');
        echo '</TD><TD>&nbsp;&nbsp;</TD><TD>';
        echo '<TABLE  class="or_panel" style="background: '.$color['content_background_color'].'; width: 100%; padding: 2px;" CELLPADDING="3" CELLSPACING="0">';
        // show headers
        email__show_headers($remail) ;

        // show email body
        email__show_body($remail);

        // attachments
        email__show_attachments($remail);
        echo '</TABLE>
        </TD></TR>
        ';
    }
    echo '</TABLE></TD></TR>';

    if (count($replies)>0) {
        echo '<TR class="emailtable"><TD align="right">';
        email__show_buttons($email,$reply_all_button,false,$allow_reply,$allow_note);
        echo '</TD></TR>';
    }

    // reply field
    if ($allow_reply) {
        echo '<TR id="replyfield"><TD>';
        echo '<A name="replyform"></A>';
        show_message();
        echo '<FORM name="send_email" action="'.thisdoc().'#replyform" method="POST">
             <INPUT type="hidden" name="message_id" value="'.$email['message_id'].'">';
        if (isset($_REQUEST['hide_header']) && $_REQUEST['hide_header']) {
            echo '<INPUT type="hidden" name="hide_header" value="true">';
        }

        if (isset($_REQUEST['replytype']) && $_REQUEST['replytype']=='reply') $replytype='reply';
        else $replytype='replyall';

        echo '<INPUT id="replytype" type="hidden" name="replytype" value="'.$replytype.'">';

        echo '<TABLE class="or_panel" style="background: '.$color['content_background_color'].'; width: 100%;">';
        echo '<TR><TD colspan=2 align=right>
                <I id="close_reply" class="fa fa-times-circle-o fa-2x"></I>
                </TD></TR>';
        echo '<TR><TD align=right>'.lang('email_from').':</TD>
                    <TD width=90% align=left>'.$settings['support_mail'].'</TD>
                </TR>';
        if (isset($_REQUEST['send_to'])) $to=$_REQUEST['send_to'];
        elseif (isset($email['reply_to_address']) && $email['reply_to_address']) $to=$email['reply_to_address'];
        else $to=$email['from_address'];
        echo '<TR>
                 <TD align=right>'.lang('email_to').':</TD><TD align=left>
                    <INPUT type="text" name="send_to" size=60 maxlength=255 value="'.$to.'">
                </TD>
                </TR>';

        if (isset($_REQUEST['send_cc_replyall'])) $cc_replyall=$_REQUEST['send_cc_replyall'];
        else {
            $cc_arr=array();
            if (count($orig_to)>1) foreach ($orig_to as $oto) if ($oto!=$settings['support_mail'] && !in_array($oto,$cc_arr)) $cc_arr[]=$oto;
            foreach ($orig_cc as $occ) if ($occ!=$settings['support_mail'] && !in_array($occ,$cc_arr)) $cc_arr[]=$occ;
            $cc_replyall=implode(",",$cc_arr);
        }
        echo '<TR id="ccfield_replyall">
                 <TD align=right>'.lang('email_cc').':</TD><TD align=left>
                    <INPUT type="text" name="send_cc_replyall" rows=2 cols=60 value="'.$cc_replyall.'">
                </TD>
                </TR>';
        if (isset($_REQUEST['send_cc_reply'])) $cc_reply=$_REQUEST['send_cc_reply'];
        else $cc_reply='';
        echo '<TR id="ccfield_reply">
                 <TD align=right>'.lang('email_cc').':</TD><TD align=left>
                    <INPUT type="text" name="send_cc_reply" rows=2 cols=60 value="'.$cc_reply.'">
                </TD>
                </TR>';
        if (isset($_REQUEST['send_subject'])) $subject=$_REQUEST['send_subject'];
        else $subject=lang('email_subject_re:').' '.$email['subject'];
        echo '<TR>
                <TD align=right>'.lang('email_subject').':</TD>
                <TD align=left><INPUT type="text" name="send_subject" size=60 maxlength=255 value="'.
                        $subject.'"></TD>
                </TR>';
        if (isset($_REQUEST['send_body'])) $body=$_REQUEST['send_body'];
        else $body="\n\n\n\n".$email['from_name'].' <'.$email['from_address'].'> '.lang('email_xxx_wrote').':'."\n".
                    email__cite_text($email['body']);
        echo '<TR><TD></TD><TD>
                <textarea name="send_body" wrap="virtual" rows="20" cols="60">'.$body.'</textarea>
            </TD></TR>';
        echo '<TR><TD colspan="2" align="center"><INPUT type="submit" class="button" name="send" value="'.lang('send_email').'"></TD></TR>';
        echo '</TABLE>';
        echo '</FORM>';
        echo '</TD></TR>';
    }

    // note field
    if ($allow_note) {
        echo '<TR id="notefield"><TD>';
        echo '<A name="noteform"></A>';
        show_message();
        echo '<FORM name="add_note" action="'.thisdoc().'#noteform" method="POST">
             <INPUT type="hidden" name="message_id" value="'.$email['message_id'].'">';
        if (isset($_REQUEST['hide_header']) && $_REQUEST['hide_header']) {
            echo '<INPUT type="hidden" name="hide_header" value="true">';
        }

        echo '<TABLE class="or_panel" style="background: '.$color['content_background_color'].'; width: 100%;">';
        echo '<TR><TD colspan="3" align=right>
                <I id="close_note" class="fa fa-times-circle-o fa-2x"></I>
                </TD></TR>';
        echo '<TR><TD valign="top" rowspan="3">';
        echo icon('file-text-o','',' fa-2x',' color: #666666;','internal note');
        echo '</TD><TD rowspan="3">&nbsp;&nbsp;</TD>';
        echo '<TD>'.lang('email_internal_note_by').' '.$expadmindata['fname'].' '.$expadmindata['lname'].'</TD>
                </TR>';
        if (isset($_REQUEST['note_body'])) $body=$_REQUEST['note_body'];
        else $body="";
        echo '<TR><TD>
                <textarea name="note_body" wrap="virtual" rows="20" cols="60">'.$body.'</textarea>
            </TD></TR>';
        echo '<TR><TD align="center"><INPUT type="submit" class="button" name="addnote" value="'.lang('add').'"></TD></TR>';
        echo '</TABLE>';
        echo '</FORM>';
        echo '</TD></TR>';
    }

    echo '</TABLE>';

    echo '  <script type="text/javascript"> ';
    if (!$open_reply) echo '$("#replyfield").hide(); ';
    else {
        if ($replytype=='reply') echo ' $("#ccfield_replyall").hide(); ';
        else echo ' $("#ccfield_reply").hide(); ';
    }
    if ($allow_note && !$open_note) echo '$("#notefield").hide(); ';

    if ($allow_note) echo '
                $(".note_button").click(function() {
                    $(".emailtable :input").attr("disabled", true);
                    $("#notefield").show();
                    $("html, body").animate({
                        scrollTop: $("#notefield").offset().top
                    }, 1000);
                });
                $("#close_note").click(function() {
                    $("#notefield").hide();
                    $(".emailtable :input").attr("disabled", false);
                });';

    if ($allow_reply) echo '
                $(".reply_button").click(function() {
                    $(".emailtable :input").attr("disabled", true);
                    $("#replytype").val("reply");
                    $("#ccfield_replyall").hide();
                    $("#ccfield_reply").show();
                    $("#replyfield").show();
                    $("html, body").animate({
                        scrollTop: $("#replyfield").offset().top
                    }, 1000);
                });
                $(".replyall_button").click(function() {
                    $(".emailtable :input").attr("disabled", true);
                    $("#replytype").val("replyall");
                    $("#ccfield_replyall").show();
                    $("#ccfield_reply").hide();
                    $("#replyfield").show();
                    $("html, body").animate({
                        scrollTop: $("#replyfield").offset().top
                    }, 1000);
                });
                $("#close_reply").click(function() {
                    $("#ccfield").hide();
                    $("#replyfield").hide();
                    $("#ccfield_replyall").hide();
                    $("#ccfield_reply").hide();
                    $(".emailtable :input").attr("disabled", false);
                });';
    echo '
            </script>';

}

function email__show_buttons($email,$reply_all_button=false,$delete_button=false,$reply_button=true,$note_button=true) {

    echo '<TABLE border=0 CELLPADDING="0" CELLSPACING="0"><TR>';
    if ($note_button) echo '<TD valign="top" align="center"><button class="note_button button">'.lang('email_add_internal_note').'</button></TD>';
    if ($reply_button) echo '<TD valign="top" align="center"><button class="reply_button button">'.lang('reply').'</button></TD>';
    if ($reply_all_button) {
        echo '<TD valign="top" align="center"><button class="replyall_button button">'.lang('reply_all').'</button></TD>';
    }
    if ($delete_button) {
        if ($email['flag_deleted']) {
            echo '<FORM action="'.thisdoc().'">
                <INPUT type="hidden" name="message_id" value="'.$email['message_id'].'">';
            if (isset($_REQUEST['hide_header']) && $_REQUEST['hide_header']) {
                echo '<INPUT type="hidden" name="hide_header" value="true">';
            }
            echo '<TD valign="top" align="center" class="small">
                <INPUT type="submit" class="button" name="undelete" value="'.lang('undelete').'">
                </TD></FORM>';
        } else {
            echo '<FORM action="'.thisdoc().'">
                <INPUT type="hidden" name="message_id" value="'.$email['message_id'].'">';
            if (isset($_REQUEST['hide_header']) && $_REQUEST['hide_header']) {
                echo '<INPUT type="hidden" name="hide_header" value="true">';
            }
            echo '<TD valign="top" align="center">
                <INPUT type="submit" class="button small" name="delete" value="'.lang('delete').'">
                </TD></FORM>';

        }
    }
    echo '</TR></TABLE>';
}

function email__show_headers($email) {
    global $color;
    $colspan=3;
    echo '<TR bgcolor="'.$color['list_shade2'].'">
            <TD align=right>'.lang('email_from').':</TD><TD colspan="'.$colspan.'" width=90% align=left>';
    if ($email['message_type']=='reply')  {
        echo experiment__list_experimenters($email['admin_id'],false,true).' &lt;'.$email['from_address'].'&gt;';
    } elseif ($email['message_type']=='note') {
        echo experiment__list_experimenters($email['admin_id'],false,true);
    } else {
        if ($email['from_name']) echo $email['from_name'].' &lt;'.$email['from_address'].'&gt;';
        else echo $email['from_address'];
    }
    echo '</TD></TR>';
    if ($email['message_type']!='note')  {
        echo '<TR bgcolor="'.$color['list_shade2'].'">
                <TD align=right>'.lang('email_to').':</TD>
                <TD colspan="'.$colspan.'" align=left>'.$email['to_address'].'</TD>
            </TR>';
        if (isset($email['cc_address']) && $email['cc_address']) {
            echo '<TR bgcolor="'.$color['list_shade2'].'">
                    <TD align=right>'.lang('email_cc').':</TD>
                    <TD colspan="'.$colspan.'" align=left>'.$email['cc_address'].'</TD>
                    </TR>';
        }
        if (isset($email['reply_to_address']) && $email['reply_to_address']) {
            echo '<TR bgcolor="'.$color['list_shade2'].'">
                    <TD align=right>'.lang('email_reply_to').':</TD>
                    <TD colspan="'.$colspan.'" align=left>'.$email['reply_to_address'].'</TD>
                    </TR>';
        }
        echo '<TR bgcolor="'.$color['list_shade2'].'">
                <TD align=right>'.lang('email_subject').':</TD>
                <TD colspan="'.$colspan.'" align=left>'.$email['subject'].'</TD>
            </TR>';
    }
}

function email__show_body($email) {
    global $color;
    echo '<TR><TD colspan="3" bgcolor="'.$color['content_background_color'].'">';
    echo email__format_email($email['body']);
    echo '</TD></TR>';
}

function email__show_attachments($email) {
    // attachments
    if ($email['has_attachments']) {
        echo '<TR><TD colspan="3" ><TABLE width="100%" CELLPADDING="0" CELLSPACING="0">';
        echo '<TR><TD>'.lang('attachments').':</TD></TR>';
        $attachments=email__dbstring_to_attachment_array($email['attachment_data'],false);
        echo '<TR><TD>';
        foreach ($attachments as $k=>$attachment) {
                echo '<A HREF="emails_download_attachment.php?message_id='.
                    urlencode($email['message_id']).'&k='.urlencode($k).'">'.
                    //icon('paperclip').
                    $attachment['filename'].'</A>&nbsp;&nbsp;&nbsp; ';
        }
        echo '</TD></TR></TABLE></TD></TR>';
    }
}


function email__list_emails($mode='inbox',$id='',$rmode='assigned',$url_string='',$show_refresh=true) {
    global $color, $lang, $settings;

    if (substr($url_string,0,1)=='?') $url_string=substr($url_string,1);

    $conditions=array(); $pars=array();
    if ($mode=='trash') { $conditions[]=' flag_deleted=1 '; } else { $conditions[]=' flag_deleted=0 '; }

    if ($mode=='inbox') { $conditions[]=' flag_processed=0 '; }
    elseif ($mode=='mailbox') { $conditions[]=' mailbox=:mailbox '; $pars[':mailbox']=$id; }
    elseif ($mode=='experiment') { $conditions[]=' experiment_id=:experiment_id '; $pars[':experiment_id']=$id; }
    elseif ($mode=='session') { $conditions[]=' session_id=:session_id '; $pars[':session_id']=$id; }
    elseif ($mode=='participant') { $conditions[]=' participant_id=:participant_id '; $pars[':participant_id']=$id; }

    if ($rmode=='assigned') {
        global $expadmindata;
        $ass_clause=query__get_experimenter_or_clause(array($expadmindata['admin_id']),'emails','assigned_to');
        $conditions[]=$ass_clause['clause']; foreach ($ass_clause['pars'] as $k=>$v) $pars[$k]=$v;
    } elseif ($rmode=='experiments') {
        global $expadmindata;
        $likelist=query__make_like_list($expadmindata['admin_id'],'assigned_to');
        $conditions[]=" experiment_id IN (SELECT experiment_id as id
                        FROM ".table('experiments')." WHERE (".$likelist['par_names'].") ) ";
        foreach ($likelist['pars'] as $k=>$v) $pars[$k]=$v;
    }

    $query="SELECT * FROM ".table('emails')."
            WHERE ".implode(" AND ",$conditions)."
            ORDER BY thread_time DESC, thread_id, if (thread_id=message_id,0,1), timestamp";
    $result = or_query($query,$pars);
    $emails=array(); $experiment_ids=array(); $session_ids=array();
    while ($email=pdo_fetch_assoc($result)) {
        $emails[]=$email;
        if ($mode!='experiment' && $email['experiment_id']) $experiment_ids[]=$email['experiment_id'];
        if ($mode!='session' && $email['session_id']) $session_ids[]=$email['session_id'];
    }
    $mailboxes=email__load_mailboxes();

    $shade=false;
    $related_experiments=experiment__load_experiments_for_ids($experiment_ids);
    $related_sessions=sessions__load_sessions_for_ids($session_ids);

    echo '<table style="max-width: 90%;">';
    if ($show_refresh) echo '
         <tr><td align="right">
            '.icon('refresh',thisdoc().'?'.$url_string,'fa-2x','color: green;','refresh list'),'
          </td></tr>';
    echo '    <tr><td>
          <table class="or_listtable"><thead>
          <tr style="background: '.$color['list_header_background'].';  color: '.$color['list_header_textcolor'].';">';
    echo '<td>&nbsp;&nbsp;&nbsp;</td>'; // is thread head
    echo '<td>'.lang('email_subject').'</td>'; // type: incoming, note, reply && subject
    echo '<td>'.lang('email_from').'</td>'; // from
    echo '<td>'.lang('date').'</td>'; // date
    echo '<td></td>';   // read // assigned_to_read
    echo '<td></td>';   // processed - check and background of row
    echo '<td></td>';   // view email button
    echo '</tr>
            </thead><tbody>';
    $cols=7;

    $shade=false; $style_unprocessed=' style="font-weight: bold;"';
    foreach ($emails as $email) {
        $second_row='';
        if ($email['thread_id']==$email['message_id']) {
            if ($shade) $shade=false; else $shade=true;
            $second_row="";
            // experiment or mailbox - not if experiment or session or mailbox
            if (!in_array($mode,array('experiment','session','mailbox'))) {
                if ($email['experiment_id']) {
                    if (isset($related_experiments[$email['experiment_id']]))
                        $second_row.=$related_experiments[$email['experiment_id']]['experiment_name'];
                } elseif ($email['mailbox']) {
                    $second_row.='<b>'.lang('email_mailbox').':</b> '.$mailboxes[$email['mailbox']];
                }
            }
            // session - not if session or mailbox
            if (!in_array($mode,array('session','mailbox'))) {
                if ($email['session_id']) {
                    if ($second_row) $second_row.=', ';
                    $second_row.=session__build_name($related_sessions[$email['session_id']]);
                    }
            }
            // assigned to
            if ($settings['email_module_allow_assign_emails']=='y' && $email['assigned_to']) {
                if ($second_row) $second_row.=', ';
                $second_row.=experiment__list_experimenters($email['assigned_to'],false,true);
            }
        }
        echo '<tr';
        if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
        else echo ' bgcolor="'.$color['list_shade2'].'"';
        if (!$email['flag_processed'] && $mode!='inbox') echo $style_unprocessed;
        echo '>';

        // thread head and subject
        if ($email['message_id']==$email['thread_id']) echo '<TD colspan=2>';
        else echo '<TD></TD><TD>';
        echo '<A name="'.$email['message_id'].'"></A>';

        $linktext='';
        if ($email['message_type']=='reply') $linktext.=icon('reply','','',' color: #666666;','reply');
        elseif ($email['message_type']=='note') $linktext.=icon('file-text-o','','',' color: #666666;','internal note');
        elseif ($email['message_type']=='incoming') $linktext.=icon('envelope-square','','',' color: #666666;','incoming');
        $linktext.='&nbsp;&nbsp;&nbsp;';
        if ($email['message_type']=='note') $linktext.=lang('email_internal_note');
        else $linktext.=$email['subject'];
        echo $linktext;
        if ($email['has_attachments']) echo icon('paperclip');
        echo '</TD>';

        // from
        echo '<td>';
        if ($email['message_type']=='reply')  {
        echo experiment__list_experimenters($email['admin_id'],false,true).' &lt;'.$email['from_address'].'&gt;';
        } elseif ($email['message_type']=='note') {
            echo experiment__list_experimenters($email['admin_id'],false,true);
        } else {
            if ($email['from_name']) echo $email['from_name'].' &lt;'.$email['from_address'].'&gt;';
            else echo $email['from_address'];
        }
        if ($email['message_type']=='incoming' && $email['participant_id']) echo icon('check-circle-o','','',' font-size: 8pt; color: #666666;','checked');
        echo '</td>';

        // date
        echo '<td>'.ortime__format($email['timestamp']).'</td>';

        if ($email['thread_id']==$email['message_id']) {
            // read // assigned_to_read
            echo '<td align=center valign=middle>';
            echo '<A HREF="'.thisdoc().'?'.$url_string.'&switch_read=true&message_id='.urlencode($email['message_id']).'">';
            if ($email['flag_read']) echo icon('circle-o','','',' color: #666666;');
            else echo icon('dot-circle-o','','',' color: #008000;');
            echo '</A>';
            if ($settings['email_module_allow_assign_emails']=='y' && $email['assigned_to']) {
                echo '<A HREF="'.thisdoc().'?'.$url_string.'&switch_assigned_to_read=true&message_id='.urlencode($email['message_id']).'">';
                if ($email['flag_assigned_to_read']) echo icon('circle-o','','',' color: #666666;');
                else echo icon('dot-circle-o','','',' color: #000080;');
                echo '</A>';
            }
            echo '</td>';

            // processed - check and background of row
            echo '<td>';
            if ($email['flag_processed']) echo icon('check','','',' color: #008000;');
            echo '</td>';

            // view email button
            echo '<td valign="top"';
            if ($second_row) echo ' rowspan="2"';
            echo '>';
            echo javascript__email_popup_button_link($email['message_id']);
            echo '</td>';
        } else {
            echo '<td colspan="3"></td>';
        }

        echo '</tr>';

        if ($second_row) {
            echo '<tr';
            if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
            else echo ' bgcolor="'.$color['list_shade2'].'"';
            if (!$email['flag_processed'] && $mode!='inbox') echo $style_unprocessed;
            echo '>';
            echo '<TD></TD>';
            echo '<TD colspan="'.($cols-2).'">';
            echo '<i>'.$second_row.'</i>';
            echo '</TD>';
            echo '</TR>';
        }
    }

    echo '</tbody></table>
            </td></tr>
            </table>';
}

function email__show_mail_boxes() {
    global $color;
    $mailboxes=email__load_mailboxes();

    $query="SELECT mailbox, if(experiment_id>0,1,0) as is_exp, flag_processed, flag_deleted, count(*) as num_emails
            FROM ".table('emails')."
            WHERE message_id = thread_id
            GROUP BY mailbox, flag_processed, flag_deleted, is_exp";
    $result=or_query($query);
    $num_emails=array();
    while ($line=pdo_fetch_assoc($result)) {
        if ($line['is_exp']) $line['mailbox']='experiments';
        elseif (!$line['mailbox']) $line['mailbox']='not_assigned';
        if ($line['flag_deleted']) $status='deleted';
        elseif ($line['flag_processed']) $status='processed';
        else $status='inbox';
        $num_emails[$line['mailbox']][$status]=$line['num_emails'];
    }

    echo '<TABLE class="or_listtable" style="min-width: 50%">';

    echo '  <thead><TR style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">
                <TD style=" padding: 5px 10px 5px 2px;">'.lang('email_mailbox').'</TD>
                <TD style=" padding: 5px 10px 5px 0px;"><A HREF="emails_main.php?mode=inbox" style="color: '.$color['list_header_textcolor'].';">'.lang('mailbox_inbox').'</A></TD>
                <TD style=" padding: 5px 10px 5px 0px;">'.lang('email_processed').'</TD>
                <TD style=" padding: 5px 10px 5px 0px;"><A HREF="emails_main.php?mode=trash" style="color: '.$color['list_header_textcolor'].';">'.lang('mailbox_trash').'</A></TD>
            </TR></thead>
            <tbody>';

    echo '<TR><TD>'.lang('assigned_to_experiments').'</TD><TD>';
    if (isset($num_emails['experiments']['inbox'])) echo $num_emails['experiments']['inbox']; else echo '0';
    echo '</TD><TD>';
    if (isset($num_emails['experiments']['processed'])) echo $num_emails['experiments']['processed']; else echo '0';
    echo '</TD><TD>';
    if (isset($num_emails['experiments']['deleted'])) echo $num_emails['experiments']['deleted']; else echo '0';
    echo '</TD></TR>';

    foreach ($mailboxes as $id=>$name) {
        echo '<TR><TD><A HREF="emails_main.php?mode=mailbox&id='.urlencode($id).'">'.$name.'</A></TD><TD>';
        if (isset($num_emails[$id]['inbox'])) echo $num_emails[$id]['inbox']; else echo '0';
        echo '</TD><TD>';
        if (isset($num_emails[$id]['processed'])) echo $num_emails[$id]['processed']; else echo '0';
        echo '</TD><TD>';
        if (isset($num_emails[$id]['deleted'])) echo $num_emails[$id]['deleted']; else echo '0';
        echo '</TD></TR>';
    }
    echo '</tbody></TABLE>';
}

// the voodoo
function email__guess_participant($email) {
    $guess=array();
    if (count($guess)==0) {
        // search for email in participants
        $pars=array(':email'=>$email['from_address']);
        $query="SELECT *
                FROM ".table('participants')."
                WHERE email=:email
                ORDER BY creation_time DESC";
        $result=or_query($query,$pars);
        while ($p=pdo_fetch_assoc($result)) $guess[]=$p;
    }
    if (count($guess)==0) {
        // search email address in email text
        if (preg_match_all("/([^@ \t\r\n\(\)\/\&\+]+@[-_0-9a-zA-Z]+\\.[^@ \t\r\n\(\)\/\&\+]+)/",
            $email['body'],$matches,PREG_PATTERN_ORDER)) {
            $par_array=id_array_to_par_array($matches[1],'email');
            $query="SELECT * FROM ".table('participants')."
                    WHERE email IN (".implode(',',$par_array['keys']).")";
            $result=or_query($query,$par_array['pars']);
            while ($p=pdo_fetch_assoc($result)) $guess[]=$p;
        }
    }
    return $guess;
}

function email__guess_expsess($email) {
        $guess=array();
/*
        REALLY NEEDED HERE?
        if (count($guess)==0) {
                // search in email for experiment id
        }

        if (count($guess)==0) {
                // look which is the last session the participant dealt with (in the logs?)
        }

        if (count($guess)==0) {
                // take the last session the participant was invited for, or registered for ...
        }
*/
        return $guess;
}

// some form items
function email__participant_select($email,$participant=array(),$guess_parts=array()) {
    $cols=participant__get_result_table_columns('email_participant_guesses_list');
    echo '<SELECT name="participant_id">
            <OPTION value="0">'.lang('unknown').'</OPTION>';
    if (isset($participant['participant_id'])) {
        echo '<OPTION value="'.$participant['participant_id'].'" SELECTED>';
        $items=array();
        foreach ($cols as $k=>$c) {
            $items[]=$participant[$k];
        }
        echo implode(" ",$items);
        echo '</OPTION>';
    }
    if (!$email['session_id']) {
        foreach ($guess_parts as $gp) {
            if ($gp['participant_id']!=$participant['participant_id']) {
                echo '<OPTION value="'.$gp['participant_id'].'">';
                $items=array();
                foreach ($cols as $k=>$c) {
                    $items[]=$gp[$k];
                }
                echo implode(" ",$items);
                echo '</OPTION>';
            }
        }
    }
    if (count($guess_parts)>0 && $email['session_id']) {
        $sort=query__load_default_sort('email_participant_guesses_list');
        $pars=array(':session_id'=>$email['session_id']);
        $query="SELECT * from ".table('participants')."
                WHERE participant_id IN (
                    SELECT participant_id FROM ".table('participate_at')."
                    WHERE session_id= :session_id)
                ORDER BY ".$sort;
        $result=or_query($query,$pars);
        while ($p=pdo_fetch_assoc($result)) {
            echo '<OPTION value="'.$p['participant_id'].'">';
            $items=array();
            foreach ($cols as $k=>$c) {
                $items[]=$p[$k];
            }
            echo implode(" ",$items);
            echo '</OPTION>';
        }
    }
    echo '</SELECT>';
}

function email__expsess_select($email,$session=array(),$experiment=array(),$participant=array()) {
    global $lang;

    if(isset($session['session_id'])) $selected=$session['experiment_id'].','.$session['session_id'];
    elseif(isset($experiment['experiment_id'])) $selected=$experiment['experiment_id'].',0';
    elseif(!$email['mailbox']) $selected='0,0';
    else $selected='';

    $pars=array();
    $query="SELECT ".table('experiments').".*, ".table('sessions').".*
            FROM ".table('experiments')." LEFT JOIN ".table('sessions')."
            ON ".table('experiments').".experiment_id=".table('sessions').".experiment_id
            WHERE (".table('experiments').".experiment_finished='n')";
    if (isset($session['experiment_id'])) {
        $query.=" OR (".table('experiments').".experiment_id= :experiment_id) ";
        $pars[':experiment_id']=$session['experiment_id'];
    } elseif (isset($experiment['experiment_id'])) {
        $query.=" OR (".table('experiments').".experiment_id= :experiment_id) ";
        $pars[':experiment_id']=$experiment['experiment_id'];
    }
    if (isset($participant['participant_id'])) {
        $query.=" OR (".table('experiments').".experiment_id IN (
                    SELECT experiment_id FROM ".table('participate_at')."
                    WHERE participant_id= :participant_id) ) ";
        $pars[':participant_id']=$participant['participant_id'];
    }
    $query.="ORDER BY session_start DESC ";
    $result=or_query($query,$pars);
    $experiments=array();
    while ($e=pdo_fetch_assoc($result)) {
        if ($e['session_id']==NULL) $e['session_id']=0;
        $experiments[$e['experiment_id']]['sessions'][$e['session_id']]=$e;
        $experiments[$e['experiment_id']]['experiment_name']=$e['experiment_name'];
        if ((!isset($experiments[$e['experiment_id']]['lastsesstime'])) ||
            $e['session_start']>$experiments[$e['experiment_id']]['lastsesstime']) {
            $experiments[$e['experiment_id']]['lastsesstime']=$e['session_start'];
        }
    }

    // now order experiments by the date of the last session of the experiment, DESC!
    foreach ($experiments as $id=>$arr) $experiments[$id]['lastsesstime_reversed']=0-$arr['lastsesstime'];
    multi_array_sort($experiments,'lastsesstime_reversed');
    echo '<SELECT name="expsess"><OPTION value="0,0">'.lang('select_none').'</OPTION>';

    // list special mail boxes
    $mailboxes=email__load_mailboxes();
    foreach ($mailboxes as $k=>$mb) {
        if ($k!='trash' && $k!='not_assigned') {
            echo '<OPTION value="box,'.$k.'"';
            if ($email['mailbox']==$k) echo ' SELECTED';
            echo '>'.$mb.'</OPTION>';
        }
    }
    foreach ($experiments as $exp_id=>$texperiment) {
        echo '<OPTION value="'.$exp_id.',0"';
        if ($selected==$exp_id.',0') echo ' SELECTED';
        echo '>'.$texperiment['experiment_name'].'</OPTION>'."\n";
        foreach ($texperiment['sessions'] as $tsession) {
            if ($tsession['session_id']>0) {
                $tsess_name=ortime__format(ortime__sesstime_to_unixtime($tsession['session_start']));
                echo '<OPTION value="'.$tsession['experiment_id'].','.$tsession['session_id'].'"';
                if ($selected==$tsession['experiment_id'].','.$tsession['session_id']) echo ' SELECTED';
                echo '>'.$tsession['experiment_name'].' - '.$tsess_name.'</OPTION>';
            }
        }
    }
    if (isset($session['session_id']) && !isset($experiments[$session['experiment_id']]['sessions'][$session['session_id']])) {
        echo '<OPTION value="'.$session['experiment_id'].','.$session['session_id'].'" SELECTED>'.
                $experiment['experiment_name'].' - '.ortime__format(ortime__sesstime_to_unixtime($session['session_start'])).'</OPTION>';
    } elseif (isset($experiment['experiment_id']) && !isset($experiments[$experiment['experiment_id']])) {
        echo '<OPTION value="'.$experiment['experiment_id'].',0" SELECTED>'.
                $experiment['experiment_name'].'</OPTION>';
    }
    echo '</SELECT>';
}


// database functions
function email__load_mailboxes() {
    global $preloaded_mailboxes;
    if (isset($preloaded_mailboxes) && is_array($preloaded_mailboxes)) return $preloaded_mailboxes;
    else {
        $query="SELECT * FROM ".table('lang')." WHERE content_type='emails_mailbox' ORDER BY order_number, content_name";
        $result=or_query($query);
        $mailboxes=array();
        $mailboxes['not_assigned']=lang('mailbox_not_assigned');
        while ($mb=pdo_fetch_assoc($result)) {
            $mailboxes[$mb['content_name']]=$mb[lang('lang')];
        }
        $preloaded_mailboxes=$mailboxes;
        return $mailboxes;
    }
}

function email__update_flags($thread_id,$flags=array()) {
    if (is_array($flags) && count($flags>0)) {
        $pars=array(); $clause=array();
        $pars[':thread_id']=$thread_id;
        foreach ($flags as $flag_name=>$flag_value) {
            $pars[':flag_'.$flag_name]=$flag_value;
            $clause[]='flag_'.$flag_name.'='.':flag_'.$flag_name;
        }
        $query="UPDATE ".table('emails')."
                SET ".implode(", ",$clause)."
                WHERE thread_id=:thread_id";
        $done=or_query($query,$pars);
        return $done;
    } else return false;
}

function email__switch_read_status($thread_id,$flag='read') {
    $pars=array();
    if ($flag!='assigned_to_read') $flag='read';
    $pars[':thread_id']=$thread_id;
    $query="UPDATE ".table('emails')."
            SET flag_".$flag." = if (flag_".$flag."=1,0,1)
            WHERE thread_id=:thread_id";
    $done=or_query($query,$pars);
    return '';
}

function email__update_thread_time($thread_id,$thread_time) {
    $pars=array();
    $pars[':thread_id']=$thread_id;
    $pars[':thread_time']=$thread_time;
    $query="UPDATE ".table('emails')."
            SET thread_time = :thread_time
            WHERE thread_id=:thread_id";
    $done=or_query($query,$pars);
    return $done;
}

function email__update_email($email) {

    $new_experiment_id=0; $new_session_id=0;

    if (isset($_REQUEST['expsess']) && $_REQUEST['expsess'])
        $sent_expsess=$_REQUEST['expsess'];
    else $sent_expsess='';
    if (isset($_REQUEST['participant_id']) && $_REQUEST['participant_id'])
        $sent_participant_id=$_REQUEST['participant_id'];
    else $sent_participant_id=0;
    if (isset($_REQUEST['assigned_to']) && $_REQUEST['assigned_to'])
        $sent_assigned_to=id_array_to_db_string(multipicker_json_to_array($_REQUEST['assigned_to']));
    else $sent_assigned_to='';
    if (isset($_REQUEST['flag_processed']) && $_REQUEST['flag_processed']) $flag_processed=1;
    else $flag_processed=0;

    $abox=explode(",",$sent_expsess);
    if ($abox[0]=='box') {
        $new_mailbox=$abox[1];
        $new_experiment_id=0;
        $new_session_id=0;
    } elseif ($abox[0]>0) {
        $new_mailbox='';
        $new_experiment_id=$abox[0];
        $new_session_id=$abox[1];
    } else {
        $new_mailbox='not_assigned';
        $new_experiment_id=0;
        $new_session_id=0;
    }
    $new_participant_id=$sent_participant_id;
    $new_assigned_to=$sent_assigned_to;

    $pars=array(':mailbox'=>$new_mailbox,
                ':experiment_id'=>$new_experiment_id,
                ':session_id'=>$new_session_id,
                ':participant_id'=>$new_participant_id,
                ':assigned_to'=>$new_assigned_to,
                ':flag_processed'=>$flag_processed,
                ':thread_id'=>$email['message_id']);
    $query="UPDATE ".table('emails')."
            SET mailbox= :mailbox,
                experiment_id= :experiment_id,
                session_id= :session_id,
                participant_id= :participant_id,
                assigned_to= :assigned_to,
                flag_processed = :flag_processed
                WHERE thread_id = :thread_id";
    $done=or_query($query,$pars);

    $redir='admin/emails_view.php?message_id='.urlencode($email['message_id']);
    if (isset($_REQUEST['hide_header']) && $_REQUEST['hide_header']) $redir.='&hide_header=true';
    return $redir;
}

function email__delete_undelete_email($email,$action) {
    if ($action=='delete') $flag_deleted=1; else $flag_deleted=0;
    $pars=array(':flag_deleted'=>$flag_deleted);
    $pars[':thread_id']=$email['thread_id'];
    $query="UPDATE ".table('emails')."
            SET flag_deleted=:flag_deleted
            WHERE thread_id=:thread_id";
    $done=or_query($query,$pars);
    $redir='admin/emails_view.php?message_id='.urlencode($email['message_id']);
    if (isset($_REQUEST['hide_header']) && $_REQUEST['hide_header']) $redir.='&hide_header=true';
    return $redir;
}

function email__empty_trash() {
    $query="DELETE FROM ".table('emails')."
            WHERE flag_deleted = 1";
    $done=or_query($query,$pars);
    message(lang('email_trash_emptied'));
    return '';
}

function email__get_count($col,$id,$assigned_to=0) {
    $pars=array(); $conditions=array();
    $conditions[]="thread_id = message_id";
    $conditions[]="flag_deleted = 0";
    if ($col) {
        $pars[':id']=$id;
        $conditions[]=$col." = :id";
    }
    if ($assigned_to) {
        $ass_clause=query__get_experimenter_or_clause(array($assigned_to),'emails','assigned_to');
        $conditions[]=$ass_clause['clause']; foreach ($ass_clause['pars'] as $k=>$v) $pars[$k]=$v;
    }
    $query="SELECT flag_processed, count(*) as num_emails
            FROM ".table('emails')."
            WHERE ".implode(" AND ",$conditions)."
            GROUP BY flag_processed ";
    $result=or_query($query,$pars);
    $nums=array('num_all'=>0,'num_new'=>0);
    while ($line=pdo_fetch_assoc($result)) {
        if ($line['flag_processed']) $nums['num_all']=$line['num_emails'];
        else $nums['num_new']=$line['num_emails'];
    }
    $nums['num_all']=$nums['num_all']+$nums['num_new'];
    return $nums;
}

function email__get_privileges($what,$array,$priv='read',$get_nums=true) {
    global $settings, $expadmindata;
    $return=array('allowed'=>false,'num_all'=>0,'num_new'=>0,$nums['rmode']='');
    if ($settings['enable_email_module']=='y') {
        if ( check_allow('emails_'.$priv.'_all')) {
            $return['allowed']=true; $return['rmode']='all';
            if ($get_nums) {
                if ($what=='experiment') $nums=email__get_count('experiment_id',$array['experiment_id']);
                elseif ($what=='session') $nums=email__get_count('session_id',$array['session_id']);
                elseif ($what=='participant' && isset($array['participant_id'])) $nums=email__get_count('participant_id',$array['participant_id']);
                else $nums=email__get_count('',0);
            }
        } elseif (check_allow('emails_'.$priv.'_experiments') && ($what=='experiment' || $what=='session')) {
            $experimenters=db_string_to_id_array($array['experimenter']);
            if (in_array($expadmindata['admin_id'],$experimenters)) {
                $return['allowed']=true; $return['rmode']='experiments';
                if ($get_nums) {
                    if ($what=='experiment') $nums=email__get_count('experiment_id',$array['experiment_id']);
                    elseif ($what=='session') $nums=email__get_count('session_id',$array['session_id']);
                }
            }
        } elseif ($settings['email_module_allow_assign_emails']=='y' && check_allow('emails_'.$priv.'_assigned')) {
            $return['allowed']=true; $return['rmode']='assigned';
            if ($get_nums) {
                if ($what=='experiment') $nums=email__get_count('experiment_id',$array['experiment_id'],$expadmindata['admin_id']);
                elseif ($what=='session') $nums=email__get_count('session_id',$array['session_id'],$expadmindata['admin_id']);
                elseif ($what=='participant') $nums=email__get_count('participant_id',$array['participant_id'],$expadmindata['admin_id']);
                else $nums=email__get_count('',0,$expadmindata['admin_id']);
            }
        }
        if ($get_nums) {
            $return['num_all']=$nums['num_all'];
            $return['num_new']=$nums['num_new'];
        }
    }
    return $return;
}

function email__is_allowed($email,$experiment,$priv='read') {
    global $settings, $expadmindata;
    $return=false; $continue=true;
    if ($settings['enable_email_module']=='y') {
        if (check_allow('emails_'.$priv.'_all')) {
            $return=true; $continue=false;
        }
        if ($continue && check_allow('emails_'.$priv.'_experiments') && $email['experiment_id']) {
            if (!isset($experiment['experiment_id'])) $experiment=orsee_db_load_array("experiments",$email['experiment_id'],"experiment_id");
            $experimenters=db_string_to_id_array($experiment['experimenter']);
            if (in_array($expadmindata['admin_id'],$experimenters)) {
                $return=true; $continue=false;
            }
        }
        if ($continue && $settings['email_module_allow_assign_emails']=='y' && check_allow('emails_'.$priv.'_assigned')) {
            $assigned_to=db_string_to_id_array($experiment['assigned_to']);
            if (in_array($expadmindata['admin_id'],$assigned_to)) {
                $return=true; $continue=false;
            }
        }
    }
    return $return;
}

function email__send_reply_email($email) {
    global $settings, $settings__server_url, $expadmindata;

    // checks
    $continue=true;

    $email_regex='/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i';

    if (isset($_REQUEST['replytype']) && in_array(trim($_REQUEST['replytype']),array('replyall','reply'))) {
        $reply_type=trim($_REQUEST['replytype']);
    } else $reply_type="reply";

    if (!isset($_REQUEST['send_to']) || !$_REQUEST['send_to']) {
        $continue=false;
        message(lang('error_email__to_address_not_given_or_wrong_format'));
    }
    if ($continue) {
        $to_adds=explode(",",$_REQUEST['send_to']);
        foreach ($to_adds as $k=>$to_add) {
            $to_adds[$k]=trim($to_add);
            if (!preg_match($email_regex,trim($to_add))) {
                $continue=false;
            }
            if (!$continue) message(lang('error_email__to_address_not_given_or_wrong_format'));
        }
    }

    if ($reply_type=='reply') $cc_field='send_cc_reply'; else $cc_field='send_cc_replyall';
    if (isset($_REQUEST[$cc_field]) && $_REQUEST[$cc_field]) $cc_adds=explode(",",$_REQUEST[$cc_field]);
    else $cc_adds=array();
    foreach ($cc_adds as $k=>$cc_add) {
        $cc_adds[$k]=trim($cc_add);
        if (!preg_match($email_regex,trim($cc_add))) {
            $continue=false;
        }
        if (!$continue) message(lang('error_email__cc_address_wrong_format'));
    }

    if (isset($_REQUEST['send_subject'])) $subject=$_REQUEST['send_subject'];
    else $subject="";
    if (!$subject) {
        $continue=false;
        message(lang('error_email__subject_is_empty'));
    }

    if (isset($_REQUEST['send_body'])) $body=$_REQUEST['send_body'];
    else $body="";
    if (!$body) {
        $continue=false;
        message(lang('error_email__message_body_is_empty'));
    }

    if ($continue) {
        $s['message_id']='<'.sha1(microtime()).'@'. $settings__server_url.'>';
        $s['message_type']='reply';
        $s['admin_id']=$expadmindata['admin_id'];
        $s['timestamp']=time();
        $s['from_address']=$settings['support_mail'];
        $s['to_address']=implode(",",$to_adds);
        $s['cc_address']=implode(",",$cc_adds);
        $s['subject']=$subject;
        $s['body']=$body;

        $s['mailbox']=$email['mailbox'];
        $s['experiment_id']=$email['experiment_id'];
        $s['session_id']=$email['session_id'];
        $s['participant_id']=$email['participant_id'];
        $s['assigned_to']=$email['assigned_to'];
        $s['thread_id']=$email['thread_id'];
        $s['thread_time']=time();
        $s['flag_read']=$email['flag_read'];
        $s['flag_assigned_to_read']=$email['flag_assigned_to_read'];
        $s['flag_processed']=$email['flag_processed'];
        $s['flag_deleted']=$email['flag_deleted'];

        // send message
        $headers="From: ".$s['from_address']."\r\n";
        if ($s['cc_address']) $headers=$headers."Cc: ".$s['cc_address']."\r\n";
        $done=experimentmail__mail($s['to_address'],$s['subject'],$s['body'],$headers);

        // save to database
        $done=orsee_db_save_array($s,"emails",$s['message_id'],"message_id");

        // update thread time
        $done=email__update_thread_time($s['thread_id'],$s['thread_time']);

        $redir='admin/emails_view.php?message_id='.urlencode($email['message_id']);
        if (isset($_REQUEST['hide_header']) && $_REQUEST['hide_header']) $redir.='&hide_header=true';
        return $redir;
    } else {
        return false;
    }
}

function email__add_internal_note($email) {
    global $settings, $settings__server_url, $expadmindata;

    // checks
    $continue=true;

    if (isset($_REQUEST['note_body'])) $body=$_REQUEST['note_body'];
    else $body="";
    if (!$body) {
        $continue=false;
        message(lang('error_email__message_body_is_empty'));
    }

    if ($continue) {
        $s['message_id']='<'.sha1(microtime()).'@'. $settings__server_url.'>';
        $s['message_type']='note';
        $s['admin_id']=$expadmindata['admin_id'];
        $s['timestamp']=time();
        $s['from_address']='';
        $s['to_address']='';
        $s['cc_address']='';
        $s['subject']='';
        $s['body']=$body;

        $s['mailbox']=$email['mailbox'];
        $s['experiment_id']=$email['experiment_id'];
        $s['session_id']=$email['session_id'];
        $s['participant_id']=$email['participant_id'];
        $s['assigned_to']=$email['assigned_to'];
        $s['thread_id']=$email['thread_id'];
        $s['thread_time']=time();
        $s['flag_read']=$email['flag_read'];
        $s['flag_assigned_to_read']=$email['flag_assigned_to_read'];
        $s['flag_processed']=$email['flag_processed'];
        $s['flag_deleted']=$email['flag_deleted'];

        // save to database
        $done=orsee_db_save_array($s,"emails",$s['message_id'],"message_id");

        // update thread time
        $done=email__update_thread_time($s['thread_id'],$s['thread_time']);

        $redir='admin/emails_view.php?message_id='.urlencode($email['message_id']);
        if (isset($_REQUEST['hide_header']) && $_REQUEST['hide_header']) $redir.='&hide_header=true';
        return $redir;
    } else {
        return false;
    }
}


// some text processing helpers
function email__strip_html($text) {
    $text=preg_replace('/<style[^<]+<\/style>/iu','',$text);
    $text=preg_replace('/<script[^<]+<\/script>/iu','',$text);
    $text=strip_tags($text);
    $text = preg_replace("/\R{3,}/", "\n\n", $text);
    $text = preg_replace("/\R/", "\n", $text);
    $text = trim($text);
    return $text;
}

function email__format_email($text) {
    return '<p>' . preg_replace(array('/(\r\n\r\n|\r\r|\n\n)(\s+)?/', '/\r\n|\r|\n/'),
            array('</p><p>', '<br/>'), $text) . '</p>';
}

function email__cite_text($text) {
    $textarray=explode("\n",$text);
    for ($i = 0; $i < count($textarray); $i++) $textarray[$i]="> ".$textarray[$i];
    $citedtext=implode("\n",$textarray);
    return $citedtext;
}

function email__attachment_array_to_dbstring($attachments=array()) {
    $atts=array();
    foreach ($attachments as $k=>$attachment) {
        // $attachment['data']=base64_encode($attachment['data']); already comes base64 encoded ...
        $atts[]=property_array_to_db_string($attachment);
    }
    $data_string=implode('|-!nextatt!-|',$atts);
    return $data_string;
}

function email__dbstring_to_attachment_array($dbstring='',$decode=false) {
    $atts=array(); $attachments=array();
    if ($dbstring) $atts=explode('|-!nextatt!-|',$dbstring);
    foreach ($atts as $ta) {
        $attachment=db_string_to_property_array($ta);
        if ($decode) $attachment['data']=base64_decode($attachment['data']);
        $attachments[]=$attachment;
    }
    return $attachments;
}

?>
