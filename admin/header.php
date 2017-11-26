<?php
// part of orsee. see orsee.org
$debug__script_started=microtime();
include ("../config/settings.php");
include ("../config/system.php");
include ("../config/requires.php");

$proceed=true;
if ($proceed) {
    $document=thisdoc();
    if ($settings__stop_admin_site=="y" && $document!="error_temporarily_disabled.php")
        redirect("admin/error_temporarily_disabled.php");
}

if ($proceed) {
    site__database_config();

    $settings=load_settings();
    $settings['style']=$settings['orsee_admin_style'];
    $color=load_colors();

    session_set_save_handler("orsee_session_open",
                 "orsee_session_close",
                 "orsee_session_read",
                 "orsee_session_write",
                 "orsee_session_destroy",
                 "orsee_session_gc");

    session_start();

    if (isset($_SESSION['expadmindata'])) $expadmindata=$_SESSION['expadmindata']; else $expadmindata=array();

    $tmparr=explode("/",$_SERVER['PHP_SELF']); $tmpnum=count($tmparr);
    $requested_url=$tmparr[$tmpnum-2]."/".$tmparr[$tmpnum-1].'?'.$_SERVER['QUERY_STRING'];

    // Check for login
    if ((!(isset($expadmindata['adminname']) && $expadmindata['adminname'])) && $document!="admin_login.php") {
        redirect ("admin/admin_login.php?requested_url=".urlencode($requested_url));
    }
}

if ($proceed) {

    if (isset($expadmindata['pw_update_requested']) && $expadmindata['pw_update_requested']  && $document!="admin_pw.php") {
        message(lang('please_change_your_password'));
        redirect ("admin/admin_pw.php");
    }
}

if ($proceed) {
    if (isset($_REQUEST['new_language'])) {
        $expadmindata['language']=$_REQUEST['new_language'];
        $_SESSION['expadmindata']=$expadmindata;
    }

    if (!isset($expadmindata['language']))
        $expadmindata['language']=$settings['admin_standard_language'];

    $authdata['language']=$expadmindata['language'];
    $_SESSION['authdata']=$authdata;

    $lang=load_language($expadmindata['language']);

    $done=check_database_upgrade();

    if (!isset($title)) $title="";
    if ($title) $title=lang($title);
    $pagetitle=$settings['default_area'].': '.$title;

    html__header();
    html__show_style_header('admin',$title);

    echo "<center>";

    show_message();

    echo "</center>";
}

?>
