<?php
// part of orsee. see orsee.org
ob_start();

$menu__area= (isset($_REQUEST['participant_id']) && $_REQUEST['participant_id']) ? "participant_edit" : "participant_create";
$title="edit participant";

/*
todos:
- check email exists
- internet experiment participation statistics
*/


include ("header.php");

	if (isset($_REQUEST['participant_id']) && $_REQUEST['participant_id']) $participant_id=$_REQUEST['participant_id'];
		else $participant_id="";

	$allow=check_allow('participants_edit','participants_main.php');

	$continue=true; $errors__dataform=array();

	if (isset($_REQUEST['add']) && $_REQUEST['add']) { 

		// checks and errors
		foreach ($_REQUEST as $k=>$v) {
			if(!is_array($v)) $_REQUEST[$k]=trim($v);
		}
		$errors__dataform=participantform__check_fields($_REQUEST,true);		
        $error_count=count($errors__dataform);
        if ($error_count>0) $continue=false;

		if ($continue) {
           	$participant=$_REQUEST;

			if (!$participant_id) {
				$participant['participant_id']=participant__create_participant_id();
           		$participant['participant_id_crypt']=unix_crypt($participant['participant_id']);
           		$participant['creation_time']=time();
           		if (isset($_REQUEST['subpool_id']) && $_REQUEST['subpool_id']) $participant['subpool_id']=$_REQUEST['subpool_id'];
                else $participant['subpool_id']=$settings['subpool_default_registration_id'];
                if (!isset($participant['language']) || !$participant['language']) $participant['language']=$settings['public_standard_language'];
			}

			$done=orsee_db_save_array($participant,"participants",$participant['participant_id'],"participant_id");
			if ($done) message($lang['changes_saved']);
			var_dump($participant);
			
			if (!$participant_id && isset($_REQUEST['register_session']) && $_REQUEST['register_session']=='y') {
				$session=orsee_db_load_array("sessions",$_REQUEST['session_id'],"session_id");
				if ($session['session_id']) {
					$query="INSERT into ".table('participate_at')." 
                 				SET participant_id='".$participant['participant_id']."',
                        			session_id='".$session['session_id']."', 
                        			experiment_id='".$session['experiment_id']."',
                        			registered='y'";
					$done=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error());
                			message($lang['registered_participant_for'].' 
                        			<A HREF="experiment_participants_show.php?experiment_id='.
						$session['experiment_id'].'&session_id='.$session['session_id'].
						'&focus=registered">'.session__build_name($session).'</A>.');
				}
			}

           	if ($done) {
				if (isset($_REQUEST['participant_id']) && $_REQUEST['participant_id'])
					log__admin("participant_edit","participant_id:".$participant['participant_id']);
				else log__admin("participant_create","participant_id:".$participant['participant_id']);
                $form=false;
                redirect ("admin/participants_edit.php?participant_id=".$participant['participant_id']);
            } else {
            	message($lang['database_error']);
            }
		}
	}

    if ($participant_id && $continue) {
    	$_REQUEST=orsee_db_load_array("participants",$participant_id,"participant_id");
	}

	$button_title = ($participant_id) ? $lang['save'] : $lang['add'];

	participant__show_form($_REQUEST,$button_title,$lang['edit_participant'],$errors__dataform,true);

	if ($participant_id) {

	echo '<CENTER><BR><BR>';

	if ((!isset($_REQUEST['deleted']) || $_REQUEST['deleted']=='n') && check_allow('participants_unsubscribe')) {

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
	   elseif (isset($_REQUEST['deleted']) && $_REQUEST['deleted']=='y') {

		if (isset($_REQUEST['excluded']) && $_REQUEST['excluded']=="y")
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
