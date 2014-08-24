<?php
// part of orsee. see orsee.org
ob_start();

$title="experiment mainpage";

include ("header.php");


	if (!$_REQUEST['experiment_id']) redirect ("admin/");
		else $experiment_id=$_REQUEST['experiment_id'];

	$allow=check_allow('experiment_show','experiment_main.php');

	// load experiment data into array experiment
    	$experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");

	echo '<BR>
		<center>
		<h4>'.$experiment['experiment_name'].'</h4>
		';

	show_message();


// show basic settings
	if(!isset($lang[$experiment['experiment_type']])) $lang[$experiment['experiment_type']]=$experiment['experiment_type'];
	echo '<BR>
	<table border=1 width=90%>
		<TR>
			<TD bgcolor="'.$color['list_header_background'].'" colspan=2><h3>'.$lang['basic_data'].'</h3></TD>
		</TR>
		<TR><TD>'.$lang['id'].':</TD><TD>'.$experiment['experiment_id'].'</TD></TR>
		<TR><TD>'.$lang['name'].':</TD><TD>'.$experiment['experiment_name'].'</TD></TR>
		<TR><TD>'.$lang['public_name'].':</TD><TD>'.$experiment['experiment_public_name'].'</TD></TR>
		<TR><TD>'.$lang['type'].':</TD>
		<TD>'.$lang[$experiment['experiment_type']].' ('.$experiment['experiment_ext_type'].')</TD></TR>
		<TR><TD>'.$lang['class'].':</TD>
			<TD>'.experiment__get_experiment_class_names($experiment['experiment_class']).'</TD></TR>
		<TR><TD>'.$lang['description'].':</TD><TD>'.$experiment['experiment_description'].'</TD></TR>
		<TR><TD>'.$lang['experimenter'].':</TD><TD>'.experiment__list_experimenters($experiment['experimenter'],true,true).
			'</TD></TR>
		<TR><TD>'.$lang['get_emails'].':</TD><TD>'.experiment__list_experimenters($experiment['experimenter_mail'],true,true).
			'</TD></TR>
		<TR><TD>'.$lang['email_sender_address'].':</TD><TD>'.$experiment['sender_mail'].'</TD></TR>
		';

	if ($experiment['experiment_type']=="laboratory") {
		echo '<TR><TD>'.$lang['from'].':</TD>
			<TD>'.sessions__get_first_date($experiment['experiment_id']).'</TD></TR>
			<TR><TD>'.$lang['to'].':</TD>
			<TD>'.sessions__get_last_date($experiment['experiment_id']).'</TD></TR>';
		}
	if (downloads__files_downloadable($experiment['experiment_id'])) {
			echo '<TR><TD valign="top">'.$lang['files'].':</TD>
				<TD>';
			downloads__list_files($experiment['experiment_id']);
			echo '</TD></TR>';
			}

	// does the following make sense? we order them on different pages! OK, but this is the
	// experiment show page. Maybe the user has forgotten ... ;-)
	if ($experiment['experiment_type']=="laboratory") {
		echo '<TR><TD colspan=2>';
		if ($experiment['experiment_finished']=="y")
			echo $lang['experiment_finished'];
		   else echo $lang['experiment_not_finished'];
		echo '</TD></TR>';
		}

	echo '<TR><TD bgcolor="'.$color['list_options_background'].'" colspan=2>
		'.$lang['options'].':<BR><BR></TD></TR>
		<TR><TD colspan=2>
			<TABLE width=100% border=0>
				<TR><TD>';
					if (check_allow('experiment_edit')) echo '
						<A HREF="experiment_edit.php?experiment_id='.
							$experiment['experiment_id'].'">'.
							$lang['edit_basic_data'].'</A>';
	echo '				</TD><TD>';
					if (check_allow('download_experiment_upload')) {
					echo '<A HREF="download_upload.php?experiment_id='.
						$experiment['experiment_id'].'">'.
						$lang['upload_file'].'</A> ';
 					echo help('upload_files');
					}
	echo '			</TD></TR>
			</TABLE>
		</TD></TR>
	</TABLE>
	</center>';


	if ($experiment['experiment_type']=="laboratory") {
	// session summary

	echo '<center>
		<BR>
		<table border=1 width=90%>
		<TR>
			<TD bgcolor="'.$color['list_header_background'].'" colspan=2>
				<h3>'.$lang['sessions'].'</h3>
			</TD>
		</TR>
		<TR>
			<TD colspan=2>
				'.experiment__count_sessions($experiment['experiment_id']).' '.
				$lang['xxx_sessions_registered'].'<BR>
			</TD>
		</TR>

		<TR>
			<TD colspan=2 bgcolor="'.$color['list_list_background'].'">

			<TABLE border=0 width=100%>';

     	$query="SELECT *
      		FROM ".table('sessions')."
        	WHERE experiment_id='".$experiment['experiment_id']."'
      		ORDER BY session_start_year, session_start_month, session_start_day,
		 session_start_hour, session_start_minute";
	$done=orsee_query($query,"sessions__format_alist");

	echo '		</TABLE>

			</TD>
		</TR>
		<TR>
			<TD bgcolor="'.$color['list_options_background'].'" colspan=2>
				'.$lang['options'].':<BR><BR>
			</TD>
		</TR>
		<TR>
			<TD>';
				if (check_allow('session_edit')) echo '
					<A HREF="session_edit.php?experiment_id='.
						$experiment['experiment_id'].'">'.
					$lang['create_new'].'</A>';
	echo '		</TD>
			<TD>
			</TD>
		</TR>
		</TABLE>
		</center>';

}



// participant summary for laboratory experiments
	if ($experiment['experiment_type']=="laboratory"  || $experiment['experiment_type']=="internet") {

		$allow_sp=check_allow('experiment_show_participants'); // show links to participant lists?

		echo '	<center>
			<BR>
			<table border=1 width=90%>
			<TR>
				<TD bgcolor="'.$color['list_header_background'].'" colspan=2>
					<h3>'.$lang['participants'].'</h3>
				</TD>
			</TR>
			<TR>
				<TD>';
					if ($allow_sp) echo '<A HREF="experiment_participants_show.php?experiment_id='.
								$experiment['experiment_id'].'">';
					echo $lang['assigned_subjects'];
					if ($allow_sp) echo '</A>';
					echo ':
					'.help('assigned_subjects').'
				</TD>
				<TD>
					'.experiment__count_participate_at($experiment['experiment_id']).'
				</TD>
			</TR>
			<TR>
				<TD>';
					if ($allow_sp) echo '<A HREF="experiment_participants_show.php?experiment_id='.
								$experiment['experiment_id'].'&focus=invited">';
					echo $lang['invited_subjects'];
					if ($allow_sp) echo '</A>';
					echo ':
					'.help('invited_subjects').'
				</TD>
				<TD>
					'.experiment__count_invited($experiment['experiment_id']).'
				</TD>
			</TR>
			<TR>
				<TD>';
					if ($allow_sp) echo '<A HREF="experiment_participants_show.php?experiment_id='.
								$experiment['experiment_id'].'&focus=registered">';
					echo $lang['registered_subjects'];
					if ($allow_sp) echo '</A>';
					echo ':
					'.help('registered_subjects').'
				</TD>
				<TD>
					'.experiment__count_registered($experiment['experiment_id']).'
				</TD>
			</TR>
			<TR>
				<TD>';
					if ($allow_sp) echo '<A HREF="experiment_participants_show.php?experiment_id='.
								$experiment['experiment_id'].'&focus=shownup">';
					echo $lang['shownup_subjects'];
					if ($allow_sp) echo '</A>';
					echo ': 
					'.help('shownup_subjects').'
				</TD>
				<TD>
					'.experiment__count_shownup($experiment['experiment_id']).'
				</TD>
			</TR>
			<TR>
				<TD>';
					if ($allow_sp) echo '<A HREF="experiment_participants_show.php?experiment_id='.
								$experiment['experiment_id'].'&focus=participated">';
					echo $lang['subjects_participated'];
					if ($allow_sp) echo '</A>';
					echo ': 
					'.help('subjects_participated').'
				</TD>
				<TD>
					'.experiment__count_participated($experiment['experiment_id']).'
				</TD>
			</TR>
			<TR>
				<TD bgcolor="'.$color['list_options_background'].'" colspan=2>
					'.$lang['options'].':<BR><BR>
				</TD>
			</TR>';
			if (check_allow('experiment_assign_participants')) echo '
			<TR>
				<TD>
					<A HREF="experiment_add_participants.php?experiment_id='.
					$experiment['experiment_id'].'">'.$lang['assign_subjects'].'</A><BR>
				</TD>
				<TD>
					<A HREF="experiment_drop_participants.php?experiment_id='.
					$experiment['experiment_id'].'">'.
					$lang['delete_assigned_subjects'].'</A><BR>
				</TD>
			</TR>';
		echo '	<TR>
				<TD>';
					if (check_allow('experiment_invitation_edit')) echo '
						<A HREF="experiment_mail_participants.php?experiment_id='.
						$experiment['experiment_id'].'">'.$lang['send_invitations'].'</A><BR>';
		echo '		</TD>
				<TD>
				</TD>
			</TR>
			</TABLE>
			</center>';
		}


include ("footer.php");

?>
