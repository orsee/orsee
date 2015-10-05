<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="delete_experiment_type";
include ("header.php");
if ($proceed) {

    if (isset($_REQUEST['exptype_id'])) $exptype_id=$_REQUEST['exptype_id']; else $exptype_id="";

    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot'])
        redirect ('admin/experiment_type_edit.php?exptype_id='.$exptype_id);
}

if ($proceed) {
    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
    else $reallydelete=false;

    $allow=check_allow('experimenttype_delete','experiment_type_edit.php?exptype_id='.$exptype_id);
}

if ($proceed) {
    $pars=array(':exptype_id'=>$exptype_id);
    $query="SELECT * from ".table('lang')." WHERE content_type='experiment_type' AND content_name= :exptype_id";
    $selfdesc=orsee_query($query,$pars);

    // load subject pool
    $exptype=orsee_db_load_array("experiment_types",$exptype_id,"exptype_id");
    if (!isset($exptype['exptype_id'])) redirect ('admin/experiment_type_main.php');
}

if ($proceed) {
    $exptypes=load_external_experiment_types();
    if (count($exptypes)==1) {
        message (lang('error_cannot_delete_last_experimenttype'));
        redirect ('admin/experiment_type_edit.php?exptype_id='.$exptype_id);
    }
}


if ($proceed) {
    // load languages
    $languages=get_languages();

    foreach ($languages as $language) $exptype[$language]=$selfdesc[$language];

    echo '<center>';

    if ($reallydelete) {

        if (isset($_REQUEST['merge_with']) && $_REQUEST['merge_with']) $merge_with=$_REQUEST['merge_with']; else $merge_with='';
        if ($merge_with) $merge_with_type=orsee_db_load_array("experiment_types",$merge_with,"exptype_id");

        if (!isset($merge_with_type['exptype_id'])) {
            message("No target exptype provided!");
            redirect ('admin/experiment_type_edit.php?exptype_id='.$exptype_id);
        } else {
            $queries=array();
            $tq=array();
            $tq['pars']=array(':exptype_id'=>$exptype_id);
            $tq['query']="DELETE FROM ".table('experiment_types')."
                    WHERE exptype_id= :exptype_id";
            $queries[]=$tq;

            $tq=array();
            $tq['pars']=array(':exptype_id'=>$exptype_id);
            $tq['query']="DELETE FROM ".table('lang')."
                    WHERE content_name= :exptype_id
                    AND content_type='experiment_type'";
            $queries[]=$tq;

            $tq=array(); $tq['pars']=array();
            $pars=array(':exptype_id'=>'%|'.$exptype_id.'|%');
            $query="SELECT participant_id, subscriptions
                    FROM ".table('participants')."
                    WHERE subscriptions LIKE :exptype_id";
            $result=or_query($query,$pars);
            while ($line=pdo_fetch_assoc($result)) {
                $subs=db_string_to_id_array($line['subscriptions']);
                foreach ($subs as $k=>$et) if ($et==$exptype_id) unset($subs[$k]);
                if (!in_array($merge_with,$subs)) $subs[]=$merge_with;
                $tq['pars'][]=array(
                            ':participant_id'=>$line['participant_id'],
                            ':subscriptions'=>id_array_to_db_string($subs)
                                );
            }
            $affected_participants=count($tq['pars']);
            $tq['query']="UPDATE ".table('participants')."
                    SET subscriptions= :subscriptions
                    WHERE participant_id= :participant_id";
            $queries[]=$tq;

            $tq=array();
            $tq['pars']=array(':merge_with'=>$merge_with,
                        ':exptype_id'=>$exptype_id);
            $tq['query']="UPDATE ".table('experiments')."
                    SET experiment_ext_type= :merge_with
                    WHERE experiment_ext_type= :exptype_id";
            $queries[]=$tq;

            $done=pdo_transaction($queries);
            log__admin("experimenttype_delete","experimenttype:".$exptype['exptype_name']);
            message (lang('experimenttype_deleted'));
            message ($affected_participants.' '.lang('xx_participants_assigned_to_exptype').' "'.$merge_with_type['exptype_name'].'".');
            redirect ("admin/experiment_type_main.php");
        }
    }
}

if ($proceed) {
    // form

        echo '  <CENTER>
                <FORM action="experiment_type_delete.php">
                <INPUT type=hidden name="exptype_id" value="'.$exptype_id.'">

                <TABLE class="or_formtable">
                    <TR><TD colspan="2">
                        <TABLE width="100%" border=0 class="or_panel_title"><TR>
                                <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
                                    '.lang('delete_experiment_type').' "'.$exptype['exptype_name'].'"
                                </TD>
                        </TR></TABLE>
                    </TD></TR>
                        <TR>
                        <TD colspan="2" align="center">
                                        '.lang('do_you_really_want_to_delete').'
                                        <BR><BR>';
                    dump_array($exptype);
            echo '
                                </TD>
                        </TR>
                        <TR>
                            <TD align=left width="50%">
                            '.lang('replace_experimenttype_with').' ';
                    experiment__exptype_select_field("merge_with","exptype_id","exptype_name",
                            "",$exptype['exptype_id']);
            echo '<BR><BR>
                        <INPUT class="button" type="submit" name="reallydelete" value="'.lang('yes_delete').'">';

            echo '      </TD>
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