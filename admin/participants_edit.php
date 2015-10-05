<?php
// part of orsee. see orsee.org
ob_start();

$menu__area= (isset($_REQUEST['participant_id']) && $_REQUEST['participant_id']) ? "participants_edit" : "participants_create";
$title="edit_participant";
$jquery=array('popup');
if (isset($_REQUEST['hide_header']) && $_REQUEST['hide_header']) $hide_header=true; else $hide_header=false;

if ($hide_header) {
    include ("nonoutputheader.php");
    html__header();
    echo '<basefont face="Arial,Helvetica,sans-serif"><center><BR>';
    echo '<TABLE width="90%" border="0"><TR><TD style="border-radius: 20px 20px 20px 20px; background: '.$color['content_background_color'].';"><BR>';
} else {
    include ("header.php");
}

if ($proceed) {
    if (isset($_REQUEST['participant_id']) && $_REQUEST['participant_id']) $participant_id=$_REQUEST['participant_id'];
    else $participant_id="";

    $allow=check_allow('participants_edit','participants_main.php');
}

if ($proceed) {
    $statuses=participant_status__get_statuses();
    $continue=true; $errors__dataform=array();

    if (isset($_REQUEST['add']) && $_REQUEST['add']) {

        // checks and errors
        foreach ($_REQUEST as $k=>$v) {
            if(!is_array($v)) $_REQUEST[$k]=trim($v);
        }
        $errors__dataform=participantform__check_fields($_REQUEST,true);
        $error_count=count($errors__dataform);
        if ($error_count>0) $continue=false;

        if ($continue) {
            $participant=$_REQUEST;

            if (!$participant_id) {
                $new_id=participant__create_participant_id($participant);
                $participant['participant_id']=$new_id['participant_id'];
                $participant['participant_id_crypt']=$new_id['participant_id_crypt'];
                $participant['creation_time']=time();
                if (isset($_REQUEST['subpool_id']) && $_REQUEST['subpool_id']) $participant['subpool_id']=$_REQUEST['subpool_id'];
                else $participant['subpool_id']=$settings['subpool_default_registration_id'];
                if (!isset($participant['language']) || !$participant['language']) $participant['language']=$settings['public_standard_language'];
            }

            if (isset($participant['status_id'])) $sid=$participant['status_id']; else $sid='';
            if (isset($participant['old_status_id'])) $osid=$participant['old_status_id']; else $osid='';
            if ($sid!='' && $osid!='' && $osid!=$sid) {
                $sid_e=$statuses[$sid]['eligible_for_experiments'];
                $osid_e=$statuses[$osid]['eligible_for_experiments'];
                if ($osid_e == 'y' && $sid_e=='n') $participant['deletion_time']=time();
                elseif ($osid_e == 'n' && $sid_e=='y') $participant['deletion_time']=0;
            }

            $done=orsee_db_save_array($participant,"participants",$participant['participant_id'],"participant_id");
            if ($done) message(lang('changes_saved'));

            if (isset($_REQUEST['register_session']) && $_REQUEST['register_session']=='y') {
                $session=orsee_db_load_array("sessions",$_REQUEST['session_id'],"session_id");
                if ($session['session_id']) {
                    $pars=array(':participant_id'=>$participant['participant_id'],
                                ':experiment_id'=>$session['experiment_id']);
                    $query="SELECT * FROM ".table('participate_at')."
                            WHERE participant_id= :participant_id
                            AND experiment_id= :experiment_id";
                    $line=orsee_query($query,$pars);
                    if (isset($line['participate_id'])) {
                        if ($line['session_id']>0) {
                            $osession=orsee_db_load_array("sessions",$line['session_id'],"session_id");
                            message(lang('participant_already_enroled_for_experiment').
                            ' <A HREF="experiment_participants_show.php?experiment_id='.
                            $osession['experiment_id'].'&session_id='.$osession['session_id'].'">'.
                            session__build_name($osession).'</A>.');
                        } else {
                            $pars=array(':participant_id'=>$participant['participant_id'],
                                        ':session_id'=>$session['session_id'],
                                        ':experiment_id'=>$session['experiment_id']);
                            $query="UPDATE ".table('participate_at')."
                                    SET session_id= :session_id,
                                    pstatus_id=0
                                    WHERE participant_id= :participant_id
                                    AND experiment_id= :experiment_id";
                            $done2=or_query($query,$pars);
                        }
                    } else {
                        $pars=array(':participant_id'=>$participant['participant_id'],
                                    ':session_id'=>$session['session_id'],
                                    ':experiment_id'=>$session['experiment_id']);
                        $query="INSERT into ".table('participate_at')."
                                SET participant_id= :participant_id,
                                session_id= :session_id,
                                experiment_id= :experiment_id,
                                pstatus_id=0";
                        $done2=or_query($query,$pars);
                    }
                    if (isset($done2) && $done2) {
                        message(lang('registered_participant_for').'
                                <A HREF="experiment_participants_show.php?experiment_id='.
                                $session['experiment_id'].'&session_id='.$session['session_id'].'">'.
                                session__build_name($session).'</A>.');
                    }
                } else {
                        message(lang('no_session_selected'),'message_error');
                }
            }

            if ($done) {
                if (isset($_REQUEST['participant_id']) && $_REQUEST['participant_id'])
                    log__admin("participant_edit","participant_id:".$participant['participant_id']);
                else log__admin("participant_create","participant_id:".$participant['participant_id']);
                $form=false;
                $addition = "";
                if($hide_header){
                    $addition .= "&hide_header=true";
                }
                redirect ("admin/participants_edit.php?participant_id=".$participant['participant_id'].$addition);
            } else {
                message(lang('database_error'));
            }
        }
    }
}

if ($proceed) {

    if ($participant_id && $continue) {
        $_REQUEST=orsee_db_load_array("participants",$participant_id,"participant_id");
    }

    $button_title = ($participant_id) ? lang('save') : lang('add');

    echo '<CENTER>';
    show_message();
    participant__show_admin_form($_REQUEST,$button_title,$errors__dataform,true);
    echo '<CENTER>';
    if ($participant_id) participants__get_statistics($participant_id);

    if ($settings['enable_email_module']=='y' && isset($_REQUEST['participant_id'])) {
        $nums=email__get_privileges('participant',$_REQUEST,'read',true);
        if ($nums['allowed'] && $nums['num_all']>0) {
            echo '<br><br><TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'; width: 90%">
                    <TR><TD align="left">
                        '.lang('emails').'
                    </TD></TR></TABLE>';
            echo javascript__email_popup();
            $url_string='participant_id='.$participant_id;
            if ($hide_header) $url_string.='&hide_header=true';
            email__list_emails('participant',$_REQUEST['participant_id'],$nums['rmode'],$url_string,false);
        }
    }

    echo "</CENTER>";

}
if ($hide_header) {
    echo '<BR><BR><BR><BR>';
    debug_output();
    echo '</TD></TR><TABLE></center><BR>';
    html__footer();
} else {
    include ("footer.php");
}
if ($hide_header) {
    echo str_ireplace("href=", "target=\"_parent\" href=", ob_get_clean());
}
?>
