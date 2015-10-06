<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="experiment_types";
include ("header.php");
if ($proceed) {
    $allow=check_allow('experimenttype_edit','options_main.php');
}

if ($proceed) {
    echo '<center>';
    if (check_allow('experimenttype_add')) {
        echo '<BR>
              '.button_link('experiment_type_edit.php?addit=true',
                        lang('create_new'),'plus-circle');
    }

    echo '<BR><BR>
        <table class="or_listtable"><thead>
            <TR style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">
                <TD>'.lang('id').'</TD>
                <TD>'.lang('name').'</TD>
                <TD>'.lang('description').'</TD>
                <TD>'.lang('assigned_internal_experiment_types').'</TD>
                <TD>'.lang('registered_for_xxx_experiments_xxx').'</TD>
                <TD></TD>
            </TR></thead>
            <tbody>';

    $query="SELECT *
            FROM ".table('experiment_types')."
            ORDER BY exptype_id";
    $result=or_query($query);

    $shade=false;
    while ($line=pdo_fetch_assoc($result)) {
        $count=participants__count_participants(" subscriptions LIKE :exptype_id",array(':exptype_id'=>'%|'.$line['exptype_id'].'|%'));
        echo '  <tr class="small"';
        if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
        else echo ' bgcolor="'.$color['list_shade2'].'"';
        echo '>
                <td>'.$line['exptype_id'].'</td>
                <td>'.$line['exptype_name'].'</td>
                <td>'.$line['exptype_description'].'</td>
                <td>'.$line['exptype_mapping'].'</td>
                <td>'.$count.'</td>
                <td><A HREF="experiment_type_edit.php?exptype_id='.$line['exptype_id'].'">'.lang('edit').'</A></td>
            </tr>';
        if ($shade) $shade=false; else $shade=true;
    }
    echo '</tbody></table>
        </CENTER>';

}
include ("footer.php");
?>