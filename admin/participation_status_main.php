<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="experiment_participation_statuses";
include ("header.php");
if ($proceed) {
    $allow=check_allow('participationstatus_edit','options_main.php');
}

if ($proceed) {
    echo '<center>';

    if (check_allow('participationstatus_add')) echo '
                <BR>'.button_link('participation_status_edit.php?addit=true',
                        lang('create_new'),'plus-circle');

    echo '<BR>
            <table class="or_listtable" style="width: 80%;"><thead>
                <TR style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">
                    <TD>'.lang('id').'</TD>
                    <TD>'.lang('internal_name').'
                    <TD>'.lang('counts_as_participated').'</TD>
                    <TD>'.lang('counts_as_noshow').'</TD>';
    // echo '               <TD>'.lang('allows_to_participate_again_short').'</TD>';
    echo '          <TD>'.lang('cases').'</TD>';
    if (check_allow('participationstatus_edit')) echo '<TD></TD>';
    echo '      </TR></thead>
                <tbody>';

    // load status names from lang table
    $status_names=lang__load_lang_cat('participation_status_internal_name');

    // load participant numbers
    $query="SELECT count(*) as pstatus_count, pstatus_id
            FROM ".table('participate_at')."
            GROUP BY pstatus_id";
    $result=or_query($query); $status_counts=array();
    while ($line=pdo_fetch_assoc($result)) {
        $status_counts[$line['pstatus_id']]=$line['pstatus_count'];
    }

    $query="SELECT *
            FROM ".table('participation_statuses')."
            ORDER BY pstatus_id";
    $result=or_query($query);

    $shade=false;
    while ($line=pdo_fetch_assoc($result)) {
        echo '  <tr class="small"';
        if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
        else echo ' bgcolor="'.$color['list_shade2'].'"';
        echo '>
                <TD>'.$line['pstatus_id'].'</TD>
                <td valign=top>'.$status_names[$line['pstatus_id']].'</td>
                <TD>'.($line['participated']?lang('y'):lang('n')).' </TD>
                <TD>'.($line['noshow']?lang('y'):lang('n')).'</TD>';
        // echo '       <TD>'.($line['participateagain']?lang('y'):lang('n')).'</TD>';
        echo '  <TD>';
        if (isset($status_counts[$line['pstatus_id']])) echo $status_counts[$line['pstatus_id']];
        else echo '0';
        echo '</TD>';
        if (check_allow('participationstatus_edit')) {
            echo '<td valign=top>';
            echo '<A HREF="participation_status_edit.php?pstatus_id='.$line['pstatus_id'].'">
                                        '.lang('edit').'</A>';
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