<?php
ob_start();

$title="delete session";
include("header.php");

         if (isset($_REQUEST['session_id']) && $_REQUEST['session_id']) $session_id=$_REQUEST['session_id'];
                else redirect ("admin/");

	if (isset($_REQUEST['betternot']) && $_REQUEST['betternot'])
		redirect ('admin/session_edit.php?session_id='.$session_id);

        if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
                        else $reallydelete=false;

	$session=orsee_db_load_array("sessions",$session_id,"session_id");

	$reg=experiment__count_participate_at($session['experiment_id'],$session_id);

	if ($reg>0) $allow=check_allow('session_nonempty_delete','session_edit.php?session_id='.$session_id);
		else if (!check_allow('session_nonempty_delete')) 
			check_allow('session_empty_delete','session_edit?session_id='.$session_id);

	if (!check_allow('experiment_restriction_override'))
		check_experiment_allowed($session['experiment_id'],"admin/experiment_show.php?experiment_id=".$session['experiment_id']);

	echo '	<BR><BR>
		<center>
			<h4>'.$lang['delete_session'].' '.session__build_name($session).'</h4>
		</center>';


	if ($reallydelete) { 

        	$query="DELETE FROM ".table('sessions')." 
         		WHERE session_id='".$session_id."'";
		$result=mysql_query($query) or die("Database error: " . mysql_error());

        	$query="UPDATE ".table('participate_at')."
			SET session_id='0', registered='n'
         		WHERE session_id='".$session_id."'";
		$result=mysql_query($query) or die("Database error: " . mysql_error());

        	message ($lang['session_deleted']);
		log__admin("session_delete","session:".session__build_name($session,$settings['admin_standard_language']).
				"\n,session_id:".$session_id);
		redirect ('admin/experiment_show.php?experiment_id='.$session['experiment_id']);
		}

	// form

	echo '	<CENTER>
		<FORM action="session_delete.php">
		<INPUT type=hidden name="session_id" value="'.$session_id.'">

		<TABLE>
			<TR>
				<TD colspan=2>
					'.$lang['really_delete_session'].'
					<BR><BR>';
					dump_array($session); echo '
				</TD>
			</TR>
			<TR>
				<TD align=left>
					<INPUT type=submit name=reallydelete value="'.$lang['yes_delete'].'">
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
