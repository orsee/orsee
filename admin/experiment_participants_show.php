<?php
ob_start();

$menu__area="experiments";
$title="show participants";

include("header.php");

	if ($_REQUEST['experiment_id']) $experiment_id=$_REQUEST['experiment_id'];
                else redirect("admin/experiment_main.php");

        if ($_REQUEST['session_id']) $session_id=$_REQUEST['session_id'];
                else $session_id='';

	if ($_REQUEST['remember']) { 
		$_REQUEST=array_merge($_REQUEST,$_SESSION['save_posted']);
		$_SESSION['save_posted']=array();
		unset($_REQUEST['change']);
		}

	$allow=check_allow('experiment_show_participants','experiment_show.php?experiment_id='.$experiment_id);

	$experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
	if (!check_allow('experiment_restriction_override'))
		check_experiment_allowed($experiment,"admin/experiment_show.php?experiment_id=".$experiment_id);

	if ($_REQUEST['change']) {

		$allow=check_allow('experiment_edit_participants','experiment_participants_show.php?experiment_id='.
				$_REQUEST['experiment_id'].'&session_id='.$_REQUEST['session_id'].'&focus='.$_REQUEST['focus']);

		if ($_REQUEST['focus']) $focus=$_REQUEST['focus'];
			else redirect('admin/'.thisdoc().'?experiment_id='.$_REQUEST['experiment_id'].
						'&session_id='.$_REQUEST['session_id']);
		$$focus=true;

		if ($_REQUEST['result_count']) $pcount=$_REQUEST['result_count']; else $pcount=0;

		if ($assigned || $invited) {
			$continue=true;

			if ($_REQUEST['to_session']) $to_session=$_REQUEST['to_session']; else $to_session=0;

			if ($to_session==0) {
				$continue=false;
				$_SESSION['save_posted']=$_REQUEST;
				message($lang['no_session_selected'],'message_error');
				redirect('admin/'.thisdoc().'?experiment_id='.$_REQUEST['experiment_id'].
                                                '&focus='.$focus.'&remember=true');
				}

			$tsession=orsee_db_load_array("sessions",$to_session,"session_id");

			$p_to_add=array();
			$i=0;
                        while ($i < $pcount) {
                                $i++;
				if ($_REQUEST['reg'.$i]=="y") $p_to_add[]=$_REQUEST['pid'.$i];
				}

			$num_to_add=count($p_to_add);

			if ($_REQUEST['check_if_full']) {
				$alr_reg=experiment__count_participate_at($experiment_id,$to_session);
				$free_places=$tsession['part_needed']+$tsession['part_reserve']-$alr_reg;
				if ($free_places < 0) $free_places=0;
	
				if ($num_to_add > $free_places) {
					$continue=false;
					message($lang['too_much_participants_to_register'].' '.
						$lang['free_places_in_session_xxx'].' '.
						session__build_name($tsession).': 
						<FONT color="green">'.$free_places.'</FONT><BR>'.
						$lang['please_change_your_selection'],'message_error');
						$_SESSION['save_posted']=$_REQUEST;
					redirect('admin/'.thisdoc().
						'?experiment_id='.$_REQUEST['experiment_id'].
                                                '&focus='.$focus.'&remember=true'.
						'&to_session='.$to_session);
					}
				}

			if ($continue) {
				$part_list=implode("','",$p_to_add);
               			$query="UPDATE ".table('participate_at')."
					SET session_id='".$to_session."',
						registered='y'
					WHERE experiment_id='".$experiment_id."'
					AND participant_id IN ('".$part_list."')";
				$done=mysql_query($query) or die("Database error: " . mysql_error());

				message ($num_to_add.' '.$lang['xxx_subjects_registered_to_session_xxx'].' '.
					session__build_name($tsession).'.<BR>
					<A HREF="'.thisdoc().'?focus=registered&experiment_id='.$experiment_id.
					'&session_id='.$to_session.'">'.$lang['click_here_to_go_to_session_xxx'].
					' '.session__build_name($tsession).'</A>');
				redirect('admin/'.thisdoc().'?experiment_id='.$experiment_id.'&focus='.$focus);
				}

			}


		elseif ($registered || $shownup || $participated) {


			$p_shup=array(); $p_shup_not=array();
			$p_part=array(); $p_part_not=array();
			$p_rules=array(); $p_rules_not=array();
			$move=array();

		        $i=0;
                        while ($i < $pcount) {
                        	$i++;
                        	if ($_REQUEST['shup'.$i]=="y") $p_shup[]=$_REQUEST['pid'.$i];
							else $p_shup_not[]=$_REQUEST['pid'.$i];

                                if ($_REQUEST['part'.$i]=="y") $p_part[]=$_REQUEST['pid'.$i];
                                                        else $p_part_not[]=$_REQUEST['pid'.$i];

                                if ($_REQUEST['rules'.$i]=="y") $p_rules[]=$_REQUEST['pid'.$i];
                                                        else $p_rules_not[]=$_REQUEST['pid'.$i];

				if ($_REQUEST['session'.$i]!=$session_id 
				    && $_REQUEST['session'.$i] != $_REQUEST['csession'.$i]) {
					$to_session=$_REQUEST['session'.$i];
					if (!isset($move[$to_session])) $move[$to_session]=array();
					$move[$to_session][]=$_REQUEST['pid'.$i];
					}
				}

			// update shownup data
			$part_list=implode("','",$p_shup);
			$query="UPDATE ".table('participate_at')."
                                SET shownup = 'y'
                                WHERE experiment_id='".$experiment_id."'
				AND participant_id IN ('".$part_list."')";
			$done=mysql_query($query) or die("Database error: " . mysql_error());
			
			$part_list=implode("','",$p_shup_not);
                        $query="UPDATE ".table('participate_at')."
                                SET shownup = 'n'
                                WHERE experiment_id='".$experiment_id."'
				AND participant_id IN ('".$part_list."')";
                        $done=mysql_query($query) or die("Database error: " . mysql_error());

			// update participation data
                        $part_list=implode("','",$p_part);
                        $query="UPDATE ".table('participate_at')."
                                SET participated = 'y'
                                WHERE experiment_id='".$experiment_id."'
                                AND participant_id IN ('".$part_list."')";
                        $done=mysql_query($query) or die("Database error: " . mysql_error());

                        $part_list=implode("','",$p_part_not);
                        $query="UPDATE ".table('participate_at')."
                                SET participated = 'n'
                                WHERE experiment_id='".$experiment_id."'
                                AND participant_id IN ('".$part_list."')";
                        $done=mysql_query($query) or die("Database error: " . mysql_error());

			// check for inconsitencies and clean
			$query="UPDATE ".table('participate_at')."
                                SET shownup = 'y'
                                WHERE participated='y'";
                        $done=mysql_query($query) or die("Database error: " . mysql_error());

			// update rules signed data
                        $part_list=implode("','",$p_rules);
                        $query="UPDATE ".table('participants')."
                                SET rules_signed = 'y'
                                WHERE participant_id IN ('".$part_list."')";
                        $done=mysql_query($query) or die("Database error: " . mysql_error());

                        $part_list=implode("','",$p_rules_not);
                        $query="UPDATE ".table('participants')."
                                SET rules_signed = 'n'
                                WHERE participant_id IN ('".$part_list."')";
                        $done=mysql_query($query) or die("Database error: " . mysql_error());



			// move participants to other sessions ...
			$moved_nr=array();
			foreach ($move as $msession => $mparts) {
				$part_list=implode("','",$mparts);
                              	$query="UPDATE ".table('participate_at')."
                                	SET session_id = '".$msession."', shownup='n', participated='n'
                             		WHERE participant_id IN ('".$part_list."')
					AND experiment_id='".$experiment_id."'";
				$done=mysql_query($query) or die("Database error: " . mysql_error());
				}

			// clean up 'no session's
                        $query="UPDATE ".table('participate_at')."
                                SET participated = 'n', shownup='n', registered='n'
                             	WHERE session_id='0'";
			$done=mysql_query($query) or die("Database error: " . mysql_error());



			message($lang['changes_saved']);
			$m_message='<UL>';
			foreach ($move as $msession => $mparts) {
				$m_message.='<LI>'.count($mparts).' ';
				if ($msession==0) $m_message.=$lang['xxx_subjects_removed_from_registration'];
				   else {
					$tsession=orsee_db_load_array("sessions",$msession,"session_id");
					$m_message.=$lang['xxx_subjects_moved_to_session_xxx'].' 
						<A HREF="'.thisdoc().'?focus=registered&experiment_id='.
							$experiment_id.'&session_id='.$msession.'">'.
                					session__build_name($tsession).'</A>';
					$tpartnr=experiment__count_participate_at($experiment_id,$msession);
					if ($tsession['part_needed'] + $tsession['part_reserve'] < $tpartnr) 
						$mmessage.='<BLINK>'.
							$lang['subjects_number_exceeded'].'</BLINK>';
					}
				}
			$m_message.='</UL>';
			message($m_message);
			$target="experiment:".$experiment['experiment_name'];
			if ($session_id) $target.="\nsession_id:".$session_id;
			log__admin("experiment_edit_participant_list",$target);

			redirect('admin/'.thisdoc().'?experiment_id='.$_REQUEST['experiment_id'].
                                                '&session_id='.$_REQUEST['session_id'].
						'&focus='.$focus);
			}

		}
	// list output

	if ($session_id) $session=orsee_db_load_array("sessions",$session_id,"session_id");

	script__part_reg_show();


	if ($_REQUEST['sort']) $order=$_REQUEST['sort']; 
		else $order="session_start_year, session_start_month, session_start_day,
                	session_start_hour, session_start_minute, lname, fname, email";

	if ($_REQUEST['focus']) $focus=$_REQUEST['focus']; else $focus="assigned";

	$$focus=true;

	switch ($focus) {
		case "assigned":	$where_clause="AND registered='n'";
					$title=$lang['assigned_subjects_not_yet_registered'];
					break;
		case "invited":		$where_clause="AND invited='y' AND registered='n'";
                                        $title=$lang['invited_subjects_not_yet_registered'];
                                        break;
        	case "registered":	$where_clause="AND registered='y'";
                                        $title=$lang['registered_subjects'];
                                        break;
        	case "shownup":		$where_clause="AND shownup='y'";
                                        $title=$lang['shownup_subjects'];
                                        break;
        	case "participated":	$where_clause="AND participated='y'";
                                        $title=$lang['subjects_participated'];
                                        break;
		} 


	$select_query=" SELECT * FROM ".table('participants').", ".table('participate_at').", 
					".table('sessions')."
			WHERE ".table('participants').".participant_id=".
					table('participate_at').".participant_id 
			AND ".table('sessions').".session_id=".table('participate_at').".session_id 
			AND ".table('participate_at').".experiment_id='".$experiment_id."' ";
	if ($session_id) $select_query.=" AND ".table('participate_at').".session_id='".$session_id."' "; 
        $select_query.=$where_clause." ORDER BY ".$order;


	echo '
		<center>
		<BR>
			<h4>'.$experiment['experiment_name'].'</h4>
			<h4>'.$title.'</h4>

		<P align=right>
			<A class="small" HREF="experiment_participants_show_pdf.php?experiment_id='.
				$experiment_id.'&session_id='.$session_id.'&focus='.$focus.
				'" target="_blank">'.$lang['print_version'].'</A>
		</P>';

	// show query
	echo '	<P class="small">Query: '.$select_query.'</P>';

	// get result
	$result=mysql_query($select_query) or die("Database error: " . mysql_error());

        $participants=array(); $plist_ids=array();
        while ($line=mysql_fetch_assoc($result)) {
                $participants[]=$line;
		$plist_ids[]=$line['participant_id'];
                }
	$_SESSION['plist_ids']=$plist_ids;
	$result_count=count($participants);

	// form
	echo '
		<FORM name="part_list" method=post action="'.thisdoc().'">
	
		<BR>
		<table border=0>
			<TR>
				<TD class="small"></TD>';
				headcell($lang['lastname'],"lname,fname,email");
				headcell($lang['firstname'],"fname,lname,email");
				headcell($lang['e-mail-address'],"email,lname,fname");
				headcell($lang['phone_number']);
				headcell($lang['gender'],"gender");
				headcell($lang['studies'].'/'.$lang['profession'],"field_of_studies,profession");
				headcell($lang['noshowup'],"number_noshowup,number_reg");
			if ($assigned || $invited) {
				headcell($lang['invited'],"invited","invited");
				headcell($lang['register']);
				}
			if ($registered || $shownup || $participated) {
				headcell($lang['session']);
				headcell($lang['shownup'],"shownup","shownup");
				headcell($lang['participated'],"participated","participated");
				headcell($lang['rules_signed']);
				}
	echo '		</TR>';

	$nr_normal_columns=8;

	$studies=lang__load_studies();
        $professions=lang__load_professions();

	$shade=false;

	$part_ids=array();
	$emails=array();

	$pnr=0;

	if (check_allow('experiment_edit_participants')) $disabled=false; else $disabled=true;

        foreach ($participants as $p) {

		$emails[]=$p['email'];
		$pnr++;

		echo '<tr class="small"';
                        if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
                               else echo 'bgcolor="'.$color['list_shade2'].'"';
                echo '>';

	        echo '	<td class="small">
				'.$pnr.'
				<INPUT name="pid'.$pnr.'" type=hidden value="'.$p['participant_id'].'">
			</td>
			<td class="small">'.$p['lname'].'</td>
                        <td class="small">'.$p['fname'].'</td>
        	   	<td class="small"><A class="small" HREF="mailto:'.
                                                $p['email'].'">'.$p['email'].'</A></TD>
		   	<td class="small">'.$p['phone_number'].'</td>
                        <td class="small">';
				if ($p['gender']=='m') echo $lang['gender_m_abbr'];
                        		elseif ($p['gender']=='f') echo $lang['gender_f_abbr'];
                        		else echo "?";
		echo '	</td>
                        <td class="small">';
                                        if ($p['field_of_studies']>0)
                        echo $studies[$p['field_of_studies']].' ('.$p['begin_of_studies'].')';
                                        else echo $professions[$p['profession']];
                                echo '</td>
			<td class="small">'.$p['number_noshowup'].
                                                '/'.$p['number_reg'].'</td>';

		if ($assigned || $invited) {
			echo '<td class="small">'.$p['invited'].'</td>
			      <td class="small">
					<INPUT type=checkbox name="reg'.$pnr.'" value="y"';
					if ($_REQUEST['reg'.$pnr]=="y") echo ' CHECKED';
					if ($disabled) echo ' DISABLED';
					echo '>
				</td>';
			}
	   	if ($registered || $shownup || $participated) {
	   		echo '<td class="small">';
				if (!$session_id) {
				echo '<INPUT type=hidden name="csession'.$pnr.'" value="'.$p['session_id'].'">';
				}
				if ($disabled) echo session__build_name($p);
				   else select__sessions($p['session_id'],'session'.$pnr,$experiment_id,false);
	   		echo '</td>
	   			<td class="small">';

                       	if ($registered || $shownup) {
                       		echo '<INPUT type=checkbox name="shup'.$pnr.'" value="y" 
					onclick="checkshup('.$pnr.')"'; 
                               	if ($p['shownup']=="y") echo ' CHECKED';
				if ($disabled) echo ' DISABLED';
				echo '>';
				}
                       	   else echo $p['shownup'];

	   		echo '</td>
	   			<td class="small">
        	               		<INPUT type=checkbox name="part'.$pnr.'" value="y" 
						onclick="checkpart('.$pnr.')"'; 
        	                       	if ($p['participated']=="y") echo ' CHECKED';
					if ($disabled) echo ' DISABLED';
                	        	echo '>
	   			</td>
		   		<td class="small">';
			//if ($session_id) {
					echo '<INPUT type=checkbox name="rules'.$pnr.'" value="y"';
                                	if ($p['rules_signed']=="y") echo ' CHECKED';
					if ($disabled) echo ' DISABLED';
					echo '>';
			//		}
			//	   else {
			//		if ($p['rules_signed']=="y") echo $lang['yes']; 
			//					else echo $lang['no_emp'];
			//		}
				echo '</td>';
			}
		echo '</tr>';
		if ($shade) $shade=false; else $shade=true;
		}



function form__check_all($name,$count,$second_sel_name="",$second_un_name="") {
	global $lang;
	echo '<center>';
	echo '<input type=button value="'.$lang['select_all'].'" ';
	echo 'onClick="checkAll(\''.$name.'\','.$count.')';
	if ($second_sel_name) echo ';checkAll(\''.$second_sel_name.'\','.$count.')';
	echo '"><br>';
	echo '<input type=button value="'.$lang['select_none'].'" ';
	echo 'onClick="uncheckAll(\''.$name.'\','.$count.')';
	if ($second_un_name) echo ';uncheckAll(\''.$second_un_name.'\','.$count.')';
	echo '"></center>';
}

	if (check_allow('experiment_edit_participants')) {

	// table footer ...
	echo '	<TR>
			<TD colspan='.$nr_normal_columns.'></td>';

		if ($assigned || $invited) {
			echo '<TD></TD><TD>';
			form__check_all('reg',$result_count);
			echo '</TD>';
			}
	
		if ($registered || $shownup || $participated) {
			echo '<TD></TD>
				<TD>';
			if ($registered || $shownup) 
				form__check_all('shup',$result_count,'','part');
			echo '</TD>
				<TD>';
                		form__check_all('part',$result_count,'shup','');
			echo '</TD>
				<TD>';
			if ($session_id)
				form__check_all('rules',$result_count);
			echo '</TD>';
			}
	echo '	</TR>
		</table>';


	echo '	<TABLE border=0>
			<TR>
				<TD>&nbsp;</TD>
			</TR>';
	if ($assigned || $invited) {
		echo '	<TR>
				<TD>
					'.$lang['register_marked_for_session'].' ';
					select__sessions($_REQUEST['to_session'],'to_session',$experiment_id,false);
			echo '	</TD>
			</TR>
			<TR>
				<TD>
					'.$lang['check_for_free_places_in_session'].'
					<INPUT type=checkbox name="check_if_full" value="true"';
					if ($_REQUEST['check_if_full'] || ! $_REQUEST['remember']) 
						echo ' CHECKED';
					echo '>
				</TD>
			</TR>';
		}

	echo '	<TR>
			<TD align=center>
				<INPUT type=hidden name="result_count" value="'.$result_count.'">
				<INPUT type=hidden name="focus" value="'.$focus.'">
				<INPUT type=hidden name="experiment_id" value="'.$experiment_id.'">
				<INPUT type=hidden name="session_id" value="'.$session_id.'">
				<INPUT type=submit name="change" value="'.$lang['change'].'">
			</TD>
		</TR>';
		}

	echo '</table>
		
		</form>

		<BR>
		<TABLE width="80%" border=0>
		<TR>
			<TD>';

	 			if ($session_id && $session['session_finished']!="y" && check_allow('session_send_reminder')) {
                                        if ($session['reminder_sent']=="y") {
                                                $state=$lang['session_reminder_state__sent'];
                                                $statecolor=$color['session_reminder_state_sent_text'];
						$explanation=$lang['session_reminder_sent_at_time_specified'];
						$send_button_title=$lang['session_reminder_send_again'];
                                                }
                                        elseif ($session['reminder_checked']=="y" && $session['reminder_sent']=="n") {
                                                $state=$lang['session_reminder_state__checked_but_not_sent'];
                                                $statecolor=$color['session_reminder_state_checked_text'];
						$explanation=$lang['session_reminder_not_sent_at_time_specified'];
                                                $send_button_title=$lang['session_reminder_send'];
                                                }
                                        else {
                                                $state=$lang['session_reminder_state__waiting'];
                                                $statecolor=$color['session_reminder_state_waiting_text'];
						$explanation=$lang['session_reminder_will_be_sent_at_time_specified'];
                                                $send_button_title=$lang['session_reminder_send_now'];
                                                }
                                        echo '<FONT color="'.$statecolor.'">'.$lang['session_reminder'].': '.$state.'</FONT><BR>';
					echo $explanation.'<BR><FORM action="session_send_reminder.php">'.
						'<INPUT type=hidden name="session_id" value="'.$session_id.'">'.
						'<INPUT type=submit name="submit" value="'.$send_button_title.'"></FORM>';
                                        }
	echo '		</TD><TD align=right>';
			if (check_allow('participants_bulk_mail')) 
                        	experimentmail__bulk_mail_form();
	echo '		</TD>';

	echo '	</TR>
		</TABLE>';

        echo '  <A HREF="experiment_show.php?experiment_id='.$experiment_id.'">
                        '.$lang['mainpage_of_this_experiment'].'</A><BR><BR>

                </CENTER>';
include ("footer.php");

?>
