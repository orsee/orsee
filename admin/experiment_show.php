<?php
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


/*

<when <string-eq '.$experiment['experiment_type'].' "online-survey">>
////////////////////////////////////////////////;;
// Experiment-Daten in Package experiment laden
////////////////////////////////////////////////;
<sql::with-open-database db dsn=<site::dsn> nolock=true>
  <set-var loaded?=<sql::database-load-record db <get-var posted::experiment_id> TABLE=os_properties KEYNAME=experiment_id PACKAGE=os>>
</sql::with-open-database>
//------------------------------------------------------------------------

<set-var os::results=<os::any-results? '.$experiment['experiment_id'].'>>

<center>
<BR>
<table border=1 width=90%>
<TR>
<TD bgcolor="'.$color['list_header_background'].'" colspan=2><h3>'.$lang['os_properties'].'</h3></TD>
</TR>
<when <get-var loaded?>>
<TR><TD>'.$lang['description'].':</TD><TD><get-var os::public_description></TD></TR>
<TR><TD>'.$lang['from'].':</TD><TD><time::format year=<get-var os::start_year> month=<get-var os::start_month> day=<get-var os::start_day> hour=<get-var os::start_hour> minute=<get-var os::start_minute> hide_second=true></TD></TR>
<TR><TD>'.$lang['to'].':</TD><TD><time::format year=<get-var os::stop_year> month=<get-var os::stop_month> day=<get-var os::stop_day> hour=<get-var os::stop_hour> minute=<get-var os::stop_minute> hide_second=true></TD></TR>
</when>
<TR><TD>'.$lang['window_size'].':</TD><TD><get-var os::window_size_x>x<get-var os::window_size_y></TD></TR>

<TR><TD bgcolor="'.$color['list_options_background'].'" colspan=2>
'.$lang['options'].':<BR><BR></TD></TR>
<TR><TD colspan=2>
<TABLE width=100% border=0>
<TR><TD>
<A HREF="os_properties_edit.php?experiment_id='.$experiment['experiment_id'].'">'.$lang['edit_os_properties'].'</A>
</TD><TD>
<defun make-page-link page>
	<when <string-eq <get-var os::<get-var page>> "y">>
		<A HREF="os_extra_pages_edit.php?experiment_id='.$experiment['experiment_id'].'&page_name=<get-var page>"><%%eval '.$lang[edit_<get-var page].'>></A><BR>
	</when>
</defun>
<make-page-link introduction>
<make-page-link instructions>
<make-page-link final_page>
</TD>
 </TR>
<TR><TD>
<A HREF="../public/os_show.php?ostest=true" target="_blank">'.$lang['test_whole_online_survey'].'</A>
</TD>
<TD></TD>
</TR>
</TABLE>
</TD></TR>
</TABLE>
</center>

<when <string-eq <get-var os::data_form> "y">>
////////////////////////////////////////////////;;
// Experiment-Daten in Package experiment laden
////////////////////////////////////////////////;
<sql::with-open-database db dsn=<site::dsn> nolock=true>
  <set-var loaded?=<sql::database-load-record db <get-var posted::experiment_id> TABLE=os_data_form KEYNAME=experiment_id PACKAGE=os_data>>
</sql::with-open-database>
//------------------------------------------------------------------------
<center>
<BR>
<table border=1 width=90%>
<TR>
<TD bgcolor="'.$color['list_header_background'].'" colspan=2><h3>'.$lang['os_personal_data_form'].'</h3></TD>
</TR>
<TR><TD>'.$lang['position'].':</td>
<td>
<if <get-var os_data::position> <group <if <string-eq <get-var os_data::position> "e"> '.$lang['at_end'].' '.$lang['at_start'].'>> <concat "???">>
</TD></TR>

<TR><TD bgcolor="'.$color['list_options_background'].'" colspan=2>
'.$lang['options'].':<BR><BR></TD></TR>
<TR><TD colspan=2>
<TABLE width=100% border=0>
<TR><TD>
<A HREF="os_data_form_edit.php?experiment_id='.$experiment['experiment_id'].'">'.$lang['edit_os_data_form'].'</A>
</TD><TD>
</TD>
 </TR>
</TABLE>
</TD></TR>
</TABLE>
</center>
</when>




<BR><BR>
<include ../scripts/open_os_question_js.php>

<center>
<table border=1 width=90%>
<TR>
<TD bgcolor="'.$color['list_header_background'].'" colspan=2><h3>'.$lang['questions'].'</h3></TD>
</TR>
<TR><TD bgcolor="'.$color['list_item_emphasize_background'].'" colspan=2><if <survey::random-order? experiment_id='.$experiment['experiment_id'].'>
				'.$lang['presented_in_random_order'].'
				'.$lang['presented_ordered_by_number'].'></TD></TR>
<TR><TD colspan=2>
<TABLE width="100%" bgcolor="'.$color['list_list_background'].'">
// question list
////////////////////////////////////////
// daten ausgeben
<sql::with-open-database db dsn=<site::dsn> nolock=true>
    <sql::database-query
     db true
     "SELECT *
      FROM os_questions
        WHERE experiment_id='.$experiment['experiment_id'].'
      ORDER BY question_order"
       format =<survey::questions-format-alist <package-to-alist>>>
</sql::with-open-database>
</TABLE>

</TD></TR>

<TR><TD bgcolor="'.$color['list_options_background'].'" colspan=2>
'.$lang['options'].':<BR><BR></TD></TR>
<TR><TD colspan=2>
<TABLE width=100% border=0>
<TR><TD>
<A HREF="os_question_edit.php?experiment_id='.$experiment['experiment_id'].'">'.$lang['create_new'].'</A>
</TD><TD>
<when <gt <survey::count-questions '.$experiment['experiment_id'].'> 1>>
<A HREF="os_question_order?experiment_id='.$experiment['experiment_id'].'">'.$lang['order_questions'].'</A>
</when>
</TD>
 </TR>
</TABLE>
</TD></TR>
</TABLE>

</center>




</when>

*/

// participant summary for laboratory experiments
	if ($experiment['experiment_type']=="laboratory") {

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

/*

//////////////////////////////////////////;;
// participant summary for online-survey experiments
////////////////////////////////////////;
<when <string-eq '.$experiment['experiment_type'].' "online-survey">>

<center>
<BR>
<table border=1 width=90%>
<TR>
<TD bgcolor="'.$color['list_header_background'].'" colspan=7><h3>'.$lang['participants'].'</h3></TD>
</TR>
<TR>
<TD></TD>
<TD>'.$lang['assigned'].'</TD>
<TD>'.$lang['invited'].'</TD>
<TD>'.$lang['participated'].'</TD>
<TD>'.$lang['finished'].'</TD>
<TD>'.$lang['dataform'].'</TD>
<TD>'.$lang['to_subpool'].'</TD>
</TR>

// row for participants from subject pool
<TR>
	<TD>
		'.$lang['participants_from_subject_pool'].'
	</TD>

	<TD>
		<A HREF="os_participants_show.php?experiment_id='.$experiment['experiment_id'].'&focus=assigned">
		<experiment::count-participate_at '.$experiment['experiment_id'].'>
		</A>
	</TD>

	<TD>
		<A HREF="os_participants_show.php?experiment_id='.$experiment['experiment_id'].'&focus=invited">
		<experiment::count-invited '.$experiment['experiment_id'].'>
		</A>
	</TD>

	<TD>
		<A HREF="os_participants_show.php?experiment_id='.$experiment['experiment_id'].'&focus=participated&free_reg=n">
		<experiment::count-participated '.$experiment['experiment_id'].'>
		</A>
	</TD>

	<TD>
		<A HREF="os_participants_show.php?experiment_id='.$experiment['experiment_id'].'&focus=finished&free_reg=n">
		<survey::count-finished '.$experiment['experiment_id'].' reg="n">
		</A>
	</TD>

	<TD>
	</TD>
</TR>

<TR>
	<TD>
		'.$lang['free_registration'].'
	</TD>
	
	<TD>
	</TD>
	<TD>
	</TD>

	<TD>
		<A HREF="os_participants_show.php?experiment_id='.$experiment['experiment_id'].'&focus=participated&free_reg=y">
		<survey::count-free-reg '.$experiment['experiment_id'].'>
		</A>
	</TD>

	<TD>
		<A HREF="os_participants_show.php?experiment_id='.$experiment['experiment_id'].'&focus=finished&free_reg=y">
		<survey::count-finished '.$experiment['experiment_id'].' reg="y">
		</A>
	</TD>
	<TD>
		<A HREF="os_participants_show.php?experiment_id='.$experiment['experiment_id'].'&focus=dataform">
		<survey::count-dataform '.$experiment['experiment_id'].'>
		</A>
	</TD>
	<TD>
		<A HREF="os_participants_show.php?experiment_id='.$experiment['experiment_id'].'&focus=to_subpool">
		<survey::count-joined-subpool '.$experiment['experiment_id'].'>
		</A>
	</TD>
</TR>


<TR><TD bgcolor="'.$color['list_options_background'].'" colspan=7>
'.$lang['options'].':<BR><BR></TD></TR>
<TR>
<TD colspan=3>
<A HREF="experiment_add_participants.php?experiment_id='.$experiment['experiment_id'].'">'.$lang['assign_subjects'].'</A>
<BR></TD>
<TD colspan=4>
<A HREF="experiment_drop_participants.php?experiment_id='.$experiment['experiment_id'].'">'.$lang['delete_assigned_subjects'].'</A><BR>
</TD></TR>
<TR>
<TD colspan=3>
<A HREF="experiment_mail_participants.php?experiment_id='.$experiment['experiment_id'].'">'.$lang['send_invitations'].'</A>
<BR></TD>
<TD colspan=4>
</TD></TR>


</TABLE>
</center>
</when>


//////////////////////////////////////////;;
// results summary  and results options for online-survey experiments
////////////////////////////////////////;
<when <string-eq '.$experiment['experiment_type'].' "online-survey">>

<center>
<BR>
<table border=1 width=90%>
<TR>
<TD bgcolor="'.$color['list_header_background'].'" colspan=2><h3>Results</h3></TD>
</TR>
<TR>
<TD></TD>
<TD></TD>
</TR>

<TR><TD bgcolor="'.$color['list_options_background'].'" colspan=2>
'.$lang['options'].':<BR><BR></TD></TR>
<TR>
<TD>
<A HREF="os_results_excel_import.php?experiment_id='.$experiment['experiment_id'].'">'.$lang['download_decision_data'].'</A>
<BR></TD>
<TD>
<A HREF="os_participants_excel_import.php?experiment_id='.$experiment['experiment_id'].'">'.$lang['download_participant_data'].'</A><BR>
</TD></TR>
<TR>
<TD>
</TD>
<TD>
</TD></TR>


</TABLE>
</center>
</when>

*/

include ("footer.php");

?>
