<?php
ob_start();

$menu__area="my_data";
include("header.php");

	$form=true;

	if ($_REQUEST['add']) {
		$continue=true;
		$_REQUEST['participant_id']=$participant['participant_id'];
                $errors__dataform=array();

                participant__check_email(true);
                participant__check_fname(true);
                participant__check_lname(true);
		participant__check_invitations(true);

                $error_count=count($errors__dataform);

                if ($error_count>0) {
                        $continue=false;

                        if (in_array("email",$errors__dataform)) message($lang['you_have_to_email_address']);
                        if (in_array("fname",$errors__dataform)) message($lang['you_have_to_fname']);
                        if (in_array("lname",$errors__dataform)) message($lang['you_have_to_lname']);
			if (in_array("subscriptions",$errors__dataform))
                                message($lang['at_least_one_exptype_has_to_be_selected']);
                        }

        	if ($continue) {
                	$participant=$_REQUEST;

			$done=orsee_db_save_array($participant,"participants",$participant['participant_id'],"participant_id");

	   		if ($done) {
				message($lang['changes_saved']);
				log__participant("edit",$participant['participant_id']);
				redirect("public/participant_edit.php?p=".url_cr_encode($participant['participant_id']));
				}
	   		   else {
				message($lang['database_error']);
                		redirect ("public/participant_edit.php?p=".url_cr_encode($participant['participant_id']));
	  			} 
			}
		}

	   else {
        	$_REQUEST=$participant;
		$subexptypes=explode(",",$_REQUEST['subscriptions']);
                $_REQUEST['invitations']=array();
                foreach ($subexptypes as $type) $_REQUEST['invitations'][$type]=$type;
		}



// form

	if ($form) {

		participant__form($lang['edit_participant_data'],$lang['save']);

		echo '<CENTER>
			<BR><BR>
			<A HREF="participant_show.php?p='.url_cr_encode($_REQUEST['participant_id']).'">'.
			$lang['click_to_experiment_registrations'].'</A>
			<BR><BR>
			<FORM action=participant_delete.php>
			<INPUT type=hidden name="p" value="'.unix_crypt($_REQUEST['participant_id']).'">
			<TABLE>
			<TR>
			<TD>
			'.$lang['i_want_to_delete_my_data'].'<BR></TD>
			</TR>
			<TR><TD>
			<center><INPUT type=submit name=delete value="'.$lang['unsubscribe'].'"></center>
			</TD>
			</TR>
			</TABLE>
			</FORM>
			</center>';

		}

	echo '</CENTER>';

include("footer.php");

?>
