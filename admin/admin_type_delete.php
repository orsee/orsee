<?php
ob_start();

$menu__area="options";
$title="delete admin type";
include("header.php");

	if (isset($_REQUEST['type_id']) && $_REQUEST['type_id']) $type_id=$_REQUEST['type_id'];
                        else redirect ('admin/admin_type_show.php');

	if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
                        else $reallydelete=false;

	if (isset($_REQUEST['betternot']) && $_REQUEST['betternot'])
                        redirect ('admin/admin_type_edit.php?type_id='.$type_id);

	$allow=check_allow('admin_type_delete','admin_type_edit.php?type_id='.$type_id);

	$type=orsee_db_load_array("admin_types",$type_id,"type_id");

	if ($reallydelete) {

		$stype=$_REQUEST['stype'];

		if ($type['type_name']==$stype) {
			message ($lang['type_to_be_deleted_cannot_be_type_to_substitute']);
			redirect ('admin/admin_type_delete.php?type_id='.$type_id);
			}

		// update admins 
		$query="UPDATE ".table('admin')." SET admin_type='".$stype."' WHERE admin_type='".$type['type_name']."'";
		$done=mysql_query($query) or die("Database error: " . mysql_error());

		// delete language column
       		$query="DELETE FROM ".table('admin_types')." 
        		WHERE type_id='".$type_id."'";
		$done=mysql_query($query) or die("Database error: " . mysql_error());


		// bye, bye
        	message ($lang['admin_type_deleted'].': '.$type['type_name']);
		log__admin("admin_type_delete","admintype:".$type['type_name']);
		redirect ('admin/admin_type_show.php');
		}


	echo '  <BR><BR>
                <center>
                         <h4>'.$lang['delete_admin_type'].' '.$type['type_name'].'</h4>
                </center>';


	// confirmation form

	echo '	<CENTER>
		<FORM action="admin_type_delete.php">
		<INPUT type=hidden name="type_id" value="'.$type_id.'">
		<INPUT type=hidden name="nlang" value="'.$slang.'">

		<TABLE>
			<TR>
				<TD colspan=2>
					'.$lang['do_you_really_want_to_delete'].'
					<BR><BR>
				</TD>
			</TR>
			<TR>
				<TD align=right>
					'.$lang['copy_admins_of_this_type_to'].':
				</TD>
				<TD>';
					admin__select_admin_type("stype",$settings['default_admin_type']);
			echo '	</TD>
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
