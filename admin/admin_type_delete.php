<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="delete_admin_type";
include("header.php");

if ($proceed) {

    if (isset($_REQUEST['type_id']) && $_REQUEST['type_id']) $type_id=$_REQUEST['type_id'];
    else {
        redirect ('admin/admin_type_show.php');
        $proceed=false;
    }

}

if ($proceed) {
    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
    else $reallydelete=false;

    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot']) {
        redirect ('admin/admin_type_edit.php?type_id='.$type_id);
        $proceed=false;
    }
}

if ($proceed) {
    $allow=check_allow('admin_type_delete','admin_type_edit.php?type_id='.$type_id);
}

if ($proceed) {
    $type=orsee_db_load_array("admin_types",$type_id,"type_id");
    if (!isset($type['type_id'])) redirect ('admin/admin_type_show.php');
}

if ($proceed) {
    if ($reallydelete) {

        if (isset($_REQUEST['stype']) && $_REQUEST['stype']) $stype=$_REQUEST['stype']; else $stype='';
        if ($stype) $stype_type=orsee_db_load_array("admin_types",$stype,"type_id");

        if (!isset($stype_type['type_id'])) {
            message("No target type id provided!");
            redirect ('admin/admin_type_edit.php?type_id='.$type_id);
            $proceed=false;
        } else {

            if ($stype==$type_id) {
                message (lang('type_to_be_deleted_cannot_be_type_to_substitute'));
                redirect ('admin/admin_type_delete.php?type_id='.$type_id);
                $proceed=false;
            }

            if ($proceed) {
                // update admins
                $pars=array(':new_type'=>$stype_type['type_name'],
                            ':old_type'=>$type['type_name']);
                $query="UPDATE ".table('admin')." SET admin_type= :new_type
                        WHERE admin_type= :old_type";
                $done=or_query($query,$pars);

                // delete admin type
                $query="DELETE FROM ".table('admin_types')."
                        WHERE type_id='".$type_id."'";
                $done=or_query($query);

                // bye, bye
                message (lang('admin_type_deleted').': '.$type['type_name']);
                log__admin("admin_type_delete","admintype:".$type['type_name'].", replacedby:".$stype_type['type_name']);
                redirect ('admin/admin_type_show.php');
                $proceed=false;
            }
        }
    }
}

if ($proceed) {
    echo '<center>';


    // confirmation form

    echo '
        <FORM action="admin_type_delete.php">
        <INPUT type=hidden name="type_id" value="'.$type_id.'">

        <TABLE class="or_formtable">
            <TR><TD colspan="2">
                <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
                            '.lang('delete_admin_type').' '.$type['type_name'].'
                        </TD>
                </TR></TABLE>
            </TD></TR>
            <TR>
                <TD colspan=2>
                    '.lang('do_you_really_want_to_delete').'
                    <BR><BR>
                </TD>
            </TR>
            <TR>
                <TD align=right>
                    '.lang('copy_admins_of_this_type_to').':
                </TD>
                <TD>';
                    echo admin__select_admin_type("stype",$settings['default_admin_type'],"type_id",array($type_id));
            echo '  </TD>
            </TR>
            <TR>
                <TD align=left>
                    <INPUT class="button" type="submit" name="reallydelete" value="'.lang('yes_delete').'">
                </TD>
                <TD align=right>
                    <INPUT class="button" type="submit" name="betternot" value="'.lang('no_sorry').'">
                </TD>
            </TR>
        </TABLE>

        </FORM>
        </center>';

}
include ("footer.php");

?>
