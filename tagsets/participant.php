<?php
// part of orsee. see orsee.org

function participants__count_participants($constraint="",$inc_deleted=false) {
	if ($constraint) $where_clause=" AND ".$constraint; else $where_clause="";
     $query="SELECT COUNT(participant_id) as pcount
      	FROM ".table('participants')." 
    	WHERE participant_id IS NOT NULL ";
    if (!$inc_deleted) $query.=" AND deleted='n' ";
    $query.=$where_clause;
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
		remarks='".mysqli_real_escape_string($GLOBALS['mysqli'],$notice)."' 
                WHERE participant_id='".$participant['participant_id']."'";
        $done=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));

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
	echo '	     </TD>
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
	$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));

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

	while ($p=mysqli_fetch_assoc($result)) {
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



// Create unique participant id
function participant__create_participant_id() {
           $gibtsschon=true;
	   srand ((double)microtime()*1000000);
           while ($gibtsschon) {
           	$crypt_id = "/";
		while (preg_match("/(\/|\\.)/",$crypt_id)) {
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
          	}
	return $participant_id; 
}

// create new id and replace old id with it
function participant__create_new_participant_id($participant_id) {
	//participants,participants_temp
	$tables=array(  "participants"=>"participant_id",
			"participants_temp"=>"participant_id",
			"mail_queue"=>"mail_recipient",
			"participate_at"=>"participant_id",
			);
	$crypt_tables=array("participants","participants_temp");
	foreach ($tables as $table=>$column) {
		$query="LOCK TABLES ".table($table);
		$done=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
		}
	$new_id=participant__create_participant_id();
	foreach ($tables as $table=>$column) {
                $query="UPDATE ".table($table)." SET ".$column."='".$new_id."' WHERE ".$column."='".$participant_id."'";
                $done=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
                }
	foreach ($crypt_tables as $table) {
		$query="UPDATE ".table($table)." SET participant_id_crypt='".unix_crypt($new_id)."' WHERE participant_id='".$new_id."'";
		$done=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
	}
	$query="UNLOCK TABLES";
	$done=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
	return $new_id;
}



// CHECKS
// check unique
function participantform__check_unique($edit,$formtype,$participant_id=0) {
	global $lang, $settings, $errors__dataform;
	
	$disable_form=false; $problem=false; $nonunique_fields=array();
    if (!isset($edit['subpool_id']) || !$edit['subpool_id']) $edit['subpool_id']=$settings['subpool_default_registration_id'];
	$subpool=orsee_db_load_array("subpools",$edit['subpool_id'],"subpool_id");
	if (!$subpool['subpool_id']) $subpool=orsee_db_load_array("subpools",1,"subpool_id");
	$edit['subpool_id']=$subpool['subpool_id'];

	$nonunique=participantform__get_nonunique($edit,$participant_id);	

	if ($formtype=='create') {
		foreach ($nonunique as $f) {
			// conditions for check: 1) subpool must fit, 2) must be a public field, 3) form not yet disabled, 4) requires unique in creation form
			if(($f['subpools']=='all' || in_array($subpool['subpool_id'],explode(",",$f['subpools']))) && 
				($f['admin_only']!='y') &&
				$disable_form==false && 
				$f['require_unique_on_create_page']=='y'
					) {
			
				if ($f['nonunique_participants']) {
					$errors__dataform[]=$f['mysql_column_name'];
					$deleted=participant__deleted($f['nonunique_participants_list'][0]);
					$excluded=participant__excluded($f['nonunique_participants_list'][0]);

					if($excluded && $f['unique_on_create_page_tell_if_deleted']=='y') {
						message($lang['error_sorry_you_are_excluded']);
						message($lang['if_you_have_questions_write_to'].' '.support_mail_link());
						$disable_form=true;
					} elseif($deleted && $f['unique_on_create_page_tell_if_deleted']=='y') {
						message($lang['error_sorry_you_are_deleted']);
						message($lang['if_you_have_questions_write_to'].' '.support_mail_link());
						$disable_form=true;		
					} else {
						$problem=true;
						if (isset($lang[$f['unique_on_create_page_error_message_if_exists_lang']])) message($lang[$f['unique_on_create_page_error_message_if_exists_lang']]);
						else message($f['unique_on_create_page_error_message_if_exists_lang']);
						if($f['unique_on_create_page_email_regmail_confmail_again']=='y') {
							message($lang['message_with_edit_link_mailed']);
							$done=experimentmail__mail_edit_link($f['nonunique_participants_list'][0]);
							$disable_form=true;	
						}
					}
				} elseif($f['nonunique_participants_temp']) {
						$problem=true;
						$errors__dataform[]=$f['mysql_column_name'];
						if (isset($lang[$f['unique_on_create_page_error_message_if_exists_lang']])) message($lang[$f['unique_on_create_page_error_message_if_exists_lang']]);
						else message($f['unique_on_create_page_error_message_if_exists_lang']);
						if($f['unique_on_create_page_email_regmail_confmail_again']=='y') {
							message($lang['already_registered_but_not_confirmed'].' '.$lang['confirmation_message_mailed_again']);
							$done=experimentmail__confirmation_mail($f['nonunique_participants_temp_list'][0]);
							$disable_form=true;
						}
				}
			}
		}
	$response=array(); $response['disable_form']=$disable_form; $response['problem']=$problem;
	return $response;
	} elseif ($formtype=='edit') {
		foreach ($nonunique as $f) {
			var_dump($f);
			// conditions for check: 1) subpool must fit, 2) must be a public field, 3) requires unique in edit form
			if(($f['subpools']=='all' || in_array($subpool['subpool_id'],explode(",",$f['subpools']))) && 
				($f['admin_only']!='y') && $f['check_unique_on_edit_page']=='y') {
			
				if ($f['nonunique_participants'] || $f['nonunique_participants_temp']) {
					$errors__dataform[]=$f['mysql_column_name'];
					$problem=true;
					if (isset($lang[$f['unique_on_edit_page_error_message_if_exists_lang']])) message($lang[$f['unique_on_edit_page_error_message_if_exists_lang']]);
					else message($f['unique_on_edit_page_error_message_if_exists_lang']);
				} 
			}
		}
	}
	$response=array(); $response['problem']=$problem;
	return $response;
}


function participantform__get_nonunique($edit,$participant_id=0) {
	$nonunique_fields=array(); 
	$formfields=participantform__load();
	foreach ($formfields as $f) { 
	$f['nonunique_participants']=false; $f['nonunique_participants_list']=array();
	$f['nonunique_participants_temp']=false; $f['nonunique_participants_temp_list']=array();
	if(($f['require_unique_on_create_page']=='y' || $f['check_unique_on_edit_page']=='y') &&
		(isset($edit[$f['mysql_column_name']]) && $edit[$f['mysql_column_name']]) ) { 
		$query="SELECT participant_id FROM ".table('participants')." 
           		WHERE ".$f['mysql_column_name']."='".mysqli_real_escape_string($GLOBALS['mysqli'],$edit[$f['mysql_column_name']])."'";
        if ($participant_id) $query.=" AND participant_id!='".mysqli_real_escape_string($GLOBALS['mysqli'],$participant_id)."'";
		$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']).", Query=".$query);
       	while ($line = mysqli_fetch_assoc($result)) $f['nonunique_participants_list'][]=$line['participant_id'];
		if (count($f['nonunique_participants_list'])>0) $f['nonunique_participants']=true;
		else {
			$query="SELECT participant_id FROM ".table('participants_temp')." 
           			WHERE ".$f['mysql_column_name']."='".mysqli_real_escape_string($GLOBALS['mysqli'],$edit[$f['mysql_column_name']])."'";
           	if ($participant_id) $query.=" AND participant_id!='".mysqli_real_escape_string($GLOBALS['mysqli'],$participant_id)."'";
			$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']).", Query=".$query);
       		while ($line = mysqli_fetch_assoc($result)) $f['nonunique_participants_temp_list'][]=$line['participant_id'];
			if (count($f['nonunique_participants_temp_list'])>0) $f['nonunique_participants_temp']=true;
		}
		if ($f['nonunique_participants'] || $f['nonunique_participants_temp']) $nonunique_fields[$f['mysql_column_name']]=$f; 	
	}}
	return $nonunique_fields;
}


// check fields
function participantform__check_fields($edit,$admin) {
	global $lang, $settings;
	$errors_dataform=array();
	
    if (!isset($edit['subpool_id']) || !$edit['subpool_id']) $edit['subpool_id']=$settings['subpool_default_registration_id'];
	$subpool=orsee_db_load_array("subpools",$edit['subpool_id'],"subpool_id");
	if (!$subpool['subpool_id']) $subpool=orsee_db_load_array("subpools",1,"subpool_id");
	$edit['subpool_id']=$subpool['subpool_id'];
	
	$formfields=participantform__load();
	foreach ($formfields as $f) { 
	if($f['subpools']=='all' | in_array($subpool['subpool_id'],explode(",",$f['subpools']))) {
		if ($f['type']=='invitations') {
			if (!isset($_REQUEST[$f['mysql_column_name']]) || !is_array($_REQUEST[$f['mysql_column_name']])) $_REQUEST[$f['mysql_column_name']]=array();
			$_REQUEST[$f['mysql_column_name']]=implode(",",$_REQUEST[$f['mysql_column_name']]);
			$edit[$f['mysql_column_name']]=$_REQUEST[$f['mysql_column_name']];
  		}
		if ($admin || $f['admin_only']!='y') {
			if ($f['compulsory']=='y') {
				if(!isset($edit[$f['mysql_column_name']]) || !$edit[$f['mysql_column_name']]) {
					$errors_dataform[]=$f['mysql_column_name'];
					if (isset($lang[$f['error_message_if_empty_lang']])) message($lang[$f['error_message_if_empty_lang']]);
					else message($f['error_message_if_empty_lang']);
				}
			}
			if ($f['perl_regexp']!='') {
				if(!preg_match($f['perl_regexp'],$edit[$f['mysql_column_name']])) {
					$errors_dataform[]=$f['mysql_column_name'];
					if (isset($lang[$f['error_message_if_no_regexp_match_lang']])) message($lang[$f['error_message_if_no_regexp_match_lang']]);
					else message($f['error_message_if_no_regexp_match_lang']);
				}
			}
		}
	}}
	return $errors_dataform;
}



// form fields
function participantform__allvalues() {
$array=array(
'admin_only'=>'n',
'allow_sort_in_session_participants_list'=>'n',
'check_unique_on_edit_page'=>'n',
'cols'=>'40',
'compulsory'=>'n',
'default_value'=>'',
'error_message_if_empty_lang'=>'',
'error_message_if_no_regexp_match_lang'=>'',
'include_in_statistics'=>'n',
'include_none_option'=>'n',
'link_as_email_in_lists'=>'n',
'list_in_session_participants_list'=>'n',
'list_in_session_pdf_list'=>'n',
'maxlength'=>'100',
'option_values_lang'=>'',
'option_values'=>'',
'perl_regexp'=>'',
'require_unique_on_create_page'=>'n',
'rows'=>'3',
'search_include_in_experiment_assign_query'=>'n',
'search_include_in_participant_query'=>'n',
'search_result_allow_sort'=>'n',
'search_result_sort_order'=>'',
'searchresult_list_in_experiment_assign_results'=>'n',
'searchresult_list_in_participant_results'=>'n',
'size'=>'40',
'subpools'=>'all',
'unique_on_create_page_email_regmail_confmail_again'=>'n',
'unique_on_create_page_error_message_if_exists_lang'=>'',
'unique_on_create_page_tell_if_deleted'=>'n',
'unique_on_edit_page_error_message_if_exists_lang'=>'',
'value_begin'=>'0',
'value_end'=>'1',
'value_step'=>'0',
'values_reverse'=>'n',
'wrap'=>'virtual'
);
return $array;
}

function participantform__load() {
	$pform=participantform__define();
	foreach ($pform as $k=>$f) {
		$t=participantform__allvalues();	
		foreach ($f as $kf=>$vf) {
			$t[$kf]=$vf;
		}
		$pform[$k]=$t;
	}
	return $pform;
}


// processing the template
function load_form_template($tpl_name,$out) {
	global $lang, $settings__root_to_server, 
		$settings__root_directory, $settings;

	// get the template
        $tpl=file_get_contents($settings__root_to_server.
                                $settings__root_directory.
                                '/ftpl/'.$tpl_name.'.tpl');
	// process conditionals
	$pattern="/\{[^#\}]*#(!?)([^#!\}]+)#([^\}]+)\}/ie";
    $replacement = "($1\$out['$2'])?\"$3\":''";
    $tpl=preg_replace($pattern, $replacement, $tpl);

	// fill in the vars
	foreach ($out as $k=>$o) $tpl=str_replace("#".$k."#",$o,$tpl);

	// fill in language terms
        $pattern="/lang\[([^\]]+)\]/ie";
        $replacement = "\$lang['$1']";
        //$replacement = "(isset(\$lang['$1']))?\"\$lang['$1']\":\"$1\"";  
        $tpl=preg_replace($pattern, $replacement, $tpl);
                                        
	return $tpl;
}


function form__replace_funcs_in_field($f) {
	global $lang, $settings;
    foreach ($f as $o=>$v) {
		if (substr($f[$o],0,5)=='func:') eval('$f[$o]='.substr($f[$o],5).';');
    }
	return $f;
}


// generic fields
function form__render_field($f) {
	$out='';
	switch($f['type']) {
		case 'textline': $out=form__render_textline($f); break;
		case 'textarea': $out=form__render_textarea($f); break;
		case 'radioline': $out=form__render_radioline($f); break;
		case 'select_list': $out=form__render_select_list($f); break;
		case 'select_numbers': $out=form__render_select_numbers($f); break;
		case 'select_lang': $out=form__render_select_lang($f); break;
	}
	return $out;
}

function form__render_textline($f) {
	$out='<INPUT type="text" name="'.$f['mysql_column_name'].'" value="'.$f['value'].'" size="'.
		$f['size'].'" maxlength="'.$f['maxlength'].'">';
	return $out;
}

function form__render_textarea($f) {
        $out='<textarea name="'.$f['mysql_column_name'].'" cols="'.$f['cols'].'" rows="'.
                $f['rows'].'" wrap="'.$f['wrap'].'">'.$f['value'].'</textarea>';
        return $out;
}

function form__render_radioline($f) {
	global $lang;
	$optionvalues=explode(",",$f['option_values']);
	$optionnames=explode(",",$f['option_values_lang']);
	$items=array();
	foreach($optionvalues as $k=>$v) {
		if (isset($optionnames[$k])) $items[$v]=$optionnames[$k];
	}
	$out='';
	foreach ($items as $val=>$text) {
		$out.='<INPUT name="'.$f['mysql_column_name'].'" type="radio" value="'.$val.'"';
        if ($f['value']==$val) $out.=" CHECKED";
       	$out.='>';
       	if (isset($lang[$text])) $out.=$lang[$text]; else $out.=$text;
       	$out.='&nbsp;&nbsp;&nbsp;';
	}
    return $out;
}

function form__render_select_list($f) {
	global $lang;
	$optionvalues=explode(",",$f['option_values']);
	$optionnames=explode(",",$f['option_values_lang']);
	if ($f['include_none_option']=='y') $incnone=true; else $incnone=false;
	$items=array();
	foreach($optionvalues as $k=>$v) {
		if (isset($optionnames[$k])) $items[$v]=$optionnames[$k];
	}
	$out='';
	$out=helpers__select_text($items,$f['mysql_column_name'],$f['value'],$incnone);
    return $out;
}

function form__render_select_lang($f) {
	if ($f['include_none_option']=='y') $incnone=true; else $incnone=false;
	$out=language__selectfield_item($f['mysql_column_name'],$f['mysql_column_name'],$f['value'],$incnone);
	return $out;
}


function form__render_select_numbers($f) {
		if ($f['include_none_option']=='y') $incnone=true; else $incnone=false;
		if ($f['values_reverse']=='y') $reverse=true; else $reverse=false;
        $out=participant__select_numbers($f['mysql_column_name'],$f['value'],$f['value_begin'],$f['value_end'],0,$f['value_step'],$reverse,$incnone);
        return $out;
}

// special fields
function participant__invitations_form_field($subpool_id,$varname,$value) {
	global $settings, $lang;
	if (!$subpool_id) $subpool_id=$settings['subpool_default_registration_id'];
    $checked=explode(",",$value);
    $query="SELECT *
            FROM ".table('experiment_types')." as texpt, 
            ".table('lang')." as tlang, ".table('subpools')." as tsub
            WHERE texpt.exptype_id=tlang.content_name
            AND tlang.content_type='experiment_type'
            AND texpt.enabled='y'
			AND tsub.experiment_types LIKE concat('%',texpt.exptype_name ,'%') 
			AND tsub.subpool_id='".$subpool_id."' 
            ORDER BY exptype_id";
    $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
    $out='';
    while ($line = mysqli_fetch_assoc($result)) {
    	$out.='<INPUT type="checkbox" name="'.$varname.'['.$line['exptype_name'].']"
                                        value="'.$line['exptype_name'].'"';
        if (in_array($line['exptype_name'],$checked)) $out.=" CHECKED";
        $out.='>'.$line[$lang['lang']];
        $out.='<BR>
                     ';
    }
    return $out;
}

function participant__rules_signed_form_field($current_rules_signed="") {
	global $lang;
	$out='<input type=radio name=rules_signed value="y"';
	if ($current_rules_signed=="y") $out.=" CHECKED";
        $out.='>'.$lang['yes'].'&nbsp;&nbsp;&nbsp;
              <input type=radio name=rules_signed value="n"';
        if ($current_rules_signed!="y") $out.=" CHECKED";
        $out.='>'.$lang['no'];
	return $out;
}

function participant__remarks_form_field($current_remarks="") {
        global $lang;
        $out='<TEXTAREA name=remarks rows=3 cols=70 wrap=virtual>';
        $out.=$current_remarks;
        $out.='</textarea>';
	return $out;
}

function participant__add_to_session_checkbox() {
        $out='<INPUT type=checkbox name=register_session value="y">';
	return $out;
}
function participant__add_to_session_select($participant_id,$session_id="") {
        $out=select__sessions($session_id,"session_id","",true,$participant_id);
	return $out;
}

function participant__select_numbers($name,$prevalue,$begin,$end,$fillzeros=2,$steps=1,$reverse=false,$incnone=false,$existing=false,$where='',$show_count=false) {
	$out='';
	$out.='<select name="'.$name.'">';
	if ($incnone) $out.='<option value="">-</option>';

	if (!$existing) {
		if ($begin<$end) { $lb=$begin; $ub=$end;} else { $lb=$end; $ub=$begin; }
		if ($reverse) $i=$ub; else $i=$lb;
		if ($steps<1) $steps=1;
		while (($reverse==false && $i<=$ub) || ($reverse==true && $i>=$lb)) {
			$out.='<option value="'.$i.'"'; if ($i == (int) $prevalue) $out.=' SELECTED'; $out.='>';
			$out.=helpers__pad_number($i,$fillzeros); $out.='</option>';
			if ($reverse) $i=$i-$steps; else $i=$i+$steps;
		}
	} else {
		$query="SELECT count(*) as tf_count, ".$name." as tf_value 
				FROM ".table('participants')." 
				WHERE ".table('participants').".participant_id IS NOT NULL ";
		if($where) $query.=" AND ".$where." ";
		$query.=" GROUP BY ".$name." 
				  ORDER BY ".$name;
		if ($reverse) $query.=" DESC ";
		$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
		$listitems=array();
        while ($line = mysqli_fetch_assoc($result)) {
			if(!isset($listitems[$line['tf_value']])) $listitems[$line['tf_value']]=$line;
            else $listitems[$line['tf_value']]['tf_count']=$listitems[$line['tf_value']]['tf_count']+$line['tf_count'];
        }
        foreach ($listitems as $line) {
            $out.='<option value="'.$line['tf_value'].'"'; if ($line['tf_value'] == (int) $prevalue) $out.=' SELECTED'; $out.='>';
            if ($line['tf_value']!='') $out.=helpers__pad_number($line['tf_value'],$fillzeros);
            else $out.='-';
            if ($show_count) $out.=' ('.$line['tf_count'].')';
        	$out.='</option>';
        }
                
		
		while ($line = mysqli_fetch_assoc($result)) {
			$out.='<option value="'.$line['tf_value'].'"'; if ($line['tf_value'] == (int) $prevalue) $out.=' SELECTED'; $out.='>';
			$out.=helpers__pad_number($line['tf_value'],$fillzeros); 
			if ($show_count) $out.=' ('.$line['tf_count'].')';
			$out.='</option>';
		}
	}
	$out.='</select>';
	return $out;
}

function participant__select_existing($name,$prevalue,$where='',$show_count=false) {
	$out='';
	$out.='<select name="'.$name.'">';
	$query="SELECT count(*) as tf_count, ".$name." as tf_value 
			FROM ".table('participants')." 
			WHERE ".table('participants').".participant_id IS NOT NULL ";
	if($where) $query.=" AND ".$where." ";
	$query.=" GROUP BY ".$name." 
			  ORDER BY ".$name;
	$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
	while ($line = mysqli_fetch_assoc($result)) {
		$out.='<option value="'.$line['tf_value'].'"'; if ($line['tf_value'] == $prevalue) $out.=' SELECTED'; $out.='>';
		$out.=$line['tf_value']; 
		if ($show_count) $out.=' ('.$line['tf_count'].')';
		$out.='</option>';
	}
	$out.='</select>';
	return $out;
}


// the participant form
function participant__show_form($edit,$button_title="",$form_title="",$errors,$admin=false) {
	global $lang, $subpool, $settings, $color;
	$out=array(); $tout=array();

	echo '<CENTER><BR>
		<h4>'.$form_title.'</h4>
		';
	show_message();
	
	if (!isset($edit['participant_id'])) $edit['participant_id']='';
    if (!isset($edit['subpool_id']) || !$edit['subpool_id']) $edit['subpool_id']=$settings['subpool_default_registration_id'];
	$subpool=orsee_db_load_array("subpools",$edit['subpool_id'],"subpool_id");
	if (!$subpool['subpool_id']) $subpool=orsee_db_load_array("subpools",1,"subpool_id");
	$edit['subpool_id']=$subpool['subpool_id'];
	
	$pools=subpools__get_subpools();
	foreach ($pools as $p) $out['is_subjectpool_'.$p]=false;
	$out['is_subjectpool_'.$subpool['subpool_id']]=true;
	$out['is_subpool_type_w']=false; $out['is_subpool_type_s']=false; $out['is_subpool_type_b']=false;
	$out['is_subpool_type_'.$subpool['subpool_type']]=true;
	if ($admin) { $out['is_admin']=true; $out['is_not_admin']=false; }
	else { $out['is_admin']=false; $out['is_not_admin']=true; }
	
	
	echo '<FORM action="'.thisdoc().'" method="POST">';
	
    if ($admin) echo '<INPUT type="hidden" name="participant_id" value="'.$edit['participant_id'].'">';
	else echo '<INPUT type=hidden name=p value="'.unix_crypt($edit['participant_id']).'">';
	if (!isset($edit['s'])) $edit['s']='';
    if (!isset($edit['dr'])) $edit['dr']='';
    echo '<INPUT type=hidden name=s value="'.$edit['s'].'">';
	echo '<INPUT type=hidden name=dr value="'.$edit['dr'].'">';
	if (!$admin) echo '<INPUT type=hidden name=subpool_id value="'.$edit['subpool_id'].'">'; 

	if ($admin) $nonunique=participantform__get_nonunique($edit,$edit['participant_id']);

	$formfields=participantform__load();

	foreach ($formfields as $f) { 
	if($f['subpools']=='all' | in_array($subpool['subpool_id'],explode(",",$f['subpools']))) {
	
		$f=form__replace_funcs_in_field($f);
		if (isset($edit[$f['mysql_column_name']])) $f['value']=$edit[$f['mysql_column_name']];
		else $f['value']=$f['default_value'];

		if ($f['type']=='language') {
			$part_langs=lang__get_part_langs();
        	if (count($part_langs)>1) {
            	$out['multiple_participant_languages_exist']=true;
            	$tout['multiple_participant_languages_exist']=true;
        	} else {
            	$out['multiple_participant_languages_exist']=false;
            	$tout['multiple_participant_languages_exist']=false;
        	}
        	$field=lang__select_lang($f['mysql_column_name'],$f['value'],$type="part");
		} elseif ($f['type']=='invitations') {
			$field=participant__invitations_form_field($subpool['subpool_id'],$f['mysql_column_name'],$f['value']);
		} else {
			$field=form__render_field($f);
		}
		if ($admin) {
			if (isset($nonunique[$f['mysql_column_name']])) {
				if (isset($lang['not_unique'])) $note=$lang['not_unique'];
						else $note='not unique';
				$link='participants_show.php?new_query=true&deleted=b&use%5B0%5D=true&query_field='.urlencode($f['value']).'&field_bezug='.urlencode($f['mysql_column_name']).'&show=true';
				$field.=' <A HREF="'.$link.'"><FONT color="red">'.$note.'</FONT></A>';
			}		
		}
		
		if ($f['admin_only']=='y') $tout[$f['mysql_column_name']]=$field; else $out[$f['mysql_column_name']]=$field;
		if(in_array($f['mysql_column_name'],$errors)) { 
			$out['error_'.$f['mysql_column_name']]=' bgcolor="'.$color['missing_field'].'"'; 
			$tout['error_'.$f['mysql_column_name']]=' bgcolor="'.$color['missing_field'].'"'; 
		} else { 
			$out['error_'.$f['mysql_column_name']]=''; 
			$tout['error_'.$f['mysql_column_name']]=''; 
		}
	}}
	
	$formoutput=load_form_template('participant_form',$out);

	echo '<table cellspacing=0 cellpadding="10em" border=0 width="90%">';
	echo $formoutput;

	if ($admin) {
		if (isset($edit['participant_id'])) $tout['participant_id']=$edit['participant_id']; else $tout['participant_id']='';
		if (isset($edit['participant_id_crypt'])) $tout['participant_id_crypt']=$edit['participant_id_crypt']; else $tout['participant_id_crypt']='';
		if (isset($edit['creation_time'])) $tout['creation_time']=time__format($lang['lang'],'',false,false,true,false,$edit['creation_time']);  else $tout['creation_time']='';
		if (!isset($edit['rules_signed'])) $edit['rules_signed']='';

		$tout['subpool_id']=subpools__select_field("subpool_id","subpool_id","subpool_name",$edit['subpool_id'],"");
		$tout['rules_signed']=participant__rules_signed_form_field($edit['rules_signed']);
		
		if (!$edit['participant_id']) {
			$tout['is_part_create_form']=true;
			$tout['checkbox_add_to_session']=participant__add_to_session_checkbox();
			$tout['select_add_to_session']=participant__add_to_session_select($edit['participant_id']);
		} else $tout['is_part_create_form']=false;
		
		$adminformoutput=load_form_template('participant_form_admin_addons',$tout);
		echo $adminformoutput;
	}

	if (!$button_title) $button_title=$lang['change'];
	echo '<tr>
			<td colspan=2 align="center">
			<INPUT name="add" type="submit" value="'.$button_title.'">
			</td>
                </tr>';
    echo '</table> </form>';
}
 

function participant__load_result_table_fields($type='search',$tlang='') { // type can be search, assign, session, email
	global $lang;
	if (!$tlang) $tlang=$lang['lang'];
	$formfields=participantform__load(); $result_columns=array();
	foreach ($formfields as $f) {
		if(  ($type=='search' && $f['searchresult_list_in_participant_results']=='y') ||
			 ($type=='assign' && $f['searchresult_list_in_experiment_assign_results']=='y') ||
			 ($type=='session' && $f['list_in_session_participants_list']=='y') ||
			 ($type=='sessionpdf' && $f['list_in_session_pdf_list']=='y') ||
			 ($type=='email' && $f['admin_only']!='y')  ) {
			 if(  ($type=='search' && $f['search_result_allow_sort']=='y') ||
			 	  ($type=='assign' && $f['search_result_allow_sort']=='y') ||
			 	  (($type=='session' || $type=='sessionpdf') && $f['allow_sort_in_session_participants_list']=='y')  ) {
			 	$f['allow_sort']=true;
			 	if ($f['search_result_sort_order']) $f['sort_order']=$f['search_result_sort_order'];
			 	else $f['sort_order']=$f['mysql_column_name'];	
			 } else {
			 	$f['allow_sort']=false;
			 	$f['sort_order']='';
			 }
			if(isset($lang[$f['name_lang']])) $f['column_name']=$lang[$f['name_lang']]; else $f['column_name']=$f['name_lang'];
			if(preg_match("/(radioline|select_list)/",$f['type'])) {
				$optionvalues=explode(",",$f['option_values']);
				$optionnames=explode(",",$f['option_values_lang']);
				$f['lang']=array();
				foreach($optionvalues as $k=>$v) {
					if (isset($optionnames[$k])) {
						if ($tlang!=$lang['lang']) {
							$oname=language__get_item('lang',$optionnames[$k],$tlang);
							if ($oname) $f['lang'][$v]=$oname;
							else $f['lang'][$v]=$optionnames[$k];
						} else {
							if(isset($lang[$optionnames[$k]])) $f['lang'][$v]=$lang[$optionnames[$k]];
							else $f['lang'][$v]=$optionnames[$k];
						}
					}
				}
			
			} elseif ($f['type']=='select_lang') {
				$f['lang']=lang__load_lang_cat($f['mysql_column_name'],$tlang);
			}
			
			$result_columns[]=$f;
		}
	}
	return $result_columns;
}


?>
