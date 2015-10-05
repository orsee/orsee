<?php
// part of orsee. see orsee.org
ob_start();

$title="edit_session";
$jquery=array('datepicker','clockpicker','textext','arraypicker');
include ("header.php");
if ($proceed) {

    if (isset($_REQUEST['session_id']) && $_REQUEST['session_id']) $session_id=$_REQUEST['session_id'];
    else $session_id="";

    if ($session_id) {
        $edit=orsee_db_load_array("sessions",$session_id,"session_id");
    } else {
        $addit=true;
    }
}

if ($proceed) {
    if (isset($_REQUEST['experiment_id'])) $experiment_id=$_REQUEST['experiment_id']; else $experiment_id=$edit['experiment_id'];
    $experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
    if (!isset($experiment['experiment_id'])) redirect("admin/");
}

if ($proceed) {
    $allow=check_allow('session_edit','experiment_show.php?experiment_id='.$experiment_id);
}
if ($proceed) {
    if (!check_allow('experiment_restriction_override'))
        check_experiment_allowed($experiment_id,"admin/experiment_show.php?experiment_id=".$experiment_id);
}

if ($proceed) {
    if (isset($experiment_id) && $experiment_id)
        $allow=check_allow('session_edit','experiment_show.php?experiment_id='.$experiment_id);
}

if ($proceed) {
    if (!check_allow('experiment_restriction_override'))
        check_experiment_allowed($_REQUEST['experiment_id'],"admin/experiment_show.php?experiment_id=".$experiment_id);
}


if ($proceed) {
    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {

        if ($settings['enable_payment_module']=='y' ) {
            if (isset($_REQUEST['payment_types']))
                $_REQUEST['payment_types']=id_array_to_db_string(multipicker_json_to_array($_REQUEST['payment_types']));
            if (isset($_REQUEST['payment_budgets']))
                $_REQUEST['payment_budgets']=id_array_to_db_string(multipicker_json_to_array($_REQUEST['payment_budgets']));
        }

        $_REQUEST['session_start']=ortime__array_to_sesstime($_REQUEST,'session_start_');

        $registered=experiment__count_participate_at($edit['experiment_id'],$edit['session_id']);
        $time_changed=false;

        if ($edit['session_start'] != $_REQUEST['session_start']) {
            $time_changed=true;
            if ($registered>0) message (lang('session_time_changed'));
        } else $time_changed=false;

        if (!isset($_REQUEST['addit'])) {
            if ($_REQUEST['registration_end_hours']!=$edit['registration_end_hours'] || $time_changed) {
                    $_REQUEST['reg_notice_sent']="n";
                        message (lang('reg_time_extended_but_notice_sent'));
            }
            if ( ($_REQUEST['session_reminder_hours']!=$edit['session_reminder_hours'] || $time_changed) &&
                    isset($edit['session_reminder_sent']) && $edit['session_reminder_sent']=="y")
                        message (lang('session_reminder_changed_but_notice_sent'));
        }

        $edit=$_REQUEST;

        $done=orsee_db_save_array($edit,"sessions",$edit['session_id'],"session_id");

        if ($done) {
            log__admin("session_edit","session:".session__build_name($edit,
                    $settings['admin_standard_language'])."\nsession_id:".$edit['session_id']);
            message (lang('changes_saved'));
            redirect ('admin/session_edit.php?session_id='.$edit['session_id']);
        } else {
            lang('database_error');
            redirect ('admin/session_edit.php?session_id='.$edit['session_id']);
        }
    }
}

if ($proceed) {
// form

    if (isset($_REQUEST['copy']) && $_REQUEST['copy']) $session_id="";

    if (!$session_id) {
        $addit=true;
        $button_name=lang('add');

        if (isset($_REQUEST['copy']) && $_REQUEST['copy']) {
            if ($settings['enable_payment_module']=='y' ) {
                if (isset($_REQUEST['payment_types']))
                    $_REQUEST['payment_types']=id_array_to_db_string(multipicker_json_to_array($_REQUEST['payment_types']));
                if (isset($_REQUEST['payment_budgets']))
                    $_REQUEST['payment_budgets']=id_array_to_db_string(multipicker_json_to_array($_REQUEST['payment_budgets']));
                }
            $_REQUEST['session_start']=ortime__array_to_sesstime($_REQUEST,'session_start_');
            $edit=$_REQUEST;
            $edit['session_id']=time();
            $edit['session_status']='planned';
            $session_time=0;
        } else {
            $edit['experiment_id']=$_REQUEST['experiment_id'];
            $edit['session_id']=time();

            $edit['laboratory_id']="";
            $edit['session_remarks']="";
            $edit['public_session_note']="";

            $edit['session_start']=ortime__unixtime_to_sesstime();

            $edit['session_duration_hour']=$settings['session_duration_hour_default'];
            $edit['session_duration_minute']=$settings['session_duration_minute_default'];

            $edit['session_reminder_hours']=$settings['session_reminder_hours_default'];
            $edit['send_reminder_on']=$settings['session_reminder_send_on_default'];
            $edit['registration_end_hours']=$settings['session_registration_end_hours_default'];
            $session_time=0;

            $edit['part_needed']=$settings['lab_participants_default'];
            $edit['part_reserve']=$settings['reserve_participants_default'];

            $edit['session_status']='planned';

            $edit['payment_types']="";
            $edit['payment_budgets']="";
        }
    } else {
        $session_time=ortime__sesstime_to_unixtime($edit['session_start']);
        $button_name=lang('change');
        session__check_lab_time_clash($edit);
    }

    echo '<center>';

    show_message();

    echo '<FORM action="session_edit.php" method="POST">
            <INPUT type=hidden name=session_id value="'.$edit['session_id'].'">
            <INPUT type=hidden name=experiment_id value="'.$edit['experiment_id'].'">';
    if (isset($addit) && $addit) echo '<INPUT type=hidden name="addit" value="true">';
    echo '
        <TABLE class="or_formtable">
        <TR>
            <TD>'.lang('id').':</TD>
            <TD>'.$edit['session_id'].'</TD>
        </TR>';

    echo '  <TR>
            <TD>'.lang('date').':</TD>
            <TD>';

    echo formhelpers__pick_date('session_start',$edit['session_start'],$settings['session_start_years_backward'],$settings['session_start_years_forward']);
    echo '
            </TD>
        </TR>';

    echo '  <TR>
            <TD>
                '.lang('time').':
            </TD>
            <TD>';
    echo formhelpers__pick_time('session_start', $edit['session_start']);
    echo'
            </TD>
        </TR>';

    echo '  <TR>
            <TD>
                '.lang('laboratory').':
            </TD>
            <TD>';
    laboratories__select_field("laboratory_id",$edit['laboratory_id']);
    echo '
            </TD>
        </TR>';


    echo '  <TR>
            <TD>
                '.lang('experiment_duration').':
            </TD>
            <TD>';
    helpers__select_numbers("session_duration_hour",$edit['session_duration_hour'],
                        0,$settings['session_duration_hour_max'],2,1);
    echo ':';
    helpers__select_numbers("session_duration_minute",
                        $edit['session_duration_minute'],0,59,2,
                        $settings['session_duration_minute_steps']);
    echo '
            </TD>
        </TR>';

    echo ' <TR>
            <TD>
                '.lang('session_reminder_hours_before').':
            </TD>
            <TD>';
    if (isset($edit['session_reminder_sent']) && $edit['session_reminder_sent']=="y")
        echo $edit['session_reminder_hours'].' ('.lang('session_reminder_already_sent').')';
    else helpers__select_numbers_relative("session_reminder_hours",$edit['session_reminder_hours'],0,
                         $settings['session_reminder_hours_max'],2,$settings['session_reminder_hours_steps'],
                         $session_time);
    echo '
            </TD>
        </TR>';

    echo ' <TR>
            <TD>
                '.lang('send_reminder_on').'
            </TD>
            <TD>';
    $oparray=array('enough_participants_needed_plus_reserve'=>'enough_participants_needed_plus_reserve',
                        'enough_participants_needed'=>'enough_participants_needed',
                        'in_any_case_dont_ask'=>'in_any_case_dont_ask');
    echo helpers__select_text($oparray,"send_reminder_on",$edit['send_reminder_on']);
    echo '
            </TD>
        </TR>';

    echo '  <TR>
            <TD>
                '.lang('needed_participants').':
            </TD>
            <TD>';
    helpers__select_numbers("part_needed",$edit['part_needed'],0,$settings['lab_participants_max']);
    echo '
            </TD>
        </TR>';

    echo '  <TR>
            <TD>
                '.lang('reserve_participants').':
            </TD>
            <TD>';
    helpers__select_numbers("part_reserve",$edit['part_reserve'],0,$settings['reserve_participants_max']);
    echo '
            </TD>
        </TR>';

    echo '  <TR>
            <TD>
                '.lang('registration_end_hours_before').':
            </TD>
            <TD>';
    helpers__select_numbers_relative("registration_end_hours",$edit['registration_end_hours'],0,
                    $settings['session_registration_end_hours_max'],2,
                    $settings['session_registration_end_hours_steps'],$session_time);
    echo '
            </TD>
        </TR>';

    echo '  <TR>
            <TD valign="top">
                '.lang('remarks').'<br><font class="small">'.lang('session_remarks_note').'</font>:
            </TD>
            <TD>
                <textarea name="session_remarks" rows=3 cols=30 wrap=virtual>'.$edit['session_remarks'].'</textarea>
            </TD>
        </TR>';

    if (or_setting('allow_public_session_note') && check_allow('session_edit_add_public_session_note')) {
        echo '  <TR>
                <TD valign="top">
                    '.lang('public_session_note').'<br><font class="small">'.lang('public_session_note_note').'</font>:
                </TD>
                <TD>
                    <textarea name="public_session_note" rows=3 cols=30 wrap=virtual>'.$edit['public_session_note'].'</textarea>
                </TD>
            </TR>';
    }

    if ($settings['enable_payment_module']=='y' ) {
            $payment_types=db_string_to_id_array($experiment['payment_types']);
            if ($edit['payment_types'] || is_array($payment_types) && count($payment_types)>1) $show_payment_types=true;
            else $show_payment_types=false;
            $payment_budgets=db_string_to_id_array($experiment['payment_budgets']);
            if ($edit['payment_budgets'] || is_array($payment_budgets) && count($payment_budgets)>1) $show_payment_budgets=true;
            else $show_payment_budgets=false;

            if ($show_payment_budgets) {
                echo '<TR>
                        <TD valign="top">'.lang('possible_budgets').'</TD>
                        <TD>';
                echo payments__budget_multiselectfield("payment_budgets",db_string_to_id_array($edit['payment_budgets']));
                echo '</TD>
                    </TR>';
            }
            if ($show_payment_types) {
                echo '<TR>
                        <TD valign="top">'.lang('possible_payment_types').'</TD>
                        <TD>';
                echo payments__paytype_multiselectfield("payment_types",db_string_to_id_array($edit['payment_types']));
                echo '<BR></TD>
                    </TR>';
            }
    }

    echo '  <TR>
            <TD>
                '.lang('session_status').'
            </TD>
            <TD>
                <TABLE border=0><TR><TD style="outline: 1px dashed red;">'.session__session_status_select('session_status',$edit['session_status']).'</TD></TR></TABLE>
            </TD>
        </TR>

        <TR>
            <TD COLSPAN=2 align="center"><BR>
                <INPUT class="button" name="edit" type="submit" value="'.$button_name.'">
            </TD>
        </TR>';

    if ($session_id) {

        echo '
            <TR>
                <TD COLSPAN=2 align="right">
                    <INPUT class="button" name="copy" type="submit" value="'.lang('copy_as_new_session').'">
                </TD>
            </TR>';
    }

    echo '
          </table>
    </FORM>
    <BR>';


    if ($session_id) {
        $reg=experiment__count_participate_at($edit['experiment_id'],$session_id);

        if (($reg==0 && check_allow('session_empty_delete')) || check_allow('session_nonempty_delete'))
            echo '
                <table>
                    <TR>
                        <TD>
                            '.button_link('session_delete.php?session_id='.$edit['session_id'],
                            lang('delete'),'trash-o').'
                        </TD>
                    </TR>
                </table>';
    }

    if ($session_id) $experiment_id=$edit['experiment_id']; else $experiment_id=$_REQUEST['experiment_id'];
    echo '<BR><BR>
        <a href="experiment_show.php?experiment_id='.$experiment_id.'"><i class="fa fa-level-up fa-lg" style="padding-right: 3px;"></i>'.
            lang('mainpage_of_this_experiment').'</A>
        </center>';

}
include ("footer.php");
?>