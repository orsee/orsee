<?php
ob_start();

$title="reserve laboratory space";
include ("header.php");
              
	if ($_REQUEST['space_id']) $space_id=$_REQUEST['space_id'];

	$allow=check_allow('lab_space_edit','calendar_main.php');

	if ($space_id)
		$edit=orsee_db_load_array("lab_space",$space_id,"space_id");

	if ($_REQUEST['edit']) { 

   		$edit=$_REQUEST;
		$continue=true;

		$start_string=$edit['space_start_year']*100000000+$edit['space_start_month']*1000000+$edit['space_start_day']*10000+
				$edit['space_start_hour']*100+$edit['space_start_minute'];
		$stop_string=$edit['space_stop_year']*100000000+$edit['space_stop_month']*1000000+$edit['space_stop_day']*10000+
                                $edit['space_stop_hour']*100+$edit['space_stop_minute'];

		if ($start_string>=$stop_string) {
			message($lang['start_time_must_be_earlier_than_stop_time']);
			$continue=false;
			}


		if ($continue) {

			$done=orsee_db_save_array($edit,"lab_space",$edit['space_id'],"space_id"); 
			
			if ($done) {
				log__admin("lab_space_edit","space_id:".$space_id);
       				message ($lang['changes_saved']);
				redirect ('admin/lab_space_edit.php?space_id='.$edit['space_id']);
				}
		   	else {
   				$lang['database_error'];
				redirect ('admin/lab_space_edit.php?space_id='.$edit['space_id']);
				}
			}
		}

// form

        if (!$space_id) {
		$addit=true;

                $edit['space_id']=time();
		
		$edit['space_start_day']=date("d");
		$edit['space_start_month']=date("m");
		$edit['space_start_year']=date("Y");
		$edit['space_start_hour']=date("H");
		$edit['space_start_minute']=date("i");

		$edit['space_stop_day']=date("d");
                $edit['space_stop_month']=date("m");
                $edit['space_stop_year']=date("Y");
                $edit['space_stop_hour']=date("H")+1;
                $edit['space_stop_minute']=date("i");


		$edit['experimenter']=$expadmindata['adminname'];

		$button_name=$lang['add'];
                }
	   else {
		session__check_lab_time_clash($edit);

		$button_name=$lang['change'];

		}

	echo '<BR><BR><center><h4>'.$lang['reserve_lab_space'].'</h4>';

	show_message();

        echo '<BR>'.$lang['for_session_time_reservation_please_use_experiments'].'<BR>'.
             $lang['this_reservation_type_is_for_maintenence_purposes'];

	echo '<FORM action="lab_space_edit.php">
		<INPUT type=hidden name=space_id value="'.$edit['space_id'].'">
		'; if ($addit) echo '<INPUT type=hidden name="addit" value="true">'; echo '
		<TABLE>
		<TR>
			<TD>
				'.$lang['id'].':
			</TD>
			<TD>
				'.$edit['space_id'].'
			</TD>
		</TR>';

	echo '  <TR>
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
				'.$lang['start_date_and_time'].':
			</TD>
			<TD>';
				$year_start=$edit['space_start_year']-$settings['session_start_years_backward'];
				$year_stop=$edit['space_start_year']+$settings['session_start_years_forward'];

				helpers__select_numbers("space_start_day",$edit['space_start_day'],1,31,2);
				echo '.';
			   	helpers__select_numbers("space_start_month",$edit['space_start_month'],
								1,12,2);
				echo '.';
				helpers__select_numbers("space_start_year",$edit['space_start_year'],
								$year_start,$year_stop,4);
				echo '&nbsp;&nbsp;';

				helpers__select_numbers("space_start_hour",$edit['space_start_hour'],0,23,2);
				echo ':';
				helpers__select_numbers("space_start_minute",$edit['space_start_minute'],
								0,59,2,$settings['session_duration_minute_steps']);

				echo'
			</TD>
		</TR>';

        echo '  <TR>
                        <TD>
                                '.$lang['stop_date_and_time'].':
                        </TD>
                        <TD>';
                                $year_start=$edit['space_stop_year']-$settings['session_start_years_backward'];
                                $year_stop=$edit['space_stop_year']+$settings['session_start_years_forward'];

                                helpers__select_numbers("space_stop_day",$edit['space_stop_day'],1,31,2);
                                echo '.';
                                helpers__select_numbers("space_stop_month",$edit['space_stop_month'],
                                                             	1,12,2);
                                echo '.';
                                helpers__select_numbers("space_stop_year",$edit['space_stop_year'],
                                                                $year_start,$year_stop,4);
                                echo '&nbsp;&nbsp;';

                                helpers__select_numbers("space_stop_hour",$edit['space_stop_hour'],0,23,2);
                                echo ':';
                                helpers__select_numbers("space_stop_minute",$edit['space_stop_minute'],
                                                                0,59,2,$settings['session_duration_minute_steps']);

                                echo'
                        </TD>
                </TR>';

        echo '  <TR>
                        <TD>
                                '.$lang['experimenter'].':
                        </TD>
                        <TD>
                                <INPUT type=text name="experimenter" size=40 maxlength=200 value="'.$edit['experimenter'].'">
                        </TD>
                </TR>';


	echo '	<TR>
			<TD>
				'.$lang['description'].':
			</TD>
			<TD>
				<INPUT type=text name="reason" size=40 maxlength=200 value="'.$edit['reason'].'">
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


	if ($space_id && check_allow('lab_space_delete')) {
		echo '<FORM action="lab_space_delete.php">
			<INPUT type=hidden name=space_id value="'.$edit['space_id'].'">
			<table>
				<TR>
					<TD>
						<INPUT type=submit name=submit value="'.$lang['delete'].'">
					</TD>
				</TR>
			</table>
			</form>';
		}


include ("footer.php");

?>
