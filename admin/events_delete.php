<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="experiment_calendar";
$title="delete_lab_reservation";
include("header.php");
if ($proceed) {
    if (isset($_REQUEST['event_id']) && $_REQUEST['event_id']) $event_id=$_REQUEST['event_id'];
    else redirect ("admin/");
}

if ($proceed) {
    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot']) redirect ('admin/events_edit.php?event_id='.$event_id);
}

if ($proceed) {
    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
    else $reallydelete=false;

    $allow=check_allow('events_delete','events_edit.php?event_id='.$event_id);
}

if ($proceed) {
    $space=orsee_db_load_array("events",$event_id,"event_id");

    if ($reallydelete) {
        $pars=array('event_id'=>$event_id);
        $query="DELETE FROM ".table('events')."
                WHERE event_id= :event_id";
        $result=or_query($query,$pars);
        log__admin("events_delete","event_id:".$event_id);
        message (lang('lab_reservation_deleted'));
        redirect ('admin/calendar_main.php');
    }
}

if ($proceed) {
    // form
    echo '  <CENTER>
        <TABLE>
            <TR>
                <TD colspan=2>
                    '.lang('do_you_really_want_to_delete').'
                    <BR><BR>';
                    dump_array($space); echo '
                </TD>
            </TR>
            <TR>
                <TD align=left>
                    '.button_link('events_delete.php?event_id='.$event_id.'&reallydelete=true',
                    lang('yes_delete'),'check-square biconred').'
                </TD>
                <TD align=right>
                    '.button_link('events_delete.php?event_id='.$event_id.'&betternot=true',
                    lang('no_sorry'),'undo bicongreen').'
                </TD>
            </TR>
        </TABLE>
        </center>';

}
include ("footer.php");
?>