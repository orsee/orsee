<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="experiments_new";
$title="edit_experiment";
$jquery=array('arraypicker','textext','datepicker');
include ("header.php");
if ($proceed) {

    if (isset($_REQUEST['experiment_id']) && $_REQUEST['experiment_id']) {
        $allow=check_allow('experiment_edit','experiment_show.php?experiment_id='.$_REQUEST['experiment_id']);
        if ($proceed) {
            $edit=orsee_db_load_array("experiments",$_REQUEST['experiment_id'],"experiment_id");
            $edit['experiment_show_type']=$edit['experiment_type'].','.$edit['experiment_ext_type'];
            if (!check_allow('experiment_restriction_override'))
                check_experiment_allowed($edit,"admin/experiment_show.php?experiment_id=".$edit['experiment_id']);
        }
    } else {
        $allow=check_allow('experiment_edit','experiment_main.php');
    }
}

if ($proceed) {
    $continue=true;

    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {
        $_REQUEST['experiment_class']=id_array_to_db_string(multipicker_json_to_array($_REQUEST['experiment_class']));
        $_REQUEST['experimenter']=id_array_to_db_string(multipicker_json_to_array($_REQUEST['experimenter']));
        $_REQUEST['experimenter_mail']=id_array_to_db_string(multipicker_json_to_array($_REQUEST['experimenter_mail']));

        if ($settings['enable_ethics_approval_module']=='y' && check_allow('experiment_edit_ethics_approval_details')) {
            $_REQUEST['ethics_expire_date']=ortime__array_to_sesstime($_REQUEST,'ethics_expire_date_');
        }

        if ($settings['enable_payment_module']=='y' ) {
            if (isset($_REQUEST['payment_types']))
                $_REQUEST['payment_types']=id_array_to_db_string(multipicker_json_to_array($_REQUEST['payment_types']));
            if (isset($_REQUEST['payment_budgets']))
                $_REQUEST['payment_budgets']=id_array_to_db_string(multipicker_json_to_array($_REQUEST['payment_budgets']));
        }

        if (!$_REQUEST['experiment_public_name']) {
            message(lang('error_you_have_to_give_public_name'));
            $continue=false;
        }

        if (!$_REQUEST['experiment_name']) {
            message(lang('error_you_have_to_give_internal_name'));
                $continue=false;
        }

        if ($settings['enable_editing_of_experiment_sender_email']=='y' && check_allow('experiment_change_sender_address')) {
            if (!preg_match("/^[^@ \t\r\n]+@[-_0-9a-zA-Z]+\.[^@ \t\r\n]+$/",$_REQUEST['sender_mail'])) {
                message(lang('error_no_valid_sender_mail'));
                $continue=false;
            }
        } else unset($_REQUEST['sender_mail']);

        if (!$_REQUEST['experimenter']) {
            message(lang('error_at_least_one_experimenter_required'));
            $continue=false;
        }

        if (!$_REQUEST['experimenter_mail']) {
            message(lang('error_at_least_one_experimenter_mail_required'));
            $continue=false;
        }


        if ($continue) {

            if (!isset($_REQUEST['experiment_finished']) ||!$_REQUEST['experiment_finished']) $_REQUEST['experiment_finished']="n";

            if (!isset($_REQUEST['hide_in_stats']) ||!$_REQUEST['hide_in_stats']) $_REQUEST['hide_in_stats']="n";

            if (!isset($_REQUEST['hide_in_cal']) ||!$_REQUEST['hide_in_cal']) $_REQUEST['hide_in_cal']="n";

            if (!isset($_REQUEST['access_restricted']) ||!$_REQUEST['access_restricted']) $_REQUEST['access_restricted']='n';



            $exptypes=explode(",",$_REQUEST['experiment_show_type']);
            $_REQUEST['experiment_type']=trim($exptypes[0]);
            $_REQUEST['experiment_ext_type']=trim($exptypes[1]);

            $edit=$_REQUEST;

            $done=orsee_db_save_array($edit,"experiments",$edit['experiment_id'],"experiment_id");

            if ($done) {
                message (lang('changes_saved'));
                redirect ("admin/experiment_edit.php?experiment_id=".$edit['experiment_id']);
            } else {
                message (lang('database_error'));
                redirect ("admin/experiment_edit.php?experiment_id=".$edit['experiment_id']);
            }

        }

        $edit=$_REQUEST;

    }

}

if ($proceed) {

    // form

    // initialize if empty
    if (!isset($edit)) {
        $edit=array();
        $formvarnames=array('experiment_name','experiment_public_name','experiment_description',
                'public_experiment_note','experiment_link_to_paper','experiment_class',
                'experiment_id','sender_mail','experiment_show_type','access_restricted',
                'experiment_finished','hide_in_stats','hide_in_cal',
                'ethics_by','ethics_number','ethics_exempt','ethics_expire_date',
                'payment_types','payment_budgets');
        foreach ($formvarnames as $fvn) {
            if (!isset($edit[$fvn])) $edit[$fvn]="";
        }
    $edit['access_restricted']=$settings['default_experiment_restriction'];
    }
    if (!$edit['ethics_expire_date']) $edit['ethics_expire_date']=ortime__unixtime_to_sesstime()+100000000;

    echo '<CENTER>';

    show_message();

    if (!isset($edit['experiment_id']) || !$edit['experiment_id']) {
        $edit['experiment_id']=time();
    }

    echo '<FORM action="experiment_edit.php">
            <INPUT type=hidden name="experiment_id" value="'.$edit['experiment_id'].'">';
    echo '<TABLE class="or_formtable" style="max-width: 90%;">';
    echo '      <TR>
                <TD>'.lang('id').'</TD>
                <TD>'.$edit['experiment_id'].'</TD>
            </TR>';

    echo '      <TR>
                <TD>'.lang('internal_name').':</TD>
                <TD><INPUT name=experiment_name type=text size=40 maxlength=100 value="'.stripslashes($edit['experiment_name']).'"></TD>
            </TR>';


    echo '      <TR>
                <TD>'.lang('public_name').':</TD>
                <TD><INPUT name=experiment_public_name type=text size=40 maxlength=100
                    value="'.stripslashes($edit['experiment_public_name']).'"></TD>
            </TR>';


    echo '      <TR>
                <TD>'.lang('internal_description').':</TD>
                <TD><textarea name=experiment_description rows=3 cols=30
                    wrap=virtual>'.stripslashes($edit['experiment_description']).'</textarea></TD>
            </TR>';

    if (or_setting('allow_public_experiment_note') && check_allow('session_edit_add_public_session_note')) {
        echo '  <TR>
                <TD valign="top">'.lang('public_experiment_note').'<br><font class="small">'.
                    lang('public_experiment_note_note').'</font>:</TD>
                <TD><textarea name="public_experiment_note" rows=3 cols=30 wrap=virtual>'.
                    $edit['public_experiment_note'].'</textarea></TD>
            </TR>';
    }

    echo '      <TR>
                <TD>'.lang('type').':</TD>
                <TD><SELECT name="experiment_show_type">';
    $experiment_types=array();
    $experiment_internal_types=$system__experiment_types;
    foreach ($experiment_internal_types as $inttype) {
        $expexttypes=load_external_experiment_types($inttype);
        foreach ($expexttypes as $exttype) {
            $value=$inttype.','.$exttype['exptype_id'];
            $show=$lang[$inttype].' ("'.$exttype[lang('lang')].'")';
            echo '<OPTION value="'.$value.'"';
            if ($value==$edit['experiment_show_type']) echo ' SELECTED';
                echo '>'.$show.'</OPTION>';
        }
    }

    echo '      </SELECT></TD>
            </TR>';

    echo '          <TR>
                                <TD>'.lang('class').':</TD>
                                <TD valign="top">';
    echo experiment__experiment_class_select_field('experiment_class',
                        db_string_to_id_array($edit['experiment_class']));

    echo '              </TD>
                        </TR>';

    echo '      <TR>
                <TD valign="top">'.lang('experimenter').':</TD>
                <TD>';
    if (!isset($_REQUEST['experiment_id']) || !$_REQUEST['experiment_id']) $edit['experimenter']='|'.$expadmindata['admin_id'].'|';
    echo experiment__experimenters_select_field("experimenter",db_string_to_id_array($edit['experimenter']),true,
        array('tag_color'=>'#f1c06f','picker_icon'=>'user','picker_color'=>'#c58720','picker_maxnumcols'=>2));
    echo '  </TD>
            </TR>';

    if ($settings['allow_experiment_restriction']=='y') {
    echo '          <TR>
                                <TD valign="top">
                                        '.lang('experiment_access_restricted').':
                                </TD>
                                <TD>
                    <INPUT name="access_restricted" type=checkbox value="y"';
    if ($edit['access_restricted']=="y") echo " CHECKED";
    echo '>
                    </TD>
                        </TR>';
    }

    echo '      <TR>
                <TD>'.lang('get_emails').':</TD>
                <TD>';
                    if (!isset($_REQUEST['experiment_id']) || !$_REQUEST['experiment_id']) $edit['experimenter_mail']='|'.$expadmindata['admin_id'].'|';
                    echo experiment__experimenters_select_field("experimenter_mail",db_string_to_id_array($edit['experimenter_mail']),true,
                        array('tag_color'=>'#c4e79d','picker_icon'=>'user','picker_color'=>'#90d841','picker_maxnumcols'=>2));

    echo '          </TD>
            </TR>';

    if ($settings['enable_editing_of_experiment_sender_email']=='y' && check_allow('experiment_change_sender_address')) {
    echo '      <TR>
                <TD>'.lang('email_sender_address').':</TD>
                <TD><INPUT name="sender_mail" type="text" size=40 maxlength=60
                    value="';
                    if ($edit['sender_mail']) echo stripslashes($edit['sender_mail']);
                        else echo $settings['support_mail'];
                    echo '"></TD>
            </TR>';

    }


    if ($settings['enable_ethics_approval_module']=='y' && check_allow('experiment_edit_ethics_approval_details')) {
    echo '      <TR>
                <TD colspan="2">
                    <TABLE width="100%" border=0>
                        <TR><TD rowspan="2" valign="top">'.lang('human_subjects_ethics_approval').':</TD>
                        <TD>'.lang('ethics_by').'<INPUT name="ethics_by" type="text" size=20 maxlength=60
                                    value="'.$edit['ethics_by'].'"></TD>
                        <TD>'.lang('ethics_number').'<INPUT name="ethics_number" type="text" size=10 maxlength=50
                                    value="'.$edit['ethics_number'].'"></TD>
                        </TR><TR>
                            <TD colspan="2"><INPUT name="ethics_exempt" type="radio" value="y"';

    if ($edit['ethics_exempt']=='y') echo ' CHECKED';
    echo '>'.lang('ethics_exempt_or').'&nbsp;<INPUT name="ethics_exempt" type="radio" value="n"';
    if($edit['ethics_exempt']!='y') echo ' CHECKED';
    echo '>'.lang('ethics_expires_on').'&nbsp;&nbsp;';
    echo formhelpers__pick_date('ethics_expire_date',$edit['ethics_expire_date'],$settings['session_start_years_backward'],$settings['session_start_years_forward']);

    echo '          </TD>
                        </TR>
                    </TABLE>
                    </TD>
            </TR>';

    }

    if ($settings['enable_payment_module']=='y' ) {
        $payment_types=payments__load_paytypes();
        if ($edit['payment_types'] || is_array($payment_types) && count($payment_types)>1) $show_payment_types=true;
        else $show_payment_types=false;
        $payment_budgets=payments__load_budgets();
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


    echo '      <TR>
                <TD>'.lang('experiment_finished?').'</TD>
                <TD><INPUT name="experiment_finished" type="checkbox" value="y"';
                    if ($edit['experiment_finished']=="y") echo " CHECKED";
                    echo '></TD>
            </TR>';

    echo '      <TR>
                <TD>'.lang('hide_in_stats?').'</TD>
                <TD><INPUT name="hide_in_stats" type="checkbox" value="y"';
    if ($edit['hide_in_stats']=="y") echo " CHECKED";
    echo '>     </TD>
            </TR>';

    echo '      <TR>
                <TD>'.lang('hide_in_cal?').'</TD>
                <TD><INPUT name="hide_in_cal" type="checkbox" value="y"';
                    if ($edit['hide_in_cal']=="y") echo " CHECKED";
                    echo '></TD>
            </TR>';

    echo '      <TR>
                <TD>'.lang('experiment_link_to_paper').':</TD>
                <TD><INPUT name="experiment_link_to_paper" type="text" size=40 maxlength=200
                    value="'.stripslashes($edit['experiment_link_to_paper']).'">';
    if (trim($edit['experiment_link_to_paper'])) echo ' <A target="_blank" HREF="'.trim($edit['experiment_link_to_paper']).'">'.lang('link').'</A>';
    echo        '</TD>
            </TR>';


    echo '      <TR>
                <TD COLSPAN=2 align=center>
                    <INPUT name="edit" type="submit" class="button" value="';
                    if (!isset($_REQUEST['experiment_id']) || !$_REQUEST['experiment_id']) echo lang('add');
                        else echo lang('change');
                    echo '"></TD>
            </TR>';

    echo '  </TABLE>';
    //echo '</TD></TR></TABLE>';
    echo '</FORM>
        <BR>';

    if (isset($_REQUEST['experiment_id']) && $_REQUEST['experiment_id'] && check_allow('experiment_delete')) {
        echo '
            <table>
                <TR>
                    <TD>
                        '.button_link('experiment_delete.php?experiment_id='.$edit['experiment_id'],
                            lang('delete'),'trash-o').'
                    <TD>
                </TR>
            </table>';
        }

    if (isset($_REQUEST['experiment_id']) && $_REQUEST['experiment_id'])
        echo '  <BR><BR>
            <a href="experiment_show.php?experiment_id='.$_REQUEST['experiment_id'].'"><i class="fa fa-level-up fa-lg" style="padding-right: 3px;"></i>'.
            lang('mainpage_of_this_experiment').'</A>';

    echo '</center>';

}
include ("footer.php");
?>
