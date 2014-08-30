<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="my_data";
include("header.php");

	$form=true;
	
	$errors__dataform=array(); 
	
	if (isset($_REQUEST['add']) && $_REQUEST['add']) {
		$continue=true;
		$_REQUEST['participant_id']=$participant['participant_id'];
            
		// checks and errors
		foreach ($_REQUEST as $k=>$v) {
			if(!is_array($v)) $_REQUEST[$k]=trim($v);
		}
		$errors__dataform=participantform__check_fields($_REQUEST,false);		
        $error_count=count($errors__dataform);
        if ($error_count>0) $continue=false;

		$response=participantform__check_unique($_REQUEST,"edit",$_REQUEST['participant_id']);
		if($response['problem']) { $continue=false; }
        
        if ($continue) {
           	$participant=$_REQUEST;

			$done=orsee_db_save_array($participant,"participants",$participant['participant_id'],"participant_id");

	   		if ($done) {
				message($lang['changes_saved']);
				log__participant("edit",$participant['participant_id']);
				redirect("public/participant_edit.php?p=".url_cr_encode($participant['participant_id']));
			} else {
				message($lang['database_error']);
             	redirect ("public/participant_edit.php?p=".url_cr_encode($participant['participant_id']));
	  		} 
		}
	} else {
    	$_REQUEST=$participant;
	}


// form

	if ($form) {

		participant__show_form($_REQUEST,$lang['save'],$lang['edit_participant_data'],$errors__dataform,false);

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
