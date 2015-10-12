<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="participant_statuses";
include ("header.php");
if ($proceed) {
    $allow=check_allow('participantstatus_edit','options_main.php');
}

if ($proceed) {
    echo '<center>';

    if (check_allow('participantstatus_add')) {
        echo '
                <BR>
                '.button_link('participant_status_edit.php?addit=true',
                        lang('create_new'),'plus-circle');
    }

    echo '<BR>
        <table class="or_listtable" style="width: 80%;"><thead>
            <TR style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">
                <TD>'.lang('id').'</TD>
                <TD>'.lang('name').'
                <TD>'.lang('access_to_profile').'</TD>
                <TD>'.lang('eligible_for_experiments').'</TD>
                <TD>'.lang('default_for_active_participants').'</TD>
                <TD>'.lang('default_for_inactive_participants').'</TD>
                <TD>'.lang('subjects').'</TD>';
    if (check_allow('participantstatus_edit')) echo '<TD></TD>';
    echo '
            </TR></thead>
            <tbody>';

    // load status names from lang table
    $status_names=lang__load_lang_cat('participant_status_name');

    // load participant numbers
    $query="SELECT count(*) as status_count, status_id
            FROM ".table('participants')."
            GROUP BY status_id";
    $result=or_query($query); $status_counts=array();
    while ($line=pdo_fetch_assoc($result)) {
        $status_counts[$line['status_id']]=$line['status_count'];
    }

    $query="SELECT *
            FROM ".table('participant_statuses')."
            ORDER BY status_id";
    $result=or_query($query);
    $shade=false;
    while ($line=pdo_fetch_assoc($result)) {
        echo '  <tr class="small"';
        if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
        else echo ' bgcolor="'.$color['list_shade2'].'"';
        echo '>
                <TD>'.$line['status_id'].'</TD>
                <td valign=top>'.$status_names[$line['status_id']].'</td>
                <TD>'.$line['access_to_profile'].'</TD>
                <TD>'.$line['eligible_for_experiments'].'</TD>
                <TD>';
        if ($line['is_default_active']=='y') echo '<B>'.lang('y').'</B>';
        echo '</TD>
                <TD>';
        if ($line['is_default_inactive']=='y') echo '<B>'.lang('y').'</B>';
        echo '</TD>
                <TD>';
        if (isset($status_counts[$line['status_id']])) echo $status_counts[$line['status_id']];
        else echo '0';
        echo '</TD>';
        if (check_allow('participantstatus_edit')) {
            echo '<td valign=top>';
            echo '<A HREF="participant_status_edit.php?status_id='.$line['status_id'].'">'.lang('edit').'</A>';
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