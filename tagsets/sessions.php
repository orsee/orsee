<?php

// experiment session functions. part of orsee. see orsee.org

function sessions__format_alist($session) {
	global $lang, $color;
  	extract($session);
	$session_time_array=time__load_session_time($session);
        $session_time=session__build_name($session);

	$reg=experiment__count_participate_at($experiment_id,$session_id);
	if ($reg < $part_needed) {
		$regfontcolor=$color['session_not_enough_participants'];
		}
	  elseif ($reg < $part_needed + $part_reserve) {
		$regfontcolor=$color['session_not_enough_reserve'];
		}
	   else {
		$regfontcolor=$color['session_complete'];
		}

        echo '	<tr>
        		<td>
				'.$session_time.'
        		</td>
			<td>
			</td>
			<td>
				'.laboratories__get_laboratory_name($laboratory_id).'
			</td>
        		<td>';
				if (check_allow('session_edit')) echo '
        			<A HREF="session_edit.php?session_id='.$session_id.'">
                			'.$lang['edit'].'
                		</A>';
        echo '		</td>
        	</tr>';

	$allow_sp=check_allow('experiment_show_participants');

	echo '	<TR>
			<TD>';
				if ($allow_sp) echo '
					<A HREF="experiment_participants_show.php?experiment_id='.
						$experiment_id.'&focus=registered&session_id='.
						$session_id.'">';
				echo $lang['registered_subjects'];
				if ($allow_sp) echo '</A>';
				echo ': 
				<FONT color="'.$regfontcolor.'">
				'.$reg.' ('.$part_needed.','.$part_reserve.')</FONT> '.
				help("shortcut_counts").'
			</TD>
			<TD>';
				if ($session_finished!="y") {
					if ($reminder_sent=="y") {
						$state=$lang['session_reminder_state__sent'];
						$statecolor=$color['session_reminder_state_sent_text'];
						}
					elseif ($reminder_checked=="y" && $reminder_sent=="n") {
						$state=$lang['session_reminder_state__checked_but_not_sent'];
						$statecolor=$color['session_reminder_state_checked_text'];
						}
					else {
						$state=$lang['session_reminder_state__waiting'];
						$statecolor=$color['session_reminder_state_waiting_text'];
						}
					echo '<FONT color="'.$statecolor.'">'.$lang['session_reminder'].': '.$state.'</FONT>';
					}
	echo '		</TD>
			<TD colspan=2>';
				if ($session_finished=="y")
					echo '<font color="'.$color['session_finished'].'">
						'.$lang['session_finished'].'</font>';
		echo '	</TD>
		</TR>';

        echo '	<TR>
			<TD colspan=3 class=small>
				&nbsp;
			</TD>
		</TR>';
}

function session__check_lab_time_clash($entry) {
        global $lang;

	if (isset($entry['session_start_year'])) {
		$notice=$lang['overlapping_sessions'];
		$thistimearray=time__load_session_time($entry);
                $this_start_time=time__time_package_to_unixtime($thistimearray);
                $this_end_time = $this_start_time +
                                    ( (int) $entry['session_duration_hour'] * 3600 ) +
                                    ( (int) $entry['session_duration_minute'] * 60);
		}
	   else {
		$notice=$lang['overlapping_lab_reservation'];
                $thistimearray=time__get_timepack_from_pack ($entry,"space_start_");
                $this_start_time=time__time_package_to_unixtime($thistimearray);
		$thisendtimearray=time__get_timepack_from_pack ($entry,"space_stop_");
                $this_end_time=time__time_package_to_unixtime($thisendtimearray);
		}


        $query="SELECT unix_timestamp(concat(session_start_year,'-',session_start_month,'-',session_start_day,' ',
					session_start_hour,':',session_start_minute,':0')) as start_time, 
			unix_timestamp(concat(session_start_year,'-',session_start_month,'-',session_start_day,' ',
                                        session_start_hour,':',session_start_minute,':0')) +
					session_duration_hour*3600 + session_duration_minute*60 as stop_time,
			 ".table('experiments').".*, ".table('sessions').".*
                FROM ".table('experiments').", ".table('sessions')."
                WHERE ".table('experiments').".experiment_id=".table('sessions').".experiment_id
                AND ".table('experiments').".experiment_type!='internet'
                AND session_id!='".$entry['session_id']."'
                AND laboratory_id='".$entry['laboratory_id']."' 
		HAVING NOT (start_time >= '".$this_end_time."' OR stop_time <= '".$this_start_time."')
		ORDER BY start_time";
        $result=mysql_query($query) or die("Database error: " . mysql_error());

        while ($osession=mysql_fetch_assoc($result)) {
        	message ('<UL><LI>'.
                	$notice.': <A HREF="session_edit.php?session_id='.$osession['session_id'].'">'.
                        experiment__get_title($osession['experiment_id']).' - '.
                        session__build_name($osession).'</A></UL>');
                }

	$query="SELECT unix_timestamp(concat(space_start_year,'-',space_start_month,'-',space_start_day,' ',
                                        space_start_hour,':',space_start_minute,':0')) as start_time,
                        unix_timestamp(concat(space_stop_year,'-',space_stop_month,'-',space_stop_day,' ',
                                        space_stop_hour,':',space_stop_minute,':0')) as stop_time,
                         ".table('lab_space').".*
                FROM ".table('lab_space')." 
                WHERE laboratory_id='".$entry['laboratory_id']."' 
			AND space_id!='".$entry['space_id']."' 
                HAVING NOT (start_time >= '".$this_end_time."' OR stop_time <= '".$this_start_time."')
                ORDER BY start_time";
        $result=mysql_query($query) or die("Database error: " . mysql_error());

        while ($osession=mysql_fetch_assoc($result)) {
		$ostart_time=time__get_timepack_from_pack ($osession,"space_start_");
		$ostop_time=time__get_timepack_from_pack ($osession,"space_stop_");
		$ostart_string=time__format($lang['lang'],$ostart_time);
		$ostop_string=time__format($lang['lang'],$ostop_time);
                message ('<UL><LI>'.
                        $notice.': <A HREF="calendar_main.php?time='.$this_start_time.'">'.
                        $osession['reason'].' - '.
                        $ostart_string.' - '.$ostop_string.'</A></UL>');
                }
}



function session__get_status($session,$tlang="",$reg="") {
	global $settings;
	if ($tlang=="") $tlang=$settings['admin_standard_language'];

	if ($reg=="") $reg=experiment__count_participate_at($session['experiment_id'],$session['session_id']);
        if ($reg < $session['part_needed']) {
                $status=load_language_symbol('not_enough_participants',$tlang);
                }
          elseif ($reg < $session['part_needed'] + $session['part_reserve']) {
                $status=load_language_symbol('not_enough_reserve',$tlang);
                }
           else {
                $status=load_language_symbol('complete',$tlang);
                }
	return $status;
}



function session__build_name($pack,$language="") {
	global $lang, $settings;
	if (!$language) {
        	if (isset($lang['lang'])) $thislang=$lang['lang'];
                        else $thislang=$settings['public_standard_language'];
		}
		else {
		$thislang=$language;
		}

	$session_time=time__load_session_time($pack);
	$duration=time__get_timepack_from_pack($pack,"session_duration_");
        $end_time=time__add_packages($session_time,$duration);
	$session_time_string=time__format($thislang,$session_time,false,false,true,false).'-'.
			time__format($thislang,$end_time,true,false,true,true);
	return $session_time_string;
}


function sessions__get_first_date($experiment_id) {
	global $expadmindata;
     	$query="SELECT *
      		FROM ".table('sessions')."
      		WHERE experiment_id='".$experiment_id."'
      		ORDER BY session_start_year, session_start_month, session_start_day
		LIMIT 1";
	$fsession=orsee_query($query);
	if (isset($fsession['session_start_year'])) {
		$ttime=time__load_session_time($fsession);
		$dstr=time__format($expadmindata['language'],$ttime,false,true,true,false);
		return $dstr;
		}
	   else {
		return "???";
		}
}

function sessions__get_last_date($experiment_id) {
        global $expadmindata;
        $query="SELECT session_start_year, session_start_month, session_start_day
      		FROM ".table('sessions')." 
      		WHERE experiment_id='".$experiment_id."'
      		ORDER BY session_start_year DESC, session_start_month DESC, session_start_day DESC
        	LIMIT 1";
        $fsession=orsee_query($query);
        if (isset($fsession['session_start_year'])) {
                $ttime=time__load_session_time($fsession);
                $dstr=time__format($expadmindata['language'],$ttime,false,true,true,false);
                return $dstr;
                }
           else {
                return "???";
                }
}

function sessions__get_registration_end($alist,$session_id="",$experiment_id="") {
	if ($session_id) {
		$query="SELECT * FROM ".table('sessions')." WHERE session_id='".$session_id."'";
		$alist=orsee_query($query);
		}
 	elseif ($experiment_id) {
		$query="SELECT ".table('sessions').".*,
                	max(session_start_year*100000000 +
                	session_start_month*1000000 +
                	session_start_day*10000 +
                	session_start_hour*100 +
                	session_start_minute) as time
               	 	FROM ".table('experiments').", ".table('sessions')."
                	WHERE ".table('experiments').".experiment_id=".table('sessions').".experiment_id
                	AND experiment_type='laboratory'
			AND ".table('experiments').".experiment_id='".$experiment_id."'
			GROUP BY ".table('experiments').".experiment_id
                	ORDER BY time DESC
			LIMIT 1";
		$alist=orsee_query($query);
		}
	$blist=time__load_session_time($alist);
	$session_unixtime=time__time_package_to_unixtime($blist);
	$diff = ((int) $alist['registration_end_hours']) * 3600;
	$registration_end_unixtime=$session_unixtime-$diff;
	return $registration_end_unixtime;
}

function sessions__get_reminder_time($alist,$session_id="") {
	if ($session_id) {
		$query="SELECT * FROM ".table('sessions')." WHERE session_id='".$session_id."'";
        	$alist=orsee_query($query);
		}
	$blist=time__load_session_time($alist);
	$session_unixtime=time__time_package_to_unixtime($blist);
	$diff = ((int) $alist['session_reminder_hours']) * 3600;
	$reminder_unixtime=$session_unixtime-$diff;
	return $reminder_unixtime;
}

function sessions__get_session_time($pack,$session_id="") {
	if ($session_id) 
        	$pack=orsee_db_load_array("sessions",$session_id,"session_id");

	$session_time=time__load_session_time($pack);
	$session_unixtime=time__time_package_to_unixtime($session_time);
	return $session_unixtime;
}


function sessions__session_full($session_id,$thissession=array()) {

	if (!isset($thissession['session_id'])) 
	 	$thissession=orsee_db_load_array("sessions",$session_id,"session_id");
	$reg=experiment__count_participate_at($thissession['experiment_id'],$thissession['session_id']);
	if ($reg < $thissession['part_needed'] + $thissession['part_reserve']) $session_full=false; else $session_full=true;
	return $session_full;
}



function sessions__get_experiment_id($session_id) {
	$query="SELECT experiment_id
      		FROM ".table('sessions')." 
      		WHERE session_id='".$session_id."'";
	$res=orsee_query($query);
	if (isset($res['experiment_id'])) $experiment_id=$res['experiment_id']; else $experiment_id="";
	return $experiment_id;
}

?>
