<?php
// part of orsee. see orsee.org
ob_start();

$title="delete_experiment";
include("header.php");

if ($proceed) {
    if (isset($_REQUEST['experiment_id']) && $_REQUEST['experiment_id']) $experiment_id=$_REQUEST['experiment_id'];
    else redirect ("admin/");
}

if ($proceed) {
    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot']) redirect ('admin/experiment_edit.php?experiment_id='.$experiment_id);
}

if ($proceed) {
    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
    else $reallydelete=false;

    $allow=check_allow('experiment_delete','experiment_edit.php?experiment_id='.$experiment_id);
    if (!check_allow('experiment_restriction_override')) check_experiment_allowed($experiment,"admin/experiment_show.php?experiment_id=".$experiment_id);
}

if ($proceed) {
    $experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");

    if ($reallydelete) {

        $pars=array(':experiment_id'=>$experiment_id);
        $query="DELETE FROM ".table('experiments')."
                WHERE experiment_id= :experiment_id";
        $result=or_query($query,$pars);

        $query="DELETE FROM ".table('sessions')."
                WHERE experiment_id= :experiment_id";
        $result=or_query($query,$pars);

        $query="DELETE FROM ".table('participate_at')."
                WHERE experiment_id= :experiment_id";
        $result=or_query($query,$pars);

        $query="DELETE FROM ".table('lang')."
                WHERE content_type='experiment_invitation_mail'
                AND content_name= :experiment_id";
        $result=or_query($query,$pars);

        message (lang('experiment_deleted'));
        log__admin("experiment_delete","experiment:".$experiment['experiment_name']);
        redirect ('admin/experiment_main.php');
    }
}

if ($proceed) {
    // form
    echo '<center>
        <TABLE class="or_formtable">
            <TR><TD colspan="2">
                <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
                            '.lang('delete_experiment').' '.$experiment['experiment_name'].'
                        </TD>
                </TR></TABLE>
            </TD></TR>
            <TR>
                <TD colspan=2>
                    '.lang('really_delete_experiment').'
                    <BR><BR>';
                    dump_array($experiment); echo '
                </TD>
            </TR>
            <TR>
                <TD align=left>
                    '.button_link('experiment_delete.php?experiment_id='.$experiment_id.'&reallydelete=true',
                    lang('yes_delete'),'check-square biconred').'
                </TD>
                <TD align=right>
                    '.button_link('experiment_delete.php?experiment_id='.$experiment_id.'&betternot=true',
                    lang('no_sorry'),'undo bicongreen').'
                </TD>
            </TR>
        </TABLE>
        </center>';

}
include ("footer.php");
?>