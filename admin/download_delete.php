<?php
ob_start();

$title="delete download";
include("header.php");

         if (isset($_REQUEST['dl']) && $_REQUEST['dl']) $upload_id=$_REQUEST['dl'];
                else redirect ("admin/");

	if (isset($_REQUEST['experiment_id']) && $_REQUEST['experiment_id']) {
		$experiment__id=$_REQUEST['experiment_id'];
		if (!check_allow('experiment_restriction_override'))
			check_experiment_allowed($experiment_id,"admin/experiment_show.php?experiment_id=".$experiment_id);
		}
                else $experiment_id="";

	$link= ($experiment_id) ? "download_main.php?experiment_id=".$experiment_id : "download_main.php";

	if ($experiment_id) $allow=check_allow('download_experiment_delete',$link);
		else $allow=check_allow('download_general_delete',$link);

	if (isset($_REQUEST['betternot']) && $_REQUEST['betternot'])
		redirect ('admin/'.$link);

        if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
                        else $reallydelete=false;

	$upload=orsee_db_load_array("uploads",$upload_id,"upload_id");

	echo '	<BR><BR>
		<center>
			<h4>'.$lang['delete_download'].'</h4>
		</center>';


	if ($reallydelete) { 

        	$query="DELETE FROM ".table('uploads')." 
         		WHERE upload_id='".$upload_id."'";
		$result=mysql_query($query) or die("Database error: " . mysql_error());

		$query="DELETE FROM ".table('uploads_data')."  
                        WHERE upload_id='".$upload_id."'";
                $result=mysql_query($query) or die("Database error: " . mysql_error());
		$target.= ($experiment_id) ? "experiment_id:".$experiment_id : "general";
		log__admin("file_delete",$target);
        	message ($lang['download_deleted']);

		redirect ('admin/'.$link);
		}

	// form

	echo '	<CENTER>
		<FORM action="download_delete.php">
		<INPUT type=hidden name="dl" value="'.$upload_id.'">
		<INPUT type=hidden name="experiment_id" value="'.$experiment_id.'">

		<TABLE>
			<TR>
				<TD colspan=2>
					'.$lang['do_you_really_want_to_delete'].'
					<BR><BR>';
					dump_array($upload); echo '
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
