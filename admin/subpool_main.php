<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="sub_subjectpools";
include ("header.php");
if ($proceed) {
    $allow=check_allow('subjectpool_edit','options_main.php');
}

if ($proceed) {
    echo '<center>';

    if (check_allow('subjectpool_add')) {
        echo '<BR>'.button_link('subpool_edit.php?addit=true',
                        lang('create_new'),'plus-circle');
    }

    echo '<BR><BR>
        <table class="or_listtable"><thead>
            <TR style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">
                <TD>'.lang('id').'</TD>
                <TD>'.lang('name').'
                <TD>'.lang('description').'</TD>
                <TD>'.lang('subjects').'</TD>
                <TD></TD>
            </TR></thead>
            <tbody>';

    $part_counts=array();
    $query="SELECT count(*) as part_count, subpool_id FROM ".table('participants')." GROUP BY subpool_id";
    $result=or_query($query);
    while ($line=pdo_fetch_assoc($result)) $part_counts[$line['subpool_id']]=$line['part_count'];

    $query="SELECT * FROM ".table('subpools')." ORDER BY subpool_id";
    $result=or_query($query);

    $shade=false;
    while ($line=pdo_fetch_assoc($result)) {
        echo '  <tr class="small"';
        if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
        else echo ' bgcolor="'.$color['list_shade2'].'"';
        echo '>
                    <TD>'.$line['subpool_id'].'</TD>
                    <td valign=top>'.$line['subpool_name'].'</td>
                    <TD>'.$line['subpool_description'].'</TD>
                    <TD>';
        if (isset($part_counts[$line['subpool_id']])) echo $part_counts[$line['subpool_id']];
        else echo '0';
        echo '      </TD>
                    <td valign=top>
                    <A HREF="subpool_edit.php?subpool_id='.$line['subpool_id'].'">
                                        '.lang('edit').'</A>
                    </td>
                </tr>';
        if ($shade) $shade=false; else $shade=true;
    }

    echo '</tbody></table>
           </CENTER>';

}
include ("footer.php");
?>