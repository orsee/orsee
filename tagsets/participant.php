<?php
// part of orsee. see orsee.org

function participants__count_participants($constraint="",$const_pars=array()) {
    $query="SELECT COUNT(participant_id) as pcount
            FROM ".table('participants');
    if ($constraint) {
        $query.=" WHERE ".$constraint;
    }
    $line=orsee_query($query,$const_pars);
    return $line['pcount'];
}

// check if participant is already active
function participant__participant_get_if_not_confirmed($confirmation_token) {
    $pars=array(':confirmation_token'=>$confirmation_token);
    $query="SELECT participant_id FROM ".table('participants')."
            WHERE confirmation_token= :confirmation_token
            AND status_id=0 ";
    $line=orsee_query($query,$pars);
    if (isset($line['participant_id'])) return $line['participant_id'];
    else return false;
}

function participant__exclude_participant($participant) {
    global $settings, $lang;
    if (lang('lang')) $notice=lang('automatic_exclusion_by_system_due_to_noshows');
    else $notice=load_language_symbol('automatic_exclusion_by_system_due_to_noshows',$settings['admin_standard_language']);
    $remarks=$participant['remarks']."\n".$notice.' '.$participant['number_noshowup'];
    $pars=array(':status_id'=>$settings['automatic_exclusion_to_participant_status'],
                ':deletion_time'=>time(),
                ':remarks'=>$remarks,
                ':participant_id'=>$participant['participant_id']);

    $query="UPDATE ".table('participants')."
            SET status_id=:status_id,
            deletion_time=:deletion_time,
            remarks=:remarks
            WHERE participant_id=:participant_id";
    $done=or_query($query,$pars);
    $result='excluded';
    if ($settings['automatic_exclusion_inform']=='y') {
        $done=experimentmail__send_participant_exclusion_mail($participant);
        $result='informed';
    }
    return $result;
}

function participants__get_statistics($participant_id) {
    global $lang, $color;
    echo '<TABLE style="width: 90%">
        <TR>
            <TD>
                <TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'; width: 100%"><TR>
                        <TD>
                            '.lang('part_statistics_for_lab_experiments').'
                        </TD>
                    </TR></TABLE>
            </TD>
        </TR>';
    echo '  <TR>
            <TD>';
            participants__stat_laboratory($participant_id);
    echo '       </TD>
                </TR>';
    echo '</TABLE>';

}

function participants__stat_laboratory($participant_id) {
    global $lang, $color;

    $exptypes=load_external_experiment_types();

    // all experiments where participant is enroled
    // plus all unfinished experiments where participant is eligible
    // order by session_status, time
    $pars=array(':participant_id'=>$participant_id);
    $query="SELECT ".table('participate_at').".experiment_id as exp_id, ".table('participate_at').".session_id as sess_id, ".table('experiments').".*, ".table('participate_at').".*, ".table('sessions').".*
            FROM ".table('experiments').",  ".table('participate_at')."
            LEFT JOIN ".table('sessions')." ON ".table('participate_at').".session_id=".table('sessions').".session_id
            WHERE ".table('participate_at').".participant_id = :participant_id
            AND ".table('experiments').".experiment_id=".table('participate_at').".experiment_id
            AND experiment_type='laboratory'
            AND ( ".table('experiments').".experiment_finished='n'
                    OR ".table('participate_at').".session_id!=0 )
            ORDER BY if(".table('sessions').".session_id IS NULL,0,1),
            if(session_status='completed' OR session_status='balanced',1,0),
            session_start DESC";
    $result=or_query($query,$pars);
    $now=time();
    $shade=false;

    echo '<TABLE class="or_listtable" style="width: 100%"><thead>';
    echo '<TR style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">
        <TD>'.lang('experiment').'</TD>';
    //  echo '<TD>.lang('type').'</TD>';
    echo '<TD>'.lang('date_and_time').'</TD>
        <TD>'.lang('registered').'</TD>
        <TD>'.lang('location').'</TD>
        <TD>'.lang('participation_status').'</TD>
        </TR></thead>
        <tbody>';

    $pstatuses=expregister__get_participation_statuses();
    $laboratories=laboratories__get_laboratories();
    while ($p=pdo_fetch_assoc($result)) {
        $last_reg_time=0;
        //if ($p['sess_id']=='0') $last_reg_time=sessions__get_registration_end("","",$p['exp_id']);
        if ($p['sess_id']!='0' || true) { //$last_reg_time > $now) {
            echo '<TR';
                if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
                        else echo ' bgcolor="'.$color['list_shade2'].'"';
            echo '>
                <TD>
                    <A href="experiment_show.php?experiment_id='.$p['exp_id'].'">'.
                        $p['experiment_name'].'</A>
                </TD>';
            /*
            echo '<TD>
                    '.$exptypes[$p['experiment_ext_type']][lang('lang')].'
                </TD>';
            */
            echo '<TD>';
                if ($p['sess_id']!='0')
                    echo '<A HREF="experiment_participants_show.php?experiment_id='.
                                $p['exp_id'].'&session_id='.
                                $p['sess_id'].'">'.session__build_name($p).'</A>';
                else echo '-';
            echo '  </TD>
                <TD>';
                if ($p['sess_id']!='0') echo lang('yes'); else echo lang('no');
            echo '  </TD>
                <TD>';
                if ($p['sess_id']!='0') {
                    if (isset($laboratories[$p['laboratory_id']]['lab_name']))
                        echo $laboratories[$p['laboratory_id']]['lab_name'];
                    else echo 'undefined';
                } else {
                    echo '-';
                }
            echo '</TD>
                <TD>';

            if ($p['pstatus_id']>0) {
                echo '<FONT color="';
                if ($pstatuses[$p['pstatus_id']]['noshow']) echo $color['shownup_no']; else echo $color['shownup_yes'];
                echo '">';
            }
            echo $pstatuses[$p['pstatus_id']]['internal_name'];
            if ($p['pstatus_id']>0) {
                echo '</FONT>';
            }
            echo '  </TD>
                  </TR>';
            if ($shade) $shade=false; else $shade=true;
        }
    }
    echo '</tbody></TABLE>';
}


// Create unique participant id
function participant__create_participant_id($pdata=array()) {
    $exists=true;
    while ($exists) {
        $participant_id = mt_rand(0,1000000000);
        $participant_id_crypt=make_p_token(get_entropy($pdata));
        $pars=array(':participant_id'=>$participant_id,
                    ':participant_id_crypt'=>$participant_id_crypt);
        $query="SELECT participant_id FROM ".table('participants')."
                 WHERE participant_id= :participant_id OR participant_id_crypt= :participant_id_crypt";
        $line=orsee_query($query,$pars);
        if (isset($line['participant_id'])) $exists=true; else $exists=false;
    }
    return array('participant_id'=>$participant_id,'participant_id_crypt'=>$participant_id_crypt);
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

                    $first_other_nonunique_status=participant__get_participant_status($f['nonunique_participants_list'][0]);
                    if(($first_other_nonunique_status['access_to_profile']=='n') && $f['unique_on_create_page_tell_if_deleted']=='y') {
                        message ($first_other_nonunique_status['error']);
                        message(lang('if_you_have_questions_write_to').' '.support_mail_link());
                        $disable_form=true;
                    } else {
                        $problem=true;
                        message(lang($f['unique_on_create_page_error_message_if_exists_lang']));
                        if($settings['subject_authentication']=='token') {
                        // if we still use token, we send a link to edit page to first on non-unique list (if enabled for field)
                            if($f['unique_on_create_page_email_regmail_confmail_again']=='y') {
                                message(lang('message_with_edit_link_mailed'));
                                $done=experimentmail__mail_edit_link($f['nonunique_participants_list'][0]);
                                $disable_form=true;
                            }
                        } else {
                        // if we use passwords, we direct to login page (if unique_on_create_page_email_cancel_signup is not set to 'n'
                            if (!(isset($f['unique_on_create_page_email_cancel_signup']) && $f['unique_on_create_page_email_cancel_signup']=='n')) {
                                    message(lang('please_use_email_address_and_password_to_login'));
                                    $disable_form=true;
                            }
                        }
                    }
                }
            }
        }
    $response=array(); $response['disable_form']=$disable_form; $response['problem']=$problem;
    return $response;
    } elseif ($formtype=='edit') {
        foreach ($nonunique as $f) {
            // conditions for check: 1) subpool must fit, 2) must be a public field, 3) requires unique in edit form
            if(($f['subpools']=='all' || in_array($subpool['subpool_id'],explode(",",$f['subpools']))) &&
                ($f['admin_only']!='y') && $f['check_unique_on_edit_page']=='y') {
                if ($f['nonunique_participants']) {
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
    if(($f['require_unique_on_create_page']=='y' || $f['check_unique_on_edit_page']=='y') &&
        (isset($edit[$f['mysql_column_name']]) && $edit[$f['mysql_column_name']]) ) {
        $pars=array(':value'=>$edit[$f['mysql_column_name']]);
        $query="SELECT participant_id FROM ".table('participants')."
                WHERE ".$f['mysql_column_name']."= :value" ;
        if ($participant_id) {
            $query.=" AND participant_id!= :participant_id";
            $pars[':participant_id']=$participant_id;
        }
        $result=or_query($query,$pars);
        while ($line = pdo_fetch_assoc($result)) $f['nonunique_participants_list'][]=$line['participant_id'];
        if (count($f['nonunique_participants_list'])>0) {
            $f['nonunique_participants']=true;
            $nonunique_fields[$f['mysql_column_name']]=$f;
        }
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
    if (!isset($_REQUEST['subscriptions']) || !is_array($_REQUEST['subscriptions'])) $_REQUEST['subscriptions']=array();
    $_REQUEST['subscriptions']=id_array_to_db_string($_REQUEST['subscriptions']);
    $edit['subscriptions']=$_REQUEST['subscriptions'];
    if(!$edit['subscriptions']) {
        $errors_dataform[]='subscriptions';
        message(lang('at_least_one_exptype_has_to_be_selected'));
    }

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
'order_select_lang_values'=>'alphabetically',
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
    global $preloaded_participant_form;
    if (isset($preloaded_participant_form) && is_array($preloaded_participant_form) && count($preloaded_participant_form)>0) {
        return $preloaded_participant_form;
    } else {
        $query="SELECT  * FROM ".table('profile_fields')."
                WHERE enabled=1";
        $result=or_query($query);
        $pform=array();
        while ($line=pdo_fetch_assoc($result)) {
            $prop=db_string_to_property_array($line['properties']);
            foreach ($prop as $k=>$v) if (!isset($line[$k])) $line[$k]=$v;
            $pform[]=$line;
        }
        // make sure all standard properties are set for all fields
        foreach ($pform as $k=>$f) {
            $t=participantform__allvalues();
            foreach ($f as $kf=>$vf) {
                $t[$kf]=$vf;
            }
            $pform[$k]=$t;
        }
        $preloaded_participant_form=$pform;
        return $pform;
    }
}

function template_replace_callbackA(array $m) {
    global $tempout;
    if ($m[1]=='!') {
        if ((!(isset($tempout[$m[2]])) || (!$tempout[$m[2]]))) return $m[3];
        else return '';
    } else {
        if (isset($tempout[$m[2]]) && $tempout[$m[2]]) return $m[3];
        else return '';
    }
}

function template_replace_callbackB(array $m) {
    return lang($m[1]);
}

// processing the template
function load_form_template($tpl_name,$out,$template='current_template') {
    global $lang, $settings__root_to_server,
        $settings__root_directory, $settings;
        global $tempout;
        $tempout = $out;

    //$tpl=file_get_contents('../ftpl/'.$tpl_name.'.tpl');
    $tpl_data=options__load_object('profile_form_template',$tpl_name);
    if (isset($tpl_data['item_details'][$template]))
        $tpl=$tpl_data['item_details'][$template];
    else $tpl=$tpl_data['item_details']['current_template'];

    // process conditionals
    $pattern="/\{[^#\}]*#(!?)([^#!\}]+)#([^\}]+)\}/i";
    $replacement = "($1\$out['$2'])?\"$3\":''";
    $tpl=preg_replace_callback($pattern,
        'template_replace_callbackA',
        $tpl);


    // fill in the vars
    foreach ($out as $k=>$o) $tpl=str_replace("#".$k."#",$o,$tpl);

    // fill in language terms
        $pattern="/lang\[([^\]]+)\]/i";
        $replacement = "\$lang['$1']";
        $tpl=preg_replace_callback($pattern,
        'template_replace_callbackB',
        $tpl);

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
        case 'radioline_lang': $out=form__render_radioline_lang($f); break;
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

function form__render_select_list($f,$formfieldvarname='') {
    global $lang;
    if (!$formfieldvarname) $formfieldvarname=$f['mysql_column_name'];
    $optionvalues=explode(",",$f['option_values']);
    $optionnames=explode(",",$f['option_values_lang']);
    if ($f['include_none_option']=='y') $incnone=true; else $incnone=false;
    $items=array();
    foreach($optionvalues as $k=>$v) {
        if (isset($optionnames[$k])) $items[$v]=$optionnames[$k];
    }
    $out='';
    $out=helpers__select_text($items,$formfieldvarname,$f['value'],$incnone);
    return $out;
}

function form__render_select_lang($f) {
    if ($f['include_none_option']=='y') $incnone=true; else $incnone=false;
    $out=language__selectfield_item($f['mysql_column_name'],$f['mysql_column_name'],$f['mysql_column_name'],$f['value'],$incnone,$f['order_select_lang_values']);
    return $out;
}

function form__render_radioline_lang($f) {
    if ($f['include_none_option']=='y') $incnone=true; else $incnone=false;
    $out=language__radioline_item($f['mysql_column_name'],$f['mysql_column_name'],$f['mysql_column_name'],$f['value'],$incnone,$f['order_select_lang_values']);
    return $out;
}

function form__render_select_numbers($f) {
        if ($f['include_none_option']=='y') $incnone=true; else $incnone=false;
        if ($f['values_reverse']=='y') $reverse=true; else $reverse=false;
        $out=participant__select_numbers($f['mysql_column_name'],$f['mysql_column_name'],$f['value'],$f['value_begin'],$f['value_end'],0,$f['value_step'],$reverse,$incnone);
        return $out;
}

// special fields

function participant__subscriptions_form_field($subpool_id,$varname,$value) {
    global $settings, $lang;
    $checked=db_string_to_id_array($value);
    $exptypes=load_external_experiment_types();
    if (!$subpool_id) $subpool_id=$settings['subpool_default_registration_id'];
    $subpool=subpools__get_subpool($subpool_id);
    $subpool_exptypes=db_string_to_id_array($subpool['experiment_types']);
    $out='';
    foreach($subpool_exptypes as $exptype_id) {
        $out.='<INPUT type="checkbox" name="'.$varname.'['.$exptype_id.']"
                                        value="'.$exptype_id.'"';
        if (in_array($exptype_id,$checked)) $out.=" CHECKED";
        $out.='>'.$exptypes[$exptype_id][lang('lang')];
        $out.='<BR>
                     ';
    }
    return $out;
}

function participant__rules_signed_form_field($current_rules_signed="") {
    global $lang;
    $out='<input type=radio name=rules_signed value="y"';
    if ($current_rules_signed=="y") $out.=" CHECKED";
        $out.='>'.lang('yes').'&nbsp;&nbsp;&nbsp;
              <input type=radio name=rules_signed value="n"';
        if ($current_rules_signed!="y") $out.=" CHECKED";
        $out.='>'.lang('no');
    return $out;
}

function participant__remarks_form_field($current_remarks="") {
        global $lang;
        $out='<TEXTAREA name="remarks" rows="5" cols="40" wrap=virtual>';
        $out.=$current_remarks;
        $out.='</textarea>';
    return $out;
}

function participant__add_to_session_checkbox() {
        $out='<INPUT type="checkbox" name="register_session" value="y">';
    return $out;
}

function participant__add_to_session_select($session_id="",$participant_id='') {
    $query="SELECT *
            FROM ".table('sessions').", ".table('experiments')."
            WHERE ".table('sessions').".experiment_id=".table('experiments').".experiment_id
            AND ".table('sessions').".session_status='live'
            ORDER BY session_start";
    $result=or_query($query); $sessions=array();
    while ($line=pdo_fetch_assoc($result)) {
        $sessions[$line['session_id']]=$line;
    }
    if ($participant_id) {
        $pars[':participant_id']=$participant_id;
        $query="SELECT *
                FROM ".table('participate_at')."
                WHERE participant_id= :participant_id";
        $result=or_query($query,$pars);
        $del_exp=array(); $ass_exp=array();
        while ($line=pdo_fetch_assoc($result)) {
            if ($line['session_id']>0) $del_exp[$line['experiment_id']]=$line['experiment_id'];
            else $ass_exp[$line['experiment_id']]=$line['experiment_id'];
        }
        foreach ($sessions as $sid=>$session) {
            if (isset($del_exp[$session['experiment_id']])) unset($sessions[$sid]);
            elseif (isset($ass_exp[$session['experiment_id']])) $sessions[$sid]['p_is_assigned']=true;
            else $sessions[$sid]['p_is_assigned']=false;
        }
    }
    $out=select__sessions($session_id,"session_id",$sessions,true,true);
    return $out;
}

function participant__select_numbers($ptablevarname,$formfieldvarname,$prevalue,$begin,$end,$fillzeros=2,$steps=1,$reverse=false,$incnone=false,$existing=false,$where='',$show_count=false) {
    $out='';
    $out.='<select name="'.$formfieldvarname.'">';
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
        $query="SELECT count(*) as tf_count, ".$ptablevarname." as tf_value
                FROM ".table('participants')."
                WHERE ".table('participants').".participant_id IS NOT NULL ";
        if($where) $query.=" AND ".$where." ";
        $query.=" GROUP BY ".$ptablevarname."
                  ORDER BY ".$ptablevarname;
        if ($reverse) $query.=" DESC ";
        $result=or_query($query);
        $listitems=array();
        while ($line = pdo_fetch_assoc($result)) {
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


        while ($line = pdo_fetch_assoc($result)) {
            $out.='<option value="'.$line['tf_value'].'"'; if ($line['tf_value'] == (int) $prevalue) $out.=' SELECTED'; $out.='>';
            $out.=helpers__pad_number($line['tf_value'],$fillzeros);
            if ($show_count) $out.=' ('.$line['tf_count'].')';
            $out.='</option>';
        }
    }
    $out.='</select>';
    return $out;
}

function participant__select_existing($ptablevarname,$formfieldvarname,$prevalue,$where='',$show_count=false) {
    $out='';
    $out.='<select name="'.$formfieldvarname.'">';
    $query="SELECT count(*) as tf_count, ".$ptablevarname." as tf_value
            FROM ".table('participants')."
            WHERE ".table('participants').".participant_id IS NOT NULL ";
    if($where) $query.=" AND ".$where." ";
    $query.=" GROUP BY ".$ptablevarname."
              ORDER BY ".$ptablevarname;
    $result=or_query($query);
    while ($line = pdo_fetch_assoc($result)) {
        $out.='<option value="'.$line['tf_value'].'"'; if ($line['tf_value'] == $prevalue) $out.=' SELECTED'; $out.='>';
        $out.=$line['tf_value'];
        if ($show_count) $out.=' ('.$line['tf_count'].')';
        $out.='</option>';
    }
    $out.='</select>';
    return $out;
}


// the outer participant form
function participant__show_form($edit,$button_title="",$errors,$admin=false,$extra="") {
    global $lang, $settings, $color;
    $out=array(); $tout=array();

    echo '<FORM action="'.thisdoc().'" method="POST">';
    echo '<table cellspacing="0" cellpadding="10em" border="0">
            <TR><TD>';
    participant__show_inner_form($edit,$errors,$admin);
    echo '</TD></TR>
            <TR><TD>';
    echo $extra;
    echo '</TD></TR>';

    if (!$button_title) $button_title=lang('change');
    echo '<tr><td colspan="2" align="center">
            <INPUT class="button" name="add" type="submit" value="'.$button_title.'">
            </td></tr>';
    echo '</table> </form>';
}

// the inner participant form
function participant__show_inner_form($edit,$errors,$admin=false,$template='current_template') {
    global $lang, $settings, $color;
    $out=array(); $tout=array();

    if (!isset($edit['participant_id'])) $edit['participant_id']='';
    if (!isset($edit['subpool_id'])) $edit['subpool_id']=1;
    $subpool=orsee_db_load_array("subpools",$edit['subpool_id'],"subpool_id");
    if (!$subpool['subpool_id']) $subpool=orsee_db_load_array("subpools",1,"subpool_id");
    $edit['subpool_id']=$subpool['subpool_id'];

    $pools=subpools__get_subpools();
    foreach ($pools as $p=>$pool) $out['is_subjectpool_'.$p]=false;
    $out['is_subjectpool_'.$subpool['subpool_id']]=true;
    if ($admin) { $out['is_admin']=true; $out['is_not_admin']=false; }
    else { $out['is_admin']=false; $out['is_not_admin']=true; }

    if (!$admin && isset($edit['participant_id_crypt'])) {
            echo '<INPUT type=hidden name="p" value="'.$edit['participant_id_crypt'].'">
                <INPUT type=hidden name="participant_id_crypt" value="'.$edit['participant_id_crypt'].'">';
    }

    if ($admin) $nonunique=participantform__get_nonunique($edit,$edit['participant_id']);

    // user-defined participant form fields
    $formfields=participantform__load();
    foreach ($formfields as $f) { if($f['subpools']=='all' | in_array($subpool['subpool_id'],explode(",",$f['subpools']))) {
        if ($f['admin_only']!='y') {
            $f=form__replace_funcs_in_field($f);
            if (isset($edit[$f['mysql_column_name']])) $f['value']=$edit[$f['mysql_column_name']];
            else $f['value']=$f['default_value'];
            $field=form__render_field($f);
            if ($admin) {
                if (isset($nonunique[$f['mysql_column_name']])) {
                    $note=lang('not_unique');
                    $link='participants_show.php?form%5Bquery%5D%5B0%5D%5Bpformtextfields_freetextsearch%5D%5Bsearch_string%5D='.urlencode($f['value']).'&form%5Bquery%5D%5B0%5D%5Bpformtextfields_freetextsearch%5D%5Bnot%5D=&form%5Bquery%5D%5B0%5D%5Bpformtextfields_freetextsearch%5D%5Bsearch_field%5D='.urlencode($f['mysql_column_name']).'&search_submit=';
                    $field.=' <A HREF="'.$link.'"><FONT color="'.$color['important_note_textcolor'].'">'.str_replace(" ","&nbsp;",$note).'</FONT></A>';
                }
            }
            $out[$f['mysql_column_name']]=$field;
            if(in_array($f['mysql_column_name'],$errors)) {
                $out['error_'.$f['mysql_column_name']]=' bgcolor="'.$color['missing_field'].'"';
            } else {
                $out['error_'.$f['mysql_column_name']]='';
            }
        }
    }}

    // language field
    if (!isset($edit['language'])) $edit['language']=lang('lang');
    $part_langs=lang__get_part_langs();
    if (count($part_langs)>1) {
        $out['multiple_participant_languages_exist']=true;
        $tout['multiple_participant_languages_exist']=true;
    } else {
        $out['multiple_participant_languages_exist']=false;
        $tout['multiple_participant_languages_exist']=false;
    }
    $out['language']=lang__select_lang('language',$edit['language'],"part");
    if(in_array('language',$errors)) $out['error_language']=' bgcolor="'.$color['missing_field'].'"';
    else $out['error_language']='';

    // subscriptions field
    if (!isset($edit['subscriptions'])) $edit['subscriptions']='';
    $out['subscriptions']=participant__subscriptions_form_field($subpool['subpool_id'],'subscriptions',$edit['subscriptions']);
    if(in_array('subscriptions',$errors)) $out['error_subscriptions']=' bgcolor="'.$color['missing_field'].'"';
    else $out['error_subscriptions']='';

    $formoutput=load_form_template('profile_form_public',$out,$template);
    echo $formoutput;
}

// the inner admin participant form
function participant__get_inner_admin_form($edit,$errors,$template='current_template') {
    global $lang, $settings, $color;

    if (!isset($edit['participant_id'])) $edit['participant_id']='';
    if (!isset($edit['subpool_id'])) $edit['subpool_id']=1;
    $subpool=orsee_db_load_array("subpools",$edit['subpool_id'],"subpool_id");
    if (!$subpool['subpool_id']) $subpool=orsee_db_load_array("subpools",1,"subpool_id");
    $edit['subpool_id']=$subpool['subpool_id'];


    // first show user-defined admin participant form fields
    $formfields=participantform__load(); $tout=array();
    foreach ($formfields as $f) { if($f['subpools']=='all' | in_array($subpool['subpool_id'],explode(",",$f['subpools']))) {
        if ($f['admin_only']=='y') {
            $f=form__replace_funcs_in_field($f);
            if (isset($edit[$f['mysql_column_name']])) $f['value']=$edit[$f['mysql_column_name']];
            else $f['value']=$f['default_value'];
            $field=form__render_field($f);
            $tout[$f['mysql_column_name']]=$field;
            if(in_array($f['mysql_column_name'],$errors)) {
                $tout['error_'.$f['mysql_column_name']]=' bgcolor="'.$color['missing_field'].'"';
            } else {
                $tout['error_'.$f['mysql_column_name']]='';
            }
        }
    }}
    $adminformoutput=load_form_template('profile_form_admin_part',$tout,$template);
    return $adminformoutput;
}


// the participant form for admins
function participant__show_admin_form($edit,$button_title="",$errors,$extra="") {
    global $lang, $settings, $color;
    $out=array();

    if (!isset($edit['participant_id'])) $edit['participant_id']='';
    if (!isset($edit['subpool_id'])) $edit['subpool_id']=1;
    $subpool=orsee_db_load_array("subpools",$edit['subpool_id'],"subpool_id");
    if (!$subpool['subpool_id']) $subpool=orsee_db_load_array("subpools",1,"subpool_id");
    $edit['subpool_id']=$subpool['subpool_id'];

    $pools=subpools__get_subpools();
    foreach ($pools as $p=>$pool) $out['is_subjectpool_'.$p]=false;
    $out['is_subjectpool_'.$subpool['subpool_id']]=true;

    echo '<FORM action="'.thisdoc().'" method="POST">';

    echo '<table border="0">';
    echo '<TR><TD valign="top">';
    echo '<TABLE class="or_formtable" style="width: 100%; height: 100%; max-width: 100%"><TR><TD>';

    // get the participant form
    participant__show_inner_form($edit,$errors,true);

    echo '</TD></TR></TABLE>';
    echo '</TD><TD valign="top">';
    echo '<TABLE class="or_formtable" style="width: 100%; height: 100%; max-width: 100%; background: '.$color['list_shade2'].'"><TR><TD>';

    echo '<INPUT type="hidden" name="participant_id" value="'.$edit['participant_id'].'">';
    global $hide_header;
    if (isset($hide_header) && $hide_header) echo '<INPUT type="hidden" name="hide_header" value="true">';


    $adminformoutput=participant__get_inner_admin_form($edit,$errors);
    if ($adminformoutput) {
        echo '<TABLE width="100%">
                <TR><TD valign="top" bgcolor="'.$color['list_shade1'].'">';
        echo $adminformoutput;
        echo '</TD></TR></TABLE>';
    }
    echo '<BR>';

    // then show the rest

    // initialize
    if (!isset($edit['participant_id'])) $edit['participant_id']='???';
    if (!isset($edit['participant_id_crypt'])) $edit['participant_id_crypt']='???';
    if (isset($edit['creation_time'])) $tout['creation_time']=ortime__format($edit['creation_time'],'',lang('lang'));  else $tout['creation_time']='';
    if (!isset($edit['rules_signed'])) $edit['rules_signed']='';
    if (!isset($edit['session_id'])) $edit['session_id']='';
    if (!isset($edit['remarks'])) $edit['remarks']='';

    echo '<table width="100%">';
    echo '  <tr><td>'.lang('subpool').'</td>
            <td>'.subpools__select_field("subpool_id",$edit['subpool_id']).'</td></tr>';

    echo '<tr><td colspan=2>&nbsp;</td></tr>';

    echo '  <tr><td>'.lang('id').'</td>
            <td>'.$edit['participant_id'].' ('.$edit['participant_id_crypt'].')</td></tr>
        <tr><td>'.lang('creation_time').'</td>
            <td>';
    if (isset($edit['creation_time'])) echo ortime__format($edit['creation_time'],'',lang('lang'));  else echo '???';
    echo '  </td></tr>';

    if ($settings['enable_rules_signed_tracking']=='y') {
        echo '<tr><td>'.lang('rules_signed').'</td>
            <td>'.participant__rules_signed_form_field($edit['rules_signed']).'</td></tr>';
    }
    echo '<tr><td valign="top">'.lang('remarks').'</td>
            <td>'.participant__remarks_form_field($edit['remarks']).'</td></tr>';

    echo '<tr><td colspan=2>&nbsp;</td></tr>';

    echo '<tr><td colspan=2 align=left>
                '.participant__add_to_session_checkbox().' '.lang('register_sub_for_session').'<BR>
                '.participant__add_to_session_select($edit['session_id'],$edit['participant_id']).'
            </td></tr>';

    echo '</td></tr></table>';
    echo '</TD></TR><TR><TD valign="bottom"  bgcolor="'.$color['list_shade2'].'">';
    echo '<table>
            <tr style="outline: 2px solid red;">
            <td>
                <B>'.lang('participant_status').'</B>: ';
    if (check_allow('participants_change_status')) {
        if(!isset($_REQUEST['status_id'])) $_REQUEST['status_id']="";
            if ($_REQUEST['status_id']=='0') $hide=array(); else $hide=array('0');
            echo '<INPUT type="hidden" name="old_status_id" value="'.$_REQUEST['status_id'].'">'.
                    participant_status__select_field('status_id',$_REQUEST['status_id'],$hide);
        } elseif (!$edit['participant_id']) {
            $default_status=participant_status__get("is_default_active");
            $statuses=participant_status__get_statuses();
            echo '<INPUT type="hidden" name="status_id" value="'.$default_status.'">'.
                        $statuses[$default_status]['name'];
        } else {
            echo participant_status__get_name($_REQUEST['status_id']);
        }
    echo '</td></tr></table>';
    echo '</td></tr></table>';
    echo '</td></tr>';

    if (!$button_title) $button_title=lang('change');
    echo '<tr><td colspan="2" align="center">
            <INPUT class="button" name="add" type="submit" value="'.$button_title.'">
            </td></tr>';
    echo '</table></form>';
}


function participant__password_form_fields($new=false,$provided=false) {
    $out='';
    $out='<tr><td>';
    if ($new) $out.=lang('new_password'); else $out.=lang('password');
    $out.='<br>';
    if ($provided) $out.='***'.lang('provided').'***';
    else $out.='<input type="password" name="password" size="20" max-length="40"><br>
                <font class="small">'.lang('participant_password_note').'</font>';
    $out.='</td></tr>
        <tr><td>';
    if ($new) $out.=lang('repeat_new_password'); else $out.=lang('repeat_password');
    $out.='<br>';
    if ($provided) $out.='***'.lang('provided').'***';
    else $out.='<input type="password" name="password2" size="20" max-length="40">';
    $out.='</td></tr>';
    return $out;
}

function participant__check_password($password,$password2) {
    global $settings;
    $continue=true;
    if (!$password || !$password2) {
        message (lang('you_have_to_give_a_password'));
        $continue=false;
    }
    if ($password!=$password2) {
        message (lang('error_password_repetition_does_not_match'));
        $continue=false;
    }
    if (!preg_match('/'.$settings['participant_password_regexp'].'/',$password)) {
        message(lang('error_password_does_not_meet_requirements'));
        $continue=false;
    }
    return $continue;
}

function participant__check_login($email,$password) {
    global $lang;
    $pars=array(':email'=>$email);
    $query="SELECT * FROM ".table('participants')."
            WHERE email= :email";
    $participant=orsee_query($query,$pars);

    $continue=true;

    if ($continue) {
        if (!isset($participant['participant_id'])) {
            $continue=false;
            log__participant('login_participant_wrong_username',0,'used_username:'.$email);
            message(lang('error_password_or_username'));
        }
    }

    if ($continue) {
        $participant=participant__check_has_lockout($participant);
        if ($participant['locked']) {
            $continue=false;
            log__participant('login_participant_locked_out',$participant['participant_id'],'username:'.$email);
            $locked=participant__track_unsuccessful_login($participant);
            message(lang('error_password_or_username'));
        }
    }

    if ($continue) {
        $check_pw=crypt_verify($password,$participant['password_crypted']);
        if (!$check_pw) {
            $continue=false;
            log__participant('login_participant_wrong_password',$participant['participant_id'],'username:'.$email);
            $locked=participant__track_unsuccessful_login($participant);
            message(lang('error_password_or_username'));
        }
    }

    if ($continue) {
        $statuses=participant_status__get_statuses();
        $statuses_profile=participant_status__get("access_to_profile");
        if (!in_array($participant['status_id'],$statuses_profile)) {
            log__participant('login_participant_not_active_anymore',$participant['participant_id'],'username:'.$email);
            message ($statuses[$participant['status_id']]['error']." ".
            lang('if_you_have_questions_write_to')." ".support_mail_link());
            $continue=false;
        }
    }

    if ($continue) {
        $_SESSION['pauthdata']['user_logged_in']=true;
        $_SESSION['pauthdata']['participant_id']=$participant['participant_id'];
        $done=participant__track_successful_login($participant);
        return true;
    } else {
        if (isset($locked) && $locked) message(lang('error_locked_out'));
        return false;
    }
}

function participant__check_has_lockout($participant) {
    global $settings;
    if (isset($settings['participant_lockout_minutes']) && $settings['participant_lockout_minutes']>0)
        $lockout_minutes=$settings['participant_lockout_minutes'];
    else $lockout_minutes=20;
    if ($participant['locked'] && ($participant['last_login_attempt'] + ($lockout_minutes*60)) < time()) {
        // unlock
        $participant['failed_login_attempts']=0;
        $participant['locked']=0;
    }
    return $participant;
}

function participant__track_unsuccessful_login($participant) {
    global $settings;
    if (isset($settings['participant_failed_logins_before_lockout']) && $settings['participant_failed_logins_before_lockout']>0)
        $limit=$settings['participant_failed_logins_before_lockout'];
    else $limit=3;
    if (isset($settings['participant_lockout_minutes']) && $settings['participant_lockout_minutes']>0)
    $lockout_minutes=$settings['participant_lockout_minutes'];
    else $lockout_minutes=20;

    $last_login_attempt=time();
    $failed_login_attempts=$participant['failed_login_attempts']+1;
    if ($failed_login_attempts>=$limit) {
        $locked=1;
    } else {
        $locked=0;
    }
    $pars=array(':participant_id'=>$participant['participant_id'],
                ':last_login_attempt'=>$last_login_attempt,
                ':failed_login_attempts'=>$failed_login_attempts,
                ':locked'=>$locked,
                );
    $query="UPDATE ".table('participants')."
            SET last_login_attempt = :last_login_attempt,
            failed_login_attempts = :failed_login_attempts,
            locked = :locked
            WHERE participant_id= :participant_id";
    $done=or_query($query,$pars);
    return $locked;
}

function participant__track_successful_login($participant) {
    $pars=array(':participant_id'=>$participant['participant_id'],
                ':last_login_attempt'=>time(),
                ':failed_login_attempts'=>0,
                ':locked'=>0,
                );
    $query="UPDATE ".table('participants')."
            SET last_login_attempt = :last_login_attempt,
            failed_login_attempts = :failed_login_attempts,
            locked = :locked
            WHERE participant_id= :participant_id";
    $done=or_query($query,$pars);
    log__participant('login_participant_success',$participant['participant_id']);
    return $done;
}


// Updating password for admin
function participant__set_password($password,$participant_id) {
    $pars=array(':participant_id'=>$participant_id,
                ':password'=>unix_crypt($password));
    $query="UPDATE ".table('participants')."
            SET password_crypted= :password
            WHERE participant_id= :participant_id";
    $done=or_query($query,$pars);
}

function participant__logout() {
    unset($_SESSION['pauthdata']);
    //session_destroy(); // we loose capability to display messages if we destroy completely
}

function participant__select_lang_idlist_to_names($mysql_column_name,$idlist) {
    $names=lang__load_lang_cat($mysql_column_name);
    $ids=explode(",",$idlist);
    $namearr=array();
    foreach ($ids as $id) {
        if (isset($names[$id])) $namearr[]=$names[$id];
        else $namearr[]=$id;
    }
    return implode(", ",$namearr);
}

function participant__update_last_enrolment_time($participant_id,$time=0) {
    if (!$time) $time=time();
    $pars=array(':time1'=>$time,':time2'=>$time);
    if (is_array($participant_id)) {
        $i=0; $parnames=array();
        foreach ($participant_id as $pid) {
            $i++;
            $tparname=':participant_id_'.$i;
            $parnames[]=$tparname;
            $pars[$tparname]=$pid;
        }
        $condition="participant_id IN (".implode(",",$parnames).")";
    } else {
        $pars[':participant_id']=$participant_id;
        $condition="participant_id= :participant_id";
    }
    $query="UPDATE ".table('participants')."
            SET last_enrolment=:time1,
            last_activity=:time2
            WHERE ".$condition;
    $done=or_query($query,$pars);
}

function participant_status__select_field($postvarname,$selected,$hidden=array(),$class='') {
    $statuses=participant_status__get_statuses();
    $out='<SELECT name="'.$postvarname.'"';
    if ($class) $out.=' class="'.$class.'"';
    $out.='>';
    foreach ($statuses as $status) {
        if (!in_array($status['status_id'],$hidden)) {
            $out.='<OPTION value="'.$status['status_id'].'"';
            if ($status['status_id']==$selected) $out.=" SELECTED";
            $out.='>'.$status['name'];
            $out.='</OPTION>
                ';
        }
    }
    $out.='</SELECT>';
    return $out;
}

function participant_status__multi_select_field($postvarname,$selected,$mpoptions=array()) {
    // $postvarname - name of form field
    // selected - array of pre-selected experimenter usernames
    global $lang, $settings;

    $out="";
    $statuses=participant_status__get_statuses();

    $mylist=array();
    foreach($statuses as $status_id=>$status) {
        $mylist[$status_id]=$status['name'];
    }

    if (!is_array($mpoptions)) $mpoptions=array();
    if (!isset($mpoptions['picker_icon'])) $mpoptions['picker_icon']='star';
    $out.= get_multi_picker($postvarname,$mylist,$selected,$mpoptions);
    return $out;
}


function participant__status_id_list_to_status_names($status_list) {
    $allstatuses=participant_status__get_statuses();
    $statusids=explode(",",$status_list);
    $statusnames=array();
    foreach ($statusids as $id) {
        if ($id!='') $statusnames[]=$allstatuses[$id]['name'];
    }
    return implode(", ",$statusnames);
}

function participant_status__get_statuses() {
    global $preloaded_participant_statuses, $lang;
    if (isset($preloaded_participant_statuses) && is_array($preloaded_participant_statuses) && count($preloaded_participant_statuses)>0) {
        return $preloaded_participant_statuses;
    } else {
        $participant_statuses=array();
        $query="SELECT *
                FROM ".table('participant_statuses')."
                ORDER BY status_id";
        $result=or_query($query);
        while ($line = pdo_fetch_assoc($result)) {
            $participant_statuses[$line['status_id']]=$line;
        }
        $query="SELECT *
                FROM ".table('lang')."
                WHERE content_type='participant_status_name'
                OR content_type='participant_status_error'
                ORDER BY content_name";
        $result=or_query($query);
        while ($line = pdo_fetch_assoc($result)) {
            if ($line['content_type']=='participant_status_name') $field='name'; else $field='error';
            $participant_statuses[$line['content_name']][$field]=$line[lang('lang')];
        }
        $preloaded_participant_statuses=$participant_statuses;
        return $participant_statuses;
    }
}

function participant_status__get($what="is_default_active") {
    // what can be access_to_profile, eligible_for_experiments, is_default_active or is_default_inactive
    $statuses=participant_status__get_statuses();
    $res=array();
    foreach ($statuses as $status_id=>$status) {
        if($status[$what]=='y') $res[]=$status_id;
    }
    if ($what=='is_default_active' || $what=='is_default_inactive') return $res[0];
    else return $res;
}

function participant_status__get_pquery_snippet($what="eligible_for_experiments") {
    // what can be access_to_profile, eligible_for_experiments, is_default_active or is_default_inactive
    $check_statuses=participant_status__get($what);
    if (count($check_statuses)>0) {
        if ($what=='is_default_active' || $what=='is_default_inactive') return " status_id='".$check_statuses."' ";
        else {
            $snippet=" status_id IN (".implode(", ",$check_statuses).") ";
            return $snippet;
        }
    } else return '';
}

function participant_status__get_name($status_id) {
    global $lang;
    $pars=array(':status_id'=>$status_id);
    $query="SELECT *
            FROM ".table('lang')."
            WHERE content_type='participant_status_name'
            AND content_name= :status_id";
    $result=or_query($query,$pars);
    $line = pdo_fetch_assoc($result);
    return $line[lang('lang')];
}

function participant__get_participant_status($participant_id) {
    //status_type can be access_to_profile, eligible_for_experiments, is_default_active or is_default_inactive
    $statuses=participant_status__get_statuses();
    $pars=array(':participant_id'=>$participant_id);
    $query="SELECT status_id
            FROM ".table('participants')."
            WHERE participant_id= :participant_id";
    $line=orsee_query($query,$pars);
    return $statuses[$line['status_id']];
}

function participant__nonuserdefined_columns() {
    $columns=array();
    $columns['participant_id']=array('use_in_tables'=>1,'lang_symbol'=>'participant_id');
    $columns['number_noshowup']=array('use_in_tables'=>1,'lang_symbol'=>'noshowup');
    $columns['rules_signed']=array('use_in_tables'=>1,'lang_symbol'=>'rules_signed');
    $columns['creation_time']=array('use_in_tables'=>1,'lang_symbol'=>'creation_time');
    $columns['deletion_time']=array('use_in_tables'=>1,'lang_symbol'=>'deletion_time');
    $columns['last_enrolment']=array('use_in_tables'=>1,'lang_symbol'=>'last_enrolment');
    $columns['last_profile_update']=array('use_in_tables'=>1,'lang_symbol'=>'last_profile_update');
    $columns['last_activity']=array('use_in_tables'=>1,'lang_symbol'=>'last_activity');
    $columns['last_login_attempt']=array('use_in_tables'=>1,'lang_symbol'=>'last_login_attempt');
    $columns['failed_login_attempts']=array('use_in_tables'=>1,'lang_symbol'=>'failed_login_attempts');
    $columns['locked']=array('use_in_tables'=>1,'lang_symbol'=>'locked');
    $columns['subpool_id']=array('use_in_tables'=>1,'lang_symbol'=>'subpool');
    $columns['subscriptions']=array('use_in_tables'=>1,'lang_symbol'=>'subscriptions');
    $columns['status_id']=array('use_in_tables'=>1,'lang_symbol'=>'participant_status');
    $columns['pending_profile_update_request']=array('use_in_tables'=>1,'lang_symbol'=>'pending_profile_update_request');
    $columns['language']=array('use_in_tables'=>1,'lang_symbol'=>'language');
    $columns['remarks']=array('use_in_tables'=>1,'lang_symbol'=>'remarks');

    $columns['participant_id_crypt']=array('use_in_tables'=>0,'lang_symbol'=>'');
    $columns['password_crypted']=array('use_in_tables'=>0,'lang_symbol'=>'');
    $columns['confirmation_token']=array('use_in_tables'=>0,'lang_symbol'=>'');
    $columns['pwreset_token']=array('use_in_tables'=>0,'lang_symbol'=>'');
    $columns['pwreset_request_time']=array('use_in_tables'=>0,'lang_symbol'=>'');
    $columns['profile_update_request_new_pool']=array('use_in_tables'=>0,'lang_symbol'=>'');
    $columns['apply_permanent_queries']=array('use_in_tables'=>0,'lang_symbol'=>'');
    $columns['number_reg']=array('use_in_tables'=>0,'lang_symbol'=>'');
    //$columns['']=array('use_in_tables'=>0,'lang_symbol'=>'');

    return $columns;
}

function participant__userdefined_columns() {
    $internal_columns=participant__nonuserdefined_columns();
    $query="SHOW COLUMNS FROM ".table('participants');
    $result=or_query($query);
    $user_columns=array();
    while ($line=pdo_fetch_assoc($result)) {
        if (!isset($internal_columns[$line['Field']])) {
            $user_columns[$line['Field']]=$line;
        }
    }
    return $user_columns;
}

function participant__get_possible_participant_columns($listtype) {

    $formfields=participantform__load();
    $ptable_columns=participant__nonuserdefined_columns();
    $other_pfields=array();
    foreach ($ptable_columns as $k=>$arr) {
        if (isset($arr['use_in_tables']) && $arr['use_in_tables']) {
            if (isset($arr['lang_symbol'])) $lang_symbol=$arr['lang_symbol'];
            else $lang_symbol=$k;
            $other_pfields[$k]=lang($lang_symbol);
        }
    }

    $cols=array();
    if ($listtype=='result_table_search_active' || $listtype=='result_table_search_all') {
        $cols['checkbox']=array('display_text'=>lang('checkbox'),'on_list'=>true,'allow_remove'=>false,'sortable'=>false);
        $cols['pform_fields']='';
        $cols['other_pfields']='';
        $cols['edit_link']=array('display_text'=>lang('edit_link'),'on_list'=>true,'allow_remove'=>false,'sortable'=>false);
    } elseif ($listtype=='result_table_assign') {
        $cols['checkbox']=array('display_text'=>lang('checkbox'),'on_list'=>true,'allow_remove'=>false,'sortable'=>false);
        $cols['pform_fields']='';
        $cols['other_pfields']='';
        //$cols['edit_link']=array('display_text'=>lang('edit_link'),'on_list'=>true,'allow_remove'=>false,'sortable'=>false);
    } elseif ($listtype=='result_table_search_duplicates') {
        $cols['pform_fields']='';
        $cols['other_pfields']='';
        $cols['edit_link']=array('display_text'=>lang('edit_link'),'on_list'=>true,'allow_remove'=>false,'sortable'=>false);
    } elseif ($listtype=='result_table_search_unconfirmed') {
        $cols['checkbox']=array('display_text'=>lang('checkbox'),'on_list'=>true,'allow_remove'=>false,'sortable'=>false);
        $cols['email_unconfirmed']=array('display_text'=>lang('email_with_confirmation_email'),'on_list'=>true,'allow_remove'=>false,'sortable'=>true);
        $cols['pform_fields']='';
        $cols['other_pfields']='';
        $cols['edit_link']=array('display_text'=>lang('edit_link'),'on_list'=>true,'allow_remove'=>false,'sortable'=>false);
    } elseif ($listtype=='experiment_assigned_list') {
        $cols['checkbox']=array('display_text'=>lang('checkbox'),'on_list'=>true,'allow_remove'=>false,'sortable'=>false);
        $cols['pform_fields']='';
        $cols['other_pfields']='';
        $cols['invited']=array('display_text'=>lang('invited'),'on_list'=>true,'allow_remove'=>false);
    } elseif ($listtype=='session_participants_list') {
        $cols['checkbox']=array('display_text'=>lang('checkbox'),'on_list'=>true,'allow_remove'=>false,'sortable'=>false);
        $cols['order_number']=array('display_text'=>lang('order_number'),'display_table_head'=>'&nbsp;','sortable'=>false);
        $cols['pform_fields']='';
        $cols['other_pfields']='';
        $cols['session_id']=array('display_text'=>lang('session'),'on_list'=>true,'allow_remove'=>false,'sort_order'=>'session_id');
        $cols['payment_budget']=array('display_text'=>lang('payment_budget'),'display_table_head'=>lang('payment_budget_abbr'),'on_list'=>true,'allow_remove'=>false);
        $cols['payment_type']=array('display_text'=>lang('payment_type'),'display_table_head'=>lang('payment_type_abbr'),'on_list'=>true,'allow_remove'=>false);
        $cols['payment_amount']=array('display_text'=>lang('payment_amount'),'display_table_head'=>lang('payment_amount_abbr'),'on_list'=>true,'allow_remove'=>false,'sort_order'=>'payment_amt');
        $cols['pstatus_id']=array('display_text'=>lang('participation_status'),'on_list'=>true,'allow_remove'=>false);
    } elseif ($listtype=='session_participants_list_pdf') {
        $cols['order_number']=array('display_text'=>lang('order_number'),'display_table_head'=>'&nbsp;','sortable'=>false);
        $cols['pform_fields']='';
        $cols['other_pfields']='';
        $cols['session_id']=array('display_text'=>lang('session'),'on_list'=>true,'allow_remove'=>false,'sort_order'=>'session_id');
        $cols['payment_amount']=array('display_text'=>lang('payment_amount'),'display_table_head'=>lang('payment_amount_abbr'),'sort_order'=>'payment_amt');
        $cols['pstatus_id']=array('display_text'=>lang('participation_status'));
    } elseif ($listtype=='email_participant_guesses_list') {
        $cols['email']=array('display_text'=>lang('email'),'on_list'=>true,'allow_remove'=>false,'sortable'=>true);
        $cols['pform_fields']='';
    }

    $poss_cols=array();
    foreach ($cols as $col=>$colarr) {
        if ($col=='pform_fields') {
            foreach ($formfields as $f) {
                if (!isset($cols[$f['mysql_column_name']])) {
                    $poss_cols[$f['mysql_column_name']]=array('display_text'=>lang($f['name_lang']));
                }
            }
        } elseif ($col=='other_pfields') {
            foreach ($other_pfields as $ofield=>$oname) {
                if (!isset($cols[$ofield])) {
                    $poss_cols[$ofield]=array('display_text'=>$oname);
                }
            }
        } else {
            $poss_cols[$col]=$colarr;
        }
    }
    return $poss_cols;
}

function participant__get_result_table_columns($list) {
// $list can be: result_table_search_active, result_table_search_all,
// result_table_assign, result_table_search_duplicates, session_list,session_list_pdf
    global $preloaded_result_table_columns;
    if (isset($preloaded_result_table_columns[$list]) && is_array($preloaded_result_table_columns[$list])) return $preloaded_result_table_columns[$list];
    else {
        $allcols=participant__get_possible_participant_columns($list);
        $pars=array(':item_type'=>$list);
        $query="SELECT *
                FROM ".table('objects')."
                WHERE item_type= :item_type
                ORDER BY order_number";
        $result=or_query($query,$pars);
        $saved_cols=array(); while ($line=pdo_fetch_assoc($result)) $saved_cols[$line['item_name']]=$line;
        $listcols=options__ordered_lists_get_current($allcols,$saved_cols);
        foreach ($listcols as $k=>$arr) if (!isset($arr['on_list']) || !$arr['on_list']) unset($listcols[$k]);
        $preloaded_result_table_columns[$list]=$listcols;
        return $listcols;
    }
}


function participant__get_result_table_headcells($columns,$allow_sort=true) {
    global $settings, $color;
    $celltag='td';

    $pform_columns=participant__load_all_pform_fields();
    $out='';
    foreach ($columns as $k=>$arr) {
        if (isset($arr['display_table_head'])) $arr['display_text']=$arr['display_table_head'];
        if (isset($arr['sort_order'])) $sort_order=$arr['sort_order'];
        else $sort_order=$k;
        switch($k) {
            case 'checkbox':
                $out.='<'.$celltag.'  style="">'.lang('select_all').'
                '.javascript__selectall_checkbox_script().'</'.$celltag.'>';
                break;
            case 'number_noshowup':
                $out.=query__headcell($arr['display_text'],"number_noshowup,number_reg",$allow_sort);
                break;
            case 'rules_signed':
                if ($settings['enable_rules_signed_tracking']=='y')  {
                    $out.=query__headcell($arr['display_text'],"rules_signed,lname,fname",$allow_sort);
                }
                break;
            case 'payment_budget':
            case 'payment_type':
            case 'payment_amount':
                if ($settings['enable_payment_module']=='y' && (check_allow('payments_view') || check_allow('payments_edit'))) {
                    $out.=query__headcell($arr['display_text'],$sort_order,$allow_sort);
                }
                break;
            case 'status_id':
                $out.=query__headcell(lang('participant_status_abbr'),"status_id",$allow_sort);
                break;
            case 'edit_link':
                if (check_allow('participants_edit')) $out.='<'.$celltag.'></'.$celltag.'>';
                break;
            default:
                if (isset($pform_columns[$k])) {
                    $out.=query__headcell($pform_columns[$k]['column_name'],$pform_columns[$k]['sort_order'],$allow_sort);
                } else {
                    if (isset($arr['sortable']) && $arr['sortable']==false) $out.=query__headcell($arr['display_text']);
                    else $out.=query__headcell($arr['display_text'],$sort_order,$allow_sort);
                }
        }
    }
    return $out;
}

function participant__get_result_table_headcells_pdf($columns) {
    global $settings;

    $pform_columns=participant__load_all_pform_fields();
    $table_headings=array();
    foreach ($columns as $k=>$arr) {
        if (isset($arr['display_table_head'])) $arr['display_text']=$arr['display_table_head'];
        switch($k) {
            case 'rules_signed':
                if ($settings['enable_rules_signed_tracking']=='y')  {
                    $table_headings[]=$arr['display_text'];
                }
                break;
            case 'payment_budget':
            case 'payment_type':
            case 'payment_amount':
                if ($settings['enable_payment_module']=='y' && (check_allow('payments_view') || check_allow('payments_edit'))) {
                    $table_headings[]=$arr['display_text'];
                }
                break;
            case 'status_id':
                $table_headings[]=lang('participant_status_abbr');
                break;
            default:
                if (isset($pform_columns[$k])) {
                    $table_headings[]=$pform_columns[$k]['column_name'];
                } else {
                    $table_headings[]=$arr['display_text'];
                }
        }
    }
    foreach ($table_headings as $k=>$v) $table_headings[$k]=str_replace("&nbsp;"," ",$v);
    return $table_headings;
}

function participant__get_result_table_row($columns,$p) {
    global $settings, $color;
    global $thislist_sessions, $thislist_avail_payment_budgets, $thislist_avail_payment_types;

    $pform_columns=participant__load_all_pform_fields();

    $out='';
    foreach ($columns as $k=>$arr) {
        switch($k) {
            case 'email_unconfirmed':
                $message="";
                $message=experimentmail__get_confirmation_mail_text($p);
                $message=str_replace(" ","%20",$message);
                $message=str_replace("\n\m","\n",$message);
                $message=str_replace("\m\n","\n",$message);
                $message=str_replace("\m","\n",$message);
                $message=str_replace("\n","%0D%0A",$message);
                $linktext='mailto:'.$p['email'].'?subject='.str_replace(" ","%20",lang('registration_email_subject')).'&reply-to='.urlencode($settings['support_mail']).'&body='.$message;
                $out.='<td class="small">';
                $out.='<A class="small" HREF="'.$linktext.'">'.$p['email'].'</A>';
                $out.='</td>';
                break;
            case 'checkbox':
                $out.='<td class="small">';
                $out.='<INPUT type="checkbox" name="sel['.$p['participant_id'].']" value="y"';
                if (isset($_REQUEST['sel'][$p['participant_id']]) && $_REQUEST['sel'][$p['participant_id']]=='y') $out.=' CHECKED';
                elseif (isset($_SESSION['sel'][$p['participant_id']]) && $_SESSION['sel'][$p['participant_id']]=='y') $out.=' CHECKED';
                $out.='></td>';
                break;
            case 'number_noshowup':
                $out.='<td class="small">';
                $out.=$p['number_noshowup'].'/'.$p['number_reg'];
                $out.='</td>';
                break;
            case 'invited':
                $out.='<td class="small">'.($p['invited']?lang('y'):lang('n')).'</td>';
                break;
            case 'rules_signed':
                if ($settings['enable_rules_signed_tracking']=='y')  {
                    $out.='<td class="small">';
                    $out.='<INPUT type="checkbox" name="rules['.$p['participant_id'].']" value="y"';
                    if ($p['rules_signed']=='y') {
                        $out.=' CHECKED';
                    }
                    $out.='></td>';
                }
                break;
            case 'subscriptions':
                $exptypes=load_external_experiment_types();
                $inv_arr=db_string_to_id_array($p[$k]);
                $inv_names=array();
                foreach($inv_arr as $inv) {
                    if (isset($exptypes[$inv]['exptype_name'])) $inv_names[]=$exptypes[$inv]['exptype_name'];
                    else $inv_names[]='undefined';
                }
                $out.='<td class="small">'.implode(", ",$inv_names).'</td>';
                break;
            case 'subpool_id':
                $subpools=subpools__get_subpools();
                $subpool_name=(isset($subpools[$p[$k]]['subpool_name']))?$subpools[$p[$k]]['subpool_name']:$p[$k];
                $out.='<td class="small">'.$subpool_name.'</td>';
                break;
            case 'status_id':
                $participant_statuses=participant_status__get_statuses();
                $pstatus_name=(isset($participant_statuses[$p[$k]]['name']))?$participant_statuses[$p[$k]]['name']:$p[$k];
                if ($participant_statuses[$p['status_id']]['eligible_for_experiments']=='y') $ccolor=$color['participant_status_eligible_for_experiments'];
                else $ccolor=$color['participant_status_noneligible_for_experiments'];
                $out.='<td class="small" bgcolor="'.$ccolor.'">'.$pstatus_name.'</td>';
                break;
            case 'edit_link':
                if (check_allow('participants_edit')) $out.='<TD class="small">'.javascript__edit_popup_link($p['participant_id']).'</TD>';
                break;
            case 'creation_time':
            case 'deletion_time':
            case 'last_enrolment':
            case 'last_profile_update':
            case 'last_activity':
            case 'last_login_attempt':
                $out.='<td class="small">';
                if ($p[$k]) $out.=ortime__format($p[$k],'hide_second:false');
                else  $out.='-';
                $out.='</td>';
                break;
            case 'session_id':
                $out.='<td class="small">';
                if (check_allow('experiment_edit_participants')) {
                    $out.='<INPUT type=hidden name="orig_session['.$p['participant_id'].']" value="'.$p['session_id'].'">';
                    $out.=select__sessions($p['session_id'],'session['.$p['participant_id'].']',$thislist_sessions,false);
                } else $out.=session__build_name($thislist_sessions[$p['session_id']]);
                $out.='</td>';
                break;
            case 'payment_budget':
                if($settings['enable_payment_module']=='y') {
                    $payment_budgets=payments__load_budgets();
                    if (check_allow('payments_edit')) {
                        $out.='<td class="small">';
                        $out.=payments__budget_selectfield('paybudget['.$p['participant_id'].']',$p['payment_budget'],array(),$thislist_avail_payment_budgets);
                        $out.='</td>';
                    } elseif (check_allow('payments_view')) {
                        $out.='<td class="small">';
                        if (isset($payment_budgets[$p['payment_budget']])) $out.=$payment_budgets[$p['payment_budget']]['budget_name']; else $out.='-';
                        $out.='</td>';
                    }
                }
                break;
            case 'payment_type':
                if($settings['enable_payment_module']=='y') {
                    $payment_types=payments__load_paytypes();
                    if (check_allow('payments_edit')) {
                        $out.='<td class="small">';
                        $out.=payments__paytype_selectfield('paytype['.$p['participant_id'].']',$p['payment_type'],array(),$thislist_avail_payment_types);
                        $out.='</td>';
                    } elseif (check_allow('payments_view')) {
                        $out.='<td class="small">';
                        if (isset($payment_types[$p['payment_type']])) $out.=$payment_types[$p['payment_type']]; else $out.='-';
                        $out.='</td>';
                    }
                }
                break;
            case 'payment_amount':
                if($settings['enable_payment_module']=='y') {
                    if (check_allow('payments_edit')) {
                        $out.='<td class="small">';
                        $out.='<INPUT type="text" name="payamt['.$p['participant_id'].']" value="';
                        if ($p['payment_amt']!='') $out.=$p['payment_amt']; else $out.='0.00';
                        $out.='" size="7" maxlength="10" style="text-align:right;">';
                        $out.='</td>';
                    } elseif (check_allow('payments_view')) {
                        $out.='<td class="small">';
                        if ($p['payment_amt']!='') $out.=$p['payment_amt']; else $out.='-';
                        $out.='</td>';
                    }
                }
                break;
            case 'pstatus_id':
                $out.='<td class="small">';
                if (check_allow('experiment_edit_participants')) {
                    $out.='<INPUT type=hidden name="orig_pstatus_id['.$p['participant_id'].']" value="'.$p['pstatus_id'].'">';
                    $out.=expregister__participation_status_select_field('pstatus_id['.$p['participant_id'].']',$p['pstatus_id']);
                } else {
                    $pstatuses=expregister__get_participation_statuses();
                    $out.=$pstatuses[$p['pstatus_id']]['internal_name'];
                }
                $out.='</td>';
                break;
            default:
                if (isset($pform_columns[$k])) {
                    $out.='<td class="small">';
                    if($pform_columns[$k]['link_as_email_in_lists']=='y') $out.='<A class="small" HREF="mailto:'.$p[$k].'">';
                    if(preg_match("/(radioline|select_list|select_lang|radioline_lang)/",$pform_columns[$k]['type'])) {
                        if (isset($pform_columns[$k]['lang'][$p[$k]])) $out.=lang($pform_columns[$k]['lang'][$p[$k]]);
                        else $out.=$p[$k];
                    } else $out.=$p[$k];
                    if($pform_columns[$k]['link_as_email_in_lists']=='y') $out.='</A>';
                    $out.='</td>';
                } else {
                    $out.='<td class="small">';
                    if (isset($p[$k])) $out.=$p[$k];
                    else $out.='???';
                    $out.='</td>';
                }
        }
    }
    return $out;
}


function participant__get_result_table_row_pdf($columns,$p) {
    global $settings, $color;
    global $thislist_sessions;

    $pform_columns=participant__load_all_pform_fields();

    $row=array();
    foreach ($columns as $k=>$arr) {
        switch($k) {
            case 'number_noshowup':
                $row[]=$p['number_noshowup'].'/'.$p['number_reg'];
                break;
            case 'rules_signed':
                if ($settings['enable_rules_signed_tracking']=='y')  {
                    $row[]= ($p['rules_signed']!='y') ? "X" : '';
                }
                break;
            case 'subscriptions':
                $exptypes=load_external_experiment_types();
                $inv_arr=db_string_to_id_array($p[$k]);
                $inv_names=array();
                foreach($inv_arr as $inv) {
                    if (isset($exptypes[$inv]['exptype_name'])) $inv_names[]=$exptypes[$inv]['exptype_name'];
                    else $inv_names[]='undefined';
                }
                $row[]=implode(", ",$inv_names);
                break;
            case 'subpool_id':
                $subpools=subpools__get_subpools();
                $subpool_name=(isset($subpools[$p[$k]]['subpool_name']))?$subpools[$p[$k]]['subpool_name']:$p[$k];
                $row[]=$subpool_name;
                break;
            case 'status_id':
                $participant_statuses=participant_status__get_statuses();
                $pstatus_name=(isset($participant_statuses[$p[$k]]['name']))?$participant_statuses[$p[$k]]['name']:$p[$k];
                $row[]=$pstatus_name;
                break;
            case 'creation_time':
            case 'deletion_time':
            case 'last_enrolment':
            case 'last_profile_update':
            case 'last_activity':
            case 'last_login_attempt':
                if ($p[$k]) $row[]=ortime__format($p[$k],'hide_second:false');
                else  $row[]='-';
                break;
            case 'session_id':
                $row[]=session__build_name($thislist_sessions[$p['session_id']]);
                break;
            case 'payment_budget':
                if($settings['enable_payment_module']=='y' && check_allow('payments_view')) {
                    $payment_budgets=payments__load_budgets();
                    if (isset($payment_budgets[$p['payment_budget']])) $row[]=$payment_budgets[$p['payment_budget']]['budget_name']; else $row[]='-';
                }
                break;
            case 'payment_type':
                if($settings['enable_payment_module']=='y' && check_allow('payments_view')) {
                    $payment_types=payments__load_paytypes();
                    if (isset($payment_types[$p['payment_type']])) $row[]=$payment_types[$p['payment_type']]; else $row[]='-';
                }
                break;
            case 'payment_amount':
                if($settings['enable_payment_module']=='y' && check_allow('payments_view')) {
                    if ($p['payment_amt']!='') $row[]=$p['payment_amt']; else $row[]='-';
                }
                break;
            case 'pstatus_id':
                $pstatuses=expregister__get_participation_statuses();
                $row[]=$pstatuses[$p['pstatus_id']]['internal_name'];
                break;
            default:
                if (isset($pform_columns[$k])) {
                    if(preg_match("/(radioline|select_list|select_lang|radioline_lang)/",$pform_columns[$k]['type'])) {
                        if (isset($pform_columns[$k]['lang'][$p[$k]])) $row[]=lang($pform_columns[$k]['lang'][$p[$k]]);
                        else $row[]=$p[$k];
                    } else $row[]=$p[$k];
                } else {
                    if (isset($p[$k])) $row[]=$p[$k];
                    else $row[]='???';
                }
        }
    }
    foreach ($row as $k=>$v) $row[$k]=str_replace("&nbsp;"," ",$v);
    return $row;
}

function participant__load_all_pform_fields($tlang='') {
    global $lang, $preloaded_all_pform_fields;
    if (!$tlang) $tlang=lang('lang');
    if (isset($preloaded_all_pform_fields[$tlang]) && is_array($preloaded_all_pform_fields[$tlang])) return $preloaded_all_pform_fields[$tlang];
    else {
        $formfields=participantform__load(); $pform_columns=array();
        foreach ($formfields as $f) {
            if ($f['search_result_sort_order']) $f['sort_order']=$f['search_result_sort_order'];
            else $f['sort_order']=$f['mysql_column_name'];
            $f['column_name']=load_language_symbol($f['name_lang'],$tlang);
            if (!$f['column_name']) $f['column_name']=$f['name_lang'];
            if(preg_match("/(radioline|select_list)/",$f['type'])) {
                $optionvalues=explode(",",$f['option_values']);
                $optionnames=explode(",",$f['option_values_lang']);
                $f['lang']=array();
                foreach($optionvalues as $k=>$v) {
                    if (isset($optionnames[$k])) {
                        if ($tlang!=lang('lang')) {
                            $oname=load_language_symbol($optionnames[$k],$tlang);
                            if ($oname) $f['lang'][$v]=$oname;
                            else $f['lang'][$v]=$optionnames[$k];
                        } else {
                            if(isset($lang[$optionnames[$k]])) $f['lang'][$v]=$lang[$optionnames[$k]];
                            else $f['lang'][$v]=$optionnames[$k];
                        }
                    }
                }
            } elseif (preg_match("/(select_lang|radioline_lang)/",$f['type'])) {
                $f['lang']=lang__load_lang_cat($f['mysql_column_name'],$tlang);
            }
            $pform_columns[$f['mysql_column_name']]=$f;
        }
        $preloaded_all_pform_fields[$tlang]=$pform_columns;
        return $pform_columns;
    }
}


function participant__load_participant_email_fields($tlang='') {
    global $lang;
    if (!$tlang) $tlang=lang('lang');
    $formfields=participantform__load(); $result_columns=array();
    foreach ($formfields as $f) {
        if($f['admin_only']!='y') {
            $f['column_name']=load_language_symbol($f['name_lang'],$tlang);
            if (!$f['column_name']) $f['column_name']=$f['name_lang'];
            if(preg_match("/(radioline|select_list)/",$f['type'])) {
                $optionvalues=explode(",",$f['option_values']);
                $optionnames=explode(",",$f['option_values_lang']);
                $f['lang']=array();
                foreach($optionvalues as $k=>$v) {
                    if (isset($optionnames[$k])) {
                        if ($tlang!=lang('lang')) {
                            $oname=load_language_symbol($optionnames[$k],$tlang);
                            if ($oname) $f['lang'][$v]=$oname;
                            else $f['lang'][$v]=$optionnames[$k];
                        } else {
                            if(isset($lang[$optionnames[$k]])) $f['lang'][$v]=$lang[$optionnames[$k]];
                            else $f['lang'][$v]=$optionnames[$k];
                        }
                    }
                }
            } elseif (preg_match("/(select_lang|radioline_lang)/",$f['type'])) {
                $f['lang']=lang__load_lang_cat($f['mysql_column_name'],$tlang);
            }
            $result_columns[]=$f;
        }
    }
    return $result_columns;
}

function participant__load_participants_for_ids($ids=array()) {
    $participants=array();
    if (count($ids)>0) {
        $par_array=id_array_to_par_array($ids);
        $query="SELECT * FROM ".table('participants')."
                WHERE participant_id IN (".implode(',',$par_array['keys']).")";
        $result=or_query($query,$par_array['pars']);
        while ($line=pdo_fetch_assoc($result)) {
            $participants[$line['participant_id']]=$line;
        }
    }
    return $participants;
}

?>