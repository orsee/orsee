<?php
ob_start();

$title="delete participant";
include ("header.php");

	if (isset($_REQUEST['participant_id']) && $_REQUEST['participant_id']) 
			$participant_id=$_REQUEST['participant_id'];
                else redirect ("admin/");

        if (isset($_REQUEST['betternot']) && $_REQUEST['betternot'])
                redirect ('admin/participants_edit.php?participant_id='.$participant_id);

        if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
                        else $reallydelete=false;

        if (isset($_REQUEST['reallyexclude']) && $_REQUEST['reallyexclude']) $reallyexclude=true;
                        else $reallyexclude=false;

	$allow=check_allow('participants_unsubscribe','participants_edit.php?participant_id='.$participant_id);

        $participant=orsee_db_load_array("participants",$participant_id,"participant_id");


	echo '<BR><BR>
		<center>
			<h4>'.$lang['delete_participant_data'].'</h4>
		</center>';



        if ($reallydelete) {

                $query="UPDATE ".table('participants')."
                        SET deleted='y'
			WHERE participant_id='".$participant_id."'";
                $result=mysql_query($query) or die("Database error: " . mysql_error());

		if ($result) { 
      			message ($lang['participant_unsubscribed']);
			log__admin("participant_unsubscribe","participant_id:".$participant_id);
			redirect ("admin/participants_edit.php?participant_id=".$participant_id);
			}
		   else message ($lang['database_error']);
		}

	if ($reallyexclude) {
                $query="UPDATE ".table('participants')."
                        SET deleted='y', excluded='y' 
                        WHERE participant_id='".$participant_id."'";
                $result=mysql_query($query) or die("Database error: " . mysql_error());

                if ($result) {
                        message ($lang['participant_unsubscribed_and_excluded']);
			log__admin("participant_exclude","participant_id:".$participant_id);
                        redirect ("admin/participants_edit.php?participant_id=".$participant_id);
			}
                   else message ($lang['database_error']);
                }

	echo '<CENTER>
		<FORM action="'.thisdoc().'">
		<INPUT type=hidden name="participant_id" value="'.$participant_id.'">
		<TABLE width=90%>
			<TR>
				<TD colspan=2 align=center>
					'.$lang['exclude_or_unsubscribe_participant'].'<BR><BR>';
					dump_array($participant);
			echo '	</TD>
			</TR>
			<TR>
				<TD align=center>
					<INPUT type=submit name="reallydelete" 
						value="'.$lang['yes_unsubscribe'].'">
				</TD>
				<TD align=center>
					<INPUT type=submit name="reallyexclude" 
						value="'.$lang['yes_unsubscribe_and_exclude'].'">
				</TD>
			</TR>
			<TR>
				<TD colspan=2 align=center>
					<INPUT type=submit name=betternot 
						value="'.$lang['no_sorry'].'">
				</TD>
			</TR>
		</TABLE>
		</FORM>
	      </center>';

include ("footer.php");

?>



