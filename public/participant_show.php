<?php
ob_start();

$menu__area="my_registrations";
include("header.php");


	if ($_REQUEST['s']) $session_id=url_cr_decode_session($_REQUEST['s']); else $session_id="";

	if ($_REQUEST['register']) {

		$continue=true;

		if ($_REQUEST['betternot']) {
			redirect("public/participant_show.php?p=".url_cr_encode($participant_id));
			}

		if (!$session_id) {
			$continue=false;
			log__participant("interfere",$participant_id);
			message($lang['error_session_id_register']);
			redirect("public/participant_show.php?p=".url_cr_encode($participant_id));
			}


		$experiment_id=sessions__get_experiment_id($session_id);
		$registered=expregister__check_registered($participant_id,$experiment_id);
		if ($registered) {
			$continue=false;
			message($lang['error_already_registered']);
			redirect("public/participant_show.php?p=".url_encode($participant_id));
			}	

		$registration_end=sessions__get_registration_end("",$session_id);
		$full=sessions__session_full($session_id);

		$now=time();
		if ($registration_end < $now) {
			$continue=false;
                	message($lang['error_registration_expired']);
                        redirect("public/participant_show.php?p=".url_cr_encode($participant_id));
			}

		if ($full) {
			 $continue=false;
                         message($lang['error_session_complete']);
                         redirect("public/participant_show.php?p=".url_cr_encode($participant_id));
                         }

		if ($_REQUEST['reallyregister']) {

			// if all checks are done, register ...
			if ($continue) {
				expregister__register($participant_id,$session_id);
				log__participant("register",$participant['participant_id'],
					"experiment_id:".$experiment_id."\nsession_id:".$session_id);
				message($lang['successfully_registered_to_experiment_xxx']." ".
			 		experiment__get_public_name($experiment_id).", ".
			 		time__format_session_time($session_id).". ".
			 		$lang['this_will_be_confirmed_by_an_email']);
					redirect("public/participant_show.php?p=".url_cr_encode($participant_id)."&s=".url_cr_encode($session_id));
				}
			} 
		   else {
			
			$session=orsee_db_load_array("sessions",$session_id,"session_id");

			echo '
                		<center><BR><BR>
                		<H4>'.$lang['experiment_registration'].'</H4>
                		<BR><BR>

				<form action="participant_show.php">
				<INPUT type=hidden name="s" value="'.$_REQUEST['s'].'">
				<INPUT type=hidden name="p" value="'.url_cr_encode($participant_id).'">
				<INPUT type=hidden name="register" value="true">

				<TABLE width=80%>
				<TR>
					<TD colspan=2 align=center>
						'.$lang['do_you_really_want_to_register_for experiment'].'
					</TD>
				</TR>
				<TR>
					<TD align=right width=50%>
						'.$lang['experiment'].':
					</TD>
					<TD width=50%>
						'.experiment__get_public_name($experiment_id).'
					</TD>
				</TR>
				<TR>
					<TD align=right>
						'.$lang['date_and_time'].':
					</TD>
					<TD>
						'.time__format_session_time($session_id).'
					</TD>
				</TR>
				<TR>
					<TD align=right>
						'.$lang['laboratory'].':
					</TD>
					<TD>
						'.laboratories__get_laboratory_name($session['laboratory_id']).'
					</TD>
				</TR>
				<TR>
					<TD colspan=2>&nbsp;</TD>
				</TR>
				<TR>
					<TD align=center colspan=2>
						<INPUT type=submit name="reallyregister" value="'.$lang['yes_i_want'].'">
						&nbsp;&nbsp;&nbsp;&nbsp;
						<INPUT type=submit name="betternot" value="'.$lang['no_sorry'].'">
					</TD>
				</TR>
				</TABLE>
				</FORM>
				</center>

				';
			}
	   	}
	   else {

	echo '<SCRIPT LANGUAGE="JavaScript">
		function openprint() {
		printwin=open("participant_show_print.php?p='.$_REQUEST['p'].'", "faq", "width=700,height=500,location=no,toolbar=yes,menubar=no,status=no,directories=no,scrollbars=yes,resizable=yes") 
		}
		</SCRIPT>
		';

	echo '
		<center>
		<BR><BR>
		<H4>'.$lang['experiment_registration'].'</H4>
		<BR><BR>


		<TABLE width=80%>
		<TR><TD colspan=3 bgcolor=lightblue>';
	echo $lang['experiments_you_are_invited_for'];
	echo '</TD>
		</TR>
		<TR><TD colspan=3>
		'.$lang['please_check_availability_before_register'].'
		</TD></TR>';

	$labs=expregister__list_invited_for($participant_id);


	echo '<TR><TD colspan=3>&nbsp;</TD></TR>
		<TR><TD colspan=3 bgcolor=lightblue>
		'.$lang['experiments_already_registered_for'].'
		(<A HREF="javascript:openprint()">'.$lang['print_version'].'</A>)
		</TD>
		</TR>';

	$labs2=expregister__list_registered_for($participant_id,$session_id);

	echo '<TR><TD colspan=3>&nbsp;</TD></TR>';

	$laboratories=array_unique(array_merge($labs,$labs2));

	if (count($laboratories)>0) {
		echo '<TR><TD colspan=3>';

		echo '<TABLE width=100%>
			<TR bgcolor="white"><TD colspan=2>';
		if (count($laboratories)==1) echo $lang['laboratory_address']; else echo $lang['laboratory_addresses'];
		echo '</TD></TR>';

		foreach ($laboratories as $laboratory_id) {
			echo '<TR><TD valign=top>';
			echo laboratories__get_laboratory_name($laboratory_id);
			echo '</TD>
				<TD>';
			$address=laboratories__get_laboratory_address($laboratory_id);
			echo str_replace("\n","<BR>",$address);
			echo '</TD></TR>';
			}
		echo '</TABLE></TD></TR>';
		echo '<TR><TD colspan=3>&nbsp;</TD></TR>';
		}


	echo '<TR><TD colspan=3 bgcolor=lightblue>
		'.$lang['experiments_you_participated'].'
		</TD></TR>
		<TR><TD colspan=3>
		'.$lang['registered_for'].' '.$participant['number_reg'].'<BR>
		'.$lang['not_shown_up'].' '.$participant['number_noshowup'].'
		</TD>
		</TR>';
	expregister__list_history($participant_id);
	echo '</TABLE>';

	echo '<BR><BR><A HREF="participant_edit.php?p='.url_cr_encode($participant_id).'">'.$lang['edit_your_profile'].'</A>';

	echo '</center>';

	}

include("footer.php");


?>
