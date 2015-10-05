<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="delete_participation_status";
include ("header.php");
if ($proceed) {
    if (isset($_REQUEST['pstatus_id'])) $pstatus_id=$_REQUEST['pstatus_id']; else $pstatus_id="";
    if ($pstatus_id!='' && $pstatus_id==0) {
        redirect ('admin/participation_status_edit.php?pstatus_id='.$pstatus_id);
    } elseif (!$pstatus_id) redirect ('admin/participation_status_main.php');
}
if ($proceed) {
    $pstatus=orsee_db_load_array("participation_statuses",$pstatus_id,"pstatus_id");
    if (!isset($pstatus['pstatus_id'])) redirect ('admin/participation_status_main.php');
}

if ($proceed) {
    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot'])
        redirect ('admin/participation_status_edit.php?pstatus_id='.$pstatus_id);
}

if ($proceed) {
    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
        else $reallydelete=false;
    $allow=check_allow('participationstatus_delete','participation_status_edit.php?pstatus_id='.$pstatus_id);
}

if ($proceed) {
    // load status
    $pars=array(':pstatus_id'=>$pstatus_id);
    $query="SELECT * from ".table('lang')." WHERE content_type='participation_status_internal_name' AND content_name= :pstatus_id";
    $pstatus_internal_name=orsee_query($query,$pars);
    $query="SELECT * from ".table('lang')." WHERE content_type='participation_status_display_name' AND content_name= :pstatus_id";
    $pstatus_display_name=orsee_query($query,$pars);


    // load languages
    $languages=get_languages();
    foreach ($languages as $language) {
        $pstatus['internal_name_'.$language]=$pstatus_internal_name[$language];
        $pstatus['display_name_'.$language]=$pstatus_display_name[$language];
    }

    if ($reallydelete) {

        $participation_statuses=expregister__get_participation_statuses();
        if (!isset($_REQUEST['merge_with']) || !isset($participation_statuses[$_REQUEST['merge_with']])) {
            redirect ('admin/participation_status_delete.php?pstatus_id='.$pstatus_id);
        } else {
            $merge_with=$_REQUEST['merge_with'];

            // transaction?
            $pars=array(':pstatus_id'=>$pstatus_id,':merge_with'=>$merge_with);
            $query="UPDATE ".table('participate_at')."
                    SET pstatus_id= :merge_with
                    WHERE pstatus_id= :pstatus_id";
            $result=or_query($query,$pars);

            $pars=array(':pstatus_id'=>$pstatus_id);
            $query="DELETE FROM ".table('participation_statuses')."
                    WHERE pstatus_id= :pstatus_id";
            $result=or_query($query,$pars);

            $pars=array(':pstatus_id'=>$pstatus_id);
            $query="DELETE FROM ".table('lang')."
                    WHERE content_name= :pstatus_id
                    AND content_type='participation_status_internal_name'";
            $result=or_query($query,$pars);

            $pars=array(':pstatus_id'=>$pstatus_id);
            $query="DELETE FROM ".table('lang')."
                    WHERE content_name= :pstatus_id
                    AND content_type='participation_status_display_name'";
            $result=or_query($query,$pars);

            log__admin("participation_status_delete","pstatus:".$pstatus_internal_name[lang('lang')].' ('.$pstatus['pstatus_id']."), ".
                        "merged_to:".$participation_statuses[$merge_with]['internal_name'].' ('.$merge_with.")");
            message (lang('participation_status_deleted_part_moved_to').' "'.$participation_statuses[$merge_with]['internal_name'].'".');
            redirect ("admin/participation_status_main.php");
        }
    }
}

if ($proceed) {
        // form

        echo '  <CENTER>
                <FORM action="participation_status_delete.php">
                <INPUT type="hidden" name="pstatus_id" value="'.$pstatus_id.'">

                <TABLE class="or_formtable">
                    <TR><TD colspan="2">
                        <TABLE width="100%" border=0 class="or_panel_title"><TR>
                                <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
                                    '.lang('delete_participation_status').' "'.$pstatus_internal_name[lang('lang')].'"
                                </TD>
                        </TR></TABLE>
                    </TD></TR>
                    <TR>
                        <TD colspan=2>
                                '.lang('really_delete_participation_status?').'<BR><BR>';
            dump_array($pstatus);
            echo '
                        </TD>
                    </TR>';

            $pstatus_internal_names=lang__load_lang_cat('participation_status_internal_name');

            echo '
                        <TR>
                            <TD align=left colspan=2>
                            <INPUT class="button" type=submit name=reallydelete value="'.lang('yes_delete').'">
                    <BR>
                    '.lang('merge_participation_status_with').'
                    '.expregister__participation_status_select_field('merge_with','',array($pstatus_id)).'
                    </TD></TR>
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