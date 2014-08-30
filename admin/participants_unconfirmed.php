<?php
// part of orsee. see orsee.org
ob_start();

$title="not confirmed registrations";
include ("header.php");

	$allow=check_allow('participants_unconfirmed_edit','participants_main.php');

	// delete one?
	if (isset($_REQUEST['delete']) && $_REQUEST['delete'] && isset($_REQUEST['participant_id']) && $_REQUEST['participant_id']) {
		$participant=orsee_db_load_array("participants_temp",$_REQUEST['participant_id'],"participant_id");
        	$query="DELETE FROM ".table('participants_temp')." 
         		WHERE participant_id='".$_REQUEST['participant_id']."'";
		$done=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
		message($lang['temp_participant_deleted'].': '.$participant['lname'].', '.$participant['fname']);
		log__admin("participant_unconfirmed_delete","participant: ".$participant['lname'].', '.$participant['fname']);
		redirect ("admin/participants_unconfirmed.php");
		}


	echo '<center><BR><BR><h4>'.$lang['registered_but_not_confirmed_xxx'].'</h4>';


	echo '<BR>
		<table border=0>
		<TR>
			<TD class="small">
				'.$lang['date'].'
			</TD>
			<TD class="small">
				'.$lang['id'].'
			</TD>
			<TD class="small">
				'.$lang['lastname'].'
			</TD>
			<TD class="small">
				'.$lang['firstname'].'
			</TD>
			<TD class="small">
				'.$lang['email'].'
			</TD>
			<TD class="small">
				'.$lang['subscriptions'].'
			</TD>
			<TD></TD>
		</TR>';


     	$query="SELECT *
      		FROM ".table('participants_temp')." 
      		ORDER BY creation_time, email";
       	$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
	$emails=array(); $shade=false;
	
	$typenames=load_external_experiment_type_names();
	
	
 	while ($line=mysqli_fetch_assoc($result)) {
		$emails[]=$line['email'];
		
		$line['field_of_studies']=language__get_item('field_of_studies',$line['field_of_studies']);       
		$line['profession']=language__get_item('profession',$line['profession']);
		$line['gender']=$lang['gender_'.$line['gender']];
		$exptypes=explode(",",$line['subscriptions']);
		$invnames=array();
		foreach ($exptypes as $type) $invnames[]=$typenames[$type];
		$line['invitations']=implode(", ",$invnames);
		$line['registration_link']=$settings__root_url."/public/participant_confirm.php?p=".url_cr_encode($line['participant_id']);

		$mailtext=load_mail("public_system_registration",$lang['lang']);
		$message=process_mail_template($mailtext,$line);
		$message=str_replace(" ","%20",$message);
		$message=str_replace("\n\m","\n",$message);
		$message=str_replace("\m\n","\n",$message);
		$message=str_replace("\m","\n",$message);
		$message=str_replace("\n","%0D%0A",$message);

		$linktext='mailto:'.$line['email'].'?subject='.str_replace(" ","%20",$lang['registration_email_subject']).'&reply-to='.urlencode($settings['support_mail']).'&body='.$message;


		echo '	<tr class="small"';
		if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"'; 
			else echo ' bgcolor="'.$color['list_shade2'].'"';
		echo '>
   				<td class="small">
					'.time__format($lang['lang'],"",false,false,false,false,$line['creation_time']).'
				</td>
   				<td class="small">
					'.$line['participant_id'].'
				</td>
   				<td class="small">
					'.$line['lname'].'
				</TD>
   				<td class="small">
					'.$line['fname'].'
				</td>
   				<td class="small">
					<A class="small" HREF="'.$linktext.'">'.$line['email'].'</A>
				</td>
   				<td class="small">
					'.$line['subscriptions'].'
				</td>
   				<TD>
					<A HREF="participants_unconfirmed.php?participant_id='.$line['participant_id'].'&delete=true">'.
						$lang['delete'].'</A>
				</TD>
   			</tr>';
   		if ($shade) $shade=false; else $shade=true;
		}



	echo '</table>';


	$emailstring=implode(",",$emails);
        echo '<BR><BR><A HREF="mailto:'.$settings['support_mail'].'?bcc='.$emailstring.'">'.$lang['write_message_to_all_listed'].'</A>';
	echo '<BR><BR><A href="participants_main.php">'.icon('back').' '.$lang['back'].'</A><BR><BR>';

	echo '</CENTER>';

include ("footer.php");

?>
