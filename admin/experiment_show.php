<?php
// part of orsee. see orsee.org
ob_start();
$title="experiment";
$menu__area="experiments_main";
include("header.php");

if ($proceed) {
    if (!$_REQUEST['experiment_id']) {
        redirect("admin/");
    } else {
        $experiment_id=$_REQUEST['experiment_id'];
    }
}

if ($proceed) {
    $allow=check_allow('experiment_show','experiment_main.php');
}

if ($proceed) {
    // load experiment data into array experiment
    $experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
    if (!is_array($experiment) || !isset($experiment['experiment_id'])) {
        redirect("admin/experiment_main.php");
    }
    if (!check_allow('experiment_restriction_override')) {
        check_experiment_allowed($experiment,"admin/experiment_main.php");
    }
}

if ($proceed) {
    // check if we are supposed to deactivate a permanent query
    if ($settings['allow_permanent_queries']=='y' && check_allow('experiment_assign_query_permanent_deactivate')
            && isset($_REQUEST['permanent_deactivate']) && $_REQUEST['permanent_deactivate']) {
        if (!csrf__validate_request_message()) {
            redirect('admin/experiment_show.php?experiment_id='.$experiment_id);
        }
        $done=query__reset_permanent($experiment_id);
        redirect('admin/experiment_show.php?experiment_id='.$experiment_id);
    }
}

if ($proceed) {
    // change session status if requested
    if (isset($_REQUEST['bulk_set_session_status']) && $_REQUEST['bulk_set_session_status'] && isset($_REQUEST['session_status'])
        && isset($_REQUEST['sel']) && is_array($_REQUEST['sel']) && count($_REQUEST['sel'])>0
        && in_array($_REQUEST['session_status'],array('planned','live','completed','balanced'))) {
        if (!csrf__validate_request_message()) {
            redirect('admin/experiment_show.php?experiment_id='.$experiment_id);
        }
        $pars=array();
        foreach ($_REQUEST['sel'] as $k=>$v) {
            $pars[]=array(':session_id'=>$k,':session_status'=>$_REQUEST['session_status'],':experiment_id'=>$experiment_id);
        }
        $query="UPDATE ".table('sessions')."
                SET session_status= :session_status
                WHERE experiment_id= :experiment_id
                AND session_id= :session_id";
        $done=or_query($query,$pars);
        message(lang('bulk_updated_session_statuses'));
        redirect('admin/experiment_show.php?experiment_id='.$experiment_id);
    }
}

if ($proceed) {
    $experiment_payments_by_type=array();
    // load sessions if lab experiment
    $sessions=array();
    if ($experiment['experiment_type']=="laboratory") {
        $pars=array(':experiment_id'=>$experiment['experiment_id']);
        $query="SELECT *
                FROM ".table('sessions')."
                WHERE experiment_id= :experiment_id
                ORDER BY session_start";
        $result=or_query($query,$pars);
        $min=0;
        $max=0;
        $sids=array();
        while ($s=pdo_fetch_assoc($result)) {
            $s['regcount']=0;
            $s['total_payment']=0;
            $s['payments_by_type']=array();
            $sessions[$s['session_id']]=$s;
            $sesstime=$s['session_start'];
            if ($min==0) {
                $min=$sesstime;
                $max=$sesstime;
            } else {
                if ($sesstime < $min) {
                    $min=$sesstime;
                }
                if ($sesstime > $max) {
                    $max=$sesstime;
                }
            }
            $sids[]=$s['session_id'];
        }
        if (count($sids)>0) {
            $query="SELECT session_id,
                    COUNT(*) as regcount ";
            $query.=" FROM ".table('participate_at')."
                    WHERE session_id IN (".
                    implode(",",$sids).")
                    GROUP BY session_id";
            $result=or_query($query);
            while ($s=pdo_fetch_assoc($result)) {
                $sessions[$s['session_id']]['regcount']=$s['regcount'];
            }
            if ($settings['enable_payment_module']=="y" && check_allow('payments_view')) {
                $query="SELECT session_id, payment_type, SUM(payment_amt) as total_payment
                        FROM ".table('participate_at')."
                        WHERE session_id IN (".
                        implode(",",$sids).")
                        GROUP BY session_id, payment_type";
                $result=or_query($query);
                while ($s=pdo_fetch_assoc($result)) {
                    $sessions[$s['session_id']]['payments_by_type'][$s['payment_type']]=$s['total_payment'];
                    if (!isset($experiment_payments_by_type[$s['payment_type']])) {
                        $experiment_payments_by_type[$s['payment_type']]=0;
                    }
                    $experiment_payments_by_type[$s['payment_type']]+=$s['total_payment'];
                }
            }
        }
    }

    $exptypes=load_external_experiment_types();
    if (!isset($lang[$experiment['experiment_type']])) {
        $lang[$experiment['experiment_type']]=$experiment['experiment_type'];
    }

    show_message();

    $experimenters=db_string_to_id_array($experiment['experimenter']);
    $header_buttons=array();
    if (check_allow('experiment_edit')) {
        $header_buttons[]=button_link('experiment_edit.php?experiment_id='.$experiment['experiment_id'], lang('edit_basic_data'),'pencil-square-o');
    }
    if (check_allow('file_view_experiment_all')
        || (in_array($expadmindata['admin_id'],$experimenters) && check_allow('file_view_experiment_my'))) {
        $header_buttons[]=button_link('download_main.php?experiment_id='.$experiment['experiment_id'], lang('show_files'),'download');
    }

    $exp_type_name=(isset($exptypes[$experiment['experiment_ext_type']]['exptype_name']) ? $exptypes[$experiment['experiment_ext_type']]['exptype_name'] : 'type undefined');
    $fact_items=array();
    $fact_items[]=array('label'=>lang('type'),'value'=>$lang[$experiment['experiment_type']].' ('.$exp_type_name.')');
    $fact_items[]=array('label'=>lang('class'),'value'=>experiment__experiment_class_field_to_list($experiment['experiment_class']));
    $fact_items[]=array('label'=>lang('name'),'value'=>$experiment['experiment_name']);
    $fact_items[]=array('label'=>lang('public_name'),'value'=>$experiment['experiment_public_name']);
    $fact_items[]=array('label'=>lang('experimenter'),'value'=>experiment__list_experimenters($experiment['experimenter'],true,true));
    $fact_items[]=array('label'=>lang('get_emails'),'value'=>experiment__list_experimenters($experiment['experimenter_mail'],true,true));

    $ethics=false;
    if ($settings['enable_ethics_approval_module']=='y') {
        if (!isset($max)) {
            $max=-1;
        }
        $ethics=experiment__get_ethics_approval_desc($experiment,$max);
    }

    if ($settings['enable_payment_module']=="y" && check_allow('payments_view')) {
        if (count($experiment_payments_by_type)>0) {
            $payment_types=payments__load_paytypes();
            $payment_lines=array();
            foreach ($experiment_payments_by_type as $paytype=>$payamount) {
                if (isset($payment_types[$paytype])) {
                    $paytype_name=$payment_types[$paytype];
                } else {
                    $paytype_name=lang('unknown');
                }
                $payment_lines[]=$paytype_name.': '.or__format_number($payamount,2);
            }
            $payment_value=implode('<br>',$payment_lines);
        } else {
            $payment_value='-';
        }
    } else {
        $payment_value='-';
    }
    $ethics_value=($ethics ? $ethics['text'] : '-');
    $ethics_style=($ethics && isset($ethics['color']) && $ethics['color'] ? 'background: '.$ethics['color'].';' : '');
    $fact_items[]=array('label'=>lang('total_payment'),'value'=>$payment_value);
    $fact_items[]=array('value'=>$ethics_value,'nolabel'=>true,'value_style'=>$ethics_style,'value_class'=>'orsee-fact-value--ethics');

    if ($experiment['experiment_finished']=="y") {
        $experiment_status_class='orsee-panel--experiment-completed';
        $experiment_status_text=lang('experiment_finished');
    } else {
        $experiment_status_class='orsee-panel--experiment-running';
        $experiment_status_text=lang('experiment_not_finished');
    }

    echo '<div class="orsee-panel '.$experiment_status_class.'">';
    echo '<div class="orsee-panel-title">';
    echo '<div class="orsee-panel-title-main">'.lang('experiment').': '.$experiment['experiment_name'].'</div>';
    echo '<div class="orsee-panel-actions">';
    foreach ($header_buttons as $button) {
        echo $button;
    }
    echo '</div>';
    echo '</div>';

    $experiment_status_tag_class=($experiment['experiment_finished']=="y" ? 'orsee-experiment-status-tag--completed' : 'orsee-experiment-status-tag--running');
    echo '<div class="orsee-dense-id"><span class="orsee-dense-id-tag">'.lang('id').': '.$experiment['experiment_id'].'</span> <span class="orsee-experiment-status-tag '.$experiment_status_tag_class.'" title="'.htmlspecialchars($experiment_status_text).'">'.$experiment_status_text.'</span></div>';

    echo '<div class="orsee-facts-grid">';
    foreach ($fact_items as $item) {
        $wide=(isset($item['wide']) && $item['wide'] ? ' orsee-fact--wide' : '');
        echo '<div class="orsee-fact'.$wide.'">';
        if (isset($item['nolabel']) && $item['nolabel']) {
            $vstyle=(isset($item['value_style']) && $item['value_style'] ? ' style="'.$item['value_style'].'"' : '');
            $vclass=(isset($item['value_class']) && $item['value_class'] ? ' '.$item['value_class'] : '');
            echo '<div class="orsee-fact-value orsee-fact-value-standalone'.$vclass.'"'.$vstyle.'>'.$item['value'].'</div>';
        } else {
            echo '<div class="orsee-fact-label">'.$item['label'].':</div>';
            echo '<div class="orsee-fact-value">'.$item['value'].'</div>';
        }
        echo '</div>';
    }
    echo '</div>';

    echo '</div>';

    if ($experiment['experiment_type']=="laboratory") {
        echo '<div class="orsee-panel '.$experiment_status_class.'">';
        echo '<form action="'.thisdoc().'" method="POST">';
        echo csrf__field();
        echo '<input type="hidden" name="experiment_id" value="'.$experiment_id.'">';

        echo '<div class="orsee-panel-title">';
        echo '<div class="orsee-panel-title-main">'.lang('sessions');
        if ($min>0) {
            echo ' '.lang('from').': '.ortime__format(ortime__sesstime_to_unixtime($min),'hide_time').' '.lang('to').': '.ortime__format(ortime__sesstime_to_unixtime($max),'hide_time');
        }
        echo '</div>';
        echo '<div class="orsee-panel-actions">';
        if (check_allow('session_edit')) {
            echo button_link('session_edit.php?experiment_id='.$experiment['experiment_id'], lang('create_new'),'plus-circle');
        }
        echo '</div>';
        echo '</div>';

        echo '<div class="orsee-session-summary">'.count($sessions).' '.lang('xxx_sessions_registered').' &middot; '.lang('select_all').' '.javascript__selectall_checkbox_script().'</div>';

        echo '<div class="orsee-dense-list">';
        foreach ($sessions as $s) {
            sessions__format_alist($s,$experiment);
        }
        echo '</div>';

        echo '<div class="orsee-session-bulk-actions">';
        echo '<span>'.lang('set_session_status_for_selected_sessions_to').'</span> ';
        echo session__session_status_select('session_status',-1).' ';
        echo '<input class="button orsee-btn" type="submit" name="bulk_set_session_status" value="'.lang('button_set').'">';
        echo '</div>';

        echo '</form>';
        echo '</div>';
    }

    if ($experiment['experiment_type']=="laboratory"  || $experiment['experiment_type']=="internet") {
        $allow_sp=check_allow('experiment_show_participants');
        $counts=experiment__count_pstatus($experiment['experiment_id']);

        echo '<div class="orsee-panel '.$experiment_status_class.'">';
        echo '<div class="orsee-panel-title"><div class="orsee-panel-title-main">'.lang('participants').'</div><div class="orsee-panel-actions"></div></div>';

        echo '<div class="orsee-panel-split">';
        echo '<div class="orsee-panel-split-main">';
        echo '<div class="orsee-stat-list">';
        echo '<div class="orsee-stat-row"><div class="orsee-stat-label">';
        if ($allow_sp) {
            echo '<a href="experiment_participants_show.php?experiment_id='.$experiment['experiment_id'].'&focus=assigned">';
        }
        echo lang('assigned_subjects');
        if ($allow_sp) {
            echo '</a>';
        }
        echo '</div><div class="orsee-stat-value">'.$counts['assigned'].'</div></div>';

        echo '<div class="orsee-stat-row"><div class="orsee-stat-label">';
        if ($allow_sp) {
            echo '<a href="experiment_participants_show.php?experiment_id='.$experiment['experiment_id'].'&focus=invited">';
        }
        echo lang('invited_subjects');
        if ($allow_sp) {
            echo '</a>';
        }
        echo '</div><div class="orsee-stat-value">'.experiment__count_participate_at($experiment_id,"","invited=1").'</div></div>';

        echo '<div class="orsee-stat-row"><div class="orsee-stat-label">';
        if ($allow_sp) {
            echo '<a href="experiment_participants_show.php?experiment_id='.$experiment['experiment_id'].'&focus=enroled">';
        }
        echo lang('registered_subjects');
        if ($allow_sp) {
            echo '</a>';
        }
        echo '</div><div class="orsee-stat-value">'.$counts['enroled'].'</div></div>';

        if ($counts['enroled']>0) {
            foreach ($counts['pstatus'] as $k=>$psarr) {
                echo '<div class="orsee-stat-row orsee-stat-row-compact"><div class="orsee-stat-label">';
                if ($allow_sp) {
                    echo '<a href="experiment_participants_show.php?experiment_id='.$experiment['experiment_id'].'&pstatus='.$k.'">';
                }
                echo $psarr['internal_name'];
                if ($allow_sp) {
                    echo '</a>';
                }
                echo '</div><div class="orsee-stat-value">'.$psarr['count'].'</div></div>';
            }
        }
        echo '</div>';
        echo '</div>';

        $buttons=array();
        if (check_allow('experiment_assign_participants')) {
            $buttons[]=button_link('experiment_add_participants.php?experiment_id='.$experiment['experiment_id'],lang('assign_subjects'),'plus-square');
            $buttons[]=button_link('experiment_drop_participants.php?experiment_id='.$experiment['experiment_id'],lang('delete_assigned_subjects'));
        }
        if (check_allow('experiment_invitation_edit')) {
            $buttons[]=button_link('experiment_mail_participants.php?experiment_id='.$experiment['experiment_id'],lang('send_invitations'),'envelope');
        }
        if (check_allow('mailqueue_show_experiment')) {
            $buttons[]=button_link('experiment_mailqueue_show.php?experiment_id='.$experiment['experiment_id'],lang('monitor_experiment_mail_queue'),'envelope-square');
        }
        if (check_allow('experiment_customize_session_reminder') && $settings['enable_session_reminder_customization']=='y') {
            $buttons[]=button_link('experiment_customize_reminder.php?experiment_id='.$experiment['experiment_id'],lang('customize_session_reminder_email'),'envelope-o');
        }
        if (check_allow('experiment_customize_enrolment_confirmation') && $settings['enable_enrolment_confirmation_customization']=='y') {
            $buttons[]=button_link('experiment_customize_enrol_conf.php?experiment_id='.$experiment['experiment_id'],lang('customize_enrolment_confirmation_email'),'envelope-o');
        }
        if ($settings['enable_email_module']=='y') {
            $nums=email__get_privileges('experiment',$experiment,'read',true);
            if ($nums['allowed'] && $nums['num_all']>0) {
                $btext=lang('view_emails_for_experiment').' ['.$nums['num_all'];
                if ($nums['num_new']) {
                    $btext.=' ('.$nums['num_new'].')';
                }
                $btext.=']';
                $buttons[]=button_link('emails_main.php?mode=experiment&id='.$experiment['experiment_id'],$btext,'envelope-square');
            }
        }
        if (check_allow('experiment_recruitment_report_show')) {
            $buttons[]=button_link('experiment_recruitment_report.php?experiment_id='.$experiment['experiment_id'],lang('generate_recruitment_report'),'list-alt');
        }

        if (($settings['allow_permanent_queries']=='y') || count($buttons)>0) {
            echo '<div class="orsee-panel-split-actions">';
            if ($settings['allow_permanent_queries']=='y') {
                $perm_queries=query__get_permanent($experiment_id);
                if (count($perm_queries)>0) {
                    echo '<div class="orsee-permanent-query-block">';
                    echo '<div class="orsee-permanent-query-title"><strong>'.lang('found_active_permanent_query').'</strong></div>';
                    foreach ($perm_queries as $pquery) {
                        $posted_query=json_decode($pquery['json_query'],true);
                        $pseudo_query_array=query__get_pseudo_query_array($posted_query['query']);
                        $pseudo_query_display=query__display_pseudo_query($pseudo_query_array,false);
                        echo '<div class="orsee-permanent-query-row">';
                        echo '<div class="orsee-permanent-query-text">'.$pseudo_query_display.'</div>';
                        echo '<div class="orsee-permanent-query-action">';
                        if (check_allow('experiment_assign_query_permanent_deactivate')) {
                            echo button_link(thisdoc().'?experiment_id='.$experiment_id.'&permanent_deactivate=true&csrf_token='.urlencode(csrf__get_token()), lang('deactivate_permanent_query'),'toggle-off');
                        }
                        echo '</div></div>';
                    }
                    echo '</div>';
                }
            }
            if (count($buttons)>0) {
                echo '<div class="orsee-action-grid">';
                foreach ($buttons as $button) {
                    echo '<div class="orsee-action-grid-item">'.$button.'</div>';
                }
                echo '</div>';
            }
            echo '</div>';
        }
        echo '</div>';

        echo '</div>';
    }
}
include("footer.php");

?>
