<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="statistics";
$title="budget_reports";
include ("header.php");
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

    if (count($budgets)==0) {
        message(lang('no_budgets_available_for_view'));
        redirect('admin/statistics_main.php');
    }
}

if ($proceed) {

    //load summary stats
    $query="SELECT sum(payment_amt) as total_payment,
            payment_budget, payment_type
            FROM ".table('participate_at')."
            WHERE payment_budget IN (".implode(",",$budget_ids).")
            AND session_id IN (
                SELECT session_id FROM ".table('sessions')."
                WHERE session_status='balanced')
            GROUP BY payment_budget, payment_type";
    $result=or_query($query);
    while ($line = pdo_fetch_assoc($result)) {
        $budgets[$line['payment_budget']]['payments'][$line['payment_type']]=$line['total_payment'];
    }


    echo '<center>';

    echo '<BR>
        <table class="or_listtable" style="width: 80%;"><thead>
            <TR style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">
                <TD>'.lang('id').'</TD>
                <TD>'.lang('enabled?').'</TD>
                <TD>'.lang('name').'</TD>
                <TD>'.lang('experimenter').'</TD>
                <TD>'.lang('budget_limit').'</TD>
                <TD>'.lang('total_payment').'</TD>';
                echo '<TD></TD>';
    echo '
            </TR></thead>
            <tbody>';

    $payment_types=payments__load_paytypes();
    $shade=false;
    foreach ($budgets as $line) {
        echo '  <tr class="small"';
        if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
        else echo ' bgcolor="'.$color['list_shade2'].'"';
        if (!$line['enabled']) echo ' style="font-style: italic;"';
        echo '>
                <TD valign=top>'.$line['budget_id'].'</TD>
                <TD valign=top>'.($line['enabled']?lang('y'):lang('n')).'</TD>
                <td valign=top>'.$line['budget_name'].'</td>
                <td valign=top>'.experiment__list_experimenters($line['experimenter'],false,true).'</td>
                <td valign=top>'.$line['budget_limit'].'</td>';
        echo '<td valign=top>';
        if (isset($line['payments'])) {
            $paystring=array();
            foreach ($line['payments'] as $paytype=>$payamount) {
                $paystring[]=$payment_types[$paytype].': '.or__format_number($payamount,2);
            }
            echo implode("<BR>",$paystring);
        } else {
            echo '0.00';
        }
        echo '</td>';
        echo '<TD><A HREF="payments_budget_view_details.php?budget_id='.$line['budget_id'].'">'.lang('view_details').'</A>
                </TD>';
        echo '</tr>';
        if ($shade) $shade=false; else $shade=true;
    }
   echo '</tbody></table>';

   echo '<BR><BR>
                <A href="statistics_main.php">'.icon('back').' '.lang('back').'</A><BR><BR>';

   echo '</CENTER>';

}
include ("footer.php");
?>