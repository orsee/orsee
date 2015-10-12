<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="experiments_old";
$title="finished_experiments";
$jquery=array('arraypicker','textext');
include("header.php");
if ($proceed) {

    if (isset($_REQUEST['class']) && $_REQUEST['class']) $tclass=$_REQUEST['class']; else $tclass="";

    experiment__current_experiment_summary("","y",true);

}
include("footer.php");
?>