<?php
ob_start();

$title="resubscribe participant";
include ("header.php");

	if (isset($_REQUEST['participant_id']) && $_REQUEST['participant_id']) 
			$participant_id=$_REQUEST['participant_id'];
                else redirect ("admin/");

        if (isset($_REQUEST['betternot']) && $_REQUEST['betternot'])
                redirect ('admin/participants_edit.php?participant_id='.$participant_id);

        if (isset($_REQUEST['reallyresub']) && $_REQUEST['reallyresub']) $reallyresub=true;
                        else $reallyresub=false;

	$allow=check_allow('participants_resubscribe','participants_edit.php?participant_id='.$participant_id);

        $participant=orsee_db_load_array("participants",$participant_id,"participant_id");


	echo '<BR><BR>
		<center>
			<h4>'.$lang['resubscribe_participant'].'</h4>
		</center>';



	if ($reallyresub) {
                $query="UPDATE ".table('participants')."
                        SET deleted='n', excluded='n' 
                        WHERE participant_id='".$participant_id."'";
                $result=mysql_query($query) or die("Database error: " . mysql_error());

                if ($result) {
			log__admin("participant_resubscribe","participant_id:".$participant_id);
                        message ($lang['participant_resubscribed']);
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
					'.$lang['really_resubscribe_participant'].'<BR><BR>';
					dump_array($participant);
			echo '	</TD>
			</TR>
			<TR>
				<TD align=center>
					<INPUT type=submit name="reallyresub" 
						value="'.$lang['yes_resubscribe'].'">
				</TD>
				<TD align=center>
					<INPUT type=submit name=betternot 
						value="'.$lang['no_sorry'].'">
				</TD>
			</TR>
		</TABLE>
		</FORM>
	      </center>';

include ("footer.php");

?>
