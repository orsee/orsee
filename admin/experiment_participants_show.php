<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="experiments";
$title="participants";
$jquery=array('popup');
include("header.php");
if ($proceed) {

    if (isset($_REQUEST['experiment_id']) && $_REQUEST['experiment_id']) $experiment_id=$_REQUEST['experiment_id'];
    else redirect("admin/experiment_main.php");
}

if ($proceed) {
    if (isset($_REQUEST['session_id']) && $_REQUEST['session_id']) $session_id=$_REQUEST['session_id'];
    else $session_id='';

    if (isset($_REQUEST['pstatus'])) $pstatus=$_REQUEST['pstatus']; else $pstatus='';

    if (isset($_REQUEST['focus']) && $_REQUEST['focus']) $focus=$_REQUEST['focus']; else $focus='';

    if (isset($_REQUEST['search_sort']) && $_REQUEST['search_sort']) $sort=$_REQUEST['search_sort']; else $sort='';

    $thiscgis='?experiment_id='.$experiment_id;
    if ($session_id) $thiscgis.='&session_id='.$session_id;
    if ($pstatus!='') $thiscgis.='&pstatus='.$pstatus;
    if ($focus) $thiscgis.='&focus='.$focus;
    if ($sort) $thiscgis.='&search_sort='.$sort;

    $allow=check_allow('experiment_show_participants','experiment_show.php?experiment_id='.$experiment_id);
    if ($proceed) {
        $experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
        if (!check_allow('experiment_restriction_override'))
            check_experiment_allowed($experiment,"admin/experiment_show.php?experiment_id=".$experiment_id);
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
            if ($pstatus==0) $clause.=" AND session_id != 0";
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
            if (!isset($session['session_id'])) redirect("admin/experiment_show.php?experiment_id=".$experiment_id);
        }
    }

    if ($proceed) {

        if ($session_id && $settings['enable_payment_module']=='y' &&
                (check_allow('payments_view') || check_allow('payments_edit'))) {
            $thislist_avail_payment_types=db_string_to_id_array($session['payment_types']);
            if (is_array($thislist_avail_payment_types) && count($thislist_avail_payment_types)>1) $show_payment_types=true;
            else {
                $show_payment_types=false;
                $default_payment_type=payments__get_default_paytype($experiment,$session);
            }
            $thislist_avail_payment_budgets=db_string_to_id_array($session['payment_budgets']);
            if (is_array($thislist_avail_payment_budgets) && count($thislist_avail_payment_budgets)>1) $show_payment_budgets=true;
            else {
                $show_payment_budgets=false;
                $default_payment_budget=payments__get_default_budget($experiment,$session);
            }
        }

        if (isset($_REQUEST['change']) && $_REQUEST['change']) {

            $allow=check_allow('experiment_edit_participants','experiment_participants_show.php'.$thiscgis);

            if ($proceed) {
                if ($display=='enrol') {
                    $continue=true;

                    if ($_REQUEST['to_session']) $to_session=$_REQUEST['to_session']; else $to_session=0;

                    if ($to_session==0) {
                        $continue=false;
                        $_SESSION['sel']=$_REQUEST['sel'];
                        message(lang('no_session_selected'),'message_error');
                        redirect('admin/'.thisdoc().$thiscgis);
                    }

                    if ($proceed) {
                        $tsession=orsee_db_load_array("sessions",$to_session,"session_id");

                        $p_to_add=array();
                        if (isset($_REQUEST['sel'])) {
                            foreach($_REQUEST['sel'] as $k=>$v) {
                                if($v) $p_to_add[]=$k;
                            }
                        }
                        $num_to_add=count($p_to_add);


                        if (isset($_REQUEST['check_if_full']) && $_REQUEST['check_if_full']) {
                            $alr_reg=experiment__count_participate_at($experiment_id,$to_session);
                            $free_places=$tsession['part_needed']+$tsession['part_reserve']-$alr_reg;
                            if ($free_places < 0) $free_places=0;
                            if ($num_to_add > $free_places) {
                                $continue=false;
                                message(lang('too_many_participants_to_register').' '.
                                    lang('free_places_in_session_xxx').' '.
                                    session__build_name($tsession).':
                                    <FONT color="green">'.$free_places.'</FONT><BR>'.
                                    lang('please_change_your_selection'),'message_error');
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

                            if (count($p_to_add)>0) participant__update_last_enrolment_time($p_to_add);

                            $_SESSION['sel']=array();

                            message ($num_to_add.' '.lang('xxx_subjects_registered_to_session_xxx').' '.
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
                    foreach($_REQUEST['pstatus_id'] as $k=>$v) {
                        //if($v!=$_REQUEST['orig_pstatus_id'][$k]) {
                            $thispar=array(':pstatus_id'=>$v,
                                        ':experiment_id'=>$experiment_id,
                                        ':participant_id'=>$k);
                            if($session_id && $settings['enable_payment_module']=='y' && check_allow('payments_edit')) {
                                if ($show_payment_types) {
                                    if (isset($_REQUEST['paytype'][$k])) $thispar[':payment_type']=$_REQUEST['paytype'][$k];
                                    else $thispar[':payment_type']=0;
                                } else $thispar[':payment_type']=$default_payment_type;
                                if ($show_payment_budgets) {
                                    if (isset($_REQUEST['paybudget'][$k])) $thispar[':payment_budget']=$_REQUEST['paybudget'][$k];
                                    else $thispar[':payment_budget']=0;
                                } else $thispar[':payment_budget']=$default_payment_budget;
                                if (isset($_REQUEST['payamt'][$k])) $thispar[':payment_amt']=$_REQUEST['payamt'][$k];
                                else $thispar[':payment_amt']=NULL;
                            }
                        //}
                            $pars[]=$thispar;
                    }
                    $query="UPDATE ".table('participate_at')."
                            SET pstatus_id = :pstatus_id ";
                    if($session_id && $settings['enable_payment_module']=='y' && check_allow('payments_edit')) {
                        $query.=", payment_amt = :payment_amt ";
                        $query.=", payment_type = :payment_type ";
                        $query.=", payment_budget = :payment_budget ";
                    }
                    $query.="WHERE experiment_id= :experiment_id
                            AND participant_id= :participant_id";
                    $done=or_query($query,$pars);

                    // update rules signed data
                    if($settings['enable_rules_signed_tracking']=='y') {
                        $pars=array();
                        foreach($_REQUEST['pid'] as $k=>$v) {
                            if (isset($_REQUEST['rules'][$v]) && $_REQUEST['rules'][$v]=='y') $r='y';
                            else $r='n';
                            $pars[]=array(':rules_signed'=>$r,
                                        ':participant_id'=>$k);
                        }
                        $query="UPDATE ".table('participants')."
                                SET rules_signed = :rules_signed
                                WHERE participant_id = :participant_id";
                        $done=or_query($query,$pars);
                    }

                    // move participants to other sessions ...
                    $new_session=array();
                    foreach($_REQUEST['session'] as $k=>$v) {
                        if($v!=$_REQUEST['orig_session'][$k]) $new_session[$v][]=$k;
                    }

                    $pars=array(); $allmids=array();
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
                    if (count($allmids)>0) participant__update_last_enrolment_time($allmids);

                    // clean up participation statuses for 'no session's
                    $query="UPDATE ".table('participate_at')."
                            SET pstatus_id = '0'
                            WHERE session_id='0'";
                    $done=or_query($query);


                    message(lang('changes_saved'));
                    $m_message='<UL>';
                    foreach ($new_session as $msession => $mparts) {
                        $m_message.='<LI>'.count($mparts).' ';
                        if ($msession==0) $m_message.=lang('xxx_subjects_removed_from_registration');
                        else {
                            $tsession=orsee_db_load_array("sessions",$msession,"session_id");
                            $m_message.=lang('xxx_subjects_moved_to_session_xxx').'
                                <A HREF="'.thisdoc().'?experiment_id='.
                                    $experiment_id.'&session_id='.$msession.'">'.
                                            session__build_name($tsession).'</A>';
                            $tpartnr=experiment__count_participate_at($experiment_id,$msession);
                            if ($tsession['part_needed'] + $tsession['part_reserve'] < $tpartnr)
                                    $mmessage.=lang('subjects_number_exceeded');
                        }
                    }
                    $m_message.='</UL>';
                    message($m_message);
                    $target="experiment:".$experiment['experiment_name'];
                    if ($session_id) $target.="\nsession_id:".$session_id;
                    log__admin("experiment_edit_participant_list",$target);

                    redirect('admin/'.thisdoc().$thiscgis);
                }
            }
        }
    }
}

if ($proceed) {
    // list output

    if ($display=='enrol') $cols=participant__get_result_table_columns('experiment_assigned_list');
    else $cols=participant__get_result_table_columns('session_participants_list');

    if(!$session_id || !isset($show_payment_budgets) || $show_payment_budgets==false) unset($cols['payment_budget']);
    if(!$session_id || !isset($show_payment_types) || $show_payment_types==false) unset($cols['payment_type']);
    if(!$session_id) unset($cols['payment_amount']);

    // load participant data for this session/experiment
    $pars=array(':texperiment_id'=>$experiment_id);
    $query="SELECT * FROM ".table('participate_at').", ".table('participants')."
                    WHERE ".table('participate_at').".experiment_id= :texperiment_id
                    AND ".table('participate_at').".participant_id=".table('participants').".participant_id
                    AND (".$clause.")";
    foreach ($clause_pars as $p=>$v) $pars[$p]=$v;

    $order=query__get_sort('session_participants_list',$sort);  // sanitize sort or load default if empty
    if((!$order) || $order=='participant_id') {
        $order=table('participants').".participant_id";
    }
    $query.=" ORDER BY ".$order;

    // get result
    $result=or_query($query,$pars);

    $participants=array(); $plist_ids=array();
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
    $result=or_query($squery,$pars); $thislist_sessions=array();
    while ($line=pdo_fetch_assoc($result)) {
        $thislist_sessions[$line['session_id']]=$line;
    }

    // reorder by session date if ordered by session id
    if ($sort=="session_id") {
        $temp_participants=$participants; $participants=array();
        foreach ($thislist_sessions as $sid=>$s) {
            foreach ($temp_participants as $p) if ($p['session_id']==$sid) $participants[]=$p;
        }
    }
    unset($temp_participants);

    if (check_allow('participants_edit')) {
        echo javascript__edit_popup();
    }

    echo '<center>';

    echo '<TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'; width: 95%">
            <TR><TD align="center">
            '.$experiment['experiment_name'];
    if ($session_id) echo ', '.lang('session').' '.session__build_name($session);
    echo ', '.$title.'
            </TD>';
    echo '</TR></TABLE>';

    if ($display!='enrol') {
        echo '<P align="right" class="small">'.lang('download_as').'
                <A HREF="experiment_participants_show_pdf.php'.
                $thiscgis.'" target="_blank">'.lang('pdf_file').'</A>
                |
                <A HREF="experiment_participants_show_csv.php'.
                $thiscgis.'">'.lang('csv_file').'</A></P>';
    }

    // show query
    //echo '    <P class="small">Query: '.$query.'</P>';

    // form
    echo '
        <FORM name="part_list" method=post action="'.thisdoc().'">

        <BR>
        <table class="or_listtable" style="width: 95%"><thead>
            <TR style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">';
    echo participant__get_result_table_headcells($cols);
    echo '      </TR></thead>
                <tbody>';

    $shade=false;
    if (check_allow('experiment_edit_participants')) $disabled=false; else $disabled=true;
    $pnr=0;
    foreach ($participants as $p) {
        $pnr++;
        $p['order_number']=$pnr;
        echo '<tr class="small"';
                        if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
                               else echo 'bgcolor="'.$color['list_shade2'].'"';
        echo '><INPUT type="hidden" name="pid['.$p['participant_id'].']" value="'.$p['participant_id'].'">';
        echo participant__get_result_table_row($cols,$p);
        echo '</tr>';
        if ($shade) $shade=false; else $shade=true;
    }
    echo '</tbody></table>';

    if (check_allow('experiment_edit_participants')) {
        echo '<BR><TABLE border=0 class="or_panel" style="width: auto;">';
        if ($display=='enrol') {
            if(!isset($_REQUEST['to_session'])) $_REQUEST['to_session']="";
            if(!isset($_REQUEST['check_if_full'])) $_REQUEST['check_if_full']="true";
            echo '  <TR><TD>'.lang('register_marked_for_session').' ';
            echo select__sessions($_REQUEST['to_session'],'to_session',$thislist_sessions,false);
            echo '  </TD></TR>
                    <TR><TD>'.lang('check_for_free_places_in_session').'
                    <INPUT type=checkbox name="check_if_full" value="true"';
                    if ($_REQUEST['check_if_full']) echo ' CHECKED';
                    echo '>
                    </TD></TR>';
        }
        echo '  <TR><TD align=center>
                <INPUT type=hidden name="experiment_id" value="'.$experiment_id.'">';
                if ($session_id) echo '<INPUT type=hidden name="session_id" value="'.$session_id.'">';
                if ($pstatus!='') echo '<INPUT type=hidden name="pstatus" value="'.$pstatus.'">';
                if ($focus) echo '<INPUT type=hidden name="focus" value="'.$focus.'">';
                if ($sort) echo '<INPUT type=hidden name="sort" value="'.$sort.'">';
        echo '<span id="change_button_note"><B>&nbsp;<BR></B></span>';
        echo '  <INPUT class="button" type=submit name="change" value="'.lang('change').'">
                </TD></TR>';
        echo '</table>';
    }
    echo '</form>';

    if ($session_id) {
        $fields=array();
        $field='';
        $field.=lang('set_session').'&nbsp';
        $field.=select__sessions($session_id,'session_allsel',$thislist_sessions,false);
        $field.='<button class="button" name="session_button" id="session_button">'.lang('button_set').'</button>';
        $fields[]=$field;
        if($settings['enable_payment_module']=='y' && check_allow('payments_edit')) {
            if ($show_payment_budgets)  {
                $field='';
                $field.=lang('set_payment_budget').'&nbsp';
                $field.=payments__budget_selectfield('paybudget_allsel','',array(),$thislist_avail_payment_budgets);
                $field.='<button class="button" name="budget_button" id="budget_button">'.lang('button_set').'</button>';
                $fields[]=$field;
            }
            if ($show_payment_types)  {
                $field='';
                $field.=lang('set_payment_type').'&nbsp';
                $field.=payments__paytype_selectfield('paytype_allsel','',array(),$thislist_avail_payment_types);
                $field.='<button class="button" name="paytype_button" id="paytype_button">'.lang('button_set').'</button>';
                $fields[]=$field;
            }
            $field='';
            $field.=lang('set_payment_amount').'&nbsp';
            $field.='<INPUT type="text" name="payamt_allsel" value="0.00" size="7" maxlength="10" style="text-align:right;">';
            $field.='<button class="button" name="payamt_button" id="payamt_button">'.lang('button_set').'</button>';
            $fields[]=$field;
        }
        $field='';
        $field.=lang('set_participation_status').'&nbsp';
        $field.=expregister__participation_status_select_field('pstatus_allsel','');
        $field.='<button class="button" name="pstatus_button" id="pstatus_button">'.lang('button_set').'</button>';
        $fields[]=$field;
        foreach ($fields as $k=>$field) $fields[$k]='<TD align="center">
                                                <TABLE border="0" class="or_panel"><TR><TD>'.
                                                        $field.'</TD></TR></TABLE>
                                                        </TD>';

        echo '<TABLE class="or_option_buttons_box" style="width: 95%; background: '.$color['options_box_background'].';">';
        echo '<TR><TD colspan="'.count($fields).'" style="padding: 5px;"><B>'.lang('for_all_selected_participants').'</B></TD></TR>';
        echo '<TR>';
        echo implode('<TD>&nbsp</TD>',$fields);
        echo '</TR>';
        echo '<TR><TD colspan="'.count($fields).'"></TD></TR>';
        echo '</TABLE>';
    $status_colors=expregister__get_pstatus_colors();
    echo '  <script language="JavaScript">
                var status_colors = [];
            ';
            foreach ($status_colors as $k=>$v) echo ' status_colors['.$k.'] = "'.$v.'"; ';
    echo '
                $("select[name*=\'pstatus_id[\']").change(function () {
                    $(this).css("background", status_colors[$(this).val()]);
                });

                function show_change_note() {
                    $("#change_button_note").html("<b><font color=\"'.$color['important_note_textcolor'].'\">Do not forget to save your changes!</font></b><BR>");
                }
                $("#session_button").click(function() {
                    var count_checked=$("input[name*=\'sel[\']:checked").length;
                    if (count_checked>0) {
                        var myvalue=$("select[name*=\'session_allsel\']").val();
                        $("input[name*=\'sel[\']:checked").closest("tr").find("select[name*=\'session[\']").val(myvalue);
                        show_change_note();
                    }
                });
                $("#budget_button").click(function() {
                    var count_checked=$("input[name*=\'sel[\']:checked").length;
                    if (count_checked>0) {
                        var myvalue=$("select[name*=\'paybudget_allsel\']").val();
                        $("input[name*=\'sel[\']:checked").closest("tr").find("select[name*=\'paybudget[\']").val(myvalue);
                        show_change_note();
                    }
                });
                $("#paytype_button").click(function() {
                    var count_checked=$("input[name*=\'sel[\']:checked").length;
                    if (count_checked>0) {
                        var myvalue=$("select[name*=\'paytype_allsel\']").val();
                        $("input[name*=\'sel[\']:checked").closest("tr").find("select[name*=\'paytype[\']").val(myvalue);
                        show_change_note();
                    }
                });
                $("#payamt_button").click(function() {
                    var count_checked=$("input[name*=\'sel[\']:checked").length;
                    if (count_checked>0) {
                        var myvalue=$("input[name*=\'payamt_allsel\']").val();
                        $("input[name*=\'sel[\']:checked").closest("tr").find("input[name*=\'payamt[\']").val(myvalue);
                        show_change_note();
                    }
                });
                $("#pstatus_button").click(function() {
                    var count_checked=$("input[name*=\'sel[\']:checked").length;
                    if (count_checked>0) {
                        var myvalue=$("select[name*=\'pstatus_allsel\']").val();
                        $("input[name*=\'sel[\']:checked").closest("tr").find("select[name*=\'pstatus_id[\']").val(myvalue).trigger("change");
                        show_change_note();
                    }
                });         </script>';
    }
    echo '
        <BR>
        <TABLE width="80%" border=0>
        <TR>
            <TD>';
    if ($session_id && $session['session_status']=="live" && check_allow('session_send_reminder')) {
        if ($session['reminder_sent']=="y") {
            $state=lang('session_reminder_state__sent');
            $statecolor=$color['session_reminder_state_sent_text'];
            $explanation=lang('session_reminder_sent_at_time_specified');
            $send_button_title=lang('session_reminder_send_again');
        } elseif ($session['reminder_checked']=="y" && $session['reminder_sent']=="n") {
            $state=lang('session_reminder_state__checked_but_not_sent');
            $statecolor=$color['session_reminder_state_checked_text'];
            $explanation=lang('session_reminder_not_sent_at_time_specified');
            $send_button_title=lang('session_reminder_send');
        } else {
            $state=lang('session_reminder_state__waiting');
            $statecolor=$color['session_reminder_state_waiting_text'];
            $explanation=lang('session_reminder_will_be_sent_at_time_specified');
            $send_button_title=lang('session_reminder_send_now');
        }
        echo '<FONT color="'.$statecolor.'">'.lang('session_reminder').': '.$state.'</FONT><BR>';
        echo $explanation.'<BR><FORM action="session_send_reminder.php">'.
            '<INPUT type=hidden name="session_id" value="'.$session_id.'">'.
            '<INPUT class="button" type=submit name="submit" value="'.$send_button_title.'"></FORM>';
    }
    echo '      </TD><TD align=right>';
    if (check_allow('participants_bulk_mail')) experimentmail__bulk_mail_form();
    echo '      </TD>';
    echo '  </TR>
        </TABLE>';

    if ($settings['enable_email_module']=='y' && $session_id) {
        $session['experimenter']=$experiment['experimenter'];
        $nums=email__get_privileges('session',$session,'read',true);
        if ($nums['allowed'] && $nums['num_all']>0) {
            echo '<br><br><TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'; width: 95%">
                    <TR><TD align="center">
                        '.lang('emails').'
                    </TD></TR></TABLE>';
            echo javascript__email_popup();
            email__list_emails('session',$session['session_id'],$nums['rmode'],$thiscgis,false);
        }
    }

    echo '  <BR><BR><A HREF="experiment_show.php?experiment_id='.$experiment_id.'">'.lang('mainpage_of_this_experiment').'</A><BR><BR>
             </CENTER>';

}
include ("footer.php");

?>