<?php
ob_start();

$menu__area="experiments_my";
$title="my experiments";
include("header.php");

	experiment__current_experiment_summary($expadmindata['adminname'],"n");

	experiment__current_experiment_summary($expadmindata['adminname'],"y");

include("footer.php");

?>
