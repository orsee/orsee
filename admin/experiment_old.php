<?php
ob_start();

$menu__area="experiments_old";
$title="old experiments";
include("header.php");

	if ($_REQUEST['class']) $tclass=$_REQUEST['class']; else $tclass="";

        experiment__current_experiment_summary("","y",true);


include("footer.php");

?>
