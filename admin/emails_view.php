<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="emails";
$title="view email";
$jquery=array('popup','arraypicker','textext','switchy');

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
    if ($settings['enable_email_module']!='y') redirect ('admin/index.php');
}
if ($proceed) {
    //$allow=check_allow('emails_show_all','emails_main.php');
}

if ($proceed) {
    if (isset($_REQUEST['message_id']) && $_REQUEST['message_id']) $message_id=$_REQUEST['message_id']; else $message_id='';
    if (!$message_id) redirect('admin/emails_main.php');
}

if ($proceed) {
    $email=orsee_db_load_array("emails",$message_id,"message_id");
    if (!isset($email['message_id'])) redirect('admin/emails_main.php');
}

if ($proceed) {
    if (isset($_REQUEST['update']) && $_REQUEST['update']) {
        $action='update';
    } elseif (isset($_REQUEST['send']) && $_REQUEST['send']) {
        $action='send';
    } elseif (isset($_REQUEST['addnote']) && $_REQUEST['addnote']) {
        $action='addnote';
    } elseif (isset($_REQUEST['delete']) && $_REQUEST['delete']) {
        $action='delete';
    } elseif (isset($_REQUEST['undelete']) && $_REQUEST['undelete']) {
        $action='undelete';
    } else $action=false;

    $open_reply=false; $open_note=false;
    // show email or perform an action
    if ($action) {
        // allow to perform action
        $redirect="";
        switch ($action) {
            case "update":
                if (email__is_allowed($email,array(),'change')) {
                    $redirect=email__update_email($email);
                }
                break;
            case "delete":
                if (email__is_allowed($email,array(),'delete')) {
                    $redirect=email__delete_undelete_email($email,'delete');
                }
                break;
            case "undelete":
                if (email__is_allowed($email,array(),'delete')) {
                    $redirect=email__delete_undelete_email($email,'undelete');
                }
                break;
            case "send":
                if (email__is_allowed($email,array(),'reply')) {
                    $open_reply=true;
                    $redirect=email__send_reply_email($email);
                }
                break;
            case "addnote":
                if (email__is_allowed($email,array(),'note')) {
                    $open_note=true;
                    $redirect=email__add_internal_note($email);
                }
                break;
            default:
        }
        if ($redirect) redirect($redirect);
    }
}

if ($proceed) {
    // show email
    echo '<BR><BR><center><TABLE width="80%" border=0><TR><TD align="center">';
    email__show_email($email,$open_reply,$open_note);
    echo '</td></tr></table>';
    echo '<br><br</center>';
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