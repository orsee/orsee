<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="experiments_old";
$title="old experiments";
include("header.php");

	if (isset($_REQUEST['class']) && $_REQUEST['class']) $tclass=$_REQUEST['class']; else $tclass="";

        experiment__current_experiment_summary("","y",true);


include("footer.php");

?>
