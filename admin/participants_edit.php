<?php
ob_start();

$menu__area= ($_REQUEST['participant_id']) ? "participant_edit" : "participant_create";
$title="edit participant";

/*
todos:
- check email exists
- internet experiment participation statistics
*/


include ("header.php");

	if ($_REQUEST['participant_id']) $participant_id=$_REQUEST['participant_id'];
		else $participant_id="";

	$allow=check_allow('participants_edit','participants_main.php');

	$continue=true;

	if ($_REQUEST['add']) { 

		participant__check_email(true);
                participant__check_fname(true);
                participant__check_lname(true);
		participant__check_invitations(true);

                $error_count=count($errors__dataform);

                if ($error_count>0) {
                        $continue=false;

                        if (in_array("email",$errors__dataform)) 
				message($lang['you_have_to_email_address']);
                        if (in_array("fname",$errors__dataform)) 
				message($lang['you_have_to_fname']);
                        if (in_array("lname",$errors__dataform)) 
				message($lang['you_have_to_lname']);
			if (in_array("subscriptions",$errors__dataform)) 
                                message($lang['at_least_one_exptype_has_to_be_selected']);
                        }

	        if ($continue) {


                	$participant=$_REQUEST;

			if (!$participant_id) {

				$participant['participant_id']=participant__create_participant_id();
                		$participant['participant_id_crypt']=unix_crypt($participant['participant_id']);
                		$participant['creation_time']=time();
                		if (isset($_REQUEST['subpool_id']) && $_REQUEST['subpool_id'])
                                		$participant['subpool_id']=$_REQUEST['subpool_id'];
                        		else    $participant['subpool_id']=$settings['subpool_default_registration_id'];
                		if (!$participant['language']) 
					$participant['language']=$settings['public_standard_language'];
				}

			$done=orsee_db_save_array($participant,"participants",$participant['participant_id'],"participant_id");
			if ($done) message($lang['changes_saved']);

			if (!$participant_id && $_REQUEST['register_session']=='y') {
				$session=orsee_db_load_array("sessions",$_REQUEST['session_id'],"session_id");
				if ($session['session_id']) {
					$query="INSERT into ".table('participate_at')." 
                 				SET participant_id='".$participant['participant_id']."',
                        			session_id='".$session['session_id']."', 
                        			experiment_id='".$session['experiment_id']."',
                        			registered='y'";
					$done=mysql_query($query) or die("Database error: " . mysql_error());
                			message($lang['registered_participant_for'].' 
                        			<A HREF="experiment_participants_show.php?experiment_id='.
						$session['experiment_id'].'&session_id='.$session['session_id'].
						'&focus=registered">'.session__build_name($session).'</A>.');
					}
				}

           		if ($done) {
				if ($_REQUEST['participant_id'])
					log__admin("participant_edit","participant_id:".$participant['participant_id']);
				   else log__admin("participant_create","participant_id:".$participant['participant_id']);
                		$form=false;
                		redirect ("admin/participants_edit.php?participant_id=".$participant['participant_id']);
                		}
             		   else {
                		message($lang['database_error']);
                		}
			}
		}

        if ($participant_id && $continue) {
                $_REQUEST=orsee_db_load_array("participants",$participant_id,"participant_id");
		$subexptypes=explode(",",$_REQUEST['subscriptions']);
		$_REQUEST['invitations']=array();
			foreach ($subexptypes as $type) $_REQUEST['invitations'][$type]=$type;
		}

	$button_title = ($participant_id) ? $lang['save'] : $lang['add'];

	if ($participant_id) $form_type="admin-edit"; else $form_type="admin-add";
	participant__form($lang['edit_participant'],$button_title,$form_type);

	if ($participant_id) {

	echo '<CENTER>';

	if ($_REQUEST['deleted']=='n' && check_allow('participants_unsubscribe')) {

		echo '<FORM action="participants_delete.php">
			<INPUT type=hidden name="participant_id" value="'.$participant_id.'">
			<TABLE>
				<TR>
					<TD>
						'.$lang['unsubscribe_participant'].'<BR>
					</TD>
				</TR>
				<TR>
					<TD align=center>
						<INPUT type=submit name=delete 
							value="'.$lang['unsubscribe'].'">
					</TD>
				</TR>
			</TABLE>
		      </FORM>';
		}
	   elseif ($_REQUEST['deleted']=='y') {

		if ($_REQUEST['excluded']=="y")
			echo '<FONT color="red">'.$lang['was_excluded_by_experimenter'].'</FONT>';

		if (check_allow('participants_resubscribe')) {
		echo '<FORM action="participants_resubscribe.php">
			<INPUT type=hidden name="participant_id" value="'.$participant_id.'">
			<TABLE>
				<TR>
					<TD>
						'.$lang['resubscribe_participant'].'<BR>
					</TD>
				</TR>
				<TR>
					<TD align=center>
						<INPUT type=submit name=resubscribe 
							value="'.$lang['resubscribe'].'">
					</TD>
				</TR>
			</TABLE>
		      </FORM>';
			}
		}

	echo '</CENTER>';

	}

	echo '<CENTER>';

	participants__get_statistics($participant_id);


	echo "</CENTER>";

include ("footer.php");
?>
