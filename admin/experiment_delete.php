<?php
// part of orsee. see orsee.org
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
		$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));

                $query="DELETE FROM ".table('sessions')."
                        WHERE experiment_id='".$experiment_id."'";
                $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));

		$query="DELETE FROM ".table('participate_at')."
         		WHERE experiment_id='".$experiment_id."'";
                $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));

                $query="DELETE FROM ".table('lang')."
         		WHERE content_type='experiment_invitation_mail' 
			AND content_name='".$experiment_id."'";
                $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));


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
