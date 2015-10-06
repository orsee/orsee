<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="experiments_main";
$title="experiments";
$jquery=array('arraypicker','textext');
include("header.php");
if ($proceed) {
    experiment__current_experiment_summary("","n",true);

}
include("footer.php");
?>