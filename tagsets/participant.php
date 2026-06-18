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
    if (isset($line['participant_id'])) {
        return $line['participant_id'];
    } else {
        return false;
    }
}

function participant__exclude_participant($participant) {
    global $settings, $lang;
    if (lang('lang')) {
        $notice=lang('automatic_exclusion_by_system_due_to_noshows');
    } else {
        $notice=load_language_symbol('automatic_exclusion_by_system_due_to_noshows',$settings['admin_standard_language']);
    }
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
    echo '<div class="orsee-surface-card" style="margin-top: 1rem; padding: 0.45rem 0.5rem;">';
    echo '<div class="orsee-option-row-comment" style="margin: 0 0 0.42rem 0;">'.lang('part_statistics_for_lab_experiments').'</div>';
    participants__stat_laboratory($participant_id);
    echo '</div>';
}

function participants__stat_laboratory($participant_id) {
    global $settings, $lang, $color;

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

    echo '<div class="orsee-table" style="width: 100%;">';
    echo '<div class="orsee-table-row orsee-table-head">
        <div class="orsee-table-cell">'.lang('experiment').'</div>';
    //  echo '<div class="orsee-table-cell">'.lang('type').'</div>';
    echo '<div class="orsee-table-cell">'.lang('date_and_time').'</div>
        <div class="orsee-table-cell">'.lang('registered').'</div>
        <div class="orsee-table-cell">'.lang('location').'</div>
        <div class="orsee-table-cell">'.lang('participation_status').'</div>';
    if ($settings['enable_payment_module']=='y' && (check_allow('payments_view') || check_allow('payments_edit'))) {
        echo '
                <div class="orsee-table-cell">'.lang('payment_type_abbr').'</div>
                <div class="orsee-table-cell">'.lang('payment_amount_abbr').'</div>
            ';
    }
    echo '
        </div>';

    $pstatuses=expregister__get_participation_statuses();
    $laboratories=laboratories__get_laboratories();
    $payment_types=payments__load_paytypes();
    while ($p=pdo_fetch_assoc($result)) {
        $last_reg_time=0;
        //if ($p['sess_id']=='0') $last_reg_time=sessions__get_registration_end("","",$p['exp_id']);
        if ($p['sess_id']!='0' || true) { //$last_reg_time > $now) {
            $row_class='orsee-table-row';
            if ($shade) {
                $row_class.=' is-alt';
            }
            echo '<div class="'.$row_class.'">
                <div class="orsee-table-cell">
                    <A href="experiment_show.php?experiment_id='.$p['exp_id'].'">'.
                        $p['experiment_name'].'</A>
                </div>';
            /*
            echo '<div class="orsee-table-cell">
                    '.$exptypes[$p['experiment_ext_type']][lang('lang')].'
                </div>';
            */
            echo '<div class="orsee-table-cell">';
            if ($p['sess_id']!='0') {
                echo '<A HREF="experiment_participants_show.php?experiment_id='.
                            $p['exp_id'].'&session_id='.
                            $p['sess_id'].'">'.session__build_name($p).'</A>';
            } else {
                echo '-';
            }
            echo '  </div>
                <div class="orsee-table-cell">';
            if ($p['sess_id']!='0') {
                echo lang('yes');
            } else {
                echo lang('no');
            }
            echo '  </div>
                <div class="orsee-table-cell">';
            if ($p['sess_id']!='0') {
                if (isset($laboratories[$p['laboratory_id']]['lab_name'])) {
                    echo $laboratories[$p['laboratory_id']]['lab_name'];
                } else {
                    echo 'undefined';
                }
            } else {
                echo '-';
            }
            echo '</div>
                <div class="orsee-table-cell">';

            if ($p['pstatus_id']>0) {
                echo '<span style="color: ';
                if ($pstatuses[$p['pstatus_id']]['noshow']) {
                    echo 'var(--color-shownup-no)';
                } else {
                    echo 'var(--color-shownup-yes)';
                }
                echo ';">';
            }
            echo $pstatuses[$p['pstatus_id']]['internal_name'];
            if ($p['pstatus_id']>0) {
                echo '</span>';
            }
            echo '  </div>';
            if ($settings['enable_payment_module']=='y' && (check_allow('payments_view') || check_allow('payments_edit'))) {
                echo '<div class="orsee-table-cell">';
                if (isset($payment_types[$p['payment_type']])) {
                    echo $payment_types[$p['payment_type']];
                } else {
                    echo '-';
                }
                echo '</div><div class="orsee-table-cell">';
                if ($p['payment_amt']!='') {
                    echo $p['payment_amt'];
                } else {
                    echo '-';
                }
                echo '</div>';
            }
            echo '</div>';
            if ($shade) {
                $shade=false;
            } else {
                $shade=true;
            }
        }
    }
    echo '</div>';
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
        if (isset($line['participant_id'])) {
            $exists=true;
        } else {
            $exists=false;
        }
    }
    return array('participant_id'=>$participant_id,'participant_id_crypt'=>$participant_id_crypt);
}

// check if a profile field applies for a given runtime context
function participant__profile_field_is_applicable($field,$subpool_id,$scope_context='') {
    if (is_array($scope_context)) {
        $scope_matches=false;
        foreach ($scope_context as $scope_key) {
            $scope_key=trim((string)$scope_key);
            if ($scope_key!=='' && in_array($scope_key,$field['scope_contexts'],true)) {
                $scope_matches=true;
                break;
            }
        }
        if (!$scope_matches) {
            return false;
        }
    } elseif ($scope_context!=='' && !in_array($scope_context,$field['scope_contexts'],true)) {
        return false;
    }
    if (count($field['restrict_to_subpools'])>0 && !in_array((int)$subpool_id,$field['restrict_to_subpools'],true)) {
        return false;
    }
    return true;
}

// check if a layout block applies for a given runtime context
function participant__profile_block_is_applicable($block,$subpool_id,$scope_context='') {
    if (!is_array($block)) {
        return false;
    }
    if (!array_key_exists('scope_contexts',$block) || !is_array($block['scope_contexts'])) {
        return false;
    }
    $has_restrict_to_subpools=(array_key_exists('restrict_to_subpools',$block) && is_array($block['restrict_to_subpools']));
    $scope_context=trim((string)$scope_context);
    $block_applicability=array(
        'scope_contexts'=>$block['scope_contexts'],
        'restrict_to_subpools'=>($has_restrict_to_subpools ? $block['restrict_to_subpools'] : array())
    );
    return participant__profile_field_is_applicable($block_applicability,$subpool_id,$scope_context);
}

// resolve effective field policies for one runtime scope/subpool cell
function participant__profile_fields_resolve_variant($formfields,$subpool_id,$scope_context='') {
    if (!is_array($formfields)) {
        return array();
    }
    if (!is_string($scope_context)) {
        return $formfields;
    }
    $subpool_id=(int)$subpool_id;
    $scope_context=trim((string)$scope_context);

    $resolved=array();
    foreach ($formfields as $field) {
        if (!is_array($field)) {
            continue;
        }
        if (!isset($field['variants']) || !is_array($field['variants']) || count($field['variants'])===0) {
            $resolved[]=$field;
            continue;
        }

        foreach ($field['variants'] as $variant) {
            if (!is_array($variant)) {
                continue;
            }
            if (!isset($variant['scope_contexts']) || !is_array($variant['scope_contexts']) || !in_array($scope_context,$variant['scope_contexts'],true)) {
                continue;
            }
            if (!isset($variant['subpools']) || !is_array($variant['subpools']) || !in_array($subpool_id,$variant['subpools'],true)) {
                continue;
            }
            if (!isset($variant['overrides']) || !is_array($variant['overrides'])) {
                continue;
            }
            foreach ($variant['overrides'] as $key=>$value) {
                $field[$key]=$value;
            }
            break;
        }
        $resolved[]=$field;
    }
    return $resolved;
}

// CHECKS
// check unique
function participantform__check_unique($edit,$formtype,$participant_id=0) {
    global $lang, $settings, $errors__dataform;

    $disable_form=false;
    $problem=false;
    $nonunique_fields=array();
    if (!isset($edit['subpool_id']) || !$edit['subpool_id']) {
        $edit['subpool_id']=$settings['subpool_default_registration_id'];
    }
    $subpool=orsee_db_load_array("subpools",$edit['subpool_id'],"subpool_id");
    if (!$subpool['subpool_id']) {
        $subpool=orsee_db_load_array("subpools",1,"subpool_id");
    }
    $edit['subpool_id']=$subpool['subpool_id'];

    $unique_scope_context=($formtype==='create' ? 'profile_form_public_create' : 'profile_form_public_edit');
    $nonunique=participantform__get_nonunique($edit,$participant_id,$unique_scope_context);
    $active_layout_fields=array();
    $use_layout_gate=(participant__profile_render_mode()==='structured_layout');
    if ($use_layout_gate) {
        $layout=participant__load_profile_layout('profile_form_public','current');
        foreach ($layout['blocks'] as $block) {
            if (!is_array($block) || !isset($block['type'])) {
                continue;
            }
            if (trim((string)$block['type'])!=='field' || !isset($block['field'])) {
                continue;
            }
            $field_name=trim((string)$block['field']);
            if ($field_name!=='') {
                $active_layout_fields[$field_name]=true;
            }
        }
    }

    if ($formtype=='create') {
        foreach ($nonunique as $f) {
            // only check fields that apply in public profile creation
            if (participant__profile_field_is_applicable($f,$subpool['subpool_id'],'profile_form_public_create') &&
                (!$use_layout_gate || isset($active_layout_fields[$f['mysql_column_name']])) &&
                $disable_form==false &&
                $f['require_unique_on_create_page']=='y') {
                if ($f['nonunique_participants']) {
                    $errors__dataform[]=$f['mysql_column_name'];

                    $first_other_nonunique_status=participant__get_participant_status($f['nonunique_participants_list'][0]);
                    if (($first_other_nonunique_status['access_to_profile']=='n') && $f['unique_on_create_page_tell_if_deleted']=='y') {
                        message($first_other_nonunique_status['error'],'error');
                        message(lang('if_you_have_questions_write_to').' '.support_mail_link(),'warning');
                        $disable_form=true;
                    } else {
                        $problem=true;
                        $msg=participant__field_localized_text($f,'unique_on_create_page_error_message_if_exists_lang','unique_on_create_page_error_message_if_exists_lang');
                        if ($msg!=='') {
                            message($msg,'warning');
                        }
                        if ($settings['subject_authentication']=='token') {
                            // if we still use token, we send a link to edit page to first on non-unique list (if enabled for field)
                            if ($f['unique_on_create_page_email_regmail_confmail_again']=='y') {
                                message(lang('message_with_edit_link_mailed'));
                                $done=experimentmail__mail_edit_link($f['nonunique_participants_list'][0]);
                                $disable_form=true;
                            }
                        } else {
                            // if we use passwords, we direct to login page (if unique_on_create_page_email_cancel_signup is not set to 'n'
                            if (!(isset($f['unique_on_create_page_email_cancel_signup']) && $f['unique_on_create_page_email_cancel_signup']=='n')) {
                                message(lang('please_use_email_address_and_password_to_login'),'warning');
                                $disable_form=true;
                            }
                        }
                    }
                }
            }
        }
        $response=array();
        $response['disable_form']=$disable_form;
        $response['problem']=$problem;
        return $response;
    } elseif ($formtype=='edit') {
        foreach ($nonunique as $f) {
            // only check fields that apply in public profile edit
            if (participant__profile_field_is_applicable($f,$subpool['subpool_id'],'profile_form_public_edit') &&
                (!$use_layout_gate || isset($active_layout_fields[$f['mysql_column_name']])) &&
                $f['check_unique_on_edit_page']=='y') {
                if ($f['nonunique_participants']) {
                    $errors__dataform[]=$f['mysql_column_name'];
                    $problem=true;
                    $msg=participant__field_localized_text($f,'unique_on_edit_page_error_message_if_exists_lang','unique_on_edit_page_error_message_if_exists_lang');
                    if ($msg!=='') {
                        message($msg,'warning');
                    }
                }
            }
        }
    }
    $response=array();
    $response['problem']=$problem;
    return $response;
}

function participantform__get_nonunique($edit,$participant_id=0,$scope_context='') {
    $nonunique_fields=array();
    $formfields=participantform__load();
    $formfields=participant__profile_fields_resolve_variant($formfields,(isset($edit['subpool_id']) ? $edit['subpool_id'] : 0),$scope_context);
    foreach ($formfields as $f) {
        $f['nonunique_participants']=false;
        $f['nonunique_participants_list']=array();
        if (($f['require_unique_on_create_page']=='y' || $f['check_unique_on_edit_page']=='y') &&
            (isset($edit[$f['mysql_column_name']]) && $edit[$f['mysql_column_name']])) {
            $pars=array(':value'=>$edit[$f['mysql_column_name']]);
            $query="SELECT participant_id FROM ".table('participants')."
                WHERE ".$f['mysql_column_name']."= :value" ;
            if ($participant_id) {
                $query.=" AND participant_id!= :participant_id";
                $pars[':participant_id']=$participant_id;
            }
            $result=or_query($query,$pars);
            while ($line = pdo_fetch_assoc($result)) {
                $f['nonunique_participants_list'][]=$line['participant_id'];
            }
            if (count($f['nonunique_participants_list'])>0) {
                $f['nonunique_participants']=true;
                $nonunique_fields[$f['mysql_column_name']]=$f;
            }
        }
    }
    return $nonunique_fields;
}


// check fields
function participantform__check_fields(&$edit,$scope_context='') {
    global $lang, $settings;
    $errors_dataform=array();
    $allowed_fields=array();
    $scope_context=trim((string)$scope_context);

    if (!isset($edit['subpool_id']) || !$edit['subpool_id']) {
        $edit['subpool_id']=$settings['subpool_default_registration_id'];
    }
    $subpool=orsee_db_load_array("subpools",$edit['subpool_id'],"subpool_id");
    if (!$subpool['subpool_id']) {
        $subpool=orsee_db_load_array("subpools",1,"subpool_id");
    }
    $edit['subpool_id']=$subpool['subpool_id'];
    $active_layout_fields=array();
    $use_layout_gate=(participant__profile_render_mode()==='structured_layout' && $scope_context!=='');
    if ($use_layout_gate) {
        if (in_array($scope_context,array('profile_form_public_create','profile_form_public_edit','profile_form_public_admin_edit'),true)) {
            $layout=participant__load_profile_layout('profile_form_public','current');
            foreach ($layout['blocks'] as $block) {
                if (!is_array($block) || !isset($block['type'])) {
                    continue;
                }
                if (trim((string)$block['type'])!=='field' || !isset($block['field'])) {
                    continue;
                }
                $field_name=trim((string)$block['field']);
                if ($field_name!=='') {
                    $active_layout_fields[$field_name]=true;
                }
            }
        } elseif ($scope_context==='profile_form_admin_part') {
            $layout=participant__load_profile_layout('profile_form_admin_part','current');
            foreach ($layout['blocks'] as $block) {
                if (!is_array($block) || !isset($block['type'])) {
                    continue;
                }
                if (trim((string)$block['type'])!=='field' || !isset($block['field'])) {
                    continue;
                }
                $field_name=trim((string)$block['field']);
                if ($field_name!=='') {
                    $active_layout_fields[$field_name]=true;
                }
            }
        }
    }

    $formfields=participantform__load();
    $formfields=participant__profile_fields_resolve_variant($formfields,$subpool['subpool_id'],$scope_context);
    foreach ($formfields as $f) {
        $check_this_field=participant__profile_field_is_applicable($f,$subpool['subpool_id'],$scope_context);
        if ($check_this_field && $use_layout_gate && !isset($active_layout_fields[$f['mysql_column_name']])) {
            $check_this_field=false;
        }
        if ($check_this_field) {
            $allowed_fields[$f['mysql_column_name']]=$f['mysql_column_name'];
            if ($f['mysql_column_name']==='email') {
                $email_input_mode=(isset($f['email_mode']['mode']) ? trim((string)$f['email_mode']['mode']) : 'full_email');
                $email_fixed_domain=(isset($f['email_mode']['domain']) ? strtolower(trim((string)$f['email_mode']['domain'])) : '');
                $email_fixed_domain=ltrim($email_fixed_domain,'@');
                $email_fixed_domain=str_replace(' ','',$email_fixed_domain);
                if (!in_array($email_input_mode,array('full_email','local_part'),true)) {
                    $email_input_mode='full_email';
                }
                if ($email_input_mode==='local_part' && $email_fixed_domain!=='') {
                    $email_value=(isset($edit[$f['mysql_column_name']]) ? trim((string)$edit[$f['mysql_column_name']]) : '');
                    if ($email_value!=='') {
                        $at_position=strpos($email_value,'@');
                        if ($at_position!==false) {
                            $email_value=substr($email_value,0,$at_position);
                        }
                        $email_value=str_replace(' ','',trim((string)$email_value));
                        if ($email_value!=='') {
                            $edit[$f['mysql_column_name']]=$email_value.'@'.$email_fixed_domain;
                        }
                    }
                }
            }
            $date_invalid=false;
            $email_invalid=false;
            if ($f['type']==='email') {
                $email_value=(isset($edit[$f['mysql_column_name']]) ? trim((string)$edit[$f['mysql_column_name']]) : '');
                if ($email_value!=='' && !filter_var($email_value,FILTER_VALIDATE_EMAIL)) {
                    $errors_dataform[]=$f['mysql_column_name'];
                    message(lang('email_address_not_ok'),'error');
                    $email_invalid=true;
                }
            }
            if ($f['type']=='date') {
                $date_mode=(isset($f['date_mode']) ? trim((string)$f['date_mode']) : 'ymd');
                if (!in_array($date_mode,array('ymd','ym','y'))) {
                    $date_mode='ymd';
                }
                $date_d_key=$f['mysql_column_name'].'_d';
                $date_m_key=$f['mysql_column_name'].'_m';
                $date_y_key=$f['mysql_column_name'].'_y';
                $date_d=(isset($edit[$date_d_key]) ? trim((string)$edit[$date_d_key]) : '');
                $date_m=(isset($edit[$date_m_key]) ? trim((string)$edit[$date_m_key]) : '');
                $date_y=(isset($edit[$date_y_key]) ? trim((string)$edit[$date_y_key]) : '');
                $has_date_parts=($date_d!=='' || $date_m!=='' || $date_y!=='');
                if ($has_date_parts) {
                    $date_ymd=ortime__date_parts_to_ymd($date_y,$date_m,$date_d,$date_mode);
                    if ($date_ymd) {
                        $edit[$f['mysql_column_name']]=$date_ymd;
                    } else {
                        $edit[$f['mysql_column_name']]='';
                        $date_invalid=true;
                        $errors_dataform[]=$f['mysql_column_name'];
                        message(lang('error_in_dataform'),'error');
                    }
                } else {
                    $edit[$f['mysql_column_name']]='';
                }
            }
            if ($f['type']=='phone') {
                $phone_e164_key=$f['mysql_column_name'].'_e164';
                $phone_raw=(isset($edit[$f['mysql_column_name']]) ? trim((string)$edit[$f['mysql_column_name']]) : '');
                $phone_e164=(isset($edit[$phone_e164_key]) ? trim((string)$edit[$phone_e164_key]) : '');
                if ($phone_raw==='' && $phone_e164==='') {
                    $edit[$f['mysql_column_name']]='';
                } elseif ($phone_e164!=='') {
                    $phone_value=preg_replace('/[^0-9\+]/','',$phone_e164);
                    if (preg_match('/^\+[1-9][0-9]{3,14}$/',$phone_value)) {
                        $edit[$f['mysql_column_name']]=$phone_value;
                    } else {
                        $edit[$f['mysql_column_name']]=$phone_raw;
                        $errors_dataform[]=$f['mysql_column_name'];
                        message(lang('error_phone_invalid'),'error');
                    }
                } else {
                    $edit[$f['mysql_column_name']]=$phone_raw;
                    $errors_dataform[]=$f['mysql_column_name'];
                    message(lang('error_phone_invalid'),'error');
                }
            }
            if ($f['type']=='checkboxlist_lang') {
                if (isset($edit[$f['mysql_column_name']]) && is_array($edit[$f['mysql_column_name']])) {
                    $edit[$f['mysql_column_name']]=id_array_to_db_string($edit[$f['mysql_column_name']]);
                } else {
                    $edit[$f['mysql_column_name']]='';
                }
            }
            if (!$date_invalid && $f['compulsory']=='y') {
                if (!isset($edit[$f['mysql_column_name']]) || !$edit[$f['mysql_column_name']]) {
                    $errors_dataform[]=$f['mysql_column_name'];
                    $msg=participant__field_localized_text($f,'error_message_if_empty_lang','error_message_if_empty_lang');
                    if ($msg!=='') {
                        message($msg,'error');
                    }
                }
            }
            if (!$date_invalid && !$email_invalid && $f['perl_regexp']!='') {
                if (!preg_match($f['perl_regexp'],$edit[$f['mysql_column_name']])) {
                    $errors_dataform[]=$f['mysql_column_name'];
                    $msg=participant__field_localized_text($f,'error_message_if_no_regexp_match_lang','error_message_if_no_regexp_match_lang');
                    if ($msg!=='') {
                        message($msg,'error');
                    }
                }
            }
            if ($f['type']=='boolean') {
                if (isset($edit[$f['mysql_column_name']]) && $edit[$f['mysql_column_name']]=='y') {
                    $edit[$f['mysql_column_name']]='y';
                } else {
                    $edit[$f['mysql_column_name']]='n';
                }
            }
        }
    }
    if ($scope_context!=='profile_form_admin_part') {
        $subscriptions_exptypes=participant__subpool_subscription_exptypes($subpool['subpool_id'],true);
        $hide_subscriptions=(
            isset($settings['hide_subscriptions_if_single_exptype']) &&
            $settings['hide_subscriptions_if_single_exptype']==='y' &&
            count($subscriptions_exptypes)===1
        );
        if ($hide_subscriptions) {
            $single_exptype_id=(string)$subscriptions_exptypes[0];
            $_REQUEST['subscriptions']=array($single_exptype_id=>$single_exptype_id);
        }
        if (!isset($_REQUEST['subscriptions']) || !is_array($_REQUEST['subscriptions'])) {
            $_REQUEST['subscriptions']=array();
        }
        $_REQUEST['subscriptions']=id_array_to_db_string($_REQUEST['subscriptions']);
        $edit['subscriptions']=$_REQUEST['subscriptions'];
        $allowed_fields['subscriptions']='subscriptions';
        if (!$edit['subscriptions']) {
            $errors_dataform[]='subscriptions';
            message(lang('at_least_one_exptype_has_to_be_selected'),'warning');
        }
    }

    $allowed_fields=array_values($allowed_fields);
    return array(
        'errors'=>$errors_dataform,
        'sanitized'=>$edit,
        'allowed_fields'=>$allowed_fields
    );
}
/*
 * Central field editor specification for options_participant_profile_edit.php.
 * This is the single source for editable field characteristics: section, label/tooltip,
 * control metadata, defaults, and variant-overridable flags.
 */
function participant__profile_field_editor_specs() {
    $sections=array(
        array('id'=>'identity','title'=>'Basics'),
        array('id'=>'form_field_settings','title'=>'Form field settings'),
        array('id'=>'layout_properties','title'=>'Layout properties'),
        array('id'=>'field_properties','title'=>'Field properties'),
        array('id'=>'search_properties','title'=>'Search properties'),
        array('id'=>'checks','title'=>'Checks'),
        array('id'=>'uniqueness','title'=>'Uniqueness')
    );
    $fields=array(
            'enabled'=>array(
                'section_id'=>'identity',
                'label'=>lang('enabled?'),
                'tooltip'=>'Whether ORSEE should use this field or ignore the database column.',
                'default'=>'y',
                'variant_overridable'=>false,
                'control'=>array('kind'=>'yesno')
            ),
            'name_lang'=>array(
                'section_id'=>'identity',
                'label'=>lang('profile_editor_field_name'),
                'tooltip'=>'Name of this field in all installed languages. Used internally.',
                'default'=>'',
                'variant_overridable'=>false,
                'control'=>array(
                    'kind'=>'localized_text'
                )
            ),
            'scope_contexts'=>array(
                'section_id'=>'identity',
                'label'=>lang('profile_editor_scopes'),
                'tooltip'=>'Form contexts where this field applies.',
                'default'=>array(
                    'profile_form_public_create',
                    'profile_form_public_edit',
                    'profile_form_public_admin_edit'
                ),
                'variant_overridable'=>false,
                'control'=>array(
                    'kind'=>'context_checkboxes',
                    'options'=>array(
                        'profile_form_public_create'=>'Public create form',
                        'profile_form_public_edit'=>'Public edit form',
                        'profile_form_public_admin_edit'=>'Public profile form in admin area',
                        'profile_form_admin_part'=>'Admin-only form part'
                    )
                )
            ),
            'restrict_to_subpools'=>array(
                'section_id'=>'identity',
                'label'=>lang('profile_editor_restrict_to_subpools'),
                'tooltip'=>'If none selected, field applies to all subpools.',
                'default'=>array(),
                'variant_overridable'=>false,
                'control'=>array('kind'=>'subpool_checkboxes')
            ),
            'type'=>array(
                'section_id'=>'form_field_settings',
                'label'=>lang('type'),
                'tooltip'=>'The type of the participant profile field.',
                'default'=>'select_lang',
                'variant_overridable'=>true,
                'control'=>array(
                    'kind'=>'select',
                    'id'=>'type_select',
                    'options'=>array(
                        'select_lang'=>'select_lang',
                        'radioline_lang'=>'radioline_lang',
                        'checkboxlist_lang'=>'checkboxlist_lang',
                        'select_numbers'=>'select_numbers',
                        'textline'=>'textline',
                        'email'=>'email_address',
                        'phone'=>'phone',
                        'textarea'=>'textarea',
                        'select_list'=>'select_list',
                        'radioline'=>'radioline',
                        'date'=>'date',
                        'boolean'=>'yes/no'
                    ),
                    'help_blocks'=>array(
                        'select_lang'=>'A select list with a number of answer options. Answer options can be configured in "Options/Items for profile fields of type *_lang".',
                        'radioline_lang'=>'A list of radio buttons. Answer options can be configured in "Options/Items for profile fields of type *_lang".',
                        'checkboxlist_lang'=>'A list of checkboxes. Answer options can be configured in "Options/Items for profile fields of type *_lang".',
                        'select_numbers'=>'A select list with numbers. For year-dependent values you can use <span style="background: white; white-space:nowrap;">func:current_year-100</span> as Start value and <span style="background: white; white-space:nowrap;">func:current_year-17</span> as End value.',
                        'textline'=>'Asks for a line of text.',
                        'email'=>'Asks for an email address. Input is validated as email address format.',
                        'phone'=>'A phone number field with country picker.',
                        'cond-textarea'=>'Allows to enter text into a larger text.',
                        'select_list'=>'This type is only provided for backward compatibility and import (e.g. of the default gender field in ORSEE&lt;=2.3). For new fields, please use "select_lang".',
                        'radioline'=>'This type is only provided for backward compatibility and import (e.g. of the default gender field in ORSEE 2). For new fields, please use "radioline_lang".',
                        'boolean'=>'A binary yes/no field. It can be rendered either as a checkbox or as a yes-no switch.',
                        'date'=>'A date field with the date picker. Can ask for full date, only month & year, or year only.'
                    )
                )
            ),
            'include_none_option'=>array(
                'section_id'=>'form_field_settings',
                'label'=>'Include a "none" option',
                'tooltip'=>'Whether to include a value 0 / "-" option in select lists.',
                'default'=>'n',
                'variant_overridable'=>true,
                'control'=>array('kind'=>'yesno'),
                'visibility_classes'=>array('select_lang','select_numbers')
            ),
            'order_select_lang_values'=>array(
                'section_id'=>'form_field_settings',
                'label'=>'Order values',
                'tooltip'=>'Sort *_lang values alphabetically or by fixed order.',
                'default'=>'alphabetically',
                'variant_overridable'=>true,
                'control'=>array(
                    'kind'=>'select',
                    'options'=>array(
                        'alphabetically'=>'alphabetically',
                        'fixed_order'=>'fixed_order'
                    )
                ),
                'visibility_classes'=>array('select_lang')
            ),
            'order_radio_lang_values'=>array(
                'section_id'=>'form_field_settings',
                'label'=>'Order values',
                'tooltip'=>'Sort *_lang values alphabetically or by fixed order set in options table.',
                'default'=>'alphabetically',
                'variant_overridable'=>true,
                'control'=>array(
                    'kind'=>'select',
                    'options'=>array(
                        'alphabetically'=>'alphabetically',
                        'fixed_order'=>'fixed_order'
                    )
                ),
                'visibility_classes'=>array('radioline_lang','checkboxlist_lang')
            ),
            'value_begin'=>array(
                'section_id'=>'form_field_settings',
                'label'=>'Start number',
                'tooltip'=>'First number in select_numbers list.',
                'default'=>'0',
                'variant_overridable'=>true,
                'force_ltr'=>true,
                'control'=>array('kind'=>'text','size'=>10),
                'visibility_classes'=>array('select_numbers')
            ),
            'value_end'=>array(
                'section_id'=>'form_field_settings',
                'label'=>'End number',
                'tooltip'=>'Last number in select_numbers list.',
                'default'=>'1',
                'variant_overridable'=>true,
                'force_ltr'=>true,
                'control'=>array('kind'=>'text','size'=>10),
                'visibility_classes'=>array('select_numbers')
            ),
            'value_step'=>array(
                'section_id'=>'form_field_settings',
                'label'=>'Step size',
                'tooltip'=>'Step size between numbers.',
                'default'=>'0',
                'variant_overridable'=>true,
                'force_ltr'=>true,
                'control'=>array('kind'=>'text','size'=>10),
                'visibility_classes'=>array('select_numbers')
            ),
            'values_reverse'=>array(
                'section_id'=>'form_field_settings',
                'label'=>'Reverse values',
                'tooltip'=>'Display values from largest to smallest.',
                'default'=>'n',
                'variant_overridable'=>true,
                'control'=>array('kind'=>'yesno'),
                'visibility_classes'=>array('select_numbers')
            ),
            'option_values'=>array(
                'section_id'=>'form_field_settings',
                'label'=>'Option values / Language symbols',
                'tooltip'=>'List of option values and symbol names for select_list/radioline.',
                'default'=>'',
                'variant_overridable'=>false,
                'force_ltr'=>true,
                'control'=>array(
                    'kind'=>'value_lang_list'
                ),
                'visibility_classes'=>array('select_list','radioline')
            ),
            'size'=>array(
                'section_id'=>'form_field_settings',
                'label'=>'Size',
                'tooltip'=>'Display width of text field (characters).',
                'default'=>'40',
                'variant_overridable'=>true,
                'force_ltr'=>true,
                'control'=>array('kind'=>'text','size'=>20),
                'visibility_classes'=>array('textline','email')
            ),
            'phone_default_country'=>array(
                'section_id'=>'form_field_settings',
                'label'=>'Default country code',
                'tooltip'=>'Default 2-letter ISO code for phone widget.',
                'default'=>'',
                'variant_overridable'=>true,
                'control'=>array('kind'=>'phone_country_select'),
                'visibility_classes'=>array('phone')
            ),
            'phone_preferred_countries'=>array(
                'section_id'=>'form_field_settings',
                'label'=>'Preferred countries',
                'tooltip'=>'Countries shown at top of the phone country picker.',
                'default'=>array(),
                'variant_overridable'=>true,
                'control'=>array('kind'=>'phone_country_tagpicker'),
                'visibility_classes'=>array('phone')
            ),
            'phone_display_mode'=>array(
                'section_id'=>'form_field_settings',
                'label'=>'Display/input as',
                'tooltip'=>'Display and input mode for phone numbers.',
                'default'=>'national',
                'variant_overridable'=>true,
                'control'=>array(
                    'kind'=>'select',
                    'options'=>array(
                        'national'=>'national number',
                        'international'=>'international number'
                    )
                ),
                'visibility_classes'=>array('phone')
            ),
            'maxlength'=>array(
                'section_id'=>'form_field_settings',
                'label'=>'Maximal input length',
                'tooltip'=>'Maximum number of characters for textline.',
                'default'=>'100',
                'variant_overridable'=>true,
                'force_ltr'=>true,
                'control'=>array('kind'=>'text','size'=>20),
                'visibility_classes'=>array('textline','email')
            ),
            'force_ltr_direction'=>array(
                'section_id'=>'form_field_settings',
                'label'=>'Force LTR direction',
                'tooltip'=>'Force left-to-right text input even for right-to-left languages.',
                'default'=>'n',
                'variant_overridable'=>false,
                'control'=>array('kind'=>'yesno'),
                'visibility_classes'=>array('textline')
            ),
            'email_mode'=>array(
                'section_id'=>'form_field_settings',
                'label'=>'Email entry mode',
                'tooltip'=>'How participants enter their email address on profile forms.',
                'default'=>array('mode'=>'full_email','domain'=>''),
                'variant_overridable'=>true,
                'control'=>array(
                    'kind'=>'custom',
                    'html'=>'<div><label class="radio"><input type="radio" name="email_input_mode" value="full_email" {{checked:email_mode.mode=full_email}}> ask for full email address</label></div><div><label class="radio"><input type="radio" name="email_input_mode" value="local_part" {{checked:email_mode.mode=local_part}}> only ask for username part, and add domain <input class="input is-primary orsee-input orsee-input-text" style="display:inline-block; width:min(100%,18ch); margin:0 0.35rem;" type="text" name="email_fixed_domain" maxlength="120" dir="ltr" value="{{value:email_mode.domain}}"> automatically</label></div>',
                    'readonly_html'=>'<div><label class="radio"><input type="radio" value="full_email" disabled {{checked:email_mode.mode=full_email}}> ask for full email address</label></div><div><label class="radio"><input type="radio" value="local_part" disabled {{checked:email_mode.mode=local_part}}> only ask for username part, and add domain <input class="input is-primary orsee-input orsee-input-text" style="display:inline-block; width:min(100%,18ch); margin:0 0.35rem;" type="text" dir="ltr" value="{{value:email_mode.domain}}" disabled> automatically</label></div>'
                ),
                'visibility_classes'=>array('email')
            ),
            'cols'=>array(
                'section_id'=>'form_field_settings',
                'label'=>'Columns',
                'tooltip'=>'Number of characters per row in textarea.',
                'default'=>'40',
                'variant_overridable'=>true,
                'force_ltr'=>true,
                'control'=>array('kind'=>'text','size'=>20),
                'visibility_classes'=>array('cond-textarea')
            ),
            'rows'=>array(
                'section_id'=>'form_field_settings',
                'label'=>'Rows',
                'tooltip'=>'Number of rows in textarea.',
                'default'=>'3',
                'variant_overridable'=>true,
                'force_ltr'=>true,
                'control'=>array('kind'=>'text','size'=>20),
                'visibility_classes'=>array('cond-textarea')
            ),
            'wrap'=>array(
                'section_id'=>'form_field_settings',
                'label'=>'wrap',
                'tooltip'=>'Textarea wrapping mode.',
                'default'=>'virtual',
                'variant_overridable'=>true,
                'control'=>array(
                    'kind'=>'select',
                    'options'=>array(
                        'virtual'=>'virtual',
                        'physical'=>'physical',
                        'off'=>'off'
                    )
                ),
                'visibility_classes'=>array('cond-textarea')
            ),
            'boolean_display'=>array(
                'section_id'=>'form_field_settings',
                'label'=>'Display as',
                'tooltip'=>'Rendering mode for yes/no fields.',
                'default'=>'checkbox',
                'variant_overridable'=>true,
                'control'=>array(
                    'kind'=>'select',
                    'options'=>array(
                        'checkbox'=>'checkbox',
                        'switchy'=>'yes-no-switch'
                    )
                ),
                'visibility_classes'=>array('boolean')
            ),
            'label_lang'=>array(
                'section_id'=>'layout_properties',
                'label'=>'Field label',
                'tooltip'=>'Label shown in participant profile form above the field.',
                'default'=>'',
                'variant_overridable'=>true,
                'control'=>array('kind'=>'localized_text')
            ),
            'text_before_lang'=>array(
                'section_id'=>'layout_properties',
                'label'=>'Text before',
                'tooltip'=>'Text shown directly before the input field.',
                'default'=>'',
                'variant_overridable'=>true,
                'control'=>array('kind'=>'localized_text')
            ),
            'text_after_lang'=>array(
                'section_id'=>'layout_properties',
                'label'=>'Text after',
                'tooltip'=>'Text shown directly after the input field.',
                'default'=>'',
                'variant_overridable'=>true,
                'control'=>array('kind'=>'localized_text')
            ),
            'help_text_lang'=>array(
                'section_id'=>'layout_properties',
                'label'=>'Help text',
                'tooltip'=>'Help text shown below the field label.',
                'default'=>'',
                'variant_overridable'=>true,
                'control'=>array(
                    'kind'=>'localized_textarea',
                    'rows'=>2
                )
            ),
            'default_value'=>array(
                'section_id'=>'field_properties',
                'label'=>'Default value',
                'tooltip'=>'Prefilled field value on profile creation form.',
                'default'=>'',
                'variant_overridable'=>true,
                'force_ltr_if_type'=>array('select_lang','radioline_lang','select_numbers','select_list','radioline','email','boolean'),
                'control'=>array('kind'=>'text'),
                'visibility_classes'=>array('select_lang','radioline_lang','checkboxlist_lang','select_numbers','textline','email','cond-textarea','select_list','radioline','boolean')
            ),
            'date_default_mode'=>array(
                'section_id'=>'field_properties',
                'label'=>'Date default',
                'tooltip'=>'How default value for date field is determined.',
                'default'=>'none',
                'variant_overridable'=>true,
                'control'=>array(
                    'kind'=>'select',
                    'id'=>'date_default_mode_select',
                    'options'=>array(
                        'none'=>'none',
                        'fixed'=>'fixed date',
                        'today'=>'current date'
                    )
                ),
                'visibility_classes'=>array('date')
            ),
            'date_mode'=>array(
                'section_id'=>'field_properties',
                'label'=>'Date format',
                'tooltip'=>'Date precision used for input and display.',
                'default'=>'ymd',
                'variant_overridable'=>true,
                'control'=>array(
                    'kind'=>'select',
                    'options'=>array(
                        'ymd'=>'full date',
                        'ym'=>'month and year',
                        'y'=>'year only'
                    )
                ),
                'visibility_classes'=>array('date')
            ),
            'date_default_value'=>array(
                'section_id'=>'field_properties',
                'label'=>'Fixed default date',
                'tooltip'=>'Default date when date default mode is fixed.',
                'default'=>'',
                'variant_overridable'=>true,
                'control'=>array('kind'=>'date_picker'),
                'visibility_classes'=>array('date','date-default-fixed')
            ),
            'include_in_statistics'=>array(
                'section_id'=>'field_properties',
                'label'=>'Include in statistics',
                'tooltip'=>'Whether to include this field in participant statistics.',
                'default'=>'n',
                'variant_overridable'=>false,
                'control'=>array(
                    'kind'=>'select',
                    'options'=>array(
                        'n'=>'n',
                        'pie'=>'pie',
                        'bars'=>'bars'
                    )
                )
            ),
            'search_include_in_participant_query'=>array(
                'section_id'=>'search_properties',
                'label'=>'Include in participant search',
                'tooltip'=>'Include this field in participant query builder.',
                'default'=>'n',
                'variant_overridable'=>false,
                'control'=>array('kind'=>'yesno')
            ),
            'search_include_in_experiment_assign_query'=>array(
                'section_id'=>'search_properties',
                'label'=>'Include in experiment assignment search',
                'tooltip'=>'Include this field in assign-participants query builder.',
                'default'=>'n',
                'variant_overridable'=>false,
                'control'=>array('kind'=>'yesno')
            ),
            'search_result_sort_order'=>array(
                'section_id'=>'search_properties',
                'label'=>'Search result sort order',
                'tooltip'=>'Optional comma-separated column list used as SQL sort order.',
                'default'=>'',
                'variant_overridable'=>false,
                'force_ltr'=>true,
                'control'=>array('kind'=>'text')
            ),
            'link_as_email_in_lists'=>array(
                'section_id'=>'search_properties',
                'label'=>'Link as email in result lists',
                'tooltip'=>'Treat field value as email when rendering result lists.',
                'default'=>'n',
                'variant_overridable'=>false,
                'control'=>array('kind'=>'yesno')
            ),
            'use_as_secondary_email'=>array(
                'section_id'=>'search_properties',
                'label'=>'Use as secondary email',
                'tooltip'=>'Use this textline as secondary participant email target.',
                'default'=>'n',
                'variant_overridable'=>false,
                'control'=>array('kind'=>'yesno'),
                'visibility_classes'=>array('textline','email')
            ),
            'compulsory'=>array(
                'section_id'=>'checks',
                'label'=>'Compulsory?',
                'tooltip'=>'Whether this field must be non-empty.',
                'default'=>'n',
                'variant_overridable'=>true,
                'control'=>array('kind'=>'yesno')
            ),
            'error_message_if_empty_lang'=>array(
                'section_id'=>'checks',
                'label'=>'Field is empty - Error text',
                'tooltip'=>'Error text for compulsory-but-empty validation.',
                'default'=>'',
                'variant_overridable'=>true,
                'control'=>array(
                    'kind'=>'localized_text'
                )
            ),
            'perl_regexp'=>array(
                'section_id'=>'checks',
                'label'=>'PERL Regular Expression',
                'tooltip'=>'Validation regex pattern applied to submitted value.',
                'default'=>'',
                'variant_overridable'=>true,
                'force_ltr'=>true,
                'control'=>array('kind'=>'text')
            ),
            'error_message_if_no_regexp_match_lang'=>array(
                'section_id'=>'checks',
                'label'=>'No pattern match - Error text',
                'tooltip'=>'Error text for regex mismatch.',
                'default'=>'',
                'variant_overridable'=>true,
                'control'=>array(
                    'kind'=>'localized_text'
                )
            ),
            'require_unique_on_create_page'=>array(
                'section_id'=>'uniqueness',
                'label'=>'Field must be unique on profile creation page',
                'tooltip'=>'Require uniqueness when participant creates profile.',
                'default'=>'n',
                'variant_overridable'=>true,
                'control'=>array('kind'=>'yesno')
            ),
            'unique_on_create_page_error_message_if_exists_lang'=>array(
                'section_id'=>'uniqueness',
                'label'=>'Not unique on profile creation - Error text',
                'tooltip'=>'Error text when uniqueness check on creation fails.',
                'default'=>'',
                'variant_overridable'=>true,
                'control'=>array(
                    'kind'=>'localized_text'
                )
            ),
            'unique_on_create_page_tell_if_deleted'=>array(
                'section_id'=>'uniqueness',
                'label'=>'If not unique, tell if profile is unsubscribed',
                'tooltip'=>'Show special info if matching profile exists but is unsubscribed.',
                'default'=>'n',
                'variant_overridable'=>true,
                'control'=>array('kind'=>'yesno')
            ),
            'unique_on_create_page_email_regmail_confmail_again'=>array(
                'section_id'=>'uniqueness',
                'label'=>'If not unique, email profile access link',
                'tooltip'=>'TOKEN auth only: resend confirmation/access email on create uniqueness conflict.',
                'default'=>'n',
                'variant_overridable'=>true,
                'control'=>array('kind'=>'yesno')
            ),
            'check_unique_on_edit_page'=>array(
                'section_id'=>'uniqueness',
                'label'=>'Field must be unique on profile change',
                'tooltip'=>'Require uniqueness when participant edits profile.',
                'default'=>'n',
                'variant_overridable'=>true,
                'control'=>array('kind'=>'yesno')
            ),
            'unique_on_edit_page_error_message_if_exists_lang'=>array(
                'section_id'=>'uniqueness',
                'label'=>'Not unique on profile change - Error text',
                'tooltip'=>'Error text when uniqueness check on edit fails.',
                'default'=>'',
                'variant_overridable'=>true,
                'control'=>array(
                    'kind'=>'localized_text'
                )
            )
    );
    foreach ($fields as $key=>$spec) {
        $fields[$key]['key']=$key;
    }
    return array('sections'=>$sections,'fields'=>$fields);
}

/*
 * Return baseline editor field sets for a profile field.
 * Variant pages can further reduce display fields (for example by variant_overridable).
 */
function participant__get_editable_display_fields($profile_field_specs,$field_name='') {
    $all_fields=array_keys($profile_field_specs['fields']);
    if (in_array($field_name,array('language','subscriptions'),true)) {
        return array(
            'editable_fields'=>array('label_lang','text_before_lang','text_after_lang','help_text_lang'),
            'display_fields'=>array('enabled','type','name_lang','scope_contexts','restrict_to_subpools','label_lang','text_before_lang','text_after_lang','help_text_lang')
        );
    }
    if ($field_name==='email') {
        return array(
            'editable_fields'=>array(
                'size','maxlength','error_message_if_empty_lang','perl_regexp','error_message_if_no_regexp_match_lang',
                'unique_on_create_page_error_message_if_exists_lang','unique_on_create_page_tell_if_deleted',
                'unique_on_create_page_email_regmail_confmail_again','unique_on_edit_page_error_message_if_exists_lang',
                'default_value','link_as_email_in_lists','email_mode','label_lang','text_before_lang','text_after_lang','help_text_lang'
            ),
            'display_fields'=>$all_fields
        );
    }
    return array('editable_fields'=>$all_fields,'display_fields'=>$all_fields);
}

/*
 * Render profile field editor controls from the central field spec.
 * In variant mode, each property row is shown with an Override checkbox column.
 */
function participant__profile_field_editor_render_controls($profile_specs, $field, $variant_mode=false, $variant_overrides=array(), $changed_keys=array()) {
    global $editable_fields, $display_fields;
    $languages=get_languages();
    $lang_dirs=lang__is_rtl_all_langs();
    if (!is_array($variant_overrides)) {
        $variant_overrides=array();
    }
    $render_values=$field;
    foreach ($variant_overrides as $override_key=>$override_value) {
        $render_values[$override_key]=$override_value;
    }
    $override_keys=array_keys($variant_overrides);
    $out='';
    foreach ($profile_specs['sections'] as $section_spec) {
        $section_id=trim((string)$section_spec['id']);
        if ($section_id==='') {
            continue;
        }

        $has_visible_fields=false;
        foreach ($profile_specs['fields'] as $key=>$field_spec) {
            if ((string)$field_spec['section_id']!==$section_id) {
                continue;
            }
            if (isset($display_fields) && is_array($display_fields) && !in_array($key,$display_fields,true)) {
                continue;
            }
            $has_visible_fields=true;
            break;
        }
        if (!$has_visible_fields) {
            continue;
        }

        $section_title=trim((string)$section_spec['title']);
        if ($section_title!=='') {
            if ($variant_mode) {
                $out.='<div class="field orsee-form-row-grid" style="grid-template-columns: minmax(8rem, 10rem) minmax(0, 1fr);">';
                $out.='<div class="orsee-form-row-col"></div>';
                $out.='<div class="orsee-form-row-col"><div class="orsee-option-row-comment"><strong>'.htmlspecialchars($section_title,ENT_QUOTES).'</strong></div></div>';
                $out.='</div>';
            } else {
                $out.='<div class="field"><div class="orsee-option-row-comment"><strong>'.htmlspecialchars($section_title,ENT_QUOTES).'</strong></div></div>';
            }
        }

        foreach ($profile_specs['fields'] as $key=>$field_spec) {
            if ((string)$field_spec['section_id']!==$section_id) {
                continue;
            }
            if (isset($display_fields) && is_array($display_fields) && !in_array($key,$display_fields,true)) {
                continue;
            }
            $is_changed=in_array($key,$changed_keys,true);

            $classes=array('field');
            $visibility_classes=array();
            $tooltip=trim((string)$field_spec['tooltip']);
            if ($tooltip!=='') {
                $classes[]='tooltip';
            }
            if ($is_changed && !$variant_mode) {
                $classes[]='orsee-track-changed-left';
            }
            if (isset($field_spec['visibility_classes']) && is_array($field_spec['visibility_classes']) && count($field_spec['visibility_classes'])>0) {
                $visibility_classes=$field_spec['visibility_classes'];
                $classes[]='condfield';
                foreach ($visibility_classes as $vclass) {
                    $vclass=trim((string)$vclass);
                    if ($vclass!=='') {
                        $classes[]=$vclass;
                    }
                }
            }

            $label=(string)$field_spec['label'];
            $row_html='<div class="'.implode(' ',$classes).'"';
            if ($tooltip!=='') {
                $row_html.=' title="'.htmlspecialchars($tooltip,ENT_QUOTES).'"';
            }
            $row_html.='>';
            $row_html.='<label class="label">'.$label.'</label>';
            $row_html.='<div class="control">';

            $kind=(string)$field_spec['control']['kind'];
            $effective_type=(isset($render_values['type']) ? trim((string)$render_values['type']) : '');
            $force_ltr=(isset($field_spec['force_ltr']) && $field_spec['force_ltr']);
            if (
                !$force_ltr &&
                isset($field_spec['force_ltr_if_type']) &&
                is_array($field_spec['force_ltr_if_type']) &&
                $effective_type!=='' &&
                in_array($effective_type,$field_spec['force_ltr_if_type'],true)
            ) {
                $force_ltr=true;
            }
            if ($kind==='yesno') {
                $row_html.=pform_options_yesnoradio($key,$render_values);
            } elseif ($key==='default_value') {
                $default_value=(isset($render_values[$key]) ? (string)$render_values[$key] : '');
                $lang_order='alphabetically';
                if ($effective_type==='select_lang' && isset($render_values['order_select_lang_values']) && $render_values['order_select_lang_values']==='fixed_order') {
                    $lang_order='fixed_order';
                }
                if (($effective_type==='radioline_lang' || $effective_type==='checkboxlist_lang') && isset($render_values['order_radio_lang_values']) && $render_values['order_radio_lang_values']==='fixed_order') {
                    $lang_order='fixed_order';
                }
                $lang_options=lang__load_lang_cat((string)$render_values['mysql_column_name'],lang('lang'),$lang_order);
                if (in_array($key,$editable_fields,true)) {
                    $default_options_boolean=array(
                        ''=>lang('none'),
                        'y'=>lang('y'),
                        'n'=>lang('n')
                    );
                    $default_options_lang=array(''=>lang('none')) + $lang_options;
                    $default_checkbox_values=db_string_to_id_array($default_value);
                    $size=(int)$field_spec['control']['size'];
                    if ($size<1) {
                        $size=25;
                    }
                    $text_attrs='';
                    if ($force_ltr) {
                        $text_attrs.=' dir="ltr"';
                    }
                    if (isset($field_spec['force_ltr_if_type']) && is_array($field_spec['force_ltr_if_type'])) {
                        $text_attrs.=' data-force-ltr-if-type="'.implode(',',$field_spec['force_ltr_if_type']).'"';
                    }

                    $row_html.='<div class="orsee-default-value-mode condfield boolean">';
                    $row_html.=pform_options_selectfield($key,$default_options_boolean,$render_values);
                    $row_html.='</div>';

                    $row_html.='<div class="orsee-default-value-mode condfield select_lang radioline_lang">';
                    $row_html.=pform_options_selectfield($key,$default_options_lang,$render_values);
                    $row_html.='</div>';

                    $row_html.='<div class="orsee-default-value-mode condfield checkboxlist_lang">';
                    if (count($lang_options)===0) {
                        $row_html.='Create options in "Options/Items for profile fields" to be able to select a default here.';
                    } else {
                        $row_html.=pform_options_checkboxrow($key,$lang_options,$default_checkbox_values);
                    }
                    $row_html.='</div>';

                    $row_html.='<div class="orsee-default-value-mode condfield cond-textarea">';
                    $row_html.='<textarea class="textarea is-primary orsee-textarea" name="'.$key.'" rows="2" wrap="virtual">'.htmlspecialchars($default_value,ENT_QUOTES).'</textarea>';
                    $row_html.='</div>';

                    $row_html.='<div class="orsee-default-value-mode condfield select_numbers textline email select_list radioline">';
                    $row_html.=pform_options_inputtext($key,$render_values,$size,$text_attrs);
                    $row_html.='</div>';
                } elseif ($effective_type==='boolean') {
                    if ($default_value==='y') {
                        $row_html.=lang('y');
                    } elseif ($default_value==='n') {
                        $row_html.=lang('n');
                    } else {
                        $row_html.=lang('none');
                    }
                } elseif ($effective_type==='select_lang' || $effective_type==='radioline_lang') {
                    if ($default_value!=='' && isset($lang_options[$default_value])) {
                        $row_html.=htmlspecialchars((string)$lang_options[$default_value],ENT_QUOTES);
                    } elseif ($default_value==='') {
                        $row_html.=lang('none');
                    } else {
                        $row_html.=htmlspecialchars($default_value,ENT_QUOTES);
                    }
                } elseif ($effective_type==='checkboxlist_lang') {
                    $default_values=db_string_to_id_array($default_value);
                    if (count($default_values)===0) {
                        $row_html.=lang('none');
                    } else {
                        $selected_labels=array_values(array_intersect_key($lang_options,array_flip($default_values)));
                        $row_html.=htmlspecialchars(implode(', ',$selected_labels),ENT_QUOTES);
                    }
                } elseif ($effective_type==='textarea') {
                    $row_html.=nl2br(htmlspecialchars($default_value,ENT_QUOTES));
                } else {
                    $row_html.=htmlspecialchars($default_value,ENT_QUOTES);
                }
            } elseif ($kind==='text') {
                $size=(int)$field_spec['control']['size'];
                if ($size<1) {
                    $size=25;
                }
                $text_attrs='';
                if ($force_ltr) {
                    $text_attrs.=' dir="ltr"';
                }
                if (isset($field_spec['force_ltr_if_type']) && is_array($field_spec['force_ltr_if_type'])) {
                    $text_attrs.=' data-force-ltr-if-type="'.implode(',',$field_spec['force_ltr_if_type']).'"';
                }
                $row_html.=pform_options_inputtext($key,$render_values,$size,$text_attrs);
            } elseif ($kind==='select') {
                $options=$field_spec['control']['options'];
                $select_id=(isset($field_spec['control']['id']) ? (string)$field_spec['control']['id'] : '');
                if ($key==='type') {
                    $current_type=(isset($render_values['type']) ? trim((string)$render_values['type']) : '');
                    if ($current_type==='select_list' || $current_type==='radioline') {
                        $legacy_label=(isset($options[$current_type]) ? $options[$current_type] : $current_type);
                        $options=array($current_type=>$legacy_label);
                        $row_html.=pform_options_selectfield($key,$options,$render_values,$select_id);
                        if (!$variant_mode && in_array($key,$editable_fields,true)) {
                            $convert_target=($current_type==='select_list' ? 'select_lang' : 'radioline_lang');
                            $row_html.=' <button class="button orsee-btn orsee-btn-compact" type="submit" name="convert_legacy_type" value="'.$convert_target.'">Convert to '.$convert_target.'</button>';
                        }
                    } else {
                        unset($options['select_list'],$options['radioline']);
                        $row_html.=pform_options_selectfield($key,$options,$render_values,$select_id);
                    }
                } else {
                    $row_html.=pform_options_selectfield($key,$options,$render_values,$select_id);
                }
            } elseif ($kind==='phone_country_select') {
                $selected_iso=(string)$render_values[$key];
                $row_html.=pform_options_selectfield($key,pform_options_phone_country_options($selected_iso),$render_values);
            } elseif ($kind==='phone_country_tagpicker') {
                $selected_isos=(isset($render_values[$key]) && is_array($render_values[$key]) ? $render_values[$key] : array());
                $country_options=pform_options_phone_country_options();
                if (in_array($key,$editable_fields,true)) {
                    $row_html.=get_tag_picker($key,$country_options,$selected_isos,array('prompt_text'=>lang('choose').' ...'));
                } else {
                    $selected_labels=array_values(array_intersect_key($country_options,array_flip($selected_isos)));
                    $row_html.=htmlspecialchars(implode(', ',$selected_labels),ENT_QUOTES);
                }
            } elseif ($kind==='value_lang_list') {
                $row_html.=pform_options_vallanglist('option_values','option_values_lang',$render_values['option_values'],$force_ltr);
            } elseif ($kind==='context_checkboxes') {
                $selected_contexts=(isset($render_values[$key]) && is_array($render_values[$key]) ? $render_values[$key] : array());
                $context_options=(isset($field_spec['control']['options']) && is_array($field_spec['control']['options']) ? $field_spec['control']['options'] : array());
                if (in_array($key,$editable_fields,true)) {
                    $row_html.=pform_options_checkboxrow($key,$context_options,$selected_contexts);
                } else {
                    $selected_context_labels=array_values(array_intersect_key($context_options,array_flip($selected_contexts)));
                    $row_html.=htmlspecialchars(implode(', ',$selected_context_labels),ENT_QUOTES);
                }
            } elseif ($kind==='subpool_checkboxes') {
                $selected_subpools=array();
                if (isset($render_values[$key]) && is_array($render_values[$key])) {
                    foreach ($render_values[$key] as $pool_id) {
                        $pool_id=(int)$pool_id;
                        if ($pool_id>0 && !in_array($pool_id,$selected_subpools,true)) {
                            $selected_subpools[]=$pool_id;
                        }
                    }
                }
                $all_subpools=subpools__get_subpools();
                $subpool_options=array();
                foreach ($all_subpools as $pool) {
                    $subpool_options[(string)$pool['subpool_id']]=$pool['subpool_name'];
                }
                $selected_subpools=array_map('strval',$selected_subpools);
                if (in_array($key,$editable_fields,true)) {
                    $row_html.=pform_options_checkboxrow($key,$subpool_options,$selected_subpools);
                } else {
                    if (count($selected_subpools)===0) {
                        $selected_subpools=array_keys($subpool_options);
                    }
                    $selected_subpool_labels=array();
                    foreach ($selected_subpools as $selected_subpool_id) {
                        if (isset($subpool_options[$selected_subpool_id])) {
                            $selected_subpool_labels[]=$subpool_options[$selected_subpool_id];
                        }
                    }
                    $row_html.=htmlspecialchars(implode(', ',$selected_subpool_labels),ENT_QUOTES);
                }
            } elseif ($kind==='localized_text' || $kind==='localized_textarea') {
                $localized_value=$render_values[$key];
                foreach ($languages as $language) {
                    $lang_value='';
                    $field_dir=(isset($lang_dirs[$language]) && $lang_dirs[$language] ? 'rtl' : 'ltr');
                    if (is_array($localized_value)) {
                        if (isset($localized_value[$language])) {
                            $lang_value=(string)$localized_value[$language];
                        }
                    } elseif ($key==='label_lang') {
                        $lang_value=participant__field_localized_text($render_values,'name_lang','name_lang',$language);
                    } else {
                        $legacy_symbol=trim((string)$localized_value);
                        if ($legacy_symbol!=='') {
                            $lang_value=load_language_symbol($legacy_symbol,$language);
                        }
                    }
                    $row_html.='<div class="field is-flex is-align-items-center">';
                    $row_html.='<label class="label">'.$language.':</label>';
                    if (in_array($key,$editable_fields)) {
                        if ($kind==='localized_textarea') {
                            $rows=(isset($field_spec['control']['rows']) ? (int)$field_spec['control']['rows'] : 2);
                            if ($rows<1) {
                                $rows=2;
                            }
                            $row_html.='<div class="control is-flex-grow-1"><textarea class="textarea is-primary orsee-textarea" dir="'.$field_dir.'" name="'.$key.'_text_lang['.$language.']" rows="'.$rows.'" wrap="virtual">'.htmlspecialchars($lang_value,ENT_QUOTES).'</textarea></div>';
                        } else {
                            $row_html.='<div class="control is-flex-grow-1"><input class="input is-primary orsee-input orsee-input-text" dir="'.$field_dir.'" type="text" name="'.$key.'_text_lang['.$language.']" value="'.htmlspecialchars($lang_value,ENT_QUOTES).'"></div>';
                        }
                    } else {
                        $row_html.='<div class="control is-flex-grow-1" dir="'.$field_dir.'">'.htmlspecialchars($lang_value,ENT_QUOTES).'</div>';
                    }
                    $row_html.='</div>';
                }
            } elseif ($kind==='custom') {
                $is_editable=in_array($key,$editable_fields,true);
                $template_key=($is_editable ? 'html' : 'readonly_html');
                $custom_html=(isset($field_spec['control'][$template_key]) ? (string)$field_spec['control'][$template_key] : '');
                if ($custom_html==='') {
                    $custom_html=(string)$field_spec['control']['html'];
                }
                $resolve_path=function ($source, $path) {
                    $parts=explode('.',trim((string)$path));
                    $cursor=$source;
                    foreach ($parts as $part) {
                        if ($part==='') {
                            continue;
                        }
                        if (!is_array($cursor) || !array_key_exists($part,$cursor)) {
                            return '';
                        }
                        $cursor=$cursor[$part];
                    }
                    if (is_array($cursor)) {
                        return '';
                    }
                    return (string)$cursor;
                };
                $custom_html=preg_replace_callback('/\{\{value:([a-zA-Z0-9_.-]+)\}\}/',function ($m) use ($render_values,$resolve_path) {
                    return htmlspecialchars($resolve_path($render_values,$m[1]),ENT_QUOTES);
                },$custom_html);
                $custom_html=preg_replace_callback('/\{\{checked:([a-zA-Z0-9_.-]+)=([^}]+)\}\}/',function ($m) use ($render_values,$resolve_path) {
                    return ($resolve_path($render_values,$m[1])===(string)$m[2] ? 'checked' : '');
                },$custom_html);
                $custom_html=preg_replace_callback('/\{\{selected:([a-zA-Z0-9_.-]+)=([^}]+)\}\}/',function ($m) use ($render_values,$resolve_path) {
                    return ($resolve_path($render_values,$m[1])===(string)$m[2] ? 'selected' : '');
                },$custom_html);
                $row_html.=$custom_html;
            } elseif ($kind==='date_picker') {
                $date_mode=(isset($render_values['date_mode']) ? trim((string)$render_values['date_mode']) : 'ymd');
                if (!in_array($date_mode,array('ymd','ym','y'),true)) {
                    $date_mode='ymd';
                }
                if (in_array($key,$editable_fields,true)) {
                    $date_default_selected=0;
                    if (isset($render_values['date_default_value']) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/',(string)$render_values['date_default_value'],$fdm)) {
                        $date_default_selected=$fdm[1].$fdm[2].$fdm[3].'0000';
                    } elseif (isset($render_values['default_value']) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/',(string)$render_values['default_value'],$fdm2)) {
                        $date_default_selected=$fdm2[1].$fdm2[2].$fdm2[3].'0000';
                    }
                    $row_html.=formhelpers__pick_date('date_default_value',$date_default_selected,0,0,false,true,$date_mode);
                } else {
                    $date_value=trim((string)$render_values[$key]);
                    if ($date_value!=='') {
                        $row_html.=ortime__format_ymd_localized($date_value,'',$date_mode);
                    }
                }
            } elseif ($kind==='hidden') {
                if (in_array($key,$editable_fields,true)) {
                    $row_html.='<input type="hidden" name="'.$key.'" value="'.htmlspecialchars((string)$render_values[$key],ENT_QUOTES).'">';
                }
            } else {
                trigger_error('Unknown profile field editor control kind: '.$kind,E_USER_ERROR);
            }

            $row_html.='</div></div>';
            if ($variant_mode) {
                $checked=(in_array($key,$override_keys,true) ? ' checked' : '');
                $variant_row_classes=array('field','orsee-form-row-grid');
                if (count($visibility_classes)>0) {
                    $variant_row_classes[]='condfield';
                    foreach ($visibility_classes as $vclass) {
                        $vclass=trim((string)$vclass);
                        if ($vclass!=='') {
                            $variant_row_classes[]=$vclass;
                        }
                    }
                }
                $override_cell_class='orsee-form-row-col';
                if ($is_changed) {
                    $override_cell_class.=' orsee-track-changed-left';
                }
                $out.='<div class="'.implode(' ',$variant_row_classes).'" style="grid-template-columns: minmax(8rem, 10rem) minmax(0, 1fr); align-items: stretch;">';
                $out.='<div class="'.$override_cell_class.'" style="background: var(--color-list-shade2); padding: 0.28rem 0.55rem; border-radius: 0.32rem;"><label class="checkbox"><input type="checkbox" name="override['.$key.']" value="y"'.$checked.'> Override</label></div>';
                $out.='<div class="orsee-form-row-col">'.$row_html.'</div>';
                $out.='</div>';
            } else {
                $out.=$row_html;
            }

            if ($key==='type') {
                foreach ($field_spec['control']['help_blocks'] as $help_class=>$help_text) {
                    $out.='<div class="field condfield '.$help_class.'">';
                    $out.='<div class="orsee-message-box orsee-callout-notice"><div class="orsee-message-box-body">'.$help_text.'</div></div>';
                    $out.='</div>';
                }
            }
        }
    }

    return $out;
}

/*
 * Validate baseline profile field editor submission and return normalized properties.
 */
function participant__profile_field_editor_validate_submission($field, $field_type, $profile_field_specs, $input, $editable_fields) {
    $languages=get_languages();
    $field_type=trim((string)$field_type);
    $prop_array=array();
    foreach ($profile_field_specs['fields'] as $k=>$field_spec) {
        if ($k==='mysql_column_name' || $k==='type' || $k==='enabled') {
            continue;
        }
        if ($k==='scope_contexts') {
            if (in_array($k,$editable_fields,true)) {
                $scope_contexts=array();
                $allowed_contexts=$field_spec['control']['options'];
                if (isset($input[$k]) && is_array($input[$k])) {
                    foreach ($input[$k] as $context_key) {
                        $context_key=trim((string)$context_key);
                        if ($context_key!=='' && isset($allowed_contexts[$context_key]) && !in_array($context_key,$scope_contexts,true)) {
                            $scope_contexts[]=$context_key;
                        }
                    }
                }
                $prop_array[$k]=$scope_contexts;
            } else {
                $prop_array[$k]=$field[$k];
            }
            continue;
        }
        if ($k==='restrict_to_subpools') {
            if (in_array($k,$editable_fields,true)) {
                $restrict_to_subpools=array();
                if (isset($input[$k]) && is_array($input[$k])) {
                    foreach ($input[$k] as $subpool_id) {
                        $subpool_id=(int)$subpool_id;
                        if ($subpool_id>0 && !in_array($subpool_id,$restrict_to_subpools,true)) {
                            $restrict_to_subpools[]=$subpool_id;
                        }
                    }
                }
                $prop_array[$k]=$restrict_to_subpools;
            } else {
                $prop_array[$k]=$field[$k];
            }
            continue;
        }
        if ($k==='option_values') {
            if (in_array('option_values',$editable_fields,true) && isset($input['option_values']) && is_array($input['option_values'])) {
                $legacy_option_values=array();
                $legacy_option_symbols=array();
                foreach ($input['option_values'] as $ok=>$ov) {
                    $ov=trim((string)$ov);
                    if ($ov==='') {
                        continue;
                    }
                    $os=(isset($input['option_values_lang'][$ok]) ? trim((string)$input['option_values_lang'][$ok]) : '');
                    if ($os==='') {
                        $os=$ov;
                    }
                    $legacy_option_values[]=$ov;
                    $legacy_option_symbols[]=$os;
                }
                if (count($legacy_option_values)>0) {
                    $prop_array[$k]=array_combine($legacy_option_values,$legacy_option_symbols);
                } else {
                    $prop_array[$k]=array();
                }
            } else {
                $prop_array[$k]=$field[$k];
            }
            continue;
        }
        if ($k==='phone_preferred_countries') {
            if (in_array($k,$editable_fields,true)) {
                $selected_isos=array();
                if (isset($input[$k]) && is_string($input[$k])) {
                    $selected_isos=array_map('trim',explode(',',$input[$k]));
                }
                $selected_isos=array_map('strtolower',$selected_isos);
                $selected_isos=array_filter($selected_isos,function ($iso) {
                    return (bool)preg_match('/^[a-z]{2}$/',$iso);
                });
                $prop_array[$k]=array_values(array_unique($selected_isos));
            } else {
                $prop_array[$k]=$field[$k];
            }
            continue;
        }
        if ($k==='email_mode') {
            if ($field_type==='email' && in_array('email_mode',$editable_fields,true)) {
                $email_input_mode=(isset($input['email_input_mode']) ? trim((string)$input['email_input_mode']) : 'full_email');
                if (!in_array($email_input_mode,array('full_email','local_part'),true)) {
                    $email_input_mode='full_email';
                }
                $email_fixed_domain=(isset($input['email_fixed_domain']) ? strtolower(trim((string)$input['email_fixed_domain'])) : '');
                $email_fixed_domain=ltrim($email_fixed_domain,'@');
                $email_fixed_domain=str_replace(' ','',$email_fixed_domain);
                if ($email_input_mode==='local_part' && $email_fixed_domain==='') {
                    $email_input_mode='full_email';
                }
                $prop_array[$k]=array('mode'=>$email_input_mode,'domain'=>$email_fixed_domain);
            } else {
                $prop_array[$k]=$field[$k];
            }
            continue;
        }
        if (isset($field_spec['control']['kind']) && in_array($field_spec['control']['kind'],array('localized_text','localized_textarea'),true)) {
            if (in_array($k,$editable_fields,true)) {
                $texts=array();
                foreach ($languages as $language) {
                    $texts[$language]=(isset($input[$k.'_text_lang'][$language]) ? trim((string)$input[$k.'_text_lang'][$language]) : '');
                }
                $prop_array[$k]=$texts;
            } else {
                $prop_array[$k]=$field[$k];
            }
            continue;
        }
        if ($k==='default_value') {
            if (in_array($k,$editable_fields,true)) {
                if ($field_type==='textarea') {
                    $prop_array[$k]=(isset($input[$k]) ? (string)$input[$k] : '');
                } else {
                    $default_value=(isset($input[$k]) ? trim((string)$input[$k]) : '');
                    if ($field_type==='boolean') {
                        if (!in_array($default_value,array('','y','n'),true)) {
                            $default_value='';
                        }
                    } elseif ($field_type==='select_lang' || $field_type==='radioline_lang') {
                        $order='alphabetically';
                        if ($field_type==='select_lang' && isset($field['order_select_lang_values']) && $field['order_select_lang_values']==='fixed_order') {
                            $order='fixed_order';
                        }
                        if ($field_type==='radioline_lang' && isset($field['order_radio_lang_values']) && $field['order_radio_lang_values']==='fixed_order') {
                            $order='fixed_order';
                        }
                        $allowed_defaults=lang__load_lang_cat((string)$field['mysql_column_name'],lang('lang'),$order);
                        if ($default_value!=='' && !isset($allowed_defaults[$default_value])) {
                            $default_value='';
                        }
                    } elseif ($field_type==='checkboxlist_lang') {
                        $allowed_defaults=lang__load_lang_cat((string)$field['mysql_column_name'],lang('lang'),'alphabetically');
                        if (isset($field['order_radio_lang_values']) && $field['order_radio_lang_values']==='fixed_order') {
                            $allowed_defaults=lang__load_lang_cat((string)$field['mysql_column_name'],lang('lang'),'fixed_order');
                        }
                        $selected_defaults=array();
                        if (isset($input[$k]) && is_array($input[$k])) {
                            foreach ($input[$k] as $selected_default) {
                                $selected_default=trim((string)$selected_default);
                                if ($selected_default!=='' && isset($allowed_defaults[$selected_default])) {
                                    $selected_defaults[]=$selected_default;
                                }
                            }
                        }
                        $selected_defaults=array_values(array_unique($selected_defaults));
                        $prop_array[$k]=id_array_to_db_string($selected_defaults);
                        continue;
                    }
                    $prop_array[$k]=$default_value;
                }
            } else {
                $prop_array[$k]=$field[$k];
            }
            continue;
        }
        if ($field_type==='date' && $k==='date_default_mode') {
            if (in_array($k,$editable_fields,true) && isset($input[$k])) {
                $date_default_mode=trim((string)$input[$k]);
                if (!in_array($date_default_mode,array('none','fixed','today'),true)) {
                    $date_default_mode='none';
                }
                $prop_array[$k]=$date_default_mode;
            } else {
                $prop_array[$k]=$field[$k];
            }
            continue;
        }
        if ($field_type==='date' && $k==='date_mode') {
            if (in_array($k,$editable_fields,true) && isset($input[$k])) {
                $date_mode=trim((string)$input[$k]);
                if (!in_array($date_mode,array('ymd','ym','y'),true)) {
                    $date_mode='ymd';
                }
                $prop_array[$k]=$date_mode;
            } else {
                $prop_array[$k]=$field[$k];
            }
            continue;
        }
        if ($field_type==='date' && $k==='date_default_value') {
            $date_default_mode=(isset($prop_array['date_default_mode']) ? $prop_array['date_default_mode'] : 'none');
            $date_mode=(isset($prop_array['date_mode']) ? $prop_array['date_mode'] : 'ymd');
            if ($date_default_mode==='fixed') {
                $prop_array[$k]=ortime__date_parts_to_ymd(
                    (isset($input['date_default_value_y']) ? $input['date_default_value_y'] : ''),
                    (isset($input['date_default_value_m']) ? $input['date_default_value_m'] : ''),
                    (isset($input['date_default_value_d']) ? $input['date_default_value_d'] : ''),
                    $date_mode
                );
            } else {
                $prop_array[$k]='';
            }
            continue;
        }
        if (in_array($k,$editable_fields,true) && isset($input[$k])) {
            if (is_array($input[$k])) {
                foreach ($input[$k] as $tk=>$tv) {
                    if ($tv) {
                        $input[$k][$tk]=trim($tv);
                    } else {
                        unset($input[$k][$tk]);
                    }
                }
                $prop_array[$k]=implode(',',$input[$k]);
            } else {
                $prop_array[$k]=trim($input[$k]);
            }
        } else {
            $prop_array[$k]=$field[$k];
        }
    }
    if (in_array('profile_form_admin_part',$prop_array['scope_contexts'],true) &&
        in_array('profile_form_public_admin_edit',$prop_array['scope_contexts'],true)) {
        $prop_array['scope_contexts']=array_values(array_diff($prop_array['scope_contexts'],array('profile_form_admin_part')));
        message(lang('error_profile_field_only_one_admin_scope_possible'),'error');
    }

    $normalized_for_save=participant__profile_field_properties_normalize(
        array_merge($field,$prop_array),
        $profile_field_specs
    );
    unset($normalized_for_save['mysql_column_name'],$normalized_for_save['type'],$normalized_for_save['enabled']);
    return $normalized_for_save;
}

function participant__profile_field_policy_default_properties($profile_field_specs) {
    $defaults=array();
    foreach ($profile_field_specs['fields'] as $key=>$field_spec) {
        if (array_key_exists('default',$field_spec)) {
            $defaults[$key]=$field_spec['default'];
        } else {
            $defaults[$key]='';
        }
    }
    return $defaults;
}

/*
 * Normalize a field properties array to the canonical policy property shape.
 * Unknown keys are dropped. Missing keys are filled from spec defaults.
 */
function participant__profile_field_properties_normalize($properties, $profile_field_specs) {
    if (!is_array($properties)) {
        $properties=array();
    }
    $normalized=array();
    if (isset($properties['mysql_column_name'])) {
        $normalized['mysql_column_name']=(string)$properties['mysql_column_name'];
    }
    foreach ($profile_field_specs['fields'] as $key=>$field_spec) {
        if (array_key_exists($key,$properties)) {
            $normalized[$key]=$properties[$key];
        } elseif (array_key_exists('default',$field_spec)) {
            $normalized[$key]=$field_spec['default'];
        } else {
            $normalized[$key]='';
        }
    }
    $scope_context_options=$profile_field_specs['fields']['scope_contexts']['control']['options'];
    $public_scope_contexts=$profile_field_specs['fields']['scope_contexts']['default'];

    // legacy bridge: admin_only -> scope_contexts
    if (!array_key_exists('scope_contexts',$properties) && array_key_exists('admin_only',$properties)) {
        $legacy_admin_only=trim((string)$properties['admin_only']);
        if ($legacy_admin_only==='y') {
            $normalized['scope_contexts']=array('profile_form_admin_part');
        } else {
            $normalized['scope_contexts']=$public_scope_contexts;
        }
    }

    // keep only known scope keys, unique, and in definition order
    if (isset($normalized['scope_contexts'])) {
        if (!is_array($normalized['scope_contexts'])) {
            $scope_contexts_selected=array();
        } else {
            $scope_contexts_selected=array_map('trim',$normalized['scope_contexts']);
        }
        $normalized['scope_contexts']=array();
        foreach ($scope_context_options as $scope_context=>$scope_label) {
            if (in_array($scope_context,$scope_contexts_selected,true)) {
                $normalized['scope_contexts'][]=$scope_context;
            }
        }
    }
    if (!array_key_exists('restrict_to_subpools',$properties) && array_key_exists('subpools',$properties)) {
        $legacy_subpools=trim((string)$properties['subpools']);
        if ($legacy_subpools==='' || $legacy_subpools==='all') {
            $normalized['restrict_to_subpools']=array();
        } else {
            $normalized['restrict_to_subpools']=explode(',',$legacy_subpools);
        }
    }
    if (isset($normalized['restrict_to_subpools'])) {
        if (!is_array($normalized['restrict_to_subpools'])) {
            $normalized['restrict_to_subpools']=array();
        }
        $normalized['restrict_to_subpools']=array_map('intval',$normalized['restrict_to_subpools']);
        $normalized['restrict_to_subpools']=array_values(array_unique($normalized['restrict_to_subpools']));
        $normalized['restrict_to_subpools']=array_values(
            array_intersect($normalized['restrict_to_subpools'],array_keys(subpools__get_subpools()))
        );
    }
    if (isset($normalized['option_values']) && !is_array($normalized['option_values'])) {
        $option_values=array();
        if (isset($properties['option_values_lang']) && !is_array($properties['option_values_lang']) && trim((string)$normalized['option_values'])!=='' && trim((string)$properties['option_values_lang'])!=='') {
            $option_values=array_map('trim',explode(',',trim((string)$normalized['option_values'])));
            $option_symbols=array_map('trim',explode(',',trim((string)$properties['option_values_lang'])));
            if (count($option_values)===count($option_symbols)) {
                $option_values=array_combine($option_values,$option_symbols);
            }
        }
        $normalized['option_values']=$option_values;
    }
    if (isset($normalized['email_mode'])) {
        if (!is_array($normalized['email_mode'])) {
            $normalized['email_mode']=array();
        }
        $email_mode=(isset($normalized['email_mode']['mode']) ? trim((string)$normalized['email_mode']['mode']) : 'full_email');
        if (!in_array($email_mode,array('full_email','local_part'),true)) {
            $email_mode='full_email';
        }
        $email_domain=(isset($normalized['email_mode']['domain']) ? strtolower(trim((string)$normalized['email_mode']['domain'])) : '');
        $email_domain=ltrim($email_domain,'@');
        $email_domain=str_replace(' ','',$email_domain);
        if ($email_mode==='local_part' && $email_domain==='') {
            $email_mode='full_email';
        }
        $normalized['email_mode']=array('mode'=>$email_mode,'domain'=>$email_domain);
    }
    if (isset($normalized['phone_preferred_countries'])) {
        if (!is_array($normalized['phone_preferred_countries'])) {
            $normalized['phone_preferred_countries']=array();
        }
        $selected_isos=array_map('strtolower',$normalized['phone_preferred_countries']);
        $selected_isos=array_map('trim',$selected_isos);
        $selected_isos=array_filter($selected_isos,function ($iso) {
            return (bool)preg_match('/^[a-z]{2}$/',$iso);
        });
        $selected_isos=array_values(array_unique($selected_isos));
        $country_options=pform_options_phone_country_options();
        $normalized['phone_preferred_countries']=array();
        foreach ($country_options as $iso=>$country_label) {
            if (in_array($iso,$selected_isos,true)) {
                $normalized['phone_preferred_countries'][]=$iso;
            }
        }
    }
    if (isset($normalized['mysql_column_name']) && strtolower(trim((string)$normalized['mysql_column_name']))==='email' && isset($normalized['type']) && $normalized['type']==='textline') {
        $normalized['type']='email';
    }
    if (isset($normalized['type']) && $normalized['type']==='date') {
        if ((!isset($normalized['date_default_mode']) || !$normalized['date_default_mode']) && isset($normalized['default_value']) && preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)$normalized['default_value'])) {
            $normalized['date_default_mode']='fixed';
            if (!isset($normalized['date_default_value']) || !$normalized['date_default_value']) {
                $normalized['date_default_value']=$normalized['default_value'];
            }
        }
        $normalized['default_value']='';
    }
    return $normalized;
}

/*
 * Compare current and draft policy state and return only actual differences.
 */
function participant__profile_field_policy_diff($current,$draft) {
    $changes=array();

    if ($current['enabled']!==$draft['enabled']) {
        $changes['enabled']=true;
    }
    if ($current['type']!==$draft['type']) {
        $changes['type']=true;
    }

    $baseline_changes=array();
    $baseline_keys=array_unique(array_merge(array_keys($current['baseline']),array_keys($draft['baseline'])));
    foreach ($baseline_keys as $key) {
        $current_value=(array_key_exists($key,$current['baseline']) ? $current['baseline'][$key] : null);
        $draft_value=(array_key_exists($key,$draft['baseline']) ? $draft['baseline'][$key] : null);
        if ($current_value!==$draft_value) {
            $baseline_changes[]=$key;
        }
    }
    if (count($baseline_changes)>0) {
        $changes['baseline']=$baseline_changes;
    }

    $variant_changes=array();
    $added=array();
    $changed=array();
    $variant_keys=array_unique(array_merge(array_keys($current['variants']),array_keys($draft['variants'])));
    foreach ($variant_keys as $variant_key) {
        $has_current=array_key_exists($variant_key,$current['variants']);
        $has_draft=array_key_exists($variant_key,$draft['variants']);
        if (!$has_current && $has_draft) {
            $added[]=(string)$variant_key;
            continue;
        }
        if (!$has_current || !$has_draft) {
            continue;
        }
        $variant_current=$current['variants'][$variant_key];
        $variant_draft=$draft['variants'][$variant_key];
        $variant_diff=array();
        if ($variant_current['scope_contexts']!==$variant_draft['scope_contexts']) {
            $variant_diff[]='scope_contexts';
        }
        if ($variant_current['subpools']!==$variant_draft['subpools']) {
            $variant_diff[]='subpools';
        }
        $override_diff=array();
        $override_keys=array_unique(array_merge(array_keys($variant_current['overrides']),array_keys($variant_draft['overrides'])));
        foreach ($override_keys as $key) {
            $current_value=(array_key_exists($key,$variant_current['overrides']) ? $variant_current['overrides'][$key] : null);
            $draft_value=(array_key_exists($key,$variant_draft['overrides']) ? $variant_draft['overrides'][$key] : null);
            if ($current_value!==$draft_value) {
                $override_diff[]=$key;
            }
        }
        if (count($override_diff)>0) {
            $variant_diff=array_merge($variant_diff,$override_diff);
        }
        if (count($variant_diff)>0) {
            $changed[(string)$variant_key]=array_values(array_unique($variant_diff));
        }
    }
    if (count($added)>0) {
        $variant_changes['added']=$added;
    }
    if (count($changed)>0) {
        $variant_changes['changed']=$changed;
    }
    if (count($variant_changes)>0) {
        $changes['variants']=$variant_changes;
    }

    return $changes;
}

/*
 * Load one profile-field policy in canonical draft/current shape.
 * Supports legacy flat properties and current non-draft JSON.
 */
function participant__profile_field_policy_load($field,$profile_field_specs=array()) {
    $field_name=(isset($field['mysql_column_name']) ? trim((string)$field['mysql_column_name']) : '');
    $current_enabled=(isset($field['enabled']) && in_array((string)$field['enabled'],array('0','n'),true) ? 'n' : 'y');
    $current_type=(isset($field['type']) && trim((string)$field['type'])!=='' ? trim((string)$field['type']) : $profile_field_specs['fields']['type']['default']);
    $allowed_types=array_keys($profile_field_specs['fields']['type']['control']['options']);
    if (!in_array($current_type,$allowed_types,true)) {
        $current_type=$profile_field_specs['fields']['type']['default'];
    }

    $properties_raw=db_string_to_property_array((string)$field['properties']);

    // normalize one baseline properties set
    $baseline_properties=$properties_raw;
    if (isset($properties_raw['current']) && is_array($properties_raw['current']) && isset($properties_raw['current']['baseline']) && is_array($properties_raw['current']['baseline'])) {
        $baseline_properties=$properties_raw['current']['baseline'];
    } elseif (isset($properties_raw['baseline']) && is_array($properties_raw['baseline'])) {
        $baseline_properties=$properties_raw['baseline'];
    }
    $baseline_source=array_merge($field,$baseline_properties);
    $current_baseline=participant__profile_field_properties_normalize($baseline_source,$profile_field_specs);
    unset($current_baseline['mysql_column_name'],$current_baseline['enabled'],$current_baseline['type'],$current_baseline['variants']);

    $known_subpool_ids=array_keys(subpools__get_subpools());
    $scope_options=$profile_field_specs['fields']['scope_contexts']['control']['options'];

    // normalize variants to the stored editor shape (scope_contexts + subpools + overrides)
    $variants_source=array();
    if (isset($properties_raw['variants']) && is_array($properties_raw['variants'])) {
        $variants_source=$properties_raw['variants'];
    }
    if (isset($properties_raw['current']) && is_array($properties_raw['current']) && isset($properties_raw['current']['variants']) && is_array($properties_raw['current']['variants'])) {
        $variants_source=$properties_raw['current']['variants'];
    }
    $current_variants=array();
    foreach ($variants_source as $variant_key=>$variant) {
        if (!is_array($variant)) {
            continue;
        }
        $scope_contexts_raw=(isset($variant['scope_contexts']) && is_array($variant['scope_contexts']) ? $variant['scope_contexts'] : array());
        $scope_contexts_selected=array();
        foreach ($scope_options as $scope_context=>$scope_label) {
            if (in_array($scope_context,$scope_contexts_raw,true)) {
                $scope_contexts_selected[]=$scope_context;
            }
        }

        $subpools_raw=(isset($variant['subpools']) && is_array($variant['subpools']) ? $variant['subpools'] : array());
        $subpools=array_map('intval',$subpools_raw);
        $subpools=array_values(array_unique($subpools));
        $subpools=array_values(array_intersect($subpools,$known_subpool_ids));

        $overrides=array();
        if (isset($variant['overrides']) && is_array($variant['overrides'])) {
            $override_values=participant__profile_field_properties_normalize(array_merge($current_baseline,$variant['overrides']),$profile_field_specs);
            foreach ($variant['overrides'] as $key=>$value) {
                if (!array_key_exists($key,$profile_field_specs['fields'])) {
                    continue;
                }
                $overrides[$key]=$override_values[$key];
            }
        }
        $current_variants[(string)$variant_key]=array(
            'scope_contexts'=>$scope_contexts_selected,
            'subpools'=>$subpools,
            'overrides'=>$overrides
        );
    }

    // draft defaults to current unless explicit draft data exists
    $draft_enabled=$current_enabled;
    $draft_type=$current_type;
    $draft_baseline=$current_baseline;
    $draft_variants=$current_variants;
    if (isset($properties_raw['draft']) && is_array($properties_raw['draft'])) {
        if (isset($properties_raw['draft']['enabled']) && in_array((string)$properties_raw['draft']['enabled'],array('y','n'),true)) {
            $draft_enabled=(string)$properties_raw['draft']['enabled'];
        }
        if (isset($properties_raw['draft']['type']) && in_array((string)$properties_raw['draft']['type'],$allowed_types,true)) {
            $draft_type=(string)$properties_raw['draft']['type'];
        }

        $draft_baseline_source=$current_baseline;
        if (isset($properties_raw['draft']['baseline']) && is_array($properties_raw['draft']['baseline'])) {
            $draft_baseline_source=array_merge($current_baseline,$properties_raw['draft']['baseline']);
        }
        $draft_baseline=participant__profile_field_properties_normalize($draft_baseline_source,$profile_field_specs);
        unset($draft_baseline['mysql_column_name'],$draft_baseline['enabled'],$draft_baseline['type'],$draft_baseline['variants']);

        $draft_variants_source=$current_variants;
        if (isset($properties_raw['draft']['variants']) && is_array($properties_raw['draft']['variants'])) {
            $draft_variants_source=$properties_raw['draft']['variants'];
        }
        $draft_variants=array();
        foreach ($draft_variants_source as $variant_key=>$variant) {
            if (!is_array($variant)) {
                continue;
            }
            $scope_contexts_raw=(isset($variant['scope_contexts']) && is_array($variant['scope_contexts']) ? $variant['scope_contexts'] : array());
            $scope_contexts_selected=array();
            foreach ($scope_options as $scope_context=>$scope_label) {
                if (in_array($scope_context,$scope_contexts_raw,true)) {
                    $scope_contexts_selected[]=$scope_context;
                }
            }

            $subpools_raw=(isset($variant['subpools']) && is_array($variant['subpools']) ? $variant['subpools'] : array());
            $subpools=array_map('intval',$subpools_raw);
            $subpools=array_values(array_unique($subpools));
            $subpools=array_values(array_intersect($subpools,$known_subpool_ids));

            $overrides=array();
            if (isset($variant['overrides']) && is_array($variant['overrides'])) {
                $override_values=participant__profile_field_properties_normalize(array_merge($draft_baseline,$variant['overrides']),$profile_field_specs);
                foreach ($variant['overrides'] as $key=>$value) {
                    if (!array_key_exists($key,$profile_field_specs['fields'])) {
                        continue;
                    }
                    $overrides[$key]=$override_values[$key];
                }
            }
            $draft_variants[(string)$variant_key]=array(
                'scope_contexts'=>$scope_contexts_selected,
                'subpools'=>$subpools,
                'overrides'=>$overrides
            );
        }
    }

    $changes=participant__profile_field_policy_diff(
        array(
            'enabled'=>$current_enabled,
            'type'=>$current_type,
            'baseline'=>$current_baseline,
            'variants'=>$current_variants
        ),
        array(
            'enabled'=>$draft_enabled,
            'type'=>$draft_type,
            'baseline'=>$draft_baseline,
            'variants'=>$draft_variants
        )
    );

    return array(
        'mysql_column_name'=>$field_name,
        'current'=>array(
            'baseline'=>$current_baseline,
            'variants'=>$current_variants
        ),
        'draft'=>array(
            'enabled'=>$draft_enabled,
            'type'=>$draft_type,
            'baseline'=>$draft_baseline,
            'variants'=>$draft_variants
        ),
        'changes'=>$changes
    );
}

/*
 * Save one profile-field policy in canonical draft/current JSON shape.
 * Table columns enabled/type remain the live/current values.
 */
function participant__profile_field_policy_save($field,$policy,$profile_field_specs=array()) {
    $field_name=trim((string)$field['mysql_column_name']);
    $current_enabled=(isset($field['enabled']) && in_array((string)$field['enabled'],array('0','n'),true) ? 0 : 1);
    $current_type=trim((string)$field['type']);
    $allowed_types=array_keys($profile_field_specs['fields']['type']['control']['options']);
    if (!in_array($current_type,$allowed_types,true)) {
        $current_type=$profile_field_specs['fields']['type']['default'];
    }
    $current_policy=participant__profile_field_policy_load($field,$profile_field_specs);

    // normalize incoming draft against current draft baseline and known field definitions
    $draft_enabled=$current_policy['draft']['enabled'];
    if (isset($policy['enabled']) && in_array((string)$policy['enabled'],array('y','n'),true)) {
        $draft_enabled=(string)$policy['enabled'];
    }
    $draft_type=$current_policy['draft']['type'];
    if (isset($policy['type']) && in_array((string)$policy['type'],$allowed_types,true)) {
        $draft_type=(string)$policy['type'];
    }

    $draft_baseline_source=$current_policy['draft']['baseline'];
    if (isset($policy['baseline']) && is_array($policy['baseline'])) {
        $draft_baseline_source=$policy['baseline'];
    }
    $draft_baseline=participant__profile_field_properties_normalize($draft_baseline_source,$profile_field_specs);
    unset($draft_baseline['mysql_column_name'],$draft_baseline['enabled'],$draft_baseline['type'],$draft_baseline['variants']);

    $known_subpool_ids=array_keys(subpools__get_subpools());
    $scope_options=$profile_field_specs['fields']['scope_contexts']['control']['options'];
    $draft_variants_source=$current_policy['draft']['variants'];
    if (isset($policy['variants']) && is_array($policy['variants'])) {
        $draft_variants_source=$policy['variants'];
    }
    $draft_variants=array();
    foreach ($draft_variants_source as $variant_key=>$variant) {
        if (!is_array($variant)) {
            continue;
        }
        $scope_contexts_raw=(isset($variant['scope_contexts']) && is_array($variant['scope_contexts']) ? $variant['scope_contexts'] : array());
        $scope_contexts_selected=array();
        foreach ($scope_options as $scope_context=>$scope_label) {
            if (in_array($scope_context,$scope_contexts_raw,true)) {
                $scope_contexts_selected[]=$scope_context;
            }
        }

        $subpools_raw=(isset($variant['subpools']) && is_array($variant['subpools']) ? $variant['subpools'] : array());
        $subpools=array_map('intval',$subpools_raw);
        $subpools=array_values(array_unique($subpools));
        $subpools=array_values(array_intersect($subpools,$known_subpool_ids));

        $overrides=array();
        if (isset($variant['overrides']) && is_array($variant['overrides'])) {
            $override_values=participant__profile_field_properties_normalize(array_merge($draft_baseline,$variant['overrides']),$profile_field_specs);
            foreach ($variant['overrides'] as $key=>$value) {
                if (!array_key_exists($key,$profile_field_specs['fields'])) {
                    continue;
                }
                $overrides[$key]=$override_values[$key];
            }
        }
        $draft_variants[(string)$variant_key]=array(
            'scope_contexts'=>$scope_contexts_selected,
            'subpools'=>$subpools,
            'overrides'=>$overrides
        );
    }

    $save_properties=array(
        'current'=>array(
            'baseline'=>$current_policy['current']['baseline'],
            'variants'=>$current_policy['current']['variants']
        ),
        'draft'=>array(
            'enabled'=>$draft_enabled,
            'type'=>$draft_type,
            'baseline'=>$draft_baseline,
            'variants'=>$draft_variants
        )
    );

    $save_field=array(
        'mysql_column_name'=>$field_name,
        'enabled'=>$current_enabled,
        'type'=>$current_type,
        'properties'=>property_array_to_db_string($save_properties)
    );
    return orsee_db_save_array($save_field,'profile_fields',$field_name,'mysql_column_name');
}

/*
 * Activate one profile-field draft by copying draft policy to current.
 * Also syncs live enabled/type table columns from draft.
 */
function participant__profile_field_policy_activate_draft($field,$profile_field_specs=array()) {
    $field_name=trim((string)$field['mysql_column_name']);
    $policy=participant__profile_field_policy_load($field,$profile_field_specs);
    $allowed_types=array_keys($profile_field_specs['fields']['type']['control']['options']);

    $enabled=(isset($policy['draft']['enabled']) && $policy['draft']['enabled']==='n' ? 0 : 1);
    $type=(isset($policy['draft']['type']) ? trim((string)$policy['draft']['type']) : '');
    if (!in_array($type,$allowed_types,true)) {
        $type=$profile_field_specs['fields']['type']['default'];
    }

    $save_properties=array(
        'current'=>array(
            'baseline'=>$policy['draft']['baseline'],
            'variants'=>$policy['draft']['variants']
        ),
        'draft'=>array(
            'enabled'=>$policy['draft']['enabled'],
            'type'=>$type,
            'baseline'=>$policy['draft']['baseline'],
            'variants'=>$policy['draft']['variants']
        )
    );
    $save_field=array(
        'mysql_column_name'=>$field_name,
        'enabled'=>$enabled,
        'type'=>$type,
        'properties'=>property_array_to_db_string($save_properties)
    );
    return orsee_db_save_array($save_field,'profile_fields',$field_name,'mysql_column_name');
}

/*
 * Prepare system profile fields with fixed policy constraints.
 * Returns false for non-system fields.
 */
function participant__system_profile_field_prepare($field_name,$field,$profile_field_specs) {
    $field_name=trim((string)$field_name);
    if (!in_array($field_name,array('email','language','subscriptions'),true)) {
        return false;
    }
    if (!is_array($field)) {
        $field=array();
    }
    if ($field_name==='email') {
        $system_defaults=array('type'=>'email','name_lang'=>'email');
    } elseif ($field_name==='language') {
        $system_defaults=array('type'=>'language','name_lang'=>'language');
    } else {
        $system_defaults=array('type'=>'subscriptions','name_lang'=>'invitations');
    }
    $field=array_merge(array(
        'mysql_column_name'=>$field_name,
        'enabled'=>1,
        'type'=>$system_defaults['type'],
        'name_lang'=>$system_defaults['name_lang']
    ),$field);
    $field['mysql_column_name']=$field_name;
    $field['enabled']=1;
    $field['type']=$system_defaults['type'];
    $field['name_lang']=$system_defaults['name_lang'];
    $field['scope_contexts']=$profile_field_specs['fields']['scope_contexts']['default'];
    $field['restrict_to_subpools']=array();
    return participant__profile_field_properties_normalize($field,$profile_field_specs);
}

/*
 * Canonical profile field policy JSON shape (stored in or_profile_fields.properties).
 *
 * baseline:
 * - scope.contexts: allowed contexts for baseline behavior.
 *   Allowed context values are:
 *   - profile_form_public_create
 *   - profile_form_public_edit
 *   - profile_form_public_admin_edit
 *   - profile_form_admin_part
 * - scope.subpool_ids: empty array means "all subpools".
 * - properties: baseline field behavior values.
 *
 * variants:
 * - ordered list of scoped overrides.
 * - each variant contains:
 *   - scope.contexts
 *   - scope.subpool_ids
 *   - overrides: only keys present here override baseline properties.
 */
function participant__profile_field_policy_schema() {
    $profile_field_specs=participant__profile_field_editor_specs();
    return array(
        'mysql_column_name'=>'',
        'baseline'=>array(
            'scope'=>array(
                'contexts'=>$profile_field_specs['fields']['scope_contexts']['default'],
                'subpool_ids'=>array()
            ),
            'properties'=>participant__profile_field_policy_default_properties($profile_field_specs)
        ),
        'variants'=>array()
    );
}

function participant__profile_field_policy_variant_schema() {
    return array(
        'scope'=>array(
            'contexts'=>array(),
            'subpool_ids'=>array()
        ),
        'overrides'=>array()
    );
}

function participant__field_localized_text($field,$json_property_name,$legacy_symbol_property_name,$language='') {
    global $settings;
    if (!is_array($field)) {
        return '';
    }
    if (!$language) {
        $language=lang('lang');
    }
    $fallback_lang=(isset($settings['public_standard_language']) ? $settings['public_standard_language'] : '');
    $text_lang=array();
    if (array_key_exists($json_property_name,$field) && is_array($field[$json_property_name])) {
        $text_lang=$field[$json_property_name];
    } elseif (array_key_exists($legacy_symbol_property_name,$field) && is_array($field[$legacy_symbol_property_name])) {
        $text_lang=$field[$legacy_symbol_property_name];
    }
    if (count($text_lang)>0) {
        if (isset($text_lang[$language]) && trim((string)$text_lang[$language])!=='') {
            return trim((string)$text_lang[$language]);
        }
        if ($fallback_lang!=='' && isset($text_lang[$fallback_lang]) && trim((string)$text_lang[$fallback_lang])!=='') {
            return trim((string)$text_lang[$fallback_lang]);
        }
        foreach ($text_lang as $txt) {
            if (trim((string)$txt)!=='') {
                return trim((string)$txt);
            }
        }
        return '';
    }
    if (!isset($field[$legacy_symbol_property_name]) || trim((string)$field[$legacy_symbol_property_name])==='') {
        return '';
    }
    return load_language_symbol(trim((string)$field[$legacy_symbol_property_name]),$language);
}

function participantform__load($layout_state='current') {
    global $preloaded_participant_form;
    if (!in_array($layout_state,array('current','draft'),true)) {
        $layout_state='current';
    }
    if (isset($preloaded_participant_form[$layout_state]) && is_array($preloaded_participant_form[$layout_state]) && count($preloaded_participant_form[$layout_state])>0) {
        return $preloaded_participant_form[$layout_state];
    }
    $profile_field_specs=participant__profile_field_editor_specs();
    $query="SELECT * FROM ".table('profile_fields');
    $result=or_query($query);
    $pform=array();
    $pform_indexes=array();
    while ($line=pdo_fetch_assoc($result)) {
        $properties_raw=db_string_to_property_array((string)$line['properties']);
        $has_current_baseline=(isset($properties_raw['current']) && is_array($properties_raw['current']) && isset($properties_raw['current']['baseline']) && is_array($properties_raw['current']['baseline']));
        $has_draft_baseline=(isset($properties_raw['draft']) && is_array($properties_raw['draft']) && isset($properties_raw['draft']['baseline']) && is_array($properties_raw['draft']['baseline']));
        if ($layout_state==='current' && $has_draft_baseline && !$has_current_baseline) {
            continue;
        }
        $policy=participant__profile_field_policy_load($line,$profile_field_specs);
        if ($layout_state==='draft') {
            $field_enabled=$policy['draft']['enabled'];
            $field_type=$policy['draft']['type'];
        } else {
            $field_enabled=(isset($line['enabled']) && in_array((string)$line['enabled'],array('0','n'),true) ? 'n' : 'y');
            $field_type=(isset($line['type']) ? trim((string)$line['type']) : '');
            if ($field_type==='') {
                $field_type=$profile_field_specs['fields']['type']['default'];
            }
        }
        if ($field_enabled!=='y') {
            continue;
        }
        $normalized=$policy[$layout_state]['baseline'];
        $normalized['mysql_column_name']=$line['mysql_column_name'];
        $normalized['enabled']=$field_enabled;
        $normalized['type']=$field_type;
        $normalized['variants']=$policy[$layout_state]['variants'];
        $normalized['has_changes']=(count($policy['changes'])>0);
        $system_field=participant__system_profile_field_prepare($normalized['mysql_column_name'],$normalized,$profile_field_specs);
        if (is_array($system_field)) {
            $normalized=$system_field;
            $normalized['variants']=$policy[$layout_state]['variants'];
            $normalized['has_changes']=(count($policy['changes'])>0);
        }
        $pform_indexes[$normalized['mysql_column_name']]=count($pform);
        $pform[]=$normalized;
    }
    $required_system_fields=array('email','language','subscriptions');
    foreach ($required_system_fields as $field_name) {
        if (isset($pform_indexes[$field_name])) {
            continue;
        }
        $system_field=participant__system_profile_field_prepare($field_name,array(),$profile_field_specs);
        if (!is_array($system_field)) {
            continue;
        }
        $pform_indexes[$field_name]=count($pform);
        $system_field['has_changes']=false;
        $pform[]=$system_field;
    }
    $preloaded_participant_form[$layout_state]=$pform;
    return $pform;
}

function participant__profile_layout_item_name($context='profile_form_public',$variant='current') {
    if (!in_array($context,array('profile_form_public','profile_form_admin_part'),true)) {
        $context='profile_form_public';
    }
    if (!in_array($variant,array('current','draft'),true)) {
        $variant='current';
    }
    return $context.'_'.$variant;
}

function participant__load_profile_layout($context='profile_form_public',$variant='current') {
    $item_name=participant__profile_layout_item_name($context,$variant);
    $layout=options__load_json_object('profile_form_layout',$item_name,array('blocks'=>array()));
    if (!is_array($layout) || !isset($layout['blocks']) || !is_array($layout['blocks'])) {
        $layout=array('blocks'=>array());
    }
    return $layout;
}

function participant__save_profile_layout($context='profile_form_public',$variant='draft',$layout=array()) {
    if (!is_array($layout)) {
        $layout=array();
    }
    if (!isset($layout['blocks']) || !is_array($layout['blocks'])) {
        $layout['blocks']=array();
    }
    $item_name=participant__profile_layout_item_name($context,$variant);
    return options__save_json_object('profile_form_layout',$item_name,$layout,1,-1);
}

function participant__profile_layout_changed_keys($draft_blocks,$current_blocks) {
    $draft_map=array();
    $current_map=array();
    foreach ($draft_blocks as $position=>$block) {
        if ($block['type']==='field') {
            $block_key='field__'.$block['field'];
            $signature='';
        } elseif ($block['type']==='text' || $block['type']==='section') {
            $block_key=$block['type'].'__'.$block['block_id'];
            $signature=json_encode(array($block['short_name'],$block['text_lang'],$block['scope_contexts'],$block['restrict_to_subpools']));
        } else {
            continue;
        }
        $draft_map[$block_key]=array('position'=>$position,'signature'=>$signature);
    }
    foreach ($current_blocks as $position=>$block) {
        if ($block['type']==='field') {
            $block_key='field__'.$block['field'];
            $signature='';
        } elseif ($block['type']==='text' || $block['type']==='section') {
            $block_key=$block['type'].'__'.$block['block_id'];
            $signature=json_encode(array($block['short_name'],$block['text_lang'],$block['scope_contexts'],$block['restrict_to_subpools']));
        } else {
            continue;
        }
        $current_map[$block_key]=array('position'=>$position,'signature'=>$signature);
    }
    $changed_keys=array();
    foreach ($draft_map as $block_key=>$draft_block) {
        if (!isset($current_map[$block_key])) {
            $changed_keys[$block_key]=true;
            continue;
        }
        if ($draft_block['position']!==$current_map[$block_key]['position']) {
            $changed_keys[$block_key]=true;
            continue;
        }
        if ($draft_block['signature']!==$current_map[$block_key]['signature']) {
            $changed_keys[$block_key]=true;
            continue;
        }
    }
    return $changed_keys;
}

function participant__profile_render_mode($force_mode='') {
    global $settings;
    if ($force_mode==='structured_layout' || $force_mode==='legacy_template') {
        return $force_mode;
    }
    if (!isset($settings['participant_profile_render_mode'])) {
        return 'legacy_template';
    }
    if ($settings['participant_profile_render_mode']!=='structured_layout') {
        return 'legacy_template';
    }
    return 'structured_layout';
}

function participant__profile_template_variant($template='current_template') {
    if ($template==='current_draft' || $template==='draft') {
        return 'draft';
    }
    return 'current';
}

function participant__render_profile_layout($layout,$fields,$field_policies=array(),$errors=array(),$changed_block_keys=array()) {
    global $settings;
    if (!is_array($layout) || !isset($layout['blocks']) || !is_array($layout['blocks'])) {
        return '';
    }
    $out='<div class="orsee-profile-layout-fields">';
    $section_open=false;
    $section_seen=false;
    $content_emitted=false;
    $ui_lang=lang('lang');
    $fallback_lang=(isset($settings['public_standard_language']) ? $settings['public_standard_language'] : '');
    foreach ($layout['blocks'] as $block) {
        if (!is_array($block)) {
            continue;
        }
        $type=isset($block['type']) ? trim((string)$block['type']) : '';
        if ($type==='section') {
            $section_block_key='';
            if (isset($block['block_id']) && trim((string)$block['block_id'])!=='') {
                $section_block_key='section__'.trim((string)$block['block_id']);
            }
            $section_changed=($section_block_key!=='' && isset($changed_block_keys[$section_block_key]));
            if ($section_seen && $section_open) {
                $out.='</div>';
            }
            $section_text='';
            if (isset($block['text_lang']) && is_array($block['text_lang'])) {
                if (isset($block['text_lang'][$ui_lang]) && trim((string)$block['text_lang'][$ui_lang])!=='') {
                    $section_text=trim((string)$block['text_lang'][$ui_lang]);
                } elseif ($fallback_lang!=='' && isset($block['text_lang'][$fallback_lang]) && trim((string)$block['text_lang'][$fallback_lang])!=='') {
                    $section_text=trim((string)$block['text_lang'][$fallback_lang]);
                } else {
                    foreach ($block['text_lang'] as $lang_text) {
                        if (trim((string)$lang_text)!=='') {
                            $section_text=trim((string)$lang_text);
                            break;
                        }
                    }
                }
            }
            if ($section_text==='' && (!isset($block['text_lang']) || !is_array($block['text_lang'])) && isset($block['text']) && trim((string)$block['text'])!=='') {
                $section_text=lang(trim((string)$block['text']));
            }
            if ($section_text!=='') {
                $section_title_class='field mb-1';
                if ($section_changed) {
                    $section_title_class.=' orsee-track-changed-left';
                }
                $out.='<div class="'.$section_title_class.'"><label class="label"><strong>'.$section_text.'</strong></label></div>';
            }
            $section_class='orsee-surface-card p-2 mb-2';
            if ($content_emitted) {
                $section_class.=' mt-2';
            }
            $out.='<div class="'.$section_class.'">';
            $section_open=true;
            $section_seen=true;
            $content_emitted=true;
            continue;
        }
        if ($type==='text') {
            $text_block_key='';
            if (isset($block['block_id']) && trim((string)$block['block_id'])!=='') {
                $text_block_key='text__'.trim((string)$block['block_id']);
            }
            $text_changed=($text_block_key!=='' && isset($changed_block_keys[$text_block_key]));
            $text_value='';
            if (isset($block['text_lang']) && is_array($block['text_lang'])) {
                if (isset($block['text_lang'][$ui_lang]) && trim((string)$block['text_lang'][$ui_lang])!=='') {
                    $text_value=trim((string)$block['text_lang'][$ui_lang]);
                } elseif ($fallback_lang!=='' && isset($block['text_lang'][$fallback_lang]) && trim((string)$block['text_lang'][$fallback_lang])!=='') {
                    $text_value=trim((string)$block['text_lang'][$fallback_lang]);
                } else {
                    foreach ($block['text_lang'] as $lang_text) {
                        if (trim((string)$lang_text)!=='') {
                            $text_value=trim((string)$lang_text);
                            break;
                        }
                    }
                }
            }
            if ($text_value==='' && (!isset($block['text_lang']) || !is_array($block['text_lang'])) && isset($block['text']) && trim((string)$block['text'])!=='') {
                $text_value=lang(trim((string)$block['text']));
            }
            if ($text_value==='') {
                continue;
            }
            $text_field_class='field';
            if ($text_changed) {
                $text_field_class.=' orsee-track-changed-left';
            }
            $out.='<div class="'.$text_field_class.'">';
            $out.='<div class="control">'.$text_value.'</div>';
            $out.='</div>';
            $content_emitted=true;
            continue;
        }
        if ($type!=='field') {
            continue;
        }
        $field_name=isset($block['field']) ? trim((string)$block['field']) : '';
        if ($field_name==='' || !isset($fields[$field_name])) {
            continue;
        }
        $label_key='';
        $text_before='';
        $text_after='';
        $help_key='';
        if (!isset($field_policies[$field_name]) || !is_array($field_policies[$field_name])) {
            continue;
        }
        $policy_field=$field_policies[$field_name];
        $label_key=participant__field_localized_text($policy_field,'label_lang','label_lang',$ui_lang);
        if ($label_key==='' && (!isset($policy_field['label_lang']) || !is_array($policy_field['label_lang']))) {
            if ($field_name==='subscriptions') {
                $label_key=load_language_symbol('i_want_invitations_for',$ui_lang);
            } else {
                $label_key=participant__field_localized_text($policy_field,'name_lang','name_lang',$ui_lang);
            }
        }
        $text_before=participant__field_localized_text($policy_field,'text_before_lang','text_before_lang',$ui_lang);
        $text_after=participant__field_localized_text($policy_field,'text_after_lang','text_after_lang',$ui_lang);
        $help_key=participant__field_localized_text($policy_field,'help_text_lang','help_text_lang',$ui_lang);

        $has_error=(isset($errors[$field_name]) && $errors[$field_name]);
        $field_class='field is-clearfix';
        if ($has_error) {
            $field_class.=' orsee-field-error';
        }
        $field_block_key='field__'.$field_name;
        if (isset($changed_block_keys[$field_block_key])) {
            $field_class.=' orsee-track-changed-left';
        }
        $out.='<div class="'.$field_class.'">';
        if ($label_key!=='') {
            $out.='<label class="label">'.$label_key.'</label>';
        }
        if ($help_key!=='') {
            $out.='<p class="help">'.$help_key.'</p>';
        }
        $out.='<div class="control">';
        if ($text_before!=='') {
            $out.='<span>'.$text_before.' </span>';
        }
        $out.=$fields[$field_name];
        if ($text_after!=='') {
            $out.='<span> '.$text_after.'</span>';
        }
        $out.='</div>';
        $out.='</div>';
        $content_emitted=true;
    }
    if ($section_seen && $section_open) {
        $out.='</div>';
    }
    $out.='</div>';
    return $out;
}

function template_replace_callbackA(array $m) {
    global $tempout;
    if ($m[1]=='!') {
        if ((!(isset($tempout[$m[2]])) || (!$tempout[$m[2]]))) {
            return $m[3];
        } else {
            return '';
        }
    } else {
        if (isset($tempout[$m[2]]) && $tempout[$m[2]]) {
            return $m[3];
        } else {
            return '';
        }
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
    if (isset($tpl_data['item_details'][$template])) {
        $tpl=$tpl_data['item_details'][$template];
    } else {
        $tpl=$tpl_data['item_details']['current_template'];
    }

    // process conditionals
    $pattern="/\{[^#\}]*#(!?)([^#!\}]+)#([^\}]+)\}/i";
    $replacement = "($1\$out['$2'])?\"$3\":''";
    $tpl=preg_replace_callback($pattern,
        'template_replace_callbackA',
        $tpl);


    // fill in the vars
    foreach ($out as $k=>$o) {
        $tpl=str_replace("#".$k."#",($o ?? ''),$tpl);
    }

    // fill in language terms
    $pattern="/lang\[([^\]]+)\]/i";
    $replacement = "\$lang['$1']";
    $tpl=preg_replace_callback($pattern,
        'template_replace_callbackB',
        $tpl);

    return $tpl;
}


function form__replace_funcs_in_field($f) {
    foreach ($f as $o=>$v) {
        if (!is_string($v) || substr($v,0,5)!=='func:') {
            continue;
        }

        $expr=trim(substr($v,5));

        // Resolving current year
        // Backward compatibility for legacy expressions like "func:date('Y')+1"
        if (preg_match('/^\(?\s*(?:\(\s*int\s*\)\s*)?date\s*\(\s*["\']Y["\']\s*\)\s*\)?\s*(?:([+-])\s*(\d+))?\s*$/i',$expr,$matches)) {
            $expr='current_year';
            if (isset($matches[1]) && isset($matches[2]) && $matches[2]!=='') {
                $expr.=$matches[1].$matches[2];
            }
        }
        // replace expressions using "current_year" with the actual year, optionally with an offset
        if (preg_match('/^current_year\s*(?:([+-])\s*(\d+))?$/i',$expr,$matches)) {
            $current_year=(int)date('Y');
            $resolved=$current_year;
            if (isset($matches[1]) && isset($matches[2]) && $matches[2]!=='') {
                $offset=(int)$matches[2];
                if ($matches[1]==='-') {
                    $resolved-=$offset;
                } else {
                    $resolved+=$offset;
                }
            }
            $f[$o]=(string)$resolved;
            continue;
        }

        // Other expressions will be added in the future


        // Any other func: expression is invalid.
        if (isset($f['type']) && $f['type']==='select_numbers') {
            if ($o==='value_begin') {
                $f[$o]='0';
            } elseif ($o==='value_end') {
                $f[$o]='1';
            } elseif ($o==='value_step') {
                $f[$o]='1';
            } else {
                $f[$o]='';
            }
        } else {
            $f[$o]='';
        }
    }
    return $f;
}


// generic fields
function form__render_field($f) {
    $out='';
    switch ($f['type']) {
        case 'textline': $out=form__render_textline($f);
            break;
        case 'email': $out=form__render_email($f);
            break;
        case 'textarea': $out=form__render_textarea($f);
            break;
        case 'radioline': $out=form__render_radioline($f);
            break;
        case 'select_list': $out=form__render_select_list($f);
            break;
        case 'select_numbers': $out=form__render_select_numbers($f);
            break;
        case 'select_lang': $out=form__render_select_lang($f);
            break;
        case 'radioline_lang': $out=form__render_radioline_lang($f);
            break;
        case 'checkboxlist_lang': $out=form__render_checkboxlist_lang($f);
            break;
        case 'boolean':
            $submitvar=$f['mysql_column_name'];
            $value=((isset($f['value']) && $f['value']==='y') ? 'y' : 'n');
            $display_mode=(isset($f['boolean_display']) && $f['boolean_display']==='switchy') ? 'switchy' : 'checkbox';
            if ($display_mode==='switchy') {
                $out='<select data-elem-name="yesnoswitch" name="'.$submitvar.'">'.
                    '<option value="n"'.(($value==='n') ? ' SELECTED' : '').'>'.lang('n').'</option>'.
                    '<option value="y"'.(($value==='y') ? ' SELECTED' : '').'>'.lang('y').'</option>'.
                    '</select>';
            } else {
                $out='<label class="checkbox"><input type="checkbox" name="'.$submitvar.'" value="y"';
                if ($value==='y') {
                    $out.=' checked';
                }
                $out.='></label>';
            }
            break;
        case 'date':
            $submitvar=$f['mysql_column_name'];
            static $date_instance_counter=0;
            $date_instance_counter++;
            $selected_date=0;
            $value=(isset($f['value']) ? trim((string)$f['value']) : '');
            $date_mode=(isset($f['date_mode']) ? trim((string)$f['date_mode']) : 'ymd');
            if (!in_array($date_mode,array('ymd','ym','y'))) {
                $date_mode='ymd';
            }
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/',$value,$dm)) {
                $selected_date=$dm[1].$dm[2].$dm[3].'0000';
            }
            $voluntary=true;
            if (isset($f['compulsory']) && $f['compulsory']==='y') {
                $voluntary=false;
            }
            $out=formhelpers__pick_date($submitvar,$selected_date,0,0,false,$voluntary,$date_mode,(string)$date_instance_counter);
            break;
        case 'phone':
            $submitvar=$f['mysql_column_name'];
            static $phone_instance_counter=0;
            $phone_instance_counter++;
            $phone_input_id=$submitvar.'_phone_'.$phone_instance_counter;
            $phone_e164_id=$submitvar.'_e164_'.$phone_instance_counter;
            $phone_country_id=$submitvar.'_country_'.$phone_instance_counter;
            $phone_error_id=$submitvar.'_phone_error_'.$phone_instance_counter;
            $phone_value=(isset($f['value']) ? trim((string)$f['value']) : '');
            $default_country=(isset($f['phone_default_country']) ? strtolower(trim((string)$f['phone_default_country'])) : '');
            $preferred_countries=(isset($f['phone_preferred_countries']) && is_array($f['phone_preferred_countries']) ? $f['phone_preferred_countries'] : array());
            $phone_display_mode=(isset($f['phone_display_mode']) ? trim((string)$f['phone_display_mode']) : 'national');
            $phone_national_mode='true';
            if ($phone_display_mode==='international') {
                $phone_national_mode='false';
            }
            if (!preg_match('/^[a-z]{2}$/',$default_country)) {
                $default_country='us';
            }
            $out='<input type="hidden" id="'.$phone_e164_id.'" name="'.$submitvar.'_e164" value="'.htmlspecialchars($phone_value,ENT_QUOTES).'">';
            $out.='<input type="hidden" id="'.$phone_country_id.'" name="'.$submitvar.'_country" value="">';
            $out.='<input type="tel" id="'.$phone_input_id.'" class="input is-primary orsee-input orsee-input-text" name="'.$submitvar.'" value="'.htmlspecialchars($phone_value,ENT_QUOTES).'" autocomplete="tel">';
            $invalid_message=lang('error_phone_invalid');
            $invalid_country_code_message=lang('error_phone_invalid_country_code');
            $too_short_message=lang('error_phone_too_short');
            $too_long_message=lang('error_phone_too_long');
            $possible_local_only_message=lang('error_phone_possible_local_only');
            $invalid_length_message=lang('error_phone_invalid_length');
            $out.='<p class="help" id="'.$phone_error_id.'" style="display:none;">'.htmlspecialchars($invalid_message,ENT_QUOTES).'</p>';
            $out.='<script type="text/javascript">
                (function() {
                    var input=document.getElementById("'.$phone_input_id.'");
                    var hidden=document.getElementById("'.$phone_e164_id.'");
                    var hiddenCountry=document.getElementById("'.$phone_country_id.'");
                    var errorNode=document.getElementById("'.$phone_error_id.'");
                    if (!input || !hidden || !hiddenCountry || typeof window.intlTelInput!=="function") return;
                    var defaultInvalidMessage='.json_encode($invalid_message).';
                    var invalidCountryCodeMessage='.json_encode($invalid_country_code_message).';
                    var tooShortMessage='.json_encode($too_short_message).';
                    var tooLongMessage='.json_encode($too_long_message).';
                    var possibleLocalOnlyMessage='.json_encode($possible_local_only_message).';
                    var invalidLengthMessage='.json_encode($invalid_length_message).';
                    var iti=window.intlTelInput(input,{
                        initialCountry: "'.$default_country.'",
                        countryOrder: '.json_encode($preferred_countries).',
                        nationalMode: '.$phone_national_mode.',
                        autoPlaceholder: "aggressive",
                        formatOnDisplay: true,
                        validationNumberTypes: ["MOBILE","FIXED_LINE_OR_MOBILE","FIXED_LINE"],
                        loadUtils: function() {
                            return import("../tagsets/js/intlTelInput/utils.js");
                        }
                    });
                    if (hidden.value) iti.setNumber(hidden.value);
                    var showValidation=false;
                    function validationMessageFromCode() {
                        if (typeof iti.getValidationError!=="function") return defaultInvalidMessage;
                        var code=iti.getValidationError();
                        if (code===1) return invalidCountryCodeMessage;
                        if (code===2) return tooShortMessage;
                        if (code===3) return tooLongMessage;
                        if (code===4) return possibleLocalOnlyMessage;
                        if (code===5) return invalidLengthMessage;
                        return defaultInvalidMessage;
                    }
                    function setValidationState(valid, msg) {
                        if (!errorNode) return;
                        if (!showValidation || valid===null) {
                            errorNode.style.display="none";
                            return;
                        }
                        if (valid) {
                            errorNode.style.display="none";
                        } else {
                            errorNode.textContent=(msg && msg.length) ? msg : defaultInvalidMessage;
                            errorNode.style.display="";
                        }
                    }
                    var syncValue=function() {
                        var countryData=iti.getSelectedCountryData();
                        hiddenCountry.value=(countryData && countryData.dialCode) ? countryData.dialCode : "";
                        var current=input.value ? input.value.trim() : "";
                        if (current==="") {
                            hidden.value="";
                            setValidationState(true);
                            return true;
                        }
                        var valid=iti.isValidNumber();
                        if (valid===null) {
                            hidden.value=current;
                            setValidationState(null, "");
                            return true;
                        }
                        if (valid===true) {
                            var e164=iti.getNumber();
                            hidden.value=e164 ? e164 : "";
                            setValidationState(true);
                            return true;
                        }
                        hidden.value=current;
                        setValidationState(false, validationMessageFromCode());
                        return false;
                    };
                    input.addEventListener("blur", function() {
                        showValidation=true;
                        syncValue();
                    });
                    input.addEventListener("input", function() {
                        if (showValidation) syncValue();
                    });
                    input.addEventListener("change", function() {
                        if (showValidation) syncValue();
                    });
                    input.addEventListener("countrychange", function() {
                        if (showValidation) syncValue();
                    });
                    var form=input.form;
                    if (form) {
                        form.addEventListener("submit", function(ev) {
                            showValidation=true;
                            var current=input.value ? input.value.trim() : "";
                            var valid=syncValue();
                            if (current!=="" && !valid) ev.preventDefault();
                        });
                    }
                })();
            </script>';
            break;
    }
    return $out;
}

function form__render_textline($f) {
    $size=(isset($f['size']) && (int)$f['size']>0) ? (int)$f['size'] : 30;
    $display_value=(isset($f['value']) ? (string)$f['value'] : '');
    $dir_ltr=(isset($f['force_ltr_direction']) && $f['force_ltr_direction']==='y' ? ' dir="ltr"' : '');
    $out='<input class="input is-primary orsee-input" style="width: min(100%, '.$size.'ch);" type="text"'.$dir_ltr.' name="'.$f['mysql_column_name'].'" value="'.htmlspecialchars($display_value,ENT_QUOTES).'" size="'.
        $size.'" maxlength="'.$f['maxlength'].'">';
    return $out;
}

function form__render_email($f) {
    $size=(isset($f['size']) && (int)$f['size']>0) ? (int)$f['size'] : 30;
    $display_value=(isset($f['value']) ? (string)$f['value'] : '');
    $email_input_mode=(isset($f['email_mode']['mode']) ? trim((string)$f['email_mode']['mode']) : 'full_email');
    if (!in_array($email_input_mode,array('full_email','local_part'),true)) {
        $email_input_mode='full_email';
    }
    $email_fixed_domain=(isset($f['email_mode']['domain']) ? trim((string)$f['email_mode']['domain']) : '');
    $email_fixed_domain=ltrim($email_fixed_domain,'@');
    if ($email_input_mode==='local_part' && $email_fixed_domain!=='') {
        $raw_email=trim((string)$display_value);
        $malformed_existing_email=false;
        if ($raw_email!=='') {
            $at_position=strpos($raw_email,'@');
            if ($at_position===false) {
                $malformed_existing_email=true;
            } else {
                $email_local_part=(string)substr($raw_email,0,$at_position);
                $email_domain_part=(string)substr($raw_email,$at_position+1);
                if ($email_local_part==='' || strtolower($email_domain_part)!==strtolower($email_fixed_domain)) {
                    $malformed_existing_email=true;
                } else {
                    $display_value=$email_local_part;
                }
            }
        } else {
            $display_value='';
        }
        if ($malformed_existing_email) {
            $display_value='';
        }
        $out='';
        if ($malformed_existing_email) {
            $warning_text=lang('email_address_enforced_domain_mismatch');
            $out.='<div class="orsee-option-row-comment" style="color: var(--color-important-note-text); margin: 0 0 0.22rem 0;">';
            $out.='<div>'.htmlspecialchars($raw_email,ENT_QUOTES).'</div>';
            $out.='<div>'.htmlspecialchars($warning_text,ENT_QUOTES).' (@'.htmlspecialchars($email_fixed_domain,ENT_QUOTES).')</div>';
            $out.='</div>';
        }
        $out.='<span class="is-inline-flex is-align-items-center" style="gap: 0;">';
        $out.='<input class="input is-primary orsee-input" style="width: min(100%, '.$size.'ch);" type="text" dir="ltr" name="'.$f['mysql_column_name'].'" value="'.htmlspecialchars($display_value,ENT_QUOTES).'" size="'.
            $size.'" maxlength="'.$f['maxlength'].'">';
        $out.='<strong style="white-space: nowrap;">@'.htmlspecialchars($email_fixed_domain,ENT_QUOTES).'</strong>';
        $out.='</span>';
        return $out;
    }
    $out='<input class="input is-primary orsee-input" style="width: min(100%, '.$size.'ch);" type="email" dir="ltr" name="'.$f['mysql_column_name'].'" value="'.htmlspecialchars($display_value,ENT_QUOTES).'" size="'.
        $size.'" maxlength="'.$f['maxlength'].'">';
    return $out;
}

function form__render_textarea($f) {
    $out='<textarea class="textarea is-primary orsee-textarea" name="'.$f['mysql_column_name'].'" cols="'.$f['cols'].'" rows="'.
            $f['rows'].'" wrap="'.$f['wrap'].'">'.htmlspecialchars((string)$f['value'],ENT_QUOTES).'</textarea>';
    return $out;
}

function form__render_radioline($f) {
    $items=array();
    foreach ($f['option_values'] as $val=>$option_symbol) {
        $items[$val]=$option_symbol;
    }
    $out='';
    foreach ($items as $val=>$text) {
        $out.='<INPUT name="'.$f['mysql_column_name'].'" type="radio" value="'.$val.'"';
        if ($f['value']==$val) {
            $out.=" CHECKED";
        }
        $out.='>&nbsp;';
        $out.=lang($text);
        $out.='&nbsp;&nbsp;&nbsp;';
    }
    return $out;
}

function form__render_select_list($f,$formfieldvarname='',$compact=false) {
    if (!$formfieldvarname) {
        $formfieldvarname=$f['mysql_column_name'];
    }
    if ($f['include_none_option']=='y') {
        $incnone=true;
    } else {
        $incnone=false;
    }
    $items=array();
    foreach ($f['option_values'] as $val=>$option_symbol) {
        $items[$val]=$option_symbol;
    }
    $select_class='select is-primary';
    if ($compact) {
        $select_class.=' select-compact';
    }
    $out='<span class="'.$select_class.'">'.helpers__select_text($items,$formfieldvarname,$f['value'],$incnone).'</span>';
    return $out;
}

function form__render_select_lang($f) {
    if ($f['include_none_option']=='y') {
        $incnone=true;
    } else {
        $incnone=false;
    }
    $out='<span class="select is-primary">'.
        language__selectfield_item($f['mysql_column_name'],$f['mysql_column_name'],$f['mysql_column_name'],$f['value'],$incnone,$f['order_select_lang_values'])
        .'</span>';
    return $out;
}

function form__render_radioline_lang($f) {
    if ($f['include_none_option']=='y') {
        $incnone=true;
    } else {
        $incnone=false;
    }
    $out=language__radioline_item($f['mysql_column_name'],$f['mysql_column_name'],$f['mysql_column_name'],$f['value'],$incnone,$f['order_radio_lang_values']);
    return $out;
}

function form__render_checkboxlist_lang($f) {
    $selected_values=db_string_to_id_array((string)$f['value']);
    $order='alphabetically';
    if (isset($f['order_radio_lang_values']) && $f['order_radio_lang_values']==='fixed_order') {
        $order='fixed_order';
    }
    $items=lang__load_lang_cat($f['mysql_column_name'],lang('lang'),$order);
    $out='';
    foreach ($items as $val=>$text) {
        $out.='<label class="checkbox"><input name="'.$f['mysql_column_name'].'['.$val.']" type="checkbox" value="'.$val.'"';
        if (in_array((string)$val,$selected_values,true) || in_array((int)$val,$selected_values,true)) {
            $out.=' checked';
        }
        $out.='> '.$text.'</label>&nbsp;&nbsp;&nbsp;';
    }
    return $out;
}

function form__render_select_numbers($f) {
    if ($f['include_none_option']=='y') {
        $incnone=true;
    } else {
        $incnone=false;
    }
    if ($f['values_reverse']=='y') {
        $reverse=true;
    } else {
        $reverse=false;
    }
    $out='<span class="select is-primary">'.
        participant__select_numbers($f['mysql_column_name'],$f['mysql_column_name'],$f['value'],$f['value_begin'],$f['value_end'],0,$f['value_step'],$reverse,$incnone)
        .'</span>';
    return $out;
}

// special fields

function participant__subscriptions_form_field($subpool_id,$varname,$value) {
    global $lang;
    $checked=db_string_to_id_array($value);
    $exptypes=load_external_experiment_types();
    $subpool_exptypes=participant__subpool_subscription_exptypes($subpool_id,true);
    $out='';
    foreach ($subpool_exptypes as $exptype_id) {
        $out.='<INPUT type="checkbox" name="'.$varname.'['.$exptype_id.']"
                                        value="'.$exptype_id.'"';
        if (in_array($exptype_id,$checked)) {
            $out.=" CHECKED";
        }
        $out.='>&nbsp;'.$exptypes[$exptype_id][lang('lang')];
        $out.='<BR>
                     ';
    }
    return $out;
}

function participant__subpool_subscription_exptypes($subpool_id,$enabled_only=true) {
    global $settings;
    if (!$subpool_id) {
        $subpool_id=$settings['subpool_default_registration_id'];
    }
    $subpool=subpools__get_subpool($subpool_id);
    if (!isset($subpool['subpool_id']) || !$subpool['subpool_id']) {
        return array();
    }
    $subpool_exptypes=db_string_to_id_array($subpool['experiment_types']);
    if (!$enabled_only) {
        return $subpool_exptypes;
    }
    $enabled_exptypes=load_external_experiment_types();
    $filtered=array();
    foreach ($subpool_exptypes as $exptype_id) {
        if (isset($enabled_exptypes[$exptype_id])) {
            $filtered[]=$exptype_id;
        }
    }
    return $filtered;
}

function participant__rules_signed_form_field($current_rules_signed="") {
    global $lang;
    $out='<input type=radio name=rules_signed value="y"';
    if ($current_rules_signed=="y") {
        $out.=" CHECKED";
    }
    $out.='>&nbsp;'.lang('yes').'&nbsp;&nbsp;&nbsp;
              <input type=radio name=rules_signed value="n"';
    if ($current_rules_signed!="y") {
        $out.=" CHECKED";
    }
    $out.='>&nbsp;'.lang('no');
    return $out;
}

function participant__remarks_form_field($current_remarks="") {
    global $lang;
    $out='<textarea class="textarea is-primary orsee-textarea" name="remarks" rows="3" cols="40" wrap="virtual">';
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
    $result=or_query($query);
    $sessions=array();
    while ($line=pdo_fetch_assoc($result)) {
        $sessions[$line['session_id']]=$line;
    }
    if ($participant_id) {
        $pars[':participant_id']=$participant_id;
        $query="SELECT *
                FROM ".table('participate_at')."
                WHERE participant_id= :participant_id";
        $result=or_query($query,$pars);
        $del_exp=array();
        $ass_exp=array();
        while ($line=pdo_fetch_assoc($result)) {
            if ($line['session_id']>0) {
                $del_exp[$line['experiment_id']]=$line['experiment_id'];
            } else {
                $ass_exp[$line['experiment_id']]=$line['experiment_id'];
            }
        }
        foreach ($sessions as $sid=>$session) {
            if (isset($del_exp[$session['experiment_id']])) {
                unset($sessions[$sid]);
            } elseif (isset($ass_exp[$session['experiment_id']])) {
                $sessions[$sid]['p_is_assigned']=true;
            } else {
                $sessions[$sid]['p_is_assigned']=false;
            }
        }
    }
    $out=select__sessions($session_id,"session_id",$sessions,true,true);
    return $out;
}

function participant__select_numbers($ptablevarname,$formfieldvarname,$prevalue,$begin,$end,$fillzeros=2,$steps=1,$reverse=false,$incnone=false,$existing=false,$where='',$show_count=false,$compact=false) {
    $out='';
    if ($compact) {
        $out.='<span class="select is-primary select-compact">';
    } else {
        $out.='<span class="select is-primary">';
    }
    $out.='<select name="'.$formfieldvarname.'">';
    if ($incnone) {
        $out.='<option value="">-</option>';
    }

    if (!$existing) {
        if ($begin<$end) {
            $lb=$begin;
            $ub=$end;
        } else {
            $lb=$end;
            $ub=$begin;
        }
        if ($reverse) {
            $i=$ub;
        } else {
            $i=$lb;
        }
        if (!is_numeric($steps) || (float)$steps<=0) {
            $steps=1;
        }
        $steps=(float)$steps;
        while (($reverse==false && $i<=$ub) || ($reverse==true && $i>=$lb)) {
            $ival=(float)$i;
            $out.='<option value="'.$ival.'"';
            if ((string)$ival === (string)$prevalue) {
                $out.=' SELECTED';
            } $out.='>';
            $out.=helpers__pad_number($i,$fillzeros);
            $out.='</option>';
            if ($reverse) {
                $i=$ival-$steps;
            } else {
                $i=$ival+$steps;
            }
            $i=round((float)$i,6);
        }
    } else {
        $query="SELECT count(*) as tf_count, ".$ptablevarname." as tf_value
                FROM ".table('participants')."
                WHERE ".table('participants').".participant_id IS NOT NULL ";
        if ($where) {
            $query.=" AND ".$where." ";
        }
        $query.=" GROUP BY ".$ptablevarname."
                  ORDER BY ".$ptablevarname;
        if ($reverse) {
            $query.=" DESC ";
        }
        $result=or_query($query);
        $listitems=array();
        while ($line = pdo_fetch_assoc($result)) {
            if (!isset($listitems[$line['tf_value']])) {
                $listitems[$line['tf_value']]=$line;
            } else {
                $listitems[$line['tf_value']]['tf_count']=$listitems[$line['tf_value']]['tf_count']+$line['tf_count'];
            }
        }
        foreach ($listitems as $line) {
            $out.='<option value="'.$line['tf_value'].'"';
            if ((string)$line['tf_value'] === (string)$prevalue) {
                $out.=' SELECTED';
            } $out.='>';
            if ($line['tf_value']!='') {
                $out.=helpers__pad_number($line['tf_value'],$fillzeros);
            } else {
                $out.='-';
            }
            if ($show_count) {
                $out.=' ('.$line['tf_count'].')';
            }
            $out.='</option>';
        }


        while ($line = pdo_fetch_assoc($result)) {
            $out.='<option value="'.$line['tf_value'].'"';
            if ((string)$line['tf_value'] === (string)$prevalue) {
                $out.=' SELECTED';
            } $out.='>';
            $out.=helpers__pad_number($line['tf_value'],$fillzeros);
            if ($show_count) {
                $out.=' ('.$line['tf_count'].')';
            }
            $out.='</option>';
        }
    }
    $out.='</select></span>';
    return $out;
}

function participant__select_existing($ptablevarname,$formfieldvarname,$prevalue,$where='',$show_count=false,$compact=false) {
    $out='';
    if ($compact) {
        $out.='<span class="select is-primary select-compact">';
    } else {
        $out.='<span class="select is-primary">';
    }
    $out.='<select name="'.$formfieldvarname.'">';
    $query="SELECT count(*) as tf_count, ".$ptablevarname." as tf_value
            FROM ".table('participants')."
            WHERE ".table('participants').".participant_id IS NOT NULL ";
    if ($where) {
        $query.=" AND ".$where." ";
    }
    $query.=" GROUP BY ".$ptablevarname."
              ORDER BY ".$ptablevarname;
    $result=or_query($query);
    while ($line = pdo_fetch_assoc($result)) {
        $out.='<option value="'.$line['tf_value'].'"';
        if ($line['tf_value'] == $prevalue) {
            $out.=' SELECTED';
        } $out.='>';
        $out.=$line['tf_value'];
        if ($show_count) {
            $out.=' ('.$line['tf_count'].')';
        }
        $out.='</option>';
    }
    $out.='</select></span>';
    return $out;
}


// the outer participant form
function participant__show_form($edit,$button_title="",$errors=array(),$admin=false,$extra="") {
    global $lang, $settings, $color;
    $out=array();
    $tout=array();

    echo '<FORM action="'.thisdoc().'" method="POST">';
    echo csrf__field();
    echo '<table cellspacing="0" cellpadding="10em" border="0">
            <TR><TD>';
    if ($admin) {
        participant__show_inner_form($edit,$errors,'profile_form_public_admin_edit');
    } else {
        participant__show_inner_form($edit,$errors,'profile_form_public_create');
    }
    echo '</TD></TR>
            <TR><TD>';
    echo $extra;
    echo '</TD></TR>';

    if (!$button_title) {
        $button_title=lang('change');
    }
    echo '<tr><td colspan="2" align="center">
            <INPUT class="button" name="add" type="submit" value="'.$button_title.'">
            </td></tr>';
    echo '</table> </form>';
}

// the inner participant form
function participant__show_inner_form($edit,$errors=array(),$scope_context='',$template='current_template',$force_mode='',$show_changes=false) {
    global $lang, $settings, $color;
    $out=array();
    $tout=array();
    $field_layout_props=array();
    $scope_context=trim((string)$scope_context);
    $is_admin_ui=($scope_context==='profile_form_public_admin_edit');

    if (!isset($edit['participant_id'])) {
        $edit['participant_id']='';
    }
    if (!isset($edit['subpool_id'])) {
        $edit['subpool_id']=1;
    }
    $subpool=orsee_db_load_array("subpools",$edit['subpool_id'],"subpool_id");
    if (!$subpool['subpool_id']) {
        $subpool=orsee_db_load_array("subpools",1,"subpool_id");
    }
    $edit['subpool_id']=$subpool['subpool_id'];

    $pools=subpools__get_subpools();
    foreach ($pools as $p=>$pool) {
        $out['is_subjectpool_'.$p]=false;
    }
    $out['is_subjectpool_'.$subpool['subpool_id']]=true;
    if ($is_admin_ui) {
        $out['is_admin']=true;
        $out['is_not_admin']=false;
    } else {
        $out['is_admin']=false;
        $out['is_not_admin']=true;
    }

    if (!$is_admin_ui && isset($edit['participant_id_crypt'])) {
        echo '<INPUT type=hidden name="p" value="'.$edit['participant_id_crypt'].'">
                <INPUT type=hidden name="participant_id_crypt" value="'.$edit['participant_id_crypt'].'">';
    }

    if ($is_admin_ui) {
        $nonunique=participantform__get_nonunique($edit,$edit['participant_id'],$scope_context);
    }

    // user-defined participant form fields
    $layout_state=(participant__profile_render_mode($force_mode)==='structured_layout' ? participant__profile_template_variant($template) : 'current');
    $formfields=participantform__load($layout_state);
    $formfields=participant__profile_fields_resolve_variant($formfields,$subpool['subpool_id'],$scope_context);
    $changed_block_keys=array();
    foreach ($formfields as $f) {
        $show_field=participant__profile_field_is_applicable($f,$subpool['subpool_id'],$scope_context);
        if ($show_field) {
            $f=form__replace_funcs_in_field($f);
            if (isset($edit[$f['mysql_column_name']])) {
                $f['value']=$edit[$f['mysql_column_name']];
            } else {
                if ($f['type']==='date') {
                    $default_mode=(isset($f['date_default_mode']) ? $f['date_default_mode'] : 'none');
                    if ($default_mode==='today') {
                        $f['value']=date('Y-m-d');
                    } elseif ($default_mode==='fixed') {
                        $default_date=(isset($f['date_default_value']) ? trim((string)$f['date_default_value']) : '');
                        if ($default_date==='' && isset($f['default_value'])) {
                            $default_date=trim((string)$f['default_value']);
                        }
                        if (preg_match('/^\d{4}-\d{2}-\d{2}$/',$default_date)) {
                            $f['value']=$default_date;
                        } else {
                            $f['value']='';
                        }
                    } else {
                        $f['value']='';
                    }
                } else {
                    $f['value']=$f['default_value'];
                }
            }
            $field=form__render_field($f);
            if ($is_admin_ui) {
                if (isset($nonunique[$f['mysql_column_name']])) {
                    $note=lang('not_unique');
                    $link='participants_show.php?form%5Bquery%5D%5B0%5D%5Bpformtextfields_freetextsearch%5D%5Bsearch_string%5D='.urlencode($f['value']).'&form%5Bquery%5D%5B0%5D%5Bpformtextfields_freetextsearch%5D%5Bnot%5D=&form%5Bquery%5D%5B0%5D%5Bpformtextfields_freetextsearch%5D%5Bsearch_field%5D='.urlencode($f['mysql_column_name']).'&search_submit=';
                    $field.=' <A HREF="'.$link.'"><FONT style="color: var(--color-important-note-text);">'.str_replace(" ","&nbsp;",$note).'</FONT></A>';
                }
            }
            $out[$f['mysql_column_name']]=$field;
            $field_layout_props[$f['mysql_column_name']]=$f;
            if ($show_changes && isset($f['has_changes']) && $f['has_changes']) {
                $changed_block_keys['field__'.$f['mysql_column_name']]=true;
            }
            if (in_array($f['mysql_column_name'],$errors)) {
                $out['error_'.$f['mysql_column_name']]=' style="background: var(--color-missing-field);"';
            } else {
                $out['error_'.$f['mysql_column_name']]='';
            }
        }
    }

    // language field
    if (!isset($edit['language'])) {
        $edit['language']=lang('lang');
    }
    $part_langs=lang__get_part_langs();
    if (count($part_langs)>1) {
        $out['multiple_participant_languages_exist']=true;
        $tout['multiple_participant_languages_exist']=true;
    } else {
        $out['multiple_participant_languages_exist']=false;
        $tout['multiple_participant_languages_exist']=false;
    }
    $out['language']=lang__select_lang('language',$edit['language'],"part");
    if (in_array('language',$errors)) {
        $out['error_language']=' style="background: var(--color-missing-field);"';
    } else {
        $out['error_language']='';
    }

    // subscriptions field
    if (!isset($edit['subscriptions'])) {
        $edit['subscriptions']='';
    }
    $subscriptions_exptypes=participant__subpool_subscription_exptypes($subpool['subpool_id'],true);
    $hide_subscriptions=(
        isset($settings['hide_subscriptions_if_single_exptype']) &&
        $settings['hide_subscriptions_if_single_exptype']==='y' &&
        count($subscriptions_exptypes)===1
    );
    if (!$hide_subscriptions) {
        $out['subscriptions']=participant__subscriptions_form_field($subpool['subpool_id'],'subscriptions',$edit['subscriptions']);
        if (in_array('subscriptions',$errors)) {
            $out['error_subscriptions']=' style="background: var(--color-missing-field);"';
        } else {
            $out['error_subscriptions']='';
        }
    } else {
        $out['subscriptions_hidden_by_setting']=true;
    }

    if (participant__profile_render_mode($force_mode)==='structured_layout') {
        $variant=participant__profile_template_variant($template);
        $layout=participant__load_profile_layout('profile_form_public',$variant);
        if ($show_changes) {
            $other_variant=($variant==='current' ? 'draft' : 'current');
            $other_layout=participant__load_profile_layout('profile_form_public',$other_variant);
            $layout_changed_block_keys=participant__profile_layout_changed_keys($layout['blocks'],$other_layout['blocks']);
            foreach ($layout_changed_block_keys as $block_key=>$is_changed) {
                $changed_block_keys[$block_key]=$is_changed;
            }
        }
        // Keep only blocks that are applicable for this runtime context.
        $filtered_blocks=array();
        foreach ($layout['blocks'] as $block) {
            if (!is_array($block) || !isset($block['type'])) {
                continue;
            }
            $block_type=trim((string)$block['type']);
            if ($block_type==='text' || $block_type==='section') {
                if (!participant__profile_block_is_applicable($block,$subpool['subpool_id'],$scope_context)) {
                    continue;
                }
            }
            $filtered_blocks[]=$block;
        }
        $layout['blocks']=$filtered_blocks;
        $errors_map=array();
        foreach ($errors as $err_name) {
            $errors_map[$err_name]=true;
        }
        if (isset($out['subscriptions_hidden_by_setting']) && $out['subscriptions_hidden_by_setting']) {
            unset($out['subscriptions']);
            unset($out['error_subscriptions']);
        }
        $formoutput=participant__render_profile_layout($layout,$out,$field_layout_props,$errors_map,$changed_block_keys);
    } else {
        $formoutput=load_form_template('profile_form_public',$out,$template);
    }
    echo $formoutput;
}

// the inner admin participant form
function participant__get_inner_admin_form($edit,$errors,$template='current_template',$force_mode='',$show_changes=false) {
    global $lang, $settings, $color;
    $field_layout_props=array();

    if (!isset($edit['participant_id'])) {
        $edit['participant_id']='';
    }
    if (!isset($edit['subpool_id'])) {
        $edit['subpool_id']=1;
    }
    $subpool=orsee_db_load_array("subpools",$edit['subpool_id'],"subpool_id");
    if (!$subpool['subpool_id']) {
        $subpool=orsee_db_load_array("subpools",1,"subpool_id");
    }
    $edit['subpool_id']=$subpool['subpool_id'];


    // first show user-defined admin participant form fields
    $layout_state=(participant__profile_render_mode($force_mode)==='structured_layout' ? participant__profile_template_variant($template) : 'current');
    $formfields=participantform__load($layout_state);
    $tout=array();
    $formfields=participant__profile_fields_resolve_variant($formfields,$subpool['subpool_id'],'profile_form_admin_part');
    $changed_block_keys=array();
    foreach ($formfields as $f) {
        if (participant__profile_field_is_applicable($f,$subpool['subpool_id'],'profile_form_admin_part')) {
            $f=form__replace_funcs_in_field($f);
            if (isset($edit[$f['mysql_column_name']])) {
                $f['value']=$edit[$f['mysql_column_name']];
            } else {
                if ($f['type']==='date') {
                    $default_mode=(isset($f['date_default_mode']) ? $f['date_default_mode'] : 'none');
                    if ($default_mode==='today') {
                        $f['value']=date('Y-m-d');
                    } elseif ($default_mode==='fixed') {
                        $default_date=(isset($f['date_default_value']) ? trim((string)$f['date_default_value']) : '');
                        if ($default_date==='' && isset($f['default_value'])) {
                            $default_date=trim((string)$f['default_value']);
                        }
                        if (preg_match('/^\d{4}-\d{2}-\d{2}$/',$default_date)) {
                            $f['value']=$default_date;
                        } else {
                            $f['value']='';
                        }
                    } else {
                        $f['value']='';
                    }
                } else {
                    $f['value']=$f['default_value'];
                }
            }
            $field=form__render_field($f);
            $tout[$f['mysql_column_name']]=$field;
            $field_layout_props[$f['mysql_column_name']]=$f;
            if ($show_changes && isset($f['has_changes']) && $f['has_changes']) {
                $changed_block_keys['field__'.$f['mysql_column_name']]=true;
            }
            if (in_array($f['mysql_column_name'],$errors)) {
                $tout['error_'.$f['mysql_column_name']]=' style="background: var(--color-missing-field);"';
            } else {
                $tout['error_'.$f['mysql_column_name']]='';
            }
        }
    }
    if (count($tout)===0) {
        return '';
    }
    if (participant__profile_render_mode($force_mode)==='structured_layout') {
        $variant=participant__profile_template_variant($template);
        $layout=participant__load_profile_layout('profile_form_admin_part',$variant);
        if ($show_changes) {
            $other_variant=($variant==='current' ? 'draft' : 'current');
            $other_layout=participant__load_profile_layout('profile_form_admin_part',$other_variant);
            $layout_changed_block_keys=participant__profile_layout_changed_keys($layout['blocks'],$other_layout['blocks']);
            foreach ($layout_changed_block_keys as $block_key=>$is_changed) {
                $changed_block_keys[$block_key]=$is_changed;
            }
        }
        // Keep only blocks that are applicable for this runtime context.
        $filtered_blocks=array();
        foreach ($layout['blocks'] as $block) {
            if (!is_array($block) || !isset($block['type'])) {
                continue;
            }
            $block_type=trim((string)$block['type']);
            if ($block_type==='text' || $block_type==='section') {
                if (!participant__profile_block_is_applicable($block,$subpool['subpool_id'],'profile_form_admin_part')) {
                    continue;
                }
            }
            $filtered_blocks[]=$block;
        }
        $layout['blocks']=$filtered_blocks;
        $errors_map=array();
        foreach ($errors as $err_name) {
            $errors_map[$err_name]=true;
        }
        $adminformoutput=participant__render_profile_layout($layout,$tout,$field_layout_props,$errors_map,$changed_block_keys);
    } else {
        $adminformoutput=load_form_template('profile_form_admin_part',$tout,$template);
    }
    return $adminformoutput;
}


// the participant form for admins
function participant__show_admin_form($edit,$button_title="",$errors=array(),$extra="") {
    global $lang, $settings, $color;
    $out=array();

    if (!isset($edit['participant_id'])) {
        $edit['participant_id']='';
    }
    if (!isset($edit['subpool_id'])) {
        $edit['subpool_id']=1;
    }
    $subpool=orsee_db_load_array("subpools",$edit['subpool_id'],"subpool_id");
    if (!$subpool['subpool_id']) {
        $subpool=orsee_db_load_array("subpools",1,"subpool_id");
    }
    $edit['subpool_id']=$subpool['subpool_id'];

    $pools=subpools__get_subpools();
    foreach ($pools as $p=>$pool) {
        $out['is_subjectpool_'.$p]=false;
    }
    $out['is_subjectpool_'.$subpool['subpool_id']]=true;

    echo '<FORM action="'.thisdoc().'" method="POST">';
    echo csrf__field();
    echo '<INPUT type="hidden" name="participant_id" value="'.$edit['participant_id'].'">';
    global $hide_header;
    if (isset($hide_header) && $hide_header) {
        echo '<INPUT type="hidden" name="hide_header" value="true">';
    }

    // initialize
    if (!isset($edit['participant_id'])) {
        $edit['participant_id']='???';
    }
    if (!isset($edit['participant_id_crypt'])) {
        $edit['participant_id_crypt']='???';
    }
    if (isset($edit['creation_time'])) {
        $tout['creation_time']=ortime__format($edit['creation_time'],'',lang('lang'));
    } else {
        $tout['creation_time']='';
    }
    if (!isset($edit['rules_signed'])) {
        $edit['rules_signed']='';
    }
    if (!isset($edit['session_id'])) {
        $edit['session_id']='';
    }
    if (!isset($edit['remarks'])) {
        $edit['remarks']='';
    }

    echo '<div class="orsee-form-shell orsee-form-shell--participant-edit">';
    echo '<div class="orsee-form-row-grid orsee-form-row-grid--2" style="align-items: start;">';

    echo '<div class="orsee-form-row-col">';
    echo '<div style="display: flex; justify-content: center;">';
    // template from DB (left side)
    participant__show_inner_form($edit,$errors,'profile_form_public_admin_edit');
    echo '</div>';
    if ($edit['participant_id']) {
        echo '<div class="orsee-options-actions-center" style="margin-top: 0.72rem;">
                <INPUT class="button orsee-btn" name="save_participant_part" type="submit" value="'.lang('save_participant_data').'">
                </div>';
    }
    echo '</div>';

    echo '<div class="orsee-form-row-col">';
    echo '<div class="orsee-surface-card p-3">';

    $adminformoutput=participant__get_inner_admin_form($edit,$errors);
    if ($adminformoutput) {
        echo '<div class="orsee-surface-card p-2 mb-2">';
        echo $adminformoutput;
        echo '</div>';
    }

    echo '<div class="field">';
    echo '<label class="label">'.lang('subpool').'</label>';
    echo '<div class="control">'.subpools__select_field("subpool_id",$edit['subpool_id']).'</div>';
    echo '</div>';

    echo '<div class="field">';
    echo '<label class="label">'.lang('id').'</label>';
    echo '<div class="control">'.$edit['participant_id'].' ('.$edit['participant_id_crypt'].')</div>';
    echo '</div>';

    echo '<div class="field">';
    echo '<label class="label">'.lang('creation_time').'</label>';
    echo '<div class="control">';
    if (isset($edit['creation_time'])) {
        echo ortime__format($edit['creation_time'],'',lang('lang'));
    } else {
        echo '???';
    }
    echo '</div>';
    echo '</div>';

    if ($settings['enable_rules_signed_tracking']=='y') {
        echo '<div class="field">';
        echo '<label class="label">'.lang('rules_signed').'</label>';
        echo '<div class="control">'.participant__rules_signed_form_field($edit['rules_signed']).'</div>';
        echo '</div>';
    }

    echo '<div class="field">';
    echo '<label class="label">'.lang('remarks').'</label>';
    echo '<div class="control">'.participant__remarks_form_field($edit['remarks']).'</div>';
    echo '</div>';

    echo '<div class="field">';
    echo '<label class="label">'.lang('register_sub_for_session').'</label>';
    echo '<div class="control">'.participant__add_to_session_checkbox().' '.participant__add_to_session_select($edit['session_id'],$edit['participant_id']).'</div>';
    echo '</div>';

    echo '<div class="field orsee-status-field">';
    echo '<label class="label">'.lang('participant_status').'</label>';
    echo '<div class="control">';
    if (!isset($edit['status_id'])) {
        $edit['status_id']="";
    }
    if (check_allow('participants_change_status')) {
        if ($edit['status_id']=='0') {
            $hide=array();
        } else {
            $hide=array('0');
        }
        echo '<INPUT type="hidden" name="old_status_id" value="'.$edit['status_id'].'">'.
                participant_status__select_field('status_id',$edit['status_id'],$hide);
    } elseif (!$edit['participant_id']) {
        $default_status=participant_status__get("is_default_active");
        $statuses=participant_status__get_statuses();
        echo '<INPUT type="hidden" name="status_id" value="'.$default_status.'">'.
                    $statuses[$default_status]['name'];
    } else {
        echo participant_status__get_name($edit['status_id']);
    }
    echo '</div>';
    echo '</div>';

    if ($edit['participant_id']) {
        echo '<div class="orsee-options-actions-center" style="margin-top: 0.72rem;">
                <INPUT class="button orsee-btn" name="save_admin_part" type="submit" value="'.lang('save_participant_admin_data').'">
                </div>';
    }
    echo '</div>'; // right panel card
    echo '</div>'; // right column
    echo '</div>'; // grid

    if (!$button_title) {
        $button_title=lang('change');
    }
    if (!$edit['participant_id']) {
        echo '<div class="orsee-options-actions-center" style="margin-top: 0.72rem;">
                <INPUT class="button orsee-btn" name="add" type="submit" value="'.$button_title.'">
                </div>';
    }
    echo '</div>'; // form shell
    echo '</form>';
}


function participant__check_password($password,$password2) {
    global $settings;
    $continue=true;
    if (!$password || !$password2) {
        message(lang('you_have_to_give_a_password'),'error');
        $continue=false;
    }
    if ($password!=$password2) {
        message(lang('error_password_repetition_does_not_match'),'error');
        $continue=false;
    }
    if (!preg_match('/'.$settings['participant_password_regexp'].'/',$password)) {
        message(lang('error_password_does_not_meet_requirements'),'error');
        $continue=false;
    }
    return $continue;
}

function participant__public_confirm_modal() {
    return '';
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
            message(lang('error_password_or_username'),'error');
        }
    }

    if ($continue) {
        $participant=participant__check_has_lockout($participant);
        if ($participant['locked']) {
            $continue=false;
            log__participant('login_participant_locked_out',$participant['participant_id'],'username:'.$email);
            $locked=participant__track_unsuccessful_login($participant);
            message(lang('error_password_or_username'),'error');
        }
    }

    if ($continue) {
        $check_pw=crypt_verify($password,$participant['password_crypted']);
        if (!$check_pw) {
            $continue=false;
            log__participant('login_participant_wrong_password',$participant['participant_id'],'username:'.$email);
            $locked=participant__track_unsuccessful_login($participant);
            message(lang('error_password_or_username'),'error');
        }
    }

    if ($continue) {
        $statuses=participant_status__get_statuses();
        $statuses_profile=participant_status__get("access_to_profile");
        if (!in_array($participant['status_id'],$statuses_profile)) {
            log__participant('login_participant_not_active_anymore',$participant['participant_id'],'username:'.$email);
            message($statuses[$participant['status_id']]['error']." ".
            lang('if_you_have_questions_write_to')." ".support_mail_link(),'error');
            $continue=false;
        }
    }

    if ($continue) {
        $_SESSION['pauthdata']['user_logged_in']=true;
        $_SESSION['pauthdata']['participant_id']=$participant['participant_id'];
        $done=participant__track_successful_login($participant);
        return true;
    } else {
        if (isset($locked) && $locked) {
            message(lang('error_locked_out'),'error');
        }
        return false;
    }
}

function participant__check_has_lockout($participant) {
    global $settings;
    if (isset($settings['participant_lockout_minutes']) && $settings['participant_lockout_minutes']>0) {
        $lockout_minutes=$settings['participant_lockout_minutes'];
    } else {
        $lockout_minutes=20;
    }
    if ($participant['locked'] && ($participant['last_login_attempt'] + ($lockout_minutes*60)) < time()) {
        // unlock
        $participant['failed_login_attempts']=0;
        $participant['locked']=0;
    }
    return $participant;
}

function participant__track_unsuccessful_login($participant) {
    global $settings;
    if (isset($settings['participant_failed_logins_before_lockout']) && $settings['participant_failed_logins_before_lockout']>0) {
        $limit=$settings['participant_failed_logins_before_lockout'];
    } else {
        $limit=3;
    }
    if (isset($settings['participant_lockout_minutes']) && $settings['participant_lockout_minutes']>0) {
        $lockout_minutes=$settings['participant_lockout_minutes'];
    } else {
        $lockout_minutes=20;
    }

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
    $ids=db_string_to_id_array($idlist);
    $namearr=array();
    foreach ($ids as $id) {
        if (isset($names[$id])) {
            $namearr[]=$names[$id];
        } else {
            $namearr[]=$id;
        }
    }
    return implode(", ",$namearr);
}

function participant__update_last_enrolment_time($participant_id,$time=0) {
    if (!$time) {
        $time=time();
    }
    $pars=array(':time1'=>$time,':time2'=>$time);
    if (is_array($participant_id)) {
        $i=0;
        $parnames=array();
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

function participant_status__select_field($postvarname,$selected,$hidden=array(),$class='',$select_wrapper_class='select is-primary',$compact=false) {
    $statuses=participant_status__get_statuses();
    if ($compact && stripos($select_wrapper_class,'select-compact')===false) {
        $select_wrapper_class=trim($select_wrapper_class.' select-compact');
    }
    $out='';
    if ($select_wrapper_class) {
        $out.='<span class="'.$select_wrapper_class.'">';
    }
    $out.='<SELECT name="'.$postvarname.'"';
    if ($class) {
        $out.=' class="'.$class.'"';
    }
    $out.='>';
    foreach ($statuses as $status) {
        if (!in_array($status['status_id'],$hidden)) {
            $out.='<OPTION value="'.$status['status_id'].'"';
            if ($status['status_id']==$selected) {
                $out.=" SELECTED";
            }
            $out.='>'.$status['name'];
            $out.='</OPTION>
                ';
        }
    }
    $out.='</SELECT>';
    if ($select_wrapper_class) {
        $out.='</span>';
    }
    return $out;
}

function participant_status__multi_select_field($postvarname,$selected,$mpoptions=array()) {
    // $postvarname - name of form field
    // selected - array of pre-selected experimenter usernames
    global $lang, $settings;

    $out="";
    $statuses=participant_status__get_statuses();

    $mylist=array();
    foreach ($statuses as $status_id=>$status) {
        $mylist[$status_id]=$status['name'];
    }

    if (!is_array($mpoptions)) {
        $mpoptions=array();
    }
    $out.= get_tag_picker($postvarname,$mylist,$selected,$mpoptions);
    return $out;
}


function participant__status_id_list_to_status_names($status_list) {
    $allstatuses=participant_status__get_statuses();
    $statusids=explode(",",$status_list);
    $statusnames=array();
    foreach ($statusids as $id) {
        if ($id!='') {
            $statusnames[]=$allstatuses[$id]['name'];
        }
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
            if ($line['content_type']=='participant_status_name') {
                $field='name';
            } else {
                $field='error';
            }
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
        if ($status[$what]=='y') {
            $res[]=$status_id;
        }
    }
    if ($what=='is_default_active' || $what=='is_default_inactive') {
        return $res[0];
    } else {
        return $res;
    }
}

function participant_status__get_pquery_snippet($what="eligible_for_experiments") {
    // what can be access_to_profile, eligible_for_experiments, is_default_active or is_default_inactive
    $check_statuses=participant_status__get($what);
    if (count($check_statuses)>0) {
        if ($what=='is_default_active' || $what=='is_default_inactive') {
            return " status_id='".$check_statuses."' ";
        } else {
            $snippet=" status_id IN (".implode(", ",$check_statuses).") ";
            return $snippet;
        }
    } else {
        return '';
    }
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
    $columns['email']=array('use_in_tables'=>1,'lang_symbol'=>'email','include_in_freetext_search'=>1);
    $columns['participant_id']=array('use_in_tables'=>1,'lang_symbol'=>'participant_id','include_in_freetext_search'=>1);
    $columns['number_noshowup']=array('use_in_tables'=>1,'lang_symbol'=>'noshowup','include_in_freetext_search'=>0);
    $columns['rules_signed']=array('use_in_tables'=>1,'lang_symbol'=>'rules_signed','include_in_freetext_search'=>0,'session_list_editable'=>'checkbox');
    $columns['creation_time']=array('use_in_tables'=>1,'lang_symbol'=>'creation_time','include_in_freetext_search'=>0);
    $columns['deletion_time']=array('use_in_tables'=>1,'lang_symbol'=>'deletion_time','include_in_freetext_search'=>0);
    $columns['last_enrolment']=array('use_in_tables'=>1,'lang_symbol'=>'last_enrolment','include_in_freetext_search'=>0);
    $columns['last_profile_update']=array('use_in_tables'=>1,'lang_symbol'=>'last_profile_update','include_in_freetext_search'=>0);
    $columns['last_activity']=array('use_in_tables'=>1,'lang_symbol'=>'last_activity','include_in_freetext_search'=>0);
    $columns['last_login_attempt']=array('use_in_tables'=>1,'lang_symbol'=>'last_login_attempt','include_in_freetext_search'=>0);
    $columns['failed_login_attempts']=array('use_in_tables'=>1,'lang_symbol'=>'failed_login_attempts','include_in_freetext_search'=>0);
    $columns['locked']=array('use_in_tables'=>1,'lang_symbol'=>'locked','include_in_freetext_search'=>0);
    $columns['subpool_id']=array('use_in_tables'=>1,'lang_symbol'=>'subpool','include_in_freetext_search'=>0);
    $columns['subscriptions']=array('use_in_tables'=>1,'lang_symbol'=>'subscriptions','include_in_freetext_search'=>0);
    $columns['status_id']=array('use_in_tables'=>1,'lang_symbol'=>'participant_status','include_in_freetext_search'=>0);
    $columns['pending_profile_update_request']=array('use_in_tables'=>1,'lang_symbol'=>'pending_profile_update_request','include_in_freetext_search'=>0);
    $columns['language']=array('use_in_tables'=>1,'lang_symbol'=>'language','include_in_freetext_search'=>0);
    $columns['remarks']=array('use_in_tables'=>1,'lang_symbol'=>'remarks','include_in_freetext_search'=>1);

    $columns['participant_id_crypt']=array('use_in_tables'=>1,'lang_symbol'=>'participant_id_crypt','include_in_freetext_search'=>1);
    $columns['password_crypted']=array('use_in_tables'=>0,'lang_symbol'=>'','include_in_freetext_search'=>0);
    $columns['confirmation_token']=array('use_in_tables'=>0,'lang_symbol'=>'','include_in_freetext_search'=>0);
    $columns['pwreset_token']=array('use_in_tables'=>0,'lang_symbol'=>'','include_in_freetext_search'=>0);
    $columns['pwreset_request_time']=array('use_in_tables'=>0,'lang_symbol'=>'','include_in_freetext_search'=>0);
    $columns['profile_update_request_new_pool']=array('use_in_tables'=>0,'lang_symbol'=>'','include_in_freetext_search'=>0);
    $columns['apply_permanent_queries']=array('use_in_tables'=>0,'lang_symbol'=>'','include_in_freetext_search'=>0);
    $columns['number_reg']=array('use_in_tables'=>0,'lang_symbol'=>'','include_in_freetext_search'=>0);
    //$columns['']=array('use_in_tables'=>0,'lang_symbol'=>'','include_in_freetext_search'=>0);

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
            if (isset($arr['lang_symbol'])) {
                $lang_symbol=$arr['lang_symbol'];
            } else {
                $lang_symbol=$k;
            }
            $other_pfields[$k]=lang($lang_symbol);
        }
    }

    $cols=array();
    if ($listtype=='result_table_search_active' || $listtype=='result_table_search_all') {
        $cols['checkbox']=array('display_text'=>lang('checkbox'),'on_list'=>true,'allow_remove'=>false,'sortable'=>false,'disallow_hide'=>true);
        $cols['pform_fields']='';
        $cols['other_pfields']='';
        $cols['edit_link']=array('display_text'=>lang('edit_link'),'on_list'=>true,'allow_remove'=>false,'sortable'=>false,'disallow_hide'=>true);
    } elseif ($listtype=='result_table_assign') {
        $cols['checkbox']=array('display_text'=>lang('checkbox'),'on_list'=>true,'allow_remove'=>false,'sortable'=>false,'disallow_hide'=>true);
        $cols['pform_fields']='';
        $cols['other_pfields']='';
        $cols['edit_link']=array('display_text'=>lang('edit_link'),'on_list'=>false,'allow_remove'=>true,'sortable'=>false);
    } elseif ($listtype=='result_table_search_duplicates') {
        $cols['pform_fields']='';
        $cols['other_pfields']='';
        $cols['edit_link']=array('display_text'=>lang('edit_link'),'on_list'=>true,'allow_remove'=>false,'sortable'=>false,'disallow_hide'=>true);
    } elseif ($listtype=='result_table_search_unconfirmed') {
        $cols['checkbox']=array('display_text'=>lang('checkbox'),'on_list'=>true,'allow_remove'=>false,'sortable'=>false,'disallow_hide'=>true);
        $cols['email_unconfirmed']=array('display_text'=>lang('email_with_confirmation_email'),'on_list'=>true,'allow_remove'=>false,'sortable'=>true);
        $cols['pform_fields']='';
        $cols['other_pfields']='';
        $cols['edit_link']=array('display_text'=>lang('edit_link'),'on_list'=>true,'allow_remove'=>false,'sortable'=>false,'disallow_hide'=>true);
    } elseif ($listtype=='experiment_assigned_list') {
        $cols['checkbox']=array('display_text'=>lang('checkbox'),'on_list'=>true,'allow_remove'=>false,'sortable'=>false,'disallow_hide'=>true);
        $cols['pform_fields']='';
        $cols['other_pfields']='';
        $cols['invited']=array('display_text'=>lang('invited'),'on_list'=>true,'allow_remove'=>false);
        $cols['edit_link']=array('display_text'=>lang('edit_link'),'on_list'=>false,'allow_remove'=>true,'sortable'=>false);
    } elseif ($listtype=='session_participants_list') {
        $cols['checkbox']=array('display_text'=>lang('checkbox'),'on_list'=>true,'allow_remove'=>false,'sortable'=>false,'disallow_hide'=>true);
        $cols['order_number']=array('display_text'=>lang('order_number'),'display_table_head'=>'','sortable'=>false,'disallow_hide'=>true);
        $cols['pform_fields']='';
        $cols['other_pfields']='';
        $cols['session_id']=array('display_text'=>lang('session'),'on_list'=>true,'allow_remove'=>false,'sort_order'=>'session_id');
        $cols['payment_budget']=array('display_text'=>lang('payment_budget'),'display_table_head'=>lang('payment_budget_abbr'),'on_list'=>true,'allow_remove'=>false);
        $cols['payment_type']=array('display_text'=>lang('payment_type'),'display_table_head'=>lang('payment_type_abbr'),'on_list'=>true,'allow_remove'=>false);
        $cols['payment_amount']=array('display_text'=>lang('payment_amount'),'display_table_head'=>lang('payment_amount_abbr'),'on_list'=>true,'allow_remove'=>false,'sort_order'=>'payment_amt');
        $cols['pstatus_id']=array('display_text'=>lang('participation_status'),'on_list'=>true,'allow_remove'=>false,'disallow_hide'=>true);
        $cols['edit_link']=array('display_text'=>lang('edit_link'),'on_list'=>false,'allow_remove'=>true,'sortable'=>false);
    } elseif ($listtype=='session_participants_list_pdf') {
        $cols['order_number']=array('display_text'=>lang('order_number'),'display_table_head'=>'','sortable'=>false,'disallow_hide'=>true);
        $cols['pform_fields']='';
        $cols['other_pfields']='';
        $cols['session_id']=array('display_text'=>lang('session'),'on_list'=>true,'allow_remove'=>false,'sort_order'=>'session_id','disallow_hide'=>true);
        $cols['payment_amount']=array('display_text'=>lang('payment_amount'),'display_table_head'=>lang('payment_amount_abbr'),'sort_order'=>'payment_amt');
        $cols['pstatus_id']=array('display_text'=>lang('participation_status'),'disallow_hide'=>true);
    } elseif ($listtype=='email_participant_guesses_list') {
        $cols['email']=array('display_text'=>lang('email'),'on_list'=>true,'allow_remove'=>false,'sortable'=>true);
        $cols['pform_fields']='';
    } elseif ($listtype=='anonymize_profile_list') {
        $cols['email']=array('display_text'=>lang('email'),'on_list'=>true,'allow_remove'=>false,'sortable'=>true);
        $cols['pform_fields']='';
    }

    $poss_cols=array();
    foreach ($cols as $col=>$colarr) {
        if ($col=='pform_fields') {
            foreach ($formfields as $f) {
                if (!isset($cols[$f['mysql_column_name']]) && !isset($ptable_columns[$f['mysql_column_name']])) {
                    $session_editable=false;
                    if (preg_match("/(radioline|select_list|select_lang|radioline_lang|select_numbers|boolean)/",$f['type'])) {
                        $session_editable=true;
                    }
                    $display_text=trim((string)participant__field_localized_text($f,'name_lang','name_lang'));
                    if ($display_text==='') {
                        $display_text=lang('mysql_column_name');
                    }
                    $poss_cols[$f['mysql_column_name']]=array(
                        'display_text'=>$display_text,
                        'session_list_editable'=>$session_editable
                    );
                }
            }
        } elseif ($col=='other_pfields') {
            foreach ($other_pfields as $ofield=>$oname) {
                if (!isset($cols[$ofield])) {
                    $poss_cols[$ofield]=array('display_text'=>$oname);
                    if (isset($ptable_columns[$ofield]['session_list_editable'])) {
                        $poss_cols[$ofield]['session_list_editable']=$ptable_columns[$ofield]['session_list_editable'];
                    }
                }
            }
        } else {
            $poss_cols[$col]=$colarr;
        }
    }
    return $poss_cols;
}

function participant__get_internal_freetext_search_fields() {
    $columns=participant__nonuserdefined_columns();
    $sfields=array();
    foreach ($columns as $k=>$arr) {
        if (isset($arr['include_in_freetext_search']) && $arr['include_in_freetext_search']) {
            if (isset($arr['lang_symbol'])) {
                $lang_symbol=$arr['lang_symbol'];
            } else {
                $lang_symbol=$k;
            }
            $sfields[]=array('value'=>$k,'name'=>lang($lang_symbol));
        }
    }
    return $sfields;
}

function participant__get_result_table_columns($list) {
    // $list can be: result_table_search_active, result_table_search_all,
    // result_table_assign, result_table_search_duplicates, session_list,session_list_pdf
    // anonymize_profile_list
    global $preloaded_result_table_columns;
    if (isset($preloaded_result_table_columns[$list]) && is_array($preloaded_result_table_columns[$list])) {
        return $preloaded_result_table_columns[$list];
    } else {
        $allcols=participant__get_possible_participant_columns($list);
        $pars=array(':item_type'=>$list);
        $query="SELECT *
                FROM ".table('objects')."
                WHERE item_type= :item_type
                ORDER BY order_number";
        $result=or_query($query,$pars);
        $saved_cols=array();
        while ($line=pdo_fetch_assoc($result)) {
            $saved_cols[$line['item_name']]=$line;
        }
        $listcols=options__ordered_lists_get_current($allcols,$saved_cols);
        foreach ($listcols as $k=>$arr) {
            if (!isset($arr['on_list']) || !$arr['on_list']) {
                unset($listcols[$k]);
            }
        }
        $preloaded_result_table_columns[$list]=$listcols;
        return $listcols;
    }
}


function participant__get_result_table_headcells($columns,$allow_sort=true) {
    global $settings, $color;
    $pform_columns=participant__load_all_pform_fields();
    $out='';
    foreach ($columns as $k=>$arr) {
        if (isset($arr['display_table_head'])) {
            $arr['display_text']=$arr['display_table_head'];
        }
        if (isset($arr['sort_order'])) {
            $sort_order=$arr['sort_order'];
        } else {
            $sort_order=$k;
        }
        switch ($k) {
            case 'checkbox':
                $out.='<div class="orsee-table-cell">'.lang('select_all').'
                '.javascript__selectall_checkbox_script().'</div>';
                break;
            case 'number_noshowup':
                $out.=query__headcell($arr['display_text'],"number_noshowup,number_reg",$allow_sort);
                break;
            case 'rules_signed':
                if ($settings['enable_rules_signed_tracking']=='y') {
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
                if (check_allow('participants_edit')) {
                    $out.='<div class="orsee-table-cell orsee-table-action"></div>';
                }
                break;
            default:
                if (isset($pform_columns[$k])) {
                    $out.=query__headcell($pform_columns[$k]['column_name'],$pform_columns[$k]['sort_order'],$allow_sort);
                } else {
                    if (isset($arr['sortable']) && $arr['sortable']==false) {
                        $out.=query__headcell($arr['display_text']);
                    } else {
                        $out.=query__headcell($arr['display_text'],$sort_order,$allow_sort);
                    }
                }
        }
    }
    return $out;
}

function participant__get_result_table_grid_template($columns,$with_leading_spacer=false) {
    $tracks=array();
    if ($with_leading_spacer) {
        $tracks[]='minmax(0.8rem, 1.2rem)';
    }
    foreach ($columns as $k=>$arr) {
        switch ($k) {
            case 'checkbox':
                $tracks[]='minmax(2rem, 2.2rem)';
                break;
            case 'rules_signed':
                $tracks[]='minmax(4.5rem, 5.5rem)';
                break;
            case 'payment_amount':
                $tracks[]='minmax(7rem, 8.5rem)';
                break;
            case 'edit_link':
                $tracks[]='minmax(3.2rem, 4rem)';
                break;
            default:
                $tracks[]='minmax(9rem, 1fr)';
        }
    }
    return implode(' ', $tracks);
}

function participant__get_result_table_headcells_pdf($columns) {
    global $settings;

    $pform_columns=participant__load_all_pform_fields();
    $table_headings=array();
    foreach ($columns as $k=>$arr) {
        if (isset($arr['display_table_head'])) {
            $arr['display_text']=$arr['display_table_head'];
        }
        switch ($k) {
            case 'rules_signed':
                if ($settings['enable_rules_signed_tracking']=='y') {
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
    foreach ($table_headings as $k=>$v) {
        $table_headings[$k]=str_replace("&nbsp;"," ",$v);
    }
    return $table_headings;
}

function participant__get_result_table_row($columns,$p,$select_wrapper_class='select is-primary',$editable_session_columns=array()) {
    global $settings, $color, $expadmindata;
    global $thislist_sessions, $thislist_avail_payment_budgets, $thislist_avail_payment_types;

    $pform_columns=participant__load_all_pform_fields();

    $out='';
    $cell=function ($content,$style='',$label='') {
        $attr='';
        if ($style) {
            $attr=' style="'.$style.'"';
        }
        $label_attr='';
        if ($label!=='') {
            $label_attr=' data-label="'.htmlspecialchars(strip_tags((string)$label),ENT_QUOTES).'"';
        }
        return '<div class="orsee-table-cell"'.$attr.$label_attr.'>'.$content.'</div>';
    };
    foreach ($columns as $k=>$arr) {
        $cell_label='';
        if (isset($arr['display_table_head'])) {
            $cell_label=$arr['display_table_head'];
        } elseif (isset($arr['display_text'])) {
            $cell_label=$arr['display_text'];
        }
        if (isset($pform_columns[$k]['column_name'])) {
            $cell_label=$pform_columns[$k]['column_name'];
        }
        if ($k=='checkbox') {
            $cell_label=lang('select');
        } elseif ($k=='status_id') {
            $cell_label=lang('participant_status_abbr');
        } elseif ($k=='edit_link') {
            $cell_label=lang('action');
        } elseif ($k=='order_number') {
            $cell_label='';
        }

        $hide_for_admin_types=array();
        if (isset($arr['item_details']['hide_admin_types'])) {
            $hide_for_admin_types=explode(",",$arr['item_details']['hide_admin_types']);
        }
        if (in_array($expadmindata['admin_type'],$hide_for_admin_types)) {
            $out.=$cell(lang('hidden_data_symbol'),'',$cell_label);
        } else {
            switch ($k) {
                case 'email_unconfirmed':
                    $message="";
                    $message=experimentmail__get_confirmation_mail_text($p);
                    $message=str_replace(" ","%20",$message);
                    $message=str_replace("\n\m","\n",$message);
                    $message=str_replace("\m\n","\n",$message);
                    $message=str_replace("\m","\n",$message);
                    $message=str_replace("\n","%0D%0A",$message);
                    $linktext='mailto:'.$p['email'].'?subject='.str_replace(" ","%20",lang('registration_email_subject')).'&reply-to='.urlencode($settings['support_mail']).'&body='.$message;
                    $out.=$cell('<A HREF="'.$linktext.'">'.$p['email'].'</A>','',$cell_label);
                    break;
                case 'checkbox':
                    $content='<INPUT type="checkbox" name="sel['.$p['participant_id'].']" value="y"';
                    if (isset($_REQUEST['sel'][$p['participant_id']]) && $_REQUEST['sel'][$p['participant_id']]=='y') {
                        $content.=' CHECKED';
                    } elseif (isset($_SESSION['sel'][$p['participant_id']]) && $_SESSION['sel'][$p['participant_id']]=='y') {
                        $content.=' CHECKED';
                    }
                    $content.='>';
                    $out.=$cell($content,'',$cell_label);
                    break;
                case 'number_noshowup':
                    $out.=$cell($p['number_noshowup'].'/'.$p['number_reg'],'',$cell_label);
                    break;
                case 'order_number':
                    if (isset($p[$k])) {
                        $out.=$cell($p[$k],'padding-inline-end: 0.35rem;',$cell_label);
                    } else {
                        $out.=$cell('','padding-inline-end: 0.35rem;',$cell_label);
                    }
                    break;
                case 'invited':
                    $out.=$cell(($p['invited'] ? lang('y') : lang('n')),'',$cell_label);
                    break;
                case 'rules_signed':
                    if ($settings['enable_rules_signed_tracking']=='y') {
                        if (isset($editable_session_columns['rules_signed']) && $editable_session_columns['rules_signed']) {
                            $content='<INPUT type="checkbox" name="rules['.$p['participant_id'].']" value="y"';
                            if ($p['rules_signed']=='y') {
                                $content.=' CHECKED';
                            }
                            $content.='>';
                        } else {
                            if ($p['rules_signed']=='y') {
                                $content=lang('y');
                            } else {
                                $content=lang('n');
                            }
                        }
                        $out.=$cell($content,'',$cell_label);
                    }
                    break;
                case 'subscriptions':
                    $exptypes=load_external_experiment_types();
                    $inv_arr=db_string_to_id_array($p[$k]);
                    $inv_names=array();
                    foreach ($inv_arr as $inv) {
                        if (isset($exptypes[$inv]['exptype_name'])) {
                            $inv_names[]=$exptypes[$inv]['exptype_name'];
                        } else {
                            $inv_names[]='undefined';
                        }
                    }
                    $out.=$cell(implode(", ",$inv_names),'',$cell_label);
                    break;
                case 'subpool_id':
                    $subpools=subpools__get_subpools();
                    $subpool_name=(isset($subpools[$p[$k]]['subpool_name'])) ? $subpools[$p[$k]]['subpool_name'] : $p[$k];
                    $out.=$cell($subpool_name,'',$cell_label);
                    break;
                case 'status_id':
                    $participant_statuses=participant_status__get_statuses();
                    $pstatus_name=(isset($participant_statuses[$p[$k]]['name'])) ? $participant_statuses[$p[$k]]['name'] : $p[$k];
                    if ($participant_statuses[$p['status_id']]['eligible_for_experiments']=='y') {
                        $ccolor='var(--color-participant-status-eligible)';
                    } else {
                        $ccolor='var(--color-participant-status-noneligible)';
                    }
                    $out.=$cell($pstatus_name,'background: '.$ccolor.';',$cell_label);
                    break;
                case 'edit_link':
                    if (check_allow('participants_edit')) {
                        $out.='<div class="orsee-table-cell orsee-table-action" data-label="'.htmlspecialchars(strip_tags((string)$cell_label),ENT_QUOTES).'"><A HREF="#" class="button orsee-btn orsee-btn-compact" onclick="javascript:editPopup('.$p['participant_id'].'); return false;"><i class="fa fa-pencil-square-o" style="padding: 0 0.3em 0 0"></i>'.lang('edit').'</A></div>';
                    }
                    break;
                case 'creation_time':
                case 'deletion_time':
                case 'last_enrolment':
                case 'last_profile_update':
                case 'last_activity':
                case 'last_login_attempt':
                    if ($p[$k]) {
                        $out.=$cell(ortime__format($p[$k],'hide_second:false'),'white-space: nowrap;', $cell_label);
                    } else {
                        $out.=$cell('-', 'white-space: nowrap;', $cell_label);
                    }
                    break;
                case 'session_id':
                    $content='';
                    if (check_allow('experiment_edit_participants')) {
                        $content.='<INPUT type=hidden name="orig_session['.$p['participant_id'].']" value="'.$p['session_id'].'">';
                        $content.=select__sessions($p['session_id'],'session['.$p['participant_id'].']',$thislist_sessions,false,false,$select_wrapper_class);
                    } else {
                        $content.=session__build_name($thislist_sessions[$p['session_id']]);
                    }
                    $out.=$cell($content,'',$cell_label);
                    break;
                case 'payment_budget':
                    if ($settings['enable_payment_module']=='y') {
                        $payment_budgets=payments__load_budgets();
                        if (check_allow('payments_edit')) {
                            $out.=$cell(payments__budget_selectfield('paybudget['.$p['participant_id'].']',$p['payment_budget'],array(),$thislist_avail_payment_budgets,$select_wrapper_class),'',$cell_label);
                        } elseif (check_allow('payments_view')) {
                            if (isset($payment_budgets[$p['payment_budget']])) {
                                $out.=$cell($payment_budgets[$p['payment_budget']]['budget_name'],'',$cell_label);
                            } else {
                                $out.=$cell('-','',$cell_label);
                            }
                        }
                    }
                    break;
                case 'payment_type':
                    if ($settings['enable_payment_module']=='y') {
                        $payment_types=payments__load_paytypes();
                        if (check_allow('payments_edit')) {
                            $out.=$cell(payments__paytype_selectfield('paytype['.$p['participant_id'].']',$p['payment_type'],array(),$thislist_avail_payment_types,$select_wrapper_class),'',$cell_label);
                        } elseif (check_allow('payments_view')) {
                            if (isset($payment_types[$p['payment_type']])) {
                                $out.=$cell($payment_types[$p['payment_type']],'',$cell_label);
                            } else {
                                $out.=$cell('-','',$cell_label);
                            }
                        }
                    }
                    break;
                case 'payment_amount':
                    if ($settings['enable_payment_module']=='y') {
                        if (check_allow('payments_edit')) {
                            $content='<INPUT type="text" name="payamt['.$p['participant_id'].']" dir="ltr" value="';
                            if ($p['payment_amt']!='') {
                                $content.=$p['payment_amt'];
                            } else {
                                $content.='0.00';
                            }
                            $content.='" size="7" maxlength="10" style="text-align:end;">';
                            $out.=$cell($content,'',$cell_label);
                        } elseif (check_allow('payments_view')) {
                            if ($p['payment_amt']!='') {
                                $out.=$cell($p['payment_amt'],'',$cell_label);
                            } else {
                                $out.=$cell('-','',$cell_label);
                            }
                        }
                    }
                    break;
                case 'pstatus_id':
                    $content='';
                    if (check_allow('experiment_edit_participants')) {
                        $content.='<INPUT type=hidden name="orig_pstatus_id['.$p['participant_id'].']" value="'.$p['pstatus_id'].'">';
                        $content.=expregister__participation_status_select_field('pstatus_id['.$p['participant_id'].']',$p['pstatus_id'],array(),true,$select_wrapper_class);
                    } else {
                        $pstatuses=expregister__get_participation_statuses();
                        $content.=$pstatuses[$p['pstatus_id']]['internal_name'];
                    }
                    $out.=$cell($content,'',$cell_label);
                    break;
                default:
                    if (isset($pform_columns[$k])) {
                        $content='';
                        $cell_rendered=false;
                        $allow_session_edit=(isset($editable_session_columns[$k]) && $editable_session_columns[$k]);
                        $field_type=$pform_columns[$k]['type'];
                        if ($allow_session_edit && preg_match("/(radioline|select_list|select_lang|radioline_lang|select_numbers|boolean)/",$field_type)) {
                            if ($field_type=='select_list' || $field_type=='radioline') {
                                $f=$pform_columns[$k];
                                $f['value']=$p[$k];
                                $content=form__render_select_list($f,'pedit['.$k.']['.$p['participant_id'].']',true);
                            } elseif ($field_type=='boolean') {
                                $content='<label class="checkbox"><input type="checkbox" name="pedit['.$k.']['.$p['participant_id'].']" value="y"';
                                if ($p[$k]==='y') {
                                    $content.=' checked';
                                }
                                $content.='></label>';
                            } elseif ($field_type=='select_lang' || $field_type=='radioline_lang') {
                                if ($pform_columns[$k]['include_none_option']=='y') {
                                    $incnone=true;
                                } else {
                                    $incnone=false;
                                }
                                $content='<span class="'.$select_wrapper_class.'">'.
                                    language__selectfield_item($k,$k,'pedit['.$k.']['.$p['participant_id'].']',$p[$k],$incnone,$pform_columns[$k]['order_select_lang_values'])
                                    .'</span>';
                            } elseif ($field_type=='select_numbers') {
                                if ($pform_columns[$k]['include_none_option']=='y') {
                                    $incnone=true;
                                } else {
                                    $incnone=false;
                                }
                                if ($pform_columns[$k]['values_reverse']=='y') {
                                    $reverse=true;
                                } else {
                                    $reverse=false;
                                }
                                $content='<span class="'.$select_wrapper_class.'">'.
                                    participant__select_numbers($k,'pedit['.$k.']['.$p['participant_id'].']',$p[$k],$pform_columns[$k]['value_begin'],$pform_columns[$k]['value_end'],0,$pform_columns[$k]['value_step'],$reverse,$incnone,false,'',false,true)
                                    .'</span>';
                            }
                        } else {
                            if ($pform_columns[$k]['link_as_email_in_lists']=='y') {
                                $content.='<A HREF="mailto:'.$p[$k].'">';
                            }
                            if ($field_type==='boolean') {
                                if ($p[$k]==='y') {
                                    $content.=lang('y');
                                } else {
                                    $content.=lang('n');
                                }
                            } elseif ($field_type==='checkboxlist_lang') {
                                $content.=participant__select_lang_idlist_to_names($k,$p[$k]);
                            } elseif (preg_match("/(radioline|select_list|select_lang|radioline_lang)/",$field_type)) {
                                if (isset($pform_columns[$k]['lang'][$p[$k]])) {
                                    $content.=lang($pform_columns[$k]['lang'][$p[$k]]);
                                } else {
                                    $content.=$p[$k];
                                }
                            } elseif ($field_type==='date') {
                                $date_mode=(isset($pform_columns[$k]['date_mode']) ? $pform_columns[$k]['date_mode'] : 'ymd');
                                $date_value=ortime__format_ymd_localized((string)$p[$k],'',$date_mode);
                                if ($date_value) {
                                    $content.=$date_value;
                                } else {
                                    $content.=$p[$k];
                                }
                                $out.=$cell($content,'white-space: nowrap;',$cell_label);
                                $cell_rendered=true;
                                $content='';
                            } else {
                                $content.=$p[$k];
                            }
                            if ($pform_columns[$k]['link_as_email_in_lists']=='y') {
                                $content.='</A>';
                            }
                        }
                        if (!$cell_rendered) {
                            $out.=$cell($content,'',$cell_label);
                        }
                    } else {
                        if (isset($p[$k])) {
                            $out.=$cell($p[$k],'',$cell_label);
                        } else {
                            $out.=$cell('???','',$cell_label);
                        }
                    }
            }
        }
    }
    return $out;
}


function participant__get_result_table_row_pdf($columns,$p) {
    global $settings, $color, $expadmindata;
    global $thislist_sessions;

    $pform_columns=participant__load_all_pform_fields();

    $row=array();
    foreach ($columns as $k=>$arr) {
        $hide_for_admin_types=array();
        if (isset($arr['item_details']['hide_admin_types'])) {
            $hide_for_admin_types=explode(",",$arr['item_details']['hide_admin_types']);
        }
        if (in_array($expadmindata['admin_type'],$hide_for_admin_types)) {
            $row[]=lang('hidden_data_symbol');
        } else {
            switch ($k) {
                case 'number_noshowup':
                    $row[]=$p['number_noshowup'].'/'.$p['number_reg'];
                    break;
                case 'rules_signed':
                    if ($settings['enable_rules_signed_tracking']=='y') {
                        $row[]= ($p['rules_signed']!='y') ? "X" : '';
                    }
                    break;
                case 'subscriptions':
                    $exptypes=load_external_experiment_types();
                    $inv_arr=db_string_to_id_array($p[$k]);
                    $inv_names=array();
                    foreach ($inv_arr as $inv) {
                        if (isset($exptypes[$inv]['exptype_name'])) {
                            $inv_names[]=$exptypes[$inv]['exptype_name'];
                        } else {
                            $inv_names[]='undefined';
                        }
                    }
                    $row[]=implode(", ",$inv_names);
                    break;
                case 'subpool_id':
                    $subpools=subpools__get_subpools();
                    $subpool_name=(isset($subpools[$p[$k]]['subpool_name'])) ? $subpools[$p[$k]]['subpool_name'] : $p[$k];
                    $row[]=$subpool_name;
                    break;
                case 'status_id':
                    $participant_statuses=participant_status__get_statuses();
                    $pstatus_name=(isset($participant_statuses[$p[$k]]['name'])) ? $participant_statuses[$p[$k]]['name'] : $p[$k];
                    $row[]=$pstatus_name;
                    break;
                case 'creation_time':
                case 'deletion_time':
                case 'last_enrolment':
                case 'last_profile_update':
                case 'last_activity':
                case 'last_login_attempt':
                    if ($p[$k]) {
                        $row[]=ortime__format($p[$k],'hide_second:false');
                    } else {
                        $row[]='-';
                    }
                    break;
                case 'session_id':
                    $row[]=session__build_name($thislist_sessions[$p['session_id']]);
                    break;
                case 'payment_budget':
                    if ($settings['enable_payment_module']=='y' && check_allow('payments_view')) {
                        $payment_budgets=payments__load_budgets();
                        if (isset($payment_budgets[$p['payment_budget']])) {
                            $row[]=$payment_budgets[$p['payment_budget']]['budget_name'];
                        } else {
                            $row[]='-';
                        }
                    }
                    break;
                case 'payment_type':
                    if ($settings['enable_payment_module']=='y' && check_allow('payments_view')) {
                        $payment_types=payments__load_paytypes();
                        if (isset($payment_types[$p['payment_type']])) {
                            $row[]=$payment_types[$p['payment_type']];
                        } else {
                            $row[]='-';
                        }
                    }
                    break;
                case 'payment_amount':
                    if ($settings['enable_payment_module']=='y' && check_allow('payments_view')) {
                        if ($p['payment_amt']!='') {
                            $row[]=$p['payment_amt'];
                        } else {
                            $row[]='-';
                        }
                    }
                    break;
                case 'pstatus_id':
                    $pstatuses=expregister__get_participation_statuses();
                    $row[]=$pstatuses[$p['pstatus_id']]['internal_name'];
                    break;
                default:
                    if (isset($pform_columns[$k])) {
                        if ($pform_columns[$k]['type']==='boolean') {
                            $row[]=(($p[$k]==='y') ? lang('y') : lang('n'));
                        } elseif ($pform_columns[$k]['type']==='checkboxlist_lang') {
                            $row[]=participant__select_lang_idlist_to_names($k,$p[$k]);
                        } elseif (preg_match("/(radioline|select_list|select_lang|radioline_lang)/",$pform_columns[$k]['type'])) {
                            if (isset($pform_columns[$k]['lang'][$p[$k]])) {
                                $row[]=lang($pform_columns[$k]['lang'][$p[$k]]);
                            } else {
                                $row[]=$p[$k];
                            }
                        } elseif ($pform_columns[$k]['type']==='date') {
                            $date_mode=(isset($pform_columns[$k]['date_mode']) ? $pform_columns[$k]['date_mode'] : 'ymd');
                            $date_value=ortime__format_ymd_localized((string)$p[$k],'',$date_mode);
                            if ($date_value) {
                                $row[]=$date_value;
                            } else {
                                $row[]=$p[$k];
                            }
                        } else {
                            $row[]=$p[$k];
                        }
                    } else {
                        if (isset($p[$k])) {
                            $row[]=$p[$k];
                        } else {
                            $row[]='???';
                        }
                    }
            }
        }
    }
    foreach ($row as $k=>$v) {
        $row[$k]=str_replace("&nbsp;"," ",$v);
    }
    return $row;
}

function participant__load_all_pform_fields($tlang='') {
    global $preloaded_all_pform_fields;
    if (!$tlang) {
        $tlang=lang('lang');
    }
    if (isset($preloaded_all_pform_fields[$tlang]) && is_array($preloaded_all_pform_fields[$tlang])) {
        return $preloaded_all_pform_fields[$tlang];
    } else {
        $formfields=participantform__load();
        $pform_columns=array();
        foreach ($formfields as $f) {
            if ($f['search_result_sort_order']) {
                $f['sort_order']=$f['search_result_sort_order'];
            } else {
                $f['sort_order']=$f['mysql_column_name'];
            }
            $f['column_name']=participant__field_localized_text($f,'name_lang','name_lang',$tlang);
            if (!$f['column_name']) {
                $f['column_name']=$f['mysql_column_name'];
            }
            if (preg_match("/(radioline|select_list)/",$f['type'])) {
                $f['lang']=array();
                foreach ($f['option_values'] as $v=>$option_symbol) {
                    if ($tlang!=lang('lang')) {
                        $oname=load_language_symbol($option_symbol,$tlang);
                        if ($oname) {
                            $f['lang'][$v]=$oname;
                        } else {
                            $f['lang'][$v]=$option_symbol;
                        }
                    } else {
                        $f['lang'][$v]=lang($option_symbol);
                    }
                }
            } elseif (preg_match("/(select_lang|radioline_lang|checkboxlist_lang)/",$f['type'])) {
                $f['lang']=lang__load_lang_cat($f['mysql_column_name'],$tlang);
            }
            $pform_columns[$f['mysql_column_name']]=$f;
        }
        $preloaded_all_pform_fields[$tlang]=$pform_columns;
        return $pform_columns;
    }
}


function participant__load_participant_email_fields($tlang='') {
    if (!$tlang) {
        $tlang=lang('lang');
    }
    $profile_field_specs=participant__profile_field_editor_specs();
    $formfields=participantform__load();
    $result_columns=array();
    $public_scope_contexts=$profile_field_specs['fields']['scope_contexts']['default'];
    foreach ($formfields as $f) {
        if (participant__profile_field_is_applicable($f,1,$public_scope_contexts)) {
            $f['column_name']=participant__field_localized_text($f,'name_lang','name_lang',$tlang);
            if (!$f['column_name']) {
                $f['column_name']=$f['mysql_column_name'];
            }
            if (preg_match("/(radioline|select_list)/",$f['type'])) {
                $f['lang']=array();
                foreach ($f['option_values'] as $v=>$option_symbol) {
                    if ($tlang!=lang('lang')) {
                        $oname=load_language_symbol($option_symbol,$tlang);
                        if ($oname) {
                            $f['lang'][$v]=$oname;
                        } else {
                            $f['lang'][$v]=$option_symbol;
                        }
                    } else {
                        $f['lang'][$v]=lang($option_symbol);
                    }
                }
            } elseif (preg_match("/(select_lang|radioline_lang|checkboxlist_lang)/",$f['type'])) {
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
