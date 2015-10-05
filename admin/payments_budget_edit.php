<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="edit_budget";
$jquery=array('arraypicker','textext');
include ("header.php");
if ($proceed) {

    if (isset($_REQUEST['budget_id'])) $budget_id=$_REQUEST['budget_id'];

    if (isset($budget_id)) $allow=check_allow('payments_budget_edit','payments_budget_main.php');
    else $allow=check_allow('payments_budget_add','payments_budget_main.php');
}

if ($proceed) {

    if (isset($budget_id)) {
        $budget=orsee_db_load_array("budgets",$budget_id,"budget_id");
        if (!isset($budget['budget_id'])) redirect ('admin/payments_budget_main.php');
    } else {
        $budget=array('budget_name'=>'','budget_limit'=>'','enabled'=>0,'experimenter'=>'');
    }
}

if ($proceed) {
    $continue=true;

    if (isset($_REQUEST['edit']) && $_REQUEST['edit']) {

        if (!isset($_REQUEST['budget_name']) || !$_REQUEST['budget_name']) {
                    message (lang('error_you_have_to_provide_budget_name'));
                    $continue=false;
        }

        if ($continue) {
            $_REQUEST['experimenter']=id_array_to_db_string(multipicker_json_to_array($_REQUEST['experimenter']));

            if (!isset($budget_id)) {
                $new=true;
                $query="SELECT max(budget_id)+1 as new_budget_id FROM ".table('budgets');
                $line=orsee_query($query);
                if (isset($line['new_budget_id'])) $budget_id=$line['new_budget_id'];
                else $budget_id=1;
            } else {
                $new=false;
            }

            $budget=$_REQUEST;
            $budget['budget_id']=$budget_id;
            if (!$budget['budget_limit']) $budget['budget_limit']=NULL;
            $done=orsee_db_save_array($budget,"budgets",$budget_id,"budget_id");

            message (lang('changes_saved'));
            log__admin("payments_budget_edit","budget_id:".$budget['budget_id']);
            //redirect ("admin/payments_budget_edit.php?budget_id=".$budget_id);
        } else {
                $budget=$_REQUEST;
        }
    }
}

if ($proceed) {
    // form

    echo '<CENTER>';
    show_message();
    echo '
            <FORM action="payments_budget_edit.php">';
        if (isset($budget_id)) echo '<INPUT type=hidden name="budget_id" value="'.$budget_id.'">';

    echo '
        <TABLE class="or_formtable">
            <TR><TD colspan="2">
                <TABLE width="100%" border=0 class="or_panel_title"><TR>
                        <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
                            '.lang('edit_budget');
    if (isset($budget_id)) echo ' '.$budget['budget_name'];
    echo '
                        </TD>
                </TR></TABLE>
            </TD></TR>';
    if (isset($budget_id)) {
        echo '
            <TR>
                <TD>
                    '.lang('id').':
                </TD>
                <TD>
                    '.$budget_id.'
                </TD>
            </TR>';
    }

    echo '
        <TR>
            <TD valign="top">
                '.lang('name').':
            </TD>
            <TD>
                <INPUT name="budget_name" type=text size=40 maxlength=200 value="'.$budget['budget_name'].'">
            </TD>
        </TR>';

    echo '
        <TR>
            <TD valign="top">
                '.lang('budget_limit').':
            </TD>
            <TD>
                <INPUT name="budget_limit" type=text size=40 maxlength=200 value="'.$budget['budget_limit'].'">
            </TD>
        </TR>';

    echo '
        <TR>
            <TD valign="top">
                '.lang('experimenter').':
            </TD>
            <TD>';
    echo experiment__experimenters_select_field('experimenter',db_string_to_id_array($budget['experimenter']),true);
    echo '  </TD>
        </TR>';




    echo '<TR>
                <TD>
                    '.lang('enabled?').'
                </TD>
                <TD>
                    <INPUT type=radio name="enabled" value="1"';
                        if ($budget['enabled']) echo ' CHECKED';
                        echo '>'.lang('yes').'
                    &nbsp;&nbsp;
                    <INPUT type=radio name="enabled" value="0"';
                         if (!$budget['enabled']) echo ' CHECKED';
                        echo '>'.lang('no').'
                </TD>
            </TR>';

    echo '
            <TR>
                <TD COLSPAN=2 align=center>
                    <INPUT class="button" name="edit" type="submit" value="';
                    if (!isset($budget_id)) echo lang('add'); else echo lang('change');
                    echo '">
                </TD>
            </TR>


        </table>
        </FORM>
        <BR>';

    $payment_budgets=payments__load_budgets();
    if (isset($budget_id) && check_allow('payments_budget_delete') && count($payment_budgets)>1) {

            echo '<table>
                <TR>
                    <TD>
                        '.button_link('payments_budget_delete.php?budget_id='.urlencode($budget_id),
                            lang('delete'),'trash-o').'
                    <TD>
                </TR>
                </table>';

    }

        echo '<BR><BR>
                <A href="payments_budget_main.php">'.icon('back').' '.lang('back').'</A><BR><BR>
                </center>';

}
include ("footer.php");
?>