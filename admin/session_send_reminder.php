<?php
ob_start();

$title="send session reminder";
include("header.php");

        if (isset($_REQUEST['session_id']) && $_REQUEST['session_id']) $session_id=$_REQUEST['session_id'];
                else redirect ("admin/");

	$session=orsee_db_load_array("sessions",$session_id,"session_id");

	if (isset($_REQUEST['betternot']) && $_REQUEST['betternot'])
		redirect ('admin/experiment_participants_show.php?experiment_id='.$session['experiment_id'].'&focus=registered'.
				'&session_id='.$session_id);

        if (isset($_REQUEST['reallysend']) && $_REQUEST['reallysend']) $reallysend=true;
                        else $reallysend=false;

	$allow=check_allow('session_send_reminder','experiment_participants_show.php?experiment_id='.
			$session['experiment_id'].'&focus=registered'.'&session_id='.$session_id);

	echo '	<BR><BR>
		<center>
			<h4>'.$lang['session_reminder_send'].' '.session__build_name($session).'</h4>
		</center>';


	if ($reallysend) { 
		// send it out to mail queue

		$number=experimentmail__send_session_reminders_to_queue($session);

		message ($number.' '.$lang['xxx_session_reminder_emails_sent_out']);
		log__admin("session_send_reminder","session:".session__build_name($session,$settings['admin_standard_language']).
                                "\nsession_id:".$session_id);
		redirect ('admin/experiment_participants_show.php?experiment_id='.$session['experiment_id'].'&focus=registered'.                                '&session_id='.$session_id);
		}

	// form

	echo '	<CENTER>
		<FORM action="session_send_reminder.php">
		<INPUT type=hidden name="session_id" value="'.$session_id.'">

		<TABLE>
			<TR>
				<TD colspan=2>
					'.$lang['really_send_session_reminder_now'].'
				</TD>
			</TR>
			<TR>
				<TD align=left>
					<INPUT type=submit name=reallysend value="'.$lang['yes'].'">
				</TD>
				<TD align=right>
					<INPUT type=submit name=betternot value="'.$lang['no_sorry'].'">
				</TD>
			</TR>
		</TABLE>

		</FORM>
		</center>';

include ("footer.php");

?>
