<?php
// part of orsee. see orsee.org

function experimentmail__mail($recipient,$subject,$message,$headers,$env_sender="") {
    global $settings;
    $headers .= "Content-Type: text/plain; charset=\"UTF-8\"\r\n";
    $message=html_entity_decode($message,ENT_COMPAT,'UTF-8');
    $subject=html_entity_decode($subject,ENT_COMPAT,'UTF-8');
    $subject='=?UTF-8?B?'.base64_encode($subject).'?=';
    $done=experimentmail__send($recipient,$subject,$message,$headers,$env_sender="");
    return $done;
}


function experimentmail__send($recipient,$subject,$message,$headers,$env_sender="") {
    global $settings;
    if (isset($settings['bcc_all_outgoing_emails']) && $settings['bcc_all_outgoing_emails']=='y'
        && isset($settings['bcc_all_outgoing_emails__address']) && $settings['bcc_all_outgoing_emails__address']) {
        $headers=$headers."Bcc: ".$settings['bcc_all_outgoing_emails__address']."\r\n";
    }
    if (!$env_sender) $env_sender=$settings['support_mail'];
    if ($settings['email_sendmail_type']=="indirect") {
        if ($settings['email_sendmail_path']) $sendmail_path=$settings['email_sendmail_path'];
            else $sendmail_path="/usr/sbin/sendmail";
        $sendmail = $sendmail_path." -t -i -f $env_sender";
        $fd = popen($sendmail, "w");
        fputs($fd, "To: $recipient\r\n");
        fputs($fd, $headers);
        fputs($fd, "Subject: $subject\r\n");
        fputs($fd, "X-Mailer: orsee\r\n\r\n");
        fputs($fd, $message);
        pclose($fd);
        $done=true;
    } else {
        $headers="Errors-To: ".$settings['support_mail']."\r\n".$headers;
        $done=mail($recipient,$subject,$message,$headers,'-f '.$env_sender);
    }
    return $done;
}


function experimentmail__mail_attach($to, $from, $subject, $message, $filename, $filecontent,$lb="\n") {
    $mime_boundary = "<<<:" . md5(uniqid(mt_rand(), 1));
    $data = chunk_split(base64_encode($filecontent),60,"\r\n");
    $header = "From: ".$from.$lb;
    $header.= "MIME-Version: 1.0".$lb;
    $header.= "Content-Type: multipart/mixed;".$lb;
    $header.= " boundary=\"".$mime_boundary."\"".$lb;

    $content = "This is a multi-part message in MIME format.".$lb.$lb;
    $content.= "--".$mime_boundary.$lb;
    $content.= "Content-Type: text/plain; charset=\"UTF-8\"".$lb;
    $content.= "Content-Transfer-Encoding: 7bit".$lb.$lb;
    $message=html_entity_decode($message,ENT_COMPAT,'UTF-8');
    $content.= $message.$lb;
    $content.= "--".$mime_boundary.$lb;
    $content.= "Content-Disposition: attachment;".$lb;
    $content.= "Content-Type: Application/Octet-Stream; name=\"".$filename."\"".$lb;
    $content.= "Content-Transfer-Encoding: base64".$lb.$lb;
    $content.= $data.$lb;
    $content.= "--" . $mime_boundary . $lb;
    $subject=html_entity_decode($subject,ENT_COMPAT,'UTF-8');
    $subject='=?UTF-8?B?'.base64_encode($subject).'?=';
    if(experimentmail__send($to, $subject, $content, $header)) {
       return TRUE;
    }
    return FALSE;
}

function load_mail($mail_name,$lang) {
    global $authdata;
    $pars=array(':mail_name'=>$mail_name);
    $query="SELECT * FROM ".table('lang')."
            WHERE content_type='mail'
            AND content_name= :mail_name";
    $marr=orsee_query($query,$pars);
    if (isset($marr[$lang])) {
        $mailtext=$marr[$lang];
    } elseif (isset($authdata['language'])) {
        $mailtext=$marr[$authdata['language']];
    } elseif (isset($marr['en'])) {
        $mailtext=$marr['en'];
    } else {
        $mailtext='';
    }
    return $mailtext;
}

function experimentmail__load_invitation_text($experiment_id,$tlang="") {
    global $settings;
    if (!$tlang) $tlang=$settings['public_standard_language'];
    $pars=array(':experiment_id'=>$experiment_id);
    $query="SELECT * from ".table('lang')."
            WHERE content_type='experiment_invitation_mail'
            AND content_name= :experiment_id";
    $experiment_mail=orsee_query($query,$pars);
    return $experiment_mail[$tlang];
}

function experimentmail__load_bulk_mail($bulk_id,$tlang="") {
    global $settings;
    if (!$tlang) $tlang=$settings['public_standard_language'];
    $pars=array(':bulk_id'=>$bulk_id,':tlang'=>$tlang);
    $query="SELECT * from ".table('bulk_mail_texts')."
            WHERE bulk_id= :bulk_id
            AND lang= :tlang";
    $bulk_mail=orsee_query($query,$pars);
    if (!isset($bulk_mail['bulk_subject'])) {
        $tlang=$settings['public_standard_language'];
        $pars=array(':bulk_id'=>$bulk_id,':tlang'=>$tlang);
        $query="SELECT * from ".table('bulk_mail_texts')."
                WHERE bulk_id= :bulk_id
                AND lang= :tlang";
        $bulk_mail=orsee_query($query);
    }
    return $bulk_mail;
}

function experimentmail__gc_bulk_mail_texts() {
    $query="DELETE from ".table('bulk_mail_texts')." WHERE
            bulk_id NOT IN (SELECT DISTINCT bulk_id FROM or_mail_queue)";
    $done=or_query($query);
    return $done;
}

function process_mail_template($template,$vararray) {
    $output=explode("\n",$template);
    $vars=array_keys($vararray);
    foreach ($vars as $key) {
        $i=0;
        foreach ($output as $outputline) {
            $output[$i]=str_replace("#".$key."#",$vararray[$key],$output[$i]);
            $i++;
        }
    }
    $result="";
    foreach($output as $outputline) $result=$result.trim($outputline)."\n";
    return $result;
}

// lists possible sessions for given experiment
// only lists sessions with future registration end and which are not full
function experimentmail__get_session_list($experiment_id,$tlang="") {
    global $settings, $lang;
    $savelang=$lang;
    if (!$tlang) $tlang=$settings['public_standard_language'];
    if (lang('lang')!=$tlang)  $lang=load_language($tlang);
    $pars=array(':experiment_id'=>$experiment_id);
    $query="SELECT *
            FROM ".table('sessions')."
            WHERE experiment_id= :experiment_id
            AND session_status='live'
            ORDER BY session_start";
    $result=or_query($query,$pars);
    $list="";
    while ($s=pdo_fetch_assoc($result)) {
        $registration_unixtime=sessions__get_registration_end($s);
        $session_full=sessions__session_full('',$s);
        $now=time();
        if ($registration_unixtime > $now && !$session_full) {
            $list.=session__build_name($s,lang('lang')).' '.
                laboratories__get_laboratory_name($s['laboratory_id']);
            if (or_setting('include_sign_up_until_in_invitation')) {
                $list.=', '.lang('registration_until').' '.
                    ortime__format($registration_unixtime,'',lang('lang'));
            }
            $list.="\n";
        }
    }
    $lang=$savelang;
    return $list;
}

function experimentmail__send_invitations_to_queue($experiment_id,$whom="not-invited") {
    switch ($whom) {
        case "not-invited":     $aquery=" AND invited=0 "; break;
        case "all":             $aquery=""; break;
        default:                $aquery=" AND ".table('participants').".participant_id='0' ";
    }
    mt_srand((double)microtime()*1000000);
    $order="ORDER BY rand(".mt_rand().") ";
    $now=time();
    $status_query=participant_status__get_pquery_snippet("eligible_for_experiments");
    $pars=array(':experiment_id'=>$experiment_id);
    $query="INSERT INTO ".table('mail_queue')." (timestamp,mail_type,mail_recipient,experiment_id)
            SELECT ".$now.",'invitation', ".table('participants').".participant_id, experiment_id
            FROM ".table('participants').", ".table('participate_at')."
            WHERE experiment_id= :experiment_id
            AND ".table('participants').".participant_id=".table('participate_at').".participant_id ".
            $aquery."
            AND session_id = '0' AND pstatus_id = '0'";
    if ($status_query) $query.=" AND ".$status_query;
    $query.=" ".$order;
    $done=or_query($query,$pars);
    $count=pdo_num_rows($done);
    return $count;
}

function experimentmail__send_bulk_mail_to_queue($bulk_id,$part_array) {
    $return=false;
    if (is_array($part_array)) {
        $now=time(); $done=shuffle($part_array); $pars=array();
        foreach ($part_array as $participant_id) {
            $pars[]=array(':participant_id'=>$participant_id,
                        ':bulk_id'=>$bulk_id);
        }
        $query="INSERT INTO ".table('mail_queue')."
                SET timestamp='".$now."',
                mail_type='bulk_mail',
                mail_recipient= :participant_id,
                bulk_id= :bulk_id";
        $return=or_query($query,$pars);
    }
    return $return;
}


function experimentmail__send_session_reminders_to_queue($session) {
    $pars=array(':experiment_id'=>$session['experiment_id'],
                ':session_id'=>$session['session_id']);
    $query="INSERT INTO ".table('mail_queue')." (timestamp,mail_type,mail_recipient,experiment_id,session_id)
            SELECT UNIX_TIMESTAMP(),'session_reminder', participant_id, experiment_id, session_id
            FROM ".table('participate_at')."
            WHERE experiment_id= :experiment_id
            AND session_id= :session_id";
    $done=or_query($query,$pars);
    $count=pdo_num_rows($done);

    // update session table : reminder_sent
    $pars=array(':session_id'=>$session['session_id']);
    $query="UPDATE ".table('sessions')." SET reminder_sent='y' WHERE session_id= :session_id";
    $done=or_query($query,$pars);
    return $count;
}


function experimentmail__send_noshow_warnings_to_queue($session) {
    $noshow_clause=expregister__get_pstatus_query_snippet("noshow");
    $pars=array(':experiment_id'=>$session['experiment_id'],
                ':session_id'=>$session['session_id']);
    $query="INSERT INTO ".table('mail_queue')." (timestamp,mail_type,mail_recipient,experiment_id,session_id)
            SELECT UNIX_TIMESTAMP(),'noshow_warning', participant_id, experiment_id, session_id
            FROM ".table('participate_at')."
            WHERE experiment_id= :experiment_id
            AND session_id= :session_id
            AND ".$noshow_clause;
    $done=or_query($query,$pars);
    $count=pdo_num_rows($done);
    return $count;
}

function experimentmail__set_reminder_checked($session_id) {
    // update session table : reminder_checked
    $pars=array(':session_id'=>$session_id);
    $query="UPDATE ".table('sessions')." SET reminder_checked='y' WHERE session_id = :session_id";
    $done=or_query($query,$pars);
    return $done;
}

function experimentmail__set_noshow_warnings_checked($session_id) {
        // update session table : noshow_warning_sent
        $pars=array(':session_id'=>$session_id);
        $query="UPDATE ".table('sessions')." SET noshow_warning_sent='y' WHERE session_id= :session_id";
        $done=or_query($query,$pars);
        return $done;
}

function experimentmail__mails_in_queue($type="",$experiment_id="",$session_id="") {
    $pars=array();
    if ($type) {
        $tquery=" AND mail_type= :type ";
        $pars[':type']=$type;
    } else $tquery="";
    if ($experiment_id) {
        $equery=" AND experiment_id= :experiment_id ";
        $pars[':experiment_id']=$experiment_id;
    } else $equery="";
    if ($session_id) {
        $squery=" AND session_id= :session_id ";
        $pars[':session_id']=$session_id;
    } else $squery="";
    $query="SELECT count(mail_id) as number FROM ".table('mail_queue')."
            WHERE mail_id>0 ".$tquery.$equery.$squery;
    $line=orsee_query($query,$pars);
    $number=$line['number'];
    return $number;
}


function experimentmail__send_mails_from_queue($number=0,$type="",$experiment_id="",$session_id="") {
    global $settings;
    $pars=array();
    if ($number>0) {
        $limit=" LIMIT :number ";
        $pars[':number']=$number;
    } else $limit="";
    if ($type) {
        $tquery=" AND mail_type= :type ";
        $pars[':type']=$type;
    } else $tquery="";
    if ($experiment_id) {
        $equery=" AND experiment_id= :experiment_id ";
        $pars[':experiment_id']=$experiment_id;
    } else $equery="";
    if ($session_id) {
        $squery=" AND session_id= :session_id ";
        $pars[':session_id']=$session_id;
    } else $squery="";

    $smails=array(); $smails_ids=array();
    $invitations=array(); $reminders=array(); $bulks=array(); $warnings=array();
    $errors=array();
    $reminder_text=array(); $warning_text=array(); $inv_texts=array();
    $exps=array(); $sesss=array(); $parts=array(); $labs=array();
    $pform_fields=array();
    $slists=array();

    // first get mails to send
    $query="SELECT * FROM ".table('mail_queue')."
            WHERE error = '' ".
            $tquery.$equery.$squery."
            ORDER BY timestamp, mail_id ".
            $limit;
    $result=or_query($query,$pars);
    while ($line=pdo_fetch_assoc($result)) {
        $smails[]=$line;
        $smails_ids[]=$line['mail_id'];
    }

    // so we don't handle errors at all, and just delete here?!?
    //$pars=array();
    //foreach ($smails_ids as $id) $pars[]=array(':id'=>$id);
    //$query="DELETE FROM ".table('mail_queue')."
    //      WHERE mail_id = :id";
    //$done=or_query($query,$pars);

    foreach ($smails as $line) {
        $texp=$line['experiment_id'];
        $tsess=$line['session_id'];
        $tpart=$line['mail_recipient'];
        $ttype=$line['mail_type'];
        $tbulk=$line['bulk_id'];
        $continue=true;

        // well, if experiment_id, session_id, recipient, footer or inv_text, add to array
        if (!isset($exps[$texp]) && $texp)  $exps[$texp]=orsee_db_load_array("experiments",$texp,"experiment_id");
        if (!isset($sesss[$tsess]) && $tsess) $sesss[$tsess]=orsee_db_load_array("sessions",$tsess,"session_id");
        if (!isset($parts[$tpart]) && $tpart) $parts[$tpart]=orsee_db_load_array("participants",$tpart,"participant_id");
        $tlang=$parts[$tpart]['language'];
        if (!isset($footers[$tlang])) $footers[$tlang]=load_mail("public_mail_footer",$tlang);
        if ($ttype=="session_reminder" && !isset($reminder_text[$texp][$tlang])) {
            $mailtext=false;
            if ($settings['enable_session_reminder_customization']=='y')
                $mailtext=experimentmail__get_customized_mailtext('experiment_session_reminder_mail',$texp,$tlang);
            if (!isset($mailtext) || !$mailtext || !is_array($mailtext)) {
                $mailtext['subject']=load_language_symbol('email_session_reminder_subject',$tlang);
                $mailtext['body']=load_mail("public_session_reminder",$tlang);
            }
            $reminder_text[$texp][$tlang]=$mailtext;
        }
        if ($ttype=="noshow_warning" && !isset($warning_text[$tlang])) {
            $warning_text[$tlang]['text']=load_mail("public_noshow_warning",$tlang);
            $warning_text[$tlang]['subject']=load_language_symbol('email_noshow_warning_subject',$tlang);
        }
        if (($ttype=="session_reminder" || $ttype=="noshow_warning") && !isset($labs[$tsess][$tlang])) {
            $labs[$tsess][$tlang]=laboratories__get_laboratory_text($sesss[$tsess]['laboratory_id'],$tlang);
        }


        if ($ttype=="invitation" && !isset($inv_texts[$texp][$tlang]))
            $inv_texts[$texp][$tlang]=experimentmail__load_invitation_text($texp,$tlang);
        if ($ttype=="invitation" && !isset($slists[$texp][$tlang]))
            $slists[$texp][$tlang]=experimentmail__get_session_list($texp,$tlang);
        if ($ttype=="bulk_mail" && !isset($bulk_mails[$tbulk][$tlang]))
                        $bulk_mails[$tbulk][$tlang]=experimentmail__load_bulk_mail($tbulk,$tlang);

        // check for missing values ...
        if (!isset($parts[$tpart]['participant_id'])) {
            $continue=false;
            // email error: no recipient
            $line['error'].="no_recipient:";
        } else {
            if (!isset($pform_fields[$tlang])) $pform_fields[$tlang]=participant__load_participant_email_fields($tlang);
            $parts[$tpart]=experimentmail__fill_participant_details($parts[$tpart],$pform_fields[$tlang]);
        }

        if (!isset($exps[$texp]['experiment_id']) && ($ttype=="invitation" || $ttype=="session_reminder" || $ttype=="noshow_warning")) {
            $continue=false;
            // email error: no experiment id given
            $line['error'].="no_experiment:";
        }

        if (!isset($sesss[$tsess]['session_id']) && ($ttype=="session_reminder" || $ttype=="noshow_warning")) {
            $continue=false;
            // email error: no session id given
            $line['error'].="no_session:";
        }

        if (!isset($inv_texts[$texp][$tlang]) && $ttype=="invitation") {
            $continue=false;
            // email error: no inv_text given
            $line['error'].="no_inv_text:";
        }

        if (!isset($bulk_mails[$tbulk][$tlang]) && $ttype=="bulk_mail") {
            $continue=false;
            // email error: no bulk_mail given
            $line['error'].="no_bulk_mail_text:";
        }

        // fine, if no errors, add to arrays
        if ($continue) {
            switch ($line['mail_type']) {
                case "invitation":
                    $invitations[]=$line;
                    break;
                case "session_reminder":
                    $reminders[]=$line;
                    break;
                case "noshow_warning":
                    $warnings[]=$line;
                    break;
                case "bulk_mail":
                    $bulks[]=$line;
                    break;
            }
        } else {
            $errors[]=$line;
        }
    }

    // fine now we have everything we want, and we can proceed with sending the mails

    $mails_sent=0; $mails_errors=0; $invmails_not_sent=0;

    // reminders
    foreach ($reminders as $mail) {
        $tlang=$parts[$mail['mail_recipient']]['language'];
        $done=experimentmail__send_session_reminder_mail($mail,$parts[$mail['mail_recipient']],
        $exps[$mail['experiment_id']],$sesss[$mail['session_id']],
        $reminder_text[$mail['experiment_id']][$tlang],$labs[$mail['session_id']][$tlang],
        $footers[$tlang]);
        if ($done) {
            $mails_sent++;
            $deleted=experimentmail__delete_from_queue($mail['mail_id']);
        } else {
            $mail['error']="sending";
            $errors[]=$mail;
        }
    }

    // noshow warnings
    foreach ($warnings as $mail) {
        $tlang=$parts[$mail['mail_recipient']]['language'];
        $done=experimentmail__send_noshow_warning_mail($mail,$parts[$mail['mail_recipient']],
        $exps[$mail['experiment_id']],$sesss[$mail['session_id']],
        $warning_text[$tlang],$labs[$mail['session_id']][$tlang],
        $footers[$tlang]);
        if ($done) {
            $mails_sent++;
            $deleted=experimentmail__delete_from_queue($mail['mail_id']);
        } else {
            $mail['error']="sending";
            $errors[]=$mail;
        }
    }

    // invitations
    foreach ($invitations as $mail) {
        $tlang=$parts[$mail['mail_recipient']]['language'];
        if ($exps[$mail['experiment_id']]['experiment_type']=='laboratory' && (!trim($slists[$mail['experiment_id']][$tlang]))) {
            $done=true; // do not send invitation when session_list is empty!
            $invmails_not_sent++;
        } else {
            $done=experimentmail__send_invitation_mail($mail,$parts[$mail['mail_recipient']],
            $exps[$mail['experiment_id']],$inv_texts[$mail['experiment_id']][$tlang],
            $slists[$mail['experiment_id']][$tlang],$footers[$tlang]);
            if ($done) $mails_sent++;
        }
        if ($done) {
            $deleted=experimentmail__delete_from_queue($mail['mail_id']);
        } else {
            $mail['error']="sending";
            $errors[]=$mail;
        }
    }

    // bulks
    foreach ($bulks as $mail) {
        $tlang=$parts[$mail['mail_recipient']]['language'];
        $done=experimentmail__send_bulk_mail($mail,$parts[$mail['mail_recipient']],$bulk_mails[$mail['bulk_id']][$tlang],$footers[$tlang]);
        if ($done) {
            $mails_sent++;
            $deleted=experimentmail__delete_from_queue($mail['mail_id']);
        } else {
            $mail['error']="sending";
            $errors[]=$mail;
        }
    }
    $done=experimentmail__gc_bulk_mail_texts();

    // handle errors
    $pars=array(); $mails_errors=count($errors);
    if ($mails_errors>0) {
        foreach ($errors as $mail) $pars[]=array(':error'=>$mail['error'],':mail_id'=>$mail['mail_id']);
        $query="UPDATE ".table('mail_queue')."
                SET error= :error
                WHERE mail_id= :mail_id";
        $done=or_query($query,$pars);
    }
    $mess['mails_sent']=$mails_sent;
    $mess['mails_invmails_not_sent']=$invmails_not_sent;
    $mess['mails_errors']=$mails_errors;
    return $mess;
}

function experimentmail__delete_from_queue($mail_id) {
    $pars=array(':mail_id'=>$mail_id);
    $query="DELETE FROM ".table('mail_queue')."
            WHERE mail_id= :mail_id";
    $result=or_query($query,$pars);
    return $result;
}

function experimentmail__fill_participant_details($participant_array,$pform_fields) {
    // $participant_array is a row from or_participants
    // $pform_fields is loaded with participant__load_participant_email_fields(lang)
    // return value is the participant array with ids replaced by values from or_lang
    foreach ($pform_fields as $f) {
        if(preg_match("/(radioline|select_list|select_lang|radioline_lang)/",$f['type']) && isset($f['lang'][$participant_array[$f['mysql_column_name']]]))
            $participant_array[$f['mysql_column_name']]=$f['lang'][$participant_array[$f['mysql_column_name']]];
    }
    return $participant_array;
}

function experimentmail__preview_fake_participant_details($pform_fields) {
    // $pform_fields is loaded with participant__load_participant_email_fields(lang)
    // return value is a fake participant array with ids replaced by names of columns
    $participant_array=array('participant_id'=>0,'participant_id_crypt'=>'UVWXYZ');
    foreach ($pform_fields as $f) {
        $participant_array[$f['mysql_column_name']]=$f['column_name'];
    }
    return $participant_array;
}

function experimentmail__preview_fake_session_details($experiment_id) {
    $pars=array(':experiment_id'=>$experiment_id);
    $query="SELECT * FROM ".table('sessions')."
            WHERE experiment_id = :experiment_id
            ORDER BY if(session_status='live',0,1), session_start DESC
            LIMIT 1";
    $session=orsee_query($query,$pars);
    if (!isset($session['session_id'])) {
        $session=array();
        $session['session_start']=ortime__unixtime_to_sesstime();
        $session['session_duration_hour']=1;
        $session['session_duration_minute']=30;
        $labs=laboratories__get_laboratories();
        $randlab=array_rand($labs);
        $session['laboratory_id']=$randlab;
    }
    return $session;
}

function experimentmail__get_session_reminder_details($part,$exp,$session,$lab) {
    $part['edit_link']=experimentmail__build_edit_link($part);
    $part['enrolment_link']=experimentmail__build_lab_registration_link($part);
    $part['experiment_name']=$exp['experiment_public_name'];
    $part['session_date']=session__build_name($session,$part['language']);
    $part['lab_name']=laboratories__strip_lab_name($lab);
    $part['lab_address']=laboratories__strip_lab_address($lab);
    return $part;
}

function experimentmail__send_session_reminder_mail($mail,$part,$exp,$session,$reminder_text,$lab,$footer) {
    global $settings;
    $part=experimentmail__get_session_reminder_details($part,$exp,$session,$lab);
    $mailtext=stripslashes($reminder_text['body']);
    $subject=$reminder_text['subject'];
    $recipient=$part['email'];
    $message=process_mail_template($mailtext,$part)."\n".process_mail_template($footer,$part);
    $sender=experimentmail__get_sender_email($exp);
    $headers="From: ".$sender."\r\n";
    $done=experimentmail__mail($recipient,$subject,$message,$headers);
    return $done;
}

function experimentmail__get_noshow_warning_details($part,$exp,$session,$lab) {
    global $settings;
    $part['edit_link']=experimentmail__build_edit_link($part);
    $part['enrolment_link']=experimentmail__build_lab_registration_link($part);
    $part['experiment_name']=$exp['experiment_public_name'];
    $part['session_date']=session__build_name($session,$part['language']);
    $part['lab_name']=laboratories__strip_lab_name($lab);
    $part['lab_address']=laboratories__strip_lab_address($lab);
    $part['max_noshows']=$settings['automatic_exclusion_noshows'];
    return $part;
}


function experimentmail__send_noshow_warning_mail($mail,$part,$exp,$session,$warning_text,$lab,$footer) {
    global $settings;
    $part=experimentmail__get_noshow_warning_details($part,$exp,$session,$lab);
    $mailtext=stripslashes($warning_text['text']);
    $subject=$warning_text['subject'];
    $recipient=$part['email'];
    $message=process_mail_template($mailtext,$part)."\n".process_mail_template($footer,$part);
    $sender=$settings['support_mail'];
    $headers="From: ".$sender."\r\n";
    $done=experimentmail__mail($recipient,$subject,$message,$headers);
    return $done;
}

function experimentmail__send_participant_exclusion_mail($part) {
    global $settings;
    $mailtext=stripslashes(load_mail("public_participant_exclusion",$part['language']));
    $subject=load_language_symbol('participant_exclusion_mail_subject',$part['language']);
    $recipient=$part['email'];
    $message=process_mail_template($mailtext,$part);
    $sender=$settings['support_mail'];
    $headers="From: ".$sender."\r\n";
    $done=experimentmail__mail($recipient,$subject,$message,$headers);
    return $done;
}

function experimentmail__send_reminder_notice($line,$number,$sent,$disclaimer="") {
    global $settings;
    $experimenters=db_string_to_id_array($line['experimenter_mail']);
    foreach ($experimenters as $experimenter) {
        $mail=orsee_db_load_array("admin",$experimenter,"admin_id");
        $tlang= ($mail['language']) ? $mail['language'] : $settings['admin_standard_language'];
        $lang=load_language($tlang);
        $mail['session_name']=session__build_name($line,$tlang);
        $mail['experiment_name']=$line['experiment_name'];
        $mail['nr_participants'] = ($sent) ? $number : 0;
        switch ($disclaimer) {
            case 'part_needed':
                $mail['disclaimer']=load_language_symbol('reminder_not_sent_part_needed',$tlang);
                break;
            case 'part_reserve':
                $mail['disclaimer']=load_language_symbol('reminder_not_sent_part_reserve',$tlang);
                break;
            default:
                $mail['disclaimer']="";
        }

        if ($mail['disclaimer']) $sub_notice=load_language_symbol('subject_for_session_reminder_error_notice',$tlang);
        else $sub_notice=load_language_symbol('subject_for_session_reminder_ok_notice',$tlang);
        $recipient=$mail['email'];
        $subject=$sub_notice.' '.$mail['experiment_name'].' '.$mail['session_name'];
        $mailtext=load_mail("admin_session_reminder_notice",$tlang);
        $message=process_mail_template($mailtext,$mail);
        $headers="From: ".$settings['support_mail']."\r\n";
        $done=experimentmail__mail($recipient,$subject,$message,$headers);
    }
    return $done;
}

function experimentmail__get_invitation_mail_details($part,$exp,$slist) {
    global $settings;
    $part['edit_link']=experimentmail__build_edit_link($part);
    $part['enrolment_link']=experimentmail__build_lab_registration_link($part);
    $part['experiment_name']=$exp['experiment_public_name'];
    $part['sessionlist']=$slist;
    $part['link']=experimentmail__build_lab_registration_link($part);
    return $part;
}


function experimentmail__send_invitation_mail($mail,$part,$exp,$inv_text,$slist,$footer) {
    global $settings;
    $part=experimentmail__get_invitation_mail_details($part,$exp,$slist);
    // split in subject and text
    $subject=stripslashes(str_replace(strstr($inv_text,"\n"),"",$inv_text));
    $mailtext=stripslashes(substr($inv_text,strpos($inv_text,"\n")+1,strlen($inv_text)));
    $recipient=$part['email'];
    $message=process_mail_template($mailtext,$part)."\n".process_mail_template($footer,$part);
    $sender=experimentmail__get_sender_email($exp);
    $headers="From: ".$sender."\r\n";
    $done=experimentmail__mail($recipient,$subject,$message,$headers);
    $done2=experimentmail__update_invited_flag($mail);
    return $done;
}

function experimentmail__get_bulk_mail_details($part) {
        $part['edit_link']=experimentmail__build_edit_link($part);
        $part['enrolment_link']=experimentmail__build_lab_registration_link($part);
        return $part;
}

function experimentmail__send_bulk_mail($mail,$part,$bulk_mail,$footer) {
        global $settings;
        $part=experimentmail__get_bulk_mail_details($part);
        // split in subject and text
        $subject=stripslashes($bulk_mail['bulk_subject']);
        $mailtext=stripslashes($bulk_mail['bulk_text']);
        $recipient=$part['email'];
        $message=process_mail_template($mailtext,$part)."\n".process_mail_template($footer,$part);
        $sender=$settings['support_mail'];
        $headers="From: ".$sender."\r\n";
        $done=experimentmail__mail($recipient,$subject,$message,$headers);
        return $done;
}

function experimentmail__update_invited_flag($mail) {
    $pars=array(':participant_id'=>$mail['mail_recipient'],
                ':experiment_id'=>$mail['experiment_id']);
    $query="UPDATE ".table('participate_at')."
            SET invited=1
            WHERE participant_id= :participant_id
            AND experiment_id= :experiment_id";
    $result=or_query($query,$pars);
    return $result;
}

function experimentmail__build_edit_link($participant) {
    global $settings__root_url, $settings;
    if (isset($settings['subject_authentication']) && $settings['subject_authentication']=='username_password') $token_string='';
    else $token_string="?p=".urlencode($participant['participant_id_crypt']);
    $edit_link=$settings__root_url."/public/participant_edit.php".$token_string;
    return $edit_link;
}

function experimentmail__build_lab_registration_link($participant) {
    global $settings__root_url, $settings;
    if (isset($settings['subject_authentication']) && $settings['subject_authentication']=='username_password') $token_string='';
    else $token_string="?p=".urlencode($participant['participant_id_crypt']);
    $reg_link=$settings__root_url."/public/participant_show.php".$token_string;
    return $reg_link;
}

function experimentmail__mail_edit_link($participant_id) {
    global $lang, $authdata, $settings;
    $participant=orsee_db_load_array("participants",$participant_id,"participant_id");
    $participant['edit_link']=experimentmail__build_edit_link($participant);
    $participant['enrolment_link']=experimentmail__build_lab_registration_link($participant);
    if (isset($_SESSION['authdata']['language']) && $_SESSION['authdata']['language']) $maillang=$_SESSION['authdata']['language'];
    else $maillang=$participant['language'];
    $mailtext=load_mail("public_mail_footer",$maillang);
    $message=process_mail_template($mailtext,$participant);
    $headers="From: ".$settings['support_mail']."\r\n";
    experimentmail__mail($participant['email'],lang('subject_for_edit_link_mail'),$message,$headers);
}

function experimentmail__mail_pwreset_link($participant) {
    global $lang, $settings;
    $message=experimentmail__get_pwreset_mail_text($participant);
    $headers="From: ".$settings['support_mail']."\r\n";
    experimentmail__mail($participant['email'],lang('password_reset_email_subject'),$message,$headers);
}

function experimentmail__get_pwreset_mail_text($participant) {
    global $authdata, $lang, $settings__root_url, $settings;
    $pform_fields=participant__load_participant_email_fields();
    foreach ($pform_fields as $f) {
        if(preg_match("/(radioline|select_list|select_lang|radioline_lang)/",$f['type']) && isset($participant[$f['mysql_column_name']]) && isset($f['lang'][$participant[$f['mysql_column_name']]]))
        $participant[$f['mysql_column_name']]=$f['lang'][$participant[$f['mysql_column_name']]];
    }
    $exptype_ids=db_string_to_id_array($participant['subscriptions']);
    $exptypes=load_external_experiment_types();
    $invnames=array();
    foreach ($exptype_ids as $exptype_id) $invnames[]=$exptypes[$exptype_id][lang('lang')];
    $participant['invitations']=implode(", ",$invnames);
    $participant['password_reset_link']=$settings__root_url."/public/participant_reset_pw.php?t=".urlencode($participant['pwreset_token']);
    $maillang=experimentmail__get_language($participant['language']);
    $mailtext=load_mail("public_password_reset",$maillang);
    $message=process_mail_template($mailtext,$participant);
    return $message;
}


function experimentmail__get_language($partlang) {
    global $authdata;
    if (isset($authdata['language']) && $authdata['language']) $maillang=$authdata['language'];
    else $maillang=$partlang;
    return $maillang;
}

function experimentmail__get_admin_language($adminlang) {
    global $authdata, $settings;
    if (isset($authdata['language']) && $authdata['language']) $maillang=$authdata['language'];
    elseif ($adminlang) $maillang=$adminlang;
    else $maillang=$settings['admin_standard_language'];
    return $maillang;
}

function experimentmail__get_mail_footer($participant) {
    global $lang, $settings;
    $participant['edit_link']=experimentmail__build_edit_link($participant);
    $participant['enrolment_link']=experimentmail__build_lab_registration_link($participant);
    $maillang=experimentmail__get_language($participant['language']);
    $mailtext=load_mail("public_mail_footer",$maillang);
    $footer=process_mail_template($mailtext,$participant);
    return $footer;
}

function experimentmail__get_admin_footer($maillang='',$admin) {
    if (!$maillang) $maillang=experimentmail__get_admin_language($admin['language']);
    $mailtext=load_mail("admin_mail_footer",$maillang);
    $footer=process_mail_template($mailtext,$admin);
    return $footer;
}

function experimentmail__confirmation_mail($participant) {
    global $authdata, $lang, $settings__root_url, $settings;
    $message=experimentmail__get_confirmation_mail_text($participant);
    $headers="From: ".$settings['support_mail']."\r\n";
    experimentmail__mail($participant['email'],lang('registration_email_subject'),$message,$headers);
}

function experimentmail__get_confirmation_mail_text($participant) {
    global $authdata, $lang, $settings__root_url, $settings;
    $pform_fields=participant__load_participant_email_fields();
    foreach ($pform_fields as $f) {
        if(preg_match("/(radioline|select_list|select_lang|radioline_lang)/",$f['type']) && isset($participant[$f['mysql_column_name']]) && isset($f['lang'][$participant[$f['mysql_column_name']]]))
        $participant[$f['mysql_column_name']]=$f['lang'][$participant[$f['mysql_column_name']]];
    }
    $exptype_ids=db_string_to_id_array($participant['subscriptions']);
    $exptypes=load_external_experiment_types();
    $invnames=array();
    foreach ($exptype_ids as $exptype_id) $invnames[]=$exptypes[$exptype_id][lang('lang')];
    $participant['invitations']=implode(", ",$invnames);
    $participant['registration_link']=$settings__root_url."/public/participant_confirm.php?c=".urlencode($participant['confirmation_token']);
    $maillang=experimentmail__get_language($participant['language']);
    $mailtext=load_mail("public_system_registration",$maillang);
    $message=process_mail_template($mailtext,$participant);
    return $message;
}


function experimentmail__get_experiment_registration_details($part,$exp,$sess,$lab) {
    $part['lab_name']=laboratories__strip_lab_name($lab);
    $part['lab_address']=laboratories__strip_lab_address($lab);
    $part['session']=session__build_name($sess,$part['language']);
    $part['experiment']=$exp['experiment_public_name'];
    $part['duration']=$sess['session_duration_hour'].":".$sess['session_duration_minute'];
    return $part;
}

function experimentmail__get_customized_mailtext($type,$experiment_id,$maillang="") {
    if (!$maillang) $maillang=lang('lang'); $mailtext=array();
    $fulltext=language__get_item($type,$experiment_id,$maillang);
    if ($fulltext) {
        $mailtext['subject']=str_replace(strstr($fulltext,"\n"),"",$fulltext);
        $mailtext['body']=substr($fulltext,strpos($fulltext,"\n")+1,strlen($fulltext));
        return $mailtext;
    } else {
        return false;
    }
}


function experimentmail__get_sender_email($experiment) {
    global $settings;
    if ($settings['enable_editing_of_experiment_sender_email']=='y' && $experiment['sender_mail'])
        return $experiment['sender_mail'];
    else return $settings['support_mail'];
}

function experimentmail__experiment_registration_mail($participant,$session) {
    global $lang, $settings;
    // load experiment
    $experiment=orsee_db_load_array("experiments",$session['experiment_id'],"experiment_id");

    $maillang=experimentmail__get_language($participant['language']);

    // load laboratory
    $lab=laboratories__get_laboratory_text($session['laboratory_id'],$maillang);

    $pform_fields=participant__load_participant_email_fields($maillang);
    $experimentmail=experimentmail__fill_participant_details($participant,$pform_fields);
    $experimentmail=experimentmail__get_experiment_registration_details($experimentmail,$experiment,$session,$lab);

    $mailtext=false;
    if ($settings['enable_enrolment_confirmation_customization']=='y')
        $mailtext=experimentmail__get_customized_mailtext('experiment_enrolment_conf_mail',$session['experiment_id'],$maillang);
    if (!isset($mailtext) || !$mailtext || !is_array($mailtext)) {
        $mailtext['subject']=load_language_symbol('enrolment_email_subject',$maillang);
        $mailtext['body']=load_mail("public_experiment_registration",$maillang);
    }

    $message=process_mail_template($mailtext['body'],$experimentmail);
    $message=$message."\n".experimentmail__get_mail_footer($participant);
    $sendermail=experimentmail__get_sender_email($experiment);
    $headers="From: ".$sendermail."\r\n";
    experimentmail__mail($participant['email'],$mailtext['subject'],$message,$headers);
}

function experimentmail__experiment_cancellation_mail($participant,$session) {
    global $lang, $settings;
    // load experiment
    $experiment=orsee_db_load_array("experiments",$session['experiment_id'],"experiment_id");

    $maillang=experimentmail__get_language($participant['language']);

    // load laboratory
    $lab=laboratories__get_laboratory_text($session['laboratory_id'],$maillang);

    $pform_fields=participant__load_participant_email_fields($maillang);
    $experimentmail=experimentmail__fill_participant_details($participant,$pform_fields);
    $experimentmail=experimentmail__get_experiment_registration_details($experimentmail,$experiment,$session,$lab);

    $mailtext['subject']=load_language_symbol('enrolment_cancellation_email_subject',$maillang);
    $mailtext['body']=load_mail("public_experiment_enrolment_cancellation",$maillang);

    $message=process_mail_template($mailtext['body'],$experimentmail);
    $message=$message."\n".experimentmail__get_mail_footer($participant);
    $sendermail=experimentmail__get_sender_email($experiment);
    $headers="From: ".$sendermail."\r\n";
    experimentmail__mail($participant['email'],$mailtext['subject'],$message,$headers);
}



function experimentmail__send_registration_notice($line) {
    global $settings;
    $reg=experiment__count_participate_at($line['experiment_id'],$line['session_id']);
    $experimenters=db_string_to_id_array($line['experimenter_mail']);

    foreach ($experimenters as $experimenter) {
        $admin=orsee_db_load_array("admin",$experimenter,"admin_id");
        if (isset($admin['admin_id'])) {
            $tlang= ($admin['language']) ? $admin['language'] : $settings['admin_standard_language'];
            $lang=load_language($tlang);
            $admin['session_name']=session__build_name($line,$tlang);
            $admin['experiment_name']=$line['experiment_name'];
            $admin['registered'] = $reg;
            $admin['status']=session__get_status($line,$tlang,$reg);
            $admin['needed']=$line['part_needed'];
            $admin['reserve']=$line['part_reserve'];
            $subject=load_language_symbol('subject_for_registration_notice',$tlang);
            $subject.=' '.$admin['experiment_name'].', '.$admin['session_name'];
            $recipient=$admin['email'];
            $mailtext=load_mail("admin_registration_notice",$tlang)."\n".
                        experimentmail__get_admin_footer($tlang,$admin)."\n";
            $message=process_mail_template($mailtext,$admin);
            $now=time();
            $list_name=lang('participant_list_filename').' '.date("Y-m-d",$now);
            $list_filename=str_replace(" ","_",$list_name).".pdf";
            $list_file=pdfoutput__make_part_list($line['experiment_id'],$line['session_id'],'registered','lname,fname',true,$tlang);
            $done=experimentmail__mail_attach($recipient,$settings['support_mail'],$subject,$message,$list_filename,$list_file);
        }
    }

    // update session table : reg_notice_sent
    $pars=array(':session_id'=>$line['session_id']);
    $query="UPDATE ".table('sessions')." SET reg_notice_sent='y' WHERE session_id= :session_id ";
    $done2=or_query($query,$pars);
    return $done;
}


function experimentmail__send_calendar() {
    global $lang, $settings;
    $now=time();
    if (isset($settings['emailed_calendar_included_months']) && $settings['emailed_calendar_included_months']>0)
            $number_of_months=$settings['emailed_calendar_included_months']-1;
    else $number_of_months=1;
    $from=$settings['support_mail'];

    // remember the current language for later reset
    $old_lang=lang('lang');

    // preload details with current language
    $maillang=$old_lang;
    $mail_subject=lang('experiment_calendar').' '.ortime__format($now,'hide_time:true');
    $cal_name=lang('experiment_calendar').' '.date("Y-m-d",$now);
    $cal_filename=str_replace(" ","_",$cal_name).".pdf";
    $cal_file=pdfoutput__make_pdf_calendar($now,false,true,$number_of_months,true);

    // get experimenters who want to receive the calendar
    $query="SELECT *
            FROM ".table('admin')."
            WHERE get_calendar_mail='y'
            AND disabled='n'
            ORDER BY language";
    $result=or_query($query);
    $i=0; $rec_count=pdo_num_rows($result);
    while ($admin = pdo_fetch_assoc($result)) {
        if ($admin['language'] != $maillang) {
            $maillang=$admin['language'];
            $lang=load_language($maillang);
            $mail_subject=lang('experiment_calendar').' '.ortime__format($now,'hide_time:true',$maillang);
            $cal_name=lang('experiment_calendar').' '.date("Y-m-d",$now);
            $cal_filename=str_replace(" ","_",$cal_name).".pdf";
            $cal_file=pdfoutput__make_pdf_calendar($now,false,true,$number_of_months,true);
        }
        $mailtext=load_mail("admin_calendar_mailtext",$maillang)."\n".
                    experimentmail__get_admin_footer($maillang,$admin)."\n";
        $message=process_mail_template($mailtext,$admin);
        $done=experimentmail__mail_attach($admin['email'],$from,$mail_subject,$message,$cal_filename,$cal_file);
        if ($done) $i++;
    }
    // reset language
    if ($maillang!=$old_lang) $lang=load_language($old_lang);
    return $cal_name." sent to ".$i." out of ".$rec_count." administrators\n";
}



function experimentmail__send_participant_statistics() {
    global $lang, $settings;
    $now=time();
    $from=$settings['support_mail'];
    $headers="From: ".$from."\r\n";

    // remember the current language for later reset
    $old_lang=lang('lang');

    // preload details with current language
    $maillang=$old_lang;
    $statistics=stats__get_textstats_for_email();
    $subject=load_language_symbol('subject_pool_statistics',$maillang).' '.
                    ortime__format($now,'hide_time:true');

    // get experimenters who want to receive the statistics
    $query="SELECT *
            FROM ".table('admin')."
            WHERE get_statistics_mail='y'
            AND disabled='n'
            ORDER BY language";
    $result=or_query($query);

    $i=0; $rec_count=pdo_num_rows($result);
    while ($admin = pdo_fetch_assoc($result)) {
        if ($admin['language'] != $maillang) {
            $maillang=$admin['language'];
            $lang=load_language($maillang);
            $statistics=stats__get_textstats_for_email();
            $subject=load_language_symbol('subject_pool_statistics',$maillang).' '.
            ortime__format($now,'hide_time:true',$maillang);
        }
        $mailtext=load_mail("admin_participant_statistics_mailtext",$maillang).
                    "\n\n".$statistics."\n".
        experimentmail__get_admin_footer($maillang,$admin)."\n";
        $message=process_mail_template($mailtext,$admin);
        $done=experimentmail__mail($admin['email'],$subject,$message,$headers);
        if ($done) $i++;
    }
    // reset language
    if ($maillang!=$old_lang) $lang=load_language($old_lang);
    return "statistics sent to ".$i." out of ".$rec_count." administrators\n";
}

function experimentmail__bulk_mail_form() {
    global $lang;
    //echo '<A HREF="participants_bulk_mail.php">'.lang('send_mail_to_listed_participants').'</A>';
    echo button_link("participants_bulk_mail.php",lang('send_mail_to_listed_participants'),"envelope-o");
}


?>
