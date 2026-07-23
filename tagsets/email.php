<?php
// part of orsee. see orsee.org

function email__retrieve_incoming() {
    global $settings, $settings__email_server_type, $settings__email_server_name, $settings__email_server_port,
    $settings__email_username, $settings__email_password, $settings__email_ssl;

    $continue=true;
    $result=array();
    $result['errors']=array();
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
        $settings__email_server_port=null;
    }
    if (!isset($settings__email_ssl) || !$settings__email_ssl) {
        $settings__email_ssl=false;
    } else {
        $settings__email_ssl=true;
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
                if (in_array($message['message_id'],$all_email_ids)) {
                    $continue=false;
                }
            }

            if ($continue) {
                // download message
                $email = $mailbox->fetchMessage($message['uid'],true);

                // prepare and save to db
                if (isset($email['text'])) {
                    $body = email__strip_html($email['text']);
                } elseif (isset($email['html'])) {
                    $body = email__strip_html($email['html']);
                }
                if (isset($email['attachment']) && count($email['attachment'])>0) {
                    $has_attachments=1;
                    $data_string=email__attachment_array_to_dbstring($email['attachment']);
                } else {
                    $has_attachments=0;
                    $data_string='';
                }
                $to_adds=array();
                $cc_adds=array();
                foreach ($email['headers']['to'] as $to_add) {
                    $to_address=email__extract_address_from_header_part($to_add);
                    if ($to_address!=='') {
                        $to_adds[]=$to_address;
                    }
                }
                if (isset($email['headers']['cc']) && is_array($email['headers']['cc'])) {
                    foreach ($email['headers']['cc'] as $cc_add) {
                        $cc_address=email__extract_address_from_header_part($cc_add);
                        if ($cc_address!=='') {
                            $cc_adds[]=$cc_address;
                        }
                    }
                }
                $pars=array();
                $pars[':message_id']=$message['message_id'];
                $pars[':message_type']='incoming';
                $pars[':timestamp']=strtotime($message['date']);
                $pars[':from_address']=email__extract_address_from_header_part($email['headers']['from']);
                $pars[':from_name']=(isset($email['headers']['from']['personal'])) ? $email['headers']['from']['personal'] : '';
                $pars[':reply_to_address']='';
                if (isset($email['headers']['reply-to'])) {
                    $pars[':reply_to_address']=email__extract_address_from_header_part($email['headers']['reply-to']);
                }
                $pars[':to_address']=implode(",",$to_adds);
                $pars[':cc_address']=implode(",",$cc_adds);
                $pars[':subject']=email__strip_html($message['subject']);
                if (!$pars[':subject']) {
                    $pars[':subject']="no subject";
                }
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
                if (pdo_num_rows($done) > 0) {
                    $count++;
                }
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

function email__extract_address_from_header_part($part) {
    if (!is_array($part)) {
        return '';
    }
    if (!isset($part['mailbox'])) {
        return '';
    }

    $mailbox=trim((string)$part['mailbox']);
    $host=(isset($part['host'])) ? trim((string)$part['host']) : '';
    if ($mailbox==='') {
        return '';
    }

    $candidate=($host!=='') ? ($mailbox.'@'.$host) : $mailbox;
    $candidate=trim($candidate, " \t\n\r\0\x0B<>\"");
    if (filter_var($candidate,FILTER_VALIDATE_EMAIL)) {
        return $candidate;
    }

    if (preg_match('/<([^>]+)>/',$mailbox,$match)) {
        $embedded=trim($match[1]);
        if (filter_var($embedded,FILTER_VALIDATE_EMAIL)) {
            return $embedded;
        }
    }

    if ($host==='') {
        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i',$mailbox,$match)) {
            $inline=trim($match[0]);
            if (filter_var($inline,FILTER_VALIDATE_EMAIL)) {
                return $inline;
            }
        }
    }

    return $candidate;
}

function email__show_email($email,$open_reply=false,$open_note=false) {
    global $settings, $expadmindata;

    // load remaining email thread
    $pars=array(':thread_id'=>$email['thread_id']);
    $query="SELECT * FROM ".table('emails')."
            WHERE thread_id = :thread_id
            AND message_id != thread_id
            ORDER BY timestamp";
    $result = or_query($query,$pars);
    $replies=array();
    while ($r=pdo_fetch_assoc($result)) {
        $replies[]=$r;
    }

    // set mail thread as read when is unread
    if (!$email['flag_read'] || (!$email['flag_assigned_to_read'])) {
        $flags=array();
        if (!$email['flag_read']) {
            $flags['read']=1;
        }
        if ($settings['email_module_allow_assign_emails']=='y') {
            if (!$email['flag_assigned_to_read']) {
                global $expadmindata;
                $assigned_to=db_string_to_id_array($email['assigned_to']);
                if (in_array($expadmindata['admin_id'],$assigned_to)) {
                    $flags['assigned_to_read']=1;
                }
            }
        } else {
            if (!$email['flag_assigned_to_read']) {
                $flags['assigned_to_read']=1;
            }
        }
        if (count($flags)>0) {
            email__update_flags($email['thread_id'],$flags);
        }
    }

    // guess participant if not already set
    $guess_parts=array();
    $guess_part_message="";
    if (!$email['participant_id']) {
        $guess_parts=email__guess_participant($email);
        if (count($guess_parts)==0) {
            $guess_part_message=lang('cannot_guess');
        } else {
            $guess_part_message=lang('guess');
            $email['participant_id']=$guess_parts[0]['participant_id'];
            $participant=$guess_parts[0];
        }
    } else {
        $participant=orsee_db_load_array("participants",$email['participant_id'],"participant_id");
    }
    if (!isset($participant['participant_id'])) {
        $participant=array();
    }

    // guess experiment/session if not already set
    $guess_exp_sess=array();
    $guess_expsess_message="";
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
        if ($email['session_id']) {
            $session=orsee_db_load_array("sessions",$email['session_id'],"session_id");
        }
        if (isset($session['experiment_id'])) {
            $email['experiment_id']=$session['experiment_id'];
        }
        if ($email['experiment_id']) {
            $experiment=orsee_db_load_array("experiments",$email['experiment_id'],"experiment_id");
        }
        if (!isset($session['session_id'])) {
            $session=array();
        }
    }
    if (!isset($session['session_id'])) {
        $session=array();
    }
    if (!isset($experiment['experiment_id'])) {
        $experiment=array();
    }

    $orig_to=explode(",",$email['to_address']);
    if ($email['cc_address']) {
        $orig_cc=explode(",",$email['cc_address']);
    } else {
        $orig_cc=array();
    }

    $allow_change=email__is_allowed($email,$experiment,'change');
    $allow_reply=email__is_allowed($email,$experiment,'reply');
    $allow_note=email__is_allowed($email,$experiment,'note');
    $allow_delete=email__is_allowed($email,$experiment,'delete');

    if ($allow_reply && (count($orig_to)+count($orig_cc)>1)) {
        $reply_all_button=true;
    } else {
        $reply_all_button=false;
    }
    echo '<div class="orsee-panel-actions has-text-right">';
    email__show_buttons($email,$reply_all_button,$allow_delete,$allow_reply,$allow_note);
    echo '</div>';

    echo '<form action="'.thisdoc().'" method="POST" class="orsee-email-main-form">';
    echo '<input type="hidden" name="message_id" value="'.$email['message_id'].'">';
    echo csrf__field();
    if (isset($_REQUEST['hide_header']) && $_REQUEST['hide_header']) {
        echo '<input type="hidden" name="hide_header" value="true">';
    }

    echo '<div class="orsee-panel">';
    echo '<div class="orsee-form-shell" style="width: 100%; max-width: 100%;">';

    echo '<div class="field">';
    echo '<label class="label">'.lang('mailbox_experiment_session').':</label>';
    echo '<div class="control">';
    if ($allow_change) {
        email__expsess_select($email,$session,$experiment,$participant);
        if ($guess_expsess_message) {
            echo ' <span class="orsee-font-compact">('.$guess_expsess_message.')</span>';
        }
    } else {
        if ($email['experiment_id']) {
            echo $experiment['experiment_name'];
            if ($email['session_id']) {
                echo ', '.session__build_name($session);
            }
        } elseif ($email['mailbox']) {
            $mailboxes=email__load_mailboxes();
            echo $mailboxes[$email['mailbox']];
        } else {
            echo lang('mailbox_not_assigned');
        }
    }
    if ($email['experiment_id']) {
        echo '<div><a href="experiment_show.php?experiment_id='.urlencode($email['experiment_id']).'">['.lang('view_experiment').']</a>';
    }
    if ($email['session_id']) {
        echo ' <a href="experiment_participants_show.php?experiment_id='.urlencode($email['experiment_id']).'&session_id='.urlencode($email['session_id']).'">['.lang('view_session').']</a>';
    }
    if ($email['experiment_id']) {
        echo '</div>';
    }
    echo '</div></div>';

    echo '<div class="field">';
    echo '<label class="label">'.lang('participant').':</label>';
    echo '<div class="control">';
    if ($allow_change) {
        email__participant_select($email,$participant,$guess_parts);
        if ($guess_part_message) {
            echo ' <span class="orsee-font-compact">('.$guess_part_message.')</span>';
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
    if ($email['participant_id']) {
        echo ' <a href="participants_edit.php?participant_id='.urlencode($email['participant_id']).'">['.lang('view_profile').']</a>';
    }
    echo '</div></div>';

    if ($settings['email_module_allow_assign_emails']=='y') {
        echo '<div class="field">';
        echo '<label class="label">'.lang('assign_email_to').':</label>';
        echo '<div class="control">';
        if ($allow_change) {
            echo experiment__experimenters_select_field("assigned_to",db_string_to_id_array($email['assigned_to']),true,array('cols'=>30,'tag_bg_color'=>'--lcolor-selector-tag-bg-experimenters'));
        } else {
            if ($email['assigned_to']) {
                echo experiment__list_experimenters($email['assigned_to'],false,true);
            } else {
                echo '-';
            }
        }
        echo '</div></div>';
    }

    echo '<div class="field">';
    echo '<label class="label">'.lang('email_processed?').'</label>';
    echo '<div class="control">';
    if ($allow_change) {
        echo '<select id="processed_switch" name="flag_processed" data-elem-name="yesnoswitch">';
        echo '<option value="0"';
        if (!$email['flag_processed']) {
            echo ' selected';
        } echo '></option>';
        echo '<option value="1"';
        if ($email['flag_processed']) {
            echo ' selected';
        } echo '></option>';
        echo '</select>';
    } else {
        if ($email['flag_processed']) {
            echo lang('y');
        } else {
            echo lang('n');
        }
    }
    echo '</div></div>';

    if ($allow_change) {
        echo '<div class="field has-text-centered"><button class="button orsee-btn" type="submit" name="update" value="1"><i class="fa fa-save"></i>&nbsp;'.lang('save').'</button></div>';
    }
    echo '</div>';

    email__show_headers($email);
    email__show_body($email);
    email__show_attachments($email);
    echo '</div>';
    echo '</form>';

    foreach ($replies as $remail) {
        echo '<div class="orsee-panel" style="margin-top: 0.75rem;">';
        echo '<div class="orsee-panel-title"><div>';
        if ($remail['message_type']=='reply') {
            echo icon('reply','','',' color: var(--color-panel-title-text);','reply').' '.lang('reply');
        } elseif ($remail['message_type']=='note') {
            echo icon('file-text-o','','',' color: var(--color-panel-title-text);','internal note').' '.lang('email_internal_note');
        } else {
            echo icon('envelope-square','','',' color: var(--color-panel-title-text);','incoming').' '.lang('email_received');
        }
        echo ' · '.ortime__format($remail['timestamp']);
        echo '</div></div>';
        email__show_headers($remail);
        email__show_body($remail);
        email__show_attachments($remail);
        echo '</div>';
    }

    if (count($replies)>0) {
        echo '<div class="orsee-panel-actions has-text-right" style="margin-top: 0.5rem;">';
        email__show_buttons($email,$reply_all_button,false,$allow_reply,$allow_note);
        echo '</div>';
    }

    if (isset($_REQUEST['replytype']) && $_REQUEST['replytype']=='reply') {
        $replytype='reply';
    } else {
        $replytype='replyall';
    }

    if ($allow_reply) {
        echo '<div id="replyfield" class="orsee-panel" style="margin-top: 0.75rem;">';
        echo '<a name="replyform"></a>';
        show_message();
        echo '<form name="send_email" action="'.thisdoc().'#replyform" method="POST">';
        echo '<input type="hidden" name="message_id" value="'.$email['message_id'].'">';
        echo csrf__field();
        if (isset($_REQUEST['hide_header']) && $_REQUEST['hide_header']) {
            echo '<input type="hidden" name="hide_header" value="true">';
        }
        echo '<input id="replytype" type="hidden" name="replytype" value="'.$replytype.'">';

        if (isset($_REQUEST['send_to'])) {
            $to=$_REQUEST['send_to'];
        } elseif (isset($email['reply_to_address']) && $email['reply_to_address']) {
            $to=$email['reply_to_address'];
        } else {
            $to=$email['from_address'];
        }

        if (isset($_REQUEST['send_cc_replyall'])) {
            $cc_replyall=$_REQUEST['send_cc_replyall'];
        } else {
            $cc_arr=array();
            if (count($orig_to)>1) {
                foreach ($orig_to as $oto) {
                    if ($oto!=$settings['support_mail'] && !in_array($oto,$cc_arr)) {
                        $cc_arr[]=$oto;
                    }
                }
            }
            foreach ($orig_cc as $occ) {
                if ($occ!=$settings['support_mail'] && !in_array($occ,$cc_arr)) {
                    $cc_arr[]=$occ;
                }
            }
            $cc_replyall=implode(",",$cc_arr);
        }
        if (isset($_REQUEST['send_cc_reply'])) {
            $cc_reply=$_REQUEST['send_cc_reply'];
        } else {
            $cc_reply='';
        }
        if (isset($_REQUEST['send_subject'])) {
            $subject=$_REQUEST['send_subject'];
        } else {
            $subject=lang('email_subject_re:').' '.$email['subject'];
        }
        if (isset($_REQUEST['send_body'])) {
            $body=$_REQUEST['send_body'];
        } else {
            if (lang__is_rtl()) {
                $lri="\xE2\x81\xA6";
                $pdi="\xE2\x81\xA9";
                $name_ref=$lri.$email['from_name'].$pdi;
                $from_ref=$lri.'<'.$email['from_address'].'>'.$pdi;
                $body="\n\n\n\n".$name_ref.' '.$from_ref.' '.lang('email_xxx_wrote').':'."\n".email__cite_text($email['body']);
            } else {
                $body="\n\n\n\n".$email['from_name'].' <'.$email['from_address'].'> '.lang('email_xxx_wrote').':'."\n".email__cite_text($email['body']);
            }
        }

        echo '<div class="orsee-form-shell" style="width: 100%; max-width: 100%;">';
        echo '<div class="field has-text-right"><button type="button" id="close_reply" class="button orsee-btn"><i class="fa fa-times-circle-o fa-lg"></i></button></div>';
        echo '<div class="field"><label class="label">'.lang('email_from').':</label><div class="control">'.$settings['support_mail'].'</div></div>';
        echo '<div class="field"><label class="label">'.lang('email_to').':</label><div class="control"><input class="input is-primary orsee-input orsee-input-text" type="text" name="send_to" dir="ltr" maxlength="255" value="'.$to.'"></div></div>';
        echo '<div id="ccfield_replyall" class="field"><label class="label">'.lang('email_cc').':</label><div class="control"><input class="input is-primary orsee-input orsee-input-text" type="text" name="send_cc_replyall" dir="ltr" value="'.$cc_replyall.'"></div></div>';
        echo '<div id="ccfield_reply" class="field"><label class="label">'.lang('email_cc').':</label><div class="control"><input class="input is-primary orsee-input orsee-input-text" type="text" name="send_cc_reply" dir="ltr" value="'.$cc_reply.'"></div></div>';
        echo '<div class="field"><label class="label">'.lang('email_subject').':</label><div class="control"><input class="input is-primary orsee-input orsee-input-text" type="text" name="send_subject" maxlength="255" value="'.$subject.'"></div></div>';
        echo '<div class="field"><div class="control"><textarea class="textarea is-primary orsee-input orsee-textarea" name="send_body" wrap="virtual" rows="17">'.$body.'</textarea></div></div>';
        echo '<div class="field has-text-centered"><button type="submit" class="button orsee-btn" name="send" value="1">'.lang('send_email').'</button></div>';
        echo '</div></form></div>';
    }

    if ($allow_note) {
        echo '<div id="notefield" class="orsee-panel" style="margin-top: 0.75rem;">';
        echo '<a name="noteform"></a>';
        show_message();
        echo '<form name="add_note" action="'.thisdoc().'#noteform" method="POST">';
        echo '<input type="hidden" name="message_id" value="'.$email['message_id'].'">';
        echo csrf__field();
        if (isset($_REQUEST['hide_header']) && $_REQUEST['hide_header']) {
            echo '<input type="hidden" name="hide_header" value="true">';
        }
        if (isset($_REQUEST['note_body'])) {
            $body=$_REQUEST['note_body'];
        } else {
            $body="";
        }
        echo '<div class="orsee-form-shell" style="width: 100%; max-width: 100%;">';
        echo '<div class="field has-text-right"><button type="button" id="close_note" class="button orsee-btn"><i class="fa fa-times-circle-o fa-lg"></i></button></div>';
        echo '<div class="field"><div class="control">'.icon('file-text-o','','',' color: var(--color-body-text);','internal note').' '.lang('email_internal_note_by').' '.$expadmindata['fname'].' '.$expadmindata['lname'].'</div></div>';
        echo '<div class="field"><div class="control"><textarea class="textarea is-primary orsee-input orsee-textarea" name="note_body" wrap="virtual" rows="17">'.$body.'</textarea></div></div>';
        echo '<div class="field has-text-centered"><button type="submit" class="button orsee-btn" name="addnote" value="1">'.lang('add').'</button></div>';
        echo '</div></form></div>';
    }

    echo '<script type="text/javascript">
            (function(){
                var replyField=document.getElementById("replyfield");
                var noteField=document.getElementById("notefield");
                var ccReply=document.getElementById("ccfield_reply");
                var ccReplyAll=document.getElementById("ccfield_replyall");
                var replyType=document.getElementById("replytype");
                var processed=document.getElementById("processed_switch");
                if (window.osmeaSwitchyEnhanceAll && processed) window.osmeaSwitchyEnhanceAll(document);

                function disableMainForm(disabled){
                    document.querySelectorAll(".orsee-email-main-form input, .orsee-email-main-form select, .orsee-email-main-form textarea, .orsee-email-main-form button").forEach(function(el){
                        el.disabled=disabled;
                    });
                }
                function showReply(type){
                    if (!replyField) return;
                    if (replyType) replyType.value=type;
                    if (ccReplyAll) ccReplyAll.style.display=(type==="replyall")?"block":"none";
                    if (ccReply) ccReply.style.display=(type==="reply")?"block":"none";
                    replyField.style.display="block";
                    if (noteField) noteField.style.display="none";
                    disableMainForm(true);
                    replyField.scrollIntoView({behavior:"smooth", block:"start"});
                }
                function closeReply(){
                    if (!replyField) return;
                    replyField.style.display="none";
                    disableMainForm(false);
                }
                function showNote(){
                    if (!noteField) return;
                    noteField.style.display="block";
                    if (replyField) replyField.style.display="none";
                    disableMainForm(true);
                    noteField.scrollIntoView({behavior:"smooth", block:"start"});
                }
                function closeNote(){
                    if (!noteField) return;
                    noteField.style.display="none";
                    disableMainForm(false);
                }

                document.querySelectorAll("[data-email-action=\"reply\"]").forEach(function(btn){ btn.addEventListener("click", function(){ showReply("reply"); }); });
                document.querySelectorAll("[data-email-action=\"replyall\"]").forEach(function(btn){ btn.addEventListener("click", function(){ showReply("replyall"); }); });
                document.querySelectorAll("[data-email-action=\"note\"]").forEach(function(btn){ btn.addEventListener("click", function(){ showNote(); }); });
                var closeReplyBtn=document.getElementById("close_reply");
                if (closeReplyBtn) closeReplyBtn.addEventListener("click", closeReply);
                var closeNoteBtn=document.getElementById("close_note");
                if (closeNoteBtn) closeNoteBtn.addEventListener("click", closeNote);
';
    if (!$open_reply) {
        echo 'if (replyField) replyField.style.display="none";';
    } else {
        if ($replytype=='reply') {
            echo 'if (ccReplyAll) ccReplyAll.style.display="none"; if (ccReply) ccReply.style.display="block";';
        } else {
            echo 'if (ccReply) ccReply.style.display="none"; if (ccReplyAll) ccReplyAll.style.display="block";';
        }
    }
    if ($allow_note && !$open_note) {
        echo 'if (noteField) noteField.style.display="none";';
    }
    echo '  })();
        </script>';
}

function email__show_buttons($email,$reply_all_button=false,$delete_button=false,$reply_button=true,$note_button=true) {
    echo '<div class="buttons is-right">';
    if ($note_button) {
        echo '<button type="button" class="button orsee-btn" data-email-action="note"><i class="fa fa-file-text-o"></i>&nbsp;'.lang('email_add_internal_note').'</button>';
    }
    if ($reply_button) {
        echo '<button type="button" class="button orsee-btn" data-email-action="reply"><i class="fa fa-reply"></i>&nbsp;'.lang('reply').'</button>';
    }
    if ($reply_all_button) {
        echo '<button type="button" class="button orsee-btn" data-email-action="replyall"><i class="fa fa-reply-all"></i>&nbsp;'.lang('reply_all').'</button>';
    }
    if ($delete_button) {
        echo '<form action="'.thisdoc().'" method="POST" style="display: inline-flex; align-items: center; vertical-align: middle; margin: 0;">';
        echo '<input type="hidden" name="message_id" value="'.$email['message_id'].'">';
        echo csrf__field();
        if (isset($_REQUEST['hide_header']) && $_REQUEST['hide_header']) {
            echo '<input type="hidden" name="hide_header" value="true">';
        }
        if ($email['flag_deleted']) {
            echo '<button type="submit" class="button orsee-btn" name="undelete" value="1"><i class="fa fa-undo"></i>&nbsp;'.lang('undelete').'</button>';
        } else {
            echo '<button type="submit" class="button orsee-btn orsee-btn--delete" name="delete" value="1"><i class="fa fa-trash"></i>&nbsp;'.lang('delete').'</button>';
        }
        echo '</form>';
    }
    echo '</div>';
}

function email__show_headers($email) {
    $from_text='';
    if ($email['message_type']=='reply') {
        $from_text=experiment__list_experimenters($email['admin_id'],false,true).' <bdi dir="ltr">&lt;'.$email['from_address'].'&gt;</bdi>';
    } elseif ($email['message_type']=='note') {
        $from_text=experiment__list_experimenters($email['admin_id'],false,true);
    } else {
        if ($email['from_name']) {
            $from_text=$email['from_name'].' <bdi dir="ltr">&lt;'.$email['from_address'].'&gt;</bdi>';
        } else {
            $from_text='<bdi dir="ltr">'.$email['from_address'].'</bdi>';
        }
    }
    echo '<div class="orsee-table orsee-table-mobile orsee-table-cells-compact" style="width: 100%; margin-top: 0.5rem;">';
    echo '<div class="orsee-table-row">';
    echo '<div class="orsee-table-cell" style="white-space: nowrap;"><strong>'.lang('email_from').':</strong></div>';
    echo '<div class="orsee-table-cell">'.$from_text.'</div>';
    echo '</div>';
    if ($email['message_type']!='note') {
        echo '<div class="orsee-table-row">';
        echo '<div class="orsee-table-cell" style="white-space: nowrap;"><strong>'.lang('email_to').':</strong></div>';
        echo '<div class="orsee-table-cell"><bdi dir="ltr">'.$email['to_address'].'</bdi></div>';
        echo '</div>';
        if (isset($email['cc_address']) && $email['cc_address']) {
            echo '<div class="orsee-table-row">';
            echo '<div class="orsee-table-cell" style="white-space: nowrap;"><strong>'.lang('email_cc').':</strong></div>';
            echo '<div class="orsee-table-cell"><bdi dir="ltr">'.$email['cc_address'].'</bdi></div>';
            echo '</div>';
        }
        if (isset($email['reply_to_address']) && $email['reply_to_address']) {
            echo '<div class="orsee-table-row">';
            echo '<div class="orsee-table-cell" style="white-space: nowrap;"><strong>'.lang('email_reply_to').':</strong></div>';
            echo '<div class="orsee-table-cell"><bdi dir="ltr">'.$email['reply_to_address'].'</bdi></div>';
            echo '</div>';
        }
        echo '<div class="orsee-table-row">';
        echo '<div class="orsee-table-cell" style="white-space: nowrap;"><strong>'.lang('email_subject').':</strong></div>';
        echo '<div class="orsee-table-cell">'.$email['subject'].'</div>';
        echo '</div>';
    }
    echo '</div>';
}

function email__show_body($email) {
    echo '<div style="margin-top: 0.5rem; padding: 0.65rem 0.8rem; border: 1px solid var(--color-border-strong); border-radius: 0.5rem; background: var(--color-content-background-color);">';
    echo email__format_email($email['body']);
    echo '</div>';
}

function email__show_attachments($email) {
    // attachments
    if ($email['has_attachments']) {
        echo '<div style="margin-top: 0.5rem; padding: 0.5rem 0.75rem; border: 1px solid var(--color-border-strong); border-radius: 0.5rem; background: var(--color-content-background-color);">';
        echo '<div><strong>'.lang('attachments').':</strong></div>';
        echo '<div style="margin-top: 0.25rem;">';
        $attachments=email__dbstring_to_attachment_array($email['attachment_data'],false);
        foreach ($attachments as $k=>$attachment) {
            echo '<A HREF="emails_download_attachment.php?message_id='.
                urlencode($email['message_id']).'&k='.urlencode($k).'">'.
                //icon('paperclip').
                $attachment['filename'].'</A>&nbsp;&nbsp;&nbsp; ';
        }
        echo '</div></div>';
    }
}


function email__list_emails($mode='inbox',$id='',$rmode='assigned',$url_string='',$show_refresh=true) {
    global $lang, $settings;

    if (substr($url_string,0,1)=='?') {
        $url_string=substr($url_string,1);
    }
    $limit=(isset($settings['emails_number_of_entries_per_page']) && (int)$settings['emails_number_of_entries_per_page']>0) ? (int)$settings['emails_number_of_entries_per_page'] : 50;
    $offset=(isset($_REQUEST['os']) && (int)$_REQUEST['os']>0) ? (int)$_REQUEST['os'] : 0;

    $conditions=array();
    $pars=array();
    if ($mode=='trash') {
        $conditions[]=' flag_deleted=1 ';
    } else {
        $conditions[]=' flag_deleted=0 ';
    }

    if ($mode=='inbox') {
        $conditions[]=' flag_processed=0 ';
    } elseif ($mode=='mailbox') {
        $conditions[]=' mailbox=:mailbox ';
        $pars[':mailbox']=$id;
    } elseif ($mode=='experiment') {
        $conditions[]=' experiment_id=:experiment_id ';
        $pars[':experiment_id']=$id;
    } elseif ($mode=='session') {
        $conditions[]=' session_id=:session_id ';
        $pars[':session_id']=$id;
    } elseif ($mode=='participant') {
        $conditions[]=' participant_id=:participant_id ';
        $pars[':participant_id']=$id;
    }

    if ($rmode=='assigned') {
        global $expadmindata;
        $ass_clause=query__get_experimenter_or_clause(array($expadmindata['admin_id']),'emails','assigned_to');
        $conditions[]=$ass_clause['clause'];
        foreach ($ass_clause['pars'] as $k=>$v) {
            $pars[$k]=$v;
        }
    } elseif ($rmode=='experiments') {
        global $expadmindata;
        $likelist=query__make_like_list($expadmindata['admin_id'],'assigned_to');
        $conditions[]=" experiment_id IN (SELECT experiment_id as id
                        FROM ".table('experiments')." WHERE (".$likelist['par_names'].") ) ";
        foreach ($likelist['pars'] as $k=>$v) {
            $pars[$k]=$v;
        }
    }

    $count_query="SELECT count(*) as tf_count
            FROM ".table('emails')."
            WHERE ".implode(" AND ",$conditions);
    $count_result=or_query($count_query,$pars);
    $count_line=pdo_fetch_assoc($count_result);
    $total_rows=(isset($count_line['tf_count']) ? (int)$count_line['tf_count'] : 0);

    if ($offset>0 && $offset>=$total_rows) {
        $offset=max(0,$total_rows-$limit);
        $offset=$offset-($offset%$limit);
    }

    $pars[':offset']=$offset;
    $pars[':limit']=$limit;
    $query="SELECT * FROM ".table('emails')."
            WHERE ".implode(" AND ",$conditions)."
            ORDER BY thread_time DESC, thread_id, if (thread_id=message_id,0,1), timestamp
            LIMIT :offset, :limit";
    $result = or_query($query,$pars);
    $emails=array();
    $experiment_ids=array();
    $session_ids=array();
    while ($email=pdo_fetch_assoc($result)) {
        $emails[]=$email;
        if ($mode!='experiment' && $email['experiment_id']) {
            $experiment_ids[]=$email['experiment_id'];
        }
        if ($mode!='session' && $email['session_id']) {
            $session_ids[]=$email['session_id'];
        }
    }
    $mailboxes=email__load_mailboxes();

    $shade=false;
    $related_experiments=experiment__load_experiments_for_ids($experiment_ids);
    $related_sessions=sessions__load_sessions_for_ids($session_ids);

    echo '<div class="orsee-log-topbar">';
    echo '<div>';
    if ($total_rows>0) {
        $show_from=$offset+1;
        $show_to=min($offset+count($emails),$total_rows);
    } else {
        $show_from=0;
        $show_to=0;
    }
    echo lang('showing_emails').' '.$show_from.' - '.$show_to;
    echo '</div>';
    echo '<div>';
    if ($show_refresh) {
        $refresh_url=thisdoc().'?'.$url_string;
        if ($offset>0) {
            $refresh_url.='&os='.urlencode((string)$offset);
        }
        echo icon('refresh',$refresh_url,'fa-2x','color: green;','refresh list');
    }
    echo '</div>';
    echo '</div>';

    echo '<div class="orsee-log-pagination">';
    if ($offset > 0) {
        echo '<a class="button orsee-btn" href="'.thisdoc().'?'.$url_string.'&os='.urlencode((string)($offset-$limit)).'">'.lang('previous').'</a>';
    } else {
        echo '<span class="button orsee-btn disabled" aria-disabled="true">'.lang('previous').'</span>';
    }
    if (($offset + $limit) < $total_rows) {
        echo '<a class="button orsee-btn" href="'.thisdoc().'?'.$url_string.'&os='.urlencode((string)($offset+$limit)).'">'.lang('next').'</a>';
    } else {
        echo '<span class="button orsee-btn disabled" aria-disabled="true">'.lang('next').'</span>';
    }
    echo '</div>';

    if (count($emails)==0) {
        orsee_callout(lang('no_emails'),'note','');
        return;
    }

    echo '<div class="orsee-table orsee-table-mobile orsee-table-cells-compact" style="max-width: 99%;">';
    echo '<div class="orsee-table-row orsee-table-head">';
    echo '<div class="orsee-table-cell" style="width: 60%;">'.lang('email_subject').'</div>'; // type: incoming, note, reply && subject
    echo '<div class="orsee-table-cell" style="width: 40%;">'.lang('email_from').'</div>'; // from
    echo '<div class="orsee-table-cell">'.lang('date').'</div>'; // date
    echo '<div class="orsee-table-cell"></div>';   // read // assigned_to_read
    echo '<div class="orsee-table-cell"></div>';   // processed - check and background of row
    echo '<div class="orsee-table-cell"></div>';   // view email button
    echo '<div class="orsee-table-cell"></div>';   // archive button
    echo '<div class="orsee-table-cell"></div>';   // move to trash button
    echo '</div>';

    $shade=false;
    foreach ($emails as $email) {
        $second_row='';
        if ($email['thread_id']==$email['message_id']) {
            if ($shade) {
                $shade=false;
            } else {
                $shade=true;
            }
            $second_row="";
            // experiment or mailbox - not if experiment or session or mailbox
            if (!in_array($mode,array('experiment','session','mailbox'))) {
                if ($email['experiment_id']) {
                    if (isset($related_experiments[$email['experiment_id']])) {
                        $second_row.=$related_experiments[$email['experiment_id']]['experiment_name'];
                    }
                } elseif ($email['mailbox']) {
                    $second_row.='<b>'.lang('email_mailbox').':</b> '.$mailboxes[$email['mailbox']];
                }
            }
            // session - not if session or mailbox
            if (!in_array($mode,array('session','mailbox'))) {
                if ($email['session_id']) {
                    if ($second_row) {
                        $second_row.=', ';
                    }
                    $second_row.=session__build_name($related_sessions[$email['session_id']]);
                }
            }
            // assigned to
            if ($settings['email_module_allow_assign_emails']=='y' && $email['assigned_to']) {
                if ($second_row) {
                    $second_row.=', ';
                }
                $second_row.=experiment__list_experimenters($email['assigned_to'],false,true);
            }
        }
        $is_thread_child=($email['thread_id']!=$email['message_id']);
        $row_class='orsee-table-row';
        if ($shade) {
            $row_class.=' is-alt';
        }
        $row_style='';
        if (!$email['flag_processed'] && $mode!='inbox') {
            $row_style=' style="font-weight: bold;"';
        }
        echo '<div class="'.$row_class.'"'.$row_style.'>';

        $linktext='';
        if ($email['message_type']=='reply') {
            $linktext.=icon('reply','','',' color: var(--color-body-text);','reply');
        } elseif ($email['message_type']=='note') {
            $linktext.=icon('file-text-o','','',' color: var(--color-body-text);','internal note');
        } elseif ($email['message_type']=='incoming') {
            $linktext.=icon('envelope-square','','',' color: var(--color-body-text);','incoming');
        }
        $linktext.='&nbsp;&nbsp;&nbsp;';
        if ($email['message_type']=='note') {
            $linktext.=lang('email_internal_note');
        } else {
            $linktext.=$email['subject'];
        }
        $subject_html=$linktext;
        if ($email['has_attachments']) {
            $subject_html.=' '.icon('paperclip');
        }
        if ($second_row) {
            $subject_html.='<div><i>'.$second_row.'</i></div>';
        }
        if ($is_thread_child) {
            $subject_html='<span style="display:inline-block; margin-inline-start: 1.1rem;">'.$subject_html.'</span>';
        }
        echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(lang('email_subject')).'" style="width: 60%;"><a name="'.$email['message_id'].'"></a>'.$subject_html.'</div>';

        // from
        $from_html='';
        if ($email['message_type']=='reply') {
            $from_html.=experiment__list_experimenters($email['admin_id'],false,true).' <bdi dir="ltr">&lt;'.$email['from_address'].'&gt;</bdi>';
        } elseif ($email['message_type']=='note') {
            $from_html.=experiment__list_experimenters($email['admin_id'],false,true);
        } else {
            if ($email['from_name']) {
                $from_html.=$email['from_name'].' <bdi dir="ltr">&lt;'.$email['from_address'].'&gt;</bdi>';
            } else {
                $from_html.='<bdi dir="ltr">'.$email['from_address'].'</bdi>';
            }
        }
        if ($email['message_type']=='incoming' && $email['participant_id']) {
            $from_html.=' '.icon('check-circle-o','','',' font-size: 8pt; color: var(--color-body-text);','checked');
        }
        echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(lang('email_from')).'" style="width: 40%;">'.$from_html.'</div>';

        // date
        echo '<div class="orsee-table-cell" data-label="'.htmlspecialchars(lang('date')).'" style="white-space: nowrap;">'.ortime__format($email['timestamp']).'</div>';

        if ($email['thread_id']==$email['message_id']) {
            // read // assigned_to_read
            $read_html='<A HREF="'.thisdoc().'?'.$url_string.'&switch_read=true&message_id='.urlencode($email['message_id']).'">';
            $read_html.=icon($email['flag_read'] ? 'circle-o' : 'dot-circle-o','','',' color: '.($email['flag_read'] ? 'var(--color-email-icon-read)' : 'var(--color-email-icon-unread)').';');
            $read_html.='</A>';
            if ($settings['email_module_allow_assign_emails']=='y' && $email['assigned_to']) {
                $read_html.=' <A HREF="'.thisdoc().'?'.$url_string.'&switch_assigned_to_read=true&message_id='.urlencode($email['message_id']).'">';
                $read_html.=icon($email['flag_assigned_to_read'] ? 'circle-o' : 'dot-circle-o','','',' color: '.($email['flag_assigned_to_read'] ? 'var(--color-email-icon-read)' : 'var(--color-email-icon-assigned-unread)').';');
                $read_html.='</A>';
            }
            echo '<div class="orsee-table-cell" data-label="" style="white-space: nowrap;">'.$read_html.'</div>';

            // processed - check and background of row
            echo '<div class="orsee-table-cell" data-label="">';
            if ($email['flag_processed']) {
                echo icon('check','','',' color: var(--color-email-icon-processed);');
            }
            echo '</div>';

            // view email button
            echo '<div class="orsee-table-cell orsee-table-action" data-label="">'.javascript__email_popup_button_link($email['message_id']).'</div>';

            // mark processed button
            if ($mode!='trash' && !$email['flag_processed'] && email__is_allowed($email,array(),'change')) {
                echo '<div class="orsee-table-cell orsee-table-action" data-label=""><A HREF="'.thisdoc().'?'.$url_string.'&archive=true&message_id='.urlencode($email['message_id']).'" title="'.lang('email_processed').'">'.icon('archive','','fa-lg').'</A></div>';
            } else {
                echo '<div class="orsee-table-cell" data-label=""></div>';
            }

            // move to trash button
            if ($mode!='trash' && email__is_allowed($email,array(),'delete')) {
                echo '<div class="orsee-table-cell orsee-table-action" data-label=""><A HREF="'.thisdoc().'?'.$url_string.'&delete=true&message_id='.urlencode($email['message_id']).'" title="'.lang('delete').'">'.icon('trash-o','','fa-lg','color: var(--color-button-delete-text);').'</A></div>';
            } else {
                echo '<div class="orsee-table-cell" data-label=""></div>';
            }
        } else {
            echo '<div class="orsee-table-cell" data-label=""></div>';
            echo '<div class="orsee-table-cell" data-label=""></div>';
            echo '<div class="orsee-table-cell" data-label=""></div>';
            echo '<div class="orsee-table-cell" data-label=""></div>';
            echo '<div class="orsee-table-cell" data-label=""></div>';
        }

        echo '</div>';
    }
    echo '</div>';
}

function email__show_mail_boxes() {
    $mailboxes=email__load_mailboxes();

    $query="SELECT mailbox, if(experiment_id>0,1,0) as is_exp, flag_processed, flag_deleted, count(*) as num_emails
            FROM ".table('emails')."
            WHERE message_id = thread_id
            GROUP BY mailbox, flag_processed, flag_deleted, is_exp";
    $result=or_query($query);
    $num_emails=array();
    while ($line=pdo_fetch_assoc($result)) {
        if ($line['is_exp']) {
            $line['mailbox']='experiments';
        } elseif (!$line['mailbox']) {
            $line['mailbox']='not_assigned';
        }
        if ($line['flag_deleted']) {
            $status='deleted';
        } elseif ($line['flag_processed']) {
            $status='processed';
        } else {
            $status='inbox';
        }
        $num_emails[$line['mailbox']][$status]=$line['num_emails'];
    }

    echo '<div class="orsee-table orsee-table-mobile">';
    echo '<div class="orsee-table-row orsee-table-head">';
    echo '<div class="orsee-table-cell">'.lang('email_mailbox').'</div>';
    echo '<div class="orsee-table-cell"><a href="emails_main.php?mode=inbox">'.lang('mailbox_inbox').'</a></div>';
    echo '<div class="orsee-table-cell">'.lang('email_processed').'</div>';
    echo '<div class="orsee-table-cell"><a href="emails_main.php?mode=trash">'.lang('mailbox_trash').'</a></div>';
    echo '</div>';

    echo '<div class="orsee-table-row">';
    echo '<div class="orsee-table-cell" data-label="'.lang('email_mailbox').'">'.lang('assigned_to_experiments').'</div>';
    echo '<div class="orsee-table-cell" data-label="'.lang('mailbox_inbox').'">';
    if (isset($num_emails['experiments']['inbox'])) {
        echo $num_emails['experiments']['inbox'];
    } else {
        echo '0';
    }
    echo '</div>';
    echo '<div class="orsee-table-cell" data-label="'.lang('email_processed').'">';
    if (isset($num_emails['experiments']['processed'])) {
        echo $num_emails['experiments']['processed'];
    } else {
        echo '0';
    }
    echo '</div>';
    echo '<div class="orsee-table-cell" data-label="'.lang('mailbox_trash').'">';
    if (isset($num_emails['experiments']['deleted'])) {
        echo $num_emails['experiments']['deleted'];
    } else {
        echo '0';
    }
    echo '</div>';
    echo '</div>';

    foreach ($mailboxes as $id=>$name) {
        echo '<div class="orsee-table-row">';
        echo '<div class="orsee-table-cell" data-label="'.lang('email_mailbox').'"><A HREF="emails_main.php?mode=mailbox&id='.urlencode($id).'">'.$name.'</A></div>';
        echo '<div class="orsee-table-cell" data-label="'.lang('mailbox_inbox').'">';
        if (isset($num_emails[$id]['inbox'])) {
            echo $num_emails[$id]['inbox'];
        } else {
            echo '0';
        }
        echo '</div><div class="orsee-table-cell" data-label="'.lang('email_processed').'">';
        if (isset($num_emails[$id]['processed'])) {
            echo $num_emails[$id]['processed'];
        } else {
            echo '0';
        }
        echo '</div><div class="orsee-table-cell" data-label="'.lang('mailbox_trash').'">';
        if (isset($num_emails[$id]['deleted'])) {
            echo $num_emails[$id]['deleted'];
        } else {
            echo '0';
        }
        echo '</div></div>';
    }
    echo '</div>';
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
        while ($p=pdo_fetch_assoc($result)) {
            $guess[]=$p;
        }
    }
    if (count($guess)==0) {
        // search email address in email text
        if (preg_match_all("/([^@ \t\r\n\(\)\/\&\+]+@[-_0-9a-zA-Z]+\\.[^@ \t\r\n\(\)\/\&\+]+)/",
            $email['body'],$matches,PREG_PATTERN_ORDER)) {
            $par_array=id_array_to_par_array($matches[1],'email');
            $query="SELECT * FROM ".table('participants')."
                    WHERE email IN (".implode(',',$par_array['keys']).")";
            $result=or_query($query,$par_array['pars']);
            while ($p=pdo_fetch_assoc($result)) {
                $guess[]=$p;
            }
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
    echo '<span class="select is-primary select-compact"><select name="participant_id">
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
    if ($email['session_id']) {
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
    echo '</select></span>';
}

function email__expsess_select($email,$session=array(),$experiment=array(),$participant=array()) {
    global $lang;

    if (isset($session['session_id'])) {
        $selected=$session['experiment_id'].','.$session['session_id'];
    } elseif (isset($experiment['experiment_id'])) {
        $selected=$experiment['experiment_id'].',0';
    } elseif (!$email['mailbox']) {
        $selected='0,0';
    } else {
        $selected='';
    }

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
        if ($e['session_id']==null) {
            $e['session_id']=0;
        }
        $experiments[$e['experiment_id']]['sessions'][$e['session_id']]=$e;
        $experiments[$e['experiment_id']]['experiment_name']=$e['experiment_name'];
        if ((!isset($experiments[$e['experiment_id']]['lastsesstime'])) ||
            $e['session_start']>$experiments[$e['experiment_id']]['lastsesstime']) {
            $experiments[$e['experiment_id']]['lastsesstime']=$e['session_start'];
        }
    }

    // now order experiments by the date of the last session of the experiment, DESC!
    foreach ($experiments as $id=>$arr) {
        $experiments[$id]['lastsesstime_reversed']=0-$arr['lastsesstime'];
    }
    multi_array_sort($experiments,'lastsesstime_reversed');
    echo '<span class="select is-primary select-compact"><select name="expsess"><OPTION value="0,0">'.lang('select_none').'</OPTION>';

    // list special mail boxes
    $mailboxes=email__load_mailboxes();
    foreach ($mailboxes as $k=>$mb) {
        if ($k!='trash' && $k!='not_assigned') {
            echo '<OPTION value="box,'.$k.'"';
            if ($email['mailbox']==$k) {
                echo ' SELECTED';
            }
            echo '>'.$mb.'</OPTION>';
        }
    }
    foreach ($experiments as $exp_id=>$texperiment) {
        echo '<OPTION value="'.$exp_id.',0"';
        if ($selected==$exp_id.',0') {
            echo ' SELECTED';
        }
        echo '>'.$texperiment['experiment_name'].'</OPTION>'."\n";
        foreach ($texperiment['sessions'] as $tsession) {
            if ($tsession['session_id']>0) {
                $tsess_name=ortime__format(ortime__sesstime_to_unixtime($tsession['session_start']));
                echo '<OPTION value="'.$tsession['experiment_id'].','.$tsession['session_id'].'"';
                if ($selected==$tsession['experiment_id'].','.$tsession['session_id']) {
                    echo ' SELECTED';
                }
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
    echo '</select></span>';
}


// database functions
function email__load_mailboxes() {
    global $preloaded_mailboxes;
    if (isset($preloaded_mailboxes) && is_array($preloaded_mailboxes)) {
        return $preloaded_mailboxes;
    } else {
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
    if (is_array($flags) && count($flags)>0) {
        $pars=array();
        $clause=array();
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
    } else {
        return false;
    }
}

function email__switch_read_status($thread_id,$flag='read') {
    $pars=array();
    if ($flag!='assigned_to_read') {
        $flag='read';
    }
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
    $new_experiment_id=0;
    $new_session_id=0;

    if (isset($_REQUEST['expsess']) && $_REQUEST['expsess']) {
        $sent_expsess=$_REQUEST['expsess'];
    } else {
        $sent_expsess='';
    }
    if (isset($_REQUEST['participant_id']) && $_REQUEST['participant_id']) {
        $sent_participant_id=$_REQUEST['participant_id'];
    } else {
        $sent_participant_id=0;
    }
    if (isset($_REQUEST['assigned_to']) && $_REQUEST['assigned_to']) {
        $sent_assigned_to=id_array_to_db_string(multipicker_json_to_array($_REQUEST['assigned_to']));
    } else {
        $sent_assigned_to='';
    }
    if (isset($_REQUEST['flag_processed']) && $_REQUEST['flag_processed']) {
        $flag_processed=1;
    } else {
        $flag_processed=0;
    }

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
    if (isset($_REQUEST['hide_header']) && $_REQUEST['hide_header']) {
        $redir.='&hide_header=true';
    }
    return $redir;
}

function email__delete_undelete_email($email,$action) {
    if ($action=='delete') {
        $flag_deleted=1;
    } else {
        $flag_deleted=0;
    }
    $pars=array(':flag_deleted'=>$flag_deleted);
    $pars[':thread_id']=$email['thread_id'];
    $query="UPDATE ".table('emails')."
            SET flag_deleted=:flag_deleted
            WHERE thread_id=:thread_id";
    $done=or_query($query,$pars);
    $redir='admin/emails_view.php?message_id='.urlencode($email['message_id']);
    if (isset($_REQUEST['hide_header']) && $_REQUEST['hide_header']) {
        $redir.='&hide_header=true';
    }
    return $redir;
}

function email__empty_trash() {
    $query="DELETE FROM ".table('emails')."
            WHERE flag_deleted = 1";
    $done=or_query($query);
    message(lang('email_trash_emptied'));
    return '';
}

function email__get_count($col,$id,$assigned_to=0) {
    $pars=array();
    $conditions=array();
    $conditions[]="thread_id = message_id";
    $conditions[]="flag_deleted = 0";
    if ($col) {
        $pars[':id']=$id;
        $conditions[]=$col." = :id";
    }
    if ($assigned_to) {
        $ass_clause=query__get_experimenter_or_clause(array($assigned_to),'emails','assigned_to');
        $conditions[]=$ass_clause['clause'];
        foreach ($ass_clause['pars'] as $k=>$v) {
            $pars[$k]=$v;
        }
    }
    $query="SELECT flag_processed, count(*) as num_emails
            FROM ".table('emails')."
            WHERE ".implode(" AND ",$conditions)."
            GROUP BY flag_processed ";
    $result=or_query($query,$pars);
    $nums=array('num_all'=>0,'num_new'=>0);
    while ($line=pdo_fetch_assoc($result)) {
        if ($line['flag_processed']) {
            $nums['num_all']=$line['num_emails'];
        } else {
            $nums['num_new']=$line['num_emails'];
        }
    }
    $nums['num_all']=$nums['num_all']+$nums['num_new'];
    return $nums;
}

function email__get_privileges($what,$array,$priv='read',$get_nums=true) {
    global $settings, $expadmindata;
    $return=array('allowed'=>false,'num_all'=>0,'num_new'=>0,$nums['rmode']='');
    if ($settings['enable_email_module']=='y') {
        if (check_allow('emails_'.$priv.'_all')) {
            $return['allowed']=true;
            $return['rmode']='all';
            if ($get_nums) {
                if ($what=='experiment') {
                    $nums=email__get_count('experiment_id',$array['experiment_id']);
                } elseif ($what=='session') {
                    $nums=email__get_count('session_id',$array['session_id']);
                } elseif ($what=='participant' && isset($array['participant_id'])) {
                    $nums=email__get_count('participant_id',$array['participant_id']);
                } else {
                    $nums=email__get_count('',0);
                }
            }
        } elseif (check_allow('emails_'.$priv.'_experiments') && ($what=='experiment' || $what=='session')) {
            $experimenters=db_string_to_id_array($array['experimenter']);
            if (in_array($expadmindata['admin_id'],$experimenters)) {
                $return['allowed']=true;
                $return['rmode']='experiments';
                if ($get_nums) {
                    if ($what=='experiment') {
                        $nums=email__get_count('experiment_id',$array['experiment_id']);
                    } elseif ($what=='session') {
                        $nums=email__get_count('session_id',$array['session_id']);
                    }
                }
            }
        } elseif ($settings['email_module_allow_assign_emails']=='y' && check_allow('emails_'.$priv.'_assigned')) {
            $return['allowed']=true;
            $return['rmode']='assigned';
            if ($get_nums) {
                if ($what=='experiment') {
                    $nums=email__get_count('experiment_id',$array['experiment_id'],$expadmindata['admin_id']);
                } elseif ($what=='session') {
                    $nums=email__get_count('session_id',$array['session_id'],$expadmindata['admin_id']);
                } elseif ($what=='participant') {
                    $nums=email__get_count('participant_id',$array['participant_id'],$expadmindata['admin_id']);
                } else {
                    $nums=email__get_count('',0,$expadmindata['admin_id']);
                }
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
    $return=false;
    $continue=true;
    if ($settings['enable_email_module']=='y') {
        if (check_allow('emails_'.$priv.'_all')) {
            $return=true;
            $continue=false;
        }
        if ($continue && check_allow('emails_'.$priv.'_experiments') && $email['experiment_id']) {
            if (!isset($experiment['experiment_id'])) {
                $experiment=orsee_db_load_array("experiments",$email['experiment_id'],"experiment_id");
            }
            $experimenters=db_string_to_id_array($experiment['experimenter']);
            if (in_array($expadmindata['admin_id'],$experimenters)) {
                $return=true;
                $continue=false;
            }
        }
        if ($continue && $settings['email_module_allow_assign_emails']=='y' && check_allow('emails_'.$priv.'_assigned')) {
            $assigned_to=db_string_to_id_array($email['assigned_to']);
            if (in_array($expadmindata['admin_id'],$assigned_to)) {
                $return=true;
                $continue=false;
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
    } else {
        $reply_type="reply";
    }

    if (!isset($_REQUEST['send_to']) || !$_REQUEST['send_to']) {
        $continue=false;
        message(lang('error_email__to_address_not_given_or_wrong_format'),'error');
    }
    if ($continue) {
        $to_adds=explode(",",$_REQUEST['send_to']);
        foreach ($to_adds as $k=>$to_add) {
            $to_adds[$k]=trim($to_add);
            if (!preg_match($email_regex,trim($to_add))) {
                $continue=false;
            }
            if (!$continue) {
                message(lang('error_email__to_address_not_given_or_wrong_format'),'error');
            }
        }
    }

    if ($reply_type=='reply') {
        $cc_field='send_cc_reply';
    } else {
        $cc_field='send_cc_replyall';
    }
    if (isset($_REQUEST[$cc_field]) && $_REQUEST[$cc_field]) {
        $cc_adds=explode(",",$_REQUEST[$cc_field]);
    } else {
        $cc_adds=array();
    }
    foreach ($cc_adds as $k=>$cc_add) {
        $cc_adds[$k]=trim($cc_add);
        if (!preg_match($email_regex,trim($cc_add))) {
            $continue=false;
        }
        if (!$continue) {
            message(lang('error_email__cc_address_wrong_format'),'error');
        }
    }

    if (isset($_REQUEST['send_subject'])) {
        $subject=$_REQUEST['send_subject'];
    } else {
        $subject="";
    }
    if (!$subject) {
        $continue=false;
        message(lang('error_email__subject_is_empty'),'error');
    }

    if (isset($_REQUEST['send_body'])) {
        $body=$_REQUEST['send_body'];
    } else {
        $body="";
    }
    if (!$body) {
        $continue=false;
        message(lang('error_email__message_body_is_empty'),'error');
    }

    if ($continue) {
        $s['message_id']='<'.sha1(microtime()).'@'. $settings__server_url.'>';
        $s['message_type']='reply';
        $s['admin_id']=$expadmindata['admin_id'];
        $s['timestamp']=time();
        $s['from_address']=$settings['support_mail'];
        $s['to_address']=implode(",",$to_adds);
        $s['cc_address']=implode(",",$cc_adds);
        $s['subject']=email__strip_html($subject);
        $s['body']=email__strip_html($body);

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
        if ($s['cc_address']) {
            $headers=$headers."Cc: ".$s['cc_address']."\r\n";
        }
        $done=experimentmail__mail($s['to_address'],$s['subject'],$s['body'],$headers);

        // save to database
        $done=orsee_db_save_array($s,"emails",$s['message_id'],"message_id");

        // update thread time
        $done=email__update_thread_time($s['thread_id'],$s['thread_time']);

        $redir='admin/emails_view.php?message_id='.urlencode($email['message_id']);
        if (isset($_REQUEST['hide_header']) && $_REQUEST['hide_header']) {
            $redir.='&hide_header=true';
        }
        return $redir;
    } else {
        return false;
    }
}

function email__add_internal_note($email) {
    global $settings, $settings__server_url, $expadmindata;

    // checks
    $continue=true;

    if (isset($_REQUEST['note_body'])) {
        $body=$_REQUEST['note_body'];
    } else {
        $body="";
    }
    if (!$body) {
        $continue=false;
        message(lang('error_email__message_body_is_empty'),'error');
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
        if (isset($_REQUEST['hide_header']) && $_REQUEST['hide_header']) {
            $redir.='&hide_header=true';
        }
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
    for ($i = 0; $i < count($textarray); $i++) {
        $textarray[$i]="> ".$textarray[$i];
    }
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
    $atts=array();
    $attachments=array();
    if ($dbstring) {
        $atts=explode('|-!nextatt!-|',$dbstring);
    }
    foreach ($atts as $ta) {
        $attachment=db_string_to_property_array($ta);
        if ($decode) {
            $attachment['data']=base64_decode($attachment['data']);
        }
        $attachments[]=$attachment;
    }
    return $attachments;
}

?>
