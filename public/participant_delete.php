<?php
// part of orsee. see orsee.org
ob_start();
$menu__area="my_data";
$title="delete_participant";
include("header.php");
if ($proceed) {
    if (isset($_REQUEST['betternot'])) redirect("public/participant_edit.php?p=".urlencode($participant['participant_id_crypt']));
}

if ($proceed) {
    $form=true;
    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']=="12345" && isset($_REQUEST['doit'])) {
        $default_inactive_status=participant_status__get("is_default_inactive");
        $pars=array(':participant_id'=>$participant_id,':default_inactive_status'=>$default_inactive_status);
        $query="UPDATE ".table('participants')."
                SET status_id= :default_inactive_status,
                deletion_time='".time()."'
                WHERE participant_id= :participant_id";
        $done=or_query($query,$pars);
        log__participant("delete",$participant_id);
        $form=false;
        message (lang('removed_from_invitation_list'));
        redirect("public/");
    }
}

if ($proceed) {
    if ($form) {
        echo '<center>

            <FORM action="participant_delete.php">
            <INPUT type=hidden name="p" value="'.$participant['participant_id_crypt'].'">
            <TABLE class="or_formtable">
            <TR>
            <TD colspan=2><INPUT name=reallydelete type=hidden value="12345">
            '.lang('do_you_really_want_to_unsubscribe').'<BR></TD>
            </TR>
            <TR><TD>
            <INPUT class="button" type=submit name=doit value="'.lang('yes_i_want').'">
            </TD>
            <TD><INPUT class="button" type=submit name=betternot value="'.lang('no_sorry').'">
            </TD>
            </TR>
            </TABLE>
            </FORM>
            </center>';
    }

}
include("footer.php");
?>