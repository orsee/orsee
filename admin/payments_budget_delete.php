<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="delete_budget";
include ("header.php");
if ($proceed) {
    if (isset($_REQUEST['budget_id'])) $budget_id=$_REQUEST['budget_id']; else $budget_id="";
    if (!$budget_id) redirect ('admin/payments_budget_main.php');
}

if ($proceed) {
    $budget=orsee_db_load_array("budgets",$budget_id,"budget_id");
    if (!isset($budget['budget_id'])) redirect ('admin/payments_budget_main.php');
}

if ($proceed) {
    if (isset($_REQUEST['betternot']) && $_REQUEST['betternot'])
        redirect ('admin/payments_budget_edit.php?budget_id='.$budget_id);
}

if ($proceed) {
    if (isset($_REQUEST['reallydelete']) && $_REQUEST['reallydelete']) $reallydelete=true;
        else $reallydelete=false;

    $allow=check_allow('payments_budget_delete','payments_budget_edit.php?budget_id='.$budget_id);
}

if ($proceed) {

    if ($reallydelete) {
        $budgets=payments__load_budgets();
        if (!isset($_REQUEST['merge_with']) || !isset($budgets[$_REQUEST['merge_with']])) {
            redirect ('admin/payments_budget_delete.php?budget_id='.$budget_id);
        } else {
            $merge_with=$_REQUEST['merge_with'];
            // transaction?

            // update paticipate_at
            $pars=array(':budget_id'=>$budget_id,':merge_with'=>$merge_with);
            $query="UPDATE ".table('participate_at')."
                    SET payment_budget= :merge_with
                    WHERE payment_budget= :budget_id";
            $done=or_query($query,$pars);

            // update sessions
            $upars=array();
            $pars=array(':payment_budget'=>'%|'.$budget_id.'|%');
            $query="SELECT session_id, payment_budgets
                    FROM ".table('sessions')."
                    WHERE payment_budgets LIKE :payment_budget";
            $result=or_query($query,$pars);
            while ($line=pdo_fetch_assoc($result)) {
                $ids=db_string_to_id_array($line['payment_budgets']);
                foreach ($ids as $k=>$v) if ($v==$budget_id) unset($ids[$k]);
                if (!in_array($merge_with,$ids)) $ids[]=$merge_with;
                $upars[]=array(
                            ':session_id'=>$line['session_id'],
                            ':payment_budgets'=>id_array_to_db_string($ids)
                                );
            }
            $query="UPDATE ".table('sessions')."
                    SET payment_budgets= :payment_budgets
                    WHERE session_id= :session_id";
            $done=or_query($query,$upars);

            // update experiments
                        $upars=array();
            $pars=array(':payment_budget'=>'%|'.$budget_id.'|%');
            $query="SELECT experiment_id, payment_budgets
                    FROM ".table('experiments')."
                    WHERE payment_budgets LIKE :payment_budget";
            $result=or_query($query,$pars);
            while ($line=pdo_fetch_assoc($result)) {
                $ids=db_string_to_id_array($line['payment_budgets']);
                foreach ($ids as $k=>$v) if ($v==$budget_id) unset($ids[$k]);
                if (!in_array($merge_with,$ids)) $ids[]=$merge_with;
                $upars[]=array(
                            ':experiment_id'=>$line['experiment_id'],
                            ':payment_budgets'=>id_array_to_db_string($ids)
                                );
            }
            $query="UPDATE ".table('experiments')."
                    SET payment_budgets= :payment_budgets
                    WHERE experiment_id= :experiment_id";
            $done=or_query($query,$upars);

            // delete from budgets
            $pars=array(':budget_id'=>$budget_id);
            $query="DELETE FROM ".table('budgets')."
                    WHERE budget_id= :budget_id";
            $result=or_query($query,$pars);

            log__admin("payments_budget_delete","budget_id:".$budget['budget_id'].", merge_with:".$merge_with);
            message (lang('payments_budget_deleted_exp_sess_part_moved_to').' "'.$budgets[$merge_with]['budget_name'].'".');
            redirect ("admin/payments_budget_main.php");
        }
    }
}


if ($proceed) {
    // form

    echo '  <CENTER>
            <FORM action="payments_budget_delete.php">
            <INPUT type="hidden" name="budget_id" value="'.$budget_id.'">
            <TABLE class="or_formtable">
                <TR><TD colspan="2">
                    <TABLE width="100%" border=0 class="or_panel_title"><TR>
                            <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
                                '.lang('delete_budget').' "'.$budget['budget_name'].'"
                            </TD>
                    </TR></TABLE>
                </TD></TR>
                <TR>
                    <TD colspan=2>'.lang('really_delete_budget?').'<BR><BR>';
    dump_array($budget);
    echo '          </TD>
                </TR>
                <TR>
                <TD align=left colspan=2>
                '.lang('merge_budget_with').'
                '.payments__budget_selectfield('merge_with','',array($budget_id)).'
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