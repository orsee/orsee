<?php
ob_start();
$title="mail participants";

include ("header.php");

	if ($_REQUEST['experiment_id']) $experiment_id=$_REQUEST['experiment_id'];
			else redirect ("admin/");

	$allow=check_allow('experiment_invitation_edit','experiment_show.php?experiment_id='.$experiment_id);

	if (isset($_REQUEST['id'])) $id=$_REQUEST['id']; else $id="";

	if ($_REQUEST['preview']) $preview=true;
	if ($_REQUEST['save']) $save=true;
	if ($_REQUEST['send']) $send=true;
	if ($_REQUEST['sendall']) $sendall=true; 

	if ($preview || $save || $send || $sendall) $action=true;


	$experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
	if (!check_allow('experiment_restriction_override'))
		check_experiment_allowed($experiment,"admin/experiment_show.php?experiment_id=".$experiment_id);

	// load invitation languages
	$inv_langs=lang__get_part_langs();
	$installed_langs=get_languages();


	echo '<BR><BR>
		<center>
			<h4>'.$experiment['experiment_name'].'</h4>
			<h4>'.$lang['send_invitations'].'</h4>
		';


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

                if ($done) message ($lang['changes_saved']);
                        else message ($lang['database_error']);

		if ($preview) {
			redirect ('admin/experiment_mail_preview.php?experiment_id='.$experiment_id);
			}
		   elseif ($send || $sendall) {
			// send mails!

		$allow=check_allow('experiment_invite_participants','experiment_mail_participants.php?experiment_id='.$experiment_id);

		if ($allow) {

                	$whom= ($sendall) ? "all" : "not-invited";
                	$measure_start=getmicrotime();
                	$sended=experimentmail__send_invitations_to_queue($experiment_id,$whom);

                	message ($sended.' '.$lang['xxx_inv_mails_added_to_mail_queue']);

			//$sended.' '.$lang['xxx_e-mail_messages_sent']

			$measure_end=getmicrotime();	
                	message($lang['time_needed_in_seconds'].': '.round(($measure_end-$measure_start),5));
			log__admin("experiment_send_invitations","experiment:".$experiment['experiment_name']);
                	redirect ("admin/experiment_mail_participants.php?experiment_id=".$experiment_id);
			}

			}
		   else {
			message($lang['mail_text_saved']);
			log__admin("experiment_edit_invitation_mail","experiment:".$experiment['experiment_name']);
			redirect ('admin/'.thisdoc().'?experiment_id='.$experiment_id);
			}
		}

	$query="SELECT * from ".table('lang')." 
		WHERE content_type='experiment_invitation_mail' 
		AND content_name='".$experiment_id."'";
	$experiment_mail=orsee_query($query);

	// form

        echo '<FORM action="'.thisdoc().'" method="post">
        	<INPUT type=hidden name="experiment_id" value="'.$experiment_id.'">
		<INPUT type=hidden name="id" value="'.$experiment_mail['lang_id'].'">

        	<TABLE border=0 width=90%>';

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
			echo '<TR><TD colspan=2 bgcolor="'.$color['list_shade1'].'">'.$inv_lang.':</TD></TR>';
			}

		echo '
			<TR>
				<TD>
					'.$lang['subject'].':
				</TD>
				<TD>
					<INPUT type=text name="'.$inv_lang.'_subject" size=30 maxlength=80 value="'.
						stripslashes($subject).'">
				</TD>
			</TR>
                	<TR>
				<TD valign=top colspan=2>
					'.$lang['body_of_message'].':<BR>
					<FONT class="small">'.$lang['experimentmail_how_to_rebuild_default'].'</FONT>
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
                	<TR bgcolor="'.$color['list_options_background'].'">
				<TD colspan=2>
					1. '.$lang['save_mail_text_only'].'
				</TD>
			</TR>
                	<TR bgcolor="'.$color['list_options_background'].'">
                		<TD align=left>
                			<INPUT type=submit name="preview" class="small" value="'.$lang['mail_preview'].'">
                		</TD>
                		<TD align=right>
					<INPUT type=submit name="save" value="'.$lang['save'].'">
                		</TD>
			</TR>
			<TR>
				<TD>
					'.$lang['assigned_subjects'].':
				</TD>
				<TD>
					'.experiment__count_participate_at($experiment_id).'
				</TD>
			</TR>
        		<TR>
				<TD>
					'.$lang['invited_subjects'].':
				</TD>
				<TD>
					'.experiment__count_invited($experiment_id).'
				</TD>
			</TR>
			<TR>
            			<TD>
					'.$lang['registered_subjects'].':
				</TD>
				<TD>
					'.experiment__count_registered($experiment_id).'
				</TD>
        		</TR>
			<TR>
				<TD>
					'.$lang['inv_mails_in_mail_queue'].':
				</TD>
				<TD>';
					$qmails=experimentmail__mails_in_queue("invitation",$experiment_id);
					echo $qmails.'
				</TD>
			</TR>';


		if ($qmails>0) {
			echo '	<TR>
					<TD colspan=2>
						<BR><FONT color="red">
						'.$qmails.' '.$lang['xxx_inv_mails_for_this_exp_still_in_queue'].'
						</FONT><BR>
					</TD>
				</TR>';
			}
		   elseif (check_allow('experiment_invite_participants')) {
                	echo '	<TR bgcolor="'.$color['list_options_background'].'">
					<TD colspan=2>
                				2. '.$lang['mail_to_not_got_one'].'
					</TD>
				</TR>
	        		<TR bgcolor="'.$color['list_options_background'].'">
					<TD colspan=2 align=right>
						<INPUT type=submit name="send" value="'.$lang['send'].'">
                			</TD>
				</TR>
                		<TR bgcolor="'.$color['list_options_background'].'">
					<TD colspan=2>
                				3. '.$lang['mail_have_got_it_already'].'
					</TD>
				</TR>
                		<TR bgcolor="'.$color['list_options_background'].'">
					<TD colspan=2 align=right>
						<INPUT type=submit name="sendall" value="'.$lang['send_to_all'].'">
                			</TD>
				</TR>';
			}
	echo '
        	</TABLE>
        	</FORM>';

	echo '<BR><A HREF="experiment_show.php?experiment_id='.$experiment_id.'">'.
			$lang['mainpage_of_this_experiment'].'</A><BR><BR>

		</CENTER>';

include ("footer.php");
?>
