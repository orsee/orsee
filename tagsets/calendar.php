<?php
// calendar functions. part of orsee. see orsee.org


function date__skip_months($count,$time=0) {
	if ($time==0) $time=time();
	$td=getdate($time);

	$tmonth=$td['mon']+$count;
	$newtimestamp=mktime(0,0,1,$tmonth,1,$td['year']);
	return $newtimestamp;
}

function date__skip_years($count,$time=0) {
	if ($time==0) $time=time();
	$td=getdate($time);

	$newyear=$td['year']+$count;
	$newtimestamp=mktime(0,0,1,1,1,$newyear);
	return $newtimestamp;
}

function date__year_start_time($time=0) {
        if ($time==0) $time=time();
        $td=getdate($time);
        $newtimestamp=mktime(0,0,1,1,1,$td['year']);
        return $newtimestamp;
}

function calendar__remember_sessions($alist) {
  global $calendar__session_data; 
  $calendar__session_data[]=$alist;
}

function calendar__days_in_month($month, $year)
{
// calculate number of days in a month
return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
}

function calendar__month_table_inner($time=0,$admin=false,$print=false) {
	if ($time==0) $time=time();

	global $lang, $color, $settings;
	static $expcolor_count=0, $expcolors=array(), $lscolor_count=0, $lscolors=array();

  	$start_date=date__skip_months(0,$time);
  	$date=getdate($start_date);
  	$limit=calendar__days_in_month($date['mon'],$date['year']);
  	$first_day=$date['wday'];
  	if ($first_day==0) $first_day=7; $first_day=$first_day-1;
  	$day="01";
  	$month=helpers__pad_number($date['mon'],2);
  	$year=$date['year'];
 	$i=0;
  	$j=0;
	$month_names=explode(",",$lang['month_names']);


	// prepare day array
	$days=array();
	for ($i=1; $i<=$limit; $i++) {
		$days[$i]=array();
		}

	// get session times
	if ($admin && isset($color['calendar_admin_experiment_sessions']))
                $expcolors_poss=explode(",",$color['calendar_admin_experiment_sessions']);
        elseif ((!$admin) && isset($color['calendar_public_experiment_sessions']))
                $expcolors_poss=explode(",",$color['calendar_public_experiment_sessions']);
        else
                $expcolors_poss=explode(",",$color['calendar_experiment_sessions']);

	$expcolors_max=count($expcolors_poss)-1;

	$query="SELECT * FROM ".table('sessions').", ".table('experiments').", ".table('lang')."
        	WHERE ".table('sessions').".experiment_id=".table('experiments').".experiment_id
        	AND ".table('sessions').".laboratory_id=".table('lang').".content_name
        	AND ".table('lang').".content_type='laboratory'";
	if (!$admin) $query.=" AND ".table('experiments').".hide_in_cal='n' ";
	$query.=" AND session_start_year='".$year."'
                 AND session_start_month='".$date['mon']."'
                 ORDER BY session_start_day, session_start_hour, session_start_minute";
	$result=mysql_query($query) or die("Database error: " . mysql_error());
	while ($line=mysql_fetch_assoc($result)) {
			$days[$line['session_start_day']][]=$line;
			if (!isset($expcolors[$line['experiment_id']])) {
				$expcolors[$line['experiment_id']]=$expcolors_poss[$expcolor_count];
				if ($expcolor_count==$expcolors_max) $expcolor_count=0; else $expcolor_count++;
				}
			}


        // get maintenance times
	$monthstring=$year.$month;
	$lscolors_poss=explode(",",$color['calendar_lab_space_reservation']);
	$lscolors_max=count($lscolors_poss)-1;

        $query="SELECT *, (space_start_year*100+space_start_month) as space_start,
			(space_stop_year*100+space_stop_month) as space_stop
		FROM ".table('lab_space').", ".table('lang')."
                WHERE ".table('lab_space').".laboratory_id=".table('lang').".content_name
                AND ".table('lang').".content_type='laboratory'
                HAVING space_start<='".$monthstring."'
                	AND space_stop>='".$monthstring."'
                ORDER BY space_start_day, space_start_hour, space_start_minute";
        $result=mysql_query($query) or die("Database error: " . mysql_error());
        while ($line=mysql_fetch_assoc($result)) {
		$maintstart = ($line['space_start']==$monthstring) ? $line['space_start_day'] : 1;
		$maintstop = ($line['space_stop']==$monthstring) ? $line['space_stop_day'] : $limit;
		if (!isset($lscolors[$line['space_id']])) {
			$lscolors[$line['space_id']]=$lscolors_poss[$lscolor_count];
			if ($lscolor_count==$lscolors_max) $lscolor_count=0; else $lscolor_count++;
			}
		for ($i=$maintstart; $i<=$maintstop; $i++) $days[$i][]=$line;
                }



	echo '
    		<tr>
      			<td bgcolor="'.$color['calendar_month_background'].'" colspan=7 align=center>
        			<font color="'.$color['calendar_month_font'].'"><b>'.
					$month_names[$date['mon']-1].' '.$year.
				'</b></font>
      			</td>
    		</tr>
    		<tr valign=top>';

	$calendar__weekdays=explode(",",$lang['weekdays_abbr']);
	foreach ($calendar__weekdays as $weekday) {
      		echo '<td align=right> <b>'.$weekday.'</b> </td>';
	}
	echo '</tr>
    		<tr valign=top>';
	$i=0;
	// write empty fields in days array
  	while ($i<$first_day) {
    		echo "<td>&nbsp;</td>";
    		$i++; $j++;
  		}


	// write day numbers
  	$i=0; $pointer=0;
  	while ($i<$limit) {
          	if (($j % 7)==0) {
           		echo "</tr>
                		<tr valign=top>";
           		}
        	$i++; $j++;
        	echo '<td height=50>
                	<table border=0 width=100%>
                	<TR><TD align=right bgcolor="'.$color['calendar_day_background'].'">';
        			echo helpers__pad_number($i,2);
        		echo '</TD></TR>';

        	foreach ($days[$i] as $entry) {
			if (isset($entry['session_start_day'])) {
				echo '<TR><TD bgcolor="'.$expcolors[$entry['experiment_id']].'">';
				$start_time=time__get_timepack_from_pack($entry,"session_start_");
				$duration=time__get_timepack_from_pack($entry,"session_duration_");
				$end_time=time__add_packages($start_time,$duration);

				echo time__format($lang['lang'],$start_time,true,false,true,true).'-'.
                                                time__format($lang['lang'],$end_time,true,false,true,true);

        			echo '<BR><FONT class="small">'.
        				laboratories__strip_lab_name(stripslashes($entry[$lang['lang']])).'</FONT>';
				if ($admin || (!isset($settings['public_calendar_hide_exp_name'])) ||
						$settings['public_calendar_hide_exp_name']!='y') {
					echo '<BR>';  
        				if ((!$print) && $admin && check_allow('experiment_show')) 
                        			echo '<A HREF="experiment_show.php?experiment_id='.
							$entry['experiment_id'].'">';
        				echo '<FONT color="'.$color['calendar_experiment_name'].'">';
        				if ($admin) echo $entry['experiment_name'];
                				else echo $entry['experiment_public_name'];
        				echo '</FONT>';
        				if ((!$print) && $admin && check_allow('experiment_show')) echo '</A>';
				}


        			if ($admin) echo '<BR><FONT class="small">'.$entry['experimenter'].'</FONT>';

        			$cs__reg=experiment__count_participate_at($entry['experiment_id'],$entry['session_id']);

        			if ($cs__reg<$entry['part_needed']) $cs__status="not_enough_participants";
        			elseif ($cs__reg<$entry['part_needed']+$entry['part_reserve']) $cs__status="not_enough_reserve";
        			else $cs__status="complete";

        			echo '<BR>';

        			if ($admin) {
        				$color_varname="session_".$cs__status;
        				echo '<FONT color="'.$color[$color_varname].'">'.$cs__reg.
						' ('.$entry['part_needed'].','.$entry['part_reserve'].')</FONT>';
        				}

        			$reg_end=sessions__get_registration_end($entry);

        			if (time()>$reg_end) $cs__status="complete";

        			if (!($admin)) {
        				switch ($cs__status) {
                				case "not_enough_participants":
                				case "not_enough_reserve":
                                                	$text=$lang['free_places'];
                                                	$thcolor=$color['session_public_free_places'];
                                                	break;
                				case "complete":
                                               		$text=$lang['complete'];
                                               		$thcolor=$color['session_public_complete'];
                                               		break;
        					}
        				echo '<FONT color="'.$thcolor.'">'.$text.'</FONT>';
        				}

        			if ((!$print) && $admin && check_allow('experiment_show_participants')) {
                			echo '<BR><A class="small" HREF="experiment_participants_show.php?experiment_id='.
                				$entry['experiment_id'].'&focus=registered&session_id='.$entry['session_id'].
                				'">['.$lang['participants'].']</A>';
                			}
        			echo '<BR><BR>
                			</TD></TR>';
				}
			else {
				if ($admin) {
					echo '<TR><TD bgcolor="'.$lscolors[$entry['space_id']].'">';
					if ($entry['space_start_day']==$i) {
						$from['hour']=$entry['space_start_hour'];
						$from['minute']=$entry['space_start_minute'];
						}
					   else {
						$from['hour']=$settings['laboratory_opening_time_hour'];
						$from['minute']=$settings['laboratory_opening_time_minute'];
						}
					if ($entry['space_stop_day']==$i) { 
                                                $to['hour']=$entry['space_stop_hour'];
                                                $to['minute']=$entry['space_stop_minute'];
						}
                                           else {
						$to['hour']=$settings['laboratory_closing_time_hour'];
                                                $to['minute']=$settings['laboratory_closing_time_minute'];
						}
					echo time__format($lang['lang'],$from,true,false,true,true).'-'.
						time__format($lang['lang'],$to,true,false,true,true);
					echo '<BR><FONT class="small">'.
                                        	laboratories__strip_lab_name(stripslashes($entry[$lang['lang']])).'</FONT><BR>';
					echo $entry['reason'].'<BR><FONT class="small">'.$entry['experimenter'].'</FONT><BR>';
					if (check_allow('lab_space_edit'))
						echo '<A HREF="lab_space_edit.php?space_id='.$entry['space_id'].'">'.
							'<FONT class="small" align=right>'.$lang['edit'].'</FONT></A><BR>';
					echo '<BR></TD></TR>';
					}

				}
        		$pointer++;
        		}
        	echo '</table>
                	</td>';
  		}

  	$i=$i+$first_day;
  	while (($i % 7)!=0) {
    		echo '<td>&nbsp;</td>';
    		$i++;
  		}
  	echo '</tr>';
}



function calendar__month_table($time=0,$forward=0,$admin=false,$print=false) {
	if ($time==0) $time=time();
	$i=0;
	echo '<table border=1 width="80%">';
	while ($i< $forward+1) {
		calendar__month_table_inner($time,$admin,$print);
		if ($forward>0) {
			echo '<TR><TD colspan=7 height=50>&nbsp;</TD></TR>';
			}
		$time=date__skip_months(1,$time);
		$i++;
		}
	echo '</table>';
}


function calendar__show_year($time=0,$admin=false,$print=false) {
if ($time==0) $time=time();
$yearstarttime=date__year_start_time($time);
calendar__month_table($yearstarttime,11,$admin,$print);
}

?>
