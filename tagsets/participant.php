<?php

// participant functions. part of orsee. see orsee.org.

function participants__count_participants($constraint="") {
	if ($constraint) $where_clause=" AND ".$constraint;
		else $where_clause="";
     	$query="SELECT COUNT(participant_id) as pcount
      		FROM ".table('participants')." 
      		WHERE deleted='n' ".$where_clause;
	$line=orsee_query($query);
	return $line['pcount'];
}

function participants__count_participants_temp($constraint="") {
        if ($constraint) $where_clause=" AND ".$constraint;
                else $where_clause="";
        $query="SELECT COUNT(participant_id) as pcount
                FROM ".table('participants_temp')."
                WHERE deleted='n' ".$where_clause;
        $line=orsee_query($query);
        return $line['pcount'];
}

// check if participant_id exists in participants table
function participant__participant_id_exists($pid) {
                $query="SELECT participant_id FROM ".table('participants')." 
                        WHERE participant_id=".$pid;
                $line=orsee_query($query);
                if (isset($line['participant_id'])) $exists=true; else $exists=false;
        return $exists;
}


// Create unique participant id
function participant__create_participant_id() {
           $gibtsschon=true;
	   srand ((double)microtime()*1000000);
           while ($gibtsschon) {
           	$crypt_id = "/";
           	while (eregi("(/|\\.)",$crypt_id)) { //<or <match <get-var crypt_id> "/"> <match <get-var crypt_id> "\\.">>>
           		$participant_id = rand();
           		$crypt_id=unix_crypt($participant_id);
           		}

                $query="SELECT participant_id FROM ".table('participants')." 
                 	WHERE participant_id=".$participant_id;
		$line=orsee_query($query);
                if (isset($line['participant_id'])) $gibtsschon=true; else $gibtsschon=false;

                if (!$gibtsschon) {
                	$query="SELECT participant_id FROM ".table('participants_temp')." 
                 		WHERE participant_id=".$participant_id;
                	$line=orsee_query($query);
                	if (isset($line['participant_id'])) $gibtsschon=true; else $gibtsschon=false;	
                	}

                if (!$gibtsschon) {
                	$query="SELECT participant_id FROM ".table('participants_os')." 
                 		WHERE participant_id=".$participant_id;
                        $line=orsee_query($query);
                        if (isset($line['participant_id'])) $gibtsschon=true; else $gibtsschon=false;
                	}
          	}
	return $participant_id; 
}

// create new id and replace old id with it
function participant__create_new_participant_id($participant_id) {
	//participants,participants_temp,participants_os
	$tables=array(  "participants"=>"participant_id",
			"participants_os"=>"participant_id",
			"participants_temp"=>"participant_id",
			"mail_queue"=>"mail_recipient",
			"os_playerdata"=>"participant_id",
			"os_results"=>"participant_id",
			"participate_at"=>"participant_id",
			);
	$crypt_tables=array("participants","participants_temp","participants_os");
	foreach ($tables as $table=>$column) {
		$query="LOCK TABLES ".table($table);
		$done=mysql_query($query);
		}
	$new_id=participant__create_participant_id();
	foreach ($tables as $table=>$column) {
                $query="UPDATE ".table($table)." SET ".$column."='".$new_id."' WHERE ".$column."='".$participant_id."'";
                $done=mysql_query($query);
                }
	foreach ($crypt_tables as $table) {
		$query="UPDATE ".table($table)." SET participant_id_crypt='".unix_crypt($new_id)."' WHERE participant_id='".$new_id."'";
		$done=mysql_query($query);
	}
	$query="UNLOCK TABLES";
	$done=mysql_query($query);
	return $new_id;
}

// participant form entries

function participant__form_fname() {
	global $lang;
	tpr("fname");
	echo '<TD>'.$lang['firstname'].':</TD>
	      <TD><INPUT name=fname type=text size=40 maxlength=50 value="'.$_REQUEST['fname'].'"></TD>
	     </TR>';
}

function participant__check_exist($varname,$required) {
global $errors__dataform;
        if ($required=="y") {
                if (!(isset($_REQUEST[$varname]) && $_REQUEST[$varname])) 
                	$errors__dataform[]=$varname;
        }
}

function participant__check_fname($required) {
	participant__check_exist("fname",$required);
}

function participant__form_lname() {
	global $lang;
	tpr("lname");
	echo '<TD>'.$lang['lastname'].':</TD>
	      <TD><INPUT name=lname type=text size=40 maxlength=50 value="'.$_REQUEST['lname'].'"></TD>
	      </TR>';
}

function participant__check_lname($required) {
        participant__check_exist("lname",$required);
}


function participant__form_email() {
	global $lang;
	tpr("email");
	echo '<TD>'.$lang['e-mail-address'].':</TD>
	      <TD><INPUT name=email type=text size=40 maxlength=100 value="'.$_REQUEST['email'].'"></TD>
	     </TR>';
}

function participant__check_email($required) {
	participant__check_exist("email",$required);
	global $errors__dataform, $lang;
	if ($_REQUEST['email']) {
		$isok=eregi("^[^@ \t\r\n]+@[-_0-9a-zA-Z]+\\.[^@ \t\r\n]+$",$_REQUEST['email']);
		if (!$isok) {
			$errors__dataform[]="email";
			message($lang['email_address_not_ok']);
		}
	}
}

function participant__form_language($required,$admin) {
	global $lang, $settings, $authdata;
	$part_langs=lang__get_part_langs();
        if (count($part_langs)>1) {
		$lang_names=lang__get_language_names();
		if ($_REQUEST['language']) $current_lang=$_REQUEST['language'];
			elseif ($admin) $current_lang=$settings['public_standard_language'];
			else $current_lang=$authdata['language'];
		tpr("language");
		echo '<TD>'.$lang['language'].':</TD>
			<TD><SELECT name="language">';
			foreach ($part_langs as $language) {
				echo '<OPTION value="'.$language.'"';
				if ($language==$current_lang) echo ' SELECTED';
				echo '>'.$lang_names[$language].'</OPTION>
					';
				}
		echo '</TD>
			</TR>';
		}
}

function participant__form_phone_number() {
	global $form__type, $lang;
	tpr("phone_number");
	echo '<TD>'.$lang['phone_number'].':';
	if (!ereg("admin",$form__type)) 
		echo '<BR><FONT class="small">'.$lang['phone_number_remark'].'</FONT>';
	echo '</TD>
	      <TD><INPUT name=phone_number type=text size=20 maxlength=30 value="'.$_REQUEST['phone_number'].'"></TD>
	     </TR>';
}

function participant__check_phone_number($required) {
	participant__check_exist("phone_number",$required);
}



function participant__form_address() {
	global $lang;
	tpr("address");
	echo '<TD>'.$lang['address'].':
	      </TD>
	     <TD>'.$lang['street'].': <INPUT name=address_street type=text size=25 maxlength=50 value="'.
			$_REQUEST['address_street'].'"></TD>
	    </TR>';
	tpr("address");
	echo '<TD></TD>
	      <TD>'.$lang['zip'].': <INPUT name=address_zip type=text size=5 maxlength=8 value="'.
			$_REQUEST['address_zip'].'">&nbsp;&nbsp;
	      '.$lang['city'].': <INPUT name=address_city type=text size=20 maxlength=50 value="'.
			$_REQUEST['address_city'].'"></TD>
	     </TR>';
	tpr("address");
	echo '<TD></TD><TD>'.$lang['country'].': 
	      <INPUT name=address_country type=text size=20 maxlength=50 value="'.$_REQUEST['address_country'].'"></TD>
	     </TR>';
}

function participant__check_address($required) {
global $errors__dataform;
	if ($required=="y") {
        	if (!($_REQUEST['address_street'] && $_REQUEST['address_zip'] && $_REQUEST['address_city'] && $_REQUEST['address_country']))
                	$errors__dataform[]="address";
	}
}


function participant__form_gender() {
	global $lang;
	tpr("gender");
	echo '<TD>'.$lang['gender'].':</TD>
	<TD>
	<INPUT name=gender type=radio value="m"';
		if ($_REQUEST['gender']=="m") echo " CHECKED";
	echo '>'.$lang['gender_m'].'
	&nbsp;&nbsp;&nbsp;
        <INPUT name=gender type=radio value="f"';
		if ($_REQUEST['gender']=="f") echo " CHECKED";
        echo '>'.$lang['gender_f'].'
	</TD>
	</TR>';
}

function participant__check_gender($required) {
	participant__check_exist("gender",$required);
}


function participant__form_profession() {
	global $lang;
	tpr("profession");
	echo '<TD>'.$lang['profession'].':</TD>
	 	<TD>';
	select__profession($_REQUEST['profession'],"profession");
	echo '</TD>
	     </TR>';
}

function participant__form_field_of_studies() {
	global $lang;
	tpr("field_of_studies");
	echo '<TD>'.$lang['studies'].':</TD>
	      <TD>';
	select__field_of_studies($_REQUEST['field_of_studies'],"field_of_studies");
	echo '</TD>
	      </TR>';
}


function participant__form_begin_of_studies() {
	global $lang;
	tpr("begin_of_studies");
	echo '<TD>'.$lang['begin_of_studies'].':</TD><TD>';

	select__begin_of_studies($_REQUEST['begin_of_studies'],"begin_of_studies");
	echo '
		</TD></TR>';
}

function participant__check_work($required) {
	global $subpool, $errors__dataform, $lang;
	if ($required=="y") {

  		if ($subpool['subpool_type']=="w" && $_REQUEST['profession']==0) 
                	$errors__dataform[]="profession";

  		if ($subpool['subpool_type']=="s" && $_REQUEST['field_of_studies']==0)
                	$errors__dataform[]="field_of_studies";
		if ($subpool['subpool_type']=="s" && !$_REQUEST['begin_of_studies'])
                        $errors__dataform[]="begin_of_studies";

  		if ($subpool['subpool_type']=="b") {
        		if ($_REQUEST['profession']==0 && $_REQUEST['field_of_studies']==0)
                		$errors__dataform[]="studies_prof_admin";
			if ( (!($_REQUEST['field_of_studies'] == 0)) && (!$_REQUEST['begin_of_studies']))
				$errors__dataform[]="begin_of_studies";
			} 
		}

	if ($_REQUEST['profession'] != 0 && $_REQUEST['field_of_studies'] != 0) {
		$errors__dataform[]="studies_prof_admin";
		message($lang['give_either_profession_or_study']);
		}
}



function participant__form_subpool() {
	global $settings;
	global $lang;
	tpr("subpool");
	echo '<TD>
		'.$lang['subpool'].'
	      </TD><TD>';
	if ($_REQUEST['subpool_id']) $selsub=$_REQUEST['subpool_id']; else $selsub=$settings['subpool_default_registration_id'];
	subpools__select_field("subpool_id","subpool_id","subpool_name",$selsub,"");
	echo '</TD></TR>';
}

function participant__form_studies_prof_admin() {
	global $lang;
	tpr("studies_prof_admin");
	echo '<TD></TD>
        	<TD>
		<TABLE width=100% border=0>
		<TR><TD>
		'.$lang['studies'].'<BR>';
	select__field_of_studies($_REQUEST['field_of_studies'],"field_of_studies");
	echo '</TD><TD align=center>&nbsp;'.$lang['or'].'&nbsp;</TD><TD>
	       '.$lang['profession'].'<BR>';
        select__profession($_REQUEST['profession'],"profession");
        echo '</TD>
             </TR>';
	tpr("begin_of_studies");
	echo '<TD>
		'.$lang['begin_of_studies'].'<BR>';
	select__begin_of_studies($_REQUEST['begin_of_studies'],"begin_of_studies");
	echo '</TD><TD colspan=2></TD></TR>
		</TABLE>
	    </TD></TR>';
}

function participant__form_invitations($admin=false) {
	global $lang, $subpool;
	tpr("subscriptions");
	echo '<TD valign=top>';
	if ($admin) echo $lang['invitations']; else echo $lang['i_want_invitations_for'];
	echo ':</TD>
		<TD>';

	participant__subscription_checkboxes('invitations',$subpool['subpool_id'],$_REQUEST['invitations']);
        echo ' <BR><BR>
		</TD></TR>';
}

function participant__check_invitations($required) {
	if (!is_array($_REQUEST['invitations'])) $_REQUEST['invitations']=array();
	$_REQUEST['subscriptions']=implode(",",$_REQUEST['invitations']);
        participant__check_exist("subscriptions",$required);
}

function participant__form_rules_signed() {
	global $lang;
	echo '<TR><TD>';
	echo $lang['rules_signed'];
	echo '</TD>
		<TD>
		<input type=radio name=rules_signed value="y"';
		if ($_REQUEST['rules_signed']=="y") echo " CHECKED";
		echo '>'.$lang['yes'].'
			&nbsp;&nbsp;&nbsp;
		<input type=radio name=rules_signed value="n"';
		if ($_REQUEST['rules_signed']!="y") echo " CHECKED";
		echo '>'.$lang['no'].'
		</TD>
		</TR>';
}

function participant__form_remarks() {
	global $lang;
	echo '<TR><TD>'.$lang['remarks'].'</TD>
		<TD>
		<TEXTAREA name=remarks rows=3 cols=30 wrap=virtual>';
		echo $_REQUEST['remarks'];
		echo '</textarea>
			</TD></TR>';
}


function participant__form_add_to_session() {
	global $lang;
	echo '<TR><TD colspan=2>&nbsp;</TD></TR><TR><TD>
		<INPUT type=checkbox name=register_session value="y"';
			if ($_REQUEST['register_session']=="y") echo " CHECKED";
	echo '>'.$lang['register_sub_for_session'].'
		</TD><TD>';
	select__sessions($_REQUEST['session_id'],"session_id","",true);
	echo '</TD></TR>';
}

function tpr($fieldname) {
global $errors__dataform, $color;
  if (!isset($errors__dataform)) $errors__dataform=array();
  echo '<TR';
  if (in_array($fieldname,$errors__dataform)) 
                echo ' bgcolor="'.$color['missing_field'].'"';
  echo '>';
}

// Create entry form
function participant__form($form_title="",$button_title="",$form_type="") {
	global $lang, $subpool, $settings;

	$admin=ereg("admin",$form_type);

        if (!$_REQUEST['subpool_id']) 
		$_REQUEST['subpool_id']=$settings['subpool_default_registration_id'];

	$subpool=orsee_db_load_array("subpools",$_REQUEST['subpool_id'],"subpool_id");

	if (!$subpool['subpool_id']) $subpool=orsee_db_load_array("subpools",1,"subpool_id");

	echo '<CENTER><BR>
		<h4>'.$form_title.'</h4>
		';
	show_message();

	echo '<FORM action="'.thisdoc().'">';

	if ($admin)  
		echo '<INPUT type=hidden name=participant_id value="'.$_REQUEST['participant_id'].'">';
	   else echo '<INPUT type=hidden name=p value="'.unix_crypt($_REQUEST['participant_id']).'">';


	echo '<INPUT type=hidden name=s value="'.$_REQUEST['s'].'">';
	echo '<INPUT type=hidden name=dr value="'.$_REQUEST['dr'].'">';

	if (!$admin) 
		echo '<INPUT type=hidden name=subpool_id value="'.$_REQUEST['subpool_id'].'">';

	echo '<TABLE width=90%>';

	participant__form_fname();
	participant__form_lname();
	participant__form_email();
	//participant__form_address();

	participant__form_language(true,$admin);

	if ($admin) participant__form_subpool();

	participant__form_invitations($admin);

	if (!$admin) 
		echo '<TR><TD></TD><TD>'.$lang['optional_fields_follow'].'</TD></TR>';

	participant__form_phone_number();

	participant__form_gender();

	if (!$admin) {

		switch ($subpool['subpool_type']) {
			case "w": participant__form_profession(); 
				  break;
			case "s": participant__form_field_of_studies();
				  participant__form_begin_of_studies();
				  break;
			case "b": participant__form_studies_prof_admin();
				  break;
			}
		}
	   else {
		participant__form_studies_prof_admin();
		}

	if (!$admin) echo '<TR><TD colspan=2>&nbsp;</TD></TR>';

	if ($admin) {
		participant__form_rules_signed();
		participant__form_remarks();
		}

	if (ereg("admin-add",$form_type)) participant__form_add_to_session();

	echo '</TABLE>';

	echo '<TABLE>
		<TR><TD COLSPAN=2><INPUT name=add type=submit 
		value="'.$button_title.'">
		</TD></TR>
		</table>

		</FORM>
		</CENTER>';
}



function participant__get_profession($profession_id,$language="") {
     if (!$language){
	global $lang;
	$language=$lang['lang'];
	}
     $query="SELECT * from ".table('lang')." WHERE content_type='profession' AND content_name='".$profession_id."'";
     $prof=orsee_query($query);
     return $prof[$language];
}


function participant__get_field_of_studies($studies_id,$language="") {
     if (!$language){
        global $lang;
        $language=$lang['lang'];
        }
     $query="SELECT * from ".table('lang')." WHERE content_type='field_of_studies' AND content_name='".$studies_id."'";
     $stud=orsee_query($query);
     return $stud[$language];
}


function participant__deleted($pid) {
       $query="SELECT deleted
               FROM ".table('participants')." 
               WHERE participant_id='".$pid."'";
	$line=orsee_query($query);
        if ($line['deleted']=="y") $result=true; else $result=false;
	return $result;
}

function participant__excluded($pid) {
        $query="SELECT excluded
                FROM ".table('participants')." 
                WHERE participant_id='".$pid."'";
        $line=orsee_query($query);
        if ($line['excluded']=="y") $result=true; else $result=false;
        return $result;
}

function participant__exclude_participant($participant) {
	global $settings, $lang;

	if ($lang['lang']) $notice=$lang['automatic_exclusion_by_system_due_to_noshows'];
		else $notice=load_language_symbol('automatic_exclusion_by_system_due_to_noshows',$sesstings['admin_standard_language']);

	$notice=$participant['remarks']."\n".$notice.' '.$participant['number_noshowup'];

        $query="UPDATE ".table('participants')."
		SET excluded='y', deleted='y',
		remarks='".mysql_escape_string($notice)."' 
                WHERE participant_id='".$participant['participant_id']."'";
        $done=mysql_query($query);

	$result='excluded';

	if ($settings['automatic_exclusion_inform']=='y') {
		$done=experimentmail__send_participant_exclusion_mail($participant);
		$result='informed';
		}

        return $result;
}



function participants__get_statistics($participant_id) {
	global $lang;

	echo '<TABLE width=90%>
		<TR>
			<TD>
				'.$lang['part_statistics_for_lab_experiments'].'
			</TD>
		</TR>';
	echo '  <TR>
			<TD>';
			participants__stat_laboratory($participant_id);
	echo '		</TD>
		</TR>
                <TR>
                        <TD>
                                '.$lang['part_statistics_for_online_surveys'].'
                        </TD>
                </TR>';
        echo '  <TR>
                        <TD>';
                        participants__stat_online_survey($participant_id);
        echo '          </TD>
                </TR>';
	echo '</TABLE>';

}

function participants__stat_laboratory($participant_id) {
	global $lang, $color;

	$query="SELECT *
		FROM ".table('experiments').", ".table('sessions').", ".table('participate_at')."
        	WHERE ".table('participate_at').".session_id=".table('sessions').".session_id 
		AND ".table('experiments').".experiment_id=".table('participate_at').".experiment_id
		AND participant_id = '".$participant_id."'
		AND experiment_type='laboratory'
		GROUP BY ".table('experiments').".experiment_id
      		ORDER BY registered, session_finished, session_start_year DESC, session_start_month DESC, session_start_day DESC,
                 	session_start_hour DESC, session_start_minute DESC";
	$result=mysql_query($query) or die("Database error: " . mysql_error());

	$now=time();

	$shade=false;

	echo '<TABLE width=90% border=0>';
	echo '<TR>
		<TD>
			'.$lang['experiment'].'
		</TD>
		<TD>
			'.$lang['type'].'
		</TD>
		<TD>
			'.$lang['date_and_time'].'
		</TD>
		<TD>
			'.$lang['registered'].'
		</TD>
		<TD>
			'.$lang['location'].'
		</TD>
		<TD>
			'.$lang['shownup'].'
		</TD>
		<TD>
			'.$lang['participated'].'
		</TD>
	     </TR>';

	while ($p=mysql_fetch_assoc($result)) {
		$last_reg_time=0;
		if ($p['registered']!='y') $last_reg_time=sessions__get_registration_end("","",$p['experiment_id']);
		if ($p['registered']=='y' || $last_reg_time > $now) {
			echo '<TR';
				if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"'; 
						else echo ' bgcolor="'.$color['list_shade2'].'"';
			echo '>
				<TD>
					<A href="experiment_show.php?experiment_id='.$p['experiment_id'].'">'.
						$p['experiment_name'].'</A>
				</TD>
				<TD>
					'.$p['experiment_ext_type'].'
				</TD>
				<TD>';
				if ($p['registered']=='y')
					echo '<A HREF="experiment_participants_show.php?experiment_id='.
                				$p['experiment_id'].'&focus=registered&session_id='.
                				$p['session_id'].'">'.session__build_name($p).'</A>';
				   else echo $lang['from'].' '.sessions__get_first_date($p['experiment_id']).' '.
                        			$lang['to'].' '.sessions__get_last_date($p['experiment_id']);
			echo '	</TD>
				<TD>';
				if ($p['registered']=='y') echo $lang['yes']; else echo $lang['no'];
			echo '	</TD>
				<TD>';
				if ($p['registered']=='y') echo laboratories__get_laboratory_name($p['laboratory_id']);
                                        else echo '-';
			echo '</TD>
				<TD>';
				if ($p['registered']=='y') {
					if ($p['session_finished']=='y') {
							if ($p['shownup']=='n')
								echo '<FONT color="'.$color['shownup_no'].'">'.$lang['no'].'</FONT>';
							   else echo '<FONT color="'.$color['shownup_yes'].'">'.$lang['yes'].'</FONT>';
							}
						else echo $lang['three_questionmarks'];
					}
				   else echo '-';
			echo '	</TD>
				<TD>';
				if ($p['registered']=='y') {
                                        if ($p['session_finished']=='y') {
							if ($p['participated']=='y') echo $lang['yes']; else echo $lang['no'];
						}
                                                else echo $lang['three_questionmarks'];
                                        }
				   else echo '-';
			echo '	</TD>
			      </TR>';
			if ($shade) $shade=false; else $shade=true;
			}
		}
	echo '</TABLE>';
}

function participants__stat_online_survey($participant_id) {
	global $lang, $color;

	$query="SELECT *
      		FROM ".table('experiments').", ".table('participate_at').", ".table('os_properties')." 
        	WHERE ".table('experiments').".experiment_id=".table('participate_at').".experiment_id
		AND participant_id = '".$participant_id."'
		AND ".table('os_properties').".experiment_id = ".table('experiments').".experiment_id
		AND experiment_type='online-survey'
      		ORDER BY participated, start_year, start_month, start_day,
                 	start_hour, start_minute";
	$result=mysql_query($query) or die("Database error: " . mysql_error());

        $now=time();

        $shade=false;

        echo '<TABLE width=90% border=0>';
        echo '<TR>
                <TD>
                        '.$lang['experiment'].'
                </TD>
                <TD>
                        '.$lang['type'].'
                </TD>
                <TD>
                        '.$lang['date_and_time'].'
                </TD>
                <TD>
                        '.$lang['participated'].'
                </TD>
		<TD>
                        '.$lang['finished'].'
                </TD>
             </TR>';

        while ($p=mysql_fetch_assoc($result)) {
		$survey_end_time=survey__get_stop_unixtime("",$p);
		if ($p['participated']=='y' || ($survey_end_time && $survey_end_time > $now)) {
			echo '<TR';
                                if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"'; 
					else echo ' bgcolor="'.$color['list_shade2'].'"';
                        echo '>
                                <TD>
                                        <A href="experiment_show.php?experiment_id='.$p['experiment_id'].'">'.
                                                $p['experiment_name'].'</A>
                                </TD>
                                <TD>
                                        '.$p['experiment_ext_type'].'
                                </TD>
                                <TD>
					'.$lang['from'].' '.survey__print_start_time($p['experiment_id'],true).' '.
                        		$lang['to'].' '.survey__print_stop_time($p['experiment_id'],true).'
                        	</TD>
                                <TD>';
                                if ($p['participated']=='y') echo $lang['yes']; else echo $lang['no'];
                        echo '  </TD>
                                <TD>';
                                if ($p['participated']=='y') echo 'finished?';
                                        else echo '-';
                        echo '  </TD>
                              </TR>';
                        if ($shade) $shade=false; else $shade=true;
                        }
                }
	echo '</TABLE>';
}

?>
