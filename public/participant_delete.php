<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="my_data";
include("header.php");

	if (isset($_REQUEST['betternot'])) redirect("public/participant_edit.php?p=".url_cr_encode($participant['participant_id']));

	$form=true;

	if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']=="12345" && isset($_REQUEST['doit'])) {
 

                $query="UPDATE ".table('participants')." 
		 	SET deleted='y'
                 	WHERE participant_id='".mysqli_real_escape_string($GLOBALS['mysqli'],$participant_id)."'";
		$done=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
		log__participant("delete",$participant_id);
		$form=false;
		message ($lang['removed_from_invitation_list']);
		redirect("public/");
		}	

	if ($form) {

		echo '<BR><BR>
			<center>
			<h4>'.$lang['delete_participant'].'</h4>

			<FORM action="participant_delete.php">
			<INPUT type=hidden name="p" value="';
		echo unix_crypt($participant_id);
		echo '">
			<TABLE>
			<TR>
			<TD colspan=2><INPUT name=reallydelete type=hidden value="12345">
			'.$lang['do_you_really_want_to_unsubscribe'].'<BR></TD>
			</TR>
			<TR><TD>
			<INPUT type=submit name=doit value="'.$lang['yes_i_want'].'">
			</TD>
			<TD><INPUT type=submit name=betternot value="'.$lang['no_sorry'].'">
			</TD>
			</TR>
			</TABLE>
			</FORM>
			</center>';
		}

include("footer.php");

?>



