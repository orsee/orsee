<?php
ob_start();

$menu__area="options";
$title="delete admin";
include("header.php");

         if (isset($_REQUEST['admin_id']) && $_REQUEST['admin_id']) $admin_id=$_REQUEST['admin_id'];
                else redirect ("admin/");

	if (isset($_REQUEST['betternot']) && $_REQUEST['betternot'])
		redirect ('admin/admin_edit.php?admin_id='.$admin_id);

        if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
                        else $reallydelete=false;

	$allow=check_allow('admin_delete','admin_edit.php?admin_id='.$admin_id);

	$admin=orsee_db_load_array("admin",$admin_id,"admin_id");

	echo '	<BR><BR>
		<center>
			<h4>'.$lang['delete_admin'].' '.$admin['fname'].' '.$admin['lname'].' ('.$admin['adminname'].')</h4>
		</center>';


	if ($reallydelete) { 

        	$query="DELETE FROM ".table('admin')." 
         		WHERE admin_id='".$admin_id."'";
		$result=mysql_query($query) or die("Database error: " . mysql_error());
		log__admin("admin_delete",$admin['adminname']);

        	message ($lang['admin_deleted'].': '.$admin['adminname']);

		redirect ('admin/admin_show.php');
		}

	// form

	echo '	<CENTER>
		<FORM action="admin_delete.php">
		<INPUT type=hidden name="admin_id" value="'.$admin_id.'">

		<TABLE>
			<TR>
				<TD colspan=2>
					'.$lang['do_you_really_want_to_delete'].'
					<BR><BR>';
					dump_array($admin); echo '
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



