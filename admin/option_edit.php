<?php
ob_start();

$title="edit settings";
include ("header.php");

	$allow=check_allow('settings_edit','options_main.php');

	if ($_REQUEST['otype']) $otype=$_REQUEST['otype']; else redirect ("admin/options_main.php");

	switch ($otype) {
		case "general":
				$header=$lang['edit_general_settings'];
				break;
                case "default":
                                $header=$lang['edit_default_values'];
                                break;
		}


	echo '<center>
		<BR><BR>

			<H4>'.$header.'</H4>
		<BR>';


		if ($_REQUEST['change']) {

			$newoptions=$_REQUEST['options'];
			foreach ($newoptions as $oname => $ovalue) {
				$query="UPDATE ".table('options')." 
					SET option_value='".mysql_escape_string($ovalue)."' 
					WHERE option_name='".$oname."'
					AND option_type='".$otype."'";
				$done=mysql_query($query) or die("Database error: " . mysql_error());
				}
			message($lang['changes_saved']);
			log__admin("options_edit","type:".$otype);
			redirect ('admin/option_edit.php?otype='.$otype);
			}

	$query="select * from ".table('options')."
                 where option_type='".$otype."'
                 order by option_name";
        $result=mysql_query($query) or die("Database error: " . mysql_error());

	$options=array();
        while ($line=mysql_fetch_assoc($result)) {
		$options[$line['option_name']]=$line['option_value'];
		}

	echo '
		<FORM action="option_edit.php" method=post>
		<INPUT type=hidden name="otype" value="'.$otype.'">
		<TABLE width=80% border=0>
			<TR>
				<TD colspan=2 align=center>
					<INPUT type=submit name="change" value="'.$lang['change'].'">
				</TD>
			</TR>';
		echo '  <TR><TD colspan=2><hr></TD></TR>';

	if ($otype=="general") {
		echo '  <TR>
				<TD>
					Administrator Standard Language
				</TD>
				<TD>
					';
					echo lang__select_lang("options[admin_standard_language]",
								$options['admin_standard_language'],"all");
					echo '
				</TD>
			</TR>';

		echo '  <TR>
                                <TD>
                                        Public Standard Language
                                </TD>
                                <TD>
                                        ';
                                        echo lang__select_lang("options[public_standard_language]",
                                                                $options['public_standard_language'],"part");
                                        echo '
                                </TD>
                        </TR>';
		echo '  <TR><TD colspan=2><hr></TD></TR>';

		echo '  <TR>
                                <TD>
                                        Exclusion Policy: Max. Number of No-Shows
                                </TD>
                                <TD>
					<INPUT type=text name="options[automatic_exclusion_noshows]" size=2 maxlength=2
						value="'.$options['automatic_exclusion_noshows'].'">
                                </TD>
                        </TR>';

                echo '  <TR>
                                <TD>
                                        Send warning email on no-show?
                                </TD>
                                <TD>
					<SELECT name="options[send_noshow_warnings]">
					<OPTION value="y"'; if ($options['send_noshow_warnings']=='y') echo ' SELECTED';
					echo '>'.$lang['y'].'</OPTION><OPTION value="n"';
					if ($options['send_noshow_warnings']!='y') echo ' SELECTED';
					echo '>'.$lang['n'].'</OPTION></SELECT>
                                </TD>
                        </TR>';

		echo '  <TR>
                                <TD>
                                        Automatically exclude participants after max no-shows?
                                </TD>
                                <TD>
                                        <SELECT name="options[automatic_exclusion]">
                                        <OPTION value="y"'; if ($options['automatic_exclusion']=='y') echo ' SELECTED';
                                        echo '>'.$lang['y'].'</OPTION><OPTION value="n"';
                                        if ($options['automatic_exclusion']!='y') echo ' SELECTED';
                                        echo '>'.$lang['n'].'</OPTION></SELECT>
                                </TD>
                        </TR>';

		echo '  <TR>
                                <TD>
                                        Inform excluded participants about automatic exclusion?
                                </TD>
                                <TD>
                                        <SELECT name="options[automatic_exclusion_inform]">
                                        <OPTION value="y"'; if ($options['automatic_exclusion_inform']=='y') 
					echo ' SELECTED'; echo '>'.$lang['y'].'</OPTION><OPTION value="n"';
                                        if ($options['automatic_exclusion_inform']!='y') echo ' SELECTED';
                                        echo '>'.$lang['n'].'</OPTION></SELECT>
                                </TD>
                        </TR>';

                echo '  <TR><TD colspan=2><hr></TD></TR>';

		echo '  <TR>
                                <TD>
                                        Allow restriction of experiment page access to experimenters?
                                </TD>
                                <TD>
                                        <SELECT name="options[allow_experiment_restriction]">
                                        <OPTION value="y"'; if ($options['allow_experiment_restriction']=='y')
                                        echo ' SELECTED'; echo '>'.$lang['y'].'</OPTION><OPTION value="n"';
                                        if ($options['allow_experiment_restriction']!='y') echo ' SELECTED';
                                        echo '>'.$lang['n'].'</OPTION></SELECT>
                                </TD>
                        </TR>';

                echo '  <TR><TD colspan=2><hr></TD></TR>';

		echo '  <TR>
                                <TD>
                                        First part of each page\'s title tag?
                                </TD>
                                <TD>
                                        <INPUT type=text name="options[default_area]" size=20 maxlength=30
                                                value="'.$options['default_area'].'">
                                </TD>
                        </TR>';

		echo '  <TR><TD colspan=2><hr></TD></TR>';

		echo '  <TR>
                                <TD>
                                        Type of sending emails (see Manual,<BR>
					try "direct", and if it doesn\'t work, try "indirect")?
                                </TD>
                                <TD>
                                        <SELECT name="options[email_sendmail_type]">
                                        <OPTION value="direct"'; if ($options['email_sendmail_type']=='direct')
                                        echo ' SELECTED'; echo '>direct</OPTION><OPTION value="indirect"';
                                        if ($options['email_sendmail_type']!='direct') echo ' SELECTED';
                                        echo '>indirect</OPTION></SELECT>
                                </TD>
                        </TR>';

		echo '  <TR>
                                <TD>
                                        If indirect: path to sendmail program/wrapper?
                                </TD>
                                <TD>
                                        <INPUT type=text name="options[email_sendmail_path]" size=30 maxlength=200
                                                value="'.$options['email_sendmail_path'].'">
                                </TD>
                        </TR>';

		echo '  <TR>
                                <TD>
                                        Number of mails send from mail queue on each processing:
                                </TD>
                                <TD>
                                        <INPUT type=text name="options[mail_queue_number_send_per_time]" size=3 maxlength=5
                                                value="'.$options['mail_queue_number_send_per_time'].'">
                                </TD>
                        </TR>';

		echo '  <TR><TD colspan=2><hr></TD></TR>';


		echo '  <TR>
                                <TD>
                                        Path to server log file (access.log)?
                                </TD>
                                <TD>
                                        <INPUT type=text name="options[http_log_file_location]" size=30 maxlength=200
                                                value="'.$options['http_log_file_location'].'">
                                </TD>
                        </TR>';

                echo '  <TR><TD colspan=2><hr></TD></TR>';

		echo '  <TR>
                                <TD>
                                        Style for Public Area
                                </TD>
                                <TD>
                                        ';
					$styles=get_style_array();
					echo '<SELECT name="options[orsee_public_style]">';
					foreach ($styles as $style) {
                                        	echo '<OPTION value="'.$style.'"'; 
						if ($options['orsee_public_style']==$style) echo ' SELECTED';
						echo '>'.$style.'</OPTION>';
						}
                                        echo '</SELECT>
                                </TD>
                        </TR>';


		echo '  <TR>
                                <TD>
                                        Style for Administration Area
                                </TD>
                                <TD>
                                        ';
                                        $styles=get_style_array();
                                        echo '<SELECT name="options[orsee_admin_style]">';
                                        foreach ($styles as $style) {
                                                echo '<OPTION value="'.$style.'"';
                                                if ($options['orsee_admin_style']==$style) echo ' SELECTED';
                                                echo '>'.$style.'</OPTION>';
                                                }
                                        echo '</SELECT>
                                </TD>
                        </TR>';

		echo '  <TR><TD colspan=2><hr></TD></TR>';

		echo '  <TR>
                                <TD>
                                        Installed GD Version (needed for graphs,<BR>
                                        try \'php -i | grep -i "GD Version"\' to find out)?
                                </TD>
                                <TD>
                                        <SELECT name="options[stats_plots_gd_version]">
                                        <OPTION value="1"'; if ($options['stats_plots_gd_version']=='1')
                                        echo ' SELECTED'; echo '>&lt;2.0</OPTION><OPTION value="2"';
                                        if ($options['stats_plots_gd_version']!='1') echo ' SELECTED';
                                        echo '>&gt;=2.0</OPTION></SELECT>
                                </TD>
                        </TR>';

		echo '  <TR><TD colspan=2><hr></TD></TR>';

                echo '  <TR>
                                <TD>
                                        Stop public site (might be useful if installing/upgrading)?
                                </TD>
                                <TD>
                                        <SELECT name="options[stop_public_site]">
                                        <OPTION value="y"'; if ($options['stop_public_site']=='y')
                                        echo ' SELECTED'; echo '>'.$lang['y'].'</OPTION><OPTION value="n"';
                                        if ($options['stop_public_site']!='y') echo ' SELECTED';
                                        echo '>'.$lang['n'].'</OPTION></SELECT>
                                </TD>
                        </TR>';

		echo '  <TR><TD colspan=2><hr></TD></TR>';

		echo '  <TR>
                                <TD>
                                        Default registration subject pool?
                                </TD>
                                <TD>';
					subpools__select_field("options[subpool_default_registration_id]","subpool_id",
							"subpool_name",$options['subpool_default_registration_id']);
					echo '
                                </TD>
                        </TR>';

                echo '  <TR><TD colspan=2><hr></TD></TR>';

                echo '  <TR>
                                <TD>
                                        System support email address (used as sender for most emails)?
                                </TD>
                                <TD>
                                        <INPUT type=text name="options[support_mail]" size=30 maxlength=200
                                                value="'.$options['support_mail'].'">
                                </TD>
                        </TR>';

		echo '  <TR><TD colspan=2><hr></TD></TR>';

                echo '  <TR>
                                <TD>
                                        Upload categories (separated by commata, should be defined as language symbols)?
                                </TD>
                                <TD>
                                        <INPUT type=text name="options[upload_categories]" size=40 maxlength=200
                                                value="'.$options['upload_categories'].'">
                                </TD>
                        </TR>';

		echo '  <TR>
                                <TD>
                                        Upload max size in bytes?
                                </TD>
                                <TD>
                                        <INPUT type=text name="options[upload_max_size]" size=10 maxlength=20
                                                value="'.$options['upload_max_size'].'">
                                </TD>
                        </TR>';

		echo '  <TR><TD colspan=2><hr></TD></TR>';

		echo '  <TR>
                                <TD>
                                        Participant calendar: Hide public experiment name?
                                </TD>
                                <TD>
					<SELECT name="options[public_calendar_hide_exp_name]">
                                        <OPTION value="y"'; if ($options['public_calendar_hide_exp_name']=='y')
                                        echo ' SELECTED'; echo '>'.$lang['y'].'</OPTION><OPTION value="n"';
                                        if ($options['public_calendar_hide_exp_name']!='y') echo ' SELECTED';
                                        echo '>'.$lang['n'].'</OPTION></SELECT>
                                </TD>
                        </TR>';


                echo '  <TR><TD colspan=2><hr></TD></TR>';

	        echo '  <TR>
                                <TD>
                                        Emailed PDF Calendar: Number of months included?
                                </TD>
                                <TD>
                                        '; helpers__select_numbers("options[emailed_calendar_included_months]",
                                                                $options['emailed_calendar_included_months'],1,12,0,1);
                                                echo '
                                </TD>
                        </TR>';

		echo '  <TR><TD colspan=2><hr></TD></TR>';

	} else {

		echo '  <TR>
                                <TD>
                                        Default administrator type?
                                </TD>
                                <TD>
					'; admin__select_admin_type("options[default_admin_type]",
						$options['default_admin_type']); echo'
                                </TD>
                        </TR>';

		echo '  <TR><TD colspan=2><hr></TD></TR>';

		echo '  <TR>
                                <TD>
                                        Participant statistics: list 
                                </TD>
                                <TD>
                                        <SELECT name="options[stats_type]">
                                        <OPTION value="text"'; if ($options['stats_type']=='text') echo ' SELECTED'; 
						echo '>Text</OPTION>
					<OPTION value="html"'; if ($options['stats_type']=='html') echo ' SELECTED'; 
                                                echo '>HTML</OPTION>
					<OPTION value="graphs"'; if ($options['stats_type']=='graphs') echo ' SELECTED'; 
                                                echo '>Graphics</OPTION>
					<OPTION value="both"'; if ($options['stats_type']=='both') echo ' SELECTED'; 
                                                echo '>HTML+Graphics</OPTION>
                                </TD>
                        </TR>';

		echo '  <TR><TD colspan=2><hr></TD></TR>';

		echo '  <TR>
                                <TD>
                                        Log files: entries per page?
                                </TD>
                                <TD>
                                        '; helpers__select_numbers("options[stats_logs_results_per_page]",
								$options['stats_logs_results_per_page'],10,200,0,10);
                                                echo '
                                </TD>
                        </TR>';

		echo '  <TR><TD colspan=2><hr></TD></TR>';

		echo '  <TR>
                                <TD>
                                        PDF Calendar: title font size?
                                </TD>
                                <TD>
                                        '; helpers__select_numbers("options[calendar_pdf_title_fontsize]",
                                                                $options['calendar_pdf_title_fontsize'],6,25,0,1);
                                                echo '
                                </TD>
                        </TR>';

		echo '  <TR>
                                <TD>
                                        PDF Calendar: table entry font size?
                                </TD>
                                <TD>
                                        '; helpers__select_numbers("options[calendar_pdf_table_fontsize]",
                                                                $options['calendar_pdf_table_fontsize'],6,25,0,1);
                                                echo '
                                </TD>
                        </TR>';

		echo '  <TR>
                                <TD>
                                        PDF Participant list: title font size?
                                </TD>
                                <TD>
                                        '; helpers__select_numbers("options[participant_list_pdf_title_fontsize]",
                                                                $options['participant_list_pdf_title_fontsize'],6,25,0,1);
                                                echo '
                                </TD>
                        </TR>';

		echo '  <TR>
                                <TD>
                                        PDF Participant list: table entry font size?
                                </TD>
                                <TD>
                                        '; helpers__select_numbers("options[participant_list_pdf_table_fontsize]",
                                                                $options['participant_list_pdf_table_fontsize'],6,25,0,1);
                                                echo '
                                </TD>
                        </TR>';

                echo '  <TR><TD colspan=2><hr></TD></TR>';

		echo '  <TR>
                                <TD>
                                        List of experimenters: number of columns?
                                </TD>
                                <TD>
                                        '; helpers__select_numbers("options[experimenter_list_nr_columns]",
                                                                $options['experimenter_list_nr_columns'],1,6,0,1);
                                                echo '
                                </TD>
                        </TR>';

                echo '  <TR><TD colspan=2><hr></TD></TR>';

		echo '  <TR>
                                <TD>
                                        Participant query: list of experiment classes: number of columns?
                                </TD>
                                <TD>
                                        '; helpers__select_numbers("options[query_experiment_classes_list_nr_columns]",
                                                            $options['query_experiment_classes_list_nr_columns'],1,6,0,1);
                                                echo '
                                </TD>
                        </TR>';

		echo '  <TR>
                                <TD>
                                        Participant query: list of old experiments: number of columns?
                                </TD>
                                <TD>
                                        '; helpers__select_numbers("options[query_experiment_list_nr_columns]",
                                                                $options['query_experiment_list_nr_columns'],1,6,0,1);
                                                echo '
                                </TD>
                        </TR>';
       

		echo '  <TR>
                                <TD>
                                        Participant query: number of old experiments to show in limited view?
                                </TD>
                                <TD>
                                        '; helpers__select_numbers("options[query_number_exp_limited_view]",
                                                                $options['query_number_exp_limited_view'],5,100,0,5);
                                                echo '
                                </TD>
                        </TR>';

		echo '  <TR>
                                <TD>
                                        Participant query: default size of random subset?
                                </TD>
                                <TD>
                                        '; helpers__select_numbers("options[query_random_subset_default_size]",
                                                                $options['query_random_subset_default_size'],50,1000,0,50);
                                                echo '
                                </TD>
                        </TR>';

                echo '  <TR><TD colspan=2><hr></TD></TR>';

		echo '  <TR>
                                <TD>
                                        Laboratory: opening time?
                                </TD>
                                <TD>
                                        '; helpers__select_numbers("options[laboratory_opening_time_hour]",
                                                                $options['laboratory_opening_time_hour'],0,23,2,1);
                                        echo ':'; 
					helpers__select_numbers("options[laboratory_opening_time_minute]",
                                                                $options['laboratory_opening_time_minute'],0,59,2,15);
					echo '
                                </TD>
                        </TR>';

		echo '  <TR>
                                <TD>
                                        Laboratory: closing time?
                                </TD>
                                <TD>
                                        '; helpers__select_numbers("options[laboratory_closing_time_hour]",
                                                                $options['laboratory_closing_time_hour'],0,23,2,1);
                                        echo ':';
                                        helpers__select_numbers("options[laboratory_closing_time_minute]",
                                                                $options['laboratory_closing_time_minute'],0,59,2,15);
                                        echo '
                                </TD>
                        </TR>';

		echo '  <TR><TD colspan=2><hr></TD></TR>';

		echo '  <TR>
                                <TD>
                                        Laboratory: max number of participants?
                                </TD>
                                <TD>
                                        <INPUT type=text name="options[lab_participants_max]" size=3 
						maxlength=4 value="'.$options['lab_participants_max'].'">
                                </TD>
                        </TR>';

		echo '  <TR>
                                <TD>
                                        Laboratory/Experiment Session: default number of participants?
                                </TD>
                                <TD>
                                        '; helpers__select_numbers("options[lab_participants_default]",
                                                                $options['lab_participants_default'],1,
								$options['lab_participants_max'],0,1);
                                                echo '
                                </TD>
                        </TR>';

                echo '  <TR>
                                <TD>
                                        Experiment session: max number of reserve participants?
                                </TD>
                                <TD>
                                        <INPUT type=text name="options[reserve_participants_max]" size=3
                                                maxlength=4 value="'.$options['reserve_participants_max'].'">
                                </TD>
                        </TR>';

                echo '  <TR>
                                <TD>
                                        Experiment Session: default number of reserve participants?
                                </TD>
                                <TD>
                                        '; helpers__select_numbers("options[reserve_participants_default]",
                                                                $options['reserve_participants_default'],1,
                                                                $options['reserve_participants_max'],0,1);
                                                echo '
                                </TD>
                        </TR>';

		echo '  <TR><TD colspan=2><hr></TD></TR>';

		echo '  <TR>
                                <TD>
                                        Experiment Session: max duration in hours?
                                </TD>
                                <TD>
                                        '; helpers__select_numbers("options[session_duration_hour_max]",
                                                                $options['session_duration_hour_max'],1,24,0,1);
                                                echo '
                                </TD>
                        </TR>';

		echo '  <TR>
                                <TD>
                                        Experiment Session: duration minute steps?
                                </TD>
                                <TD>
                                        <SELECT name="options[session_duration_minute_steps]">';
					$steps=array(5,10,15,20,30);
					foreach ($steps as $step) {
						echo '<OPTION value="'.$step.'"';
                                                if ($step==$options['session_duration_minute_steps']) echo ' SELECTED';
						echo '>'.$step.'</OPTION>';
						}
                                        echo '</SELECT>
                                </TD>
                        </TR>';

		echo '  <TR>
                                <TD>
                                        Experiment session: default duration?
                                </TD>
                                <TD>
                                        '; helpers__select_numbers("options[session_duration_hour_default]",
                                                                $options['session_duration_hour_default'],0,
								$options['session_duration_hour_max'],0,1);
                                        echo ':';
                                        helpers__select_numbers("options[session_duration_minute_default]",
                                                                $options['session_duration_minute_default'],0,59,2,
								$options['session_duration_minute_steps']);
                                        echo '
                                </TD>
                        </TR>';
		
		echo '  <TR><TD colspan=2><hr></TD></TR>';

		echo '  <TR>
                                <TD>
                                        Experiment Session: registration end: max hours before session?
                                </TD>
                                <TD>
					<INPUT type=text name="options[session_registration_end_hours_max]" size=3
                                                maxlength=3 value="'.$options['session_registration_end_hours_max'].'">
                                </TD>
                        </TR>';

		echo '  <TR>
                                <TD>
                                        Experiment Session: registration end: steps for hours before session?
                                </TD>
                                <TD>
                                        <INPUT type=text name="options[session_registration_end_hours_steps]" size=3
                                                maxlength=3 value="'.$options['session_registration_end_hours_steps'].'">
                                </TD>
                        </TR>';

		echo '  <TR>
                                <TD>
                                        Experiment Session: registration end: default hours before session?
                                </TD>
                                <TD>
                                        '; helpers__select_numbers("options[session_registration_end_hours_default]",
                                                                $options['session_registration_end_hours_default'],0,
                                                                $options['session_registration_end_hours_max'],0,
								$options['session_registration_end_hours_steps']);
                                                echo '
                                </TD>
                        </TR>';

		echo '  <TR><TD colspan=2><hr></TD></TR>';

		echo '  <TR>
                                <TD>
                                        Experiment Session: default for "send session reminder on"-condition?
                                </TD>
                                <TD>';
					$oparray=array('enough_participants_needed_plus_reserve',
                                                	'enough_participants_needed',
                                                	'in_any_case_dont_ask');
                                	helpers__select_text($oparray,"options[session_reminder_send_on_default]",
							$options['session_reminder_send_on_default']);
					echo '
                                </TD>
                        </TR>';

                echo '  <TR>
                                <TD>
                                        Experiment Session: send session reminder email: max hours before session?
                                </TD>
                                <TD>
                                        <INPUT type=text name="options[session_reminder_hours_max]" size=3
                                                maxlength=3 value="'.$options['session_reminder_hours_max'].'">
                                </TD>
                        </TR>';

                echo '  <TR>
                                <TD>
                                        Experiment Session: send session reminder email: steps for hours before session?
                                </TD>
                                <TD>
                                        <INPUT type=text name="options[session_reminder_hours_steps]" size=3
                                                maxlength=3 value="'.$options['session_reminder_hours_steps'].'">
                                </TD>
                        </TR>';

                echo '  <TR>
                                <TD>
                                        Experiment Session: send session reminder email: default hours before session?
                                </TD>
                                <TD>
                                        '; helpers__select_numbers("options[session_reminder_hours_default]",
                                                                $options['session_reminder_hours_default'],0,
                                                                $options['session_reminder_hours_max'],0,
                                                                $options['session_reminder_hours_steps']);
                                                echo '
                                </TD>
                        </TR>';	
		
		echo '  <TR><TD colspan=2><hr></TD></TR>';

		echo '  <TR>
                                <TD>
                                        Experiment Session: "session start"-field: years backward?
                                </TD>
                                <TD>
                                        '; helpers__select_numbers("options[session_start_years_backward]",
                                                                $options['session_start_years_backward'],0,20,0,1);
                                                echo '
                                </TD>
                        </TR>';

		echo '  <TR>
                                <TD>
                                        Experiment Session: "session start"-field: years forward?
                                </TD>
                                <TD>
                                        '; helpers__select_numbers("options[session_start_years_forward]",
                                                                $options['session_start_years_forward'],0,20,0,1);
                                                echo '
                                </TD>
                        </TR>';

		echo '  <TR><TD colspan=2><hr></TD></TR>';

		echo '  <TR>
                                <TD>
                                        Participant form: "begin of studies"-field: years backward?
                                </TD>
                                <TD>
                                        '; helpers__select_numbers("options[begin_of_studies_years_backward]",
                                                                $options['begin_of_studies_years_backward'],0,20,0,1);
                                                echo '
                                </TD>
                        </TR>';

                echo '  <TR>
                                <TD>
                                        Participant form: "begin of studies"-field: years forward?
                                </TD>
                                <TD>
                                        '; helpers__select_numbers("options[begin_of_studies_years_forward]",
                                                                $options['begin_of_studies_years_forward'],0,20,0,1);
                                                echo '
                                </TD>
                        </TR>';

                echo '  <TR><TD colspan=2><hr></TD></TR>';

	}

	/* complete list as text inputs ...

	if ($otype!="general") {

	$query="select * from ".table('options')."
                 where option_type='".$otype."'
                 order by option_name";
        $result=mysql_query($query) or die("Database error: " . mysql_error());

	while ($line=mysql_fetch_assoc($result)) {

		echo '	<TR>
				<TD>
					'.$line['option_name'].'
				</TD>
				<TD>
					<input type=text name="options['.$line['option_name'].
						']" size=40 maxlength=250 value="'.
						trim(stripslashes($line['option_value'])).'">
				</TD>
			</TR>
			';
		}

	}

	*/

	echo '		<TR>
				<TD colspan=2 align=center>
					<INPUT type=submit name="change" value="'.$lang['change'].'">
				</TD>
			</TR>
		</TABLE>
		</FORM>';

	echo '</center>';

include ("footer.php");

?>
