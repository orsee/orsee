<?php
ob_start();

$title="show log file";
$menu__area="statistics";
include ("header.php");

	$limit=$settings['stats_logs_results_per_page'];

	if ($_REQUEST['log']) $log=$_REQUEST['log']; else redirect("admin/statistics.php");

	$allow=check_allow('log_file_'.$log.'_show','statistics_main.php');

	echo '	<center>
		<BR><BR>
		<h3>'.$lang['log_files'].': '.$lang[$log].'</h3>
		';

	$num_rows=log__show_log($log);


	echo '<BR><BR><A href="statistics_main.php">'.icon('back').' '.$lang['back'].'</A><BR><BR>';

	echo '</center>';

include ("footer.php");

?>
