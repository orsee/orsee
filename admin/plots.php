<?php
ob_start();
 
$menu__area="participants";
$title="participant statistics";

include("header.php");

	echo '<BR><BR><center>';

	echo '</center><pre>';
	var_dump(gd_info());
	echo stats__textstats_all();
	echo '</pre><center>';

	echo stats__htmlstats_all();

	echo stats__graphstats_all();

	echo '</center>';

include("footer.php");

?>
