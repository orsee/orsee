<?php
ob_start();

$menu__area="statistics";
$title="system statistics";

include("header.php");

	$allow=check_allow('statistics_system_show','statistics_main.php');

	echo '<center>
                <BR><BR>
                        <h4>'.$lang['system_statistics'].'</h4>';

	if ($settings['stats_type']=='text') {
		echo '<TABLE><TR><TD><pre>';
		echo stats__textstats_all();
		echo '</pre></TD></TR></TABLE>';
		}
	elseif ($settings['stats_type']=='plots') {
		echo stats__system_graphstats_all();
		}
	elseif ($settings['stats_type']=='both') {
                echo stats__system_htmlgraphstats_all();
                }
	else {
		echo stats__system_htmlstats_all();
		}

	echo '<BR><BR><A href="statistics_main.php">'.icon('back').' '.$lang['back'].'</A><BR><BR>';
	echo '</center>';

include("footer.php");
