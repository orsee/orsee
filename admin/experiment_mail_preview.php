<?php
ob_start();
$title="mail preview";

include("header.php");

	if ($_REQUEST['experiment_id']) $experiment_id=$_REQUEST['experiment_id'];
			else redirect ("admin/");

	$allow=check_allow('experiment_invitation_edit','experiment_show.php?experiment_id='.$experiment_id);
	$experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
	if (!check_allow('experiment_restriction_override'))
		check_experiment_allowed($experiment,"admin/experiment_show.php?experiment_id=".$experiment_id);

	$query="SELECT * from ".table('lang')."
                WHERE content_type='experiment_invitation_mail'
                AND content_name='".$experiment_id."'";
        $experiment_mail=orsee_query($query);

	$inv_langs=lang__get_part_langs();


	if ($experiment['experiment_type']=="online-survey")
		$os=orsee_db_load_array("os_properties",$experiment_id,"experiment_id");

	echo '	<BR><BR>
		<center>
		<h4>'.$lang['mail_preview'].'</h4>';

	echo '<TABLE border=0 width=90%>';

	foreach ($inv_langs as $inv_lang) {
        	// split in subject and text
        	$subject=str_replace(strstr($experiment_mail[$inv_lang],"\n"),"",$experiment_mail[$inv_lang]);
        	$body=substr($experiment_mail[$inv_lang],strpos($experiment_mail[$inv_lang],"\n")+1,strlen($experiment_mail[$inv_lang]));

        	$experimentmail['email']=load_language_symbol('recipients_email_address',$inv_lang);
        	$experimentmail['lname']=load_language_symbol('lastname',$inv_lang);
        	$experimentmail['fname']=load_language_symbol('firstname',$inv_lang);
        	$experimentmail['begin_of_studies']=load_language_symbol('begin_of_studies',$inv_lang);
        	$experimentmail['participant_id']=load_language_symbol('id',$inv_lang);
		$experimentmail['field_of_studies']=load_language_symbol('studies',$inv_lang);
		$experimentmail['profession']=load_language_symbol('profession',$inv_lang);
		$experimentmail['phone_number']=load_language_symbol('phone_number',$inv_lang);
		$experimentmail['sender']=$experiment['sender_mail'];

        	if ($experiment['experiment_type']=="laboratory") {
                	$experimentmail['sessionlist']=experimentmail__get_session_list($experiment_id,$inv_lang);
                	$experimentmail['link']=experimentmail__build_lab_registration_link(0);
                	}

        	elseif ($experiment['experiment_type']=="online-survey") {

                	$experimentmail['link']=experimentmail__build_os_link($participant_id);

                	/*
                	$experimentmail['start_time']=time__format lang=<get-var settings::public-standard-language>
                                year=<get-var os::start_year> month=<get-var os::start_month> day=<get-var os::start_day>
                                hour=<get-var os::start_hour> minute=<get-var os::start_minute> hide_second=true></defun>

                	<defun experimentmail::stop_time><time::format lang=<get-var settings::public-standard-language>
                                year=<get-var os::stop_year> month=<get-var os::stop_month> day=<get-var os::stop_day>
                                hour=<get-var os::stop_hour> minute=<get-var os::stop_minute> hide_second=true></defun>
                	*/

                	}

		if (count($inv_langs) > 1) {
                        echo '<TR><TD colspan=2 bgcolor="'.$color['list_shade1'].'">'.$inv_lang.':</TD></TR>';
                        }

		echo '
              		<TR>
				<TD>'.$lang['from'].':</TD>
				<TD>'.$experimentmail['sender'].'</TD>
			</TR>
                	<TR>
				<TD>'.$lang['to'].':</TD>
				<TD>'.$experimentmail['email'].'</TD>
			</TR>
                	<TR bgcolor="'.$color['list_shade2'].'">
				<TD>'.$lang['subject'].':</TD>
				<TD>'.stripslashes($subject).'</TD>
			</TR>
                	<TR>
				<TD valign=top bgcolor="'.$color['list_list_background'].'" colspan=2>
					'.nl2br(process_mail_template(stripslashes($body),$experimentmail));
				if ($experimentmail['include_footer']=="y") 
					echo nl2br(stripslashes(experimentmail__get_mail_footer(0)));
		echo '		</TD>
			</TR>';
		echo '<TR><TD colspan=2>&nbsp;</TD></TR>';

		}

	echo '
              </TABLE>';

	echo '<BR><BR>
		<A HREF="experiment_mail_participants.php?experiment_id='.$experiment_id.'">'.
				$lang['back_to_mail_page'].'</A><BR><bR>
		</CENTER>';

include("footer.php");
?>
