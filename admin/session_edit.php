<?php
ob_start();

$title="edit session";
include ("header.php");
              
	echo '<center><h4>'.$lang['edit_session'].'</h4></center>';

	if ($_REQUEST['session_id']) $session_id=$_REQUEST['session_id'];

	if ($session_id) {
		$edit=orsee_db_load_array("sessions",$session_id,"session_id");
		$allow=check_allow('session_edit','experiment_show.php?experiment_id='.$edit['experiment_id']);
		if (!check_allow('experiment_restriction_override'))
			check_experiment_allowed($edit['experiment_id'],"admin/experiment_show.php?experiment_id=".$edit['experiment_id']);
		}

	if ($_REQUEST['experiment_id']) {
		$allow=check_allow('session_edit','experiment_show.php?experiment_id='.$_REQUEST['experiment_id']);
	   if (!check_allow('experiment_restriction_override'))
		check_experiment_allowed($_REQUEST['experiment_id'],"admin/experiment_show.php?experiment_id=".$_REQUEST['experiment_id']);
		}

	if ($_REQUEST['edit']) { 

		if (!$_REQUEST['session_finished']) $_REQUEST['session_finished']="n";

		$oldtime=time__load_session_time($edit);
		$oldunixtime=time__time_package_to_unixtime($oldtime);

		$newtime=time__load_session_time($_REQUEST);
		$newunixtime=time__time_package_to_unixtime($newtime);

		$registered=experiment__count_participate_at($edit['experiment_id'],$edit['session_id']);
		$time_changed=false;

		if ($oldunixtime != $newunixtime) { 
			$time_changed=true;
			if ($registered>0) {
				message ($lang['session_time_changed']);
				}
			} else $time_changed=false;

		if (!isset($_REQUEST['addit'])) {

	 		if ($_REQUEST['registration_end_hours']!=$edit['registration_end_hours'] || $time_changed) {
				$_REQUEST['reg_notice_sent']="n";
				message ($lang['reg_time_extended_but_notice_sent']);
				}

			if ( ($_REQUEST['session_reminder_hours']!=$edit['session_reminder_hours'] || $time_changed) && 
					$edit['session_reminder_sent']=="y")
				message ($lang['session_reminder_changed_but_notice_sent']);
			}

   		$edit=$_REQUEST;
		$edit['session_id_crypt']=unix_crypt($edit['session_id']); 

		$done=orsee_db_save_array($edit,"sessions",$edit['session_id'],"session_id"); 

		if ($done) {
			log__admin("session_edit","session:".session__build_name($edit,
				$settings['admin_standard_language'])."\nsession_id:".$edit['session_id']);
       			message ($lang['changes_saved']);
			redirect ('admin/session_edit.php?session_id='.$edit['session_id']);
			}
		   else {
   			$lang['database_error'];
			redirect ('admin/session_edit.php?session_id='.$edit['session_id']);
			}
		}

// form

        if (!$session_id) {
		$addit=true;

                $edit['experiment_id']=$_REQUEST['experiment_id'];
                $edit['session_id']=get_unique_id("sessions","session_id");
		
		$edit['session_start_day']=date("d");
		$edit['session_start_month']=date("m");
		$edit['session_start_year']=date("Y");
		$edit['session_start_hour']=date("H");
		$edit['session_start_minute']=date("i");

		$edit['session_duration_hour']=$settings['session_duration_hour_default'];
		$edit['session_duration_minute']=$settings['session__duration_minute_default'];

		$edit['session_reminder_hours']=$settings['session_reminder_hours_default'];
		$edit['send_reminder_on']=$settings['session_reminder_send_on_default'];
		$edit['registration_end_hours']=$settings['session_registration_end_hours_default'];
		$session_time=0;

		$edit['part_needed']=$settings['lab_participants_default'];
		$edit['part_reserve']=$settings['reserve_participants_default'];

		$button_name=$lang['add'];
                }
	   else {
		$sessiontimearray=time__load_session_time($edit);
                $session_time=time__time_package_to_unixtime($sessiontimearray);

		$button_name=$lang['change'];

		session__check_lab_time_clash($edit);
		}

	echo '<CENTER>';
	show_message();

	echo '<FORM action="session_edit.php">
		<INPUT type=hidden name=session_id value="'.$edit['session_id'].'">
		<INPUT type=hidden name=experiment_id value="'.$edit['experiment_id'].'">
		'; if ($addit) echo '<INPUT type=hidden name="addit" value="true">'; echo '
		<TABLE>
		<TR>
			<TD>
				'.$lang['id'].':
			</TD>
			<TD>
				'.$edit['session_id'].'
			</TD>
		</TR>';

	echo '	<TR>
			<TD>
				'.$lang['date'].':
			</TD>
			<TD>';
				$year_start=$edit['session_start_year']-$settings['session_start_years_backward'];
				$year_stop=$edit['session_start_year']+$settings['session_start_years_forward'];

				helpers__select_numbers("session_start_day",$edit['session_start_day'],1,31,2);
				echo '.';
			   	helpers__select_numbers("session_start_month",$edit['session_start_month'],
								1,12,2);
				echo '.';
				helpers__select_numbers("session_start_year",$edit['session_start_year'],
								$year_start,$year_stop,4);
				echo ' 
			</TD>
		</TR>';

	echo '	<TR>
			<TD>
				'.$lang['time'].':
			</TD>
			<TD>';
				helpers__select_numbers("session_start_hour",$edit['session_start_hour'],0,23,2);
				echo ':';
				helpers__select_numbers("session_start_minute",$edit['session_start_minute'],
								0,59,2,$settings['session_duration_minute_steps']);

				echo'
			</TD>
		</TR>';

	echo '	<TR>
			<TD>
				'.$lang['laboratory'].':
			</TD>
			<TD>';
				laboratories__select_field("laboratory_id",$edit['laboratory_id']);
				echo '
			</TD>
		</TR>';


	echo '	<TR>
			<TD>
				'.$lang['experiment_duration'].':
			</TD>
			<TD>';
				helpers__select_numbers("session_duration_hour",$edit['session_duration_hour'],
								0,$settings['session_duration_hour_max'],2,1);
				echo ':';
				helpers__select_numbers("session_duration_minute",
						$edit['session_duration_minute'],0,59,2,
						$settings['session_duration_minute_steps']);
				echo ' '.help(session_duration).'
			</TD>
		</TR>';

	echo ' <TR>
			<TD>
				'.$lang['session_reminder_hours_before'].':
			</TD>
			<TD>';
				if ($edit['session_reminder_sent']=="y") 
					echo $edit['session_reminder_hours'].' ('.$lang['session_reminder_already_sent'].')';
				   else 
					helpers__select_numbers_relative("session_reminder_hours",$edit['session_reminder_hours'],0,
							 $settings['session_reminder_hours_max'],2,$settings['session_reminder_hours_steps'],
							 $session_time);
				echo ' '.help("session_reminder").'
			</TD>
		</TR>';

	echo ' <TR>
			<TD>
				'.$lang['send_reminder_on'].'
			</TD>
			<TD>';
				$oparray=array('enough_participants_needed_plus_reserve',
						'enough_participants_needed',
						'in_any_case_dont_ask');
				helpers__select_text($oparray,"send_reminder_on",$edit['send_reminder_on']);
				echo '
			</TD>
		</TR>';	

	echo '	<TR>
			<TD>
				'.$lang['needed_participants'].':
			</TD>
			<TD>';
				helpers__select_numbers("part_needed",$edit['part_needed'],0,$settings['lab_participants_max']); 
				echo ' '.help("needed_participants").'
			</TD>
		</TR>';

	echo '	<TR>
			<TD>
				'.$lang['reserve_participants'].':
			</TD>
			<TD>';
				helpers__select_numbers("part_reserve",$edit['part_reserve'],0,$settings['reserve_participants_max']);
				echo ' '.help("reserve_participants").'
			</TD>
		</TR>';

	echo '	<TR>
			<TD>
				'.$lang['registration_end_hours_before'].':
			</TD>
			<TD>';
				helpers__select_numbers_relative("registration_end_hours",$edit['registration_end_hours'],0,
					$settings['session_registration_end_hours_max'],2,
					$settings['session_registration_end_hours_steps'],$session_time);
				echo ' '.help("registration_end").'
			</TD>
		</TR>';

	echo '	<TR>
			<TD>
				'.$lang['remarks'].':
			</TD>
			<TD>
				<textarea name="session_remarks" rows=5 cols=30 wrap=virtual>'.$edit['session_remarks'].'</textarea>
			</TD>
		</TR>';

	echo '	<TR>
			<TD>
				'.$lang['session_finished?'].'
			</TD>
			<TD>
				<input name="session_finished" type=checkbox value="y"';
				if ($edit['session_finished']=="y") echo ' CHECKED';
				echo '> '.help("session_finished").'
			</TD>
		</TR>';

	echo '</TABLE>
 	      <TABLE>

		<TR>
			<TD COLSPAN=2>
				<INPUT name=edit type=submit value="'.$button_name.'">
			</TD>
		</TR>
	      </table>
	</FORM>
	<BR>';


	if ($session_id) {
		$reg=experiment__count_participate_at($edit['experiment_id'],$session_id);

		if (($reg==0 && check_allow('session_empty_delete')) || check_allow('session_nonempty_delete')) 
			echo '<FORM action="session_delete.php">
				<INPUT type=hidden name=session_id value="'.$edit['session_id'].'">
				<table>
					<TR>
						<TD>
							<INPUT type=submit name=submit value="'.$lang['delete'].'">
						</TD>
					</TR>
				</table>
				</form>';
		}

	if ($session_id) $experiment_id=$edit['experiment_id']; else $experiment_id=$_REQUEST['experiment_id'];
	echo '<BR><BR>
		<A HREF="experiment_show.php?experiment_id='.$experiment_id.'">'.$lang['mainpage_of_this_experiment'].'</A>
		</center>';


include ("footer.php");

?>
