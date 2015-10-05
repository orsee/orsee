<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="delete_subpool";
include ("header.php");
if ($proceed) {
    if (isset($_REQUEST['subpool_id'])) $subpool_id=$_REQUEST['subpool_id']; else $subpool_id="";

    if (!$subpool_id || !$subpool_id>1) redirect ('admin/subpool_edit.php?subpool_id='.$subpool_id);

    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot'])
       redirect ('admin/subpool_edit.php?subpool_id='.$subpool_id);
}

if ($proceed) {
    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
    else $reallydelete=false;

    $allow=check_allow('subjectpool_delete','subpool_edit.php?subpool_id='.$subpool_id);
}

if ($proceed) {
    // load languages
    $languages=get_languages();
    $exptypes=load_external_experiment_types();

    // load subject pool
    $subpool=orsee_db_load_array("subpools",$subpool_id,"subpool_id");
    if (!isset($subpool['subpool_id'])) redirect ("admin/subpool_main.php");
}

if ($proceed) {
    $exptype_ids=db_string_to_id_array($subpool['experiment_types']);
    $subpool['exptypes']=array();
    foreach ($exptype_ids as $exptype_id) {
            $subpool['exptypes'][]=$exptypes[$exptype_id][lang('lang')];
    }
    unset($subpool['experiment_types']);
    $pars=array(':subpool_id'=>$subpool_id);
    $query="SELECT * from ".table('lang')." WHERE content_type='subjectpool' AND content_name= :subpool_id";
    $selfdesc=orsee_query($query,$pars);
    foreach ($languages as $language) $subpool['selfdesc_'.$language]=$selfdesc[$language];

    echo '<center>';

    if ($reallydelete) {

        if (isset($_REQUEST['merge_with']) && $_REQUEST['merge_with']) $merge_with=$_REQUEST['merge_with']; else $merge_with=1;
        $subpools=subpools__get_subpools();
        if (!isset($subpools[$merge_with])) redirect ("admin/subpool_main.php");
        else {
            // transaction?
            $pars=array(':subpool_id'=>$subpool_id);
            $query="DELETE FROM ".table('subpools')."
                    WHERE subpool_id= :subpool_id";
            $result=or_query($query,$pars);

            $pars=array(':subpool_id'=>$subpool_id);
            $query="DELETE FROM ".table('lang')."
                    WHERE content_name= :subpool_id
                    AND content_type='subjectpool'";
            $result=or_query($query,$pars);

            $pars=array(':subpool_id'=>$subpool_id,':merge_with'=>$merge_with);
            $query="UPDATE ".table('participants')."
                    SET subpool_id= :merge_with
                    WHERE subpool_id= :subpool_id";
            $result=or_query($query,$pars);

            log__admin("subjectpool_delete","subjectpool:".$subpool['subpool_name']);
            message (lang('subpool_deleted_part_moved_to').' "'.$subpools[$merge_with]['subpool_name'].'".');
            redirect ("admin/subpool_main.php");
        }
    }
}

if ($proceed) {
        // form
        echo '  <CENTER>
                <FORM action="subpool_delete.php">
                <INPUT type=hidden name="subpool_id" value="'.$subpool_id.'">
                <TABLE class="or_formtable">
                <TR><TD colspan="2">
                <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
                            '.lang('delete_subpool').' "'.$subpool['subpool_name'].'"
                        </TD>
                </TR></TABLE>
                </TD></TR>

                <TR>
                    <TD colspan=2>
                        '.lang('really_delete_subpool?').'
                                <BR><BR>';
        dump_array($subpool);
        echo '</TD></TR>
                <TR><TD align=left colspan=2>
                    <INPUT class="button" type=submit name=reallydelete value="'.lang('yes_delete').'">
                <BR>'.lang('merge_subject_pool_with').' ';
        echo subpools__select_field("merge_with","1",array($subpool_id));
        echo '      </TD></TR><TR>
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