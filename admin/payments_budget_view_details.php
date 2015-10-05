<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="statistics";
$title="budget_report";
include ("header.php");
if ($proceed) {

    if (isset($_REQUEST['budget_id'])) $budget_id=$_REQUEST['budget_id'];
    else redirect('admin/statistics_main.php');
}

if ($proceed) {
    if (!(check_allow('payments_budget_view_my') || check_allow('payments_budget_view_all')))
        redirect('admin/statistics_main.php');
}

if ($proceed) {

    if (check_allow('payments_budget_view_all')) {
        $restriction=""; $pars=array();
    } else {
        $pars=array(':adminname'=>'%|'.$expadmindata['adminname']).'|%';
        $restriction=" experimenter LIKE :adminname ";
    }

    // get budgets
    $query="SELECT * FROM ".table('budgets')." ".$restriction."
            ORDER BY enabled DESC, budget_name";
    $result=or_query($query,$pars);
    $shade=false; $budgets=array(); $budget_ids=array();
    while ($line = pdo_fetch_assoc($result)) {
        $budgets[$line['budget_id']]=$line;
        $budget_ids[]=$line['budget_id'];
    }

    if (!in_array($budget_id,$budget_ids)) {
        redirect('admin/payment_budget_view.php');
    }
}

if ($proceed) {
    $budget=$budgets[$budget_id];

    //load data
    $pars=array(':budget_id'=>$budget_id);
    $query="SELECT * FROM ".table('participate_at')." as p,
            ".table('sessions')." as s, ".table('experiments')." as e
            WHERE p.payment_budget = :budget_id
            AND p.session_id=s.session_id
            AND s.session_status='balanced'
            AND p.experiment_id=e.experiment_id
            ORDER BY s.session_start, p.payment_type";
    $result=or_query($query,$pars); $payments=array();
    while ($line = pdo_fetch_assoc($result)) {
        $payments[$line['experiment_id']][$line['session_id']][$line['payment_type']][]=$line;
    }


    echo '<center>';

    echo '<TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'; width: auto;">
            <TR><TD align="center">
            '.lang('budget_report').' '.$budget['budget_name'].'
            </TD>';
    echo '</TR></TABLE>';

    echo '<BR>
        <table class="or_listtable" style="width: auto;">';

    $payment_types=payments__load_paytypes();
    $cexp_id=''; $csess_id=''; $cpaytype_id='';
    $sum_exp=0; $sum_sess=0; $sum_paytype=0; $pid=0;
    foreach ($payments as $exp_id=>$exp) {
        $csess_id='';
        foreach ($exp as $sess_id=>$sess) {
            $cpaytype_id='';
            foreach ($sess as $paytype_id=>$paytype) {
                foreach ($paytype as $p) {
                    $pid++;
                    if ($cexp_id!=$exp_id) {
                        echo '<TR style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">
                                <TD colspan=8><B>'.$p['experiment_name'].'</B></TD>
                                </TR>';
                        $cexp_id=$exp_id;
                    }
                    if ($csess_id!=$sess_id) {
                        echo '<TR bgcolor="'.$color['list_shade1'].'">
                                <TD>&nbsp;&nbsp;</TD>
                                <TD colspan=6>'.session__build_name($p).'</TD>
                                <TD style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">&nbsp;</TD>
                                </TR>';
                        $csess_id=$sess_id;
                    }
                    if ($cpaytype_id!=$paytype_id) {
                        echo '<TR>
                                <TD bgcolor="'.$color['list_shade2'].'">&nbsp;</TD>
                                <TD bgcolor="'.$color['list_shade2'].'">&nbsp;&nbsp;</TD>
                                <TD bgcolor="'.$color['list_shade2'].'" colspan=4>'.$payment_types[$p['payment_type']].'</TD>
                                <TD bgcolor="'.$color['list_shade1'].'">&nbsp;</TD>
                                <TD style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">&nbsp;</TD>
                                </TR>';
                        $cpaytype_id=$paytype_id;
                    }
                    echo '<TR>
                                <TD colspan=2></TD>
                                <TD>&nbsp;&nbsp;</TD>
                                <TD>'.lang('participant').'&nbsp;'.$pid.'&nbsp;&nbsp;</TD>
                                <TD align="right">'.or__format_number($p['payment_amt'],2).'</TD>
                                <TD bgcolor="'.$color['list_shade2'].'">&nbsp;</TD>
                                <TD bgcolor="'.$color['list_shade1'].'">&nbsp;</TD>
                                <TD style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">&nbsp;</TD>
                                </TR>';
                    $sum_exp+=$p['payment_amt']; $sum_sess+=$p['payment_amt']; $sum_paytype+=$p['payment_amt'];
                }
                echo '<TR>
                        <TD colspan=2></TD>
                        <TD colspan=3 style="border-bottom: 1px solid black;">&nbsp;</TD>
                        <TD bgcolor="'.$color['list_shade2'].'" align="right" style="border-bottom: 1px solid black;">&nbsp;&nbsp;&nbsp;&nbsp;<B>'.or__format_number($sum_paytype,2).'</B></TD>
                        <TD bgcolor="'.$color['list_shade1'].'">&nbsp;</TD>
                        <TD style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">&nbsp;</TD>
                        </TR>';
                $sum_paytype=0;
            }
            echo '<TR>
                    <TD colspan=1></TD>
                    <TD colspan=5 style="border-bottom: 1px solid black;">&nbsp;</TD>
                    <TD bgcolor="'.$color['list_shade1'].'" align="right" style="border-bottom: 1px solid black;">&nbsp;&nbsp;&nbsp;&nbsp;<B>'.or__format_number($sum_sess,2).'</B></TD>
                    <TD style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">&nbsp;</TD>
                    </TR>';
            $sum_sess=0;
        }
        echo '<TR>
                <TD colspan=7 style="border-bottom: 1px solid black;">&nbsp;</TD>
                <TD style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].'; border-bottom: 1px solid black;" align="right">&nbsp;&nbsp;&nbsp;&nbsp;<B>'.or__format_number($sum_exp,2).'</B></TD>
                </TR>';
        $sum_exp=0;
   }
   echo '</table>';

   echo '<BR><BR>
                <A href="payments_budget_view.php">'.icon('back').' '.lang('back').'</A><BR><BR>';

   echo '</CENTER>';

}
include ("footer.php");
?>