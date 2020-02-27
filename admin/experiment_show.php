<?php
// part of orsee. see orsee.org
ob_start();
$jquery=array();
$title="experiment";
$menu__area="experiments_main";
include ("header.php");
if ($proceed) {
    if (!$_REQUEST['experiment_id']) redirect ("admin/");
    else $experiment_id=$_REQUEST['experiment_id'];
}

if ($proceed) {
    $allow=check_allow('experiment_show','experiment_main.php');
}

if ($proceed) {
    // load experiment data into array experiment
    $experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
    if (!check_allow('experiment_restriction_override'))
        check_experiment_allowed($experiment,"admin/experiment_main.php");
}

if ($proceed) {
    // check if we are supposed to deactivate a permanent query
    if ($settings['allow_permanent_queries']=='y' && check_allow('experiment_assign_query_permanent_deactivate')
            && isset($_REQUEST['permanent_deactivate']) && $_REQUEST['permanent_deactivate']) {
        $done=query__reset_permanent($experiment_id);
        redirect('admin/experiment_show.php?experiment_id='.$experiment_id);
    }
}


if ($proceed) {
    // change session status if requested
    if (isset($_REQUEST['bulk_set_session_status']) && $_REQUEST['bulk_set_session_status'] && isset($_REQUEST['session_status']) 
        && isset($_REQUEST['sel']) && is_array($_REQUEST['sel']) && count($_REQUEST['sel'])>0 
        && in_array($_REQUEST['session_status'],array('planned','live','completed','balanced')) ) {
        $pars=array();
        foreach($_REQUEST['sel'] as $k=>$v) {
            $pars[]=array(':session_id'=>$k,':session_status'=>$_REQUEST['session_status'],':experiment_id'=>$experiment_id);
        }
        $query="UPDATE ".table('sessions')."
                SET session_status= :session_status
                WHERE experiment_id= :experiment_id
                AND session_id= :session_id";
        $done=or_query($query,$pars);
        message (lang('bulk_updated_session_statuses'));
        redirect('admin/experiment_show.php?experiment_id='.$experiment_id);
    }
}

if ($proceed) {
    $experiment_total_payment=0;
    // load sessions if lab experiment
    $sessions=array();
    if ($experiment['experiment_type']=="laboratory") {
        $pars=array(':experiment_id'=>$experiment['experiment_id']);
        $query="SELECT *
                FROM ".table('sessions')."
                WHERE experiment_id= :experiment_id
                ORDER BY session_start";
        $result=or_query($query,$pars); $min=0; $max=0; $sids=array();
        while ($s=pdo_fetch_assoc($result)) {
            $s['regcount']=0;
            $s['total_payment']=0;
            $sessions[$s['session_id']]=$s;
            $sesstime=$s['session_start'];
            if ($min==0) {
                $min=$sesstime; $max=$sesstime;
            } else {
                if ($sesstime < $min) $min=$sesstime;
                if ($sesstime > $max) $max=$sesstime;
            }
            $sids[]=$s['session_id'];
        }
        if (count($sids)>0) {
            $query="SELECT session_id,
                    COUNT(*) as regcount ";
            if ($settings['enable_payment_module']=="y" && check_allow('payments_view')) {
                $query.=", SUM(payment_amt) as total_payment ";
            }
            $query.=" FROM ".table('participate_at')."
                    WHERE session_id IN (".
                    implode(",",$sids).")
                    GROUP BY session_id";
            $result=or_query($query);
            while ($s=pdo_fetch_assoc($result)) {
                $sessions[$s['session_id']]['regcount']=$s['regcount'];
                if ($settings['enable_payment_module']=="y" && check_allow('payments_view')) {
                    $sessions[$s['session_id']]['total_payment']=$s['total_payment'];
                    $experiment_total_payment+=$s['total_payment'];
                }
            }
        }
    }

    $exptypes=load_external_experiment_types();

    echo '<center>';

    show_message();

    echo '<TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'">
            <TR><TD align="center">
            '.$experiment['experiment_name'].'
            </TD>';
    echo '</TR></TABLE>';


// show basic settings
    if(!isset($lang[$experiment['experiment_type']])) $lang[$experiment['experiment_type']]=$experiment['experiment_type'];
    echo '<BR>
    <table class="or_panel">';

    // EXPERIMENT OPTIONS
    echo '<TR>
            <TD colspan=2>
                <TABLE width="100%" border=0 class="or_panel_title">
                    <TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">'.lang('basic_data').'</TD>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">';
    if (check_allow('experiment_edit')) echo button_link('experiment_edit.php?experiment_id='.
                            $experiment['experiment_id'], lang('edit_basic_data'),'pencil-square-o');
    echo '              </TD><TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">';
    $experimenters=db_string_to_id_array($experiment['experimenter']);
    if ( check_allow('file_upload_experiment_all')
        || (in_array($expadmindata['admin_id'],$experimenters) && check_allow('file_upload_experiment_my'))) {
        echo button_link('download_upload.php?experiment_id='.
            $experiment['experiment_id'], lang('upload_file'),'upload');
    }
    if ( check_allow('file_view_experiment_all')
        || (in_array($expadmindata['admin_id'],$experimenters) && check_allow('file_view_experiment_my'))) {
        $files_downloadable=downloads__list_files_experiment($experiment['experiment_id']);
        if ($files_downloadable) {
            echo button_link('download_main.php?experiment_id='.
                    $experiment['experiment_id'], lang('show_files'),'download');
        }

    }


    echo '              </TD>
                    </TR>
                </TABLE>
            </TD>
        </TR>';

    // COMMON PARAMETERS
    echo '
        <TR>
            <TD colspan=2>
                <TABLE width="100%">
                    <TR>
                        <TD>'.lang('id').':</TD><TD>'.$experiment['experiment_id'].'</TD>
                        <TD>'.lang('type').':</TD>';
    if (!isset($exptypes[$experiment['experiment_ext_type']]['exptype_name']))
            $exptypes[$experiment['experiment_ext_type']]['exptype_name']='type undefined';
    echo '<TD>'.$lang[$experiment['experiment_type']].' ('.$exptypes[$experiment['experiment_ext_type']]['exptype_name'].')</TD>
                    </TR>
                    <TR>
                        <TD>'.lang('name').':</TD><TD>'.$experiment['experiment_name'].'</TD>
                        <TD>'.lang('public_name').':</TD><TD>'.$experiment['experiment_public_name'].'</TD>
                    </TR>';
    echo '
                    <TR>
                        <TD>'.lang('class').':</TD>
                        <TD>'.experiment__experiment_class_field_to_list($experiment['experiment_class']).'</TD>
                        <TD>'.lang('description').':</TD><TD>'.$experiment['experiment_description'].'</TD>
                    </TR>
                    <TR>
                        <TD>'.lang('experimenter').':</TD><TD>'.experiment__list_experimenters($experiment['experimenter'],true,true).'</TD>
                        <TD>'.lang('get_emails').':</TD><TD>'.experiment__list_experimenters($experiment['experimenter_mail'],true,true).'</TD>
                    </TR>';


    // CONDITIONAL EXPERIMENT FIELDS
    $conditional_fields=array();
    if ($settings['enable_editing_of_experiment_sender_email']=='y')
        $conditional_fields[]='<TD>'.lang('email_sender_address').':</TD><TD>'.$experiment['sender_mail'].'</TD>';

    if ($settings['enable_payment_module']=="y" && check_allow('payments_view')) {
            $conditional_fields[]='<TD>'.lang('total_payment').':</TD><TD>'.or__format_number($experiment_total_payment,2).'</TD>';
    }

    if (trim($experiment['experiment_link_to_paper'])) {
            $conditional_fields[]='<TD colspan=2><A target="_blank" HREF="'.trim($edit['experiment_link_to_paper']).'">'.lang('Link to paper').'</A></TD>';
    }

    $i=0;
    foreach ($conditional_fields as $condfield) {
        if ($i/2 == round($i/2)) {
            echo '<TR>';
            echo $condfield;
            if (isset($conditional_fields[$i+1])) echo $conditional_fields[$i+1];
            else echo '<TD></TD>';
            echo '</TR>';
        }
        $i++;
    }

    // ETHICS APPROVAL - IF ENABLED
    if ($settings['enable_ethics_approval_module']=='y') {
        if (!isset($max)) $max=-1;
        $ethics=experiment__get_ethics_approval_desc($experiment,$max);
        echo '<TR bgcolor="'.$ethics['color'].'"><TD colspan="4">'.$ethics['text'].'</TD></TR>';
    }

    if ($experiment['experiment_type']=="laboratory") {
        echo '<TR><TD colspan=4><B>';
        if ($experiment['experiment_finished']=="y")
            echo lang('experiment_finished');
           else echo lang('experiment_not_finished');
        echo '</B></TD></TR>';
    }

    echo '</TABLE></TD></TR>';

    echo '
    </TABLE>
    </center><BR><BR>';



    if ($experiment['experiment_type']=="laboratory") {
    // session summary

    echo '<center>
        <BR>
        <FORM action="'.thisdoc().'" method="POST">
        <INPUT type=hidden name="experiment_id" value="'.$experiment_id.'">
        <table class="or_panel">
        <TR>
            <TD>
                <TABLE width="100%" border=0 class="or_panel_title"><TR>
                <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">'.lang('sessions').'</TD>';
    if ($min>0) {
                echo '<TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">'.lang('from').': '.ortime__format(ortime__sesstime_to_unixtime($min),'hide_time').'
                        '.lang('to').': '.ortime__format(ortime__sesstime_to_unixtime($max),'hide_time').'
                        </TD>';
    }
    echo '      <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">';
                if (check_allow('session_edit')) echo button_link('session_edit.php?experiment_id='.
                        $experiment['experiment_id'], lang('create_new'),'plus-circle');
    echo '      </TD>
                </TR></TABLE>
            </TD>
        </TR>
        <TR>
            <TD>
                '.count($sessions).' '.
                lang('xxx_sessions_registered').'<BR>
            </TD>
        </TR>
        <TR>
            <TD align="left">
                '.lang('select_all').' '.javascript__selectall_checkbox_script().'
            </TD>
        </TR>

        <TR>
            <TD>

            <TABLE border=0 width=100% style="border-spacing: 0px; border-collapse: separate;">';

            foreach ($sessions as $s) sessions__format_alist($s,$experiment);

    echo '      </TABLE>

            </TD>
        </TR>
        <TR>
            <TD>
            <TABLE class="or_option_buttons_box" style="background: '.$color['options_box_background'].';">
                <TR>
                    <TD>
                        '.lang('set_session_status_for_selected_sessions_to').' '.session__session_status_select('session_status',-1).'
                        <input class="button" type="submit" name="bulk_set_session_status" value="'.lang('button_set').'">
                    </TD>
                </TR>
            </TABLE>
        </TR>
        </TABLE>
        </FORM>
        </center><BR><BR>';

}



// participant summary for laboratory experiments
    if ($experiment['experiment_type']=="laboratory"  || $experiment['experiment_type']=="internet") {

        $allow_sp=check_allow('experiment_show_participants'); // show links to participant lists?

        // get the numbers per status
        $counts=experiment__count_pstatus($experiment['experiment_id']);

        echo '  <center>
            <BR>
            <table class="or_panel">
            <TR>
                <TD colspan=3>
                    <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">
                            '.lang('participants').'
                        </TD>
                    </TR></TABLE>
                </TD>
            </TR>
            <TR>
                <TD colspan=2>';
                    if ($allow_sp) echo '<A HREF="experiment_participants_show.php?experiment_id='.
                                $experiment['experiment_id'].'&focus=assigned">';
                    echo lang('assigned_subjects');
                    if ($allow_sp) echo '</A>';
                    echo ':
                </TD>
                <TD>
                    '.$counts['assigned'].'
                </TD>
            </TR>
            <TR>
                <TD colspan=2>';
                    if ($allow_sp) echo '<A HREF="experiment_participants_show.php?experiment_id='.
                                $experiment['experiment_id'].'&focus=invited">';
                    echo lang('invited_subjects');
                    if ($allow_sp) echo '</A>';
                    echo ':
                </TD>
                <TD>
                    '.experiment__count_participate_at($experiment_id,"","invited=1").'
                </TD>
            </TR>
            <TR>
                <TD colspan=2>';
                    if ($allow_sp) echo '<A HREF="experiment_participants_show.php?experiment_id='.
                                $experiment['experiment_id'].'&focus=enroled">';
                    echo lang('registered_subjects');
                    if ($allow_sp) echo '</A>';
                    echo ':
                </TD>
                <TD>
                    '.$counts['enroled'].'
                </TD>
            </TR>';
            if ($counts['enroled']>0) { foreach ($counts['pstatus'] as $k=>$psarr) {
                echo '  <TR>
                            <TD>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</TD>
                            <TD>';
                            if ($allow_sp) echo '<A HREF="experiment_participants_show.php?experiment_id='.
                                    $experiment['experiment_id'].'&pstatus='.$k.'">';
                            echo $psarr['internal_name'];
                            if ($allow_sp) echo '</A>';
                            echo '</TD>
                            <TD>
                                '.$psarr['count'].'
                            </TD>
                        </TR>';
            }}

        if ($settings['allow_permanent_queries']=='y') {
            $perm_queries=query__get_permanent($experiment_id);
            if (count($perm_queries)>0) {
                echo '<TR><TD colspan=3><B>'.lang('found_active_permanent_query').'</B></TD></TR>';
                echo '<TR><TD colspan=3><TABLE width="100%" border="0">';
                foreach($perm_queries as $pquery) {
                    $posted_query=json_decode($pquery['json_query'],true);
                    $pseudo_query_array=query__get_pseudo_query_array($posted_query['query']);
                    $pseudo_query_display=query__display_pseudo_query($pseudo_query_array,false);
                    echo '<TR><TD>'.$pseudo_query_display.'</TD><TD>';
                    if (check_allow('experiment_assign_query_permanent_deactivate')) {
                        echo button_link(thisdoc().'?experiment_id='.$experiment_id.'&permanent_deactivate=true',
                                    lang('deactivate_permanent_query'),'toggle-off');
                    }
                    echo '</TD></TR>';
                }
                '</TABLE></TD></TR>';
            }
        }

        echo '          <TR><TD colspan=3>
                <TABLE class="or_option_buttons_box" style="background: '.$color['options_box_background'].';">';



        $buttons=array();
        if (check_allow('experiment_assign_participants')) {
            $buttons[]=button_link('experiment_add_participants.php?experiment_id='.
                    $experiment['experiment_id'],lang('assign_subjects'),'plus-square');
            $buttons[]=button_link('experiment_drop_participants.php?experiment_id='.
                    $experiment['experiment_id'],lang('delete_assigned_subjects'));
        }

        if (check_allow('experiment_invitation_edit'))
            $buttons[]=button_link('experiment_mail_participants.php?experiment_id='.
                    $experiment['experiment_id'],lang('send_invitations'),'envelope');

        if (check_allow('mailqueue_show_experiment'))
            $buttons[]=button_link('experiment_mailqueue_show.php?experiment_id='.
                    $experiment['experiment_id'],lang('monitor_experiment_mail_queue'),'envelope-square');

        if (check_allow('experiment_customize_session_reminder') && $settings['enable_session_reminder_customization']=='y')
            $buttons[]=button_link('experiment_customize_reminder.php?experiment_id='.
                    $experiment['experiment_id'],lang('customize_session_reminder_email'),'envelope-o');

        if (check_allow('experiment_customize_enrolment_confirmation') && $settings['enable_enrolment_confirmation_customization']=='y')
            $buttons[]=button_link('experiment_customize_enrol_conf.php?experiment_id='.
                    $experiment['experiment_id'],lang('customize_enrolment_confirmation_email'),'envelope-o');

        if ($settings['enable_email_module']=='y') {
            $nums=email__get_privileges('experiment',$experiment,'read',true);
            if ($nums['allowed'] && $nums['num_all']>0) {
                $btext=lang('view_emails_for_experiment').' ['.$nums['num_all'];
                if ($nums['num_new']) $btext.=' <font color="'.$color['email__new_emails_textcolor'].'">('.$nums['num_new'].')</font>';
                $btext.=']';
                $buttons[]=button_link('emails_main.php?mode=experiment&id='.
                        $experiment['experiment_id'],$btext,'envelope-square');
            }
        }

        if (check_allow('experiment_recruitment_report_show'))
            $buttons[].=button_link('experiment_recruitment_report.php?experiment_id='.
                        $experiment['experiment_id'],lang('generate_recruitment_report'),'list-alt');

        foreach ($buttons as $k=>$button) {
            if (($k % 2)==0) {
                echo '<TR><TD>'.$button.'</TD><TD>';
                if (isset($buttons[$k+1])) echo $buttons[$k+1];
                echo '</TD></TR>';
            }
        }

        echo '
                </TABLE></TD>
            </TR>
            </TABLE>
            </center>';
        }

}
include ("footer.php");
?>
