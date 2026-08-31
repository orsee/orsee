<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="experiments";
$title="participants";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['experiment_id']) && $_REQUEST['experiment_id']) {
        $experiment_id=$_REQUEST['experiment_id'];
    } else {
        redirect("admin/experiment_main.php");
    }
}

if ($proceed) {
    if (isset($_REQUEST['session_id']) && $_REQUEST['session_id']) {
        $session_id=$_REQUEST['session_id'];
    } else {
        $session_id='';
    }

    if (isset($_REQUEST['pstatus'])) {
        $pstatus=$_REQUEST['pstatus'];
    } else {
        $pstatus='';
    }

    if (isset($_REQUEST['focus']) && $_REQUEST['focus']) {
        $focus=$_REQUEST['focus'];
    } else {
        $focus='';
    }

    if (isset($_REQUEST['search_sort']) && $_REQUEST['search_sort']) {
        $sort=query__get_sort('session_participants_list',$_REQUEST['search_sort']);
        $_REQUEST['search_sort']=$sort;
    } else {
        $sort='';
    }

    $thiscgis='?experiment_id='.urlencode((string)$experiment_id);
    if ($session_id) {
        $thiscgis.='&session_id='.urlencode((string)$session_id);
    }
    if ($pstatus!='') {
        $thiscgis.='&pstatus='.urlencode((string)$pstatus);
    }
    if ($focus) {
        $thiscgis.='&focus='.urlencode($focus);
    }
    if ($sort) {
        $thiscgis.='&search_sort='.urlencode($sort);
    }

    $allow=check_allow('experiment_show_participants','experiment_show.php?experiment_id='.$experiment_id);
    if ($proceed) {
        $experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
        if (!check_allow('experiment_restriction_override')) {
            check_experiment_allowed($experiment,"admin/experiment_show.php?experiment_id=".$experiment_id);
        }
    }
    if ($proceed) {
        $pstatuses=expregister__get_participation_statuses();
        $payment_types=payments__load_paytypes();
        $payment_budgets=payments__load_budgets();

        if ($session_id) {
            $clause="session_id = :session_id";
            $clause_pars=array(':session_id'=>$session_id);
            $display="pstatus";
            $title=lang('registered_subjects');
        } elseif (isset($pstatuses[$pstatus])) {
            $clause="pstatus_id = :pstatus";
            $clause_pars=array(':pstatus'=>$pstatus);
            if ($pstatus==0) {
                $clause.=" AND session_id != 0";
            }
            $display="pstatus";
            $title=lang('subjects_in_participation_status').' "'.$pstatuses[$pstatus]['internal_name'].'"';
        } elseif ($focus=='enroled') {
            $clause="session_id != 0";
            $clause_pars=array();
            $display="pstatus";
            $title=lang('registered_subjects');
        } elseif ($focus=='invited') {
            $clause="session_id = 0 AND invited=1";
            $clause_pars=array();
            $display="enrol";
            $title=lang('invited_subjects_not_yet_registered');
        } else {
            $clause="session_id = 0";
            $clause_pars=array();
            $display="enrol";
            $title=lang('assigned_subjects_not_yet_registered');
        }

        if ($session_id) {
            $session=orsee_db_load_array("sessions",$session_id,"session_id");
            if (!isset($session['session_id'])) {
                redirect("admin/experiment_show.php?experiment_id=".$experiment_id);
            }
        }
    }

    if ($proceed) {
        if ($session_id && $settings['enable_payment_module']=='y' &&
                (check_allow('payments_view') || check_allow('payments_edit'))) {
            $thislist_avail_payment_types=db_string_to_id_array($session['payment_types']);
            if (is_array($thislist_avail_payment_types) && count($thislist_avail_payment_types)>1) {
                $show_payment_types=true;
            } else {
                $show_payment_types=false;
                $default_payment_type=payments__get_default_paytype($experiment,$session);
            }
            $thislist_avail_payment_budgets=db_string_to_id_array($session['payment_budgets']);
            if (is_array($thislist_avail_payment_budgets) && count($thislist_avail_payment_budgets)>1) {
                $show_payment_budgets=true;
            } else {
                $show_payment_budgets=false;
                $default_payment_budget=payments__get_default_budget($experiment,$session);
            }
        }

        $editable_session_columns=array();
        if ($display!='enrol' && check_allow('experiment_edit_participants')) {
            $editable_columns_all=participant__get_result_table_columns('session_participants_list');
            foreach ($editable_columns_all as $column_name=>$column_def) {
                if (!(isset($column_def['item_details']['editable_on_session_list']) && $column_def['item_details']['editable_on_session_list']=='y')) {
                    continue;
                }
                if (!(isset($column_def['session_list_editable']) && $column_def['session_list_editable'])) {
                    continue;
                }
                if ($column_name=='rules_signed' && $settings['enable_rules_signed_tracking']!='y') {
                    continue;
                }
                $editable_session_columns[$column_name]=true;
            }
        }

        if (isset($_REQUEST['change']) && $_REQUEST['change']) {
            if (!csrf__validate_request_message()) {
                redirect('admin/'.thisdoc().$thiscgis);
            }

            $allow=check_allow('experiment_edit_participants','experiment_participants_show.php'.$thiscgis);

            if ($proceed) {
                if ($display=='enrol') {
                    $continue=true;

                    if ($_REQUEST['to_session']) {
                        $to_session=$_REQUEST['to_session'];
                    } else {
                        $to_session=0;
                    }

                    if ($to_session==0) {
                        $continue=false;
                        $_SESSION['sel']=$_REQUEST['sel'];
                        message(lang('no_session_selected'),'warning');
                        redirect('admin/'.thisdoc().$thiscgis);
                    }

                    if ($proceed) {
                        $tsession=orsee_db_load_array("sessions",$to_session,"session_id");

                        $p_to_add=array();
                        if (isset($_REQUEST['sel'])) {
                            foreach ($_REQUEST['sel'] as $k=>$v) {
                                if ($v) {
                                    $p_to_add[]=$k;
                                }
                            }
                        }
                        $num_to_add=count($p_to_add);


                        if (isset($_REQUEST['check_if_full']) && $_REQUEST['check_if_full']) {
                            $alr_reg=experiment__count_participate_at($experiment_id,$to_session);
                            $free_places=$tsession['part_needed']+$tsession['part_reserve']-$alr_reg;
                            if ($free_places < 0) {
                                $free_places=0;
                            }
                            if ($num_to_add > $free_places) {
                                $continue=false;
                                message(lang('too_many_participants_to_register').' '.
                                    lang('free_places_in_session_xxx').' '.
                                    session__build_name($tsession).':
                                    <FONT color="green">'.$free_places.'</FONT><BR>'.
                                    lang('please_change_your_selection'),'error');
                                $_SESSION['sel']=$_REQUEST['sel'];
                                redirect('admin/'.thisdoc().$thiscgis.
                                    '&to_session='.$to_session.'&check_if_full='.$_REQUEST['check_if_full']);
                            }
                        }
                    }

                    if ($proceed) {
                        if ($continue) {
                            $pars=array();
                            foreach ($p_to_add as $pid) {
                                $pars[]=array(':session_id'=>$to_session,
                                            ':experiment_id'=>$experiment_id,
                                            ':participant_id'=>$pid);
                            }
                            $query="UPDATE ".table('participate_at')."
                                    SET session_id= :session_id,
                                    pstatus_id=0
                                    WHERE experiment_id= :experiment_id
                                    AND participant_id= :participant_id";
                            $done=or_query($query,$pars);

                            if (count($p_to_add)>0) {
                                participant__update_last_enrolment_time($p_to_add);
                            }

                            $_SESSION['sel']=array();

                            message($num_to_add.' '.lang('xxx_subjects_registered_to_session_xxx').' '.
                            session__build_name($tsession).'.<BR>
                                <A HREF="'.thisdoc().'?experiment_id='.$experiment_id.
                                '&session_id='.$to_session.'">'.lang('click_here_to_go_to_session_xxx').
                                ' '.session__build_name($tsession).'</A>');
                            redirect('admin/'.thisdoc().$thiscgis);
                        }
                    }
                } else {
                    // update participant status data and payments, if enabled
                    $new_status=array();
                    $pars=array();
                    foreach ($_REQUEST['pstatus_id'] as $k=>$v) {
                        //if($v!=$_REQUEST['orig_pstatus_id'][$k]) {
                        $thispar=array(':pstatus_id'=>$v,
                                    ':experiment_id'=>$experiment_id,
                                    ':participant_id'=>$k);
                        if ($session_id && $settings['enable_payment_module']=='y' && check_allow('payments_edit')) {
                            if ($show_payment_types) {
                                if (isset($_REQUEST['paytype'][$k])) {
                                    $thispar[':payment_type']=$_REQUEST['paytype'][$k];
                                } else {
                                    $thispar[':payment_type']=0;
                                }
                            } else {
                                $thispar[':payment_type']=$default_payment_type;
                            }
                            if ($show_payment_budgets) {
                                if (isset($_REQUEST['paybudget'][$k])) {
                                    $thispar[':payment_budget']=$_REQUEST['paybudget'][$k];
                                } else {
                                    $thispar[':payment_budget']=0;
                                }
                            } else {
                                $thispar[':payment_budget']=$default_payment_budget;
                            }
                            if (isset($_REQUEST['payamt'][$k])) {
                                $thispar[':payment_amt']=$_REQUEST['payamt'][$k];
                            } else {
                                $thispar[':payment_amt']=null;
                            }
                        }
                        //}
                        $pars[]=$thispar;
                    }
                    $query="UPDATE ".table('participate_at')."
                            SET pstatus_id = :pstatus_id ";
                    if ($session_id && $settings['enable_payment_module']=='y' && check_allow('payments_edit')) {
                        $query.=", payment_amt = :payment_amt ";
                        $query.=", payment_type = :payment_type ";
                        $query.=", payment_budget = :payment_budget ";
                    }
                    $query.="WHERE experiment_id= :experiment_id
                            AND participant_id= :participant_id";
                    $done=or_query($query,$pars);

                    // update editable participant fields configured for session participants list
                    if ($display!='enrol' && count($editable_session_columns)>0 && isset($_REQUEST['pid']) && is_array($_REQUEST['pid'])) {
                        $pform_columns=participant__load_all_pform_fields();
                        $participant_updates=array();
                        $allowed_lang_values_cache=array();
                        foreach ($_REQUEST['pid'] as $k=>$v) {
                            if (!is_numeric($k)) {
                                continue;
                            }
                            $pid=(int)$k;
                            if ($pid<=0) {
                                continue;
                            }
                            $participant_updates[$pid]=array();
                        }
                        foreach ($editable_session_columns as $column_name=>$enabled) {
                            if (!$enabled) {
                                continue;
                            }
                            if ($column_name=='rules_signed') {
                                foreach ($participant_updates as $pid=>$values) {
                                    if (isset($_REQUEST['rules'][$pid]) && $_REQUEST['rules'][$pid]=='y') {
                                        $participant_updates[$pid]['rules_signed']='y';
                                    } else {
                                        $participant_updates[$pid]['rules_signed']='n';
                                    }
                                }
                                continue;
                            }
                            if (!isset($pform_columns[$column_name])) {
                                continue;
                            }
                            $pfield=$pform_columns[$column_name];
                            $ptype=$pfield['type'];
                            if ($ptype=='boolean') {
                                $posted_values=array();
                                if (isset($_REQUEST['pedit'][$column_name]) && is_array($_REQUEST['pedit'][$column_name])) {
                                    $posted_values=$_REQUEST['pedit'][$column_name];
                                }
                                foreach ($participant_updates as $pid=>$values) {
                                    if (isset($posted_values[$pid]) && $posted_values[$pid]=='y') {
                                        $participant_updates[$pid][$column_name]='y';
                                    } else {
                                        $participant_updates[$pid][$column_name]='n';
                                    }
                                }
                            } else {
                                if (!isset($_REQUEST['pedit'][$column_name]) || !is_array($_REQUEST['pedit'][$column_name])) {
                                    continue;
                                }
                                $posted_values=$_REQUEST['pedit'][$column_name];
                            }
                            if ($ptype=='select_list' || $ptype=='radioline') {
                                $allowed_values=array_keys($pfield['option_values']);
                                if ($pfield['include_none_option']=='y') {
                                    $allowed_values[]='0';
                                }
                                foreach ($participant_updates as $pid=>$values) {
                                    if (!isset($posted_values[$pid])) {
                                        continue;
                                    }
                                    $raw=(string)$posted_values[$pid];
                                    if (in_array($raw,$allowed_values,true)) {
                                        $participant_updates[$pid][$column_name]=$raw;
                                    }
                                }
                            } elseif ($ptype=='select_numbers') {
                                $begin=(float)$pfield['value_begin'];
                                $end=(float)$pfield['value_end'];
                                $step=(float)$pfield['value_step'];
                                if ($step<=0) {
                                    $step=1;
                                }
                                $min=min($begin,$end);
                                $max=max($begin,$end);
                                foreach ($participant_updates as $pid=>$values) {
                                    if (!isset($posted_values[$pid])) {
                                        continue;
                                    }
                                    $raw=trim((string)$posted_values[$pid]);
                                    if ($pfield['include_none_option']=='y' && $raw==='0') {
                                        $participant_updates[$pid][$column_name]='0';
                                        continue;
                                    }
                                    if (!is_numeric($raw)) {
                                        continue;
                                    }
                                    $num=(float)$raw;
                                    if ($num<$min || $num>$max) {
                                        continue;
                                    }
                                    $delta=$num-$begin;
                                    $factor=round($delta/$step);
                                    if (abs($delta-($factor*$step))>0.000001) {
                                        continue;
                                    }
                                    $participant_updates[$pid][$column_name]=$raw;
                                }
                            } elseif ($ptype=='select_lang' || $ptype=='radioline_lang') {
                                if (!isset($allowed_lang_values_cache[$column_name])) {
                                    $allowed_lang_values_cache[$column_name]=array();
                                    $qpars=array(':content_type'=>$column_name);
                                    $q="SELECT content_name
                                        FROM ".table('lang')."
                                        WHERE content_type= :content_type";
                                    $qres=or_query($q,$qpars);
                                    while ($qline=pdo_fetch_assoc($qres)) {
                                        $allowed_lang_values_cache[$column_name][]=(string)$qline['content_name'];
                                    }
                                }
                                $allowed_values=$allowed_lang_values_cache[$column_name];
                                if ($pfield['include_none_option']=='y') {
                                    $allowed_values[]='0';
                                }
                                foreach ($participant_updates as $pid=>$values) {
                                    if (!isset($posted_values[$pid])) {
                                        continue;
                                    }
                                    $raw=(string)$posted_values[$pid];
                                    if (in_array($raw,$allowed_values,true)) {
                                        $participant_updates[$pid][$column_name]=$raw;
                                    }
                                }
                            }
                        }
                        foreach ($participant_updates as $pid=>$values) {
                            if (!is_array($values) || count($values)==0) {
                                continue;
                            }
                            $sets=array();
                            $upars=array(':participant_id'=>$pid);
                            foreach ($values as $field_name=>$field_value) {
                                $sets[]=$field_name." = :".$field_name;
                                $upars[":".$field_name]=$field_value;
                            }
                            if (count($sets)==0) {
                                continue;
                            }
                            $uquery="UPDATE ".table('participants')."
                                     SET ".implode(", ",$sets)."
                                     WHERE participant_id = :participant_id";
                            or_query($uquery,$upars);
                        }
                    }

                    // move participants to other sessions ...
                    $new_session=array();
                    foreach ($_REQUEST['session'] as $k=>$v) {
                        if ($v!=$_REQUEST['orig_session'][$k]) {
                            $new_session[$v][]=$k;
                        }
                    }

                    $pars=array();
                    $allmids=array();
                    foreach ($new_session as $msession => $mparts) {
                        foreach ($mparts as $participant_id) {
                            $pars[]=array(':session_id'=>$msession,
                                        ':participant_id'=>$participant_id,
                                        ':experiment_id'=>$experiment_id);
                            $allmids[]=$participant_id;
                        }
                    }
                    $query="UPDATE ".table('participate_at')."
                            SET session_id = :session_id, pstatus_id=0,
                            payment_type=0, payment_amt=0
                            WHERE participant_id = :participant_id
                            AND experiment_id= :experiment_id";
                    $done=or_query($query,$pars);
                    if (count($allmids)>0) {
                        participant__update_last_enrolment_time($allmids);
                    }

                    // clean up participation statuses for 'no session's
                    $query="UPDATE ".table('participate_at')."
                            SET pstatus_id = '0'
                            WHERE session_id='0'";
                    $done=or_query($query);


                    message(lang('changes_saved'));
                    $m_message='<UL>';
                    foreach ($new_session as $msession => $mparts) {
                        $m_message.='<LI>'.count($mparts).' ';
                        if ($msession==0) {
                            $m_message.=lang('xxx_subjects_removed_from_registration');
                        } else {
                            $tsession=orsee_db_load_array("sessions",$msession,"session_id");
                            $m_message.=lang('xxx_subjects_moved_to_session_xxx').'
                                <A HREF="'.thisdoc().'?experiment_id='.
                                    $experiment_id.'&session_id='.$msession.'">'.
                                            session__build_name($tsession).'</A>';
                            $tpartnr=experiment__count_participate_at($experiment_id,$msession);
                            if ($tsession['part_needed'] + $tsession['part_reserve'] < $tpartnr) {
                                $mmessage.=lang('subjects_number_exceeded');
                            }
                        }
                    }
                    $m_message.='</UL>';
                    message($m_message);
                    $target="experiment:".$experiment['experiment_name'];
                    $target.=", experiment_id:".$experiment_id;
                    if ($session_id) {
                        $target.=", session_id:".$session_id;
                    }
                    log__admin("experiment_edit_participant_list",$target);

                    redirect('admin/'.thisdoc().$thiscgis);
                }
            }
        }
    }
}

if ($proceed) {
    // list output

    if ($display=='enrol') {
        $cols=participant__get_result_table_columns('experiment_assigned_list');
    } else {
        $cols=participant__get_result_table_columns('session_participants_list');
    }

    if (!$session_id || !isset($show_payment_budgets) || $show_payment_budgets==false) {
        unset($cols['payment_budget']);
    }
    if (!$session_id || !isset($show_payment_types) || $show_payment_types==false) {
        unset($cols['payment_type']);
    }
    if (!$session_id) {
        unset($cols['payment_amount']);
    }

    // load participant data for this session/experiment
    $pars=array(':texperiment_id'=>$experiment_id);
    $query="SELECT * FROM ".table('participate_at').", ".table('participants')."
                    WHERE ".table('participate_at').".experiment_id= :texperiment_id
                    AND ".table('participate_at').".participant_id=".table('participants').".participant_id
                    AND (".$clause.")";
    foreach ($clause_pars as $p=>$v) {
        $pars[$p]=$v;
    }

    $order=query__get_sort('session_participants_list',$sort);  // sanitize sort or load default if empty
    if ((!$order) || $order=='participant_id') {
        $order=table('participants').".participant_id";
    }
    $query.=" ORDER BY ".$order;

    // get result
    $result=or_query($query,$pars);

    $participants=array();
    $plist_ids=array();
    while ($line=pdo_fetch_assoc($result)) {
        $participants[]=$line;
        $plist_ids[]=$line['participant_id'];
    }
    $result_count=count($participants);
    $_SESSION['plist_ids']=$plist_ids;

    // load sessions of this experiment
    $pars=array(':texperiment_id'=>$experiment_id);
    $squery="SELECT *
            FROM ".table('sessions')."
            WHERE experiment_id= :texperiment_id
            ORDER BY session_start";
    $result=or_query($squery,$pars);
    $thislist_sessions=array();
    while ($line=pdo_fetch_assoc($result)) {
        $thislist_sessions[$line['session_id']]=$line;
    }

    // reorder by session date if ordered by session id
    if ($sort=="session_id") {
        $temp_participants=$participants;
        $participants=array();
        foreach ($thislist_sessions as $sid=>$s) {
            foreach ($temp_participants as $p) {
                if ($p['session_id']==$sid) {
                    $participants[]=$p;
                }
            }
        }
    }
    unset($temp_participants);

    if (check_allow('participants_edit')) {
        echo javascript__edit_popup();
    }

    echo '<div class="orsee-panel">';
    echo '<div class="orsee-panel-title"><div class="orsee-panel-title-main">'.$experiment['experiment_name'];
    if ($session_id) {
        echo ', '.lang('session').' '.session__build_name($session);
    }
    echo ', '.$title.'
            </div>';

    if ($display!='enrol') {
        echo '<div class="orsee-panel-actions"></div>';
    } else {
        echo '<div class="orsee-panel-actions"></div>';
    }
    echo '</div>';
    if ($display!='enrol') {
        echo '<div class="has-text-right" style="margin-top: 0.18rem; margin-bottom: 0.22rem; font-size: var(--font-size-compact);">'.lang('download_as').'
                <A HREF="experiment_participants_show_pdf.php'.
                $thiscgis.'" target="_blank">'.lang('pdf_file').'</A>
                |
                <A HREF="experiment_participants_show_csv.php'.
                $thiscgis.'">'.lang('csv_file').'</A></div>';
    }
    echo '<div>';

    // show query
    //echo '    <P class="small">Query: '.$query.'</P>';

    // form
    echo '
        <FORM name="part_list" method=post action="'.thisdoc().'">
        '.csrf__field().'

        <BR>
        <div class="orsee-table orsee-table-tablet-2rows orsee-table-mobile orsee-table-cells-compact">';
    echo '<div class="orsee-table-row orsee-table-head">';
    echo participant__get_result_table_headcells($cols);
    echo '</div>';

    $shade=false;
    if (check_allow('experiment_edit_participants')) {
        $disabled=false;
    } else {
        $disabled=true;
    }
    $pnr=0;
    foreach ($participants as $p) {
        $pnr++;
        $p['order_number']=$pnr;
        echo '<INPUT type="hidden" name="pid['.$p['participant_id'].']" value="'.$p['participant_id'].'">';
        echo '<div class="orsee-table-row';
        if ($shade) {
            echo ' is-alt';
        }
        echo '">';
        echo participant__get_result_table_row($cols,$p,'select select-compact',$editable_session_columns);
        echo '</div>';
        if ($shade) {
            $shade=false;
        } else {
            $shade=true;
        }
    }
    echo '</div>';

    if (check_allow('experiment_edit_participants')) {
        echo '<div class="orsee-form-actions">';
        if ($display=='enrol') {
            if (!isset($_REQUEST['to_session'])) {
                $_REQUEST['to_session']="";
            }
            if (!isset($_REQUEST['check_if_full'])) {
                $_REQUEST['check_if_full']="true";
            }
            echo '<div class="orsee-surface-card" style="max-width: 42rem; margin: 0 auto 0.55rem auto;"><div style="padding: 0.32rem 0.48rem;">';
            echo '<div class="field" style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.4rem 0.6rem;">';
            echo '<label class="label" style="margin: 0;">'.lang('register_marked_for_session').'</label><div class="control">';
            echo select__sessions($_REQUEST['to_session'],'to_session',$thislist_sessions,false,false,'select select-compact');
            echo '</div></div><div class="field" style="margin-bottom: 0; display: flex; justify-content: flex-end;"><div class="control"><label class="checkbox orsee-checkline"><INPUT type=checkbox name="check_if_full" value="true"';
            if ($_REQUEST['check_if_full']) {
                echo ' CHECKED';
            }
            echo '>'.lang('check_for_free_places_in_session').'</label></div></div></div></div>';
        }
        echo '<div class="orsee-form-actions has-text-centered">
                <INPUT type=hidden name="experiment_id" value="'.$experiment_id.'">';
        if ($session_id) {
            echo '<INPUT type=hidden name="session_id" value="'.$session_id.'">';
        }
        if ($pstatus!='') {
            echo '<INPUT type=hidden name="pstatus" value="'.$pstatus.'">';
        }
        if ($focus) {
            echo '<INPUT type=hidden name="focus" value="'.$focus.'">';
        }
        if ($sort) {
            echo '<INPUT type=hidden name="sort" value="'.$sort.'">';
        }
        echo '<span id="change_button_note" class="orsee-font-compact"><B>&nbsp;<BR></B></span>';
        echo '  <INPUT class="button orsee-btn" type=submit name="change" value="'.lang('change').'">
                </div>';
        echo '</div>';
    }
    echo '</form>';

    if ($session_id) {
        $fields=array();
        $field='';
        $field.='<label class="label">'.lang('set_session').'</label>';
        $field.='<div style="display: flex; align-items: center; gap: 0.35rem; margin-bottom: 0;">';
        $field.='<div class="control">'.select__sessions($session_id,'session_allsel',$thislist_sessions,false,false,'select select-compact').'</div>';
        $field.='<div class="control"><button class="button orsee-btn orsee-btn-compact" style="min-inline-size: 0;" name="session_button" id="session_button">'.lang('button_set').'</button></div>';
        $field.='</div>';
        $fields[]=$field;
        if ($settings['enable_payment_module']=='y' && check_allow('payments_edit')) {
            if ($show_payment_budgets) {
                $field='';
                $field.='<label class="label">'.lang('set_payment_budget').'</label>';
                $field.='<div style="display: flex; align-items: center; gap: 0.35rem; margin-bottom: 0;">';
                $field.='<div class="control">'.payments__budget_selectfield('paybudget_allsel','',array(),$thislist_avail_payment_budgets,'select select-compact').'</div>';
                $field.='<div class="control"><button class="button orsee-btn orsee-btn-compact" style="min-inline-size: 0;" name="budget_button" id="budget_button">'.lang('button_set').'</button></div>';
                $field.='</div>';
                $fields[]=$field;
            }
            if ($show_payment_types) {
                $field='';
                $field.='<label class="label">'.lang('set_payment_type').'</label>';
                $field.='<div style="display: flex; align-items: center; gap: 0.35rem; margin-bottom: 0;">';
                $field.='<div class="control">'.payments__paytype_selectfield('paytype_allsel','',array(),$thislist_avail_payment_types,'select select-compact').'</div>';
                $field.='<div class="control"><button class="button orsee-btn orsee-btn-compact" style="min-inline-size: 0;" name="paytype_button" id="paytype_button">'.lang('button_set').'</button></div>';
                $field.='</div>';
                $fields[]=$field;
            }
            $field='';
            $field.='<label class="label">'.lang('set_payment_amount').'</label>';
            $field.='<div style="display: flex; align-items: center; gap: 0.35rem; margin-bottom: 0;">';
            $field.='<div class="control"><INPUT type="text" name="payamt_allsel" dir="ltr" value="0.00" size="7" maxlength="10" style="text-align:end;"></div>';
            $field.='<div class="control"><button class="button orsee-btn orsee-btn-compact" style="min-inline-size: 0;" name="payamt_button" id="payamt_button">'.lang('button_set').'</button></div>';
            $field.='</div>';
            $fields[]=$field;
        }
        $field='';
        $field.='<label class="label">'.lang('set_participation_status').'</label>';
        $field.='<div style="display: flex; align-items: center; gap: 0.35rem; margin-bottom: 0;">';
        $field.='<div class="control">'.expregister__participation_status_select_field('pstatus_allsel','',array(),true,'select select-compact').'</div>';
        $field.='<div class="control"><button class="button orsee-btn orsee-btn-compact" style="min-inline-size: 0;" name="pstatus_button" id="pstatus_button">'.lang('button_set').'</button></div>';
        $field.='</div>';
        $fields[]=$field;
        foreach ($fields as $k=>$field) {
            $fields[$k]='<div>'.$field.'</div>';
        }

        echo '<div class="orsee-surface-card"><div style="padding: 0.3rem 0.48rem;">';
        echo '<div class="field"><div class="orsee-option-row-comment"><strong>'.lang('for_all_selected_participants').'</strong></div></div>';
        echo '<div style="display: flex; flex-wrap: wrap; gap: 0.5rem 0.9rem; align-items: end;">';
        echo implode('',$fields);
        echo '</div></div></div>';
        $status_colors=expregister__get_pstatus_colors();
        echo '  <script language="JavaScript">
                var status_colors = [];
            ';
        foreach ($status_colors as $k=>$v) {
            echo ' status_colors['.$k.'] = "'.$v.'"; ';
        }
        echo '
                (function() {
                    function qsa(sel, root) {
                        return Array.prototype.slice.call((root || document).querySelectorAll(sel));
                    }
                    function countCheckedRows() {
                        return qsa("input[name*=\'sel[\']:checked").length;
                    }
                    function show_change_note() {
                        var note=document.getElementById("change_button_note");
                        if (note) note.innerHTML="<b><span style=\"color: var(--color-important-note-text);\">Do not forget to save your changes!</span></b><BR>";
                    }
                    function applyStatusColor(selectEl) {
                        if (!selectEl) return;
                        var c=status_colors[selectEl.value];
                        if (c) selectEl.style.background=c;
                    }
                    function nearestRow(el) {
                        return el ? el.closest(".orsee-table-row") : null;
                    }
                    function wireButton(id, sourceSel, targetSel, triggerStatusColor) {
                        var btn=document.getElementById(id);
                        if (!btn) return;
                        btn.addEventListener("click", function(ev) {
                            ev.preventDefault();
                            if (countCheckedRows()<=0) return;
                            var source=document.querySelector(sourceSel);
                            if (!source) return;
                            var myvalue=source.value;
                            qsa("input[name*=\'sel[\']:checked").forEach(function(chk) {
                                var row=nearestRow(chk);
                                if (!row) return;
                                qsa(targetSel,row).forEach(function(target) {
                                    target.value=myvalue;
                                    if (triggerStatusColor) applyStatusColor(target);
                                });
                            });
                            show_change_note();
                        });
                    }

                    qsa("select[name*=\'pstatus_id[\']").forEach(function(sel) {
                        applyStatusColor(sel);
                        sel.addEventListener("change", function() {
                            applyStatusColor(sel);
                        });
                    });

                    wireButton("session_button", "select[name*=\'session_allsel\']", "select[name*=\'session[\']", false);
                    wireButton("budget_button", "select[name*=\'paybudget_allsel\']", "select[name*=\'paybudget[\']", false);
                    wireButton("paytype_button", "select[name*=\'paytype_allsel\']", "select[name*=\'paytype[\']", false);

                    var payamtBtn=document.getElementById("payamt_button");
                    if (payamtBtn) {
                        payamtBtn.addEventListener("click", function(ev) {
                            ev.preventDefault();
                            if (countCheckedRows()<=0) return;
                            var source=document.querySelector("input[name*=\'payamt_allsel\']");
                            if (!source) return;
                            var myvalue=source.value;
                            qsa("input[name*=\'sel[\']:checked").forEach(function(chk) {
                                var row=nearestRow(chk);
                                if (!row) return;
                                qsa("input[name*=\'payamt[\']",row).forEach(function(target) {
                                    target.value=myvalue;
                                });
                            });
                            show_change_note();
                        });
                    }

                    wireButton("pstatus_button", "select[name*=\'pstatus_allsel\']", "select[name*=\'pstatus_id[\']", true);
                })();
                </script>';
    }
    echo '
        <BR>
        <div class="columns is-mobile is-variable is-2">
            <div class="column">';
    if ($session_id && $session['session_status']=="live" && check_allow('session_send_reminder')) {
        if ($session['reminder_sent']=="y") {
            $state=lang('session_reminder_state__sent');
            $statecolor='var(--color-session-reminder-state-sent)';
            $explanation=lang('session_reminder_sent_at_time_specified');
            $send_button_title=lang('session_reminder_send_again');
        } elseif ($session['reminder_checked']=="y" && $session['reminder_sent']=="n") {
            $state=lang('session_reminder_state__checked_but_not_sent');
            $statecolor='var(--color-session-reminder-state-checked)';
            $explanation=lang('session_reminder_not_sent_at_time_specified');
            $send_button_title=lang('session_reminder_send');
        } else {
            $state=lang('session_reminder_state__waiting');
            $statecolor='var(--color-session-reminder-state-waiting)';
            $explanation=lang('session_reminder_will_be_sent_at_time_specified');
            $send_button_title=lang('session_reminder_send_now');
        }
        echo '<FONT color="'.$statecolor.'">'.lang('session_reminder').': '.$state.'</FONT><BR>';
        echo $explanation.'<BR><FORM action="session_send_reminder.php" method="POST">'.csrf__field().
            '<INPUT type=hidden name="session_id" value="'.$session_id.'">'.
            '<INPUT class="button" type=submit name="submit" value="'.$send_button_title.'"></FORM>';
    }
    echo '      </div><div class="column has-text-right">';
    if (check_allow('participants_bulk_mail')) {
        $bulk_focus=isset($_REQUEST['focus']) ? $_REQUEST['focus'] : '';
        $bulk_session_id=$session_id ? $session_id : '';
        experimentmail__bulk_mail_form($experiment_id,$bulk_session_id,$bulk_focus);
    }
    echo '      </div>
        </div>';

    if ($settings['enable_email_module']=='y' && $session_id) {
        $session['experimenter']=$experiment['experimenter'];
        $nums=email__get_privileges('session',$session,'read',true);
        if ($nums['allowed'] && $nums['num_all']>0) {
            echo '<br><br><div class="orsee-panel-title"><div class="orsee-panel-title-main">'.lang('emails').'</div><div class="orsee-panel-actions"></div></div>';
            echo javascript__email_popup();
            email__list_emails('session',$session['session_id'],$nums['rmode'],$thiscgis,false);
        }
    }

    echo '<div class="orsee-form-actions">'.
            button_back('experiment_show.php?experiment_id='.$experiment_id).
         '</div>';
    echo '</div></div>';
}
include("footer.php");

?>
