<?php 

// experimentmail functions. part of orsee. see orsee.org
//

function experimentmail__mail($recipient,$subject,$message,$headers,$env_sender="") {
	global $settings;

	if ($settings['email_sendmail_type']=="indirect") {
		if (!$env_sender) $env_sender=$settings['support_mail'];
		if ($settings['email_sendmail_path']) $sendmail_path=$settings['email_sendmail_path'];
			else $sendmail_path="/usr/sbin/sendmail";

		$sendmail = $sendmail_path." -t -f $env_sender";

		$fd = popen($sendmail, "w");
		fputs($fd, "To: $recipient\r\n");
		fputs($fd, $headers);
		fputs($fd, "Subject: $subject\r\n");
		fputs($fd, "X-Mailer: orsee\r\n\r\n");
		fputs($fd, $message);
		pclose($fd);
		$done=true;
		}
	   else {
		$done=mail($recipient,$subject,$message,$headers);
		}
	return $done;
}


function load_mail($mail_name,$lang) {
	global $authdata;

	$query="SELECT * FROM ".table('lang')." 
		WHERE content_type='mail'
		AND content_name='".$mail_name."'";
	$marr=orsee_query($query);
	if (isset($marr[$lang])) $mailtext=$marr[$lang];
		else $mailtext=$marr[$authdata['language']];
	return $mailtext;
}

function experimentmail__load_invitation_text($experiment_id,$tlang="") {
	global $settings;
	if (!$tlang) $tlang=$settings['public_standard_language'];
	$query="SELECT * from ".table('lang')."
                WHERE content_type='experiment_invitation_mail'
                AND content_name='".$experiment_id."'";
        $experiment_mail=orsee_query($query);
	return $experiment_mail[$tlang];
}

function experimentmail__load_bulk_mail($bulk_id,$tlang="") {
        global $settings;
        if (!$tlang) $tlang=$settings['public_standard_language'];
        $query="SELECT * from ".table('bulk_mail_texts')."
                WHERE bulk_id='".$bulk_id."'
                AND lang='".$tlang."'";
        $bulk_mail=orsee_query($query);
	if (!$bulk_mail['bulk_subject']) {
		$query="SELECT * from ".table('bulk_mail_texts')."
                WHERE bulk_id='".$bulk_id."'
                AND lang='".$settings['public_standard_language']."'";
        	$bulk_mail=orsee_query($query);
		}
        return $bulk_mail;
}


function experimentmail__gc_bulk_mail_texts() {
	$active_bulks=array();
        $query="SELECT ".table('bulk_mail_texts').".bulk_id from ".table('bulk_mail_texts').", ".table('mail_queue')." 
		WHERE ".table('bulk_mail_texts').".bulk_id=".table('mail_queue').".bulk_id 
                ORDER BY ".table('bulk_mail_texts').".bulk_id";
        $result=mysql_query($query) or die("Database error: " . mysql_error());
	while ($line=mysql_fetch_assoc($result)) {
		$active_bulks[]=$line['bulk_id'];
		}
	$bulk_string=implode("','",$active_bulks);
        $query="DELETE from ".table('bulk_mail_texts')."
                WHERE bulk_id NOT IN ('".$bulk_string."')";
        $done=mysql_query($query);
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
        foreach($output as $outputline) 
              $result=$result.$outputline."\n";
        return $result;
}

function experimentmail__mail_attach($to, $from, $subject, $message, $filename, $filecontent,$lb="\r\n") {
   // $to Recipient
   // $from Sender (like "email@domain.com" or "Name <email@domain.com>")
   // $subject Subject
   // $message Content
   // $file File (on server) to attach
   $mime_boundary = "<<<:" . md5(uniqid(mt_rand(), 1));
   $data = chunk_split(base64_encode($filecontent),60,"\r\n");
   $header = "From: ".$from.$lb;
   //$header.= "To: ".$to.$lb;
   $header.= "MIME-Version: 1.0".$lb;
   $header.= "Content-Type: multipart/mixed;".$lb;
   $header.= " boundary=\"".$mime_boundary."\"".$lb;
   
   $content = "This is a multi-part message in MIME format.".$lb.$lb;
   $content.= "--".$mime_boundary.$lb;
   $content.= "Content-Type: text/plain; charset=\"iso-8859-1\"".$lb;
   $content.= "Content-Transfer-Encoding: 7bit".$lb.$lb;
   $content.= $message.$lb;
   $content.= "--".$mime_boundary.$lb;
   $content.= "Content-Disposition: attachment;".$lb;
   $content.= "Content-Type: Application/Octet-Stream; name=\"".$filename."\"".$lb;
   $content.= "Content-Transfer-Encoding: base64".$lb.$lb;
   $content.= $data.$lb;
   $content.= "--" . $mime_boundary . $lb;
   if(experimentmail__mail($to, $subject, $content, $header)) {
       return TRUE;
   }
   return FALSE;
}


// lists possible sessions for given experiment
// only sessions, which registration end is in the future and which 
// are not full are listed
function experimentmail__get_session_list($experiment_id,$tlang="") {
	global $settings, $lang;
	$savelang=$lang;

	if (!$tlang) $tlang=$settings['public_standard_language'];

	if ($thislang['lang']!=$tlang) 
		$lang=load_language($tlang);

	$query="SELECT *
      		FROM ".table('sessions')."
        	WHERE experiment_id='".$experiment_id."'
		AND session_finished!='y' 
      		ORDER BY session_start_year, session_start_month, session_start_day,
		 	session_start_hour, session_start_minute";
	$result=mysql_query($query) or die("Database error: " . mysql_error());
	$list="";
	while ($s=mysql_fetch_assoc($result)) {
        	$registration_unixtime=sessions__get_registration_end($s);
        	$session_full=sessions__session_full('',$s);
		$now=time();
		if ($registration_unixtime > $now && !$session_full) {
        		$list.=session__build_name($s,$lang['lang']).' '.
        			laboratories__get_laboratory_name($s['laboratory_id']).', '.
        			$lang['registration_until'].' '.
        			time__format($lang['lang'],'',false,false,true,false,$registration_unixtime);
			$list.="\n";
			}
		}
	$lang=$savelang;
	return $list;
}

function experimentmail__send_invitations_to_queue($experiment_id,$whom="not-invited") {

	switch ($whom) {
		case "not-invited": 	$aquery=" AND invited='n' "; break;
		case "all":		$aquery=""; break;
		default:		$aquery=" AND ".table('participants').".participant_id='0' ";

		}
	mt_srand((double)microtime()*1000000);
        $now=mt_rand();
        $order="ORDER BY rand(".$now.") ";
	$query="INSERT INTO ".table('mail_queue')." (timestamp,mail_type,mail_recipient,experiment_id) 
		SELECT UNIX_TIMESTAMP(),'invitation', ".table('participants').".participant_id, experiment_id 
		FROM ".table('participants').", ".table('participate_at')." 
		WHERE experiment_id='".$experiment_id."' 
		AND ".table('participants').".participant_id=".table('participate_at').".participant_id ".
		$aquery." 
		AND registered = 'n' AND deleted='n'".$order;

	$done=mysql_query($query) or die("Database error: " . mysql_error());
	$count=mysql_affected_rows();
	return $count;
}

function experimentmail__send_bulk_mail_to_queue($bulk_id,$part_array) {

	$return=false;
	if (is_array($part_array)) {
		$now=time();
		foreach ($part_array as $participant_id) {
        		$query="INSERT INTO ".table('mail_queue')." 
				SET timestamp='".$now."',
				mail_type='bulk_mail',
				mail_recipient='".$participant_id."',
				bulk_id='".$bulk_id."'";
        		$done=mysql_query($query) or die("Database error: " . mysql_error());
			}
			$return=true;
		}
	return $return;
}


function experimentmail__send_session_reminders_to_queue($session) {

        $query="INSERT INTO ".table('mail_queue')." (timestamp,mail_type,mail_recipient,experiment_id,session_id)
                SELECT UNIX_TIMESTAMP(),'session_reminder', participant_id, experiment_id, session_id
                FROM ".table('participate_at')."
                WHERE experiment_id='".$session['experiment_id']."'
                AND session_id='".$session['session_id']."'";
        $done=mysql_query($query) or die("Database error: " . mysql_error());
        $count=mysql_affected_rows();

	// update session table : reminder_sent
	$query="UPDATE ".table('sessions')." SET reminder_sent='y' WHERE session_id='".$session['session_id']."'";
	$done=mysql_query($query) or die("Database error: ".mysql_error());
        return $count;
}


function experimentmail__send_noshow_warnings_to_queue($session) {

        $query="INSERT INTO ".table('mail_queue')." (timestamp,mail_type,mail_recipient,experiment_id,session_id)
                SELECT UNIX_TIMESTAMP(),'noshow_warning', participant_id, experiment_id, session_id
                FROM ".table('participate_at')."
                WHERE experiment_id='".$session['experiment_id']."'
                AND session_id='".$session['session_id']."'
		AND shownup='n'";
        $done=mysql_query($query) or die("Database error: " . mysql_error());
        $count=mysql_affected_rows();
        return $count;
}

function experimentmail__set_reminder_checked($session_id) {
	// update session table : reminder_checked
        $query="UPDATE ".table('sessions')." SET reminder_checked='y' WHERE session_id='".$session_id."'";
        $done=mysql_query($query) or die("Database error: ".mysql_error());
	return $done;
}

function experimentmail__set_noshow_warnings_checked($session_id) {
        // update session table : reminder_checked
        $query="UPDATE ".table('sessions')." SET noshow_warning_sent='y' WHERE session_id='".$session_id."'";
        $done=mysql_query($query) or die("Database error: ".mysql_error());
        return $done;
}

function experimentmail__mails_in_queue($type="",$experiment_id="",$session_id="") {

	if ($type) $tquery=" AND mail_type='".$type."' "; else $tquery="";
	if ($experiment_id) $equery=" AND experiment_id='".$experiment_id."' "; else $equery="";
	if ($session_id) $squery=" AND session_id='".$session_id."' "; else $squery="";

	$query="SELECT count(mail_id) as number FROM ".table('mail_queue')."
		WHERE mail_id>0 ".$tquery.$equery.$squery;
	$line=orsee_query($query);
	$number=$line['number'];
	return $number;
}


function experimentmail__send_mails_from_queue($number=0,$type="",$experiment_id="",$session_id="") {

	if ($number>0) $limit=" LIMIT ".$number; else $limit="";
        if ($type) $tquery=" AND mail_type='".$type."' "; else $tquery="";
        if ($experiment_id) $equery=" AND experiment_id='".$experiment_id."' "; else $equery="";
        if ($session_id) $squery=" AND session_id='".$session_id."' "; else $squery="";

	// first get mails to send
	$query="SELECT * FROM ".table('mail_queue')."
                WHERE mail_id>0 ".
		$tquery.
		$equery.
		$squery."
		ORDER BY timestamp, mail_id ".
		$limit;
	$result=mysql_query($query) or die("Database error: " . mysql_error());

	$invitations=array(); $reminders=array(); $bulks=array(); $errors=array(); $reminder_text=array(); $warning_text=array();
	$professions=array(); $fields_of_studies=array(); $genders=array(); $warnings=array();
	$exps=array(); $sesss=array(); $parts=array(); $inv_texts=array(); $slists=array();
	$labs=array();

	while ($line=mysql_fetch_assoc($result)) {
		$texp=$line['experiment_id'];
		$tsess=$line['session_id'];
		$tpart=$line['mail_recipient'];
		$ttype=$line['mail_type'];
		$tbulk=$line['bulk_id'];
		$continue=true;

		// well, if experiment_id, session_id, recipient, footer or inv_text, add to array
		if (!isset($exps[$texp]) && $texp) 
			$exps[$texp]=orsee_db_load_array("experiments",$texp,"experiment_id");
		if (!isset($sesss[$tsess]) && $tsess)
                        $sesss[$tsess]=orsee_db_load_array("sessions",$tsess,"session_id");
		if (!isset($parts[$tpart]) && $tpart)
                        $parts[$tpart]=orsee_db_load_array("participants",$tpart,"participant_id");
		$tlang=$parts[$tpart]['language'];
		if (!isset($footers[$tlang]))
			$footers[$tlang]=load_mail("public_mail_footer",$tlang);
		if ($ttype=="session_reminder" && !isset($reminder_text[$tlang])) {
                        $reminder_text[$tlang]['text']=load_mail("public_session_reminder",$tlang);
			$reminder_text[$tlang]['subject']=load_language_symbol('email_session_reminder_subject',$tlang);
			}
		if ($ttype=="noshow_warning" && !isset($warning_text[$tlang])) {
                        $warning_text[$tlang]['text']=load_mail("public_noshow_warning",$tlang);
                        $warning_text[$tlang]['subject']=load_language_symbol('email_noshow_warning_subject',$tlang);
                        }
		if (($ttype=="session_reminder" || $ttype=="noshow_warning") && !isset($labs[$tsess][$tlang])) {
                        $labs[$tsess][$tlang]=laboratories__get_laboratory_text($sesss[$tsess]['laboratory_id'],$tlang);
                        }
		if (!isset($professions[$tlang]))
                        $professions[$tlang]=lang__load_professions($tlang);
		if (!isset($fields_of_studies[$tlang]))
                        $fields_of_studies[$tlang]=lang__load_studies($tlang);
		if (!isset($genders[$tlang]))
                        $genders[$tlang]=lang__load_genders($tlang);
		if ($ttype=="invitation" && !isset($inv_texts[$texp][$tlang]))
			$inv_texts[$texp][$tlang]=experimentmail__load_invitation_text($texp,$tlang);
		if ($ttype=="invitation" && !isset($slists[$texp][$tlang]))
			$slists[$texp][$tlang]=experimentmail__get_session_list($texp,$tlang);
		if ($ttype=="bulk_mail" && !isset($bulk_mails[$tlang]))
                        $bulk_mails[$tlang]=experimentmail__load_bulk_mail($tbulk,$tlang);

		// check for missing values ...
		if (!isset($parts[$tpart]['participant_id'])) {
			$continue=false;
			// email error: no recipient
			$line['error'].="recipient:";
			}
		   else {
			$parts[$tpart]['field_of_studies']=$fields_of_studies[$tlang][$parts[$tpart]['field_of_studies']];
			$parts[$tpart]['profession']=$professions[$tlang][$parts[$tpart]['profession']];
			$parts[$tpart]['gender']=$genders[$tlang][$parts[$tpart]['gender']];
			}

                if (!isset($exps[$texp]['experiment_id']) && ($ttype=="invitation" || $ttype=="session_reminder" 
				|| $ttype=="noshow_warning")) {
                        $continue=false;
                        // email error: no experiment id given
			$line['error'].="experiment:";
                        }

                if (!isset($sesss[$tsess]['session_id']) && ($ttype=="session_reminder" || $ttype=="noshow_warning")) {
                        $continue=false;
                        // email error: no session id given
			$line['error'].="session:";
                        }

		if (!isset($inv_texts[$texp][$tlang]) && $ttype=="invitation") {
                        $continue=false;
                        // email error: no inv_text given
			$line['error'].="inv_text:";
                        }

		if (!isset($bulk_mails[$tlang]) && $ttype=="bulk_mail") {
                        $continue=false;
                        // email error: no bulk_mail given
                        $line['error'].="bulk_mail:";
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
			}
		   else {
			$errors[]=$line;
			}
		}

	// fine now we have everything we want, and we can proceed with sending the mails

	$mails_sent=0; $mails_errors=0;

	// reminders
	foreach ($reminders as $mail) {
                $tlang=$parts[$mail['mail_recipient']]['language'];
                $done=experimentmail__send_session_reminder_mail($mail,$parts[$mail['mail_recipient']],
                                                $exps[$mail['experiment_id']],$sesss[$mail['session_id']],
                                                $reminder_text[$tlang],$labs[$mail['session_id']][$tlang],
						$footers[$tlang]);
                if ($done) {
                        $mails_sent++;
                        $deleted=experimentmail__delete_from_queue($mail['mail_id']);
                        }
                   else {
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
                        }
                   else {
                        $mail['error']="sending";
                        $errors[]=$mail;
                        }
                }



	// invitations
	foreach ($invitations as $mail) {
		$tlang=$parts[$mail['mail_recipient']]['language'];
		$done=experimentmail__send_invitation_mail($mail,$parts[$mail['mail_recipient']],
						$exps[$mail['experiment_id']],$inv_texts[$mail['experiment_id']][$tlang],
						$slists[$mail['experiment_id']][$tlang],$footers[$tlang]);
		if ($done) {
			$mails_sent++;
			$deleted=experimentmail__delete_from_queue($mail['mail_id']);
			}
		   else {
			$mail['error']="sending";
			$errors[]=$mail;
			}
		}
				
	// bulks
	foreach ($bulks as $mail) {
                $tlang=$parts[$mail['mail_recipient']]['language'];
                $done=experimentmail__send_bulk_mail($mail,$parts[$mail['mail_recipient']],$bulk_mails[$tlang],$footers[$tlang]);
                if ($done) {
                        $mails_sent++;
                        $deleted=experimentmail__delete_from_queue($mail['mail_id']);
                        }
                   else {
                        $mail['error']="sending";
                        $errors[]=$mail;
                        }
                }
	$done=experimentmail__gc_bulk_mail_texts();


	// handle errors
	foreach ($errors as $mail) {
		$query="UPDATE ".table('mail_queue')." 
			SET error='".$mail['error']."' 
			WHERE mail_id='".$mail['mail_id']."'";
		$done=mysql_query($query) or die("Database error: " . mysql_error());

		}
	$mess['mails_sent']=$mails_sent;
	$mess['mails_errors']=$mails_errors;
	return $mess;

}

function experimentmail__delete_from_queue($mail_id) {

	$query="DELETE FROM ".table('mail_queue')."
		WHERE mail_id='".$mail_id."'";
	$result=mysql_query($query) or die("Database error: " . mysql_error());
	return $result;
}

function experimentmail__send_session_reminder_mail($mail,$part,$exp,$session,$reminder_text,$lab,$footer) {
        global $settings;


        $part['edit_link']=experimentmail__build_edit_link($part['participant_id']);
        $part['experiment_name']=$exp['experiment_public_name'];
        $part['session_date']=session__build_name($session,$part['language']);
	$part['lab_name']=laboratories__strip_lab_name($lab);
	$part['lab_address']=laboratories__strip_lab_address($lab);


        $mailtext=stripslashes($reminder_text['text']);
        $subject=$reminder_text['subject'];
        $recipient=$part['email'];

        $message=process_mail_template($mailtext,$part)."\n".process_mail_template($footer,$part);

        if ($exp['sender_mail']) $sender=$exp['sender_mail']; else $sender=$settings['support_mail'];

        $headers="From: ".$sender."\r\n";

        //echo $headers.$recipient."\n".$subject."\n\n".$message;

        $done=experimentmail__mail($recipient,$subject,$message,$headers);
        return $done;

}

function experimentmail__send_noshow_warning_mail($mail,$part,$exp,$session,$warning_text,$lab,$footer) {
        global $settings;


        $part['edit_link']=experimentmail__build_edit_link($part['participant_id']);
        $part['experiment_name']=$exp['experiment_public_name'];
        $part['session_date']=session__build_name($session,$part['language']);
        $part['lab_name']=laboratories__strip_lab_name($lab);
        $part['lab_address']=laboratories__strip_lab_address($lab);
	$part['max_noshows']=$settings['automatic_exclusion_noshows'];


        $mailtext=stripslashes($warning_text['text']);
        $subject=$warning_text['subject'];
        $recipient=$part['email'];

        $message=process_mail_template($mailtext,$part)."\n".process_mail_template($footer,$part);

        $sender=$settings['support_mail'];

        $headers="From: ".$sender."\r\n";

        //echo $headers.$recipient."\n".$subject."\n\n".$message;

        $done=experimentmail__mail($recipient,$subject,$message,$headers);
        return $done;

}

function experimentmail__send_participant_exclusion_mail($part) {
        global $settings;

	$mailtext=stripslashes(load_mail("public_participant_exclusion",$part['language']));
        $subject=load_language_symbol('participant_exclusion_mail_subject',$part['language']);
        $recipient=$part['email'];
        $message=process_mail_template($mailtext,$part)."\n".experimentmail__get_mail_footer($part['participant_id']);
        $sender=$settings['support_mail'];
        $headers="From: ".$sender."\r\n";

        $done=experimentmail__mail($recipient,$subject,$message,$headers);
        return $done;
}

function experimentmail__send_reminder_notice($line,$number,$sent,$disclaimer="") {
	global $settings;

	$experimenters=explode(",",$line['experimenter_mail']);

	foreach ($experimenters as $experimenter) {

		$mail=orsee_db_load_array("admin",$experimenter,"adminname");

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

function experimentmail__send_invitation_mail($mail,$part,$exp,$inv_text,$slist,$footer) {
	global $settings;


	$part['edit_link']=experimentmail__build_edit_link($part['participant_id']);
	$part['experiment_name']=$exp['experiment_public_name'];
	$part['sessionlist']=$slist;
        $part['link']=experimentmail__build_lab_registration_link($part['participant_id']);

	// split in subject and text
        $subject=stripslashes(str_replace(strstr($inv_text,"\n"),"",$inv_text));
        $mailtext=stripslashes(substr($inv_text,strpos($inv_text,"\n")+1,strlen($inv_text)));
	$recipient=$part['email'];

        $message=process_mail_template($mailtext,$part)."\n".process_mail_template($footer,$part);

	if ($exp['sender_mail']) $sender=$exp['sender_mail']; else $sender=$settings['support_mail'];

        $headers="From: ".$sender."\r\n";

	//echo $headers.$recipient."\n".$subject."\n\n".$message;

        $done=experimentmail__mail($recipient,$subject,$message,$headers);
	$done2=experimentmail__update_invited_flag($mail);
	return $done;

}


function experimentmail__send_bulk_mail($mail,$part,$bulk_mail,$footer) {
        global $settings;

        $part['edit_link']=experimentmail__build_edit_link($part['participant_id']);

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

        $query="UPDATE ".table('participate_at')."
		SET invited='y' 
                WHERE participant_id='".$mail['mail_recipient']."'
		AND experiment_id='".$mail['experiment_id']."'";
        $result=mysql_query($query) or die("Database error: " . mysql_error());
        return $result;
}

function experimentmail__build_edit_link($participant_id) {
	global $settings__root_url;
	$edit_link=$settings__root_url."/public/participant_edit.php?p=".url_cr_encode($participant_id);
	return $edit_link;
}

function experimentmail__build_lab_registration_link($participant_id) {
        global $settings__root_url;
        $reg_link=$settings__root_url."/public/participant_show.php?p=".url_cr_encode($participant_id);
        return $reg_link;
}

function experimentmail__build_os_link($participant_id) {
        global $settings__root_url;
        $os_link=$settings__root_url."/public/os_show.php?p=".url_cr_encode($participant_id);
        return $os_link;
}

function experimentmail__mail_edit_link($participant_id) {
	global $lang, $authdata, $settings;

	$participant=orsee_db_load_array("participants",$participant_id,"participant_id");
	$participant['edit_link']=experimentmail__build_edit_link($participant_id);

	if (isset($authdata['language']) && $authdata['language']) $maillang=$authdata['language'];
                        else $maillang=$participant['language'];
        $mailtext=load_mail("public_mail_footer",$maillang);
        $message=process_mail_template($mailtext,$participant);

        $headers="From: ".$settings['support_mail']."\r\n";

        experimentmail__mail($participant['email'],$lang['subject_for_edit_link_mail'],$message,$headers);

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

function experimentmail__get_mail_footer($participant_id) {
        global $lang, $settings;

        $participant=orsee_db_load_array("participants",$participant_id,"participant_id");
        $participant['edit_link']=experimentmail__build_edit_link($participant_id);

        $maillang=experimentmail__get_language($participant['language']);

        $mailtext=load_mail("public_mail_footer",$maillang);
        $footer=process_mail_template($mailtext,$participant);
	return $footer;
}


function experimentmail__get_admin_footer($maillang='',$admin_id=0) {

	if ($admin_id>0) 
		$admin=orsee_db_load_array("admin",$admin_id,"admin_id");
		else $admin=array();

        if (!$maillang) $maillang=experimentmail__get_admin_language($admin['language']);

        $mailtext=load_mail("admin_mail_footer",$maillang);
        $footer=process_mail_template($mailtext,$admin);
        return $footer;
}

function experimentmail__confirmation_mail($participant_id) {
	global $authdata, $lang, $settings__root_url, $settings;

	$participant=orsee_db_load_array("participants_temp",$participant_id,"participant_id");

	$participant['field_of_studies']=participant__get_field_of_studies($participant['field_of_studies']);

        $participant['profession']=participant__get_profession($participant['profession']);
	$participant['gender']=$lang['gender_'.$participant['gender']];

	$exptypes=explode(",",$participant['subscriptions']);
        $typenames=load_external_experiment_type_names();
	$invnames=array();
        foreach ($exptypes as $type) $invnames[]=$typenames[$type];
	$participant['invitations']=implode(", ",$invnames);

        $participant['registration_link']=$settings__root_url."/public/participant_confirm.php?p=".url_cr_encode($participant_id);


	$maillang=experimentmail__get_language($participant['language']);

	$mailtext=load_mail("public_system_registration",$maillang);
	$message=process_mail_template($mailtext,$participant);

	$headers="From: ".$settings['support_mail']."\r\n";

        experimentmail__mail($participant['email'],$lang['registration_email_subject'],$message,$headers);
}


function experimentmail__experiment_registration_mail($participant_id,$session_id,$experiment_id) {
	global $lang, $settings;

        // load experiment
        $experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");

        // load participant
        $participant=orsee_db_load_array("participants",$participant_id,"participant_id");
        // load session
        $session=orsee_db_load_array("sessions",$session_id,"session_id");

	$maillang=experimentmail__get_language($participant['language']);

	$experimentmail=$participant;

        // define some more shortcuts
        $experimentmail['laboratory']=laboratories__get_laboratory_name($session['laboratory_id']);
        $experimentmail['location']=laboratories__get_laboratory_address($session['laboratory_id']);
        $experimentmail['field_of_studies']=participant__get_field_of_studies($participant['field_of_studies'],$maillang);
	$experimentmail['profession']=participant__get_profession($participant['profession'],$maillang);
        $experimentmail['session']=session__build_name($session,$maillang);
        $experimentmail['experiment']=$experiment['experiment_public_name'];
	$experimentmail['duration']=$session['duration_hour'].":".$session['duration_minute'];

        $mailtext=load_mail("public_experiment_registration",$maillang);
        $message=process_mail_template($mailtext,$experimentmail);

	$message=$message."\n".experimentmail__get_mail_footer($participant_id);

	if ($experiment['sender_mail']) $sendermail=$experiment['sender_mail']; else $sendermail=$settings['support_mail'];

        $headers="From: ".$sendermail."\r\n";

        experimentmail__mail($participant['email'],$lang['registration_email_subject'],$message,$headers);
}


function experimentmail__send_registration_notice($line) {
        global $settings;

	$reg=experiment__count_participate_at($line['experiment_id'],$line['session_id']);

	$experimenters=explode(",",$line['experimenter_mail']);

        foreach ($experimenters as $experimenter) {

                $mail=orsee_db_load_array("admin",$experimenter,"adminname");

                $tlang= ($mail['language']) ? $mail['language'] : $settings['admin_standard_language'];
                $lang=load_language($tlang);

                $mail['session_name']=session__build_name($line,$tlang);
                $mail['experiment_name']=$line['experiment_name'];
                $mail['registered'] = $reg;
		$mail['status']=session__get_status($line,$tlang,$reg);
		$mail['needed']=$line['part_needed'];
		$mail['reserve']=$line['part_reserve'];

                $subject=load_language_symbol('subject_for_registration_notice',$tlang);
		$subject.=' '.$mail['experiment_name'].', '.$mail['session_name'];

                $recipient=$mail['email'];

                $mailtext=load_mail("admin_registration_notice",$tlang)."\n".
                                        experimentmail__get_admin_footer($tlang)."\n";
                $message=process_mail_template($mailtext,$mail);

		$now=time();
		$list_name=$lang['participant_list_filename'].' '.date("Y-m-d",$now);
        	$list_filename=str_replace(" ","_",$list_name).".pdf";
        	$list_file=pdfoutput__make_part_list($line['experiment_id'],$line['session_id'],'registered','lname,fname',true,$tlang);

		$done=experimentmail__mail_attach($recipient,$settings['support_mail'],$subject,$message,$list_filename,$list_file);
                }

	// update session table : reg_notice_sent
        $query="UPDATE ".table('sessions')." SET reg_notice_sent='y' WHERE session_id='".$line['session_id']."'";
        $done2=mysql_query($query) or die("Database error: ".mysql_error());

        return $done;

}


function experimentmail__send_calendar() {
	global $lang, $settings;

        $now=time();

	if (isset($settings['emailed_calendar_included_months']) && $settings['emailed_calendar_included_months']>0) 
			$number_of_months=$settings['emailed_calendar_included_months']-1;
	else $number_of_months=1;

        $cal_name=$lang['experiment_calendar'].' '.date("Y-m-d",$now);
        $cal_filename=str_replace(" ","_",$cal_name).".pdf";

	// save old lang
	$old_lang=$lang['lang'];
	$maillang=$old_lang;
	$cal_name=$lang['experiment_calendar'].' '.date("Y-m-d",$now);
        $cal_filename=str_replace(" ","_",$cal_name).".pdf";
	$cal_file=pdfoutput__make_calendar($now,false,true,$number_of_months,true);

        $from=$settings['support_mail'];


	// get experimenters who want to receive the calendar
     	$query="SELECT *
      		FROM ".table('admin')."
      		WHERE get_calendar_mail='y'
		ORDER BY language";

	$result=mysql_query($query) or die("Database error: " . mysql_error());

	$i=0; $rec_count=mysql_num_rows($result);
	while ($admin = mysql_fetch_assoc($result)) {
			if ($admin['language'] != $maillang) {
				$maillang=$admin['language'];
				$lang=load_language($maillang);
				$cal_name=$lang['experiment_calendar'].' '.date("Y-m-d",$now);
        			$cal_filename=str_replace(" ","_",$cal_name).".pdf";
				$cal_file=pdfoutput__make_calendar($now,false,true,$number_of_months,true);
				}

			$mailtext=load_mail("admin_calendar_mailtext",$maillang).
					"\n".
					experimentmail__get_admin_footer($maillang)."\n";
			$message=process_mail_template($mailtext,$admin);
			$done=experimentmail__mail_attach($admin['email'],$from,$cal_name,$message,$cal_filename,$cal_file);
			if ($done) $i++;
                        }

	mysql_free_result($result);

	if ($maillang!=$old_lang) $lang=load_language($old_lang);

	return $cal_name." sent to ".$i." out of ".$rec_count." administrators\n";
}



function experimentmail__send_participant_statistics() {
	global $lang, $settings;
	$now=time();

        // save old lang
        $old_lang=$lang['lang'];
        $maillang=$old_lang;
	$statistics=stats__textstats_all();
	$subject=load_language_symbol('participant_statistics',$maillang).' '.
                                        time__format($maillang,"",false,true,true,false,$now);

        $from=$settings['support_mail'];
	$headers="From: ".$from."\r\n";

        // get experimenters who want to receive the statistics
        $query="SELECT *
                FROM ".table('admin')."
                WHERE get_statistics_mail='y'
                ORDER BY language";

        $result=mysql_query($query) or die("Database error: " . mysql_error());

        $i=0; $rec_count=mysql_num_rows($result);
        while ($admin = mysql_fetch_assoc($result)) {
                        if ($admin['language'] != $maillang) {
                                $maillang=$admin['language'];
                                $lang=load_language($maillang);
				$statistics=stats__textstats_all();
				$subject=load_language_symbol('participant_statistics',$maillang).' '.
					time__format($maillang,"",false,true,true,false,$now);
                                }

                        $mailtext=load_mail("admin_participant_statistics_mailtext",$maillang).
                                        "\n\n".
					$statistics."\n".
                                        experimentmail__get_admin_footer($maillang)."\n";
                        $message=process_mail_template($mailtext,$admin);
                        $done=experimentmail__mail($admin['email'],$subject,$message,$headers);
                        if ($done) $i++;
                        }

        mysql_free_result($result);

        if ($maillang!=$old_lang) $lang=load_language($old_lang);

        return "statistics sent to ".$i." out of ".$rec_count." administrators\n";
}

function experimentmail__bulk_mail_form() {
	global $lang;

	echo '<A HREF="participants_bulk_mail.php">'.$lang['send_mail_to_listed_participants'].'</A>';
}


?>
