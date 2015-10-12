<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="delete_participant_status";
include ("header.php");
if ($proceed) {
    if (isset($_REQUEST['status_id'])) $status_id=$_REQUEST['status_id']; else $status_id="";
    if (!$status_id) redirect ('admin/participant_status_main.php');
}

if ($proceed) {
    $status=orsee_db_load_array("participant_statuses",$status_id,"status_id");
    if (!isset($status['status_id'])) redirect ('admin/participant_status_main.php');
}

if ($proceed) {
    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot'])
        redirect ('admin/participant_status_edit.php?status_id='.$status_id);
}

if ($proceed) {
    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
        else $reallydelete=false;

    $allow=check_allow('participantstatus_delete','participant_status_edit.php?status_id='.$status_id);
}

if ($proceed) {
    // load status details
    $pars=array(':status_id'=>$status_id);
    $query="SELECT * from ".table('lang')." WHERE content_type='participant_status_name' AND content_name= :status_id";
    $status_name=orsee_query($query,$pars);
    $query="SELECT * from ".table('lang')." WHERE content_type='participant_status_error' AND content_name= :status_id";
    $status_error=orsee_query($query,$pars);

    if ($status['is_default_active']=="y" || $status['is_default_inactive']=="y") {
        message(lang('cannot_delete_participant_status_which_is_default'));
        redirect ('admin/participant_status_edit.php?status_id='.$status_id);
    }
}

if ($proceed) {
    // load languages
    $languages=get_languages();
    foreach ($languages as $language) {
        $status['name_'.$language]=$status_name[$language];
        $status['error_'.$language]=$status_error[$language];
    }

    if ($reallydelete) {
        $participant_statuses=participant_status__get_statuses();
        if (!isset($_REQUEST['merge_with']) || !isset($participant_statuses[$_REQUEST['merge_with']])) {
            redirect ('admin/participant_status_delete.php?status_id='.$status_id);
        } else {
            $merge_with=$_REQUEST['merge_with'];
            // transaction?
            $pars=array(':status_id'=>$status_id,':merge_with'=>$merge_with);
            $query="UPDATE ".table('participants')."
                    SET status_id= :merge_with
                    WHERE status_id= :status_id";
            $result=or_query($query,$pars);

            $pars=array(':status_id'=>$status_id);
            $query="DELETE FROM ".table('participant_statuses')."
                    WHERE status_id= :status_id";
            $result=or_query($query,$pars);

            $query="DELETE FROM ".table('lang')."
                    WHERE content_name= :status_id
                    AND content_type='participant_status_name'";
            $result=or_query($query,$pars);

            $query="DELETE FROM ".table('lang')."
                    WHERE content_name= :status_id
                    AND content_type='participant_status_error'";
            $result=or_query($query,$pars);

            log__admin("participant_status_delete","status_id:".$status['status_id']);
            message (lang('participant_status_deleted_part_moved_to').' "'.$participant_statuses[$merge_with]['name'].'".');
            redirect ("admin/participant_status_main.php");
        }
    }
}


if ($proceed) {
    // form

    echo '  <CENTER>
            <FORM action="participant_status_delete.php">
            <INPUT type="hidden" name="status_id" value="'.$status_id.'">
            <TABLE class="or_formtable">
                <TR><TD colspan="2">
                    <TABLE width="100%" border=0 class="or_panel_title"><TR>
                            <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
                                '.lang('delete_participant_status').' "'.$status_name[lang('lang')].'"
                            </TD>
                    </TR></TABLE>
                </TD></TR>
                <TR>
                    <TD colspan=2>'.lang('really_delete_participant_status?').'<BR><BR>';
    dump_array($status);
    echo '          </TD>
                </TR>
                <TR>
                <TD align=left colspan=2>
                '.lang('merge_participant_status_with').'
                '.participant_status__select_field('merge_with','',array(0,$status_id)).'
                <BR>
                <INPUT class="button" type=submit name=reallydelete value="'.lang('yes_delete').'">
                </TD>
            </TR>
            <TR>
                <TD align=center colspan=2><BR><BR>
                    <INPUT class="button" type=submit name=betternot value="'.lang('no_sorry').'">
                </TD>
            </TR>
            </TABLE>

            </FORM>
            </center>';

}
include ("footer.php");
?>