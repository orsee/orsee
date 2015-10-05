<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="budgets";
include ("header.php");
if ($proceed) {
    $allow=check_allow('payments_budget_edit','options_main.php');
}

if ($proceed) {
    echo '<center>';

    if (check_allow('payments_budget_add')) {
        echo '
                <BR>
                '.button_link('payments_budget_edit.php?addit=true',
                        lang('create_new'),'plus-circle');
    }

    echo '<BR>
        <table class="or_listtable" style="width: 80%;"><thead>
            <TR style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">
                <TD>'.lang('id').'</TD>
                <TD>'.lang('enabled?').'</TD>
                <TD>'.lang('name').'</TD>
                <TD>'.lang('experimenter').'</TD>
                <TD>'.lang('budget_limit').'</TD>';
    if (check_allow('payments_budget_edit')) echo '<TD></TD>';
    echo '
            </TR></thead>
                <tbody>';

    $query="SELECT * FROM ".table('budgets')."
            ORDER BY enabled DESC, budget_name";
    $result=or_query($query);
    $shade=false;
    while ($line = pdo_fetch_assoc($result)) {
        echo '  <tr class="small"';
        if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
        else echo ' bgcolor="'.$color['list_shade2'].'"';
        if (!$line['enabled']) echo ' style="font-style: italic;"';
        echo '>
                <TD>'.$line['budget_id'].'</TD>
                <TD>'.($line['enabled']?lang('y'):lang('n')).'</TD>
                <td>'.$line['budget_name'].'</td>
                <td>'.experiment__list_experimenters($line['experimenter'],false,true).'</td>
                <td>'.$line['budget_limit'].'</td>';
        if (check_allow('payments_budget_edit')) {
            echo '<td valign=top>';
            echo '<A HREF="payments_budget_edit.php?budget_id='.$line['budget_id'].'">'.lang('edit').'</A>';
            echo '</td>';
        }
        echo '</tr>';
        if ($shade) $shade=false; else $shade=true;
    }
   echo '</tbody></table>';

   echo '<BR><BR>
                <A href="options_main.php">'.icon('back').' '.lang('back').'</A><BR><BR>';

   echo '</CENTER>';



}
include ("footer.php");
?>