<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="emails";
$title="emails";
$jquery=array('popup');
include ("header.php");
if ($proceed) {
    if ($settings['enable_email_module']!='y') redirect ('admin/index.php');
}
if ($proceed) {
    if (check_allow('emails_read_all')) $rmode="all";
    elseif (check_allow('emails_read_assigned')) $rmode="assigned";
    elseif (check_allow('emails_read_experiments')) $rmode="experiments";
    else redirect ('admin/index.php');
}

if ($proceed) {
    $mailboxes=email__load_mailboxes();
    $continue=false; $id=false;
    if (isset($_REQUEST['mode']) &&  $_REQUEST['mode']) {
        $mode=$_REQUEST['mode'];
        if (in_array($mode,array('mailbox','experiment','session','participant'))) {
            if (isset($_REQUEST['id']) &&  $_REQUEST['id']) $id=$_REQUEST['id'];
            if ($mode=='mailbox') {
                if (isset($mailboxes[$id])) $continue=true;
            } elseif ($mode=='experiment') {
                $experiment=orsee_db_load_array("experiments",$id,"experiment_id");
                if (isset($experiment['experiment_id'])) $continue=true;
            } elseif ($mode=='session') {
                $session=orsee_db_load_array("sessions",$id,"session_id");
                if (isset($session['session_id'])) {
                    $continue=true;
                    $experiment=orsee_db_load_array("experiments",$session['experiment_id'],"experiment_id");
                }
            } elseif ($mode=='participant') {
                $participant=orsee_db_load_array("participants",$id,"participant_id");
                if (isset($participant['participant_id'])) $continue=true;
            }
        } elseif ($mode=='trash' && check_allow('emails_trash_view')) {
            $continue=true;
        } elseif ($mode=='listmailboxes') {
            $continue=true;
        }
    }
    if (!$continue) $mode="inbox";
}

if ($proceed) {
    $url_string='mode='.urlencode($mode);
    if ($id) $url_string.='&id='.urlencode($id);

    $action='';
    if (isset($_REQUEST['switch_read']) && $_REQUEST['switch_read'] &&
            isset($_REQUEST['message_id']) && $_REQUEST['message_id']) $action='switch_read_status';
    if (isset($_REQUEST['switch_assigned_to_read']) && $_REQUEST['switch_assigned_to_read'] &&
            isset($_REQUEST['message_id']) && $_REQUEST['message_id']) $action='switch_assigned_to_read_status';
    if ($mode=='trash' && isset($_REQUEST['empty_trash']) && $_REQUEST['empty_trash']) $action='empty_trash';

    if ($action) {
        // allow to perform action
        $redirect="";
        switch ($action) {
            case "switch_read_status":
                $redirect=email__switch_read_status($_REQUEST['message_id'],'read');
                break;
            case "switch_assigned_to_read_status":
                $redirect=email__switch_read_status($_REQUEST['message_id'],'assigned_to_read');
                break;
            case "empty_trash":
                if (check_allow('emails_trash_empty')) {
                    $redirect=email__empty_trash();
                }
                break;
            default:
        }
        if ($redirect) redirect($redirect);
        else redirect('admin/emails_main.php?'.$url_string);
    }
}

if ($proceed) {

    echo '<center>';
    echo '<TABLE width=90% border=0><TR><TD align="left">
                <A HREF="emails_main.php?mode=inbox">'.lang('mailbox_inbox').'</A> |
                <A HREF="emails_main.php?mode=listmailboxes">'.lang('all_mailboxes').'</A>';
    if (check_allow('emails_trash_empty'))echo ' | <A HREF="emails_main.php?mode=trash">'.lang('mailbox_trash').'</A> ';
    echo '</TD></TR>
        <TR><TD align="center">
            <TABLE class="or_page_subtitle" style="width: 100%; background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'">
                <TR><TD align="center">';

    if ($mode=='inbox') echo lang('mailbox_inbox');
    elseif ($mode=='experiment') echo lang('experiment').': '.$experiment['experiment_name'];
    elseif ($mode=='session') echo lang('session').': '.$experiment['experiment_name'].', '.session__build_name($session);
    elseif ($mode=='participant') echo lang('participant').': '.$participant['email'];
    elseif ($mode=='mailbox') echo lang('email_mailbox').': '.$mailboxes[$id];
    elseif ($mode=='listmailboxes') echo lang('all_mailboxes');
    elseif ($mode=='trash') {
        echo lang('mailbox_trash');
        if (check_allow('emails_trash_empty')) echo button_link(thisdoc().'?mode=trash&empty_trash=true',lang('email_empty_trash'),'trash').'<BR>';
    }

    echo '
            </TD></TR></TABLE>
        </TD></TR>
        <TR><TD align="center">';

    // list emails
    if ($mode=='listmailboxes') {
        // show mail boxes
        email__show_mail_boxes();
    } elseif ($mode=='search') {
        // search for emails and list them
    } else {
        echo javascript__email_popup();
        email__list_emails($mode,$id,$rmode,$url_string);
    }

    echo '   </TD></TR></TABLE>
                <br><br>
        </center>';
}

include ("footer.php");
?>