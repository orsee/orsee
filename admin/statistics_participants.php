<?php
ob_start();

$menu__area="statistics";
$title="participant statistics";

include("header.php");

	$allow=check_allow('statistics_participants_show','statistics_main.php');

	if (isset($_REQUEST['subpool_id']) && $_REQUEST['subpool_id']) 
		$subpool_id=$_REQUEST['subpool_id']; else $subpool_id='';

	echo '<center>
                <BR><BR>
                        <h4>'.$lang['participant_statistics'].'</h4>';

	if ($settings['stats_type']=='text') {
		echo '<TABLE><TR><TD><pre>';
		echo stats__textstats_all();
		echo '</pre></TD></TR></TABLE>';
		}
	elseif ($settings['stats_type']=='plots') {
		echo stats__participant_graphstats_all();
		}
	elseif ($settings['stats_type']=='both') {
                echo stats__participant_htmlgraphstats_all();
                }
	else {
		echo stats__participant_htmlstats_all();
		}

	echo '<BR><BR><A href="statistics_main.php">'.icon('back').' '.$lang['back'].'</A><BR><BR>';
	echo '</center>';

include("footer.php");
