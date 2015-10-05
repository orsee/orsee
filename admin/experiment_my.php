<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="experiments_my";
$title="my_experiments";
$jquery=array('arraypicker','textext');
include("header.php");
if ($proceed) {

    experiment__current_experiment_summary($expadmindata['admin_id'],"n",true);
    experiment__current_experiment_summary($expadmindata['admin_id'],"y",false,false);

}
include("footer.php");
?>