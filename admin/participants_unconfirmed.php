<?php
ob_start();

$title="not confirmed registrations";
include ("header.php");

	$allow=check_allow('participants_unconfirmed_edit','participants_main.php');

	// delete one?
	if ($_REQUEST['delete'] && $_REQUEST['participant_id']) {
		$participant=orsee_db_load_array("participants_temp",$_REQUEST['participant_id'],"participant_id");
        	$query="DELETE FROM ".table('participants_temp')." 
         		WHERE participant_id='".$_REQUEST['participant_id']."'";
		$done=mysql_query($query) or die("Database error: " . mysql_error());
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
       	$result=mysql_query($query) or die("Database error: " . mysql_error());
	$emails=array();
 	while ($line=mysql_fetch_assoc($result)) {
		$emails[]=$line['email'];

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
					<A class="small" HREF="mailto:'.$line['email'].'">'.$line['email'].'</A>
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
