<?php
ob_start();

$title="delete experiment";
include("header.php");

         if (isset($_REQUEST['experiment_id']) && $_REQUEST['experiment_id']) $experiment_id=$_REQUEST['experiment_id'];
                else redirect ("admin/");
	if (isset($_REQUEST['betternot']) && $_REQUEST['betternot'])
		rediRect ('admin/experiment_edit.php?experiment_id='.$experiment_id);
        if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
                        else $reallydelete=false;

	$allow=check_allow('experiment_delete','experiment_edit.php?experiment_id='.$experiment_id);

	$experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");

	if (!check_allow('experiment_restriction_override'))
		check_experiment_allowed($experiment,"admin/experiment_show.php?experiment_id=".$experiment_id);

	echo '	<BR><BR>
		<center>
			<h4>'.$lang['delete_experiment'].' '.$experiment['experiment_name'].'</h4>
		</center>';

	if ($reallydelete) { 

        	$query="DELETE FROM ".table('experiments')."
         		WHERE experiment_id='".$experiment_id."'";
		$result=mysql_query($query) or die("Database error: " . mysql_error());

                $query="DELETE FROM ".table('sessions')."
                        WHERE experiment_id='".$experiment_id."'";
                $result=mysql_query($query) or die("Database error: " . mysql_error());

		$query="DELETE FROM ".table('participate_at')."
         		WHERE experiment_id='".$experiment_id."'";
                $result=mysql_query($query) or die("Database error: " . mysql_error());

                $query="DELETE FROM ".table('lang')."
         		WHERE content_type='experiment_invitation_mail' 
			AND content_name='".$experiment_id."'";
                $result=mysql_query($query) or die("Database error: " . mysql_error());


		// now for the online surveys ...

                $query="DELETE FROM ".table('os_properties')."
         		WHERE experiment_id='".$experiment_id."'";
                $result=mysql_query($query) or die("Database error: " . mysql_error());

                $query="DELETE FROM ".table('os_data_form')."
         		WHERE experiment_id='".$experiment_id."'";
                $result=mysql_query($query) or die("Database error: " . mysql_error());

                $query="DELETE FROM ".table('os_results')."
         		WHERE experiment_id='".$experiment_id."'";
                $result=mysql_query($query) or die("Database error: " . mysql_error());

                $query="DELETE FROM ".table('os_page_content')."
         		WHERE experiment_id='".$experiment_id."'";
                $result=mysql_query($query) or die("Database error: " . mysql_error());

                $query="SELECT participant_id FROM ".table('os_playerdata')." 
         		WHERE experiment_id='".$experiment_id."' 
         		AND participant_id > 0";
                $result=mysql_query($query) or die("Database error: " . mysql_error());

		if (mysql_num_rows($result) > 0) {
			while ($line = mysql_fetch_assoc($result)) {
				$participants[]=$line['participant_id'];
				}
			$instr=implode(",",$participants);
                	$query="DELETE FROM ".table('participants_os')."
         			WHERE participant_id IN (".$instr.")";
                	$result=mysql_query($query) or die("Database error: " . mysql_error());
			}

                $query="SELECT * FROM ".table('os_questions')."
                        WHERE experiment_id='".$experiment_id."'";
                $result=mysql_query($query) or die("Database error: " . mysql_error());

                if (mysql_num_rows($result) > 0) {
                        while ($line = mysql_fetch_assoc($result)) {
                                $qids[]=$line['question_id'];
				$qtypes[$line['question_id']]=$line['question_type'];
                                }
                        $instr=implode(",",$qids);
                        $query="DELETE FROM ".table('os_pre_answers')."
                                WHERE question_id IN (".$instr.")";
                        $result=mysql_query($query) or die("Database error: " . mysql_error());

        		foreach ($qtypes as $key => $value) {
        			$query="DELETE FROM ".table('os_items_'.$value)." 
         				WHERE question_id='".$key."'";
				$result=mysql_query($query) or die("Database error: " . mysql_error());
				}
			}

                $query="DELETE FROM ".table('os_questions')."
                        WHERE experiment_id='".$experiment_id."'";
                $result=mysql_query($query) or die("Database error: " . mysql_error());

                $query="DELETE FROM ".table('os_playerdata')."
                        WHERE experiment_id='".$experiment_id."'";
                $result=mysql_query($query) or die("Database error: " . mysql_error());

        	message ($lang['experiment_deleted']);

		log__admin("experiment_delete","experiment:".$experiment['experiment_name']);
		redirect ('admin/experiment_main.php');
		}

	// form

	echo '	<CENTER>
		<FORM action="experiment_delete.php">
		<INPUT type=hidden name="experiment_id" value="'.$experiment_id.'">

		<TABLE>
			<TR>
				<TD colspan=2>
					'.$lang['really_delete_experiment'].'
					<BR><BR>';
					dump_array($experiment); echo '
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
