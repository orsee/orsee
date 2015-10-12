<?php

//////////////////////////////////////////////////////////////////
// CREATE THE CONFIGURATION CODE IN Options/Prepare data import //
// THEN COPY YOUR IMPORT CONFIGURATION CODE BELOW HERE          //

$old_db_name="orsee_old";
$new_db_name="orsee_3_0_0";

// mapping of participant statuses
// participant_status_mapping[deleted y/n][excluded y/n]=status_id
$participant_status_mapping=array();
$participant_status_mapping["n"]["n"]="1";
$participant_status_mapping["n"]["y"]="3";
$participant_status_mapping["y"]["n"]="2";
$participant_status_mapping["y"]["y"]="3";

// mapping of participation statuses
// participation_mapping[shownup y/n][participated y/n]=pstatus_id
$participation_mapping=array();
$participation_mapping["n"]["n"]="3";
$participation_mapping["n"]["y"]="3";
$participation_mapping["y"]["n"]="2";
$participation_mapping["y"]["y"]="1";

// mapping from old participant profile form to new form
// empty value for new column name implies no import
// pform_mapping[old column name]=new column name
$pform_mapping=array();
$pform_mapping["email"]="email";
$pform_mapping["phone_number"]="phone_number";
$pform_mapping["lname"]="lname";
$pform_mapping["fname"]="fname";
$pform_mapping["begin_of_studies"]="begin_of_studies";
$pform_mapping["field_of_studies"]="field_of_studies";
$pform_mapping["profession"]="profession";
$pform_mapping["gender"]="gender";

// other settings
$import_type="all";
$replace_tokens="n";


// COPY YOUR IMPORT CONFIGURATION CODE ABOVE HERE               //
//////////////////////////////////////////////////////////////////

// for debugging purposes
$do_delete=true;
$do_insert=true;

// what to do. reset.
$import_admin=false;
$import_admin_log=false;
$import_custom_admin_types=false;
$import_cron_log=false;
$import_experiment_types=false;
$import_experiments=false;
$import_faqs=false;
$import_lab_bookings=false;
$import_lang_items=false;
$import_userform_select_langs=false;
$import_participants=false;
$import_sessions=false;
$import_participant_log=false;
$import_participate_at=false;
$import_subpools=false;
$import_files=false;
$update_last_enrolment_date=false;


if ($import_type=='all') {
    $import_admin=true;
    $import_admin_log=true;
    $import_custom_admin_types=true;
    $import_cron_log=true;
    $import_experiment_types=true;
    $import_experiments=true;
    $import_faqs=true;
    $import_lab_bookings=true;
    $import_lang_items=true;
    $import_userform_select_langs=true;
    $import_participants=true;
    $import_sessions=true;
    $import_participant_log=true;
    $import_participate_at=true;
    $import_subpools=true;
    $import_files=true;
    $update_last_enrolment_date=true;
} elseif ($import_type=='participation') {
    $import_admin_log=true;
    $import_cron_log=true;
    $import_experiments=true;
    $import_lab_bookings=true;
    $import_participants=true;
    $import_sessions=true;
    $import_participant_log=true;
    $import_participate_at=true;
    $update_last_enrolment_date=true;
}

// get tagsets
include ("../admin/nonoutputheader.php");


// some functions we will need
function mult_time_to_sesstime($line,$pre) {
    return helpers__pad_number($line[$pre.'year'],4).
            helpers__pad_number($line[$pre.'month'],2).
            helpers__pad_number($line[$pre.'day'],2).
            helpers__pad_number($line[$pre.'hour'],2).
            helpers__pad_number($line[$pre.'minute'],2);
}

function commalist_to_dbstring($commalist) {
    $c_arr=explode(",",$commalist);
    foreach ($c_arr as $k=>$v) $c_arr[$k]=trim($v);
    return id_array_to_db_string($c_arr);
}

function experimentercommalist_to_newdbstring($commalist) {
    global $old_experimenters, $old_db_name;
    if (!(isset($old_experimenters) && is_array($old_experimenters) && count($old_experimenters)>0)) {
        $squery="SELECT * FROM ".$old_db_name.".".table('admin')."";
        $result=or_query($squery); $old_experimenters=array();
        while ($line=pdo_fetch_assoc($result)) $old_experimenters[trim($line['adminname'])]=$line['admin_id'];
    }
    $c_arr=explode(",",$commalist); $n_arr=array();
    foreach ($c_arr as $k=>$v) {
        if (isset($old_experimenters[trim($v)])) $n_arr[]=$old_experimenters[trim($v)];
    }
    return id_array_to_db_string($n_arr);
}

function detectUTF8($string) {
        return preg_match('%(?:
        [\xC2-\xDF][\x80-\xBF]        # non-overlong 2-byte
        |\xE0[\xA0-\xBF][\x80-\xBF]               # excluding overlongs
        |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}      # straight 3-byte
        |\xED[\x80-\x9F][\x80-\xBF]               # excluding surrogates
        |\xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
        |[\xF1-\xF3][\x80-\xBF]{3}                  # planes 4-15
        |\xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
        )+%xs', $string);
}

function convert_array_to_UTF8($arr) {
    foreach($arr as $k=>$v) {
        if (!detectUTF8(stripslashes($v))) {
            $arr[$k]=utf8_encode(stripslashes($v));
        }
    }
    return $arr;
}

function copy_table($table, $idvar,$cond="",$delete=true) {
    global $do_delete, $do_insert, $new_db_name, $old_db_name;
    $dquery="DELETE FROM ".$new_db_name.".".table($table)."";
    if ($do_delete && $delete) $done=or_query($dquery);
    $squery="SELECT * FROM ".$old_db_name.".".table($table);
    if ($cond) $squery.=' '.$cond;
    $result=or_query($squery);
    while ($line=pdo_fetch_assoc($result)) {
        $line=convert_array_to_UTF8($line);
        if ($do_insert) $done=orsee_db_save_array($line,$table,$line[$idvar],$idvar);
    }
}

// IMPORT STARTS HERE
if (isset($participant_status_mapping) && is_array($participant_status_mapping) && count($participant_status_mapping)>0 &&
    isset($participation_mapping) && is_array($participation_mapping) && count($participation_mapping)>0 &&
    isset($pform_mapping) && is_array($pform_mapping) && count($pform_mapping)>0) $allset=true;
else $allset=false;

if (!$allset) {
    echo "You first need to configure the data import (in ORSEE, as 'installer', go to Options->Prepare data import) and paste the resulting code into the top of this file.\n\n";

} else {

    // exptype name-id mapping
    // needed for or_participants.subscriptions and or_experiments.experiment_ext_type
    $old_exptype_name_to_id=array();
    $squery="SELECT * FROM ".$old_db_name.".".table('experiment_types')."";
    $result=or_query($squery);
    while ($line=pdo_fetch_assoc($result)) {
        $old_exptype_name_to_id[$line['exptype_name']]=$line['exptype_id'];
    }


// START IMPORTING

    if ($import_admin) {
        echo "Importing not yet existing admin profiles from ".table('admin')."\n";
        $existing_admins=array();
        $squery="SELECT adminname FROM ".$new_db_name.".".table('admin')."";
        $result=or_query($squery);
        while ($line=pdo_fetch_assoc($result)) $existing_admins[]=$line['adminname'];

        $squery="SELECT * FROM ".$old_db_name.".".table('admin')."";
        $result=or_query($squery);
        while ($line=pdo_fetch_assoc($result)) {
            if (!in_array($line['adminname'],$existing_admins)) {
                $line['pw_update_requested']=1;
                $line['password_crypt']=$line['password'];
                $line=convert_array_to_UTF8($line);
                if ($do_insert) $done=orsee_db_save_array($line,"admin",$line['admin_id'],"admin_id");
            }
        }
    }

    if ($import_admin_log) {
        echo "Importing admin log from ".table('admin_log')."\n";
        copy_table('admin_log', 'log_id');
    }

    if ($import_custom_admin_types) {
        echo "Importing custom admin types from ".table('admin_types')."\n";
        copy_table('admin_types', 'type_id',"WHERE type_name NOT IN (SELECT type_name FROM ".$new_db_name.".".table('admin_types').")",false);
    }

    if ($import_cron_log) {
        echo "Importing cron log from ".table('cron_log')."\n";
        copy_table('cron_log', 'log_id');
    }

    if ($import_experiment_types) {
        echo "Importing experiment types from ".table('experiment_types')."\n";
        copy_table('experiment_types', 'exptype_id');
    }

    if ($import_experiments) {
        echo "Importing experiments from ".table('experiments')."\n";
        $dquery="DELETE FROM ".$new_db_name.".".table('experiments')."";
        if ($do_delete) $done=or_query($dquery);
        $squery="SELECT * FROM ".$old_db_name.".".table('experiments')."";
        $result=or_query($squery);
        $trans_list=array('experiment_class','experimenter_mail','experimenter');
        while ($line=pdo_fetch_assoc($result)) {
            $line['experiment_class']=commalist_to_dbstring($line['experiment_class']);
            $line['experimenter_mail']=experimentercommalist_to_newdbstring($line['experimenter_mail']);
            $line['experimenter']=experimentercommalist_to_newdbstring($line['experimenter']);
            if (isset($old_exptype_name_to_id[$line['experiment_ext_type']]))  $line['experiment_ext_type']=$old_exptype_name_to_id[$line['experiment_ext_type']];
            elseif (isset($old_exptype_name_to_id[trim($line['experiment_ext_type'])]))  $line['experiment_ext_type']=$old_exptype_name_to_id[trim($line['experiment_ext_type'])];
            $line=convert_array_to_UTF8($line);
            if ($do_insert) $done=orsee_db_save_array($line,"experiments",$line['experiment_id'],"experiment_id");
        }
    }

    if ($import_faqs) {
        echo "Importing FAQs from ".table('faqs')."\n";
        copy_table('faqs', 'faq_id');
    }

    if ($import_lab_bookings) {
        echo "Importing non-session lab bookings from ".table('lab_space')." to table ".table('events')."\n";
        // default lab booking category: 1
        $dquery="DELETE FROM ".$new_db_name.".".table('events')."";
        if ($do_delete) $done=or_query($dquery);
        $squery="SELECT * FROM ".$old_db_name.".".table('lab_space')."";
        $result=or_query($squery);
        while ($line=pdo_fetch_assoc($result)) {
            $line['experimenter']=experimentercommalist_to_newdbstring($line['experimenter']);
            $line['event_start']=mult_time_to_sesstime($line,'space_start_');
            $line['event_stop']=mult_time_to_sesstime($line,'space_stop_');
            $line['event_category']=1;
            $line=convert_array_to_UTF8($line);
            if ($do_insert) $done=orsee_db_save_array($line,"events",$line['space_id'],"event_id");
        }
    }

    if ($import_lang_items) {
        $cats=array('experimentclass','experiment_invitation_mail','experiment_type','faq_answer',
            'faq_question','laboratory','public_content','subjectpool');
        $new_langs=get_languages(); $first=true; $old_langs=array();
        foreach ($cats as $cat) {
            echo "Importing language items for ".$cat." from ".table('lang')."\n";
            $dquery="DELETE FROM ".$new_db_name.".".table('lang')." WHERE content_type='".$cat."'";
            if ($do_delete) $done=or_query($dquery);

            $squery="SELECT * FROM ".$old_db_name.".".table('lang')." WHERE content_type='".$cat."'";
            $result=or_query($squery);
            while ($line=pdo_fetch_assoc($result)) {
                if ($first) {
                    foreach ($line as $k=>$v) {
                        if (!preg_match("(lang_id|content_name|content_type|enabled|order_number)",$k))
                                $old_langs[]=$k;
                    }
                    $first=false;
                }
                foreach ($new_langs as $new_lang) { if (!isset($line[$new_lang])) $line[$new_lang]=$line[$old_langs[0]]; }
                $line=convert_array_to_UTF8($line);
                if ($do_insert) $done=lang__insert_to_lang($line);
            }
        }
    }

    if ($import_userform_select_langs) {
        $protected_cats=array('datetime_format','default_text','experiment_enrolment_conf_mail','experiment_invitation_mail',
                            'experiment_session_reminder_mail','experiment_type','experimentclass',
                            'faq_answer','faq_question','laboratory','lang','mail',
                            'participant_status_error','participant_status_name','participation_status_display_name',
                            'participation_status_internal_name',
                            'payments_type','public_content','subjectpool',
                            'file_upload_category','events_category');
        $dquery="DELETE FROM ".$new_db_name.".".table('lang')." WHERE content_type='field_of_studies' OR content_type='profession'";
        if ($do_delete) $done=or_query($dquery);
        foreach ($pform_mapping as $oldf=>$newf) {
            if ($newf && (!in_array($newf,$protected_cats))) {
                $squery="SELECT * FROM ".$old_db_name.".".table('lang')." WHERE content_type='".$oldf."'";
                $result=or_query($squery); $first=true;
                while ($line=pdo_fetch_assoc($result)) {
                    if ($first) {
                        echo "Importing language items for profile field old: ".$oldf.", new: ".$newf." from ".table('lang')."\n";
                        $first=false;
                    }
                    foreach ($new_langs as $new_lang) { if (!isset($line[$new_lang])) $line[$new_lang]=$line[$old_langs[0]]; }
                    $line['content_type']=$newf;
                    $line=convert_array_to_UTF8($line);
                    if ($do_insert) $done=lang__insert_to_lang($line);
                }
            }
        }
    }

    if ($import_participants) {
        echo "Importing participants from ".table('participants')."\n";
        echo "using mapping:\n";
        foreach ($pform_mapping as $k=>$v) {
            echo $k."->".$v."\n";
        }
        echo "\n";


        $copy_directly=array('participant_id','participant_id_crypt','creation_time','subpool_id',
            'number_reg','number_noshowup','language','remarks','rules_signed');
        $dquery="DELETE FROM ".$new_db_name.".".table('participants')."";
        if ($do_delete) $done=or_query($dquery);
        $squery="SELECT * FROM ".$old_db_name.".".table('participants')."";
        $result=or_query($squery);
        while ($o=pdo_fetch_assoc($result)) {
            $n=array();
            foreach($copy_directly as $cf) if(isset($o[$cf])) $n[$cf]=$o[$cf];
            $oldsubs=explode(",",$o['subscriptions']); $sub_arr=array();
            foreach ($oldsubs as $sub) {
                if (isset($old_exptype_name_to_id[$sub]))  $sub_arr[]=$old_exptype_name_to_id[$sub];
                elseif (isset($old_exptype_name_to_id[trim($sub)]))  $sub_arr[]=$old_exptype_name_to_id[trim($sub)];
            }
            $n['subscriptions']=id_array_to_db_string($sub_arr);

            $n['pending_profile_update_request']='n';

            $n['last_enrolment']=0;
            $n['last_profile_update']=$o['creation_time'];
            $n['last_activity']=max($n['last_enrolment'],$n['last_profile_update']);

            $n['status_id']=$participant_status_mapping[$o['deleted']][$o['excluded']];
            if ($o['deleted']=='y') $n['deletion_time']=time(); else $n['deletion_time']=0;

            if ($replace_tokens=='y') $n['participant_id_crypt']=make_p_token(get_entropy($n));

            foreach ($pform_mapping as $oldf=>$newf) {
                if ($newf && isset($o[$oldf])) {
                    $n[$newf]=$o[$oldf];
                }
            }
            $n=convert_array_to_UTF8($n);
            if ($do_insert) $done=orsee_db_save_array($n,"participants",$n['participant_id'],"participant_id");
        }
    }

    if ($import_sessions) {
        echo "Importing sessions from ".table('sessions')."\n";
        $dquery="DELETE FROM ".$new_db_name.".".table('sessions')."";
        if ($do_delete) $done=or_query($dquery);
        $squery="SELECT * FROM ".$old_db_name.".".table('sessions')." WHERE session_id>0";
        $result=or_query($squery); $completed_sessions=array();
        while ($line=pdo_fetch_assoc($result)) {
            $line['session_start']=mult_time_to_sesstime($line,'session_start_');
            if ($line['session_finished']=='y') {
                $line['session_status']='completed';
                $completed_sessions[]=$line['session_id'];
            } else $line['session_status']='live';
            $line=convert_array_to_UTF8($line);
            if ($do_insert) $done=orsee_db_save_array($line,"sessions",$line['session_id'],"session_id");
        }
    }

    if ($import_participant_log) {
        echo "Importing participant log from ".table('participants_log')."\n";
        copy_table('participants_log', 'log_id');
    }

    if ($import_participate_at) {
        echo "Importing participation history from ".table('participate_at')."\n";
        $dquery="DELETE FROM ".$new_db_name.".".table('participate_at')."";
        if ($do_delete) $done=or_query($dquery);
        $squery="SELECT * FROM ".$old_db_name.".".table('participate_at')."";
        $result=or_query($squery);
        while ($line=pdo_fetch_assoc($result)) {
            if ($line['invited']=='y') $line['invited']=1;
            else $line['invited']=0;
            if ($line['session_id']>0 && in_array($line['session_id'],$completed_sessions))
                $line['pstatus_id']=$participation_mapping[$line['shownup']][$line['participated']];
            else $line['pstatus_id']=0;
            $line=convert_array_to_UTF8($line);
            if ($do_insert) $done=orsee_db_save_array($line,"participate_at",$line['participate_id'],"participate_id");
        }
    }

    if ($import_subpools) {
        echo "Importing sub-subject pools from ".table('subpools')."\n";
        $dquery="DELETE FROM ".$new_db_name.".".table('subpools')."";
        if ($do_delete) $done=or_query($dquery);
        $squery="SELECT * FROM ".$old_db_name.".".table('subpools');
        $result=or_query($squery);
        while ($line=pdo_fetch_assoc($result)) {
            $oldets=explode(",",$line['experiment_types']); $et_arr=array();
            foreach ($oldets as $et) {
                if (isset($old_exptype_name_to_id[$et]))  $et_arr[]=$old_exptype_name_to_id[$et];
                elseif (isset($old_exptype_name_to_id[trim($et)]))  $et_arr[]=$old_exptype_name_to_id[trim($et)];
            }
            $line['experiment_types']=id_array_to_db_string($et_arr);
            $line=convert_array_to_UTF8($line);
            if ($do_insert) $done=orsee_db_save_array($line,"subpools",$line['subpool_id'],"subpool_id");
        }
    }

    if ($import_files) {
        echo "Importing uploaded files from ".table('uploads')." and ".table('uploads_data')."\n";
        $filecat_map=array('instructions'=>1,'data_files'=>2,'programs'=>4,'paper'=>5,'presentations'=>6,'other'=>7);
        $new_langs=get_languages();
        $dquery="DELETE FROM ".$new_db_name.".".table('uploads')." WHERE upload_id>1";
        if ($do_delete) $done=or_query($dquery);
        $squery="SELECT * FROM ".$old_db_name.".".table('uploads');
        $result=or_query($squery); $now=time(); $i=0;
        while ($line=pdo_fetch_assoc($result)) {
            if ($line['upload_type']) {
                if(isset($filecat_map[$line['upload_type']])) $line['upload_type']=$filecat_map[$line['upload_type']];
                else {
                    $id=$now+$i;
                    $lline=array();
                    $lline['content_type']='file_upload_category';
                    $lline['content_name']=$id;
                    foreach ($new_langs as $tl) $lline[$tl]=$line['upload_type'];
                    if ($do_insert) $done=lang__insert_to_lang($lline);
                    $i++;
                    $line['upload_type']=$id;
                }
            } else $line['upload_type']=7;
            $line=convert_array_to_UTF8($line);
            if ($do_insert) $done=orsee_db_save_array($line,"uploads",$line['upload_id'],"upload_id");
        }

        $dquery="DELETE FROM ".$new_db_name.".".table('uploads_data')."";
        if ($do_delete) $done=or_query($dquery);
        $iquery="INSERT INTO ".$new_db_name.".".table('uploads_data')."
                SELECT * FROM ".$old_db_name.".".table('uploads_data')."";
        if ($do_insert) $done=or_query($iquery);
    }

    if ($update_last_enrolment_date) {
        echo "Updating last_enrolment in ".table('participants')." with last session's date\n";
        // last enrolements separately
        $squery="SELECT p.participant_id, max(s.session_start) as last_sess
                FROM ".$new_db_name.".".table('participate_at')." as p,  ".$new_db_name.".".table('sessions')." as s
                WHERE p.session_id>0
                AND p.session_id=s.session_id
                GROUP BY p.participant_id";
        $result=or_query($squery); $pars=array();
        while ($line=pdo_fetch_assoc($result)) {
            $pars[]=array(':participant_id'=>$line['participant_id'],
                        ':last_enrolment'=>ortime__sesstime_to_unixtime($line['last_sess'])
                        );
        }
        $uquery="UPDATE ".$new_db_name.".".table('participants')."
                 SET last_enrolment = :last_enrolment
                 WHERE participant_id = :participant_id";
        if ($do_insert) $result=or_query($uquery,$pars);
    }
}

?>
