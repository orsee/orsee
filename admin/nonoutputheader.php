<?php
// part of orsee. see orsee.org
$debug__script_started=microtime();
include ("../config/settings.php");
include ("../config/system.php");
include ("../config/requires.php");

error_reporting(0);  // shut down all error reporting

header('X-Frame-Options: SAMEORIGIN');

$proceed=true;
if ($proceed) {
    $document=thisdoc();
    if ($settings__stop_admin_site=="y" && $document!="error_temporaly_disabled.php")
        redirect("errors/error_temporaly_disabled.php");
}


$createNewCsrfToken = false;

$_REQUEST = stripTagsRequestArray($_REQUEST, array('requested_url', 'sign'));
if(isset($_REQUEST['requested_url'])) {
    $_REQUEST['requested_url'] = strip_tags( $_REQUEST['requested_url']);
}




if ($proceed) {
    site__database_config();
    $settings=load_settings();
    $settings['style']=$settings['orsee_admin_style'];
    $color=load_colors();
    session_set_save_handler("orsee_session_open", "orsee_session_close", "orsee_session_read", "orsee_session_write", "orsee_session_destroy", "orsee_session_gc");
    session_start();

    $currentCookieParams = session_get_cookie_params();

    // set cookie flags secure and http
    session_set_cookie_params(
        $currentCookieParams['lifetime'],
        $currentCookieParams['path'],
        $currentCookieParams['domain'],
        true,
        true
    );

    // if only csrf security when logging in is wanted, take out commented section in next line
    if( $_SERVER['REQUEST_METHOD'] == 'POST') {// AND getRefererFileName() == 'admin_login' ) {
        if(!isset($_REQUEST["csrf_token"])) {
            exit;
        }
        elseif(! hash_equals($_SESSION["csrf_token"], $_REQUEST["csrf_token"])) {
            exit;
        }
        else { // after successful comparison recreate token
            $createNewCsrfToken = true;
        }
    }

    if (isset($_SESSION['expadmindata'])) $expadmindata=$_SESSION['expadmindata']; else $expadmindata=array();

    // Check for login
    if ((!(isset($expadmindata['adminname']) && $expadmindata['adminname'])) && $document!="admin_login.php") {
            redirect ("admin/admin_login.php");
            $proceed=false;
    }
}

if ($proceed) {
    if(!isset($_SESSION['csrf_token']) OR $createNewCsrfToken) {
        // new token to be taken into each form
        if (function_exists('mcrypt_create_iv')) {
            $_SESSION['csrf_token'] = bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
        } else {
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }

    if (isset($_REQUEST['new_language'])) {
        $expadmindata['language']=$_REQUEST['new_language'];
        $_SESSION['expadmindata']=$expadmindata;
    }

    if (!isset($expadmindata['language']))
        $expadmindata['language']=$settings['admin_standard_language'];

    $authdata['language']=$expadmindata['language'];
    $_SESSION['authdata']=$authdata;

    $lang=load_language($expadmindata['language']);

    if (!isset($title)) $title="";
    $pagetitle=$settings['default_area'].': '.$title;
}
?>
