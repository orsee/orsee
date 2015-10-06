<?php
// part of orsee. see orsee.org
ob_start();

$title="delete_session";
include("header.php");
if ($proceed) {
     if (isset($_REQUEST['session_id']) && $_REQUEST['session_id']) $session_id=$_REQUEST['session_id'];
     else redirect ("admin/");
}

if ($proceed) {
    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot'])
    redirect ('admin/session_edit.php?session_id='.$session_id);
}

if ($proceed) {
    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
    else $reallydelete=false;

    $session=orsee_db_load_array("sessions",$session_id,"session_id");

    $reg=experiment__count_participate_at($session['experiment_id'],$session_id);

    if ($reg>0) $allow=check_allow('session_nonempty_delete','session_edit.php?session_id='.$session_id);
    else if (!check_allow('session_nonempty_delete'))
            check_allow('session_empty_delete','session_edit?session_id='.$session_id);
}

if ($proceed) {
    if (!check_allow('experiment_restriction_override'))
        check_experiment_allowed($session['experiment_id'],"admin/experiment_show.php?experiment_id=".$session['experiment_id']);
}

if ($proceed) {


    if ($reallydelete) {
        // transaction?
        $pars=array(':session_id'=>$session_id);
        $query="UPDATE ".table('participate_at')."
                SET session_id='0', pstatus_id=0
                WHERE session_id= :session_id";
        $result=or_query($query,$pars);

        $pars=array(':session_id'=>$session_id);
        $query="DELETE FROM ".table('sessions')."
                WHERE session_id= :session_id";
        $result=or_query($query,$pars);

        message (lang('session_deleted'));
        log__admin("session_delete","session:".session__build_name($session,$settings['admin_standard_language']).
                "\n,session_id:".$session_id);
        redirect ('admin/experiment_show.php?experiment_id='.$session['experiment_id']);
    }
}

if ($proceed) {
    // form
    echo '  <CENTER>
        <TABLE class="or_formtable">
            <TR><TD colspan="2">
                <TABLE width="100%" border=0 class="or_panel_title"><TR>
                                <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'">
                                    '.lang('delete_session').' '.session__build_name($session).'
                                </TD>
                </TR></TABLE>
            </TD></TR>
            <TR>
                <TD colspan=2>
                    '.lang('really_delete_session').'
                    <BR><BR>';
                    dump_array($session); echo '
                </TD>
            </TR>
            <TR>
                <TD align=left>
                    '.button_link('session_delete.php?session_id='.$session_id.'&reallydelete=true',
                    lang('yes_delete'),'check-square biconred').'
                </TD>
                <TD align=right>
                    '.button_link('session_delete.php?session_id='.$session_id.'&betternot=true',
                    lang('no_sorry'),'undo bicongreen').'
                </TD>
            </TR>
        </TABLE>
        </center>';

}
include ("footer.php");
?>