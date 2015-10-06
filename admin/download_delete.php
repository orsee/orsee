<?php
// part of orsee. see orsee.org
ob_start();

$title="delete_download";
include("header.php");
if ($proceed) {
    if (isset($_REQUEST['dl']) && $_REQUEST['dl']) $upload_id=$_REQUEST['dl'];
    else redirect ("admin/");
}

if ($proceed) {
    $upload=orsee_db_load_array("uploads",$upload_id,"upload_id");
    if (!isset($upload['upload_id'])) redirect ('admin/download_main.php');
}

if ($proceed) {
    if ($upload['experiment_id']>0) {
        $experiment_id=$upload['experiment_id'];
        if (!check_allow('experiment_restriction_override'))
            check_experiment_allowed($experiment_id,"admin/experiment_show.php?experiment_id=".$experiment_id);
    } else $experiment_id=0;
}

if ($proceed) {
    if ($experiment_id>0) {
        $experiment=orsee_db_load_array("experiments",$experiment_id,"experiment_id");
        if (!isset($experiment['experiment_id'])) $experiment_id=0;
    }
}

if ($proceed) {
    if ($experiment_id>0) {
        $experimenters=db_string_to_id_array($experiment['experimenter']);
        if (! ((in_array($expadmindata['admin_id'],$experimenters) && check_allow('file_delete_experiment_my'))
                || check_allow('file_delete_experiment_all')) ) {
            redirect('admin/experiment_show.php?experiment_id='.$experiment_id);
        }
    } else {
        $allow=check_allow('file_delete_general','download_main.php');
    }
}


if ($proceed) {
    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot']) {
        if ($experiment_id) redirect ('admin/download_main.php?experiment_id='.urlencode($experiment_id));
        else redirect ('admin/download_main.php');
        $proceed=false;
    }
}

if ($proceed) {

    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
    else $reallydelete=false;

    if ($reallydelete) {

        $pars=array(':upload_id'=>$upload_id);
        $query="DELETE FROM ".table('uploads')."
                WHERE upload_id= :upload_id";
        $result=or_query($query,$pars);
        $query="DELETE FROM ".table('uploads_data')."
                WHERE upload_id= :upload_id";
        $result=or_query($query,$pars);
        $target= ($experiment_id) ? "experiment_id:".$experiment_id : "general";
        log__admin("file_delete",$target);
        message (lang('download_deleted'));
        redirect ('admin/download_main.php');
        $proceed=false;
    }
}

if ($proceed) {

    // form
    echo '  <CENTER>
        <TABLE class="or_formtable">
            <TR>
                <TD colspan=2>
                    '.lang('do_you_really_want_to_delete').'
                    <BR><BR>';
                    dump_array($upload); echo '
                </TD>
            </TR>
            <TR>
                <TD align=left>
                    '.button_link('download_delete.php?dl='.$upload_id.'&reallydelete=true',
                    lang('yes_delete'),'check-square biconred').'
                </TD>
                <TD align=right>
                    '.button_link('download_delete.php?dl='.$upload_id.'&betternot=true',
                    lang('no_sorry'),'undo bicongreen').'
                </TD>
            </TR>
        </TABLE>
        </center>';

}
include ("footer.php");
?>