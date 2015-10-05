<?php
// part of orsee. see orsee.org
ob_start();

$title="log_files";
$menu__area="statistics";
include ("header.php");
if ($proceed) {
    $limit=$settings['stats_logs_results_per_page'];
    if ($_REQUEST['log']) $log=$_REQUEST['log']; else redirect("admin/statistics.php");
}

if ($proceed) {
    $allow=check_allow('log_file_'.$log.'_show','statistics_main.php');
}

if ($proceed) {
    echo '<center>';
    echo '<TABLE class="or_page_subtitle" style="background: '.$color['page_subtitle_background'].'; color: '.$color['page_subtitle_textcolor'].'">
            <TR><TD align="center">
            '.lang('log_files').' '.lang($log).'
            </TD>';
    echo '</TR></TABLE><br>';

    $num_rows=log__show_log($log);

    echo '<BR><BR><A href="statistics_main.php">'.icon('back').' '.lang('back').'</A><BR><BR>';

    echo '</center>';

}
include ("footer.php");
?>