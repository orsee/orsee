<?php

include ("../config/settings.php");
include ("../config/system.php");
include ("../config/requires.php");

site__database_config();

$settings=load_settings();
$settings['style']=$settings['orsee_public_style'];
$color=load_colors();

session_set_save_handler("orsee_session_open", "orsee_session_close", "orsee_session_read", "orsee_session_write", "orsee_session_destroy", "orsee_session_gc");

session_start();
$authdata=$_SESSION['authdata'];

if (isset($_REQUEST['language'])) {
	$authdata['language']=$_REQUEST['language'];
	$_SESSION['authdata']=$authdata;
}

if (!isset($authdata['language'])) {
	$authdata['language']=$settings['public_standard_language'];
	$_SESSION['authdata']=$authdata;
	}

// load and check participant data
$part_load=array("participant_edit.php",
                "participant_delete.php",
                "participant_show.php",
                "participant_show_print.php");

if (in_array(thisdoc(),$part_load)) {

        if (!$_REQUEST['p']) redirect("public/");
        $participant_id=url_cr_decode($_REQUEST['p']);
        if (!$participant_id) redirect("public/");
        if (thisdoc()=="participant_confirm.php")
                $participant=orsee_db_load_array("participants_temp",$participant_id,"participant_id");
          else
                $participant=orsee_db_load_array("participants",$participant_id,"participant_id");

        $authdata['language']=$participant['language'];
        $_SESSION['authdata']=$authdata;
        }

if (!isset($authdata['language'])) $authdata['language']=$settings['public_standard_language'];
$lang=load_language($authdata['language']);

        if (isset($participant) && $participant['excluded']=="y") {
                message ($lang['error_sorry_you_are_excluded']." ".
                        $lang['if_you_have_questions_write_to']." ".support_mail_link());
                redirect("public/");
                }

        if (isset($participant) && $participant['deleted']=="y") {
                message ($lang['error_sorry_you_are_deleted']." ".
                        $lang['if_you_have_questions_write_to']." ".support_mail_link());
                redirect("public/");
                }

if ($settings__stop_public_site=="y" && !isset($umadmindata['username']) && !(thisdoc()=="error_temporaly_disabled.php")) redirect("errors/error_temporaly_disabled.php");

$pagetitle=$settings['default_area'].': '.$title;

?>
