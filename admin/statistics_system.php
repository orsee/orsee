<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="statistics";
$title="system_statistics";
include("header.php");
if ($proceed) {
    $allow=check_allow('statistics_system_show','statistics_main.php');
}

if ($proceed) {
    echo '<center>';

    $data['participant_actions']=stats__get_participant_action_data();
    $_SESSION['stats_data']=$data;

    $out=stats__stats_display_table($data['participant_actions']);
    echo '<TABLE class="or_formtable" style="width: 90%">
            <TR><TD colspan="2">
                <TABLE width="100%" border=0 class="or_panel_title"><TR>
                    <TD style="background: '.$color['panel_title_background'].'; color: '.$color['panel_title_textcolor'].'" align="center">
                        '.$data['participant_actions']['title'].'
                    </TD>
                </TR></TABLE>
            </TD></TR>';
    echo '<TR>';
    echo '<TD valign="top" align="left" style="width: 50%">'.$out.'</TD>';
    echo '<TD valign="top" align="center" style="width: 50%">';
        echo '<img border="0" src="statistics_graph.php?stype=participant_actions">';
    echo '</td>';
    echo '</TR>';
    echo '</TABLE>';

    echo '<BR><BR><A href="statistics_main.php">'.icon('back').' '.lang('back').'</A><BR><BR>';
    echo '</center>';

}
include("footer.php");
?>