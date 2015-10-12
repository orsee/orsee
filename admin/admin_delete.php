<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="delete_admin";
include("header.php");
if ($proceed) {

    if (isset($_REQUEST['admin_id']) && $_REQUEST['admin_id']) $admin_id=$_REQUEST['admin_id'];
    else { redirect ("admin/"); $proceed=false; }
}

if ($proceed) {

    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot']) {
        redirect ('admin/admin_edit.php?admin_id='.$admin_id);
        $proceed=false;
    }
}

if ($proceed) {

    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
    else $reallydelete=false;

    $allow=check_allow('admin_delete','admin_edit.php?admin_id='.$admin_id);
}

if ($proceed) {

    $admin=orsee_db_load_array("admin",$admin_id,"admin_id");

    echo '<center>';


    if ($reallydelete) {

        $pars=array(':admin_id'=>$admin_id);
        $query="DELETE FROM ".table('admin')."
                WHERE admin_id= :admin_id";
        $result=or_query($query,$pars);
        log__admin("admin_delete",$admin['adminname']);

        message (lang('admin_deleted').': '.$admin['adminname']);

        redirect ('admin/admin_show.php');
        $proceed=false;
    }

}

if ($proceed) {

    // form

    $num_experiments=experiment__count_experiments("experimenter LIKE :adminname",array(':adminname'=>'%|'.$admin['adminname'].'|%'));

    if ($num_experiments>0) {
        echo lang('admin_delete_warning');
    }

    echo '
        <TABLE class="or_formtable">
            <TR><TD colspan="2">
                <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
                            '.$admin['fname'].' '.$admin['lname'].' ('.$admin['adminname'].')
                        </TD>
                </TR></TABLE>
            </TD></TR>
            <TR>
                <TD colspan=2>
                    '.lang('do_you_really_want_to_delete').'
                    <BR><BR>';
                    dump_array($admin); echo '
                </TD>
            </TR>
            <TR>
                <TD align=left>
                    '.button_link('admin_delete.php?admin_id='.$admin_id.'&reallydelete=true',
                    lang('yes_delete'),'check-square biconred').'
                </TD>
                <TD align=right>
                    '.button_link('admin_delete.php?admin_id='.$admin_id.'&betternot=true',
                    lang('no_sorry'),'undo bicongreen').'
                </TD>
            </TR>
        </TABLE>
        </center>';

}
include ("footer.php");

?>



