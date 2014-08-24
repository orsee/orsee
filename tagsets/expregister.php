<?php
// part of orsee. see orsee.org

function expregister__list_invited_for_format($varray) {
	static $saved_experiment_id;
	global $color, $lang, $authdata;

	$done=extract($varray);

	$session_unixtime=sessions__get_session_time($varray);
        $registration_unixtime=sessions__get_registration_end($varray);
        $session_full=sessions__session_full("",$varray);
	$now=time();

	if( $now < $session_unixtime) {

        	if ($experiment_id != $saved_experiment_id) {
        		$saved_experiment_id=$experiment_id;
        		echo '<TR><TD>&nbsp;&nbsp;&nbsp;</TD>
        			<TD colspan=2 bgcolor="'.$color['list_title_background'].'">
                		'.$experiment_public_name.'
        			</TD></TR>';
       			} 

	echo '<TR bgcolor="'.$color['list_shade1'].'"><TD></TD><TD>';

        echo session__build_name($varray).", "; 
        echo laboratories__get_laboratory_name($laboratory_id).",  ".$lang['registration_until']." ";
        echo time__format($authdata['language'],"",false,false,true,false,$registration_unixtime);
	echo '</TD>
		<TD>';
	if ((!$session_full) && ($registration_unixtime >= $now)) {
		echo '
			<FORM action="participant_show.php">
			<INPUT type=hidden name="p" value="'.unix_crypt($participant_id).'">
			<INPUT type=hidden name="s" value="'.unix_crypt($session_id).'">
			<INPUT type=submit class="small" name="register" value="'.$lang['register'].'">
			</FORM>';
		} 
		elseif ($registration_unixtime < $now) {
			echo '<FONT color="'.$color['session_public_expired'].'">'.$lang['expired'].'</FONT>';
		}
		else {
			echo '<FONT color="'.$color['session_public_complete'].'">'.$lang['complete'].'</FONT>';
		}
	echo '</TD>
		</TR>
		<TR><TD colpan=3>&nbsp;</TD></TR>';

	}
	return $laboratory_id;
}



function expregister__list_invited_for($participant_id) {
	$query="SELECT *
      		FROM ".table('participate_at').", ".table('experiments').", ".table('sessions')." 
        	WHERE ".table('experiments').".experiment_id=".table('sessions').".experiment_id
		AND ".table('experiments').".experiment_id=".table('participate_at').".experiment_id
		AND ".table('participate_at').".participant_id = '".$participant_id."'
		AND ".table('sessions').".session_finished = 'n'
		AND ".table('participate_at').".registered = 'n'
		AND ".table('experiments').".experiment_type='laboratory'
      		ORDER BY ".table('experiments').".experiment_id, session_start_year, session_start_month, session_start_day,
                 session_start_hour, session_start_minute";
	$labs=orsee_query($query,"expregister__list_invited_for_format");
	$unique_labs=array_unique($labs);
	return $unique_labs;
}


function expregister__list_registered_for_format($varray,$reg_session_id="",$type="registered") {
        static $saved_experiment_id, $first_line=false, $first_hline=false, $shade=false;
        global $color, $lang, $authdata;

	if ($type=="print") $print=true; else $print=false;
	if ($type=="history") $history=true; else $history=false;

        $done=extract($varray);

        $session_unixtime=sessions__get_session_time($varray);
        $now=time();

        if ((!$history && $now < $session_unixtime) || ($history && $now >= $session_unixtime)) {

                if ((!$history && !$first_line) || ($history && !$first_hline)) {
                	echo '<TR';
			if (!$print) echo ' bgcolor="'.$color['list_title_background'].'"';
			echo '><TD>
                        	'.$lang['experiment'].'
                    		</TD>
                    		<TD>
                        	'.$lang['date_and_time'].'
                    		</TD>
                    		<TD>
                        	'.$lang['location'].'
                    		</TD>';
			if ($history) {
				echo '<TD>'.$lang['showup?'].'</TD>';
				$first_hline=true;
				}
			else $first_line=true;
	             	echo '</TR>';
                	}


		echo '<TR';
			if ($shade) $shade=false; else $shade=true;
			if ($session_id==$reg_session_id) 
				echo ' bgcolor="'.$color['just_registered_session_background'].'"';
			elseif ($print && $shade) 
				echo ' bgcolor="lightgrey"';
			elseif ($shade) 
				echo ' bgcolor="'.$color['list_shade1'].'"';
			else echo ' bgcolor="'.$color['list_shade2'].'"';

		echo '><TD>
                	'.$experiment_public_name.'
			</TD>
        		<TD>';
		echo session__build_name($varray);
		echo '</TD>
			<TD>';
		echo laboratories__get_laboratory_name($laboratory_id);
		echo '</TD>';
		if ($history) {
			echo '<TD>';
                	if ($session_finished=="y") {
				if ($shownup=="n") {
					$tcolor=$color['shownup_no'];
					$ttext=$lang['no'];
					}
				elseif ($shownup=="y") {
                                        $tcolor=$color['shownup_yes'];
                                        $ttext=$lang['yes'];
					}
				echo '<FONT color="'.$tcolor.'">'.$ttext.'</FONT>';
				}
			   else echo $lang['three_questionmarks'];
			echo '</TD>';
			}
		echo '</TR>';

		if (!$print) {
			echo '<TR><TD colspan=';
			if ($history) echo "4"; else echo "3";
			echo '>&nbsp;</TD></TR>';
			}

		return $laboratory_id;
		}
}



function expregister__list_registered_for($participant_id,$reg_session_id="",$type="registered") {
	echo '<TR><TD colspan=3>
		<TABLE width="100%" border=';
	if ($type=="print") echo "1"; else echo "0";
	echo '>';
	$query="SELECT *
      		FROM ".table('experiments').", ".table('sessions').", ".table('participate_at')."
        	WHERE ".table('experiments').".experiment_id=".table('sessions').".experiment_id
		AND ".table('experiments').".experiment_id=".table('participate_at').".experiment_id
		AND ".table('participate_at').".participant_id = '".$participant_id."'
		AND ".table('sessions').".session_id = ".table('participate_at').".session_id
		AND ".table('sessions').".session_finished = 'n'
		AND ".table('participate_at').".registered = 'y' 
		AND ".table('experiments').".experiment_type='laboratory' 
      		ORDER BY session_start_year, session_start_month, session_start_day,
                 	session_start_hour, session_start_minute";

        $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
		$labs=array();
                while ($line = mysqli_fetch_assoc($result)) {
                        $labs[]=expregister__list_registered_for_format($line,$reg_session_id,$type);
                        }
        mysqli_free_result($result);

	echo '</TABLE>
		</TD></TR>';
		$unique_labs=array_unique($labs);
		return $unique_labs;
}



function expregister__list_history($participant_id) {
	echo '<TR><TD colspan=3>
		<TABLE width=100% border=0>';

     	$query="SELECT *
      		FROM ".table('experiments').", ".table('sessions').", ".table('participate_at')."
        	WHERE ".table('experiments').".experiment_id=".table('sessions').".experiment_id
        	AND ".table('experiments').".experiment_id=".table('participate_at').".experiment_id
        	AND ".table('participate_at').".participant_id = '".$participant_id."'
        	AND ".table('sessions').".session_id = ".table('participate_at').".session_id
        	AND ".table('participate_at').".registered = 'y'
		AND ".table('experiments').".experiment_type='laboratory' 
      		ORDER BY session_start_year DESC, session_start_month DESC, session_start_day DESC,
                 	session_start_hour DESC, session_start_minute DESC";
        $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
                while ($line = mysqli_fetch_assoc($result)) {
                        $labs[]=expregister__list_registered_for_format($line,"","history");
                        }
        mysqli_free_result($result);

        echo '</TABLE>
                </TD></TR>';
}


function expregister__check_registered($participant_id,$experiment_id) {
	$query="SELECT registered
      		FROM ".table('participate_at')."
      		WHERE experiment_id='".$experiment_id."'
		AND participant_id='".$participant_id."'";
	$res=orsee_query($query);
	if (isset($res['registered']) && $res['registered']=="y") $reg=true; else $reg=false;
	return $reg;
}



function expregister__register($participant_id,$session_id) {
	$experiment_id=sessions__get_experiment_id($session_id);
     	$query="UPDATE ".table('participate_at')."
      		SET registered='y', session_id='".$session_id."' 
      		WHERE experiment_id='".$experiment_id."'
        	AND participant_id='".$participant_id."'";
	$done=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));

	experimentmail__experiment_registration_mail($participant_id,$session_id,$experiment_id);

}



?>
