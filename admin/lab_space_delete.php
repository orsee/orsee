<?php
ob_start();

$title="delete lab reservation";
include("header.php");

         if (isset($_REQUEST['space_id']) && $_REQUEST['space_id']) $space_id=$_REQUEST['space_id'];
                else redirect ("admin/");

	if (isset($_REQUEST['betternot']) && $_REQUEST['betternot'])
		redirect ('admin/lab_space_edit.php?space_id='.$space_id);

        if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
                        else $reallydelete=false;

	$allow=check_allow('lab_space_delete','lab_space_edit.php?space_id='.$space_id);

	$space=orsee_db_load_array("lab_space",$space_id,"space_id");

	echo '	<BR><BR>
		<center>
			<h4>'.$lang['delete_lab_reservation'].'</h4>
		</center>';


	if ($reallydelete) { 

        	$query="DELETE FROM ".table('lab_space')." 
         		WHERE space_id='".$space_id."'";
		$result=mysql_query($query) or die("Database error: " . mysql_error());

		log__admin("lab_space_delete","space_id:".$space_id);
        	message ($lang['lab_reservation_deleted']);

		redirect ('admin/calendar_main.php');
		}

	// form

	echo '	<CENTER>
		<FORM action="lab_space_delete.php">
		<INPUT type=hidden name="space_id" value="'.$space_id.'">

		<TABLE>
			<TR>
				<TD colspan=2>
					'.$lang['do_you_really_want_to_delete'].'
					<BR><BR>';
					dump_array($space); echo '
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
